<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cuadre Diario de Cajas y Turnos</title>
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
            font-size: 12px;
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
            padding: 4px 6px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 8.5px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #059669; }
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
                <div class="report-title">CUADRE DIARIO DE CAJAS & TURNOS</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $data['kpis']['periodo_texto'] }}<br>
                    <strong>Tasa Oficial:</strong> {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$ | <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="kpi-container" cellspacing="4">
        <tr>
            <td style="width: 20%;">
                <div class="kpi-box">
                    <div class="kpi-label">Ventas Brutas</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_ventas_bruto_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">Dev: -${{ number_format($data['kpis']['total_devoluciones_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Venta Neta</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['total_ventas_neto_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">Turnos: {{ $data['kpis']['total_turnos'] }}</div>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="kpi-box">
                    <div class="kpi-label">Efectivo Esperado</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_esperado_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">Abonos: ${{ number_format($data['kpis']['total_abonos_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="kpi-box">
                    <div class="kpi-label">Efectivo Contado</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_contado_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-sub">Vueltos: ${{ number_format($data['kpis']['total_vueltos_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 20%;">
                <div class="kpi-box" style="border-left: 3px solid {{ $data['kpis']['total_diferencia_usd'] < 0 ? '#dc2626' : ($data['kpis']['total_diferencia_usd'] > 0 ? '#059669' : '#64748b') }};">
                    <div class="kpi-label">Diferencia Neta</div>
                    <div class="kpi-val-usd {{ $data['kpis']['total_diferencia_usd'] < 0 ? 'text-danger' : ($data['kpis']['total_diferencia_usd'] > 0 ? 'text-success' : '') }}">
                        ${{ number_format($data['kpis']['total_diferencia_usd'], 2, ',', '.') }}
                    </div>
                    <div class="kpi-val-sub">{{ $data['kpis']['total_diferencia_usd'] < 0 ? 'Faltante' : ($data['kpis']['total_diferencia_usd'] > 0 ? 'Sobrante' : 'Exacto') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th>Turno</th>
                <th>Caja / Cajero</th>
                <th>Apertura / Cierre</th>
                <th class="text-center">Estado</th>
                <th class="text-end">Apertura ($)</th>
                <th class="text-end">Ventas Brutas ($)</th>
                <th class="text-end">Devolución ($)</th>
                <th class="text-end">Venta Neta ($)</th>
                <th class="text-end">Abonos CXC ($)</th>
                <th class="text-end">Vueltos ($)</th>
                <th class="text-end">Esperado ($)</th>
                <th class="text-end">Contado ($)</th>
                <th class="text-end">Diferencia ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['turnos'] as $t)
                <tr>
                    <td class="fw-bold">#{{ $t['turno_id'] }}</td>
                    <td>
                        <span class="fw-bold">{{ $t['caja_nombre'] }}</span><br>
                        <span style="font-size: 7.5px; color: #64748b;">{{ $t['cajero_nombre'] }}</span>
                    </td>
                    <td>
                        {{ $t['fecha_apertura'] }} {{ $t['hora_apertura'] }}<br>
                        <span style="font-size: 7.5px; color: #64748b;">{{ $t['fecha_cierre'] }} {{ $t['hora_cierre'] }}</span>
                    </td>
                    <td class="text-center">
                        <strong class="{{ $t['estado'] === 'cerrada' ? 'text-success' : 'text-warning' }}">{{ strtoupper($t['estado']) }}</strong>
                    </td>
                    <td class="text-end">${{ number_format($t['monto_apertura_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($t['ventas_bruto_usd'], 2, ',', '.') }}</td>
                    <td class="text-end text-danger">-${{ number_format($t['devoluciones_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">${{ number_format($t['ventas_neto_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($t['abonos_cxc_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">-${{ number_format($t['vueltos_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold">${{ number_format($t['esperado_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($t['contado_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold {{ $t['diferencia_usd'] < 0 ? 'text-danger' : ($t['diferencia_usd'] > 0 ? 'text-success' : '') }}">
                        ${{ number_format($t['diferencia_usd'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="13" class="text-center" style="color: #94a3b8; padding: 15px;">No hay turnos registrados en este período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Centro Oficial de Reportería • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
