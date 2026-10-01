<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas y Comisiones por Vendedor</title>
    <style>
        @page { margin: 25px 30px 40px 30px; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #1e293b; line-height: 1.4; }
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
        .empresa-title { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .empresa-info { font-size: 9px; color: #64748b; }
        .report-title { font-size: 14px; font-weight: bold; color: #0f172a; text-align: right; }
        .report-subtitle { font-size: 10px; color: #475569; text-align: right; }
        .kpi-container { width: 100%; margin-bottom: 15px; }
        .kpi-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .kpi-val-usd { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .kpi-val-bs { font-size: 9px; color: #2563eb; font-weight: 600; }
        .section-heading { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 15px; margin-bottom: 8px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th { background-color: #0f172a; color: #ffffff; font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 6px 8px; text-align: left; }
        .data-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9.5px; }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-primary { color: #2563eb; }
        .footer-note { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>

    <!-- CABECERA -->
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
                <div class="report-title">VENTAS Y COMISIONES POR VENDEDOR</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $periodo_texto }}<br>
                    <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }} | <strong>Generado por:</strong> {{ auth()->user()->name ?? 'Sistema' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- KPIS -->
    <table class="kpi-container" cellspacing="6">
        <tr>
            <td style="width: 33%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Total Ventas Asesoradas</div>
                    <div class="kpi-val-usd text-primary">${{ number_format($kpis['total_ventas_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($kpis['total_ventas_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Total Comisiones Ganadas</div>
                    <div class="kpi-val-usd text-success">${{ number_format($kpis['total_comisiones_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($kpis['total_comisiones_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 33%;">
                <div class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">Vendedor Estrella / Líder</div>
                    <div class="kpi-val-usd" style="font-size: 11px; color: #d97706;">{{ $kpis['top_vendedor'] }}</div>
                    <div class="kpi-val-bs">{{ $kpis['conteo_facturas'] }} Facturas Asesoradas</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- RESUMEN CONSOLIDADO POR VENDEDOR -->
    <div class="section-heading">Resumen Consolidado de Ventas y Comisiones</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Vendedor / Documento</th>
                <th class="text-center">% Comisión</th>
                <th class="text-center">Facturas</th>
                <th class="text-end">Total Vendido ($)</th>
                <th class="text-end">Total Vendido (Bs.)</th>
                <th class="text-end">Comisión Ganada ($)</th>
                <th class="text-end">Comisión Ganada (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resumen as $v)
                <tr>
                    <td class="fw-bold">{{ $v['nombre'] }} <small style="color: #64748b;">({{ $v['documento'] }})</small></td>
                    <td class="text-center">{{ number_format($v['comision_porcentaje'], 2) }}%</td>
                    <td class="text-center">{{ $v['conteo_ventas'] }}</td>
                    <td class="text-end fw-bold">${{ number_format($v['total_vendido_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">Bs. {{ number_format($v['total_vendido_bs'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">${{ number_format($v['total_comision_usd'], 2, ',', '.') }}</td>
                    <td class="text-end text-success">Bs. {{ number_format($v['total_comision_bs'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center" style="color: #94a3b8; padding: 15px;">No hay ventas asociadas a vendedores en el período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema POS • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
