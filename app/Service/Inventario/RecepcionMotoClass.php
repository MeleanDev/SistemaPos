<?php

namespace App\Service\Inventario;

use App\Models\CuentaPorPagar;
use App\Models\EmpresaMoneda;
use App\Models\Moto;
use App\Models\RecepcionMoto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecepcionMotoClass
{
    /**
     * Listado de recepciones de motos por empresa
     */
    public function lista(int $empresaId)
    {
        return RecepcionMoto::with(['proveedor', 'almacen', 'usuario'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc');
    }

    /**
     * Obtener detalle completo de una recepción de motos
     */
    public function detalle(int $id, int $empresaId)
    {
        return RecepcionMoto::with([
            'proveedor',
            'almacen',
            'usuario',
            'detalles.almacen',
            'detalles.motos.almacen',
            'motos.almacen',
        ])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Generar correlativo único de recepción de motos por empresa
     */
    public function generarCodigo(int $empresaId): string
    {
        $ultimo = RecepcionMoto::where('empresa_id', $empresaId)->max('id') ?? 0;
        $numero = str_pad($ultimo + 1, 6, '0', STR_PAD_LEFT);

        return 'RECMOTO-'.$numero;
    }

    /**
     * Generar referencia numérica autoincrementable para motos por empresa
     */
    public function generarReferenciaNumerica(int $empresaId): string
    {
        $motos = Moto::where('empresa_id', $empresaId)->get(['id', 'referencia']);
        $maxNum = 0;

        foreach ($motos as $m) {
            $ref = trim($m->referencia ?? '');
            if (is_numeric($ref)) {
                $val = (int) $ref;
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            } elseif (preg_match('/(\d+)/', $ref, $matches)) {
                $val = (int) $matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        return (string) ($maxNum + 1);
    }

    /**
     * Obtener tasa de cambio oficial de la empresa
     */
    public function obtenerTasaOficial(int $empresaId): float
    {
        $moneda = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->first();

        return $moneda && $moneda->tasa_cambio > 0 ? (float) $moneda->tasa_cambio : 1.0000;
    }

    /**
     * Guardar recepción de lote de motos con seriales únicos
     */
    public function guardar(array $datos, $user, int $empresaId): RecepcionMoto
    {
        return DB::transaction(function () use ($datos, $user, $empresaId) {
            $tasaCambio = ! empty($datos['tasa_cambio']) && $datos['tasa_cambio'] > 0
                ? (float) $datos['tasa_cambio']
                : $this->obtenerTasaOficial($empresaId);

            $monedaDoc = $datos['moneda_documento'] ?? 'USD';

            // 1. Validar seriales únicos contra la base de datos y dentro del mismo lote
            $nivsEnviados = [];
            $chasisEnviados = [];
            $motoresEnviados = [];

            foreach ($datos['detalles'] as $idxDetalle => $det) {
                if (empty($det['seriales']) || ! is_array($det['seriales'])) {
                    throw ValidationException::withMessages([
                        "detalles.{$idxDetalle}.seriales" => "Debe ingresar los seriales para el modelo {$det['marca']} {$det['modelo']}.",
                    ]);
                }

                if (count($det['seriales']) !== (int) $det['cantidad']) {
                    throw ValidationException::withMessages([
                        "detalles.{$idxDetalle}.seriales" => 'La cantidad de seriales ('.count($det['seriales']).") no coincide con la cantidad del lote ({$det['cantidad']}) en {$det['marca']} {$det['modelo']}.",
                    ]);
                }

                foreach ($det['seriales'] as $sIdx => $serial) {
                    $niv = strtoupper(trim($serial['numero_niv'] ?? ''));
                    $chasis = strtoupper(trim($serial['numero_chasis'] ?? ''));
                    $motor = strtoupper(trim($serial['numero_motor'] ?? ''));

                    if (empty($niv) || empty($chasis) || empty($motor)) {
                        throw ValidationException::withMessages([
                            'detalles' => 'Faltan seriales obligatorios en el renglón #'.($idxDetalle + 1).', unidad #'.($sIdx + 1).'.',
                        ]);
                    }

                    // Duplicados en el payload
                    if (in_array($niv, $nivsEnviados, true)) {
                        throw ValidationException::withMessages([
                            'seriales' => "El N.I.V. '{$niv}' está duplicado en este mismo documento.",
                        ]);
                    }
                    if (in_array($chasis, $chasisEnviados, true)) {
                        throw ValidationException::withMessages([
                            'seriales' => "El Número de Chasis '{$chasis}' está duplicado en este mismo documento.",
                        ]);
                    }
                    if (in_array($motor, $motoresEnviados, true)) {
                        throw ValidationException::withMessages([
                            'seriales' => "El Número de Motor '{$motor}' está duplicado en este mismo documento.",
                        ]);
                    }

                    $nivsEnviados[] = $niv;
                    $chasisEnviados[] = $chasis;
                    $motoresEnviados[] = $motor;

                    // Duplicados en BD para la misma empresa en motos activas/disponibles
                    $duplicadoNiv = Moto::where('empresa_id', $empresaId)
                        ->where('numero_niv', $niv)
                        ->whereIn('estado', ['disponible', 'reservada'])
                        ->first();
                    if ($duplicadoNiv) {
                        throw ValidationException::withMessages([
                            'seriales' => "El N.I.V. '{$niv}' ya se encuentra registrado en el inventario activo de esta empresa.",
                        ]);
                    }

                    $duplicadoChasis = Moto::where('empresa_id', $empresaId)
                        ->where('numero_chasis', $chasis)
                        ->whereIn('estado', ['disponible', 'reservada'])
                        ->first();
                    if ($duplicadoChasis) {
                        throw ValidationException::withMessages([
                            'seriales' => "El Número de Chasis '{$chasis}' ya se encuentra registrado en el inventario activo.",
                        ]);
                    }

                    $duplicadoMotor = Moto::where('empresa_id', $empresaId)
                        ->where('numero_motor', $motor)
                        ->whereIn('estado', ['disponible', 'reservada'])
                        ->first();
                    if ($duplicadoMotor) {
                        throw ValidationException::withMessages([
                            'seriales' => "El Número de Motor '{$motor}' ya se encuentra registrado en el inventario activo.",
                        ]);
                    }
                }
            }

            // 2. Cálculos de totales
            $subtotalGlobalUsd = 0;
            $ivaGlobalUsd = 0;
            $totalGlobalUsd = 0;
            $totalUnidades = 0;

            $detallesCalculados = [];

            foreach ($datos['detalles'] as $det) {
                $cantidad = (int) $det['cantidad'];
                $totalUnidades += $cantidad;

                $costoUnitarioUsd = (float) $det['costo_unitario_usd'];
                $costoUnitarioBs = $costoUnitarioUsd * $tasaCambio;

                $descPct = ! empty($det['descuento_porcentaje']) ? (float) $det['descuento_porcentaje'] : 0;
                $descUsd = round($costoUnitarioUsd * ($descPct / 100), 4);
                $descBs = round($descUsd * $tasaCambio, 4);

                $costoNetoUsd = $costoUnitarioUsd - $descUsd;
                $costoNetoBs = $costoNetoUsd * $tasaCambio;

                $aplicaIva = ! empty($det['aplica_iva']);
                $ivaPct = $aplicaIva ? (! empty($det['iva_porcentaje']) ? (float) $det['iva_porcentaje'] : 16.00) : 0;
                $ivaUnitarioUsd = $aplicaIva ? round($costoNetoUsd * ($ivaPct / 100), 4) : 0;
                $ivaUnitarioBs = round($ivaUnitarioUsd * $tasaCambio, 4);

                $margenDetalPct = ! empty($det['margen_detal']) ? (float) $det['margen_detal'] : 0;
                $precioDetalUsd = ! empty($det['precio_detal_usd']) && $det['precio_detal_usd'] > 0
                    ? (float) $det['precio_detal_usd']
                    : round($costoNetoUsd * (1 + ($margenDetalPct / 100)), 4);
                $precioDetalBs = round($precioDetalUsd * $tasaCambio, 4);

                $margenMayoristaPct = ! empty($det['margen_mayorista']) ? (float) $det['margen_mayorista'] : 0;
                $precioMayoristaUsd = ! empty($det['precio_mayorista_usd']) && $det['precio_mayorista_usd'] > 0
                    ? (float) $det['precio_mayorista_usd']
                    : round($costoNetoUsd * (1 + ($margenMayoristaPct / 100)), 4);
                $precioMayoristaBs = round($precioMayoristaUsd * $tasaCambio, 4);

                $renglonSubtotalUsd = round($costoNetoUsd * $cantidad, 2);
                $renglonSubtotalBs = round($renglonSubtotalUsd * $tasaCambio, 2);

                $renglonIvaUsd = round($ivaUnitarioUsd * $cantidad, 2);
                $renglonIvaBs = round($renglonIvaUsd * $tasaCambio, 2);

                $renglonTotalUsd = round($renglonSubtotalUsd + $renglonIvaUsd, 2);
                $renglonTotalBs = round($renglonTotalUsd * $tasaCambio, 2);

                $subtotalGlobalUsd += $renglonSubtotalUsd;
                $ivaGlobalUsd += $renglonIvaUsd;
                $totalGlobalUsd += $renglonTotalUsd;

                $refLote = trim($det['referencia'] ?? '');
                if (empty($refLote)) {
                    $refLote = $this->generarReferenciaNumerica($empresaId);
                }

                $detallesCalculados[] = [
                    'almacen_id' => $det['almacen_id'] ?? $datos['almacen_id'],
                    'referencia' => $refLote,
                    'marca' => trim($det['marca']),
                    'modelo' => trim($det['modelo']),
                    'anio' => trim($det['anio'] ?? date('Y')),
                    'color' => trim($det['color'] ?? 'Sin Color'),
                    'cilindrada' => trim($det['cilindrada'] ?? '150cc'),
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUnitarioUsd,
                    'costo_unitario_bs' => $costoUnitarioBs,
                    'descuento_porcentaje' => $descPct,
                    'descuento_usd' => $descUsd,
                    'descuento_bs' => $descBs,
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPct,
                    'iva_monto_usd' => $ivaUnitarioUsd,
                    'iva_monto_bs' => $ivaUnitarioBs,
                    'margen_detal' => $margenDetalPct,
                    'precio_detal_usd' => $precioDetalUsd,
                    'precio_detal_bs' => $precioDetalBs,
                    'margen_mayorista' => $margenMayoristaPct,
                    'precio_mayorista_usd' => $precioMayoristaUsd,
                    'precio_mayorista_bs' => $precioMayoristaBs,
                    'subtotal_usd' => $renglonSubtotalUsd,
                    'subtotal_bs' => $renglonSubtotalBs,
                    'total_usd' => $renglonTotalUsd,
                    'total_bs' => $renglonTotalBs,
                    'seriales' => $det['seriales'],
                ];
            }

            $montoBrutoUsd = ! empty($datos['monto_bruto_usd']) ? (float) $datos['monto_bruto_usd'] : $subtotalGlobalUsd;
            $montoBrutoBs = round($montoBrutoUsd * $tasaCambio, 2);

            $descGlobalPct = ! empty($datos['descuento_global_porcentaje']) ? (float) $datos['descuento_global_porcentaje'] : 0;
            $descGlobalUsd = round($montoBrutoUsd * ($descGlobalPct / 100), 2);
            $descGlobalBs = round($descGlobalUsd * $tasaCambio, 2);

            $totalBs = round($totalGlobalUsd * $tasaCambio, 2);
            $subtotalBs = round($subtotalGlobalUsd * $tasaCambio, 2);
            $ivaBs = round($ivaGlobalUsd * $tasaCambio, 2);

            // Fechas y condición de pago
            $diasCredito = ($datos['condicion_pago'] === 'credito') ? (int) ($datos['dias_credito'] ?? 30) : 0;
            $fechaVencimiento = ($datos['condicion_pago'] === 'credito')
                ? Carbon::parse($datos['fecha_emision'])->addDays($diasCredito)->toDateString()
                : null;

            // 3. Crear cabecera RecepcionMoto
            $recepcionMoto = RecepcionMoto::create([
                'empresa_id' => $empresaId,
                'almacen_id' => $datos['almacen_id'],
                'proveedor_id' => $datos['proveedor_id'],
                'user_id' => $user->id,
                'codigo' => $this->generarCodigo($empresaId),
                'tipo_documento' => $datos['tipo_documento'] ?? 'factura',
                'numero_documento' => trim($datos['numero_documento']),
                'numero_control' => ! empty($datos['numero_control']) ? trim($datos['numero_control']) : null,
                'moneda_documento' => $monedaDoc,
                'tasa_cambio' => $tasaCambio,
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'condicion_pago' => $datos['condicion_pago'],
                'dias_credito' => $diasCredito,
                'fecha_vencimiento' => $fechaVencimiento,
                'monto_bruto_usd' => $montoBrutoUsd,
                'monto_bruto_bs' => $montoBrutoBs,
                'descuento_global_porcentaje' => $descGlobalPct,
                'descuento_global_usd' => $descGlobalUsd,
                'descuento_global_bs' => $descGlobalBs,
                'subtotal_usd' => $subtotalGlobalUsd,
                'subtotal_bs' => $subtotalBs,
                'iva_usd' => $ivaGlobalUsd,
                'iva_bs' => $ivaBs,
                'total_usd' => $totalGlobalUsd,
                'total_bs' => $totalBs,
                'total_unidades' => $totalUnidades,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'procesada',
            ]);

            // 4. Crear detalles y motos individuales
            foreach ($detallesCalculados as $det) {
                $seriales = $det['seriales'];
                unset($det['seriales']);

                $detalleModel = $recepcionMoto->detalles()->create($det);

                foreach ($seriales as $serial) {
                    $almacenMotoId = ! empty($serial['almacen_id']) ? (int) $serial['almacen_id'] : $det['almacen_id'];

                    Moto::create([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $almacenMotoId,
                        'proveedor_id' => $datos['proveedor_id'],
                        'recepcion_moto_id' => $recepcionMoto->id,
                        'recepcion_moto_detalle_id' => $detalleModel->id,
                        'referencia' => $det['referencia'],
                        'marca' => $det['marca'],
                        'modelo' => $det['modelo'],
                        'anio' => $det['anio'],
                        'color' => $det['color'],
                        'cilindrada' => $det['cilindrada'],
                        'numero_niv' => strtoupper(trim($serial['numero_niv'])),
                        'numero_chasis' => strtoupper(trim($serial['numero_chasis'])),
                        'numero_motor' => strtoupper(trim($serial['numero_motor'])),
                        'certificado_origen' => strtoupper(trim($serial['certificado_origen'])),
                        'placa' => ! empty($serial['placa']) ? strtoupper(trim($serial['placa'])) : null,
                        'precio_costo_usd' => $det['costo_unitario_usd'],
                        'precio_costo_bs' => $det['costo_unitario_bs'],
                        'margen_detal' => $det['margen_detal'],
                        'precio_detal_usd' => $det['precio_detal_usd'],
                        'precio_detal_bs' => $det['precio_detal_bs'],
                        'margen_mayorista' => $det['margen_mayorista'],
                        'precio_mayorista_usd' => $det['precio_mayorista_usd'],
                        'precio_mayorista_bs' => $det['precio_mayorista_bs'],
                        'estado' => 'disponible',
                        'observaciones' => "Ingresada en recepción {$recepcionMoto->codigo}",
                    ]);
                }
            }

            // 5. Si es a crédito, registrar en Cuentas por Pagar (CXP)
            if ($datos['condicion_pago'] === 'credito') {
                CuentaPorPagar::create([
                    'empresa_id' => $empresaId,
                    'proveedor_id' => $datos['proveedor_id'],
                    'recepcion_id' => null, // o vincular si se usa polimórfico
                    'numero_factura' => $datos['numero_documento'],
                    'fecha_emision' => $datos['fecha_emision'],
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total_usd' => $totalGlobalUsd,
                    'monto_total_bs' => $totalBs,
                    'monto_pagado_usd' => 0,
                    'monto_pagado_bs' => 0,
                    'saldo_pendiente_usd' => $totalGlobalUsd,
                    'saldo_pendiente_bs' => $totalBs,
                    'estado' => 'pendiente',
                    'observaciones' => "Factura de compra de motos {$recepcionMoto->codigo}",
                ]);
            }

            return $recepcionMoto;
        });
    }

    /**
     * Anular una recepción de motos
     */
    public function anular(int $id, int $empresaId): RecepcionMoto
    {
        return DB::transaction(function () use ($id, $empresaId) {
            $recepcion = RecepcionMoto::with('motos')
                ->where('empresa_id', $empresaId)
                ->findOrFail($id);

            if ($recepcion->estado === 'anulada') {
                throw new \Exception('Esta recepción ya se encuentra anulada.');
            }

            // Verificar si alguna moto ya fue vendida o reservada
            $motosVendidas = $recepcion->motos->where('estado', 'vendida');
            if ($motosVendidas->isNotEmpty()) {
                throw new \Exception("No se puede anular la recepción porque {$motosVendidas->count()} moto(s) ya han sido vendidas.");
            }

            // Marcar motos como anuladas
            foreach ($recepcion->motos as $moto) {
                $moto->estado = 'anulada';
                $moto->save();
            }

            $recepcion->estado = 'anulada';
            $recepcion->save();

            return $recepcion;
        });
    }
}
