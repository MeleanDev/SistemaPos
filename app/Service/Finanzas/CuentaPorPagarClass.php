<?php

namespace App\Service\Finanzas;

use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarAbono;
use App\Models\Empresa;
use App\Models\MetodoPago;
use App\Models\Proveedor;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CuentaPorPagarClass
{
    /**
     * Resumen general y listado agrupado de deudas con Proveedores
     */
    public function resumenProveedores(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);
        $hoy = Carbon::now()->startOfDay();

        // Obtener todas las cuentas por pagar pendientes o parciales
        $cuentasPendientes = CuentaPorPagar::with(['proveedor'])
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_emision', 'asc')
            ->get();

        $totalPorPagarUsd = round($cuentasPendientes->sum('saldo_pendiente_usd'), 2);
        $totalPorPagarBs = round($totalPorPagarUsd * $tasaCambio, 2);
        $facturasPendientesCount = $cuentasPendientes->count();

        $facturasVencidas = $cuentasPendientes->filter(function ($c) use ($hoy) {
            return Carbon::parse($c->fecha_vencimiento)->startOfDay()->lt($hoy);
        });
        $facturasVencidasCount = $facturasVencidas->count();
        $totalVencidoUsd = round($facturasVencidas->sum('saldo_pendiente_usd'), 2);
        $totalVencidoBs = round($totalVencidoUsd * $tasaCambio, 2);

        // Agrupar por proveedor
        $proveedoresAgrupados = $cuentasPendientes->groupBy('proveedor_id')->map(function ($cuentas, $proveedorId) use ($hoy, $tasaCambio) {
            /** @var Proveedor $proveedor */
            $proveedor = $cuentas->first()->proveedor;
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
                'proveedor_id' => $proveedor ? $proveedor->id : $proveedorId,
                'proveedor_nombre' => $proveedor ? $proveedor->nombre : 'Proveedor Desconocido',
                'proveedor_rif' => $proveedor ? $proveedor->rif : 'S/D',
                'proveedor_telefono' => $proveedor ? ($proveedor->telefono ?: 'No registrado') : 'No registrado',
                'proveedor_contacto' => $proveedor ? ($proveedor->nombre_contacto ?: 'No registrado') : 'No registrado',
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
                'total_por_pagar_usd' => $totalPorPagarUsd,
                'total_por_pagar_bs' => $totalPorPagarBs,
                'proveedores_deudores_count' => $proveedoresAgrupados->count(),
                'facturas_pendientes_count' => $facturasPendientesCount,
                'facturas_vencidas_count' => $facturasVencidasCount,
                'total_vencido_usd' => $totalVencidoUsd,
                'total_vencido_bs' => $totalVencidoBs,
                'tasa_cambio' => $tasaCambio,
            ],
            'proveedores' => $proveedoresAgrupados,
        ];
    }

    /**
     * Detalle completo de facturas de un proveedor (pendientes, pagadas e historial de abonos)
     */
    public function detalleProveedorFacturas(int $proveedorId, int $empresaId): array
    {
        $proveedor = Proveedor::findOrFail($proveedorId);
        $empresa = Empresa::findOrFail($empresaId);

        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);
        $hoy = Carbon::now()->startOfDay();

        // Facturas pendientes y parciales
        $facturasPendientes = CuentaPorPagar::with(['recepcion', 'abonos.metodoPago', 'abonos.usuario'])
            ->where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
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
                    'recepcion_id' => $f->recepcion_id,
                    'recepcion_codigo' => $f->recepcion?->codigo ?? $f->numero_factura,
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
                    'dias_vencimiento' => $diasDiferencia, // negativo = mora, positivo = días restantes
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
        $facturasPagadas = CuentaPorPagar::with(['recepcion', 'abonos.metodoPago', 'abonos.usuario'])
            ->where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedorId)
            ->where('estado', 'pagada')
            ->orderBy('fecha_emision', 'desc')
            ->get()
            ->map(function ($f) {
                return [
                    'id' => $f->id,
                    'numero_factura' => $f->numero_factura,
                    'recepcion_id' => $f->recepcion_id,
                    'recepcion_codigo' => $f->recepcion?->codigo ?? $f->numero_factura,
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
            'proveedor' => [
                'id' => $proveedor->id,
                'nombre' => $proveedor->nombre,
                'rif' => $proveedor->rif,
                'telefono' => $proveedor->telefono ?: 'No registrado',
                'correo' => $proveedor->correo ?: 'No registrado',
                'direccion' => $proveedor->direccion ?: 'No registrada',
                'nombre_contacto' => $proveedor->nombre_contacto ?: 'No registrado',
                'total_deuda_usd' => $totalDeudaUsd,
                'total_deuda_bs' => $totalDeudaBs,
            ],
            'facturas_pendientes' => $facturasPendientes,
            'facturas_pagadas' => $facturasPagadas,
            'tasa_cambio' => $tasaCambio,
        ];
    }

    /**
     * Realizar abono a una factura específica de proveedor
     */
    public function abonarFacturaEspecifica(int $cxpId, array $datos, int $userId, int $empresaId): array
    {
        return DB::transaction(function () use ($cxpId, $datos, $userId, $empresaId) {
            $cuenta = CuentaPorPagar::where('empresa_id', $empresaId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->lockForUpdate()
                ->findOrFail($cxpId);

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
                throw new Exception('El monto a pagar ($'.number_format($montoUsd, 2).') no puede ser mayor al saldo pendiente ($'.number_format($saldoActualUsd, 2).').');
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
            $abono = CuentaPorPagarAbono::create([
                'cuenta_por_pagar_id' => $cuenta->id,
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
                'mensaje' => $nuevoEstado === 'pagada' ? 'Factura de compra saldada completamente.' : 'Pago a proveedor registrado con éxito.',
            ];
        });
    }

    /**
     * Realizar Abono General a la deuda con un Proveedor con Algoritmo FIFO (Deuda más antigua primero)
     */
    public function abonarGeneralDeuda(int $proveedorId, array $datos, int $userId, int $empresaId): array
    {
        return DB::transaction(function () use ($proveedorId, $datos, $userId, $empresaId) {
            $proveedor = Proveedor::findOrFail($proveedorId);
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
            $cuentasPendientes = CuentaPorPagar::where('empresa_id', $empresaId)
                ->where('proveedor_id', $proveedorId)
                ->whereIn('estado', ['pendiente', 'parcial'])
                ->orderBy('fecha_emision', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            if ($cuentasPendientes->isEmpty()) {
                throw new Exception('El proveedor no posee facturas con saldo pendiente.');
            }

            $deudaTotalProveedorUsd = round($cuentasPendientes->sum('saldo_pendiente_usd'), 2);

            if ($montoTotalUsd > ($deudaTotalProveedorUsd + 0.05)) {
                throw new Exception('El abono general ($'.number_format($montoTotalUsd, 2).') no puede exceder la deuda total con el proveedor ($'.number_format($deudaTotalProveedorUsd, 2).').');
            }

            if ($montoTotalUsd > $deudaTotalProveedorUsd) {
                $montoTotalUsd = $deudaTotalProveedorUsd;
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
                $abono = CuentaPorPagarAbono::create([
                    'cuenta_por_pagar_id' => $cuenta->id,
                    'user_id' => $userId,
                    'metodo_pago_id' => $datos['metodo_pago_id'],
                    'fecha_abono' => $fechaAbono,
                    'monto_usd' => $montoAplicarUsd,
                    'monto_bs' => $montoAplicarBs,
                    'tasa_cambio' => $tasaCambio,
                    'referencia' => $referenciaGlobal,
                    'observaciones' => $observacionesGlobal ? "Abono general FIFO: {$observacionesGlobal}" : 'Abono general distribuido FIFO a proveedor',
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

            // Calcular deuda restante con el proveedor
            $nuevaDeudaRestanteUsd = max(0, round($deudaTotalProveedorUsd - $montoTotalUsd, 2));
            $nuevaDeudaRestanteBs = round($nuevaDeudaRestanteUsd * $tasaCambio, 2);

            return [
                'proveedor' => $proveedor->nombre,
                'monto_total_abonado_usd' => $montoTotalUsd,
                'monto_total_abonado_bs' => round($montoTotalUsd * $tasaCambio, 2),
                'deuda_anterior_usd' => $deudaTotalProveedorUsd,
                'deuda_restante_usd' => $nuevaDeudaRestanteUsd,
                'deuda_restante_bs' => $nuevaDeudaRestanteBs,
                'facturas_afectadas' => $facturasAfectadas,
                'primer_abono_id' => $abonosIds[0] ?? null,
                'abonos_ids' => $abonosIds,
                'mensaje' => 'Abono general FIFO aplicado con éxito a '.count($facturasAfectadas).' factura(s) de compras.',
            ];
        });
    }

    /**
     * Catálogos para formularios de pago a proveedores
     */
    public function catalogos(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $metodosPago = MetodoPago::where('estado', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre', 'descripcion']);

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
        $abono = CuentaPorPagarAbono::with([
            'cuentaPorPagar.proveedor',
            'cuentaPorPagar.empresa',
            'cuentaPorPagar.recepcion',
            'metodoPago',
            'usuario',
        ])->whereHas('cuentaPorPagar', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->findOrFail($abonoId);

        $cuenta = $abono->cuentaPorPagar;
        $empresa = $cuenta->empresa;
        $proveedor = $cuenta->proveedor;

        // Calcular deuda actual total con el proveedor
        $deudaTotalProveedorUsd = (float) CuentaPorPagar::where('empresa_id', $empresaId)
            ->where('proveedor_id', $proveedor->id)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->sum('saldo_pendiente_usd');

        return [
            'abono' => $abono,
            'cuenta' => $cuenta,
            'empresa' => $empresa,
            'proveedor' => $proveedor,
            'deuda_total_proveedor_usd' => round($deudaTotalProveedorUsd, 2),
            'deuda_total_proveedor_bs' => round($deudaTotalProveedorUsd * (float) $abono->tasa_cambio, 2),
        ];
    }
}
