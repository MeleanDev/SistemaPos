<?php

namespace App\Service\Inventario;

use App\Models\Almacen;
use App\Models\EmpresaMoneda;
use App\Models\Kardex;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KardexClass
{
    /**
     * Registrar un movimiento inmutable en el Kardex y actualizar stock en el almacén
     */
    public function registrarMovimiento(array $datos): Kardex
    {
        $empresaId = $datos['empresa_id'] ?? (Auth::user()?->empresaActiva()?->id ?? session('empresa_activa_id'));
        $almacenId = $datos['almacen_id'];
        $productoId = $datos['producto_id'];
        $userId = $datos['user_id'] ?? Auth::id();
        $tipoMovimiento = $datos['tipo_movimiento'];
        $documentoTipo = $datos['documento_tipo'];
        $documentoId = $datos['documento_id'];
        $cantidad = (float) $datos['cantidad'];
        $costoUnitarioUsd = (float) ($datos['costo_unitario_usd'] ?? 0);
        $costoUnitarioBs = (float) ($datos['costo_unitario_bs'] ?? 0);
        $motivo = $datos['motivo'] ?? null;

        // Obtener o inicializar registro de stock en el almacén
        $stockAlmacen = ProductoStockAlmacen::firstOrCreate(
            [
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
            ],
            [
                'cantidad_actual' => 0,
                'cantidad_reservada' => 0,
            ]
        );

        $stockAnterior = (float) $stockAlmacen->cantidad_actual;

        // Determinar impacto de stock según el tipo de movimiento
        $esEntrada = in_array($tipoMovimiento, [
            'entrada_recepcion',
            'traslado_entrada',
            'ajuste_positivo',
            'anulacion_venta',
        ]);

        if ($esEntrada) {
            $stockNuevo = $stockAnterior + $cantidad;
        } else {
            $stockNuevo = max(0, $stockAnterior - $cantidad);
        }

        // Actualizar stock del almacén
        $stockAlmacen->cantidad_actual = $stockNuevo;
        $stockAlmacen->save();

        // Crear asiento de Kardex
        return Kardex::create([
            'empresa_id' => $empresaId,
            'almacen_id' => $almacenId,
            'producto_id' => $productoId,
            'user_id' => $userId,
            'tipo_movimiento' => $tipoMovimiento,
            'documento_tipo' => $documentoTipo,
            'documento_id' => $documentoId,
            'cantidad' => $cantidad,
            'costo_unitario_usd' => $costoUnitarioUsd,
            'costo_unitario_bs' => $costoUnitarioBs,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
        ]);
    }

    /**
     * Query de lista para DataTables con paginación del servidor
     */
    public function listaQuery(int $empresaId): Builder
    {
        return Kardex::with([
            'producto:id,nombre,codigo_interno,unidad_medida,aplica_iva',
            'almacen:id,nombre,codigo',
            'usuario:id,name,email',
        ])
            ->where('empresa_id', $empresaId)
            ->orderByDesc('id');
    }

    /**
     * Listar movimientos de Kardex con filtros y paginación para DataTables
     */
    public function listarKardex(array $filtros, int $empresaId): array
    {
        $query = Kardex::with([
            'producto:id,nombre,codigo_interno,unidad_medida,aplica_iva',
            'almacen:id,nombre,codigo',
            'usuario:id,name,email',
        ])
            ->where('empresa_id', $empresaId);

        // Filtro por Almacén
        if (! empty($filtros['almacen_id'])) {
            $query->where('almacen_id', (int) $filtros['almacen_id']);
        }

        // Filtro por Producto
        if (! empty($filtros['producto_id'])) {
            $query->where('producto_id', (int) $filtros['producto_id']);
        }

        // Filtro por Tipo de Movimiento
        if (! empty($filtros['tipo_movimiento'])) {
            $query->where('tipo_movimiento', $filtros['tipo_movimiento']);
        }

        // Filtro por Rango de Fechas
        if (! empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }
        if (! empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        // Búsqueda general
        if (! empty($filtros['search'])) {
            $search = trim($filtros['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('motivo', 'like', "%{$search}%")
                    ->orWhere('documento_tipo', 'like', "%{$search}%")
                    ->orWhereHas('producto', function (Builder $qp) use ($search) {
                        $qp->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo_interno', 'like', "%{$search}%");
                    })
                    ->orWhereHas('almacen', function (Builder $qa) use ($search) {
                        $qa->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    });
            });
        }

        $totalRegistros = $query->count();

        $movimientos = $query->orderBy('id', 'desc')
            ->limit(500)
            ->get();

        return [
            'total' => $totalRegistros,
            'items' => $movimientos,
        ];
    }

    /**
     * Obtener métricas / KPIs de inventario del día
     */
    public function obtenerKpis(int $empresaId): array
    {
        $hoy = Carbon::today();

        $totalHoy = Kardex::where('empresa_id', $empresaId)
            ->whereDate('created_at', $hoy)
            ->count();

        $entradasHoy = Kardex::where('empresa_id', $empresaId)
            ->whereDate('created_at', $hoy)
            ->whereIn('tipo_movimiento', ['entrada_recepcion', 'ajuste_positivo', 'traslado_entrada'])
            ->sum('cantidad');

        $salidasHoy = Kardex::where('empresa_id', $empresaId)
            ->whereDate('created_at', $hoy)
            ->whereIn('tipo_movimiento', ['salida_venta', 'ajuste_negativo', 'traslado_salida'])
            ->sum('cantidad');

        $trasladosHoy = MovimientoInventario::where('empresa_id', $empresaId)
            ->where('tipo', 'traslado')
            ->whereDate('fecha', $hoy)
            ->count();

        return [
            'total_movimientos_hoy' => $totalHoy,
            'entradas_hoy_unidades' => (float) $entradasHoy,
            'salidas_hoy_unidades' => (float) $salidasHoy,
            'traslados_hoy_count' => $trasladosHoy,
        ];
    }

    /**
     * Catálogos para el modal de operaciones
     */
    public function obtenerCatalogos(int $empresaId): array
    {
        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo', 'direccion']);

        $productos = Producto::with([
            'categoria:id,nombre',
            'stockAlmacenes:id,producto_id,almacen_id,cantidad_actual',
        ])
            ->where('empresa_id', $empresaId)
            ->where('estado', true)
            ->where(function (Builder $q) {
                $q->whereNull('tipo')
                    ->orWhere('tipo', '!=', 'servicio');
            })
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                $stockTotal = $p->stockAlmacenes->sum('cantidad_actual');

                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'codigo_interno' => $p->codigo_interno,
                    'unidad_medida' => $p->unidad_medida ?? 'UND',
                    'categoria_nombre' => $p->categoria?->nombre ?? 'General',
                    'precio_costo_usd' => (float) $p->precio_costo_usd,
                    'precio_costo_bs' => (float) $p->precio_costo_bs,
                    'stock_total' => (float) $stockTotal,
                    'stock_por_almacen' => $p->stockAlmacenes->pluck('cantidad_actual', 'almacen_id')->toArray(),
                ];
            });

        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
        $tasaUsd = $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0000;

        return [
            'almacenes' => $almacenes,
            'productos' => $productos,
            'tasa_usd' => $tasaUsd,
            'tipos_movimiento' => [
                'ajuste_positivo' => 'Ajuste Positivo (+)',
                'ajuste_negativo' => 'Ajuste Negativo (-)',
                'traslado_salida' => 'Traslado (Salida)',
                'traslado_entrada' => 'Traslado (Entrada)',
                'entrada_recepcion' => 'Entrada por Recepción',
                'salida_venta' => 'Salida por Venta',
                'anulacion_recepcion' => 'Anulación de Recepción',
                'anulacion_venta' => 'Anulación de Venta',
            ],
            'motivos_ajuste_entrada' => [
                'Sobrante de Inventario / Conteo Físico',
                'Inventario Inicial / Regularización',
                'Corrección de Auditoría',
                'Devolución de Muestra / Exhibición',
                'Otro Motivo (Entrada)',
            ],
            'motivos_ajuste_salida' => [
                'Faltante de Inventario / Conteo Físico',
                'Merma / Deterioro / Rotura',
                'Vencimiento de Producto',
                'Autoconsumo / Uso Interno',
                'Pérdida / Extravío',
                'Corrección de Auditoría',
                'Otro Motivo (Salida)',
            ],
            'motivos_traslado' => [
                'Reabastecimiento de Sucursal / Almacén',
                'Distribución Operativa',
                'Reubicación de Mercancía',
                'Consolidación de Inventario',
                'Otro Motivo (Traslado)',
            ],
        ];
    }

    /**
     * Consultar stock detallado de un producto por almacén
     */
    public function obtenerStockProductoAlmacenes(int $productoId, int $empresaId): array
    {
        $producto = Producto::where('empresa_id', $empresaId)
            ->where('id', $productoId)
            ->firstOrFail();

        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->get();

        $stocks = [];
        $stockTotal = 0;

        foreach ($almacenes as $alm) {
            $stk = ProductoStockAlmacen::where('producto_id', $productoId)
                ->where('almacen_id', $alm->id)
                ->first();

            $cant = $stk ? (float) $stk->cantidad_actual : 0;
            $stockTotal += $cant;

            $stocks[] = [
                'almacen_id' => $alm->id,
                'almacen_nombre' => $alm->nombre,
                'almacen_codigo' => $alm->codigo,
                'cantidad_actual' => $cant,
            ];
        }

        return [
            'producto_id' => $producto->id,
            'nombre' => $producto->nombre,
            'codigo_interno' => $producto->codigo_interno,
            'unidad_medida' => $producto->unidad_medida,
            'stock_total' => $stockTotal,
            'almacenes' => $stocks,
        ];
    }

    /**
     * Procesar Ajuste de Inventario (Entrada o Salida)
     */
    public function procesarAjuste(array $datos, int $empresaId, int $userId): MovimientoInventario
    {
        return DB::transaction(function () use ($datos, $empresaId, $userId) {
            $almacenId = (int) $datos['almacen_id'];
            $tipoAjuste = $datos['tipo_ajuste']; // 'entrada' o 'salida'
            $motivo = trim($datos['motivo']);
            $observaciones = trim($datos['observaciones'] ?? '') ?: null;
            $fecha = ! empty($datos['fecha']) ? $datos['fecha'] : Carbon::now()->toDateString();
            $detalles = $datos['detalles'];

            $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
            $tasaUsd = $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0000;

            $tipoMovimiento = ($tipoAjuste === 'entrada') ? 'ajuste_positivo' : 'ajuste_negativo';
            $codigo = $this->generarCodigo(($tipoAjuste === 'entrada') ? 'AJUSTE_ENTRADA' : 'AJUSTE_SALIDA', $empresaId);

            $totalItems = 0;
            $totalUnidades = 0;

            // 1. Validar existencias si es salida
            foreach ($detalles as $idx => $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (float) $item['cantidad'];

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.{$idx}.cantidad" => 'La cantidad debe ser mayor a 0.',
                    ]);
                }

                $producto = Producto::where('id', $productoId)
                    ->where('empresa_id', $empresaId)
                    ->firstOrFail();

                if ($tipoAjuste === 'salida') {
                    $stockAlm = ProductoStockAlmacen::where('producto_id', $productoId)
                        ->where('almacen_id', $almacenId)
                        ->first();

                    $stockActual = $stockAlm ? (float) $stockAlm->cantidad_actual : 0;
                    if ($cantidad > $stockActual) {
                        throw ValidationException::withMessages([
                            'detalles' => "Stock insuficiente para el producto '{$producto->nombre}'. Stock disponible en almacén: {$stockActual} {$producto->unidad_medida}, cantidad solicitada: {$cantidad}.",
                        ]);
                    }
                }

                $totalItems++;
                $totalUnidades += $cantidad;
            }

            // 2. Crear cabecera del Movimiento de Inventario
            $movimiento = MovimientoInventario::create([
                'empresa_id' => $empresaId,
                'codigo' => $codigo,
                'tipo' => ($tipoAjuste === 'entrada') ? 'ajuste_entrada' : 'ajuste_salida',
                'almacen_origen_id' => ($tipoAjuste === 'salida') ? $almacenId : null,
                'almacen_destino_id' => ($tipoAjuste === 'entrada') ? $almacenId : null,
                'user_id' => $userId,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'fecha' => $fecha,
                'total_items' => $totalItems,
                'total_unidades' => $totalUnidades,
            ]);

            // 3. Registrar asientos de Kardex
            foreach ($detalles as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (float) $item['cantidad'];
                $producto = Producto::findOrFail($productoId);

                $costoUsd = isset($item['costo_unitario_usd']) && (float) $item['costo_unitario_usd'] > 0
                    ? (float) $item['costo_unitario_usd']
                    : (float) $producto->precio_costo_usd;

                $costoBs = round($costoUsd * $tasaUsd, 4);

                $this->registrarMovimiento([
                    'empresa_id' => $empresaId,
                    'almacen_id' => $almacenId,
                    'producto_id' => $productoId,
                    'user_id' => $userId,
                    'tipo_movimiento' => $tipoMovimiento,
                    'documento_tipo' => 'movimiento_inventario',
                    'documento_id' => $movimiento->id,
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUsd,
                    'costo_unitario_bs' => $costoBs,
                    'motivo' => "{$motivo} - Comprobante: {$codigo}",
                ]);
            }

            return $movimiento;
        });
    }

    /**
     * Procesar Traslado entre Almacenes
     */
    public function procesarTraslado(array $datos, int $empresaId, int $userId): MovimientoInventario
    {
        return DB::transaction(function () use ($datos, $empresaId, $userId) {
            $almacenOrigenId = (int) $datos['almacen_origen_id'];
            $almacenDestinoId = (int) $datos['almacen_destino_id'];
            $motivo = trim($datos['motivo']);
            $observaciones = trim($datos['observaciones'] ?? '') ?: null;
            $fecha = ! empty($datos['fecha']) ? $datos['fecha'] : Carbon::now()->toDateString();
            $detalles = $datos['detalles'];

            if ($almacenOrigenId === $almacenDestinoId) {
                throw ValidationException::withMessages([
                    'almacen_destino_id' => 'El almacén de destino debe ser diferente al de origen.',
                ]);
            }

            $almacenOrigen = Almacen::where('empresa_id', $empresaId)->where('id', $almacenOrigenId)->firstOrFail();
            $almacenDestino = Almacen::where('empresa_id', $empresaId)->where('id', $almacenDestinoId)->firstOrFail();

            $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
            $tasaUsd = $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0000;

            $codigo = $this->generarCodigo('TRASLADO', $empresaId);
            $totalItems = 0;
            $totalUnidades = 0;

            // 1. Validar existencias en Almacén Origen
            foreach ($detalles as $idx => $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (float) $item['cantidad'];

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.{$idx}.cantidad" => 'La cantidad a trasladar debe ser mayor a 0.',
                    ]);
                }

                $producto = Producto::where('id', $productoId)
                    ->where('empresa_id', $empresaId)
                    ->firstOrFail();

                $stockAlmOrigen = ProductoStockAlmacen::where('producto_id', $productoId)
                    ->where('almacen_id', $almacenOrigenId)
                    ->first();

                $stockDisponible = $stockAlmOrigen ? (float) $stockAlmOrigen->cantidad_actual : 0;
                if ($cantidad > $stockDisponible) {
                    throw ValidationException::withMessages([
                        'detalles' => "Stock insuficiente para '{$producto->nombre}' en {$almacenOrigen->nombre}. Disponible: {$stockDisponible}, Solicitado: {$cantidad}.",
                    ]);
                }

                $totalItems++;
                $totalUnidades += $cantidad;
            }

            // 2. Crear cabecera del Movimiento de Traslado
            $movimiento = MovimientoInventario::create([
                'empresa_id' => $empresaId,
                'codigo' => $codigo,
                'tipo' => 'traslado',
                'almacen_origen_id' => $almacenOrigenId,
                'almacen_destino_id' => $almacenDestinoId,
                'user_id' => $userId,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'fecha' => $fecha,
                'total_items' => $totalItems,
                'total_unidades' => $totalUnidades,
            ]);

            // 3. Registrar salidas en origen y entradas en destino
            foreach ($detalles as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (float) $item['cantidad'];
                $producto = Producto::findOrFail($productoId);

                $costoUsd = (float) $producto->precio_costo_usd;
                $costoBs = round($costoUsd * $tasaUsd, 4);

                // Asiento 1: Salida de almacén origen
                $this->registrarMovimiento([
                    'empresa_id' => $empresaId,
                    'almacen_id' => $almacenOrigenId,
                    'producto_id' => $productoId,
                    'user_id' => $userId,
                    'tipo_movimiento' => 'traslado_salida',
                    'documento_tipo' => 'movimiento_inventario',
                    'documento_id' => $movimiento->id,
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUsd,
                    'costo_unitario_bs' => $costoBs,
                    'motivo' => "Traslado a {$almacenDestino->nombre} - Ref: {$codigo} ({$motivo})",
                ]);

                // Asiento 2: Entrada a almacén destino
                $this->registrarMovimiento([
                    'empresa_id' => $empresaId,
                    'almacen_id' => $almacenDestinoId,
                    'producto_id' => $productoId,
                    'user_id' => $userId,
                    'tipo_movimiento' => 'traslado_entrada',
                    'documento_tipo' => 'movimiento_inventario',
                    'documento_id' => $movimiento->id,
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUsd,
                    'costo_unitario_bs' => $costoBs,
                    'motivo' => "Traslado desde {$almacenOrigen->nombre} - Ref: {$codigo} ({$motivo})",
                ]);
            }

            return $movimiento;
        });
    }

    /**
     * Obtener comprobante formal de movimiento para vista / impresión
     */
    public function obtenerComprobante(int $id, int $empresaId): MovimientoInventario
    {
        return MovimientoInventario::with([
            'almacenOrigen',
            'almacenDestino',
            'usuario',
            'empresa',
            'kardex.producto',
            'kardex.almacen',
        ])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Generar correlativo automático para Traslado o Ajuste
     */
    public function generarCodigo(string $tipo, int $empresaId): string
    {
        $prefix = match ($tipo) {
            'TRASLADO' => 'TRASF',
            'AJUSTE_ENTRADA' => 'AJUST-E',
            'AJUSTE_SALIDA' => 'AJUST-S',
            default => 'MOV',
        };

        $ultimoId = MovimientoInventario::where('empresa_id', $empresaId)
            ->where('codigo', 'like', "{$prefix}-%")
            ->count();

        $correlativo = str_pad($ultimoId + 1, 5, '0', STR_PAD_LEFT);

        return "{$prefix}-{$correlativo}";
    }
}
