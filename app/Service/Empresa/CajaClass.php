<?php

namespace App\Service\Empresa;

use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\CuentaPorCobrarAbono;
use App\Models\DevolucionVenta;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CajaClass
{
    protected function validarEmpresa(int $empresaId): void
    {
        if ($empresaId <= 0) {
            throw new Exception('No se pudo determinar la empresa activa para realizar la operación.');
        }
    }

    protected function validarUsuario(int $userId): void
    {
        if ($userId <= 0) {
            throw new Exception('No se pudo determinar el usuario autenticado para realizar la operación.');
        }
    }

    /**
     * Listado de cajas para DataTables
     */
    public function listaCajas(int $empresaId): Builder
    {
        $this->validarEmpresa($empresaId);

        return Caja::with(['almacen', 'turnoActivo.usuario'])
            ->where('empresa_id', $empresaId);
    }

    /**
     * Listado histórico de turnos / aperturas de caja
     */
    public function listaTurnos(int $empresaId): Builder
    {
        return CajaTurno::with(['caja', 'usuario'])
            ->where('empresa_id', $empresaId)
            ->latest('id');
    }

    /**
     * Cajas activas disponibles para apertura (sin turno abierto)
     */
    public function cajasDisponiblesParaApertura(int $empresaId): Collection
    {
        return Caja::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->whereDoesntHave('turnos', function ($q) {
                $q->where('estado', 'abierta');
            })
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Cajeros / Usuarios disponibles en la empresa para asignar a un turno de caja
     */
    public function cajerosDisponibles(int $empresaId): Collection
    {
        return User::where('estado', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'SuperAdmin'))
            ->whereHas('empresas', function ($q) use ($empresaId) {
                $q->where('empresas.id', $empresaId)
                    ->where('empresa_user.estado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'nombre', 'apellido', 'email']);
    }

    /**
     * Detalle de una caja
     */
    public function detalleCaja(int $id, int $empresaId): Caja
    {
        return Caja::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar nueva caja
     */
    public function guardarCaja(array $datos, int $empresaId): Caja
    {
        $this->validarEmpresa($empresaId);
        $datos['empresa_id'] = $empresaId;
        $datos['estado'] = true;

        return Caja::create($datos);
    }

    /**
     * Actualizar caja
     */
    public function actualizarCaja(array $datos, int $id, int $empresaId): Caja
    {
        $caja = $this->detalleCaja($id, $empresaId);
        $caja->update($datos);

        return $caja;
    }

    /**
     * Alternar estado de una caja
     */
    public function eliminarCaja(int $id, int $empresaId): Caja
    {
        $caja = $this->detalleCaja($id, $empresaId);

        if ($caja->turnoActivo()->exists()) {
            throw new Exception('No se puede desactivar la caja porque tiene una sesión de turno actualmente abierta.');
        }

        $caja->estado = ! $caja->estado;
        $caja->save();

        return $caja;
    }

    /**
     * Obtener el turno activo de un usuario en la empresa
     */
    public function obtenerTurnoActivoUsuario(int $userId, int $empresaId): ?CajaTurno
    {
        return CajaTurno::with(['caja', 'usuario', 'empresa', 'aperturadoPor'])
            ->where('empresa_id', $empresaId)
            ->where('user_id', $userId)
            ->where('estado', 'abierta')
            ->latest('id')
            ->first();
    }

    /**
     * Obtener turno por ID
     */
    public function obtenerTurnoPorId(int $id, int $empresaId): CajaTurno
    {
        return CajaTurno::with(['caja', 'usuario', 'empresa', 'aperturadoPor'])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Aperturar un nuevo turno de caja asignando al cajero responsable
     */
    public function aperturarTurno(array $datos, int $empresaId, int $adminUserId): CajaTurno
    {
        $this->validarEmpresa($empresaId);
        $this->validarUsuario($adminUserId);

        return DB::transaction(function () use ($datos, $empresaId, $adminUserId) {
            $adminUser = User::findOrFail($adminUserId);
            $esAdmin = $adminUser->hasRole(['SuperAdmin', 'Admin', 'superadmin', 'admin', 'administrador']) || $adminUser->can('cajas.aperturar');

            if (! $esAdmin) {
                throw new Exception('Solo los administradores o usuarios autorizados pueden aperturar turnos de caja.');
            }

            $cajaId = (int) $datos['caja_id'];
            $cajeroId = (int) ($datos['user_id'] ?? $adminUserId);

            // Validar que el cajero pertenezca a la empresa o sea SuperAdmin
            $cajero = User::findOrFail($cajeroId);
            if (! $cajero->hasRole('SuperAdmin')) {
                $pertenece = $cajero->empresas()
                    ->where('empresas.id', $empresaId)
                    ->wherePivot('estado', true)
                    ->exists();

                if (! $pertenece) {
                    throw new Exception('El usuario seleccionado no pertenece a esta empresa o se encuentra inactivo en ella.');
                }
            }

            // 1. Validar que la caja no esté ocupada
            $turnoExistenteCaja = CajaTurno::where('empresa_id', $empresaId)
                ->where('caja_id', $cajaId)
                ->where('estado', 'abierta')
                ->first();

            if ($turnoExistenteCaja) {
                $usuarioExistente = $turnoExistenteCaja->usuario?->name ?? 'otro cajero';
                throw new Exception("Esta caja ya tiene una sesión abierta por {$usuarioExistente}.");
            }

            // 2. Validar que el cajero asignado no tenga ya otra caja abierta
            $turnoActivoCajero = $this->obtenerTurnoActivoUsuario($cajeroId, $empresaId);
            if ($turnoActivoCajero) {
                $nombreCajaActual = $turnoActivoCajero->caja?->nombre ?? 'otra caja';
                $cajero = User::find($cajeroId);
                $nombreCajero = $cajero?->name ?? 'El cajero';
                throw new Exception("{$nombreCajero} ya tiene una caja abierta activa ({$nombreCajaActual}). Debe cerrar su turno anterior antes de asignarle otra caja.");
            }

            $ahora = Carbon::now();

            return CajaTurno::create([
                'empresa_id' => $empresaId,
                'caja_id' => $cajaId,
                'user_id' => $cajeroId,
                'aperturado_por_id' => $adminUserId,
                'fecha_apertura' => $ahora->toDateString(),
                'hora_apertura' => $ahora->toTimeString(),
                'monto_apertura_usd' => (float) ($datos['monto_apertura_usd'] ?? 0),
                'monto_apertura_bs' => (float) ($datos['monto_apertura_bs'] ?? 0),
                'estado' => 'abierta',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        });
    }

    /**
     * Calcular reporte X o previo a Z para un turno de caja
     */
    public function calcularReporteTurno(int $cajaTurnoId, int $empresaId): array
    {
        $turno = $this->obtenerTurnoPorId($cajaTurnoId, $empresaId);

        // Ventas asociadas al turno
        $ventas = Venta::with(['pagos.metodoPago', 'cliente', 'vendedor'])
            ->where('empresa_id', $empresaId)
            ->where('caja_turno_id', $cajaTurnoId)
            ->where('estado', '!=', 'anulada')
            ->get();

        $cantidadVentas = $ventas->count();
        $totalVentasUsd = (float) $ventas->sum('total_usd');
        $totalVentasBs = (float) $ventas->sum('total_bs');

        // Devoluciones asociadas al turno
        $devoluciones = DevolucionVenta::with(['venta'])
            ->where('empresa_id', $empresaId)
            ->where('caja_turno_id', $cajaTurnoId)
            ->get();

        $cantidadDevoluciones = $devoluciones->count();
        $totalDevolucionesUsd = (float) $devoluciones->sum('total_devuelto_usd');
        $totalDevolucionesBs = (float) $devoluciones->sum('total_devuelto_bs');

        // Desglose de pagos por método y moneda
        $pagosPorMetodo = [];
        $totalEfectivoVentasUsd = 0.0;
        $totalEfectivoVentasBs = 0.0;

        foreach ($ventas as $v) {
            foreach ($v->pagos as $p) {
                $metodoNombre = $p->metodoPago?->nombre ?? 'Otro Método';
                $moneda = $p->moneda ?? 'USD';
                $key = "{$metodoNombre} ({$moneda})";

                if (! isset($pagosPorMetodo[$key])) {
                    $pagosPorMetodo[$key] = [
                        'metodo' => $metodoNombre,
                        'moneda' => $moneda,
                        'total_origen' => 0.0,
                        'total_usd' => 0.0,
                        'total_bs' => 0.0,
                        'conteo' => 0,
                    ];
                }

                $pagosPorMetodo[$key]['total_origen'] += (float) $p->monto_origen;
                $pagosPorMetodo[$key]['total_usd'] += (float) $p->monto_usd;
                $pagosPorMetodo[$key]['total_bs'] += (float) $p->monto_bs;
                $pagosPorMetodo[$key]['conteo']++;

                // Identificar pagos en efectivo para arqueo en caja
                if ($p->metodoPago?->es_efectivo) {
                    if ($moneda === 'VES' || $moneda === 'BS') {
                        $totalEfectivoVentasBs += (float) $p->monto_origen;
                    } else {
                        $totalEfectivoVentasUsd += (float) $p->monto_origen;
                    }
                }
            }
        }

        // Devoluciones en efectivo
        $totalDevolucionesEfectivoUsd = 0.0;
        $totalDevolucionesEfectivoBs = 0.0;
        foreach ($devoluciones as $dev) {
            if ($dev->tipo_reembolso === 'efectivo') {
                $usd = (float) $dev->total_devuelto_usd;
                $bs = (float) $dev->total_devuelto_bs;
                if ($usd > 0 && $bs <= 0) {
                    $totalDevolucionesEfectivoUsd += $usd;
                } elseif ($bs > 0 && $usd <= 0) {
                    $totalDevolucionesEfectivoBs += $bs;
                } else {
                    $totalDevolucionesEfectivoUsd += $usd;
                }
            }
        }

        // Abonos de Cuentas por Cobrar (CXC) asociados al turno
        $abonosCxc = CuentaPorCobrarAbono::with('metodoPago')
            ->where('caja_turno_id', $cajaTurnoId)
            ->get();

        $totalAbonosCxcUsd = (float) $abonosCxc->sum('monto_usd');
        $totalAbonosCxcBs = (float) $abonosCxc->sum('monto_bs');
        $totalAbonosCxcEfectivoUsd = 0.0;
        $totalAbonosCxcEfectivoBs = 0.0;

        foreach ($abonosCxc as $ab) {
            if ($ab->metodoPago?->es_efectivo) {
                $usd = (float) $ab->monto_usd;
                $bs = (float) $ab->monto_bs;
                $nombreMet = strtolower($ab->metodoPago?->nombre ?? '');
                $esMetodoBs = str_contains($nombreMet, 'bs') || str_contains($nombreMet, 'ves') || str_contains($nombreMet, 'bolivar') || str_contains($nombreMet, 'bolívar');

                if ($usd > 0 && $bs <= 0) {
                    $totalAbonosCxcEfectivoUsd += $usd;
                } elseif ($bs > 0 && $usd <= 0) {
                    $totalAbonosCxcEfectivoBs += $bs;
                } elseif ($esMetodoBs) {
                    $totalAbonosCxcEfectivoBs += $bs;
                } else {
                    $totalAbonosCxcEfectivoUsd += $usd;
                }
            }
        }

        // Movimientos de caja (ingresos, egresos manuales y vuelto entregado)
        $movimientos = CajaMovimiento::with(['usuario', 'metodoPago'])
            ->where('caja_turno_id', $cajaTurnoId)
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'asc')
            ->get();

        $totalIngresosCajaUsd = 0.0;
        $totalIngresosCajaBs = 0.0;
        $totalEgresosCajaUsd = 0.0;
        $totalEgresosCajaBs = 0.0;
        $totalVueltoUsd = 0.0;
        $totalVueltoBs = 0.0;

        foreach ($movimientos as $mov) {
            $esMovimientoEfectivo = $mov->metodo_pago_id === null || (bool) $mov->metodoPago?->es_efectivo;
            if (! $esMovimientoEfectivo) {
                continue;
            }

            $monedaMov = strtoupper(trim($mov->moneda ?? ''));
            $esVuelto = $mov->tipo === 'egreso' && $mov->documento_tipo === 'venta';
            $esReembolsoDev = $mov->tipo === 'egreso' && in_array($mov->documento_tipo, ['devolucion', 'anulacion'], true);

            if ($mov->tipo === 'ingreso') {
                if ($monedaMov === 'VES' || $monedaMov === 'BS') {
                    $totalIngresosCajaBs += (float) $mov->monto_bs;
                } elseif ($monedaMov === 'USD') {
                    $totalIngresosCajaUsd += (float) $mov->monto_usd;
                } else {
                    $totalIngresosCajaUsd += (float) $mov->monto_usd;
                    $totalIngresosCajaBs += (float) $mov->monto_bs;
                }
            } elseif ($esVuelto) {
                if ($monedaMov === 'VES' || $monedaMov === 'BS') {
                    $totalVueltoBs += (float) $mov->monto_bs;
                } elseif ($monedaMov === 'USD') {
                    $totalVueltoUsd += (float) $mov->monto_usd;
                } else {
                    $totalVueltoUsd += (float) $mov->monto_usd;
                    $totalVueltoBs += (float) $mov->monto_bs;
                }
            } elseif ($esReembolsoDev) {
                if ($cantidadDevoluciones === 0) {
                    if ($monedaMov === 'VES' || $monedaMov === 'BS') {
                        $totalDevolucionesEfectivoBs += (float) $mov->monto_bs;
                    } elseif ($monedaMov === 'USD') {
                        $totalDevolucionesEfectivoUsd += (float) $mov->monto_usd;
                    } else {
                        $totalDevolucionesEfectivoUsd += (float) $mov->monto_usd;
                        $totalDevolucionesEfectivoBs += (float) $mov->monto_bs;
                    }
                }
            } else {
                if ($monedaMov === 'VES' || $monedaMov === 'BS') {
                    $totalEgresosCajaBs += (float) $mov->monto_bs;
                } elseif ($monedaMov === 'USD') {
                    $totalEgresosCajaUsd += (float) $mov->monto_usd;
                } else {
                    $totalEgresosCajaUsd += (float) $mov->monto_usd;
                    $totalEgresosCajaBs += (float) $mov->monto_bs;
                }
            }
        }

        // Efectivo esperado en caja (Fórmula cajas-turnos.md §4 por moneda, sin max(0, ...))
        $montoAperturaUsd = (float) $turno->monto_apertura_usd;
        $montoAperturaBs = (float) $turno->monto_apertura_bs;

        $efectivoEsperadoUsd = round(
            $montoAperturaUsd
            + $totalEfectivoVentasUsd
            + $totalAbonosCxcEfectivoUsd
            + $totalIngresosCajaUsd
            - $totalVueltoUsd
            - $totalDevolucionesEfectivoUsd
            - $totalEgresosCajaUsd,
            2
        );
        $efectivoEsperadoBs = round(
            $montoAperturaBs
            + $totalEfectivoVentasBs
            + $totalAbonosCxcEfectivoBs
            + $totalIngresosCajaBs
            - $totalVueltoBs
            - $totalDevolucionesEfectivoBs
            - $totalEgresosCajaBs,
            2
        );

        return [
            'turno' => $turno,
            'caja' => $turno->caja,
            'usuario' => $turno->usuario,
            'empresa' => $turno->empresa,
            'fecha_impresion' => Carbon::now()->format('d/m/Y h:i A'),
            'monto_apertura_usd' => $montoAperturaUsd,
            'monto_apertura_bs' => $montoAperturaBs,
            'cantidad_ventas' => $cantidadVentas,
            'total_ventas_usd' => $totalVentasUsd,
            'total_ventas_bs' => $totalVentasBs,
            'cantidad_devoluciones' => $cantidadDevoluciones,
            'total_devoluciones_usd' => $totalDevolucionesUsd,
            'total_devoluciones_bs' => $totalDevolucionesBs,
            'total_abonos_cxc_usd' => $totalAbonosCxcUsd,
            'total_abonos_cxc_bs' => $totalAbonosCxcBs,
            'total_abonos_cxc_efectivo_usd' => $totalAbonosCxcEfectivoUsd,
            'total_abonos_cxc_efectivo_bs' => $totalAbonosCxcEfectivoBs,
            'total_ingresos_caja_usd' => round($totalIngresosCajaUsd, 2),
            'total_ingresos_caja_bs' => round($totalIngresosCajaBs, 2),
            'total_egresos_caja_usd' => round($totalEgresosCajaUsd, 2),
            'total_egresos_caja_bs' => round($totalEgresosCajaBs, 2),
            'total_vuelto_usd' => round($totalVueltoUsd, 2),
            'total_vuelto_bs' => round($totalVueltoBs, 2),
            'pagos_por_metodo' => array_values($pagosPorMetodo),
            'total_efectivo_ventas_usd' => round($totalEfectivoVentasUsd, 2),
            'total_efectivo_ventas_bs' => round($totalEfectivoVentasBs, 2),
            'total_devoluciones_efectivo_usd' => round($totalDevolucionesEfectivoUsd, 2),
            'total_devoluciones_efectivo_bs' => round($totalDevolucionesEfectivoBs, 2),
            'efectivo_esperado_usd' => $efectivoEsperadoUsd,
            'efectivo_esperado_bs' => $efectivoEsperadoBs,
            'movimientos_listado' => $movimientos->map(function ($m) {
                $esVuelto = $m->tipo === 'egreso' && $m->documento_tipo === 'venta';

                return [
                    'id' => $m->id,
                    'tipo' => $m->tipo,
                    'es_vuelto' => $esVuelto,
                    'etiqueta_tipo' => $esVuelto ? 'Vuelto entregado' : ($m->tipo === 'ingreso' ? 'Ingreso' : 'Egreso'),
                    'documento_tipo' => $m->documento_tipo,
                    'documento_id' => $m->documento_id,
                    'moneda' => $m->moneda,
                    'es_efectivo' => $m->metodo_pago_id === null || (bool) $m->metodoPago?->es_efectivo,
                    'concepto' => $m->concepto,
                    'comprobante_referencia' => $m->comprobante_referencia,
                    'observaciones' => $m->observaciones,
                    'usuario' => $m->usuario?->name ?? 'Sistema',
                    'monto_usd' => (float) $m->monto_usd,
                    'monto_bs' => (float) $m->monto_bs,
                    'hora' => $m->created_at?->format('h:i A') ?? '--',
                ];
            })->toArray(),
            'ventas_listado' => $ventas->map(fn ($v) => [
                'id' => $v->id,
                'codigo' => $v->codigo,
                'cliente' => $v->cliente?->nombre_completo ?? 'Consumidor Final',
                'vendedor' => $v->vendedor?->nombre ?? 'N/A',
                'total_usd' => (float) $v->total_usd,
                'total_bs' => (float) $v->total_bs,
                'condicion_pago' => $v->condicion_pago,
                'hora' => $v->hora_emision,
            ])->toArray(),
        ];
    }

    /**
     * Registrar movimiento menor de caja (ingreso o egreso)
     */
    public function registrarMovimiento(array $datos, int $empresaId, int $userId): CajaMovimiento
    {
        $this->validarEmpresa($empresaId);
        $this->validarUsuario($userId);

        $cajaTurnoId = (int) $datos['caja_turno_id'];
        $turno = $this->obtenerTurnoPorId($cajaTurnoId, $empresaId);

        if ($turno->estado !== 'abierta') {
            throw new Exception('No se pueden registrar movimientos en un turno de caja que ya está cerrado.');
        }

        $montoUsd = (float) ($datos['monto_usd'] ?? 0);
        $montoBs = (float) ($datos['monto_bs'] ?? 0);
        $moneda = $datos['moneda'] ?? (($montoUsd > 0 && $montoBs <= 0) ? 'USD' : (($montoBs > 0 && $montoUsd <= 0) ? 'VES' : 'MIXTA'));

        return CajaMovimiento::create([
            'empresa_id' => $empresaId,
            'caja_turno_id' => $cajaTurnoId,
            'user_id' => $userId,
            'tipo' => $datos['tipo'] ?? 'egreso',
            'metodo_pago_id' => $datos['metodo_pago_id'] ?? null,
            'moneda' => $moneda,
            'monto_usd' => $montoUsd,
            'monto_bs' => $montoBs,
            'concepto' => trim($datos['concepto']),
            'comprobante_referencia' => trim($datos['comprobante_referencia'] ?? '') ?: null,
            'observaciones' => trim($datos['observaciones'] ?? '') ?: null,
            'documento_tipo' => $datos['documento_tipo'] ?? null,
            'documento_id' => $datos['documento_id'] ?? null,
        ]);
    }

    /**
     * Cerrar turno y generar Cierre Z con arqueo físico
     */
    public function cerrarTurno(int $cajaTurnoId, array $datos, int $empresaId, int $userId): CajaTurno
    {
        $this->validarEmpresa($empresaId);
        $this->validarUsuario($userId);

        return DB::transaction(function () use ($cajaTurnoId, $datos, $empresaId, $userId) {
            $user = User::findOrFail($userId);
            $esAdmin = $user->hasRole(['SuperAdmin', 'Admin', 'superadmin', 'admin', 'administrador']);
            $puedeCerrar = $esAdmin || $user->can('cajas.cerrar');

            if (! $puedeCerrar) {
                throw new AuthorizationException('No tienes permiso para cerrar turnos de caja.');
            }

            $turno = $this->obtenerTurnoPorId($cajaTurnoId, $empresaId);

            if (! $esAdmin && (int) $turno->user_id !== $userId) {
                throw new AuthorizationException('No puedes cerrar un turno de caja asignado a otro cajero.');
            }

            if ($turno->estado === 'cerrada') {
                throw new Exception('Este turno de caja ya se encuentra cerrado.');
            }

            $reporte = $this->calcularReporteTurno($cajaTurnoId, $empresaId);

            $montoCierreUsd = (float) $datos['monto_cierre_usd'];
            $montoCierreBs = (float) $datos['monto_cierre_bs'];

            $diferenciaUsd = round($montoCierreUsd - $reporte['efectivo_esperado_usd'], 2);
            $diferenciaBs = round($montoCierreBs - $reporte['efectivo_esperado_bs'], 2);

            $ahora = Carbon::now();

            $turno->update([
                'cerrado_por_id' => $userId,
                'fecha_cierre' => $ahora->toDateString(),
                'hora_cierre' => $ahora->toTimeString(),
                'monto_cierre_usd' => $montoCierreUsd,
                'monto_cierre_bs' => $montoCierreBs,
                'total_ventas_usd' => $reporte['total_ventas_usd'],
                'total_ventas_bs' => $reporte['total_ventas_bs'],
                'total_devoluciones_usd' => $reporte['total_devoluciones_usd'],
                'total_devoluciones_bs' => $reporte['total_devoluciones_bs'],
                'diferencia_usd' => $diferenciaUsd,
                'diferencia_bs' => $diferenciaBs,
                'estado' => 'cerrada',
                'observaciones' => $datos['observaciones'] ?? $turno->observaciones,
            ]);

            return $turno;
        });
    }
}
