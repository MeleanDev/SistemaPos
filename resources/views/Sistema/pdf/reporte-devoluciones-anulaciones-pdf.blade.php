<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Devoluciones y Anulaciones</title>
    <style>
        @page {
            margin: 20px 25px 30px 25px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .empresa-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .empresa-info {
            font-size: 8.5px;
            color: #64748b;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .report-subtitle {
            font-size: 9px;
            color: #475569;
            text-align: right;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 12px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
        }
        .kpi-label {
            font-size: 7.5px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .kpi-val-usd {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .kpi-val-sub {
            font-size: 8px;
            color: #64748b;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 6px;
            text-align: left;
        }
        .data-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 8.5px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }
        .footer-note {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="empresa-title">{{ $empresa->nombre_comercial }}</div>
                <div class="empresa-info">
                    <strong>Razón Social:</strong> {{ $empresa->razon_social }} | <strong>RIF:</strong> {{ $empresa->rif }}<br>
                    <strong>Dirección:</strong> {{ $empresa->direccion }}
                </div>
            </td>
            <td style="width: 45%;" class="text-end">
                <div class="report-title">AUDITORÍA DE DEVOLUCIONES Y ANULACIONES</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $data['kpis']['periodo_texto'] }}<br>
                    <strong>Tasa Oficial:</strong> {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$ | <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="kpi-container" cellspacing="4">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">Total Devoluciones</div>
                    <div class="kpi-val-usd text-warning">${{ number_format($data['kpis']['total_devoluciones_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">{{ $data['kpis']['conteo_devoluciones'] }} devoluciones</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #dc2626;">
                    <div class="kpi-label">Total Anulaciones</div>
                    <div class="kpi-val-usd text-danger">${{ number_format($data['kpis']['total_anuladas_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">{{ $data['kpis']['conteo_anulaciones'] }} facturas anuladas</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Revertido ($ USD)</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_general_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">{{ $data['kpis']['conteo_total'] }} eventos totales</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Revertido (Bs. VES)</div>
                    <div class="kpi-val-usd">Bs. {{ number_format($data['kpis']['total_general_bs'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">Tasa: {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 9%;">Tipo</th>
                <th style="width: 10%;">Comprobante</th>
                <th style="width: 10%;">Factura Orig.</th>
                <th style="width: 11%;">Fecha / Hora</th>
                <th style="width: 12%;">Usuario</th>
                <th style="width: 14%;">Cliente</th>
                <th style="width: 18%;">Motivo</th>
                <th style="width: 8%;">Reembolso</th>
                <th class="text-end" style="width: 8%;">Monto ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] as $item)
                <tr>
                    <td class="text-center fw-bold {{ $item['tipo'] === 'Anulación' ? 'text-danger' : 'text-warning' }}">
                        {{ $item['tipo'] }}
                    </td>
                    <td class="fw-bold">{{ $item['codigo'] }}</td>
                    <td>{{ $item['factura_codigo'] }}</td>
                    <td>{{ $item['fecha'] }} {{ $item['hora'] }}</td>
                    <td>{{ $item['usuario'] }}</td>
                    <td>{{ $item['cliente'] }}</td>
                    <td>{{ $item['motivo'] }}</td>
                    <td>{{ $item['metodo_reembolso'] }}</td>
                    <td class="text-end fw-bold text-danger">-${{ number_format($item['monto_usd'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center" style="color: #94a3b8; padding: 15px;">No se registraron devoluciones ni anulaciones en este período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Centro Oficial de Reportería • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
