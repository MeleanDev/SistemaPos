<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte X - Caja {{ $reporte['caja']->nombre }} - {{ $reporte['empresa']->nombre }}</title>
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
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 7px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-close {
            background: #e2e8f0;
            color: #334155;
            border: none;
            padding: 7px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .ticket {
                width: 100%;
                box-shadow: none;
                border-radius: 0;
                padding: 4px;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">
            🖨️ Imprimir Corte X
        </button>
        <button class="btn-close" onclick="window.close()">
            Cerrar
        </button>
    </div>

    <div class="ticket">
        <div class="text-center">
            <h3 class="fw-bold text-uppercase">{{ $reporte['empresa']->nombre }}</h3>
            @if($reporte['empresa']->razon_social && $reporte['empresa']->razon_social !== $reporte['empresa']->nombre)
                <p>{{ $reporte['empresa']->razon_social }}</p>
            @endif
            <p>RIF: {{ $reporte['empresa']->rif }}</p>
            <p style="font-size: 9.5px;">{{ $reporte['empresa']->direccion }}</p>
            @if($reporte['empresa']->telefono)
                <p style="font-size: 9.5px;">Tel: {{ $reporte['empresa']->telefono }}</p>
            @endif
        </div>

        <div class="double-divider"></div>

        <div class="text-center">
            <h4 class="fw-bold">*** REPORTE CORTE X ***</h4>
            <p style="font-size: 9.5px; font-style: italic;">(Corte Parcial Informativo / Auditoría)</p>
        </div>

        <div class="divider"></div>

        <div class="totales-row">
            <span><strong>CAJA:</strong></span>
            <span>{{ $reporte['caja']->nombre }}</span>
        </div>
        <div class="totales-row">
            <span><strong>TURNO ID:</strong></span>
            <span>#{{ str_pad($reporte['turno']->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="totales-row">
            <span><strong>CAJERO:</strong></span>
            <span>{{ $reporte['usuario']->name ?? $reporte['usuario']->nombre_completo }}</span>
        </div>
        <div class="totales-row">
            <span><strong>APERTURA:</strong></span>
            <span>{{ \Carbon\Carbon::parse($reporte['turno']->fecha_apertura)->format('d/m/Y') }} {{ $reporte['turno']->hora_apertura }}</span>
        </div>
        <div class="totales-row">
            <span><strong>HORA CORTE:</strong></span>
            <span>{{ $reporte['fecha_impresion'] }}</span>
        </div>

        <div class="divider"></div>

        <div class="fw-bold text-uppercase" style="margin-bottom: 3px;">1. FONDO INICIAL (APERTURA)</div>
        <div class="totales-row">
            <span>Monto Inicial USD:</span>
            <span>${{ number_format($reporte['monto_apertura_usd'], 2) }}</span>
        </div>
        <div class="totales-row">
            <span>Monto Inicial Bs.:</span>
            <span>Bs. {{ number_format($reporte['monto_apertura_bs'], 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="fw-bold text-uppercase" style="margin-bottom: 3px;">2. TOTALES TRANSACCIONALES</div>
        <div class="totales-row">
            <span>Cant. Facturas Emitidas:</span>
            <span>{{ $reporte['cantidad_ventas'] }}</span>
        </div>
        <div class="totales-row">
            <span>Total Ventas USD:</span>
            <span class="fw-bold">${{ number_format($reporte['total_ventas_usd'], 2) }}</span>
        </div>
        <div class="totales-row">
            <span>Total Ventas Bs.:</span>
            <span class="fw-bold">Bs. {{ number_format($reporte['total_ventas_bs'], 2) }}</span>
        </div>
        <div class="totales-row">
            <span>Cant. Devoluciones:</span>
            <span>{{ $reporte['cantidad_devoluciones'] }}</span>
        </div>
        <div class="totales-row">
            <span>Total Devoluciones USD:</span>
            <span class="text-danger">-${{ number_format($reporte['total_devoluciones_usd'], 2) }}</span>
        </div>
        <div class="totales-row">
            <span>Total Devoluciones Bs.:</span>
            <span class="text-danger">-Bs. {{ number_format($reporte['total_devoluciones_bs'], 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="fw-bold text-uppercase" style="margin-bottom: 3px;">3. DESGLOSE POR FORMA DE PAGO</div>
        <table>
            <thead>
                <tr>
                    <th>MÉTODO / MONEDA</th>
                    <th class="text-center">CNT</th>
                    <th class="text-end">TOTAL ORIGEN</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reporte['pagos_por_metodo'] as $pm)
                    <tr>
                        <td>{{ $pm['metodo'] }}</td>
                        <td class="text-center">{{ $pm['conteo'] }}</td>
                        <td class="text-end fw-bold">
                            {{ $pm['moneda'] === 'USD' ? '$' : 'Bs.' }} {{ number_format($pm['total_origen'], 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted" style="padding: 6px 0;">Sin pagos registrados</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="fw-bold text-uppercase" style="margin-bottom: 3px;">4. EFECTIVO TEÓRICO EN CAJA</div>
        <div class="totales-row">
            <span>Efectivo USD Esperado:</span>
            <span class="fw-bold" style="font-size: 12px;">${{ number_format($reporte['efectivo_esperado_usd'], 2) }}</span>
        </div>
        <div class="totales-row">
            <span>Efectivo Bs. Esperado:</span>
            <span class="fw-bold" style="font-size: 12px;">Bs. {{ number_format($reporte['efectivo_esperado_bs'], 2) }}</span>
        </div>

        <div class="double-divider"></div>

        <div class="text-center" style="font-size: 9px; margin-top: 5px;">
            <p>*** CORTE X NO CIERRA EL TURNO ***</p>
            <p>Software POS - {{ config('app.name', 'Sistema') }}</p>
        </div>
    </div>

</body>
</html>
