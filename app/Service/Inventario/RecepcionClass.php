<?php

namespace App\Service\Inventario;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\CuentaPorPagar;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use App\Models\Recepcion;
use App\Models\RecepcionDetalle;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecepcionClass
{
    public function __construct(
        protected KardexClass $kardexService
    ) {}

    /**
     * Empresa ID activa de la sesión
     */
    protected function empresaId(): int
    {
        return (int) (Auth::user()?->empresaActiva()?->id ?? session('empresa_activa_id', 1));
    }

    /**
     * Query de lista para DataTable
     */
    public function lista(): Builder
    {
        return Recepcion::with(['proveedor', 'almacen', 'usuario', 'detalles.producto'])
            ->where('empresa_id', $this->empresaId())
            ->orderByDesc('id');
    }

    /**
     * Catálogos requeridos para el formulario de recepción
     */
    public function catalogos(): array
    {
        $empresaId = $this->empresaId();

        $proveedores = Proveedor::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'rif', 'nombre', 'razon_social', 'telefono', 'correo']);

        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'direccion']);

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        // Tasa activa de USD
        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->first();

        $tasaUsd = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;

        // Productos activos con sus códigos de barra, almacenes y márgenes
        $productos = Producto::with(['codigosBarra', 'stockAlmacenes.almacen', 'categoria'])
            ->where('empresa_id', $empresaId)
            ->where('tipo', 'producto')
            ->where('estado', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo_interno' => $p->codigo_interno,
                    'nombre' => $p->nombre,
                    'unidad_medida' => $p->unidad_medida,
                    'categoria_nombre' => $p->categoria?->nombre ?? 'General',
                    'precio_costo_usd' => (float) $p->precio_costo_usd,
                    'precio_costo_bs' => (float) $p->precio_costo_bs,
                    'precio_detal_usd' => (float) $p->precio_detal_usd,
                    'precio_detal_bs' => (float) $p->precio_detal_bs,
                    'precio_mayorista_usd' => (float) $p->precio_mayorista_usd,
                    'precio_mayorista_bs' => (float) $p->precio_mayorista_bs,
                    'ultimo_margen_detal' => (float) ($p->ultimo_margen_detal ?: 30.00),
                    'ultimo_margen_mayorista' => (float) ($p->ultimo_margen_mayorista ?: 15.00),
                    'aplica_iva' => (bool) $p->aplica_iva,
                    'iva_porcentaje' => (float) $p->iva_porcentaje,
                    'aplica_igtf' => (bool) $p->aplica_igtf,
                    'igtf_porcentaje' => (float) $p->igtf_porcentaje,
                    'codigos_barra' => $p->codigosBarra->pluck('codigo_barra')->toArray(),
                    'stock_almacenes' => $p->stockAlmacenes->map(function ($stk) {
                        return [
                            'almacen_id' => $stk->almacen_id,
                            'almacen_nombre' => $stk->almacen?->nombre,
                            'cantidad_actual' => (float) $stk->cantidad_actual,
                        ];
                    }),
                ];
            });

        return [
            'proveedores' => $proveedores,
            'almacenes' => $almacenes,
            'categorias' => $categorias,
            'tasa_usd' => $tasaUsd,
            'productos' => $productos,
            'proximo_codigo' => $this->generarCodigo($empresaId),
        ];
    }

    /**
     * Generar código correlativo de recepción (Ej: REC-00001)
     */
    public function generarCodigo(int $empresaId): string
    {
        $ultimoId = Recepcion::where('empresa_id', $empresaId)->max('id') ?? 0;
        $correlativo = str_pad($ultimoId + 1, 5, '0', STR_PAD_LEFT);

        return "REC-{$correlativo}";
    }

    /**
     * Procesar y registrar una recepción de mercancía atómica
     */
    public function guardar(array $datos): Recepcion
    {
        return DB::transaction(function () use ($datos) {
            $empresaId = $this->empresaId();
            $userId = Auth::id();

            $tasaCambio = (float) ($datos['tasa_cambio'] ?? 1.0000);
            if ($tasaCambio <= 0) {
                $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
                $tasaCambio = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;
            }

            $almacenIdGlobal = (int) $datos['almacen_id'];
            $proveedorId = (int) $datos['proveedor_id'];
            $tipoDocumento = $datos['tipo_documento'] ?? 'factura';
            $numeroDocumento = trim($datos['numero_documento'] ?? '');
            $numeroControl = trim($datos['numero_control'] ?? '') ?: null;
            $monedaDocumento = $datos['moneda_documento'] ?? 'USD';
            $fechaEmision = $datos['fecha_emision'] ?? Carbon::now()->toDateString();
            $fechaRecepcion = $datos['fecha_recepcion'] ?? Carbon::now()->toDateString();
            $condicionPago = $datos['condicion_pago'] ?? 'contado';
            $diasCredito = (int) ($datos['dias_credito'] ?? 0);
            $observaciones = $datos['observaciones'] ?? null;

            $descuentoGlobalPorcentaje = (float) ($datos['descuento_global_porcentaje'] ?? 0);

            $fechaVencimiento = null;
            if ($condicionPago === 'credito') {
                $fechaVencimiento = Carbon::parse($fechaEmision)->addDays(max(1, $diasCredito))->toDateString();
            }

            $codigo = $this->generarCodigo($empresaId);

            $detallesInput = $datos['detalles'] ?? [];
            if (empty($detallesInput) || ! is_array($detallesInput)) {
                throw new Exception('Debes incluir al menos un producto en la recepción.');
            }

            // Calcular totales de los renglones
            $montoBrutoUsd = 0;
            $subtotalNetoUsd = 0;
            $ivaTotalUsd = 0;
            $detallesAProcesar = [];

            foreach ($detallesInput as $item) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $costoUnitarioUsd = (float) ($item['costo_unitario_usd'] ?? 0);

                if ($productoId <= 0 || $cantidad <= 0 || $costoUnitarioUsd <= 0) {
                    continue;
                }

                $producto = Producto::where('id', $productoId)
                    ->where('empresa_id', $empresaId)
                    ->firstOrFail();

                $almacenItem = ! empty($item['almacen_id']) ? (int) $item['almacen_id'] : $almacenIdGlobal;
                $bultos = ! empty($item['bultos']) ? (float) $item['bultos'] : null;
                $unidadesPorBulto = ! empty($item['unidades_por_bulto']) ? (float) $item['unidades_por_bulto'] : null;
                $costoBultoUsd = (float) ($item['costo_bulto_usd'] ?? 0);
                $costoBultoBs = round($costoBultoUsd * $tasaCambio, 4);

                $costoAnteriorUsd = (float) $producto->precio_costo_usd;
                $costoAnteriorBs = (float) $producto->precio_costo_bs;
                $detalAnteriorUsd = (float) $producto->precio_detal_usd;
                $detalAnteriorBs = (float) $producto->precio_detal_bs;
                $mayoristaAnteriorUsd = (float) $producto->precio_mayorista_usd;
                $mayoristaAnteriorBs = (float) $producto->precio_mayorista_bs;

                // Descuento por producto
                $descuentoPorcentaje = (float) ($item['descuento_porcentaje'] ?? 0);
                $descuentoUsd = 0;
                $descuentoBs = 0;

                $renglonBrutoUsd = round($cantidad * $costoUnitarioUsd, 2);
                $montoBrutoUsd += $renglonBrutoUsd;

                if ($descuentoPorcentaje > 0) {
                    $descuentoUsd = round($renglonBrutoUsd * ($descuentoPorcentaje / 100), 2);
                    $descuentoBs = round($descuentoUsd * $tasaCambio, 2);
                }

                $renglonSubtotalNetoUsd = round($renglonBrutoUsd - $descuentoUsd, 2);
                $renglonSubtotalNetoBs = round($renglonSubtotalNetoUsd * $tasaCambio, 2);
                $subtotalNetoUsd += $renglonSubtotalNetoUsd;

                $costoUnitarioBs = round($costoUnitarioUsd * $tasaCambio, 4);

                // IVA por producto
                $aplicaIva = isset($item['aplica_iva']) ? (bool) $item['aplica_iva'] : (bool) $producto->aplica_iva;
                $ivaPorcentaje = $aplicaIva ? (float) ($item['iva_porcentaje'] ?? $producto->iva_porcentaje ?? 16.00) : 0;
                $ivaMontoUsd = 0;
                $ivaMontoBs = 0;

                if ($aplicaIva && $ivaPorcentaje > 0) {
                    $ivaMontoUsd = round($renglonSubtotalNetoUsd * ($ivaPorcentaje / 100), 2);
                    $ivaMontoBs = round($ivaMontoUsd * $tasaCambio, 2);
                    $ivaTotalUsd += $ivaMontoUsd;
                }

                // Precios de Venta
                $margenDetal = (float) ($item['margen_detal_porcentaje'] ?? $producto->ultimo_margen_detal ?? 30);
                $precioDetalUsd = (float) ($item['precio_detal_usd'] ?? ($costoUnitarioUsd * (1 + ($margenDetal / 100))));
                $precioDetalBs = round($precioDetalUsd * $tasaCambio, 4);

                $margenMayorista = (float) ($item['margen_mayorista_porcentaje'] ?? $producto->ultimo_margen_mayorista ?? 15);
                $precioMayoristaUsd = (float) ($item['precio_mayorista_usd'] ?? ($costoUnitarioUsd * (1 + ($margenMayorista / 100))));
                $precioMayoristaBs = round($precioMayoristaUsd * $tasaCambio, 4);

                $detallesAProcesar[] = [
                    'producto' => $producto,
                    'almacen_id' => $almacenItem,
                    'cantidad' => $cantidad,
                    'bultos' => $bultos,
                    'unidades_por_bulto' => $unidadesPorBulto,
                    'costo_bulto_usd' => $costoBultoUsd,
                    'costo_bulto_bs' => $costoBultoBs,
                    'costo_anterior_usd' => $costoAnteriorUsd,
                    'costo_anterior_bs' => $costoAnteriorBs,
                    'costo_unitario_usd' => $costoUnitarioUsd,
                    'costo_unitario_bs' => $costoUnitarioBs,
                    'descuento_porcentaje' => $descuentoPorcentaje,
                    'descuento_usd' => $descuentoUsd,
                    'descuento_bs' => $descuentoBs,
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'iva_monto_usd' => $ivaMontoUsd,
                    'iva_monto_bs' => $ivaMontoBs,
                    'precio_detal_anterior_usd' => $detalAnteriorUsd,
                    'precio_detal_anterior_bs' => $detalAnteriorBs,
                    'margen_detal_porcentaje' => $margenDetal,
                    'precio_detal_usd' => $precioDetalUsd,
                    'precio_detal_bs' => $precioDetalBs,
                    'precio_mayorista_anterior_usd' => $mayoristaAnteriorUsd,
                    'precio_mayorista_anterior_bs' => $mayoristaAnteriorBs,
                    'margen_mayorista_porcentaje' => $margenMayorista,
                    'precio_mayorista_usd' => $precioMayoristaUsd,
                    'precio_mayorista_bs' => $precioMayoristaBs,
                    'subtotal_usd' => $renglonSubtotalNetoUsd,
                    'subtotal_bs' => $renglonSubtotalNetoBs,
                ];
            }

            if (empty($detallesAProcesar)) {
                throw new Exception('No hay productos válidos con cantidades y costos mayores a cero.');
            }

            // Descuento global
            $descuentoGlobalUsd = 0;
            $descuentoGlobalBs = 0;
            if ($descuentoGlobalPorcentaje > 0) {
                $descuentoGlobalUsd = round($subtotalNetoUsd * ($descuentoGlobalPorcentaje / 100), 2);
                $descuentoGlobalBs = round($descuentoGlobalUsd * $tasaCambio, 2);
                $subtotalNetoUsd = round($subtotalNetoUsd - $descuentoGlobalUsd, 2);
            }

            $totalUsd = round($subtotalNetoUsd + $ivaTotalUsd, 2);
            $totalBs = round($totalUsd * $tasaCambio, 2);
            $montoBrutoBs = round($montoBrutoUsd * $tasaCambio, 2);

            // 1. Crear Recepción Encabezado
            $recepcion = Recepcion::create([
                'empresa_id' => $empresaId,
                'almacen_id' => $almacenIdGlobal,
                'proveedor_id' => $proveedorId,
                'user_id' => $userId,
                'codigo' => $codigo,
                'tipo_documento' => $tipoDocumento,
                'numero_documento' => $numeroDocumento,
                'numero_control' => $numeroControl,
                'fecha_emision' => $fechaEmision,
                'fecha_recepcion' => $fechaRecepcion,
                'condicion_pago' => $condicionPago,
                'dias_credito' => $diasCredito,
                'fecha_vencimiento' => $fechaVencimiento,
                'tasa_cambio' => $tasaCambio,
                'moneda_documento' => $monedaDocumento,
                'monto_bruto_usd' => $montoBrutoUsd,
                'monto_bruto_bs' => $montoBrutoBs,
                'descuento_global_porcentaje' => $descuentoGlobalPorcentaje,
                'descuento_global_usd' => $descuentoGlobalUsd,
                'descuento_global_bs' => $descuentoGlobalBs,
                'subtotal_usd' => $subtotalNetoUsd,
                'iva_usd' => $ivaTotalUsd,
                'total_usd' => $totalUsd,
                'total_bs' => $totalBs,
                'observaciones' => $observaciones,
                'estado' => 'procesada',
            ]);

            // 2. Procesar Detalles, Kardex y Precios del Producto
            foreach ($detallesAProcesar as $d) {
                /** @var Producto $producto */
                $producto = $d['producto'];
                $itemAlmacenId = $d['almacen_id'];

                RecepcionDetalle::create([
                    'recepcion_id' => $recepcion->id,
                    'producto_id' => $producto->id,
                    'almacen_id' => $itemAlmacenId,
                    'cantidad' => $d['cantidad'],
                    'bultos' => $d['bultos'],
                    'unidades_por_bulto' => $d['unidades_por_bulto'],
                    'costo_bulto_usd' => $d['costo_bulto_usd'],
                    'costo_bulto_bs' => $d['costo_bulto_bs'],
                    'costo_anterior_usd' => $d['costo_anterior_usd'],
                    'costo_anterior_bs' => $d['costo_anterior_bs'],
                    'costo_unitario_usd' => $d['costo_unitario_usd'],
                    'costo_unitario_bs' => $d['costo_unitario_bs'],
                    'descuento_porcentaje' => $d['descuento_porcentaje'],
                    'descuento_usd' => $d['descuento_usd'],
                    'descuento_bs' => $d['descuento_bs'],
                    'aplica_iva' => $d['aplica_iva'],
                    'iva_porcentaje' => $d['iva_porcentaje'],
                    'iva_monto_usd' => $d['iva_monto_usd'],
                    'iva_monto_bs' => $d['iva_monto_bs'],
                    'precio_detal_anterior_usd' => $d['precio_detal_anterior_usd'],
                    'precio_detal_anterior_bs' => $d['precio_detal_anterior_bs'],
                    'margen_detal_porcentaje' => $d['margen_detal_porcentaje'],
                    'precio_detal_usd' => $d['precio_detal_usd'],
                    'precio_detal_bs' => $d['precio_detal_bs'],
                    'precio_mayorista_anterior_usd' => $d['precio_mayorista_anterior_usd'],
                    'precio_mayorista_anterior_bs' => $d['precio_mayorista_anterior_bs'],
                    'margen_mayorista_porcentaje' => $d['margen_mayorista_porcentaje'],
                    'precio_mayorista_usd' => $d['precio_mayorista_usd'],
                    'precio_mayorista_bs' => $d['precio_mayorista_bs'],
                    'subtotal_usd' => $d['subtotal_usd'],
                    'subtotal_bs' => $d['subtotal_bs'],
                ]);

                // Actualizar Catálogo de Producto (Precios y Memoria de Margen)
                $producto->precio_costo_usd = $d['costo_unitario_usd'];
                $producto->precio_costo_bs = $d['costo_unitario_bs'];
                $producto->precio_detal_usd = $d['precio_detal_usd'];
                $producto->precio_detal_bs = $d['precio_detal_bs'];
                $producto->precio_mayorista_usd = $d['precio_mayorista_usd'];
                $producto->precio_mayorista_bs = $d['precio_mayorista_bs'];
                $producto->ultimo_margen_detal = $d['margen_detal_porcentaje'];
                $producto->ultimo_margen_mayorista = $d['margen_mayorista_porcentaje'];
                $producto->save();

                // Vincular o actualizar automáticamente el Proveedor en el catálogo del Producto
                ProductoProveedor::updateOrCreate(
                    [
                        'producto_id' => $producto->id,
                        'proveedor_id' => $proveedorId,
                    ],
                    [
                        'ultimo_costo_usd' => $d['costo_unitario_usd'],
                        'ultimo_costo_bs' => $d['costo_unitario_bs'],
                    ]
                );

                // Registrar Kardex y Aumentar Stock en el Almacén específico del ítem
                $this->kardexService->registrarMovimiento([
                    'empresa_id' => $empresaId,
                    'almacen_id' => $itemAlmacenId,
                    'producto_id' => $producto->id,
                    'user_id' => $userId,
                    'tipo_movimiento' => 'entrada_recepcion',
                    'documento_tipo' => 'recepcion',
                    'documento_id' => $recepcion->id,
                    'cantidad' => $d['cantidad'],
                    'costo_unitario_usd' => $d['costo_unitario_usd'],
                    'costo_unitario_bs' => $d['costo_unitario_bs'],
                    'motivo' => "Recepción {$codigo} - Factura {$numeroDocumento}",
                ]);
            }

            // 3. Crear Cuenta por Pagar si fue a Crédito
            if ($condicionPago === 'credito') {
                CuentaPorPagar::create([
                    'empresa_id' => $empresaId,
                    'proveedor_id' => $proveedorId,
                    'recepcion_id' => $recepcion->id,
                    'numero_factura' => $numeroDocumento,
                    'fecha_emision' => $fechaEmision,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total_usd' => $totalUsd,
                    'monto_total_bs' => $totalBs,
                    'monto_pagado_usd' => 0,
                    'monto_pagado_bs' => 0,
                    'saldo_pendiente_usd' => $totalUsd,
                    'saldo_pendiente_bs' => $totalBs,
                    'estado' => 'pendiente',
                    'observaciones' => "Generado automáticamente por Recepción {$codigo}",
                ]);
            }

            return $recepcion->load(['proveedor', 'almacen', 'usuario', 'detalles.producto', 'detalles.almacen']);
        });
    }

    /**
     * Consultar detalles 360° de una recepción
     */
    public function detalles(int $id): Recepcion
    {
        return Recepcion::with([
            'proveedor',
            'almacen',
            'usuario',
            'detalles.producto.categoria',
            'detalles.almacen',
            'cuentaPorPagar',
        ])
            ->where('empresa_id', $this->empresaId())
            ->findOrFail($id);
    }

    /**
     * Anular una recepción de mercancía y revertir el stock en almacén y Kardex
     */
    public function anular(int $id, ?string $motivo = null): Recepcion
    {
        return DB::transaction(function () use ($id, $motivo) {
            $empresaId = $this->empresaId();
            $userId = Auth::id();

            $recepcion = Recepcion::with('detalles.producto')
                ->where('empresa_id', $empresaId)
                ->findOrFail($id);

            if ($recepcion->estado === 'anulada') {
                throw new Exception('Esta recepción ya se encuentra anulada.');
            }

            $recepcion->estado = 'anulada';
            $recepcion->observaciones = ($recepcion->observaciones ? $recepcion->observaciones.' | ' : '')."ANULADA por usuario {$userId}: ".($motivo ?? 'Anulación administrativa');
            $recepcion->save();

            // Revertir inventario vía Kardex en el almacén específico de cada detalle
            foreach ($recepcion->detalles as $detalle) {
                $almacenDestino = $detalle->almacen_id ?: $recepcion->almacen_id;

                $this->kardexService->registrarMovimiento([
                    'empresa_id' => $empresaId,
                    'almacen_id' => $almacenDestino,
                    'producto_id' => $detalle->producto_id,
                    'user_id' => $userId,
                    'tipo_movimiento' => 'anulacion_recepcion',
                    'documento_tipo' => 'recepcion',
                    'documento_id' => $recepcion->id,
                    'cantidad' => (float) $detalle->cantidad,
                    'costo_unitario_usd' => (float) $detalle->costo_unitario_usd,
                    'costo_unitario_bs' => (float) $detalle->costo_unitario_bs,
                    'motivo' => "Anulación de Recepción {$recepcion->codigo} (Doc: {$recepcion->numero_documento})",
                ]);
            }

            // Anular Cuenta por Pagar si existía
            CuentaPorPagar::where('recepcion_id', $recepcion->id)
                ->update(['estado' => 'anulada']);

            return $recepcion;
        });
    }
}
