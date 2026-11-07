<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Flujo Real de Caja</title>
    <style>
        @page {
            margin: 25px 30px 40px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .empresa-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .empresa-info {
            font-size: 9px;
            color: #64748b;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .report-subtitle {
            font-size: 10px;
            color: #475569;
            text-align: right;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 15px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 10px;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
        }
        .kpi-val-usd {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .kpi-val-bs {
            font-size: 9px;
            color: #64748b;
        }
        .section-heading {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 6px 8px;
            text-align: left;
        }
        .data-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .footer-note {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #94a3b8;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
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
                <div class="report-title">REPORTE DE FLUJO REAL DE CAJA</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $data['kpis']['periodo_texto'] }}<br>
                    <strong>Tasa Oficial:</strong> {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$ | <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="kpi-container" cellspacing="6">
        <tr>
            <td style="width: 33%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Total Entradas (Ventas + Abonos)</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['total_entradas_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_entradas_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="kpi-box" style="border-left: 3px solid #dc2626;">
                    <div class="kpi-label">Total Salidas (Vueltos + Reembolsos)</div>
                    <div class="kpi-val-usd text-danger">${{ number_format($data['kpis']['total_salidas_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_salidas_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 34%;">
                <div class="kpi-box" style="border-left: 3px solid {{ $data['kpis']['flujo_neto_usd'] >= 0 ? '#059669' : '#dc2626' }};">
                    <div class="kpi-label">Flujo Neto Real</div>
                    <div class="kpi-val-usd {{ $data['kpis']['flujo_neto_usd'] >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($data['kpis']['flujo_neto_usd'], 2, ',', '.') }}
                    </div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['flujo_neto_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-heading">1. Desglose de Flujo por Método de Pago</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Método de Pago</th>
                <th class="text-end" style="width: 17%;">Entradas ($)</th>
                <th class="text-end" style="width: 17%;">Entradas (Bs.)</th>
                <th class="text-end" style="width: 17%;">Salidas ($)</th>
                <th class="text-end" style="width: 19%;">Flujo Neto ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['desglose_metodos'] as $m)
                <tr>
                    <td class="fw-bold">{{ $m['nombre'] }}</td>
                    <td class="text-end text-success">${{ number_format($m['entradas_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">Bs. {{ number_format($m['entradas_bs'], 2, ',', '.') }}</td>
                    <td class="text-end text-danger">${{ number_format($m['salidas_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold {{ $m['neto_usd'] >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($m['neto_usd'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center" style="color: #94a3b8; padding: 15px;">Sin movimientos para los filtros seleccionados</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-heading">2. Movimientos Cronológicos de Caja</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Fecha</th>
                <th class="text-center" style="width: 15%;">Tipo</th>
                <th style="width: 35%;">Concepto / Documento</th>
                <th style="width: 20%;">Método</th>
                <th class="text-end" style="width: 15%;">Monto ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['transacciones'] as $t)
                <tr>
                    <td>{{ $t['fecha'] }}</td>
                    <td class="text-center fw-bold {{ $t['tipo_flujo'] === 'Entrada' ? 'text-success' : 'text-danger' }}">
                        {{ $t['tipo_flujo'] }}
                    </td>
                    <td>{{ $t['concepto'] }}</td>
                    <td>{{ $t['metodo'] }}</td>
                    <td class="text-end fw-bold {{ $t['monto_usd'] >= 0 ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($t['monto_usd'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center" style="color: #94a3b8; padding: 15px;">No hay transacciones registradas</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Centro Oficial de Reportería • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
