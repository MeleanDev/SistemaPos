<?php

namespace App\Service\Finanzas;

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorCobrarAbono;
use App\Models\Empresa;
use App\Models\MetodoPago;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CuentaPorCobrarClass
{
    /**
     * Resumen general y listado agrupado de deudores (Clientes)
     */
    public function resumenClientes(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);
        $hoy = Carbon::now()->startOfDay();

        // Obtener todas las cuentas por cobrar pendientes o parciales
        $cuentasPendientes = CuentaPorCobrar::with(['cliente'])
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_emision', 'asc')
            ->get();

        $totalPorCobrarUsd = round($cuentasPendientes->sum('saldo_pendiente_usd'), 2);
        $totalPorCobrarBs = round($totalPorCobrarUsd * $tasaCambio, 2);
        $facturasPendientesCount = $cuentasPendientes->count();

        $facturasVencidas = $cuentasPendientes->filter(function ($c) use ($hoy) {
            return Carbon::parse($c->fecha_vencimiento)->startOfDay()->lt($hoy);
        });
        $facturasVencidasCount = $facturasVencidas->count();
        $totalVencidoUsd = round($facturasVencidas->sum('saldo_pendiente_usd'), 2);
        $totalVencidoBs = round($totalVencidoUsd * $tasaCambio, 2);

        // Agrupar por cliente
        $clientesAgrupados = $cuentasPendientes->groupBy('cliente_id')->map(function ($cuentas, $clienteId) use ($hoy, $tasaCambio) {
            /** @var Cliente $cliente */
            $cliente = $cuentas->first()->cliente;
            $deudaUsd = round($cuentas->sum('saldo_pendiente_usd'), 2);
            $deudaBs = round($deudaUsd * $tasaCambio, 2);

            $facturaMasAntigua = $cuentas->sortBy('fecha_emision')->first();
            $fechaAntigua = $facturaMasAntigua ? Carbon::parse($facturaMasAntigua->fecha_emision) : null;
            $diasDesdeAntigua = $fechaAntigua ? (int) $fechaAntigua->diffInDays($hoy, false) : 0;

            $tieneVencidas = $cuentas->contains(function ($c) use ($hoy) {
                return Carbon::parse($c->fecha_vencimiento)->startOfDay()->lt($hoy);
            });

            $proximaVencer = $cuentas->sortBy('fecha_vencimiento')->first();
            $fechaProxima = $proximaVencer ? Carbon::parse($proximaVencer->fecha_vencimiento) : null;

            return [
                'cliente_id' => $cliente ? $cliente->id : $clienteId,
                'cliente_nombre' => $cliente ? $cliente->nombre_completo : 'Cliente Desconocido',
                'cliente_cedula' => $cliente ? $cliente->cedula : 'S/D',
                'cliente_telefono' => $cliente ? ($cliente->telefono ?: 'No registrado') : 'No registrado',
                'cliente_tipo' => $cliente ? $cliente->tipo_cliente : 'detal',
                'total_deuda_usd' => $deudaUsd,
                'total_deuda_bs' => $deudaBs,
                'total_facturas_pendientes' => $cuentas->count(),
                'factura_mas_antigua_fecha' => $fechaAntigua ? $fechaAntigua->format('d/m/Y') : 'N/A',
                'fecha_emision_antigua_raw' => $fechaAntigua ? $fechaAntigua->toDateString() : '',
                'dias_antiguedad' => max(0, $diasDesdeAntigua),
                'tiene_vencidas' => $tieneVencidas,
                'proximo_vencimiento' => $fechaProxima ? $fechaProxima->format('d/m/Y') : 'N/A',
                'estado_alerta' => $tieneVencidas ? 'vencida' : ($diasDesdeAntigua > 15 ? 'por_vencer' : 'al_dia'),
            ];
        })->values()->sortByDesc('total_deuda_usd')->values();

        return [
            'kpis' => [
                'total_por_cobrar_usd' => $totalPorCobrarUsd,
                'total_por_cobrar_bs' => $totalPorCobrarBs,
                'clientes_deudores_count' => $clientesAgrupados->count(),
                'facturas_pendientes_count' => $facturasPendientesCount,
                'facturas_vencidas_count' => $facturasVencidasCount,
                'total_vencido_usd' => $totalVencidoUsd,
                'total_vencido_bs' => $totalVencidoBs,
                'tasa_cambio' => $tasaCambio,
            ],
            'clientes' => $clientesAgrupados,
        ];
    }

    /**
     * Detalle completo de facturas de un cliente (pendientes, pagadas e historial de abonos)
     */
    public function detalleClienteFacturas(int $clienteId, int $empresaId): array
    {
        $cliente = Cliente::findOrFail($clienteId);
        $empresa = Empresa::findOrFail($empresaId);

        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);
        $hoy = Carbon::now()->startOfDay();

        // Facturas pendientes y parciales
        $facturasPendientes = CuentaPorCobrar::with(['venta', 'abonos.metodoPago', 'abonos.usuario'])
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_emision', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($f) use ($hoy) {
                $vencimiento = Carbon::parse($f->fecha_vencimiento)->startOfDay();
                $esVencida = $vencimiento->lt($hoy);
                $diasDiferencia = (int) $hoy->diffInDays($vencimiento, false);

                return [
                    'id' => $f->id,
                    'numero_factura' => $f->numero_factura,
                    'venta_id' => $f->venta_id,
                    'venta_codigo' => $f->venta?->codigo ?? $f->numero_factura,
                    'fecha_emision' => Carbon::parse($f->fecha_emision)->format('d/m/Y'),
                    'fecha_vencimiento' => Carbon::parse($f->fecha_vencimiento)->format('d/m/Y'),
                    'fecha_emision_raw' => $f->fecha_emision->toDateString(),
                    'monto_total_usd' => (float) $f->monto_total_usd,
                    'monto_total_bs' => (float) $f->monto_total_bs,
                    'monto_pagado_usd' => (float) $f->monto_pagado_usd,
                    'monto_pagado_bs' => (float) $f->monto_pagado_bs,
                    'saldo_pendiente_usd' => (float) $f->saldo_pendiente_usd,
                    'saldo_pendiente_bs' => (float) $f->saldo_pendiente_bs,
                    'estado' => $f->estado,
                    'es_vencida' => $esVencida,
                    'dias_vencimiento' => $diasDiferencia, // negativo = días de mora, positivo = días restantes
                    'observaciones' => $f->observaciones,
                    'abonos_count' => $f->abonos->count(),
                    'abonos' => $f->abonos->map(fn ($a) => [
                        'id' => $a->id,
                        'fecha' => Carbon::parse($a->fecha_abono)->format('d/m/Y'),
                        'monto_usd' => (float) $a->monto_usd,
                        'monto_bs' => (float) $a->monto_bs,
                        'tasa_cambio' => (float) $a->tasa_cambio,
                        'metodo_pago' => $a->metodoPago?->nombre ?? 'N/A',
                        'referencia' => $a->referencia,
                        'usuario' => $a->usuario?->name ?? 'Sistema',
                    ]),
                ];
            });

        // Facturas pagadas (historial)
        $facturasPagadas = CuentaPorCobrar::with(['venta', 'abonos.metodoPago', 'abonos.usuario'])
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('estado', 'pagada')
            ->orderBy('fecha_emision', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'numero_factura' => $f->numero_factura,
                    'venta_id' => $f->venta_id,
                    'venta_codigo' => $f->venta?->codigo ?? $f->numero_factura,
                    'fecha_emision' => Carbon::parse($f->fecha_emision)->format('d/m/Y'),
                    'fecha_vencimiento' => Carbon::parse($f->fecha_vencimiento)->format('d/m/Y'),
                    'monto_total_usd' => (float) $f->monto_total_usd,
                    'monto_total_bs' => (float) $f->monto_total_bs,
                    'monto_pagado_usd' => (float) $f->monto_pagado_usd,
                    'monto_pagado_bs' => (float) $f->monto_pagado_bs,
                    'saldo_pendiente_usd' => (float) $f->saldo_pendiente_usd,
                    'estado' => $f->estado,
                    'abonos' => $f->abonos->map(fn ($a) => [
                        'id' => $a->id,
                        'fecha' => Carbon::parse($a->fecha_abono)->format('d/m/Y'),
                        'monto_usd' => (float) $a->monto_usd,
                        'monto_bs' => (float) $a->monto_bs,
                        'tasa_cambio' => (float) $a->tasa_cambio,
                        'metodo_pago' => $a->metodoPago?->nombre ?? 'N/A',
                        'referencia' => $a->referencia,
                    ]),
                ];
            });

        $totalDeudaUsd = round($facturasPendientes->sum('saldo_pendiente_usd'), 2);
        $totalDeudaBs = round($totalDeudaUsd * $tasaCambio, 2);

        return [
            'cliente' => [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'cedula' => $cliente->cedula,
                'telefono' => $cliente->telefono ?: 'No registrado',
                'correo' => $cliente->correo ?: 'No registrado',
                'direccion' => $cliente->direccion ?: 'No registrada',
                'limite_credito' => (float) $cliente->limite_credito,
                'dias_credito' => (int) $cliente->dias_credito,
                'total_deuda_usd' => $totalDeudaUsd,
                'total_deuda_bs' => $totalDeudaBs,
            ],
            'facturas_pendientes' => $facturasPendientes,
            'facturas_pagadas' => $facturasPagadas,
            'tasa_cambio' => $tasaCambio,
        ];
    }

    /**
     * Realizar abono a una factura específica
     */
    public function abonarFacturaEspecifica(int $cxcId, array $datos, int $userId, int $empresaId): array
    {
        return DB::transaction(function () use ($cxcId, $datos, $userId, $empresaId) {
            $cuenta = CuentaPorCobrar::where('empresa_id', $empresaId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->lockForUpdate()
                ->findOrFail($cxcId);

            $moneda = $datos['moneda'] ?? 'USD';
            $tasaCambio = (float) ($datos['tasa_cambio'] ?? ($cuenta->empresa?->tasa_usd > 0 ? $cuenta->empresa->tasa_usd : 1));
            $montoIngresado = (float) ($datos['monto'] ?? 0);

            if ($montoIngresado <= 0) {
                throw new Exception('El monto del abono debe ser mayor a 0.');
            }

            // Convertir a USD y Bs
            $montoUsd = $moneda === 'VES' ? round($montoIngresado / $tasaCambio, 2) : round($montoIngresado, 2);
            $montoBs = $moneda === 'VES' ? round($montoIngresado, 2) : round($montoIngresado * $tasaCambio, 2);

            $saldoActualUsd = (float) $cuenta->saldo_pendiente_usd;

            // Si el monto en USD excede el saldo pendiente (con margen de redondeo de 0.05)
            if ($montoUsd > ($saldoActualUsd + 0.05)) {
                throw new Exception('El monto a abonar ($'.number_format($montoUsd, 2).') no puede ser mayor al saldo pendiente ($'.number_format($saldoActualUsd, 2).').');
            }

            // Ajustar al saldo si la diferencia es por redondeo de centavos
            if ($montoUsd > $saldoActualUsd) {
                $montoUsd = $saldoActualUsd;
                $montoBs = round($montoUsd * $tasaCambio, 2);
            }

            $nuevoPagadoUsd = round((float) $cuenta->monto_pagado_usd + $montoUsd, 2);
            $nuevoPagadoBs = round($nuevoPagadoUsd * $tasaCambio, 2);
            $nuevoSaldoUsd = max(0, round((float) $cuenta->monto_total_usd - $nuevoPagadoUsd, 2));
            $nuevoSaldoBs = round($nuevoSaldoUsd * $tasaCambio, 2);

            $nuevoEstado = $nuevoSaldoUsd <= 0.001 ? 'pagada' : 'parcial';

            // Crear el registro de abono
            $abono = CuentaPorCobrarAbono::create([
                'cuenta_por_cobrar_id' => $cuenta->id,
                'user_id' => $userId,
                'metodo_pago_id' => $datos['metodo_pago_id'],
                'fecha_abono' => $datos['fecha_abono'] ?? Carbon::now()->toDateString(),
                'monto_usd' => $montoUsd,
                'monto_bs' => $montoBs,
                'tasa_cambio' => $tasaCambio,
                'referencia' => trim($datos['referencia'] ?? '') ?: null,
                'observaciones' => trim($datos['observaciones'] ?? '') ?: null,
            ]);

            // Actualizar la cuenta
            $cuenta->update([
                'monto_pagado_usd' => $nuevoPagadoUsd,
                'monto_pagado_bs' => $nuevoPagadoBs,
                'saldo_pendiente_usd' => $nuevoSaldoUsd,
                'saldo_pendiente_bs' => $nuevoSaldoBs,
                'estado' => $nuevoEstado,
            ]);

            return [
                'abono_id' => $abono->id,
                'cuenta_id' => $cuenta->id,
                'numero_factura' => $cuenta->numero_factura,
                'monto_abonado_usd' => $montoUsd,
                'monto_abonado_bs' => $montoBs,
                'nuevo_saldo_usd' => $nuevoSaldoUsd,
                'nuevo_saldo_bs' => $nuevoSaldoBs,
                'nuevo_estado' => $nuevoEstado,
                'mensaje' => $nuevoEstado === 'pagada' ? 'Factura saldada completamente.' : 'Abono registrado con éxito.',
            ];
        });
    }

    /**
     * Realizar Abono General a la deuda de un cliente con Algoritmo FIFO (Deuda más antigua primero)
     */
    public function abonarGeneralDeuda(int $clienteId, array $datos, int $userId, int $empresaId): array
    {
        return DB::transaction(function () use ($clienteId, $datos, $userId, $empresaId) {
            $cliente = Cliente::findOrFail($clienteId);
            $empresa = Empresa::findOrFail($empresaId);

            $tasaCambio = (float) ($datos['tasa_cambio'] ?? ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1));
            $moneda = $datos['moneda'] ?? 'USD';
            $montoIngresado = (float) ($datos['monto'] ?? 0);

            if ($montoIngresado <= 0) {
                throw new Exception('El monto del abono general debe ser mayor a 0.');
            }

            // Convertir el abono total a USD
            $montoTotalUsd = $moneda === 'VES' ? round($montoIngresado / $tasaCambio, 2) : round($montoIngresado, 2);

            // Obtener todas las facturas pendientes en orden FIFO (más antigua primero)
            $cuentasPendientes = CuentaPorCobrar::where('empresa_id', $empresaId)
                ->where('cliente_id', $clienteId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderBy('fecha_emision', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($cuentasPendientes->isEmpty()) {
                throw new Exception('El cliente no posee facturas con saldo pendiente.');
            }

            $deudaTotalClienteUsd = round($cuentasPendientes->sum('saldo_pendiente_usd'), 2);

            if ($montoTotalUsd > ($deudaTotalClienteUsd + 0.05)) {
                throw new Exception('El abono general ($'.number_format($montoTotalUsd, 2).') no puede exceder la deuda total del cliente ($'.number_format($deudaTotalClienteUsd, 2).').');
            }

            if ($montoTotalUsd > $deudaTotalClienteUsd) {
                $montoTotalUsd = $deudaTotalClienteUsd;
            }

            $montoRestanteUsd = $montoTotalUsd;
            $facturasAfectadas = [];
            $abonosIds = [];
            $fechaAbono = $datos['fecha_abono'] ?? Carbon::now()->toDateString();
            $referenciaGlobal = trim($datos['referencia'] ?? '') ?: null;
            $observacionesGlobal = trim($datos['observaciones'] ?? '') ?: null;

            foreach ($cuentasPendientes as $cuenta) {
                if ($montoRestanteUsd <= 0.001) {
                    break;
                }

                $saldoCuentaUsd = (float) $cuenta->saldo_pendiente_usd;
                $montoAplicarUsd = min($montoRestanteUsd, $saldoCuentaUsd);
                $montoAplicarBs = round($montoAplicarUsd * $tasaCambio, 2);

                $nuevoPagadoUsd = round((float) $cuenta->monto_pagado_usd + $montoAplicarUsd, 2);
                $nuevoPagadoBs = round($nuevoPagadoUsd * $tasaCambio, 2);
                $nuevoSaldoUsd = max(0, round((float) $cuenta->monto_total_usd - $nuevoPagadoUsd, 2));
                $nuevoSaldoBs = round($nuevoSaldoUsd * $tasaCambio, 2);
                $nuevoEstado = $nuevoSaldoUsd <= 0.001 ? 'pagada' : 'parcial';

                // Registrar abono específico para este renglón
                $abono = CuentaPorCobrarAbono::create([
                    'cuenta_por_cobrar_id' => $cuenta->id,
                    'user_id' => $userId,
                    'metodo_pago_id' => $datos['metodo_pago_id'],
                    'fecha_abono' => $fechaAbono,
                    'monto_usd' => $montoAplicarUsd,
                    'monto_bs' => $montoAplicarBs,
                    'tasa_cambio' => $tasaCambio,
                    'referencia' => $referenciaGlobal,
                    'observaciones' => $observacionesGlobal ? "Abono general FIFO: {$observacionesGlobal}" : 'Abono general distribuido FIFO',
                ]);

                $cuenta->update([
                    'monto_pagado_usd' => $nuevoPagadoUsd,
                    'monto_pagado_bs' => $nuevoPagadoBs,
                    'saldo_pendiente_usd' => $nuevoSaldoUsd,
                    'saldo_pendiente_bs' => $nuevoSaldoBs,
                    'estado' => $nuevoEstado,
                ]);

                $abonosIds[] = $abono->id;
                $facturasAfectadas[] = [
                    'cuenta_id' => $cuenta->id,
                    'numero_factura' => $cuenta->numero_factura,
                    'fecha_emision' => Carbon::parse($cuenta->fecha_emision)->format('d/m/Y'),
                    'monto_aplicado_usd' => $montoAplicarUsd,
                    'monto_aplicado_bs' => $montoAplicarBs,
                    'nuevo_saldo_usd' => $nuevoSaldoUsd,
                    'nuevo_estado' => $nuevoEstado,
                ];

                $montoRestanteUsd = round($montoRestanteUsd - $montoAplicarUsd, 2);
            }

            // Calcular deuda restante del cliente
            $nuevaDeudaRestanteUsd = max(0, round($deudaTotalClienteUsd - $montoTotalUsd, 2));
            $nuevaDeudaRestanteBs = round($nuevaDeudaRestanteUsd * $tasaCambio, 2);

            return [
                'cliente' => $cliente->nombre,
                'monto_total_abonado_usd' => $montoTotalUsd,
                'monto_total_abonado_bs' => round($montoTotalUsd * $tasaCambio, 2),
                'deuda_anterior_usd' => $deudaTotalClienteUsd,
                'deuda_restante_usd' => $nuevaDeudaRestanteUsd,
                'deuda_restante_bs' => $nuevaDeudaRestanteBs,
                'facturas_afectadas' => $facturasAfectadas,
                'primer_abono_id' => $abonosIds[0] ?? null,
                'abonos_ids' => $abonosIds,
                'mensaje' => 'Abono general FIFO aplicado con éxito a '.count($facturasAfectadas).' factura(s).',
            ];
        });
    }

    /**
     * Catálogos para formularios de cobro (métodos de pago y tasa de cambio activa)
     */
    public function catalogos(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $metodosPago = MetodoPago::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre', 'tipo', 'icono']);

        return [
            'metodos_pago' => $metodosPago,
            'tasa_usd' => (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1),
            'empresa_nombre' => $empresa->nombre_comercial,
        ];
    }

    /**
     * Obtener comprobante de abono para impresión térmica
     */
    public function obtenerComprobanteAbono(int $abonoId, int $empresaId): array
    {
        $abono = CuentaPorCobrarAbono::with([
            'cuentaPorCobrar.cliente',
            'cuentaPorCobrar.empresa',
            'cuentaPorCobrar.venta',
            'metodoPago',
            'usuario',
        ])->whereHas('cuentaPorCobrar', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->findOrFail($abonoId);

        $cuenta = $abono->cuentaPorCobrar;
        $empresa = $cuenta->empresa;
        $cliente = $cuenta->cliente;

        // Calcular deuda actual total del cliente
        $deudaTotalClienteUsd = (float) CuentaPorCobrar::where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->sum('saldo_pendiente_usd');

        return [
            'abono' => $abono,
            'cuenta' => $cuenta,
            'empresa' => $empresa,
            'cliente' => $cliente,
            'deuda_total_cliente_usd' => round($deudaTotalClienteUsd, 2),
            'deuda_total_cliente_bs' => round($deudaTotalClienteUsd * (float) $abono->tasa_cambio, 2),
        ];
    }
}
