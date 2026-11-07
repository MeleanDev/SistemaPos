<?php

namespace App\Service\Empresa;

use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\LiquidacionComision;
use App\Models\MetodoPago;
use App\Models\Secuencia;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class VendedorClass
{
    /**
     * Obtener usuarios del sistema elegibles para vincular a vendedores
     */
    public function usuariosDisponibles(int $empresaId): Collection
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
     * Listado de vendedores para DataTables
     */
    public function lista(int $empresaId): Builder
    {
        return Vendedor::with('user:id,name,nombre,apellido,email')
            ->select(
                'id',
                'empresa_id',
                'user_id',
                'tipo_documento',
                'documento',
                'nombre',
                'telefono',
                'correo',
                'comision_porcentaje',
                'estado'
            )->where('empresa_id', $empresaId);
    }

    /**
     * Obtener listado de vendedores activos para selectores
     */
    public function activos(int $empresaId): Collection
    {
        return Vendedor::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Detalle de un vendedor
     */
    public function detalle(int $id, int $empresaId): Vendedor
    {
        return Vendedor::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar nuevo vendedor
     */
    public function guardar(array $datos, int $empresaId): Vendedor
    {
        $datos['empresa_id'] = $empresaId;
        $datos['estado'] = true;
        if (isset($datos['tipo_documento'])) {
            $datos['tipo_documento'] = strtoupper(str_replace('-', '', (string) $datos['tipo_documento']));
        }

        return Vendedor::create($datos);
    }

    /**
     * Actualizar vendedor
     */
    public function actualizar(array $datos, int $id, int $empresaId): Vendedor
    {
        $vendedor = $this->detalle($id, $empresaId);
        if (isset($datos['tipo_documento'])) {
            $datos['tipo_documento'] = strtoupper(str_replace('-', '', (string) $datos['tipo_documento']));
        }
        $vendedor->update($datos);

        return $vendedor;
    }

    /**
     * Alternar estado (activo / inactivo)
     */
    public function eliminar(int $id, int $empresaId): Vendedor
    {
        $vendedor = $this->detalle($id, $empresaId);
        $vendedor->estado = ! $vendedor->estado;
        $vendedor->save();

        return $vendedor;
    }

    /**
     * Listar ventas con comisiones pendientes de un vendedor
     */
    public function comisionesPendientes(int $vendedorId, int $empresaId, ?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        $vendedor = $this->detalle($vendedorId, $empresaId);

        $query = Venta::with('cliente:id,nombre,apellido,cedula')
            ->where('empresa_id', $empresaId)
            ->where('vendedor_id', $vendedor->id)
            ->where('comision_estado', 'pendiente')
            ->whereNotIn('estado', ['anulada', 'devuelta_total'])
            ->where('comision_monto_usd', '>', 0);

        if ($fechaInicio) {
            $query->whereDate('fecha_emision', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->whereDate('fecha_emision', '<=', $fechaFin);
        }

        $ventas = $query->orderBy('fecha_emision', 'asc')
            ->orderBy('id', 'asc')
            ->get([
                'id',
                'codigo',
                'numero_control',
                'cliente_id',
                'fecha_emision',
                'estado',
                'subtotal_neto_usd',
                'subtotal_neto_bs',
                'total_usd',
                'total_bs',
                'comision_porcentaje',
                'comision_monto_usd',
                'comision_monto_bs',
                'comision_estado',
            ]);

        return [
            'vendedor' => [
                'id' => $vendedor->id,
                'nombre' => $vendedor->nombre,
                'documento' => $vendedor->documento_completo,
                'comision_porcentaje' => (float) $vendedor->comision_porcentaje,
            ],
            'cantidad_ventas' => $ventas->count(),
            'total_comision_usd' => round((float) $ventas->sum('comision_monto_usd'), 2),
            'total_comision_bs' => round((float) $ventas->sum('comision_monto_bs'), 2),
            'ventas' => $ventas,
        ];
    }

    /**
     * Liquidar y pagar comisiones pendientes de un vendedor
     */
    public function liquidarComisiones(int $vendedorId, array $datos, int $empresaId, int $userId): LiquidacionComision
    {
        return DB::transaction(function () use ($vendedorId, $datos, $empresaId, $userId) {
            $vendedor = $this->detalle($vendedorId, $empresaId);
            $user = User::findOrFail($userId);

            $ventaIds = ! empty($datos['venta_ids']) && is_array($datos['venta_ids'])
                ? array_values(array_unique(array_map('intval', $datos['venta_ids'])))
                : [];

            $query = Venta::where('empresa_id', $empresaId)
                ->where('vendedor_id', $vendedor->id)
                ->where('comision_estado', 'pendiente')
                ->whereNotIn('estado', ['anulada', 'devuelta_total'])
                ->where('comision_monto_usd', '>', 0);

            if (! empty($ventaIds)) {
                $query->whereIn('id', $ventaIds);
            }

            if (! empty($datos['fecha_inicio'])) {
                $query->whereDate('fecha_emision', '>=', $datos['fecha_inicio']);
            }

            if (! empty($datos['fecha_fin'])) {
                $query->whereDate('fecha_emision', '<=', $datos['fecha_fin']);
            }

            $ventasElegibles = $query->orderBy('id', 'asc')->lockForUpdate()->get();

            if ($ventasElegibles->isEmpty()) {
                throw new Exception('No hay comisiones pendientes elegibles para liquidar con los criterios indicados.');
            }

            if (! empty($ventaIds) && $ventasElegibles->count() !== count($ventaIds)) {
                throw new Exception('Una o más facturas seleccionadas ya fueron liquidadas o no son elegibles para comisión.');
            }

            $registrarEgresoCaja = (bool) ($datos['registrar_egreso_caja'] ?? false);
            $cajaTurno = null;
            $metodoPagoId = ! empty($datos['metodo_pago_id']) ? (int) $datos['metodo_pago_id'] : null;

            if ($metodoPagoId) {
                $metodoValido = MetodoPago::where('id', $metodoPagoId)->where('estado', true)->exists();
                if (! $metodoValido) {
                    throw new Exception('El método de pago seleccionado no existe o se encuentra inactivo.');
                }
            }

            if ($registrarEgresoCaja) {
                $cajaTurnoIdInput = ! empty($datos['caja_turno_id']) ? (int) $datos['caja_turno_id'] : null;
                $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin']);

                if ($esAdmin) {
                    $turnosAbiertos = CajaTurno::where('empresa_id', $empresaId)
                        ->where('estado', 'abierta')
                        ->orderBy('id', 'asc')
                        ->get();

                    if ($turnosAbiertos->isEmpty()) {
                        throw new Exception('Debes tener un turno de caja abierto para registrar el pago de comisiones por caja.');
                    }

                    if ($cajaTurnoIdInput) {
                        $cajaTurno = $turnosAbiertos->firstWhere('id', $cajaTurnoIdInput);
                        if (! $cajaTurno) {
                            throw new Exception('El turno de caja seleccionado no pertenece a la empresa o no se encuentra abierto.');
                        }
                    } elseif ($turnosAbiertos->count() === 1) {
                        $cajaTurno = $turnosAbiertos->first();
                    } else {
                        throw new Exception('Debes seleccionar la caja abierta de la cual saldrá el pago de la comisión.');
                    }
                } else {
                    $turnoPropio = $user->cajaTurnoActivo($empresaId);
                    if (! $turnoPropio) {
                        throw new Exception('Debes tener un turno de caja abierto asignado para registrar el pago de comisiones por caja.');
                    }
                    if ($cajaTurnoIdInput && $cajaTurnoIdInput !== (int) $turnoPropio->id) {
                        throw new Exception('Solo puedes registrar el egreso de comisión en tu propio turno de caja abierto.');
                    }
                    $cajaTurno = $turnoPropio;
                }

                if (! $metodoPagoId) {
                    $metodoPagoId = MetodoPago::where('estado', true)->where('es_efectivo', true)->value('id')
                        ?? MetodoPago::where('estado', true)->value('id');
                }
            }

            $numeroSecuencia = Secuencia::siguiente($empresaId, 'liquidacion_comision');
            $codigo = 'LIQ-'.str_pad((string) $numeroSecuencia, 5, '0', STR_PAD_LEFT);

            $cantidadVentas = $ventasElegibles->count();
            $totalComisionUsd = round((float) $ventasElegibles->sum('comision_monto_usd'), 2);
            $totalComisionBs = round((float) $ventasElegibles->sum('comision_monto_bs'), 2);

            $minFecha = $ventasElegibles->min('fecha_emision');
            $maxFecha = $ventasElegibles->max('fecha_emision');
            $fechaInicioLiq = $datos['fecha_inicio'] ?? ($minFecha instanceof \DateTimeInterface ? $minFecha->format('Y-m-d') : (is_string($minFecha) ? substr($minFecha, 0, 10) : null));
            $fechaFinLiq = $datos['fecha_fin'] ?? ($maxFecha instanceof \DateTimeInterface ? $maxFecha->format('Y-m-d') : (is_string($maxFecha) ? substr($maxFecha, 0, 10) : null));

            $liquidacion = LiquidacionComision::create([
                'empresa_id' => $empresaId,
                'vendedor_id' => $vendedor->id,
                'user_id' => $userId,
                'caja_turno_id' => $cajaTurno?->id,
                'metodo_pago_id' => $metodoPagoId,
                'codigo' => $codigo,
                'fecha_inicio' => $fechaInicioLiq,
                'fecha_fin' => $fechaFinLiq,
                'cantidad_ventas' => $cantidadVentas,
                'total_comision_usd' => $totalComisionUsd,
                'total_comision_bs' => $totalComisionBs,
                'pagado_por_caja' => $registrarEgresoCaja,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            Venta::whereIn('id', $ventasElegibles->pluck('id'))
                ->update([
                    'comision_estado' => 'pagada',
                    'liquidacion_comision_id' => $liquidacion->id,
                ]);

            if ($registrarEgresoCaja && $cajaTurno) {
                $monedaEgreso = ! empty($datos['moneda'])
                    ? strtoupper(trim((string) $datos['moneda']))
                    : 'USD';

                if (! in_array($monedaEgreso, ['USD', 'VES', 'BS'], true)) {
                    $monedaEgreso = 'USD';
                }

                CajaMovimiento::create([
                    'empresa_id' => $empresaId,
                    'caja_turno_id' => $cajaTurno->id,
                    'user_id' => $userId,
                    'tipo' => 'egreso',
                    'metodo_pago_id' => $metodoPagoId,
                    'moneda' => $monedaEgreso,
                    'monto_usd' => $totalComisionUsd,
                    'monto_bs' => $totalComisionBs,
                    'concepto' => "Liquidación de comisiones #{$liquidacion->codigo} ({$vendedor->nombre})",
                    'comprobante_referencia' => $liquidacion->codigo,
                    'observaciones' => $datos['observaciones'] ?? null,
                    'documento_tipo' => 'liquidacion_comision',
                    'documento_id' => $liquidacion->id,
                ]);
            }

            return $liquidacion->load(['vendedor', 'usuario', 'cajaTurno', 'metodoPago', 'ventas']);
        });
    }
}
