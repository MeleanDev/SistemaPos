<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta {{ $venta->codigo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace, sans-serif;
        }
        body {
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .ticket {
            width: 80mm;
            background: #ffffff;
            padding: 15px 12px;
            font-size: 12px;
            line-height: 1.3;
            color: #000000;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }
        .double-divider {
            border-top: 2px dashed #000;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        th {
            border-bottom: 1px solid #000;
            padding: 4px 0;
            text-align: left;
        }
        td {
            padding: 4px 0;
            vertical-align: top;
        }
        .totales-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        .no-print {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .btn-print {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-close-window {
            background-color: #64748b;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
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
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div>
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Imprimir Ticket</button>
            <button class="btn-close-window" onclick="window.close()">✖️ Cerrar</button>
        </div>

        <div class="ticket">
            <!-- ENCABEZADO EMPRESA -->
            <div class="text-center">
                <h2 class="fw-bold" style="font-size: 16px; text-transform: uppercase;">{{ $venta->empresa->nombre }}</h2>
                @if($venta->empresa->razon_social && $venta->empresa->razon_social !== $venta->empresa->nombre)
                    <div>{{ $venta->empresa->razon_social }}</div>
                @endif
                <div>RIF: {{ $venta->empresa->rif }}</div>
                @if($venta->empresa->telefono)
                    <div>Tel: {{ $venta->empresa->telefono }}</div>
                @endif
                @if($venta->empresa->direccion)
                    <div style="font-size: 10px;">{{ $venta->empresa->direccion }}</div>
                @endif
            </div>

            <div class="double-divider"></div>

            <!-- DATOS DE LA VENTA -->
            <div>
                <div class="totales-row">
                    <span class="fw-bold">COMPROBANTE:</span>
                    <span class="fw-bold">{{ $venta->codigo }}</span>
                </div>
                <div class="totales-row">
                    <span>FECHA:</span>
                    <span>{{ \Carbon\Carbon::parse($venta->fecha_emision)->format('d/m/Y') }} {{ $venta->hora_emision }}</span>
                </div>
                <div class="totales-row">
                    <span>CAJERO:</span>
                    <span>{{ $venta->usuario?->name ?? 'Caja 1' }}</span>
                </div>
                <div class="totales-row">
                    <span>ALMACÉN:</span>
                    <span>{{ $venta->almacen?->nombre ?? 'Piso de Venta' }}</span>
                </div>
                <div class="totales-row">
                    <span>TIPO VENTA:</span>
                    <span class="fw-bold">{{ strtoupper($venta->tipo_venta) }}</span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- DATOS DEL CLIENTE -->
            <div>
                <div class="totales-row">
                    <span>CLIENTE:</span>
                    <span class="fw-bold">{{ $venta->cliente->nombre_completo }}</span>
                </div>
                <div class="totales-row">
                    <span>CÉDULA / RIF:</span>
                    <span>{{ $venta->cliente->cedula }}</span>
                </div>
                @if($venta->cliente->telefono)
                <div class="totales-row">
                    <span>TELÉFONO:</span>
                    <span>{{ $venta->cliente->telefono }}</span>
                </div>
                @endif
            </div>

            <div class="double-divider"></div>

            <!-- DETALLE DE PRODUCTOS -->
            <table>
                <thead>
                    <tr>
                        <th style="width: 50%;">DESCRIPCIÓN</th>
                        <th class="text-center" style="width: 15%;">CANT</th>
                        <th class="text-end" style="width: 17%;">P.UNIT</th>
                        <th class="text-end" style="width: 18%;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($venta->detalles as $det)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $det->producto?->nombre ?? 'Artículo' }}</div>
                                <div style="font-size: 9px; color: #444;">SKU: {{ $det->producto?->codigo_interno }} {{ $det->aplica_iva ? '(IVA 16%)' : '(E)' }}</div>
                            </td>
                            <td class="text-center">{{ (float) $det->cantidad }}</td>
                            <td class="text-end">${{ number_format($det->precio_unitario_usd, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format($det->subtotal_usd, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="double-divider"></div>

            <!-- TOTALES -->
            <div>
                <div class="totales-row">
                    <span>SUBTOTAL NETO:</span>
                    <span class="fw-bold">${{ number_format($venta->subtotal_neto_usd, 2) }}</span>
                </div>
                @if($venta->descuento_usd > 0)
                <div class="totales-row">
                    <span>DESCUENTO ({{ (float) $venta->descuento_porcentaje }}%):</span>
                    <span>-${{ number_format($venta->descuento_usd, 2) }}</span>
                </div>
                @endif
                <div class="totales-row">
                    <span>IVA (16%):</span>
                    <span>${{ number_format($venta->iva_monto_usd, 2) }}</span>
                </div>

                <div class="divider"></div>

                <div class="totales-row" style="font-size: 14px;">
                    <span class="fw-bold">TOTAL USD:</span>
                    <span class="fw-bold">${{ number_format($venta->total_usd, 2) }}</span>
                </div>
                <div class="totales-row" style="font-size: 13px;">
                    <span class="fw-bold">TOTAL BS:</span>
                    <span class="fw-bold">Bs. {{ number_format($venta->total_bs, 2) }}</span>
                </div>
                <div class="totales-row" style="font-size: 10px; color: #555;">
                    <span>TASA DE CAMBIO BCV:</span>
                    <span>Bs. {{ number_format($venta->tasa_cambio, 4) }}</span>
                </div>
            </div>

            <div class="divider"></div>

            <!-- FORMAS DE PAGO -->
            <div>
                <div class="fw-bold text-center" style="font-size: 11px; margin-bottom: 4px;">PAGOS RECIBIDOS</div>
                @foreach($venta->pagos as $pago)
                    <div class="totales-row" style="font-size: 10px;">
                        <span>{{ $pago->metodoPago?->nombre ?? 'Pago' }} ({{ $pago->moneda }}):</span>
                        <span>{{ $pago->moneda === 'VES' ? 'Bs. ' : '$ ' }}{{ number_format($pago->monto_origen, 2) }}</span>
                    </div>
                    @if($pago->referencia)
                        <div style="font-size: 9px; color: #555;">Ref: {{ $pago->referencia }}</div>
                    @endif
                @endforeach

                @if($venta->vuelto_usd > 0)
                    <div class="totales-row fw-bold" style="color: #047857; margin-top: 4px;">
                        <span>CAMBIO / VUELTO:</span>
                        <span>${{ number_format($venta->vuelto_usd, 2) }} (Bs. {{ number_format($venta->vuelto_bs, 2) }})</span>
                    </div>
                @endif

                @if($venta->saldo_pendiente_usd > 0)
                    <div class="totales-row fw-bold" style="color: #b91c1c; margin-top: 4px;">
                        <span>SALDO A CRÉDITO:</span>
                        <span>${{ number_format($venta->saldo_pendiente_usd, 2) }}</span>
                    </div>
                @endif
            </div>

            <div class="double-divider"></div>

            <!-- PIE DE PÁGINA -->
            <div class="text-center" style="font-size: 10px; margin-top: 8px;">
                <div class="fw-bold">¡GRACIAS POR SU PREFERENCIA!</div>
                <div>Comprobante de entrega y control interno</div>
                <div style="font-size: 9px; margin-top: 4px; color: #666;">Sistema POS Enterprise</div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            // Auto impresión opcional al cargar
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
