<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Libro de Ventas Fiscal (Sin IGTF)</title>
    <style>
        @page {
            margin: 20px 25px 30px 25px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .empresa-title {
            font-size: 15px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .empresa-info {
            font-size: 8px;
            color: #64748b;
        }
        .report-title {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
        }
        .report-subtitle {
            font-size: 8.5px;
            color: #475569;
            text-align: right;
        }
        .kpi-container {
            width: 100%;
            margin-bottom: 10px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 8px;
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
        .kpi-val-bs {
            font-size: 8px;
            color: #2563eb;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .data-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 5px 4px;
            text-align: left;
        }
        .data-table td {
            padding: 4px 4px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 8px;
        }
        .data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-danger { color: #dc2626; }
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
                <div class="report-title">LIBRO DE VENTAS FISCAL (SIN IGTF)</div>
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
                <div class="kpi-box">
                    <div class="kpi-label">Total Ventas Facturadas</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_facturado_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_facturado_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Base Imponible Gravada</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_base_imponible_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_base_imponible_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Impuesto IVA Causado (16%)</div>
                    <div class="kpi-val-usd" style="color: #059669;">${{ number_format($data['kpis']['total_iva_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_iva_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-label">Ventas Exentas / Exoneradas</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_exento_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_exento_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 7%;">Fecha</th>
                <th style="width: 9%;">Tipo Doc.</th>
                <th style="width: 9%;">N° Factura</th>
                <th style="width: 8%;">N° Control</th>
                <th style="width: 9%;">Fact. Afectada</th>
                <th style="width: 18%;">Cliente / Razón Social</th>
                <th style="width: 8%;">RIF / CI</th>
                <th class="text-end" style="width: 8%;">Total ($)</th>
                <th class="text-end" style="width: 8%;">Exento ($)</th>
                <th class="text-end" style="width: 8%;">Base Imp. ($)</th>
                <th class="text-end" style="width: 8%;">IVA ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] as $item)
                <tr>
                    <td>{{ $item['fecha'] }}</td>
                    <td class="fw-bold {{ str_contains($item['tipo_doc'], 'Nota') ? 'text-danger' : '' }}">{{ $item['tipo_doc'] }}</td>
                    <td class="fw-bold">{{ $item['codigo'] }}</td>
                    <td>{{ $item['numero_control'] }}</td>
                    <td>{{ $item['factura_afectada'] ?: '-' }}</td>
                    <td>{{ $item['cliente_nombre'] }}</td>
                    <td>{{ $item['cliente_rif'] }}</td>
                    <td class="text-end fw-bold {{ $item['total_usd'] < 0 ? 'text-danger' : '' }}">${{ number_format($item['total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($item['exento_usd'], 2, ',', '.') }}</td>
                    <td class="text-end {{ $item['base_imponible_usd'] < 0 ? 'text-danger' : '' }}">${{ number_format($item['base_imponible_usd'], 2, ',', '.') }}</td>
                    <td class="text-end {{ $item['iva_usd'] < 0 ? 'text-danger' : '' }}">${{ number_format($item['iva_usd'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center" style="color: #94a3b8; padding: 15px;">No hay registros fiscales en este período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Centro Oficial de Reportería • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
