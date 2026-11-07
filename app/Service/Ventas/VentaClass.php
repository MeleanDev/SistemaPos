<?php

namespace App\Service\Ventas;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\DevolucionVenta;
use App\Models\DevolucionVentaDetalle;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Secuencia;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaEnEspera;
use App\Models\VentaPago;
use App\Service\Inventario\KardexClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentaClass
{
    public function __construct(
        protected KardexClass $kardexService
    ) {}

    protected function empresaId(?int $empresaId = null): int
    {
        $id = (int) ($empresaId ?: (Auth::user()?->empresaActiva()?->id ?? session('empresa_activa_id') ?? 0));

        if ($id <= 0) {
            throw new Exception('No se pudo determinar la empresa activa para realizar la operación.');
        }

        return $id;
    }

    protected function userId(?int $userId = null): int
    {
        $id = (int) ($userId ?: (Auth::id() ?? 0));

        if ($id <= 0) {
            throw new Exception('No se pudo determinar el usuario autenticado para realizar la operación.');
        }

        return $id;
    }

    /**
     * Cargar todos los catálogos y datos iniciales para el arranque del POS
     */
    public function datosInicialesPos(?int $empresaId = null): array
    {
        $empresaId = $this->empresaId($empresaId);

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
            ->get(['id', 'nombre', 'descripcion', 'es_efectivo']);

        // 4. Moneda y Tasa de Cambio Activa
        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
        $tasaUsd = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;

        // 5. Productos con existencias por almacén y códigos de barra
        $productos = Producto::with([
            'stockAlmacenes.almacen',
            'codigosBarra',
            'categoria',
            'seriales' => fn ($q) => $q->where('estado', 'disponible'),
        ])
            ->where('empresa_id', $empresaId)
            ->where('estado', true)
            ->get()
            ->map(function ($p) use ($tasaUsd) {
                $costoUsd = (float) $p->precio_costo_usd;
                $detalBaseUsd = (float) $p->precio_detal_usd;
                $mayorBaseUsd = (float) $p->precio_mayorista_usd;
                $aplicaIva = (bool) $p->aplica_iva;
                $ivaPorcentaje = (float) ($p->iva_porcentaje ?? 16.00);
                $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;

                $detalFinalUsd = round($detalBaseUsd * $factorIva, 2);
                $mayorFinalUsd = round($mayorBaseUsd * $factorIva, 2);

                $serialesDisponibles = $p->seriales->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'numero_serial' => $s->numero_serial,
                        'variante_color' => $s->variante_color,
                        'almacen_id' => $s->almacen_id,
                    ];
                })->values()->toArray();

                $codigos = $p->codigosBarra->pluck('codigo_barra')->toArray();
                foreach ($serialesDisponibles as $sd) {
                    if (! empty($sd['numero_serial']) && ! in_array($sd['numero_serial'], $codigos, true)) {
                        $codigos[] = $sd['numero_serial'];
                    }
                }

                return [
                    'id' => $p->id,
                    'tipo_item' => 'producto',
                    'codigo_interno' => $p->codigo_interno,
                    'nombre' => $p->nombre,
                    'unidad_medida' => $p->unidad_medida,
                    'categoria_nombre' => $p->categoria?->nombre ?? 'General',
                    'precio_costo_usd' => $costoUsd,
                    'precio_costo_bs' => round($costoUsd * $tasaUsd, 2),
                    'precio_detal_usd' => $detalFinalUsd,
                    'precio_detal_bs' => round($detalFinalUsd * $tasaUsd, 2),
                    'precio_mayorista_usd' => $mayorFinalUsd,
                    'precio_mayorista_bs' => round($mayorFinalUsd * $tasaUsd, 2),
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'aplica_igtf' => (bool) $p->aplica_igtf,
                    'igtf_porcentaje' => (float) ($p->igtf_porcentaje ?? 3.00),
                    'maneja_variantes' => (bool) $p->maneja_variantes,
                    'atributos_variantes' => $p->atributos_variantes ?? [],
                    'maneja_seriales' => (bool) $p->maneja_seriales,
                    'seriales_disponibles' => $serialesDisponibles,
                    'codigos_barra' => array_values(array_filter($codigos)),
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
                $precioBaseUsd = (float) $s->precio_venta_usd;
                $aplicaIva = (bool) $s->aplica_iva;
                $ivaPorcentaje = (float) ($s->iva_porcentaje ?? 16.00);
                $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;

                $precioFinalUsd = round($precioBaseUsd * $factorIva, 2);

                return [
                    'id' => $s->id,
                    'tipo_item' => 'servicio',
                    'codigo_interno' => (string) ($s->codigo ?: $s->id),
                    'nombre' => $s->nombre,
                    'unidad_medida' => 'SRV',
                    'categoria_nombre' => $s->categoria?->nombre ?? 'Servicio',
                    'precio_costo_usd' => (float) $s->precio_costo_usd,
                    'precio_costo_bs' => (float) $s->precio_costo_bs,
                    'precio_detal_usd' => $precioFinalUsd,
                    'precio_detal_bs' => round($precioFinalUsd * $tasaUsd, 2),
                    'precio_mayorista_usd' => $precioFinalUsd,
                    'precio_mayorista_bs' => round($precioFinalUsd * $tasaUsd, 2),
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPorcentaje,
                    'aplica_igtf' => (bool) $s->aplica_igtf,
                    'igtf_porcentaje' => (float) ($s->igtf_porcentaje ?? 3.00),
                    'codigos_barra' => array_values(array_filter([(string) $s->codigo, (string) $s->id])),
                    'stock_almacenes' => [],
                ];
            });

        // 7. Motos disponibles
        $motos = Moto::with('almacen')
            ->where('empresa_id', $empresaId)
            ->where('estado', 'disponible')
            ->get()
            ->map(function ($m) use ($tasaUsd) {
                $costoUsd = (float) $m->precio_costo_usd;
                $detalBaseUsd = (float) $m->precio_detal_usd;
                $mayorBaseUsd = (float) $m->precio_mayorista_usd;
                $factorIva = 1.16;

                $detalConIvaUsd = (float) ($m->precio_detal_con_iva_usd > 0 ? $m->precio_detal_con_iva_usd : round($detalBaseUsd * $factorIva, 2));
                $mayorConIvaUsd = (float) ($m->precio_mayorista_con_iva_usd > 0 ? $m->precio_mayorista_con_iva_usd : round($mayorBaseUsd * $factorIva, 2));

                $referencia = trim($m->referencia ?? '');
                $placa = trim($m->placa ?? '');
                $placaTexto = $placa !== '' ? " - Placa: {$placa}" : '';
                $codigos = array_values(array_filter([
                    $referencia,
                    $m->numero_niv,
                    $m->numero_chasis,
                    $m->numero_motor,
                    $m->certificado_origen,
                    $placa,
                ]));

                $colorTexto = ! empty($m->color) ? " - Color: {$m->color}" : '';
                $motorTexto = ! empty($m->numero_motor) ? " - Motor: {$m->numero_motor}" : '';

                return [
                    'id' => $m->id,
                    'tipo_item' => 'moto',
                    'codigo_interno' => $referencia ?: $m->numero_niv,
                    'referencia' => $referencia,
                    'nombre' => "{$m->marca} {$m->modelo} ({$m->anio})".($referencia ? " [Ref: #{$referencia}]" : '')." - NIV: {$m->numero_niv}{$colorTexto}{$motorTexto}",
                    'marca' => $m->marca,
                    'modelo' => $m->modelo,
                    'anio' => $m->anio,
                    'color' => $m->color,
                    'cilindrada' => $m->cilindrada,
                    'numero_niv' => $m->numero_niv,
                    'numero_chasis' => $m->numero_chasis,
                    'numero_motor' => $m->numero_motor,
                    'certificado_origen' => $m->certificado_origen,
                    'placa' => $placa ?: null,
                    'unidad_medida' => 'UND',
                    'categoria_nombre' => 'Motos & Vehículos',
                    'precio_costo_usd' => $costoUsd,
                    'precio_costo_bs' => round($costoUsd * $tasaUsd, 2),
                    'precio_detal_usd' => $detalConIvaUsd,
                    'precio_detal_bs' => round($detalConIvaUsd * $tasaUsd, 2),
                    'precio_mayorista_usd' => $mayorConIvaUsd,
                    'precio_mayorista_bs' => round($mayorConIvaUsd * $tasaUsd, 2),
                    'aplica_iva' => true,
                    'iva_porcentaje' => 16.00,
                    'aplica_igtf' => true,
                    'igtf_porcentaje' => 3.00,
                    'codigos_barra' => $codigos,
                    'almacen_id' => $m->almacen_id,
                    'stock_almacenes' => [
                        [
                            'almacen_id' => $m->almacen_id,
                            'almacen_nombre' => $m->almacen?->nombre ?? 'Almacén',
                            'cantidad_actual' => 1,
                        ],
                    ],
                ];
            });

        // 6. Roles, Control de Acceso de Usuario y Vendedores activos
        $user = Auth::user();
        $empresa = Empresa::findOrFail($empresaId);
        $manejaVendedores = (bool) ($empresa->maneja_vendedores ?? false);

        $vendedores = $manejaVendedores
            ? Vendedor::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo_documento', 'documento', 'comision_porcentaje', 'user_id'])
            : collect([]);

        $esAdmin = $this->esAdmin($user);
        $vendedorAsociado = ($manejaVendedores && $user)
            ? $user->vendedor()->where('empresa_id', $empresaId)->where('estado', true)->first()
            : null;
        $esVendedor = $this->esVendedor($user);
        $esCajero = (! $esAdmin) && (! $esVendedor);

        // Turnos Activos de la Empresa y Cajas Disponibles
        $turnosActivos = CajaTurno::with(['caja', 'usuario', 'aperturadoPor'])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'abierta')
            ->orderBy('id', 'asc')
            ->get();

        $cajaTurnoAsignada = $user ? $user->cajaTurnoActivo($empresaId)?->load(['caja', 'aperturadoPor']) : null;
        $turnoActivo = null;
        if ($esAdmin) {
            $turnoActivo = $turnosActivos->count() === 1 ? $turnosActivos->first() : null;
        } elseif ($esCajero) {
            $turnoActivo = $cajaTurnoAsignada;
        }

        $puedeOperar = false;
        if ($esAdmin) {
            $puedeOperar = $turnosActivos->isNotEmpty();
        } elseif ($esVendedor) {
            $puedeOperar = true;
        } elseif ($esCajero) {
            $puedeOperar = ($cajaTurnoAsignada !== null);
        }

        $cajasDisponibles = Caja::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->whereDoesntHave('turnos', fn ($q) => $q->where('estado', 'abierta'))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        return [
            'cliente_defecto' => $clienteDefecto,
            'almacenes' => $almacenes,
            'metodos_pago' => $metodosPago,
            'vendedores' => $vendedores,
            'maneja_vendedores' => $manejaVendedores,
            'es_admin' => $esAdmin,
            'es_vendedor' => $esVendedor,
            'es_cajero' => $esCajero,
            'vendedor_asociado' => $vendedorAsociado,
            'puede_operar' => $puedeOperar,
            'turno_activo' => $turnoActivo,
            'turnos_activos' => $turnosActivos,
            'cajas_disponibles' => $cajasDisponibles,
            'tasa_usd' => $tasaUsd,
            'proximo_codigo' => $this->generarCodigo($empresaId, false),
            'productos' => $productos->concat($servicios)->concat($motos)->values(),
        ];
    }

    /**
     * Determinar si el usuario tiene rol administrativo (SuperAdmin o Admin)
     */
    protected function esAdmin(?User $user): bool
    {
        return $user ? $user->hasAnyRole(['SuperAdmin', 'Admin']) : false;
    }

    /**
     * Determinar con criterio único si el usuario opera exclusivamente como Vendedor (preventas sin facturación)
     */
    protected function esVendedor(?User $user): bool
    {
        if (! $user || $this->esAdmin($user)) {
            return false;
        }

        return ($user->can('pos.preventa') || $user->hasRole('Vendedor')) && ! $user->can('ventas.crear');
    }

    /**
     * Resolver y validar un turno de caja abierto para facturar, devolver o anular
     */
    protected function resolverTurnoOperacion(int $empresaId, User $user, ?int $cajaTurnoIdInput = null): CajaTurno
    {
        $esAdmin = $this->esAdmin($user);

        if ($esAdmin) {
            $turnosAbiertos = CajaTurno::where('empresa_id', $empresaId)
                ->where('estado', 'abierta')
                ->orderBy('id', 'asc')
                ->get();

            if ($turnosAbiertos->isEmpty()) {
                throw new Exception('No hay cajas abiertas para procesar la operación.');
            }

            $cajaTurnoId = (int) ($cajaTurnoIdInput ?? 0);
            if ($cajaTurnoId <= 0) {
                if ($turnosAbiertos->count() === 1) {
                    return $turnosAbiertos->first();
                }
                throw new Exception('Debes seleccionar una caja con turno abierto para procesar la operación.');
            }

            $cajaTurno = CajaTurno::where('empresa_id', $empresaId)
                ->where('id', $cajaTurnoId)
                ->where('estado', 'abierta')
                ->first();

            if (! $cajaTurno) {
                throw new Exception('El turno de caja seleccionado no pertenece a la empresa o no se encuentra abierto.');
            }

            return $cajaTurno;
        }

        $turnoPropio = $user->cajaTurnoActivo($empresaId);
        if (! $turnoPropio) {
            throw new Exception('No tienes una caja registradora aperturada asignada para procesar cobros.');
        }

        if ($cajaTurnoIdInput !== null && $cajaTurnoIdInput > 0 && (int) $cajaTurnoIdInput !== (int) $turnoPropio->id) {
            throw new Exception('Solo puedes operar utilizando tu propio turno de caja abierto.');
        }

        return $turnoPropio;
    }

    /**
     * Generar correlativo de venta (Ej: VEN-00001) por empresa
     */
    public function generarCodigo(int $empresaId, ?bool $incrementar = null): string
    {
        $usarLock = $incrementar ?? (DB::transactionLevel() > 0);
        $numero = $usarLock
            ? Secuencia::siguiente($empresaId, 'venta')
            : Secuencia::proximoValorSinIncrementar($empresaId, 'venta');

        $correlativo = str_pad((string) $numero, 5, '0', STR_PAD_LEFT);

        return "VEN-{$correlativo}";
    }

    /**
     * Generar número de control fiscal (Ej: 00-00000001) por empresa
     */
    public function generarNumeroControl(int $empresaId, ?bool $incrementar = null): string
    {
        $usarLock = $incrementar ?? false;
        $numero = $usarLock
            ? Secuencia::siguiente($empresaId, 'numero_control')
            : Secuencia::proximoValorSinIncrementar($empresaId, 'numero_control');

        $correlativo = str_pad((string) $numero, 8, '0', STR_PAD_LEFT);

        return "00-{$correlativo}";
    }

    /**
     * Generar correlativo de devolución (Ej: DEV-00001) por empresa
     */
    public function generarCodigoDevolucion(int $empresaId, ?bool $incrementar = null): string
    {
        $usarLock = $incrementar ?? (DB::transactionLevel() > 0);
        $numero = $usarLock
            ? Secuencia::siguiente($empresaId, 'devolucion')
            : Secuencia::proximoValorSinIncrementar($empresaId, 'devolucion');

        $correlativo = str_pad((string) $numero, 5, '0', STR_PAD_LEFT);

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
    public function procesarVenta(array $datos, ?int $empresaId = null, ?int $userId = null): Venta
    {
        return DB::transaction(function () use ($datos, $empresaId, $userId) {
            $empresaId = $this->empresaId($empresaId);
            $userId = $this->userId($userId);

            $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
            $tasaOficial = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 1.0000;
            $tasaCambio = (float) ($datos['tasa_cambio'] ?? $tasaOficial);
            if ($tasaCambio <= 0) {
                $tasaCambio = $tasaOficial;
            }

            $clienteId = (int) ($datos['cliente_id'] ?? 0);
            $cliente = Cliente::findOrFail($clienteId);

            $almacenIdGlobal = (int) ($datos['almacen_id'] ?? 0);
            if ($almacenIdGlobal <= 0) {
                throw new Exception('El almacén de despacho es obligatorio para procesar la venta.');
            }

            $almacenValido = Almacen::where('empresa_id', $empresaId)
                ->where('id', $almacenIdGlobal)
                ->where('estado', true)
                ->exists();

            if (! $almacenValido) {
                throw new Exception('El almacén seleccionado no pertenece a la empresa o no se encuentra disponible.');
            }
            $tipoVenta = $datos['tipo_venta'] ?? 'detal';
            if (! in_array($tipoVenta, ['detal', 'mayor', 'mayorista'], true)) {
                throw new Exception("El tipo de venta '{$tipoVenta}' no es válido.");
            }
            $esMayor = in_array($tipoVenta, ['mayor', 'mayorista'], true);
            $condicionPago = $datos['condicion_pago'] ?? 'contado';
            $diasCredito = (int) ($datos['dias_credito'] ?? 0);
            $observaciones = $datos['observaciones'] ?? null;

            $userCobro = User::find($userId);
            if (! $userCobro) {
                throw new Exception('No se pudo determinar el usuario que procesa la venta.');
            }
            $esAdmin = $this->esAdmin($userCobro);
            if ($this->esVendedor($userCobro)) {
                throw new AuthorizationException('Un usuario con perfil Vendedor solo puede gestionar preventas y no está autorizado para facturar.');
            }
            $puedeDescontar = $esAdmin || $userCobro->can('ventas.descuentos');

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

                if ($almacenItem !== $almacenIdGlobal) {
                    $almacenItemValido = Almacen::where('empresa_id', $empresaId)
                        ->where('id', $almacenItem)
                        ->where('estado', true)
                        ->exists();

                    if (! $almacenItemValido) {
                        throw new Exception('El almacén indicado para el ítem no pertenece a la empresa o se encuentra inactivo.');
                    }
                }

                $producto = null;
                $moto = null;
                $servicio = null;
                $costoUnitarioUsd = 0;
                $aplicaIva = false;
                $ivaPorcentaje = 0;
                $nombreItem = null;
                $serialIdentificador = null;
                $serialId = ! empty($it['producto_serial_id']) ? (int) $it['producto_serial_id'] : null;
                $precioListaConIva = 0.0;
                $precioListaSinIva = 0.0;

                if ($tipoItem === 'producto') {
                    $producto = Producto::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->where('estado', true)
                        ->first();

                    if (! $producto) {
                        throw new Exception('El producto seleccionado no existe, no pertenece a la empresa o está inactivo.');
                    }

                    $costoUnitarioUsd = (float) $producto->precio_costo_usd;
                    $aplicaIva = (bool) $producto->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($producto->iva_porcentaje ?? 16.00) : 0;
                    $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;
                    $precioBase = $esMayor
                        ? (float) ($producto->precio_mayorista_usd > 0 ? $producto->precio_mayorista_usd : $producto->precio_detal_usd)
                        : (float) $producto->precio_detal_usd;

                    $precioListaConIva = round($precioBase * $factorIva, 2);
                    $precioListaSinIva = round($precioBase, 2);
                    $nombreItem = $producto->nombre;
                    $serialIdentificador = $producto->codigo_interno;

                    if ($producto->maneja_seriales && ! $serialId) {
                        throw new Exception("El producto '{$producto->nombre}' maneja seriales y requiere seleccionar un serial disponible.");
                    }

                    if ($serialId) {
                        $serialModel = ProductoSerial::where('id', $serialId)
                            ->where('empresa_id', $empresaId)
                            ->lockForUpdate()
                            ->first();

                        if (
                            ! $serialModel
                            || (int) $serialModel->producto_id !== (int) $producto->id
                            || (int) $serialModel->almacen_id !== (int) $almacenItem
                            || $serialModel->estado !== 'disponible'
                        ) {
                            throw new Exception("El serial seleccionado para '{$producto->nombre}' no pertenece al producto/almacén o no está disponible.");
                        }

                        if (! empty($serialModel->numero_serial)) {
                            $serialIdentificador = $serialModel->numero_serial;
                        }
                    }
                } elseif ($tipoItem === 'moto') {
                    $moto = Moto::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->lockForUpdate()
                        ->first();

                    if (! $moto || $moto->estado !== 'disponible') {
                        throw new Exception('La moto seleccionada no pertenece a la empresa o ya no se encuentra disponible para la venta.');
                    }

                    $costoUnitarioUsd = (float) $moto->precio_costo_usd;
                    $aplicaIva = true;
                    $ivaPorcentaje = (float) ($moto->iva_porcentaje ?? 16.00);
                    $factorIva = $ivaPorcentaje > 0 ? (1 + ($ivaPorcentaje / 100)) : 1.16;
                    $detalBase = (float) $moto->precio_detal_usd;
                    $mayorBase = (float) ($moto->precio_mayorista_usd > 0 ? $moto->precio_mayorista_usd : $moto->precio_detal_usd);
                    $detalConIva = (float) ($moto->precio_detal_con_iva_usd > 0 ? $moto->precio_detal_con_iva_usd : round($detalBase * $factorIva, 2));
                    $mayorConIva = (float) ($moto->precio_mayorista_con_iva_usd > 0 ? $moto->precio_mayorista_con_iva_usd : round($mayorBase * $factorIva, 2));

                    $precioListaConIva = round($esMayor ? $mayorConIva : $detalConIva, 2);
                    $precioListaSinIva = round($esMayor ? $mayorBase : $detalBase, 2);

                    $colorTexto = ! empty($moto->color) ? " - Color: {$moto->color}" : '';
                    $motorTexto = ! empty($moto->numero_motor) ? " - Motor: {$moto->numero_motor}" : '';
                    $nombreItem = "{$moto->marca} {$moto->modelo} ({$moto->anio}){$colorTexto}{$motorTexto}";
                    $serialIdentificador = $moto->numero_niv;
                    $almacenItem = $moto->almacen_id ?: $almacenItem;
                } elseif ($tipoItem === 'servicio') {
                    $servicio = Servicio::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->where('estado', true)
                        ->first();

                    if (! $servicio) {
                        throw new Exception('El servicio seleccionado no pertenece a la empresa o está inactivo.');
                    }

                    $costoUnitarioUsd = (float) $servicio->precio_costo_usd;
                    $aplicaIva = (bool) $servicio->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($servicio->iva_porcentaje ?? 16.00) : 0;
                    $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;
                    $precioBase = (float) $servicio->precio_venta_usd;

                    $precioListaConIva = round($precioBase * $factorIva, 2);
                    $precioListaSinIva = round($precioBase, 2);
                    $nombreItem = $servicio->nombre;
                    $serialIdentificador = $servicio->codigo;
                } else {
                    throw new Exception("El tipo de ítem '{$tipoItem}' no es válido.");
                }

                $descuentoPorc = (float) ($it['descuento_porcentaje'] ?? 0);
                $descuentoInputUsd = isset($it['descuento_usd']) ? (float) $it['descuento_usd'] : 0.0;

                if ($descuentoPorc < 0 || $descuentoPorc > 100 || $descuentoInputUsd < 0) {
                    throw new Exception("El descuento para '{$nombreItem}' no puede ser negativo ni superar el 100%.");
                }

                $difiereDeLista = abs($precioUnitarioUsd - $precioListaConIva) > 0.01
                    && abs($precioUnitarioUsd - $precioListaSinIva) > 0.01;
                $tieneDescuento = $descuentoPorc > 0 || $descuentoInputUsd > 0;

                if (($difiereDeLista || $tieneDescuento) && ! $puedeDescontar) {
                    throw new Exception("No tienes permiso para modificar precios de lista ni aplicar descuentos en '{$nombreItem}' (se requiere el permiso ventas.descuentos).");
                }

                if (! $difiereDeLista) {
                    $precioUnitarioUsd = abs($precioUnitarioUsd - $precioListaConIva) <= 0.01
                        ? $precioListaConIva
                        : $precioListaSinIva;
                }

                $renglonBrutoUsd = round($cantidad * $precioUnitarioUsd, 2);
                if ($descuentoInputUsd > $renglonBrutoUsd + 0.01) {
                    throw new Exception("El monto de descuento para '{$nombreItem}' no puede superar el 100% del monto del renglón.");
                }
                $montoBrutoUsd += $renglonBrutoUsd;

                $descuentoItemUsd = 0;
                if ($descuentoInputUsd > 0) {
                    $descuentoItemUsd = min($renglonBrutoUsd, round($descuentoInputUsd, 2));
                    if ($renglonBrutoUsd > 0 && $descuentoPorc <= 0) {
                        $descuentoPorc = round(($descuentoItemUsd / $renglonBrutoUsd) * 100, 2);
                    }
                    $totalDescuentosUsd += $descuentoItemUsd;
                } elseif ($descuentoPorc > 0) {
                    $descuentoItemUsd = round($renglonBrutoUsd * ($descuentoPorc / 100), 2);
                    $totalDescuentosUsd += $descuentoItemUsd;
                }

                $renglonTotalNetoUsd = round($renglonBrutoUsd - $descuentoItemUsd, 2);
                $precioNetoUnitarioUsd = $cantidad > 0 ? round($renglonTotalNetoUsd / $cantidad, 4) : 0.0;

                if ($costoUnitarioUsd > 0 && ($precioUnitarioUsd + 0.0001 < $costoUnitarioUsd || $precioNetoUnitarioUsd + 0.0001 < $costoUnitarioUsd)) {
                    throw new Exception("El precio de venta neto de '{$nombreItem}' ($".number_format($precioNetoUnitarioUsd, 2).') no puede ser menor al costo ($'.number_format($costoUnitarioUsd, 2).').');
                }

                if ($aplicaIva && $ivaPorcentaje > 0) {
                    $factorIva = 1 + ($ivaPorcentaje / 100);
                    $baseImponibleRenglonUsd = round($renglonTotalNetoUsd / $factorIva, 2);
                    $ivaItemUsd = round($renglonTotalNetoUsd - $baseImponibleRenglonUsd, 2);
                } else {
                    $baseImponibleRenglonUsd = $renglonTotalNetoUsd;
                    $ivaItemUsd = 0;
                }

                $subtotalNetoUsd += $baseImponibleRenglonUsd;
                $ivaTotalUsd += $ivaItemUsd;

                $detallesParaInsertar[] = [
                    'producto_id' => $producto?->id,
                    'moto_id' => $moto?->id,
                    'servicio_id' => $servicio?->id,
                    'producto_model' => $producto,
                    'moto_model' => $moto,
                    'almacen_id' => $almacenItem,
                    'tipo_item' => $tipoItem,
                    'nombre_item' => $nombreItem,
                    'variante_texto' => ! empty($it['variante_texto']) ? trim($it['variante_texto']) : null,
                    'serial_identificador' => $serialIdentificador,
                    'producto_serial_id' => ! empty($it['producto_serial_id']) ? (int) $it['producto_serial_id'] : null,
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
                    'subtotal_usd' => $baseImponibleRenglonUsd,
                    'subtotal_bs' => round($baseImponibleRenglonUsd * $tasaCambio, 2),
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
                $monedaPago = strtoupper(trim((string) ($p['moneda'] ?? 'USD')));
                $tasaPago = (float) ($p['tasa_cambio'] ?? $tasaCambio);
                $referencia = trim($p['referencia'] ?? '') ?: null;

                if ($metodoId <= 0 || $montoOrigen <= 0) {
                    continue;
                }

                if (! in_array($monedaPago, ['USD', 'VES', 'BS'], true)) {
                    throw new Exception("La moneda de pago '{$monedaPago}' no es válida. Valores permitidos: USD, VES, BS.");
                }

                $metodoValido = MetodoPago::where('id', $metodoId)
                    ->where('estado', true)
                    ->exists();

                if (! $metodoValido) {
                    throw new Exception('El método de pago seleccionado no existe o se encuentra inactivo.');
                }

                $esBolivares = in_array($monedaPago, ['VES', 'BS'], true);
                $montoUsd = $esBolivares ? ($tasaPago > 0 ? round($montoOrigen / $tasaPago, 2) : 0) : $montoOrigen;
                $montoBs = $esBolivares ? $montoOrigen : round($montoOrigen * $tasaPago, 2);

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

            // Vendedor y Comisión
            $empresa = Empresa::findOrFail($empresaId);
            $manejaVendedores = (bool) ($empresa->maneja_vendedores ?? false);
            $vendedor = null;
            $comisionPorcentaje = 0.0;
            $comisionMontoUsd = 0.0;
            $comisionMontoBs = 0.0;

            if ($manejaVendedores) {
                $vendedorId = ! empty($datos['vendedor_id']) ? (int) $datos['vendedor_id'] : null;

                if (! empty($datos['venta_en_espera_id'])) {
                    $esperaRecuperada = VentaEnEspera::where('id', (int) $datos['venta_en_espera_id'])
                        ->where('empresa_id', $empresaId)
                        ->first();

                    if ($esperaRecuperada && ! empty($esperaRecuperada->datos_json['vendedor_id']) && ! $esAdmin) {
                        $vendedorId = (int) $esperaRecuperada->datos_json['vendedor_id'];
                    }
                }

                $vendedor = $vendedorId ? Vendedor::where('empresa_id', $empresaId)->find($vendedorId) : null;
                $comisionPorcentaje = $vendedor ? (float) $vendedor->comision_porcentaje : 0.0;
                $comisionMontoUsd = round($subtotalNetoUsd * ($comisionPorcentaje / 100), 2);
                $comisionMontoBs = round($comisionMontoUsd * $tasaCambio, 2);
            }

            $comisionEstado = ($vendedor && $comisionMontoUsd > 0) ? 'pendiente' : 'sin_comision';

            // Turno de Caja
            $cajaTurno = null;

            if ($esAdmin) {
                $hayTurnosAbiertos = CajaTurno::where('empresa_id', $empresaId)
                    ->where('estado', 'abierta')
                    ->exists();

                if (! $hayTurnosAbiertos) {
                    throw new Exception('No hay cajas abiertas para procesar la venta.');
                }

                $cajaTurnoId = (int) ($datos['caja_turno_id'] ?? 0);
                if ($cajaTurnoId <= 0) {
                    throw new Exception('Debes seleccionar una caja con turno abierto para procesar la venta.');
                }

                $cajaTurno = CajaTurno::where('empresa_id', $empresaId)
                    ->where('id', $cajaTurnoId)
                    ->where('estado', 'abierta')
                    ->first();

                if (! $cajaTurno) {
                    throw new Exception('El turno de caja seleccionado no pertenece a la empresa o no se encuentra abierto.');
                }
            } else {
                $turnoPropio = $userCobro->cajaTurnoActivo($empresaId);

                if (! $turnoPropio) {
                    throw new Exception('No tienes una caja registradora aperturada asignada para procesar cobros.');
                }

                if (! empty($datos['caja_turno_id']) && (int) $datos['caja_turno_id'] !== (int) $turnoPropio->id) {
                    throw new Exception('Solo puedes facturar utilizando tu propio turno de caja abierto.');
                }

                $cajaTurno = $turnoPropio;
            }

            // 1. Guardar Cabecera Venta
            $venta = Venta::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'almacen_id' => $almacenIdGlobal,
                'caja_id' => $cajaTurno->caja_id,
                'caja_turno_id' => $cajaTurno->id,
                'vendedor_id' => $vendedor?->id,
                'comision_porcentaje' => $comisionPorcentaje,
                'comision_monto_usd' => $comisionMontoUsd,
                'comision_monto_bs' => $comisionMontoBs,
                'comision_estado' => $comisionEstado,
                'user_id' => $userId,
                'codigo' => $codigo,
                'numero_control' => $datos['numero_control'] ?? $this->generarNumeroControl($empresaId, true),
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
                    'moto_id' => $det['moto_id'],
                    'servicio_id' => $det['servicio_id'],
                    'almacen_id' => $det['almacen_id'],
                    'tipo_item' => $det['tipo_item'],
                    'nombre_item' => $det['nombre_item'],
                    'variante_texto' => $det['variante_texto'],
                    'serial_identificador' => $det['serial_identificador'],
                    'producto_serial_id' => $det['producto_serial_id'],
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

                // Actualizar serial único físico a vendido si aplica
                if (! empty($det['producto_serial_id'])) {
                    ProductoSerial::where('id', $det['producto_serial_id'])
                        ->where('empresa_id', $empresaId)
                        ->update([
                            'estado' => 'vendido',
                            'venta_id' => $venta->id,
                            'venta_detalle_id' => $detModel->id,
                        ]);
                }

                // Descontar inventario solo si es producto físico
                if ($det['tipo_item'] === 'producto' && $det['producto_model'] && $det['producto_id']) {
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

                // Si es moto, actualizar su estado a 'vendida'
                if ($det['tipo_item'] === 'moto' && $det['moto_model']) {
                    $det['moto_model']->update(['estado' => 'vendida']);
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

            // 3b. Si hubo vuelto y hay turno activo, registrarlo como egreso en caja_movimientos
            if (($vueltoUsd > 0 || $vueltoBs > 0) && $cajaTurno) {
                $metodosIds = array_values(array_unique(array_column($pagosParaInsertar, 'metodo_pago_id')));
                $metodosMap = MetodoPago::whereIn('id', $metodosIds)->get()->keyBy('id');

                $pagoEfectivo = null;
                foreach ($pagosParaInsertar as $p) {
                    $m = $metodosMap->get($p['metodo_pago_id']);
                    if ($m && $m->es_efectivo) {
                        $pagoEfectivo = $p;
                        break;
                    }
                }

                $pagoRef = $pagoEfectivo
                    ?? ($pagosParaInsertar[0] ?? null);

                $metodoVueltoId = $pagoRef['metodo_pago_id']
                    ?? MetodoPago::where('estado', true)->where('es_efectivo', true)->value('id');
                $monedaVuelto = $pagoRef['moneda'] ?? 'USD';

                CajaMovimiento::create([
                    'empresa_id' => $empresaId,
                    'caja_turno_id' => $cajaTurno->id,
                    'user_id' => $userId,
                    'tipo' => 'egreso',
                    'metodo_pago_id' => $metodoVueltoId,
                    'moneda' => $monedaVuelto,
                    'monto_usd' => $vueltoUsd,
                    'monto_bs' => $vueltoBs,
                    'concepto' => "Vuelto de venta #{$venta->codigo}",
                    'comprobante_referencia' => $venta->codigo,
                    'documento_tipo' => 'venta',
                    'documento_id' => $venta->id,
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

            // Si proviene de una venta/preventa en espera, eliminarla de forma segura dentro de la transacción
            if (! empty($datos['venta_en_espera_id'])) {
                VentaEnEspera::where('id', (int) $datos['venta_en_espera_id'])
                    ->where('empresa_id', $empresaId)
                    ->delete();
            }

            return $venta->load(['cliente', 'almacen', 'detalles.producto', 'detalles.moto', 'detalles.servicio', 'pagos.metodoPago']);
        });
    }

    /**
     * Guardar venta en espera (pausar carrito / preventa)
     */
    public function guardarEnEspera(array $datos, ?int $empresaId = null, ?int $userId = null): VentaEnEspera
    {
        $empresaId = $this->empresaId($empresaId);
        $userId = $this->userId($userId);
        $user = User::find($userId);
        if (! $user || (! $this->esAdmin($user) && ! $user->canAny(['pos.preventa', 'ventas.crear']))) {
            throw new AuthorizationException('No tienes permiso para registrar preventas en espera.');
        }

        $vendedorAsociado = $user->vendedor()->where('empresa_id', $empresaId)->where('estado', true)->first();

        if ($vendedorAsociado) {
            $datos['vendedor_id'] = $vendedorAsociado->id;
            $datos['vendedor_nombre'] = $vendedorAsociado->nombre;
            $datos['comision_porcentaje'] = (float) $vendedorAsociado->comision_porcentaje;
        } elseif ($this->esVendedor($user)) {
            unset($datos['vendedor_id'], $datos['vendedor_nombre'], $datos['comision_porcentaje']);
        } elseif (! empty($datos['vendedor_id'])) {
            $vendedor = Vendedor::where('empresa_id', $empresaId)->where('estado', true)->find($datos['vendedor_id']);
            if ($vendedor) {
                $datos['vendedor_id'] = $vendedor->id;
                $datos['vendedor_nombre'] = $vendedor->nombre;
                $datos['comision_porcentaje'] = (float) $vendedor->comision_porcentaje;
            }
        }

        $nota = trim($datos['nota_referencia'] ?? '');
        if (empty($nota)) {
            $vendedorLabel = ! empty($datos['vendedor_nombre']) ? " - Asesor: {$datos['vendedor_nombre']}" : '';
            $nota = 'Preventa '.Carbon::now()->format('h:i A').$vendedorLabel;
        }

        return VentaEnEspera::create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'cliente_id' => ! empty($datos['cliente_id']) ? (int) $datos['cliente_id'] : null,
            'tipo_venta' => $datos['tipo_venta'] ?? 'detal',
            'nota_referencia' => $nota,
            'datos_json' => $datos,
            'total_usd' => (float) ($datos['total_usd'] ?? 0),
            'total_bs' => (float) ($datos['total_bs'] ?? 0),
        ]);
    }

    /**
     * Listar ventas en espera activas
     */
    public function listarEnEspera(?int $empresaId = null, ?int $userId = null): array
    {
        $empresaId = $this->empresaId($empresaId);
        $user = $userId ? User::find($userId) : Auth::user();

        $query = VentaEnEspera::with(['cliente', 'usuario'])
            ->where('empresa_id', $empresaId);

        if ($user && ! $this->esAdmin($user) && ! $user->can('ventas.crear')) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('id', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Recuperar venta en espera sin eliminarla prematuramente
     */
    public function recuperarEnEspera(int $id, ?int $empresaId = null, ?int $userId = null): array
    {
        $empresaId = $this->empresaId($empresaId);
        $espera = VentaEnEspera::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $user = $userId ? User::find($userId) : Auth::user();
        if ($user && ! $this->esAdmin($user) && ! $user->can('ventas.crear') && (int) $espera->user_id !== (int) $user->id) {
            throw new AuthorizationException('No tienes autorización para recuperar preventas de otro vendedor.');
        }

        $datos = $espera->datos_json;
        $datos['venta_en_espera_id'] = $espera->id;

        return $datos;
    }

    /**
     * Eliminar venta en espera
     */
    public function eliminarEnEspera(int $id, ?int $empresaId = null, ?int $userId = null): bool
    {
        $empresaId = $this->empresaId($empresaId);
        $espera = VentaEnEspera::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $user = $userId ? User::find($userId) : Auth::user();
        if ($user && ! $this->esAdmin($user) && ! $user->can('ventas.crear') && (int) $espera->user_id !== (int) $user->id) {
            throw new AuthorizationException('No tienes autorización para eliminar preventas de otro vendedor.');
        }

        return (bool) $espera->delete();
    }

    /**
     * Buscar factura para devolución por código o ID
     */
    public function buscarFacturaDevolucion(string $busqueda, ?int $empresaId = null): array
    {
        $empresaId = $this->empresaId($empresaId);
        $busqueda = trim($busqueda);

        $venta = Venta::with(['cliente', 'almacen', 'detalles.producto', 'detalles.moto', 'detalles.servicio', 'pagos.metodoPago', 'devoluciones.detalles'])
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($busqueda) {
                $q->where('codigo', $busqueda)
                    ->orWhere('numero_control', $busqueda)
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
    public function procesarDevolucion(array $datos, ?int $empresaId = null, ?int $userId = null): DevolucionVenta
    {
        return DB::transaction(function () use ($datos, $empresaId, $userId) {
            $empresaId = $this->empresaId($empresaId);
            $userId = $this->userId($userId);

            $user = User::find($userId);
            if (! $user) {
                throw new Exception('No se pudo determinar el usuario que procesa la devolución.');
            }

            $esAdmin = $this->esAdmin($user);
            if (! $esAdmin && ! $user->can('ventas.anular')) {
                throw new AuthorizationException('No tienes permiso para procesar devoluciones de venta (se requiere ventas.anular).');
            }

            $cajaTurno = $this->resolverTurnoOperacion(
                $empresaId,
                $user,
                ! empty($datos['caja_turno_id']) ? (int) $datos['caja_turno_id'] : null
            );

            $ventaId = (int) ($datos['venta_id'] ?? 0);
            $venta = Venta::with(['detalles', 'pagos.metodoPago', 'cuentaPorCobrar'])
                ->where('id', $ventaId)
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($venta->estado === 'anulada' || $venta->estado === 'devuelta_total') {
                throw new Exception("La factura '{$venta->codigo}' ya se encuentra {$venta->estado} y no admite nuevas devoluciones.");
            }

            $motivo = trim($datos['motivo'] ?? 'Devolución de cliente');
            $tipoReembolso = $datos['tipo_reembolso'] ?? 'sin_reembolso';

            $itemsADevolver = $datos['items'] ?? [];
            if (empty($itemsADevolver) || ! is_array($itemsADevolver)) {
                throw new Exception('Debes indicar los productos y cantidades a devolver.');
            }

            $tasaVenta = (float) ($venta->tasa_cambio > 0 ? $venta->tasa_cambio : 1.0000);
            $codigoDevolucion = $this->generarCodigoDevolucion($empresaId, true);
            $totalDevueltoUsd = 0.0;
            $totalDevueltoBs = 0.0;
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

                $cantFacturada = (float) $ventaDetalle->cantidad;
                $cantPrevia = (float) DevolucionVentaDetalle::where('venta_detalle_id', $detalleId)->sum('cantidad');
                $cantDisponibleParaDevolver = round($cantFacturada - $cantPrevia, 4);

                if ($cantidadDevolver > $cantDisponibleParaDevolver + 0.0001) {
                    throw new Exception("La cantidad a devolver ({$cantidadDevolver}) supera lo disponible facturado ({$cantDisponibleParaDevolver}) para el producto.");
                }

                // Monto devuelto = valor neto proporcional del renglón (respetando descuento e IVA que tuvo el renglón)
                $renglonTotalConIvaUsd = round((float) $ventaDetalle->subtotal_usd + (float) $ventaDetalle->iva_monto_usd, 2);
                $renglonTotalConIvaBs = round((float) $ventaDetalle->subtotal_bs + (float) $ventaDetalle->iva_monto_bs, 2);
                if ($renglonTotalConIvaBs <= 0 && $renglonTotalConIvaUsd > 0) {
                    $renglonTotalConIvaBs = round($renglonTotalConIvaUsd * $tasaVenta, 2);
                }

                $unitNetoConIvaUsd = $cantFacturada > 0 ? round($renglonTotalConIvaUsd / $cantFacturada, 4) : (float) $ventaDetalle->precio_unitario_usd;
                $unitNetoConIvaBs = $cantFacturada > 0 ? round($renglonTotalConIvaBs / $cantFacturada, 4) : (float) $ventaDetalle->precio_unitario_bs;

                $subtotalDevueltoUsd = $cantFacturada > 0
                    ? round(($renglonTotalConIvaUsd / $cantFacturada) * $cantidadDevolver, 2)
                    : 0.0;
                $subtotalDevueltoBs = $cantFacturada > 0
                    ? round(($renglonTotalConIvaBs / $cantFacturada) * $cantidadDevolver, 2)
                    : 0.0;

                $totalDevueltoUsd = round($totalDevueltoUsd + $subtotalDevueltoUsd, 2);
                $totalDevueltoBs = round($totalDevueltoBs + $subtotalDevueltoBs, 2);

                $detallesDevolucion[] = [
                    'venta_detalle' => $ventaDetalle,
                    'cantidad' => $cantidadDevolver,
                    'precio_unitario_usd' => $unitNetoConIvaUsd,
                    'precio_unitario_bs' => $unitNetoConIvaBs,
                    'subtotal_usd' => $subtotalDevueltoUsd,
                    'subtotal_bs' => $subtotalDevueltoBs,
                ];
            }

            if (empty($detallesDevolucion)) {
                throw new Exception('No hay renglones válidos para procesar en la devolución.');
            }

            // Resolver método de pago de reembolso cuando corresponda
            $metodoReembolsoId = ! empty($datos['metodo_pago_id']) ? (int) $datos['metodo_pago_id'] : null;
            if (! $metodoReembolsoId) {
                if ($tipoReembolso === 'efectivo') {
                    $pagoEfectivo = $venta->pagos->first(fn ($p) => (bool) $p->metodoPago?->es_efectivo);
                    $metodoReembolsoId = $pagoEfectivo?->metodo_pago_id
                        ?? MetodoPago::where('estado', true)->where('es_efectivo', true)->value('id');
                } elseif (in_array($tipoReembolso, ['transferencia', 'otro_metodo', 'metodo_original'], true)) {
                    $pagoNoEfectivo = $venta->pagos->first(fn ($p) => ! $p->metodoPago?->es_efectivo);
                    $metodoReembolsoId = $pagoNoEfectivo?->metodo_pago_id
                        ?? $venta->pagos->first()?->metodo_pago_id
                        ?? MetodoPago::where('estado', true)->where('es_efectivo', false)->value('id');
                }
            }

            // 1. Guardar Cabecera de Devolución (caja_turno_id nunca null)
            $devolucion = DevolucionVenta::create([
                'empresa_id' => $empresaId,
                'venta_id' => $venta->id,
                'caja_turno_id' => $cajaTurno->id,
                'user_id' => $userId,
                'codigo' => $codigoDevolucion,
                'motivo' => $motivo,
                'total_devuelto_usd' => $totalDevueltoUsd,
                'total_devuelto_bs' => $totalDevueltoBs,
                'tipo_reembolso' => $tipoReembolso,
                'metodo_pago_id' => $metodoReembolsoId,
            ]);

            // 2. Guardar Detalles y Revertir Kardex (con lockForUpdate en seriales y motos)
            foreach ($detallesDevolucion as $d) {
                $vd = $d['venta_detalle'];

                DevolucionVentaDetalle::create([
                    'devolucion_venta_id' => $devolucion->id,
                    'venta_detalle_id' => $vd->id,
                    'producto_id' => $vd->producto_id,
                    'moto_id' => $vd->moto_id,
                    'servicio_id' => $vd->servicio_id,
                    'almacen_id' => $vd->almacen_id,
                    'nombre_item' => $vd->nombre_item,
                    'serial_identificador' => $vd->serial_identificador,
                    'cantidad' => $d['cantidad'],
                    'precio_unitario_usd' => $d['precio_unitario_usd'],
                    'precio_unitario_bs' => $d['precio_unitario_bs'],
                    'subtotal_usd' => $d['subtotal_usd'],
                    'subtotal_bs' => $d['subtotal_bs'],
                ]);

                if ($vd->tipo_item === 'producto' && $vd->producto_id) {
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

                if (! empty($vd->producto_serial_id)) {
                    $serialModel = ProductoSerial::where('id', $vd->producto_serial_id)
                        ->where('empresa_id', $empresaId)
                        ->lockForUpdate()
                        ->first();

                    if ($serialModel) {
                        $serialModel->update([
                            'estado' => 'disponible',
                            'venta_id' => null,
                            'venta_detalle_id' => null,
                        ]);
                    }
                }

                if ($vd->tipo_item === 'moto' && $vd->moto_id) {
                    $motoModel = Moto::where('id', $vd->moto_id)
                        ->where('empresa_id', $empresaId)
                        ->lockForUpdate()
                        ->first();

                    if ($motoModel) {
                        $motoModel->update(['estado' => 'disponible']);
                    }
                }
            }

            // 3. Si la venta tiene CXC con saldo pendiente, primero descuenta del saldo_pendiente; solo el excedente se reembolsa
            $montoAReembolsarUsd = $totalDevueltoUsd;
            $cxc = CuentaPorCobrar::where('venta_id', $venta->id)
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->first();

            if ($cxc) {
                $saldoAnteriorCxcUsd = (float) $cxc->saldo_pendiente_usd;
                $descuentoEnCxcUsd = min($saldoAnteriorCxcUsd, $totalDevueltoUsd);
                $nuevoSaldoCxcUsd = max(0.0, round($saldoAnteriorCxcUsd - $descuentoEnCxcUsd, 2));
                $nuevoSaldoCxcBs = round($nuevoSaldoCxcUsd * $tasaVenta, 2);

                $cxc->saldo_pendiente_usd = $nuevoSaldoCxcUsd;
                $cxc->saldo_pendiente_bs = $nuevoSaldoCxcBs;
                if ($nuevoSaldoCxcUsd <= 0.001) {
                    $cxc->estado = 'pagada';
                }
                $cxc->save();

                $venta->saldo_pendiente_usd = $nuevoSaldoCxcUsd;
                $venta->saldo_pendiente_bs = $nuevoSaldoCxcBs;

                $montoAReembolsarUsd = max(0.0, round($totalDevueltoUsd - $descuentoEnCxcUsd, 2));
            } elseif ((float) $venta->saldo_pendiente_usd > 0) {
                $saldoAntVenta = (float) $venta->saldo_pendiente_usd;
                $descSaldoVenta = min($saldoAntVenta, $totalDevueltoUsd);
                $nuevoSaldoVentaUsd = max(0.0, round($saldoAntVenta - $descSaldoVenta, 2));
                $venta->saldo_pendiente_usd = $nuevoSaldoVentaUsd;
                $venta->saldo_pendiente_bs = round($nuevoSaldoVentaUsd * $tasaVenta, 2);
                $montoAReembolsarUsd = max(0.0, round($totalDevueltoUsd - $descSaldoVenta, 2));
            }

            $montoAReembolsarBs = round($montoAReembolsarUsd * $tasaVenta, 2);

            // 4. Si hay monto efectivo/método a reembolsar, registrar movimiento egreso en el turno actual
            $tiposSinSalidaCaja = ['sin_reembolso', 'credito', 'credito_cliente', 'credito_favor', 'nota_credito', 'saldo_cxc'];
            if ($montoAReembolsarUsd > 0 && ! in_array($tipoReembolso, $tiposSinSalidaCaja, true)) {
                $monedaReembolso = ! empty($datos['moneda_reembolso'])
                    ? strtoupper(trim((string) $datos['moneda_reembolso']))
                    : 'USD';

                if (! in_array($monedaReembolso, ['USD', 'VES', 'BS'], true)) {
                    $monedaReembolso = 'USD';
                }

                CajaMovimiento::create([
                    'empresa_id' => $empresaId,
                    'caja_turno_id' => $cajaTurno->id,
                    'user_id' => $userId,
                    'tipo' => 'egreso',
                    'metodo_pago_id' => $metodoReembolsoId,
                    'moneda' => $monedaReembolso,
                    'monto_usd' => $montoAReembolsarUsd,
                    'monto_bs' => $montoAReembolsarBs,
                    'concepto' => "Reembolso por Devolución #{$devolucion->codigo} (Factura #{$venta->codigo})",
                    'comprobante_referencia' => $devolucion->codigo,
                    'documento_tipo' => 'devolucion_venta',
                    'documento_id' => $devolucion->id,
                ]);
            }

            // 5. Actualizar estado de la venta y recalcular comisión proporcional al monto neto sin IVA restante
            $totalCantidadOriginal = (float) $venta->detalles->sum('cantidad');
            $cantidadesDevueltasPorDetalle = DevolucionVentaDetalle::whereIn('venta_detalle_id', $venta->detalles->pluck('id'))
                ->selectRaw('venta_detalle_id, SUM(cantidad) as total_cant')
                ->groupBy('venta_detalle_id')
                ->pluck('total_cant', 'venta_detalle_id');

            $totalCantidadDevuelta = (float) $cantidadesDevueltasPorDetalle->sum();

            $subtotalNetoSinIvaDevueltoUsd = 0.0;
            foreach ($venta->detalles as $detOrig) {
                $cantDevDet = (float) ($cantidadesDevueltasPorDetalle->get($detOrig->id) ?? 0);
                if ($cantDevDet > 0 && (float) $detOrig->cantidad > 0) {
                    $proporcionDet = min(1.0, $cantDevDet / (float) $detOrig->cantidad);
                    $subtotalNetoSinIvaDevueltoUsd += round((float) $detOrig->subtotal_usd * $proporcionDet, 4);
                }
            }

            if ($totalCantidadDevuelta >= $totalCantidadOriginal - 0.0001) {
                $venta->estado = 'devuelta_total';
                if ($venta->vendedor_id) {
                    $venta->comision_monto_usd = 0;
                    $venta->comision_monto_bs = 0;
                }
                if ($venta->comision_estado !== 'pagada') {
                    $venta->comision_estado = 'sin_comision';
                }
            } else {
                $venta->estado = 'devuelta_parcial';
                if ($venta->vendedor_id && (float) $venta->comision_porcentaje > 0) {
                    $nuevoSubtotalNetoRestanteUsd = max(0.0, round((float) $venta->subtotal_neto_usd - $subtotalNetoSinIvaDevueltoUsd, 2));
                    $nuevaComisionUsd = round($nuevoSubtotalNetoRestanteUsd * ((float) $venta->comision_porcentaje / 100), 2);
                    $venta->comision_monto_usd = $nuevaComisionUsd;
                    $venta->comision_monto_bs = round($nuevaComisionUsd * $tasaVenta, 2);
                    if ($nuevaComisionUsd <= 0 && $venta->comision_estado !== 'pagada') {
                        $venta->comision_estado = 'sin_comision';
                    }
                }
            }

            $venta->save();

            return $devolucion->load(['detalles.producto', 'detalles.moto', 'detalles.servicio', 'venta.cliente']);
        });
    }

    /**
     * Anular una factura (completada o devuelta_parcial) revirtiendo inventario restante, CXC, comisión y dinero cobrado
     */
    public function anularVenta(int $ventaId, array $datos, ?int $empresaId = null, ?int $userId = null): Venta
    {
        return DB::transaction(function () use ($ventaId, $datos, $empresaId, $userId) {
            $empresaId = $this->empresaId($empresaId);
            $userId = $this->userId($userId);

            $user = User::find($userId);
            if (! $user) {
                throw new Exception('No se pudo determinar el usuario que anula la factura.');
            }

            $esAdmin = $this->esAdmin($user);
            if (! $esAdmin && ! $user->can('ventas.anular')) {
                throw new AuthorizationException('No tienes permiso para anular facturas (se requiere ventas.anular).');
            }

            $motivo = trim((string) ($datos['motivo_anulacion'] ?? $datos['motivo'] ?? ''));
            if (mb_strlen($motivo) < 5) {
                throw new Exception('El motivo de anulación es obligatorio y debe tener al menos 5 caracteres.');
            }

            $cajaTurno = $this->resolverTurnoOperacion(
                $empresaId,
                $user,
                ! empty($datos['caja_turno_id']) ? (int) $datos['caja_turno_id'] : null
            );

            $venta = Venta::with([
                'detalles',
                'pagos.metodoPago',
                'cuentaPorCobrar.abonos.metodoPago',
                'devoluciones.detalles',
            ])
                ->where('id', $ventaId)
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($venta->estado, ['completada', 'devuelta_parcial'], true)) {
                throw new Exception("La factura '{$venta->codigo}' se encuentra en estado '{$venta->estado}' y no puede ser anulada.");
            }

            $tasaVenta = (float) ($venta->tasa_cambio > 0 ? $venta->tasa_cambio : 1.0000);

            // 1. Revertir inventario por la cantidad aún no devuelta de cada renglón
            foreach ($venta->detalles as $vd) {
                $cantPreviaDevuelta = (float) DevolucionVentaDetalle::where('venta_detalle_id', $vd->id)->sum('cantidad');
                $cantRestante = round((float) $vd->cantidad - $cantPreviaDevuelta, 4);

                if ($cantRestante <= 0.0001) {
                    continue;
                }

                if ($vd->tipo_item === 'producto' && $vd->producto_id) {
                    $this->kardexService->registrarMovimiento([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $vd->almacen_id,
                        'producto_id' => $vd->producto_id,
                        'user_id' => $userId,
                        'tipo_movimiento' => 'anulacion_venta',
                        'documento_tipo' => 'venta',
                        'documento_id' => $venta->id,
                        'cantidad' => $cantRestante,
                        'costo_unitario_usd' => $vd->costo_unitario_usd,
                        'costo_unitario_bs' => $vd->costo_unitario_bs,
                        'motivo' => "Anulación de Factura #{$venta->codigo}: {$motivo}",
                    ]);
                }

                if (! empty($vd->producto_serial_id)) {
                    $serialModel = ProductoSerial::where('id', $vd->producto_serial_id)
                        ->where('empresa_id', $empresaId)
                        ->lockForUpdate()
                        ->first();

                    if ($serialModel) {
                        $serialModel->update([
                            'estado' => 'disponible',
                            'venta_id' => null,
                            'venta_detalle_id' => null,
                        ]);
                    }
                }

                if ($vd->tipo_item === 'moto' && $vd->moto_id) {
                    $motoModel = Moto::where('id', $vd->moto_id)
                        ->where('empresa_id', $empresaId)
                        ->lockForUpdate()
                        ->first();

                    if ($motoModel) {
                        $motoModel->update(['estado' => 'disponible']);
                    }
                }
            }

            // 2. Si tiene CuentaPorCobrar, anularla y llevar su saldo a 0
            $cxc = CuentaPorCobrar::where('venta_id', $venta->id)
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->first();

            if ($cxc) {
                $cxc->update([
                    'estado' => 'anulada',
                    'saldo_pendiente_usd' => 0,
                    'saldo_pendiente_bs' => 0,
                ]);
            }

            // 3. Dinero: calcular lo efectivamente cobrado por método/moneda
            // (pagos netos de la venta + abonos CXC recibidos - devoluciones previas ya reembolsadas)
            $gruposCobro = [];
            $agregarCobroGrupo = function (int $metodoId, string $moneda, float $usd, float $bs) use (&$gruposCobro) {
                if ($usd <= 0.001 && $bs <= 0.001) {
                    return;
                }
                $monedaNorm = in_array(strtoupper($moneda), ['VES', 'BS'], true) ? 'VES' : 'USD';
                $key = "{$metodoId}_{$monedaNorm}";
                if (! isset($gruposCobro[$key])) {
                    $gruposCobro[$key] = [
                        'metodo_pago_id' => $metodoId,
                        'moneda' => $monedaNorm,
                        'usd' => 0.0,
                        'bs' => 0.0,
                    ];
                }
                $gruposCobro[$key]['usd'] = round($gruposCobro[$key]['usd'] + $usd, 2);
                $gruposCobro[$key]['bs'] = round($gruposCobro[$key]['bs'] + $bs, 2);
            };

            foreach ($venta->pagos as $pago) {
                $agregarCobroGrupo(
                    (int) $pago->metodo_pago_id,
                    (string) ($pago->moneda ?? 'USD'),
                    (float) $pago->monto_usd,
                    (float) $pago->monto_bs
                );
            }

            // Descontar el vuelto entregado originalmente en la venta para obtener el cobro neto inicial
            $vueltoVentaUsd = (float) $venta->vuelto_usd;
            $vueltoVentaBs = (float) $venta->vuelto_bs;
            if ($vueltoVentaUsd > 0 && ! empty($gruposCobro)) {
                $movVueltoOrig = CajaMovimiento::where('documento_tipo', 'venta')
                    ->where('documento_id', $venta->id)
                    ->where('tipo', 'egreso')
                    ->first();

                $keyVuelto = null;
                if ($movVueltoOrig && $movVueltoOrig->metodo_pago_id) {
                    $monV = in_array(strtoupper((string) $movVueltoOrig->moneda), ['VES', 'BS'], true) ? 'VES' : 'USD';
                    $candidata = "{$movVueltoOrig->metodo_pago_id}_{$monV}";
                    if (isset($gruposCobro[$candidata])) {
                        $keyVuelto = $candidata;
                    }
                }
                $keyVuelto = $keyVuelto ?? array_key_first($gruposCobro);
                if ($keyVuelto !== null) {
                    $gruposCobro[$keyVuelto]['usd'] = max(0.0, round($gruposCobro[$keyVuelto]['usd'] - $vueltoVentaUsd, 2));
                    $gruposCobro[$keyVuelto]['bs'] = max(0.0, round($gruposCobro[$keyVuelto]['bs'] - $vueltoVentaBs, 2));
                }
            }

            // Sumar abonos CXC recibidos
            if ($cxc) {
                $abonosCxc = $cxc->abonos()->with('metodoPago')->get();
                foreach ($abonosCxc as $ab) {
                    $nombreMet = strtolower($ab->metodoPago?->nombre ?? '');
                    $esBs = str_contains($nombreMet, 'bs') || str_contains($nombreMet, 'ves') || str_contains($nombreMet, 'bolivar') || str_contains($nombreMet, 'bolívar');
                    $monedaAb = $esBs ? 'VES' : 'USD';
                    $agregarCobroGrupo(
                        (int) $ab->metodo_pago_id,
                        $monedaAb,
                        (float) $ab->monto_usd,
                        (float) $ab->monto_bs
                    );
                }
            }

            // Restar devoluciones previas ya reembolsadas de esta venta
            $devIds = $venta->devoluciones->pluck('id')->all();
            $reembolsosPreviosUsd = 0.0;
            $reembolsosPreviosBs = 0.0;

            if (! empty($devIds)) {
                $movsDev = CajaMovimiento::whereIn('documento_tipo', ['devolucion_venta', 'devolucion'])
                    ->whereIn('documento_id', $devIds)
                    ->where('tipo', 'egreso')
                    ->get();

                if ($movsDev->isNotEmpty()) {
                    $reembolsosPreviosUsd = (float) $movsDev->sum('monto_usd');
                    $reembolsosPreviosBs = (float) $movsDev->sum('monto_bs');
                } else {
                    $tiposSinSalida = ['sin_reembolso', 'credito', 'credito_cliente', 'credito_favor', 'nota_credito', 'saldo_cxc'];
                    foreach ($venta->devoluciones as $devPrev) {
                        if (! in_array($devPrev->tipo_reembolso, $tiposSinSalida, true)) {
                            $reembolsosPreviosUsd += (float) $devPrev->total_devuelto_usd;
                            $reembolsosPreviosBs += (float) $devPrev->total_devuelto_bs;
                        }
                    }
                }
            }

            $restanteDescontarUsd = round($reembolsosPreviosUsd, 2);
            $restanteDescontarBs = round($reembolsosPreviosBs, 2);

            foreach ($gruposCobro as $k => &$grupo) {
                if ($restanteDescontarUsd <= 0.001) {
                    break;
                }
                $descUsd = min($grupo['usd'], $restanteDescontarUsd);
                $descBs = $restanteDescontarBs > 0 ? min($grupo['bs'], round($descUsd * $tasaVenta, 2)) : round($descUsd * $tasaVenta, 2);

                $grupo['usd'] = max(0.0, round($grupo['usd'] - $descUsd, 2));
                $grupo['bs'] = max(0.0, round($grupo['bs'] - $descBs, 2));

                $restanteDescontarUsd = max(0.0, round($restanteDescontarUsd - $descUsd, 2));
                $restanteDescontarBs = max(0.0, round($restanteDescontarBs - $descBs, 2));
            }
            unset($grupo);

            foreach ($gruposCobro as $grupo) {
                if ($grupo['usd'] <= 0.001 && $grupo['bs'] <= 0.001) {
                    continue;
                }

                $montoBsFinal = $grupo['bs'] > 0 ? $grupo['bs'] : round($grupo['usd'] * $tasaVenta, 2);

                CajaMovimiento::create([
                    'empresa_id' => $empresaId,
                    'caja_turno_id' => $cajaTurno->id,
                    'user_id' => $userId,
                    'tipo' => 'egreso',
                    'metodo_pago_id' => $grupo['metodo_pago_id'],
                    'moneda' => $grupo['moneda'],
                    'monto_usd' => $grupo['usd'],
                    'monto_bs' => $montoBsFinal,
                    'concepto' => "Reembolso por Anulación de Factura #{$venta->codigo}",
                    'comprobante_referencia' => $venta->codigo,
                    'observaciones' => $motivo,
                    'documento_tipo' => 'anulacion_venta',
                    'documento_id' => $venta->id,
                ]);
            }

            // 4. Actualizar estado de la venta, auditoría de anulación, saldos y comisión = 0
            $venta->estado = 'anulada';
            $venta->anulada_por_id = $userId;
            $venta->anulada_at = Carbon::now();
            $venta->motivo_anulacion = $motivo;
            $venta->saldo_pendiente_usd = 0;
            $venta->saldo_pendiente_bs = 0;
            $venta->comision_monto_usd = 0;
            $venta->comision_monto_bs = 0;
            if ($venta->comision_estado !== 'pagada') {
                $venta->comision_estado = 'sin_comision';
            }
            $venta->save();

            return $venta->load(['cliente', 'almacen', 'detalles.producto', 'detalles.moto', 'detalles.servicio', 'pagos.metodoPago', 'cuentaPorCobrar']);
        });
    }

    /**
     * Buscar factura para reimpresión
     */
    public function obtenerVentaParaImpresion(int|string $idOcodigo, ?int $empresaId = null): Venta
    {
        $empresaId = $this->empresaId($empresaId);

        return Venta::with(['empresa', 'cliente', 'almacen', 'usuario', 'vendedor', 'detalles.producto', 'detalles.moto', 'detalles.servicio', 'pagos.metodoPago'])
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($idOcodigo) {
                $q->where('id', $idOcodigo)
                    ->orWhere('codigo', $idOcodigo);
            })
            ->firstOrFail();
    }
}
