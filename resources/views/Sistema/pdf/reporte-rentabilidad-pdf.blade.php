<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Rentabilidad y Utilidad Bruta</title>
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
        .text-danger { color: #dc2626; }
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
                    <strong>Dirección:</strong> {{ $empresa->direccion }}<br>
                    <strong>Teléfono:</strong> {{ $empresa->telefono ?: 'S/D' }}
                </div>
            </td>
            <td style="width: 45%;" class="text-end">
                <div class="report-title">RENTABILIDAD Y UTILIDAD BRUTA</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $data['kpis']['periodo_texto'] }}<br>
                    <strong>Tasa Oficial:</strong> {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$ | <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }}<br>
                    <strong>Generado por:</strong> {{ auth()->user()->name ?? 'Sistema' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- KPIS -->
    <table class="kpi-container" cellspacing="6">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Ingresos por Ventas</div>
                    <div class="kpi-val-usd text-primary">${{ number_format($data['kpis']['total_ingresos_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_ingresos_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #64748b;">
                    <div class="kpi-label">Costo de Ventas (COGS)</div>
                    <div class="kpi-val-usd" style="color: #64748b;">${{ number_format($data['kpis']['total_costo_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_costo_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Ganancia Bruta Real</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['ganancia_bruta_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['ganancia_bruta_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">Margen Bruto Total</div>
                    <div class="kpi-val-usd" style="color: #d97706;">{{ number_format($data['kpis']['margen_bruto_porcentaje'], 1) }}%</div>
                    <div class="kpi-val-bs">Rentabilidad Global</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- TOP 20 PRODUCTOS MÁS RENTABLES -->
    <div class="section-heading">Top 20 Productos / Servicios con Mayor Retorno y Ventas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30%;">Ítem / Categoría</th>
                <th class="text-center" style="width: 10%;">Tipo</th>
                <th class="text-center" style="width: 10%;">Cant. Vendida</th>
                <th class="text-end" style="width: 15%;">Ingreso Total ($)</th>
                <th class="text-end" style="width: 15%;">Costo Total ($)</th>
                <th class="text-end" style="width: 10%;">Ganancia ($)</th>
                <th class="text-end" style="width: 10%;">Margen %</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['top_productos'] as $p)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $p['nombre'] }}</div>
                        <small style="color: #64748b;">{{ $p['categoria'] }}</small>
                    </td>
                    <td class="text-center">{{ $p['tipo'] }}</td>
                    <td class="text-center fw-bold">{{ $p['cantidad_vendida'] }}</td>
                    <td class="text-end">${{ number_format($p['ingreso_total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($p['costo_total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-success">${{ number_format($p['ganancia_bruta_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold {{ $p['margen_porcentaje'] >= 30 ? 'text-success' : ($p['margen_porcentaje'] >= 15 ? 'text-primary' : 'text-danger') }}">
                        {{ number_format($p['margen_porcentaje'], 1) }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="color: #94a3b8; padding: 15px;">No hay movimientos de ventas registrados en el período</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema POS • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
