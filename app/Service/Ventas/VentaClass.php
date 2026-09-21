<?php

namespace App\Service\Ventas;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\DevolucionVenta;
use App\Models\DevolucionVentaDetalle;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\Servicio;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaEnEspera;
use App\Models\VentaPago;
use App\Service\Inventario\KardexClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentaClass
{
    public function __construct(
        protected KardexClass $kardexService
    ) {}

    protected function empresaId(): int
    {
        return (int) (Auth::user()?->empresaActiva()?->id ?? session('empresa_activa_id') ?? 1);
    }

    /**
     * Cargar todos los catálogos y datos iniciales para el arranque del POS
     */
    public function datosInicialesPos(): array
    {
        $empresaId = $this->empresaId();

        // 1. Cliente por defecto (Consumidor Final)
        $clienteDefecto = Cliente::firstOrCreate(
            ['cedula' => 'V-00000000'],
            [
                'nombre' => 'Consumidor',
                'apellido' => 'Final',
                'telefono' => '0000-0000000',
                'correo' => 'cliente@pos.com',
                'direccion' => 'Ciudad',
                'tipo_cliente' => 'detal',
                'estado' => true,
            ]
        );

        // 2. Almacenes de la empresa
        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('id', 'asc')
            ->get(['id', 'nombre', 'codigo', 'direccion']);

        // 3. Métodos de Pago
        $metodosPago = MetodoPago::where('estado', true)
            ->orderBy('id', 'asc')
            ->get(['id', 'nombre', 'descripcion']);

        // 4. Moneda y Tasa de Cambio Activa
        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
        $tasaUsd = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;

        // 5. Productos con existencias por almacén y códigos de barra
        $productos = Producto::with(['stockAlmacenes.almacen', 'codigosBarra', 'categoria'])
            ->where('empresa_id', $empresaId)
            ->where('estado', true)
            ->get()
            ->map(function ($p) use ($tasaUsd) {
                $costoUsd = (float) $p->precio_costo_usd;
                $detalUsd = (float) $p->precio_detal_usd;
                $mayorUsd = (float) $p->precio_mayorista_usd;

                return [
                    'id' => $p->id,
                    'tipo_item' => 'producto',
                    'codigo_interno' => $p->codigo_interno,
                    'nombre' => $p->nombre,
                    'unidad_medida' => $p->unidad_medida,
                    'categoria_nombre' => $p->categoria?->nombre ?? 'General',
                    'precio_costo_usd' => $costoUsd,
                    'precio_costo_bs' => round($costoUsd * $tasaUsd, 2),
                    'precio_detal_usd' => $detalUsd,
                    'precio_detal_bs' => round($detalUsd * $tasaUsd, 2),
                    'precio_mayorista_usd' => $mayorUsd,
                    'precio_mayorista_bs' => round($mayorUsd * $tasaUsd, 2),
                    'aplica_iva' => (bool) $p->aplica_iva,
                    'iva_porcentaje' => (float) ($p->iva_porcentaje ?? 16.00),
                    'aplica_igtf' => (bool) $p->aplica_igtf,
                    'igtf_porcentaje' => (float) ($p->igtf_porcentaje ?? 3.00),
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

        // 6. Servicios activos
        $servicios = Servicio::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->get()
            ->map(function ($s) use ($tasaUsd) {
                $precioUsd = (float) $s->precio_venta_usd;

                return [
                    'id' => $s->id,
                    'tipo_item' => 'servicio',
                    'codigo_interno' => $s->codigo ?? 'SRV-'.$s->id,
                    'nombre' => $s->nombre,
                    'unidad_medida' => 'SRV',
                    'categoria_nombre' => $s->categoria?->nombre ?? 'Servicio',
                    'precio_costo_usd' => (float) $s->precio_costo_usd,
                    'precio_costo_bs' => (float) $s->precio_costo_bs,
                    'precio_detal_usd' => $precioUsd,
                    'precio_detal_bs' => round($precioUsd * $tasaUsd, 2),
                    'precio_mayorista_usd' => $precioUsd,
                    'precio_mayorista_bs' => round($precioUsd * $tasaUsd, 2),
                    'aplica_iva' => (bool) $s->aplica_iva,
                    'iva_porcentaje' => (float) ($s->iva_porcentaje ?? 16.00),
                    'aplica_igtf' => (bool) $s->aplica_igtf,
                    'igtf_porcentaje' => (float) ($s->igtf_porcentaje ?? 3.00),
                    'codigos_barra' => [],
                    'stock_almacenes' => [],
                ];
            });

        return [
            'cliente_defecto' => $clienteDefecto,
            'almacenes' => $almacenes,
            'metodos_pago' => $metodosPago,
            'tasa_usd' => $tasaUsd,
            'proximo_codigo' => $this->generarCodigo($empresaId),
            'productos' => $productos->concat($servicios)->values(),
        ];
    }

    /**
     * Generar correlativo de venta (Ej: VEN-00001)
     */
    public function generarCodigo(int $empresaId): string
    {
        $ultimoId = Venta::where('empresa_id', $empresaId)->max('id') ?? 0;
        $correlativo = str_pad($ultimoId + 1, 5, '0', STR_PAD_LEFT);

        return "VEN-{$correlativo}";
    }

    /**
     * Generar correlativo de devolución (Ej: DEV-00001)
     */
    public function generarCodigoDevolucion(int $empresaId): string
    {
        $ultimoId = DevolucionVenta::where('empresa_id', $empresaId)->max('id') ?? 0;
        $correlativo = str_pad($ultimoId + 1, 5, '0', STR_PAD_LEFT);

        return "DEV-{$correlativo}";
    }

    /**
     * Buscar clientes por cédula o nombre
     */
    public function buscarClientes(string $termino): array
    {
        $termino = trim($termino);
        if (strlen($termino) < 2) {
            return [];
        }

        return Cliente::where('estado', true)
            ->where(function ($q) use ($termino) {
                $q->where('cedula', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('apellido', 'like', "%{$termino}%")
                    ->orWhere('telefono', 'like', "%{$termino}%");
            })
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Registrar o actualizar cliente rápido desde el POS
     */
    public function guardarClienteRapido(array $datos): Cliente
    {
        $cedula = trim($datos['cedula'] ?? '');
        if (empty($cedula)) {
            throw new Exception('La cédula / RIF del cliente es requerida.');
        }

        $cliente = Cliente::where('cedula', $cedula)->first();

        $payload = [
            'cedula' => $cedula,
            'nombre' => trim($datos['nombre'] ?? 'Cliente'),
            'apellido' => trim($datos['apellido'] ?? 'General'),
            'telefono' => trim($datos['telefono'] ?? '') ?: null,
            'correo' => trim($datos['correo'] ?? '') ?: null,
            'direccion' => trim($datos['direccion'] ?? '') ?: null,
            'tipo_cliente' => $datos['tipo_cliente'] ?? 'detal',
            'estado' => true,
        ];

        if ($cliente) {
            $cliente->update($payload);

            return $cliente;
        }

        return Cliente::create($payload);
    }

    /**
     * Procesar y emitir una venta atómica con pagos, Kardex y CXC
     */
    public function procesarVenta(array $datos): Venta
    {
        return DB::transaction(function () use ($datos) {
            $empresaId = $this->empresaId();
            $userId = Auth::id() ?? 1;

            $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
            $tasaOficial = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;
            $tasaCambio = (float) ($datos['tasa_cambio'] ?? $tasaOficial);
            if ($tasaCambio <= 0) {
                $tasaCambio = $tasaOficial;
            }

            $clienteId = (int) ($datos['cliente_id'] ?? 0);
            $cliente = Cliente::findOrFail($clienteId);

            $almacenIdGlobal = (int) ($datos['almacen_id'] ?? 1);
            $tipoVenta = $datos['tipo_venta'] ?? 'detal';
            $condicionPago = $datos['condicion_pago'] ?? 'contado';
            $diasCredito = (int) ($datos['dias_credito'] ?? 0);
            $observaciones = $datos['observaciones'] ?? null;

            $items = $datos['items'] ?? [];
            if (empty($items) || ! is_array($items)) {
                throw new Exception('No hay productos en el carrito de venta.');
            }

            $codigo = $this->generarCodigo($empresaId);
            $ahora = Carbon::now();

            $montoBrutoUsd = 0;
            $totalDescuentosUsd = 0;
            $subtotalNetoUsd = 0;
            $ivaTotalUsd = 0;
            $detallesParaInsertar = [];

            foreach ($items as $it) {
                $productoId = (int) ($it['producto_id'] ?? 0);
                $tipoItem = $it['tipo_item'] ?? 'producto';
                $cantidad = (float) ($it['cantidad'] ?? 0);
                $precioUnitarioUsd = (float) ($it['precio_unitario_usd'] ?? 0);
                $almacenItem = ! empty($it['almacen_id']) ? (int) $it['almacen_id'] : $almacenIdGlobal;

                if ($productoId <= 0 || $cantidad <= 0 || $precioUnitarioUsd <= 0) {
                    continue;
                }

                $producto = null;
                $costoUnitarioUsd = 0;
                $aplicaIva = false;
                $ivaPorcentaje = 0;

                if ($tipoItem === 'producto') {
                    $producto = Producto::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->firstOrFail();

                    $costoUnitarioUsd = (float) $producto->precio_costo_usd;
                    $aplicaIva = (bool) $producto->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($producto->iva_porcentaje ?? 16.00) : 0;
                } elseif ($tipoItem === 'servicio') {
                    $servicio = Servicio::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->firstOrFail();

                    $costoUnitarioUsd = 0;
                    $aplicaIva = (bool) $servicio->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($servicio->iva_porcentaje ?? 16.00) : 0;
                }

                $descuentoPorc = (float) ($it['descuento_porcentaje'] ?? 0);
                $renglonBrutoUsd = round($cantidad * $precioUnitarioUsd, 2);
                $montoBrutoUsd += $renglonBrutoUsd;

                $descuentoItemUsd = 0;
                if ($descuentoPorc > 0) {
                    $descuentoItemUsd = round($renglonBrutoUsd * ($descuentoPorc / 100), 2);
                    $totalDescuentosUsd += $descuentoItemUsd;
                }

                $renglonNetoUsd = round($renglonBrutoUsd - $descuentoItemUsd, 2);
                $subtotalNetoUsd += $renglonNetoUsd;

                $ivaItemUsd = 0;
                if ($aplicaIva && $ivaPorcentaje > 0) {
                    $ivaItemUsd = round($renglonNetoUsd * ($ivaPorcentaje / 100), 2);
                    $ivaTotalUsd += $ivaItemUsd;
                }

                $detallesParaInsertar[] = [
                    'producto_id' => $productoId,
                    'producto_model' => $producto,
                    'almacen_id' => $almacenItem,
                    'tipo_item' => $tipoItem,
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUnitarioUsd,
                    'costo_unitario_bs' => round($costoUnitarioUsd * $tasaCambio, 4),
                    'precio_unitario_usd' => $precioUnitarioUsd,
                    'precio_unitario_bs' => round($precioUnitarioUsd * $tasaCambio, 4),
                    'descuento_porcentaje' => $descuentoPorc,
                    'descuento_usd' => $descuentoItemUsd,
                    'descuento_bs' => round($descuentoItemUsd * $tasaCambio, 2),
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'iva_monto_usd' => $ivaItemUsd,
                    'iva_monto_bs' => round($ivaItemUsd * $tasaCambio, 2),
                    'subtotal_usd' => $renglonNetoUsd,
                    'subtotal_bs' => round($renglonNetoUsd * $tasaCambio, 2),
                ];
            }

            if (empty($detallesParaInsertar)) {
                throw new Exception('No hay renglones válidos para procesar.');
            }

            $totalGeneralUsd = round($subtotalNetoUsd + $ivaTotalUsd, 2);
            $totalGeneralBs = round($totalGeneralUsd * $tasaCambio, 2);

            // Procesar Pagos
            $pagosInput = $datos['pagos'] ?? [];
            $montoTotalPagadoUsd = 0;
            $montoTotalPagadoBs = 0;
            $pagosParaInsertar = [];

            foreach ($pagosInput as $p) {
                $metodoId = (int) ($p['metodo_pago_id'] ?? 0);
                $montoOrigen = (float) ($p['monto'] ?? 0);
                $monedaPago = $p['moneda'] ?? 'USD';
                $tasaPago = (float) ($p['tasa_cambio'] ?? $tasaCambio);
                $referencia = trim($p['referencia'] ?? '') ?: null;

                if ($metodoId <= 0 || $montoOrigen <= 0) {
                    continue;
                }

                $montoUsd = $monedaPago === 'VES' ? ($tasaPago > 0 ? round($montoOrigen / $tasaPago, 2) : 0) : $montoOrigen;
                $montoBs = $monedaPago === 'VES' ? $montoOrigen : round($montoOrigen * $tasaPago, 2);

                $montoTotalPagadoUsd += $montoUsd;
                $montoTotalPagadoBs += $montoBs;

                $pagosParaInsertar[] = [
                    'metodo_pago_id' => $metodoId,
                    'moneda' => $monedaPago,
                    'tasa_cambio' => $tasaPago,
                    'monto_origen' => $montoOrigen,
                    'monto_usd' => $montoUsd,
                    'monto_bs' => $montoBs,
                    'referencia' => $referencia,
                ];
            }

            $vueltoUsd = 0;
            $vueltoBs = 0;
            $saldoPendienteUsd = 0;
            $saldoPendienteBs = 0;

            if ($condicionPago === 'credito') {
                $saldoPendienteUsd = max(0, $totalGeneralUsd - $montoTotalPagadoUsd);
                $saldoPendienteBs = round($saldoPendienteUsd * $tasaCambio, 2);
            } else {
                if ($montoTotalPagadoUsd > $totalGeneralUsd) {
                    $vueltoUsd = round($montoTotalPagadoUsd - $totalGeneralUsd, 2);
                    $vueltoBs = round($vueltoUsd * $tasaCambio, 2);
                } elseif ($montoTotalPagadoUsd < $totalGeneralUsd) {
                    $saldoPendienteUsd = round($totalGeneralUsd - $montoTotalPagadoUsd, 2);
                    $saldoPendienteBs = round($saldoPendienteUsd * $tasaCambio, 2);
                    $condicionPago = 'credito';
                }
            }

            // Validar que venta a crédito no se haga a Consumidor Final genérico
            if ($saldoPendienteUsd > 0 && ($cliente->cedula === 'V-00000000' || $cliente->cedula === 'J-00000000')) {
                throw new Exception('No se puede procesar una venta a crédito para el cliente "Consumidor Final". Por favor registra o selecciona un cliente identificado.');
            }

            // 1. Guardar Cabecera Venta
            $venta = Venta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'almacen_id' => $almacenIdGlobal,
                'user_id' => $userId,
                'codigo' => $codigo,
                'tipo_venta' => $tipoVenta,
                'moneda' => 'USD',
                'tasa_cambio' => $tasaCambio,
                'fecha_emision' => $ahora->toDateString(),
                'hora_emision' => $ahora->toTimeString(),
                'monto_bruto_usd' => $montoBrutoUsd,
                'monto_bruto_bs' => round($montoBrutoUsd * $tasaCambio, 2),
                'descuento_porcentaje' => $montoBrutoUsd > 0 ? round(($totalDescuentosUsd / $montoBrutoUsd) * 100, 2) : 0,
                'descuento_usd' => $totalDescuentosUsd,
                'descuento_bs' => round($totalDescuentosUsd * $tasaCambio, 2),
                'subtotal_neto_usd' => $subtotalNetoUsd,
                'subtotal_neto_bs' => round($subtotalNetoUsd * $tasaCambio, 2),
                'iva_monto_usd' => $ivaTotalUsd,
                'iva_monto_bs' => round($ivaTotalUsd * $tasaCambio, 2),
                'total_usd' => $totalGeneralUsd,
                'total_bs' => $totalGeneralBs,
                'condicion_pago' => $condicionPago,
                'monto_pagado_usd' => min($montoTotalPagadoUsd, $totalGeneralUsd),
                'monto_pagado_bs' => round(min($montoTotalPagadoUsd, $totalGeneralUsd) * $tasaCambio, 2),
                'vuelto_usd' => $vueltoUsd,
                'vuelto_bs' => $vueltoBs,
                'saldo_pendiente_usd' => $saldoPendienteUsd,
                'saldo_pendiente_bs' => $saldoPendienteBs,
                'estado' => 'completada',
                'observaciones' => $observaciones,
            ]);

            // 2. Guardar Renglones y Asientos en Kardex
            foreach ($detallesParaInsertar as $det) {
                $detModel = VentaDetalle::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $det['producto_id'],
                    'almacen_id' => $det['almacen_id'],
                    'tipo_item' => $det['tipo_item'],
                    'cantidad' => $det['cantidad'],
                    'costo_unitario_usd' => $det['costo_unitario_usd'],
                    'costo_unitario_bs' => $det['costo_unitario_bs'],
                    'precio_unitario_usd' => $det['precio_unitario_usd'],
                    'precio_unitario_bs' => $det['precio_unitario_bs'],
                    'descuento_porcentaje' => $det['descuento_porcentaje'],
                    'descuento_usd' => $det['descuento_usd'],
                    'descuento_bs' => $det['descuento_bs'],
                    'aplica_iva' => $det['aplica_iva'],
                    'iva_porcentaje' => $det['iva_porcentaje'],
                    'iva_monto_usd' => $det['iva_monto_usd'],
                    'iva_monto_bs' => $det['iva_monto_bs'],
                    'subtotal_usd' => $det['subtotal_usd'],
                    'subtotal_bs' => $det['subtotal_bs'],
                ]);

                // Descontar inventario solo si es producto físico
                if ($det['tipo_item'] === 'producto' && $det['producto_model']) {
                    $this->kardexService->registrarMovimiento([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $det['almacen_id'],
                        'producto_id' => $det['producto_id'],
                        'user_id' => $userId,
                        'tipo_movimiento' => 'salida_venta',
                        'documento_tipo' => 'venta',
                        'documento_id' => $venta->id,
                        'cantidad' => $det['cantidad'],
                        'costo_unitario_usd' => $det['costo_unitario_usd'],
                        'costo_unitario_bs' => $det['costo_unitario_bs'],
                        'motivo' => "Venta POS Comprobante #{$venta->codigo}",
                    ]);
                }
            }

            // 3. Guardar Pagos
            foreach ($pagosParaInsertar as $pago) {
                VentaPago::create([
                    'venta_id' => $venta->id,
                    'metodo_pago_id' => $pago['metodo_pago_id'],
                    'moneda' => $pago['moneda'],
                    'tasa_cambio' => $pago['tasa_cambio'],
                    'monto_origen' => $pago['monto_origen'],
                    'monto_usd' => $pago['monto_usd'],
                    'monto_bs' => $pago['monto_bs'],
                    'referencia' => $pago['referencia'],
                ]);
            }

            // 4. Si hay saldo a crédito, registrar Cuenta por Cobrar
            if ($saldoPendienteUsd > 0) {
                $fechaVencimiento = Carbon::parse($venta->fecha_emision)->addDays(max(1, $diasCredito))->toDateString();

                CuentaPorCobrar::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $clienteId,
                    'venta_id' => $venta->id,
                    'numero_factura' => $venta->codigo,
                    'fecha_emision' => $venta->fecha_emision,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total_usd' => $totalGeneralUsd,
                    'monto_total_bs' => $totalGeneralBs,
                    'monto_pagado_usd' => min($montoTotalPagadoUsd, $totalGeneralUsd),
                    'monto_pagado_bs' => round(min($montoTotalPagadoUsd, $totalGeneralUsd) * $tasaCambio, 2),
                    'saldo_pendiente_usd' => $saldoPendienteUsd,
                    'saldo_pendiente_bs' => $saldoPendienteBs,
                    'estado' => $montoTotalPagadoUsd > 0 ? 'parcial' : 'pendiente',
                    'observaciones' => "Crédito generado desde venta {$venta->codigo}",
                ]);
            }

            return $venta->load(['cliente', 'almacen', 'detalles.producto', 'pagos.metodoPago']);
        });
    }

    /**
     * Guardar venta en espera (pausar carrito)
     */
    public function guardarEnEspera(array $datos): VentaEnEspera
    {
        $empresaId = $this->empresaId();
        $userId = Auth::id() ?? 1;

        return VentaEnEspera::create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'cliente_id' => ! empty($datos['cliente_id']) ? (int) $datos['cliente_id'] : null,
            'tipo_venta' => $datos['tipo_venta'] ?? 'detal',
            'nota_referencia' => trim($datos['nota_referencia'] ?? 'Cuenta en espera '.Carbon::now()->format('h:i A')),
            'datos_json' => $datos,
            'total_usd' => (float) ($datos['total_usd'] ?? 0),
            'total_bs' => (float) ($datos['total_bs'] ?? 0),
        ]);
    }

    /**
     * Listar ventas en espera activas
     */
    public function listarEnEspera(): array
    {
        $empresaId = $this->empresaId();

        return VentaEnEspera::with(['cliente'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Recuperar y eliminar venta en espera
     */
    public function recuperarEnEspera(int $id): array
    {
        $empresaId = $this->empresaId();
        $espera = VentaEnEspera::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $datos = $espera->datos_json;
        $espera->delete();

        return $datos;
    }

    /**
     * Eliminar venta en espera
     */
    public function eliminarEnEspera(int $id): bool
    {
        $empresaId = $this->empresaId();
        $espera = VentaEnEspera::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        return (bool) $espera->delete();
    }

    /**
     * Buscar factura para devolución por código o ID
     */
    public function buscarFacturaDevolucion(string $busqueda): array
    {
        $empresaId = $this->empresaId();
        $busqueda = trim($busqueda);

        $venta = Venta::with(['cliente', 'almacen', 'detalles.producto', 'pagos.metodoPago', 'devoluciones.detalles'])
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($busqueda) {
                $q->where('codigo', $busqueda)
                    ->orWhere('id', $busqueda);
            })
            ->first();

        if (! $venta) {
            throw new Exception("No se encontró ninguna factura con el código o número '{$busqueda}'.");
        }

        if ($venta->estado === 'anulada' || $venta->estado === 'devuelta_total') {
            throw new Exception("La factura '{$venta->codigo}' ya se encuentra {$venta->estado}.");
        }

        return $venta->toArray();
    }

    /**
     * Procesar devolución de venta y reversión de inventario
     */
    public function procesarDevolucion(array $datos): DevolucionVenta
    {
        return DB::transaction(function () use ($datos) {
            $empresaId = $this->empresaId();
            $userId = Auth::id() ?? 1;

            $ventaId = (int) ($datos['venta_id'] ?? 0);
            $venta = Venta::with(['detalles'])->where('id', $ventaId)->where('empresa_id', $empresaId)->firstOrFail();

            $motivo = trim($datos['motivo'] ?? 'Devolución de cliente');
            $tipoReembolso = $datos['tipo_reembolso'] ?? 'sin_reembolso';

            $itemsADevolver = $datos['items'] ?? [];
            if (empty($itemsADevolver) || ! is_array($itemsADevolver)) {
                throw new Exception('Debes indicar los productos y cantidades a devolver.');
            }

            $codigoDevolucion = $this->generarCodigoDevolucion($empresaId);
            $totalDevueltoUsd = 0;
            $totalDevueltoBs = 0;
            $detallesDevolucion = [];

            foreach ($itemsADevolver as $item) {
                $detalleId = (int) ($item['venta_detalle_id'] ?? 0);
                $cantidadDevolver = (float) ($item['cantidad'] ?? 0);

                if ($detalleId <= 0 || $cantidadDevolver <= 0) {
                    continue;
                }

                $ventaDetalle = $venta->detalles->firstWhere('id', $detalleId);
                if (! $ventaDetalle) {
                    continue;
                }

                // Cantidad previamente devuelta
                $cantPrevia = DevolucionVentaDetalle::where('venta_detalle_id', $detalleId)->sum('cantidad');
                $cantDisponibleParaDevolver = $ventaDetalle->cantidad - $cantPrevia;

                if ($cantidadDevolver > $cantDisponibleParaDevolver) {
                    throw new Exception("La cantidad a devolver ({$cantidadDevolver}) supera lo disponible facturado ({$cantDisponibleParaDevolver}) para el producto.");
                }

                $subtotalDevueltoUsd = round($cantidadDevolver * $ventaDetalle->precio_unitario_usd, 2);
                $subtotalDevueltoBs = round($cantidadDevolver * $ventaDetalle->precio_unitario_bs, 2);

                $totalDevueltoUsd += $subtotalDevueltoUsd;
                $totalDevueltoBs += $subtotalDevueltoBs;

                $detallesDevolucion[] = [
                    'venta_detalle' => $ventaDetalle,
                    'cantidad' => $cantidadDevolver,
                    'precio_unitario_usd' => $ventaDetalle->precio_unitario_usd,
                    'precio_unitario_bs' => $ventaDetalle->precio_unitario_bs,
                    'subtotal_usd' => $subtotalDevueltoUsd,
                    'subtotal_bs' => $subtotalDevueltoBs,
                ];
            }

            if (empty($detallesDevolucion)) {
                throw new Exception('No hay renglones válidos para procesar en la devolución.');
            }

            // 1. Guardar Cabecera de Devolución
            $devolucion = DevolucionVenta::create([
                'empresa_id' => $empresaId,
                'venta_id' => $venta->id,
                'user_id' => $userId,
                'codigo' => $codigoDevolucion,
                'motivo' => $motivo,
                'total_devuelto_usd' => $totalDevueltoUsd,
                'total_devuelto_bs' => $totalDevueltoBs,
                'tipo_reembolso' => $tipoReembolso,
            ]);

            // 2. Guardar Detalles y Revertir Kardex
            foreach ($detallesDevolucion as $d) {
                $vd = $d['venta_detalle'];

                DevolucionVentaDetalle::create([
                    'devolucion_venta_id' => $devolucion->id,
                    'venta_detalle_id' => $vd->id,
                    'producto_id' => $vd->producto_id,
                    'almacen_id' => $vd->almacen_id,
                    'cantidad' => $d['cantidad'],
                    'precio_unitario_usd' => $d['precio_unitario_usd'],
                    'precio_unitario_bs' => $d['precio_unitario_bs'],
                    'subtotal_usd' => $d['subtotal_usd'],
                    'subtotal_bs' => $d['subtotal_bs'],
                ]);

                // Reintegrar stock al almacén si es producto físico
                if ($vd->tipo_item === 'producto') {
                    $this->kardexService->registrarMovimiento([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $vd->almacen_id,
                        'producto_id' => $vd->producto_id,
                        'user_id' => $userId,
                        'tipo_movimiento' => 'anulacion_venta',
                        'documento_tipo' => 'venta',
                        'documento_id' => $venta->id,
                        'cantidad' => $d['cantidad'],
                        'costo_unitario_usd' => $vd->costo_unitario_usd,
                        'costo_unitario_bs' => $vd->costo_unitario_bs,
                        'motivo' => "Reintegro por Devolución #{$devolucion->codigo} de Venta #{$venta->codigo}",
                    ]);
                }
            }

            // 3. Actualizar estado de la venta
            $totalCantidadOriginal = $venta->detalles->sum('cantidad');
            $totalCantidadDevuelta = DevolucionVentaDetalle::whereIn('venta_detalle_id', $venta->detalles->pluck('id'))->sum('cantidad');

            if ($totalCantidadDevuelta >= $totalCantidadOriginal) {
                $venta->estado = 'devuelta_total';
            } else {
                $venta->estado = 'devuelta_parcial';
            }
            $venta->save();

            // 4. Si la venta tenía cuenta por cobrar pendiente, recalcular o anular
            if ($venta->cuentaPorCobrar) {
                $cxc = $venta->cuentaPorCobrar;
                $nuevoSaldoUsd = max(0, $cxc->saldo_pendiente_usd - $totalDevueltoUsd);
                $cxc->saldo_pendiente_usd = $nuevoSaldoUsd;
                $cxc->saldo_pendiente_bs = round($nuevoSaldoUsd * $venta->tasa_cambio, 2);
                if ($nuevoSaldoUsd <= 0) {
                    $cxc->estado = 'pagada';
                }
                $cxc->save();
            }

            return $devolucion->load(['detalles.producto', 'venta.cliente']);
        });
    }

    /**
     * Buscar factura para reimpresión
     */
    public function obtenerVentaParaImpresion(int|string $idOcodigo): Venta
    {
        $empresaId = $this->empresaId();

        return Venta::with(['empresa', 'cliente', 'almacen', 'usuario', 'detalles.producto', 'pagos.metodoPago'])
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($idOcodigo) {
                $q->where('id', $idOcodigo)
                    ->orWhere('codigo', $idOcodigo);
            })
            ->firstOrFail();
    }
}
