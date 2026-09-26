<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Factura {{ $venta->codigo }} - {{ $venta->empresa->nombre }}</title>
    <style>
        @page {
            margin: 0;
            size: auto;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            background-color: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 10px;
        }
        .ticket {
            width: 80mm;
            background: #ffffff;
            padding: 12px 10px;
            font-size: 11px;
            line-height: 1.25;
            color: #000000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        th {
            border-bottom: 1px solid #000;
            padding: 3px 0;
            text-align: left;
            font-size: 10px;
        }
        td {
            padding: 3px 0;
            vertical-align: top;
        }

        .totales-row {
            display: flex;
            justify-content: space-between;
            padding: 1.5px 0;
            font-size: 10.5px;
        }

        .no-print {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-print {
            background-color: #059669;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-carta {
            background-color: #1e40af;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-close-window {
            background-color: #64748b;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .ticket {
                width: 100%;
                box-shadow: none;
                padding: 4px 6px;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

@php
    $montoExentoUsd = 0;
    $montoExentoBs = 0;
    $baseImponibleUsd = 0;
    $baseImponibleBs = 0;

    foreach ($venta->detalles as $det) {
        $aplicaIva = (bool) $det->aplica_iva;
        $ivaPct = (float) $det->iva_porcentaje;

        if ($aplicaIva && $ivaPct > 0) {
            $baseImponibleUsd += (float) $det->subtotal_usd;
            $baseImponibleBs += (float) $det->subtotal_bs;
        } else {
            $montoExentoUsd += (float) $det->subtotal_usd;
            $montoExentoBs += (float) $det->subtotal_bs;
        }
    }

    $subtotalUsd = (float) $venta->subtotal_neto_usd;
    $subtotalBs = (float) $venta->subtotal_neto_bs;
    $ivaUsd = (float) $venta->iva_monto_usd;
    $ivaBs = (float) $venta->iva_monto_bs;
    $totalNetoUsd = $subtotalUsd + $ivaUsd;
    $totalNetoBs = $subtotalBs + $ivaBs;

    $baseIgtfUsd = (float) ($venta->igtf_monto_usd > 0 ? ($venta->igtf_monto_usd / 0.03) : 0);
    $baseIgtfBs = (float) ($venta->igtf_monto_bs > 0 ? ($venta->igtf_monto_bs / 0.03) : 0);
    $igtfUsd = (float) ($venta->igtf_monto_usd ?? 0);
    $igtfBs = (float) ($venta->igtf_monto_bs ?? 0);

    $totalPagarUsd = (float) $venta->total_usd;
    $totalPagarBs = (float) $venta->total_bs;

    $diasCredito = $venta->condicion_pago === 'credito' ? 15 : 0;
    $fechaEmision = \Carbon\Carbon::parse($venta->fecha_emision);
    $fechaVencimiento = $fechaEmision->copy()->addDays($diasCredito);

    $tieneVehiculo = $venta->detalles->contains(fn($d) => $d->tipo_item === 'moto');
@endphp

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket</button>
        <a href="{{ route('pos.imprimir_carta', $venta->id) }}" class="btn-carta">📄 Ver Factura Carta</a>
        <button class="btn-close-window" onclick="window.close()">✖️ Cerrar</button>
    </div>

    <div class="ticket">
        <!-- ENCABEZADO EMPRESA -->
        <div class="text-center">
            <h3 class="fw-bold text-uppercase" style="font-size: 14px;">{{ $venta->empresa->nombre }}</h3>
            @if($venta->empresa->razon_social && $venta->empresa->razon_social !== $venta->empresa->nombre)
                <div style="font-size: 10px;">{{ $venta->empresa->razon_social }}</div>
            @endif
            <div class="fw-bold">RIF: {{ $venta->empresa->rif }}</div>
            @if($venta->empresa->telefono)
                <div>Tel: {{ $venta->empresa->telefono }}</div>
            @endif
            @if($venta->empresa->direccion)
                <div style="font-size: 9.5px; margin-top: 1px;">{{ $venta->empresa->direccion }}</div>
            @endif
        </div>

        <div class="double-divider"></div>

        <!-- DATOS DEL DOCUMENTO -->
        <div>
            <div class="totales-row">
                <span class="fw-bold">FACTURA NRO:</span>
                <span class="fw-bold">{{ $venta->codigo }}</span>
            </div>
            <div class="totales-row">
                <span>N° CONTROL:</span>
                <span class="fw-bold">00-{{ str_pad(preg_replace('/^00-/', '', (string) ($venta->numero_control ?? $venta->id)), 8, '0', STR_PAD_LEFT) }}</span>
            </div>
            <div class="totales-row">
                <span>CONDICIÓN:</span>
                <span class="fw-bold text-uppercase">{{ $venta->condicion_pago === 'credito' ? 'CRÉDITO' : 'CONTADO' }}</span>
            </div>
            <div class="totales-row">
                <span>EMISIÓN:</span>
                <span>{{ $fechaEmision->format('d/m/Y') }} {{ $venta->hora_emision }}</span>
            </div>
            <div class="totales-row">
                <span>VENCIMIENTO:</span>
                <span>{{ $fechaVencimiento->format('d/m/Y') }}</span>
            </div>
            <div class="totales-row">
                <span>ASESOR / CAJERO:</span>
                <span>{{ $venta->usuario?->name ?? 'Caja' }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- DATOS DEL CLIENTE -->
        <div>
            <div class="totales-row">
                <span>CLIENTE:</span>
                <span class="fw-bold text-uppercase" style="max-width: 60%; text-align: right;">{{ $venta->cliente->nombre_completo }}</span>
            </div>
            <div class="totales-row">
                <span>CI / RIF:</span>
                <span class="fw-bold">{{ $venta->cliente->cedula ?? 'V-00000000' }}</span>
            </div>
            @if($venta->cliente->telefono)
            <div class="totales-row">
                <span>TELÉFONO:</span>
                <span>{{ $venta->cliente->telefono }}</span>
            </div>
            @endif
            @if($venta->cliente->direccion)
            <div style="font-size: 9.5px; margin-top: 1px;">
                <span>DIR: {{ $venta->cliente->direccion }}</span>
            </div>
            @endif
        </div>

        <div class="double-divider"></div>

        <!-- DETALLE DE ARTÍCULOS -->
        <table>
            <thead>
                <tr>
                    <th style="width: 48%;">DESCRIPCIÓN</th>
                    <th class="text-center" style="width: 14%;">CANT</th>
                    <th class="text-end" style="width: 18%;">P.UNIT</th>
                    <th class="text-end" style="width: 20%;">TOTAL BS</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $det)
                    @php
                        $nombreItem = $det->nombre_item ?? $det->producto?->nombre ?? ($det->moto ? "{$det->moto->marca} {$det->moto->modelo}" : null) ?? $det->servicio?->nombre ?? 'Artículo';
                        $cant = (float) $det->cantidad;
                        $precioUnitBs = (float) $det->precio_unitario_bs;
                        $totalRenglonBs = (float) $det->subtotal_bs;
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-bold text-uppercase">{{ $nombreItem }}</div>
                            <div style="font-size: 9px; color: #333;">
                                @if($det->tipo_item === 'moto' && $det->moto)
                                    NIV: {{ $det->serial_identificador ?? $det->moto->numero_niv }}
                                    @if($det->moto->numero_motor)<br>Mot: {{ $det->moto->numero_motor }}@endif
                                    @if($det->moto->color)<br>Col: {{ $det->moto->color }}@endif
                                @else
                                    {{ $det->aplica_iva ? '(IVA ' . (float)$det->iva_porcentaje . '%)' : '(EXENTO)' }}
                                @endif
                            </div>
                        </td>
                        <td class="text-center">{{ number_format($cant, 1) }}</td>
                        <td class="text-end">${{ number_format($det->precio_unitario_usd, 2) }}</td>
                        <td class="text-end fw-bold">{{ number_format($totalRenglonBs, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="double-divider"></div>

        <!-- TOTALES DUAL CURRENCY (USD Y BS) -->
        <div>
            <div class="totales-row">
                <span>SUBTOTAL USD:</span>
                <span>${{ number_format($subtotalUsd, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>SUBTOTAL BS:</span>
                <span>Bs. {{ number_format($subtotalBs, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>MONTO EXENTO USD:</span>
                <span>${{ number_format($montoExentoUsd, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>BASE IMPONIBLE (16%):</span>
                <span>${{ number_format($baseImponibleUsd, 2) }}</span>
            </div>
            <div class="totales-row">
                <span>IVA (16,00%):</span>
                <span>${{ number_format($ivaUsd, 2) }} (Bs. {{ number_format($ivaBs, 2) }})</span>
            </div>

            @if($igtfUsd > 0)
            <div class="totales-row">
                <span>IGTF (3%):</span>
                <span>${{ number_format($igtfUsd, 2) }}</span>
            </div>
            @endif

            <div class="divider"></div>

            <div class="totales-row" style="font-size: 13px;">
                <span class="fw-bold">TOTAL USD:</span>
                <span class="fw-bold">${{ number_format($totalPagarUsd, 2) }}</span>
            </div>
            <div class="totales-row" style="font-size: 12.5px;">
                <span class="fw-bold">TOTAL BS:</span>
                <span class="fw-bold">Bs. {{ number_format($totalPagarBs, 2) }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- TASA BCV & PAGOS -->
        <div style="font-size: 9.5px;">
            <div class="totales-row">
                <span>TASA BCV:</span>
                <span class="fw-bold">Bs. {{ number_format($venta->tasa_cambio, 4) }}</span>
            </div>
            @if($venta->pagos && $venta->pagos->count() > 0)
                <div style="margin-top: 3px; font-weight: bold;">FORMAS DE PAGO:</div>
                @foreach($venta->pagos as $p)
                    <div class="totales-row" style="padding-left: 4px;">
                        <span>• {{ $p->metodoPago?->nombre ?? 'Pago' }}:</span>
                        <span>{{ $p->moneda === 'VES' ? 'Bs. ' . number_format($p->monto, 2) : '$ ' . number_format($p->monto, 2) }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="double-divider"></div>

        <!-- MENSAJE SENIAT / PIE -->
        <div class="text-center" style="font-size: 8.5px; line-height: 1.2;">
            <div>A los efectos previstos en el art. 13 de la PA-0071 SNAT\2011\0071 montos expresados en Bs. al cambio BCV.</div>
            <div class="fw-bold" style="margin-top: 4px;">ESTA FACTURA VA SIN TACHADURAS NI ENMENDADURAS</div>
            <div style="margin-top: 6px;">¡Gracias por su compra!</div>
        </div>
    </div>

</body>
</html>
