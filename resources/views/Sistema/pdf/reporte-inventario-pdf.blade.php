<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario y Valorización</title>
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
        .data-table td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-size: 9px; }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
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
                    <strong>Dirección:</strong> {{ $empresa->direccion }}<br>
                    <strong>Teléfono:</strong> {{ $empresa->telefono ?: 'S/D' }}
                </div>
            </td>
            <td style="width: 45%;" class="text-end">
                <div class="report-title">INVENTARIO Y VALORIZACIÓN</div>
                <div class="report-subtitle">
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
                <div class="kpi-box">
                    <div class="kpi-label">Artículos / Existencia</div>
                    <div class="kpi-val-usd">{{ $data['kpis']['total_items'] }} ítems</div>
                    <div class="kpi-val-bs">{{ number_format($data['kpis']['total_unidades'], 0, ',', '.') }} unidades físicas</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Valor a Costo Base</div>
                    <div class="kpi-val-usd text-primary">${{ number_format($data['kpis']['valor_costo_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['valor_costo_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Valor a Precio Venta</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['valor_venta_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['valor_venta_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">Margen Proyectado</div>
                    <div class="kpi-val-usd" style="color: #d97706;">{{ $data['kpis']['margen_porcentaje'] }}%</div>
                    <div class="kpi-val-bs">+${{ number_format($data['kpis']['margen_proyectado_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DETALLE DE INVENTARIO -->
    <div class="section-heading">Detalle de Stock y Valorización por Almacén</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">Código</th>
                <th style="width: 25%;">Producto</th>
                <th style="width: 12%;">Categoría</th>
                <th style="width: 13%;">Almacén</th>
                <th class="text-center" style="width: 8%;">Stock</th>
                <th class="text-end" style="width: 10%;">Costo ($)</th>
                <th class="text-end" style="width: 11%;">Precio ($)</th>
                <th class="text-end" style="width: 11%;">Subtotal Costo ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['inventario'] as $i)
                <tr>
                    <td>{{ $i['codigo_interno'] }}</td>
                    <td class="fw-bold">{{ $i['nombre'] }}</td>
                    <td>{{ $i['categoria'] }}</td>
                    <td>{{ $i['almacen'] }}</td>
                    <td class="text-center {{ $i['es_bajo_stock'] ? 'text-danger fw-bold' : '' }}">
                        {{ $i['stock_actual'] }} {!! $i['es_bajo_stock'] ? '<small style="color: red;">(!)</small>' : '' !!}
                    </td>
                    <td class="text-end">${{ number_format($i['costo_unitario_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($i['precio_venta_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-primary">${{ number_format($i['costo_subtotal_usd'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center" style="color: #94a3b8; padding: 15px;">No hay productos que coincidan con los filtros</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema POS • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
