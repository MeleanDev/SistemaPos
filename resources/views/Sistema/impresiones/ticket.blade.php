<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $factura->correlativo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background-color: #f0f0f0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .ticket {
            background: white;
            padding: 12px 14px;
            width: {{ $formato === 'ticket_80mm' ? '80mm' : ($formato === 'ticket_58mm' ? '58mm' : '80mm') }};
            font-size: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }

        .header { margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
        .logo { max-width: 100px; margin-bottom: 5px; }

        .info-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }

        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { text-align: left; padding: 3px 0; }
        th.right, td.right { text-align: right; }

        .totals { margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; }
        .total-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 3px; }

        .footer { margin-top: 15px; text-align: center; font-size: 10px; }

        @media print {
            body {
                background: none;
                padding: 0;
                display: block;
            }
            .ticket {
                width: 100%;
                box-shadow: none;
                margin: 0 auto;
                /* Margen lateral seguro de 15px para que la impresora física NO corte el texto a los lados */
                padding: 0 15px;
            }
            @page {
                /* Margen de página seguro en hoja blanca */
                margin: 12mm 16mm;
                size: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">
        <div class="header text-center">
            @if($factura->empresa->logo)
                @php $disk = config('filesystems.disks.r2') ? 'r2' : 'public'; @endphp
                <img src="{{ Storage::disk($disk)->url($factura->empresa->logo) }}" class="logo" alt="Logo">
            @endif
            <div class="bold" style="font-size: 13px;">{{ $factura->empresa->nombre }}</div>
            <div>RIF: {{ $factura->empresa->rif }}</div>
            <div>{{ $factura->empresa->direccion }}</div>
            <div>Tel: {{ $factura->empresa->telefonoUno }}</div>
        </div>

        <div class="info">
            <div class="info-row">
                <span>TICKET:</span>
                <span class="bold" style="font-size: 15px;">{{ $factura->correlativo }}</span>
            </div>
            <div class="info-row">
                <span>CONTROL:</span>
                <span class="bold" style="font-size: 15px;">{{ $factura->numero_control_formateado }}</span>
            </div>
            @if($factura->orden && $factura->orden->codigo)
            <div class="info-row">
                <span class="bold">N° ORDEN:</span>
                <span class="bold" style="font-size: 16px; letter-spacing: 0.5px;">{{ $factura->orden->codigo }}</span>
            </div>
            @endif
            <div class="info-row">
                <span>FECHA:</span>
                <span class="bold" style="font-size: 13px;">{{ $factura->created_at->format('d/m/Y h:i A') }}</span>
            </div>
            <div class="divider"></div>
            <div class="info-row">
                <span>CLIENTE:</span>
                <span class="bold">{{ $factura->paciente->nombreUno }} {{ $factura->paciente->apellidoUno }}</span>
            </div>
            <div class="info-row">
                <span>CI/RIF:</span>
                <span class="bold">{{ $factura->paciente->cedula ?? 'N/A' }}</span>
            </div>
        </div>

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th>CANT</th>
                    <th>DESCRIPCION</th>
                    <th class="right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->orden->detalles as $detalle)
                <tr>
                    <td>1</td>
                    <td>
                        @if($detalle->perfil_id && $detalle->perfil)
                            {{ $detalle->perfil->nombre }}
                        @elseif($detalle->examen_id && $detalle->examen)
                            {{ $detalle->examen->nombre }}
                        @elseif($detalle->inventario_id && $detalle->inventario)
                            {{ $detalle->inventario->nombre }}
                        @else
                            Ítem médico / Servicio
                        @endif
                    </td>
                    <td class="right">${{ number_format($detalle->precio_registrado_usd, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="total-row">
                <span>SUBTOTAL:</span>
                <span>${{ number_format($factura->subtotal_exento_usd + $factura->base_imponible_usd, 2) }}</span>
            </div>

            <div class="total-row bold" style="font-size: 16px;">
                <span>TOTAL USD:</span>
                <span>${{ number_format($factura->total_usd, 2) }}</span>
            </div>
            <div class="total-row bold" style="font-size: 16px;">
                <span>TOTAL BS:</span>
                <span>Bs.{{ number_format($factura->total_bs, 2) }}</span>
            </div>
            <div style="font-size: 10px; text-align: right;">(Tasa: Bs.{{ number_format($factura->tasa_cambio, 2) }})</div>
        </div>

        @if($factura->pagos->count() > 0)
        <div class="divider"></div>
        <div style="margin: 5px 0;" class="bold text-center">HISTORIAL DE PAGOS</div>
        <table>
            <thead>
                <tr>
                    <th>MÉTODO</th>
                    <th class="right">MONTO USD</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->pagos as $pago)
                <tr>
                    <td>{{ $pago->metodo_pago }}<br><span style="font-size: 10px;">{{ $pago->referencia ?? '' }}</span></td>
                    <td class="right">${{ number_format($pago->monto_usd, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="footer">
            <p>GRACIAS POR SU VISITA</p>
            <p>ESTE DOCUMENTO NO ES FACTURA FISCAL</p>
        </div>
    </div>
</body>
</html>
