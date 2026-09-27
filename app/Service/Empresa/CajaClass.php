<?php

namespace App\Service\Empresa;

use App\Models\Caja;
use App\Models\CajaTurno;
use App\Models\DevolucionVenta;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CajaClass
{
    /**
     * Listado de cajas para DataTables
     */
    public function listaCajas(int $empresaId): Builder
    {
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
            ->where(function ($query) use ($empresaId) {
                $query->whereHas('empresas', fn ($q) => $q->where('empresas.id', $empresaId))
                    ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['SuperAdmin', 'Admin', 'Operador']));
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
        return DB::transaction(function () use ($datos, $empresaId, $adminUserId) {
            $adminUser = User::findOrFail($adminUserId);
            $esAdmin = $adminUser->hasRole(['SuperAdmin', 'Admin', 'superadmin', 'admin', 'administrador']) || $adminUser->can('cajas.aperturar');

            if (! $esAdmin) {
                throw new Exception('Solo los administradores o usuarios autorizados pueden aperturar turnos de caja.');
            }

            $cajaId = (int) $datos['caja_id'];
            $cajeroId = (int) ($datos['user_id'] ?? $adminUserId);

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
                $nombreLower = strtolower($metodoNombre);
                if (str_contains($nombreLower, 'efectivo') || str_contains($nombreLower, 'cash')) {
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
                $totalDevolucionesEfectivoUsd += (float) $dev->total_devuelto_usd;
                $totalDevolucionesEfectivoBs += (float) $dev->total_devuelto_bs;
            }
        }

        // Efectivo esperado en caja (Fondo inicial + Ventas efectivo - Devoluciones efectivo)
        $montoAperturaUsd = (float) $turno->monto_apertura_usd;
        $montoAperturaBs = (float) $turno->monto_apertura_bs;

        $efectivoEsperadoUsd = max(0, $montoAperturaUsd + $totalEfectivoVentasUsd - $totalDevolucionesEfectivoUsd);
        $efectivoEsperadoBs = max(0, $montoAperturaBs + $totalEfectivoVentasBs - $totalDevolucionesEfectivoBs);

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
            'pagos_por_metodo' => array_values($pagosPorMetodo),
            'total_efectivo_ventas_usd' => $totalEfectivoVentasUsd,
            'total_efectivo_ventas_bs' => $totalEfectivoVentasBs,
            'total_devoluciones_efectivo_usd' => $totalDevolucionesEfectivoUsd,
            'total_devoluciones_efectivo_bs' => $totalDevolucionesEfectivoBs,
            'efectivo_esperado_usd' => $efectivoEsperadoUsd,
            'efectivo_esperado_bs' => $efectivoEsperadoBs,
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
     * Cerrar turno y generar Cierre Z con arqueo físico
     */
    public function cerrarTurno(int $cajaTurnoId, array $datos, int $empresaId, int $adminUserId): CajaTurno
    {
        return DB::transaction(function () use ($cajaTurnoId, $datos, $empresaId, $adminUserId) {
            $adminUser = User::findOrFail($adminUserId);
            $esAdmin = $adminUser->hasRole(['SuperAdmin', 'Admin', 'superadmin', 'admin', 'administrador']) || $adminUser->can('cajas.cerrar');

            if (! $esAdmin) {
                throw new Exception('Solo los administradores o usuarios autorizados pueden cerrar turnos de caja.');
            }

            $turno = $this->obtenerTurnoPorId($cajaTurnoId, $empresaId);

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
                'cerrado_por_id' => $adminUserId,
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
