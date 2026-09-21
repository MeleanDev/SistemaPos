<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Pago Proveedor #{{ str_pad($abono->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background-color: #f0f0f0;
            padding: 15px;
            display: flex;
            justify-content: center;
        }

        .ticket {
            background: white;
            padding: 14px;
            width: 80mm;
            font-size: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #000;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }

        .header { margin-bottom: 8px; border-bottom: 1px dashed #000; padding-bottom: 8px; text-align: center; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }

        .totals { margin-top: 8px; border-top: 1px dashed #000; padding-top: 5px; }
        .total-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 2px; }

        .footer { margin-top: 12px; text-align: center; font-size: 10px; border-top: 1px dashed #000; padding-top: 6px; }

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
                padding: 0 10px;
            }
            @page {
                margin: 5mm;
                size: auto;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="ticket">
        <div class="header">
            <div class="bold" style="font-size: 14px;">{{ $empresa->nombre_comercial ?? $empresa->razon_social }}</div>
            <div>RIF: {{ $empresa->rif }}</div>
            @if($empresa->direccion)<div>{{ $empresa->direccion }}</div>@endif
            @if($empresa->telefono)<div>Tel: {{ $empresa->telefono }}</div>@endif
            <div class="bold" style="margin-top: 5px; font-size: 13px;">EGRESO / PAGO A PROVEEDOR</div>
            <div style="font-size: 11px;">COMPROBANTE N° #{{ str_pad($abono->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>

        <div class="info">
            <div class="info-row">
                <span>FECHA:</span>
                <span class="bold">{{ \Carbon\Carbon::parse($abono->fecha_abono)->format('d/m/Y') }}</span>
            </div>
            <div class="info-row">
                <span>HORA:</span>
                <span>{{ $abono->created_at->format('h:i A') }}</span>
            </div>
            <div class="info-row">
                <span>RESPONSABLE:</span>
                <span>{{ $abono->usuario?->name ?? 'Sistema' }}</span>
            </div>
            <div class="divider"></div>
            <div class="info-row">
                <span>PROVEEDOR:</span>
                <span class="bold">{{ $proveedor->nombre }}</span>
            </div>
            <div class="info-row">
                <span>RIF:</span>
                <span class="bold">{{ $proveedor->rif }}</span>
            </div>
            @if($proveedor->telefono)
            <div class="info-row">
                <span>TELÉFONO:</span>
                <span>{{ $proveedor->telefono }}</span>
            </div>
            @endif
        </div>

        <div class="divider"></div>

        <div class="info">
            <div class="info-row">
                <span>FACTURA COMPRA:</span>
                <span class="bold">{{ $cuenta->numero_factura }}</span>
            </div>
            <div class="info-row">
                <span>TOTAL FACTURA:</span>
                <span>${{ number_format($cuenta->monto_total_usd, 2) }}</span>
            </div>
            <div class="info-row">
                <span>MÉTODO DE PAGO:</span>
                <span class="bold">{{ $abono->metodoPago?->nombre ?? 'N/A' }}</span>
            </div>
            @if($abono->referencia)
            <div class="info-row">
                <span>REFERENCIA:</span>
                <span>{{ $abono->referencia }}</span>
            </div>
            @endif
            @if($abono->observaciones)
            <div class="info-row">
                <span>OBSERVACIÓN:</span>
                <span>{{ $abono->observaciones }}</span>
            </div>
            @endif
        </div>

        <div class="totals">
            <div class="total-row bold" style="font-size: 14px;">
                <span>MONTO PAGADO ($):</span>
                <span>${{ number_format($abono->monto_usd, 2) }}</span>
            </div>
            <div class="total-row bold" style="font-size: 13px;">
                <span>MONTO EN BS:</span>
                <span>Bs. {{ number_format($abono->monto_bs, 2) }}</span>
            </div>
            <div style="font-size: 10px; text-align: right; margin-bottom: 4px;">
                (Tasa BCV: Bs. {{ number_format($abono->tasa_cambio, 2) }}/$)
            </div>

            <div class="divider"></div>

            <div class="total-row">
                <span>SALDO RESTANTE FACTURA:</span>
                <span class="bold">${{ number_format($cuenta->saldo_pendiente_usd, 2) }}</span>
            </div>
            <div class="total-row bold" style="margin-top: 4px; color: #1e3a8a;">
                <span>DEUDA GLOBAL PROVEEDOR:</span>
                <span>${{ number_format($deuda_total_proveedor_usd, 2) }}</span>
            </div>
            <div style="font-size: 10px; text-align: right;">
                (Equivalente: Bs. {{ number_format($deuda_total_proveedor_bs, 2) }})
            </div>
        </div>

        <div class="footer">
            <p class="bold">COMPROBANTE DE EGRESO A PROVEEDOR</p>
            <p>REGISTRO CONTABLE DE FINANZAS</p>
        </div>
    </div>
</body>
</html>
