<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Existencias por Almacén</title>
    <style>
        @page { margin: 25px 30px 40px 30px; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #1e293b; line-height: 1.4; }
        .header-table { width: 100%; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 15px; }
        .empresa-title { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .empresa-info { font-size: 9px; color: #64748b; }
        .report-title { font-size: 14px; font-weight: bold; color: #0f172a; text-align: right; }
        .report-subtitle { font-size: 9px; color: #475569; text-align: right; }
        .kpi-container { width: 100%; margin-bottom: 15px; }
        .kpi-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; }
        .kpi-label { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .kpi-val { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .kpi-sub { font-size: 8px; color: #64748b; }
        .section-heading { font-size: 11px; font-weight: bold; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 15px; margin-bottom: 8px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th { background-color: #0f172a; color: #ffffff; font-size: 8.5px; font-weight: bold; text-transform: uppercase; padding: 6px 6px; text-align: left; }
        .data-table td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; font-size: 8.5px; }
        .data-table tr:nth-child(even) td { background-color: #f8fafc; }
        .data-table tfoot td { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 9px; padding: 6px 6px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-primary { color: #2563eb; }
        .text-muted { color: #64748b; }
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
                <div class="report-title">EXISTENCIAS POR ALMACÉN</div>
                <div class="report-subtitle">
                    <strong>Matriz Multialmacén Consolidada</strong><br>
                    <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }} | <strong>Generado por:</strong> {{ auth()->user()->name ?? 'Sistema' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- KPIS -->
    <table class="kpi-container" cellspacing="6">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Productos Evaluados</div>
                    <div class="kpi-val text-primary">{{ $data['kpis']['total_productos'] }}</div>
                    <div class="kpi-sub">Artículos en catálogo</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Existencia Total</div>
                    <div class="kpi-val text-success">{{ number_format($data['kpis']['total_unidades'], 2, ',', '.') }}</div>
                    <div class="kpi-sub">Unidades físicas globales</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #6366f1;">
                    <div class="kpi-label">Almacenes Activos</div>
                    <div class="kpi-val" style="color: #6366f1;">{{ $data['kpis']['total_almacenes'] }}</div>
                    <div class="kpi-sub">Ubicaciones físicas</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #dc2626;">
                    <div class="kpi-label">Sin Existencia</div>
                    <div class="kpi-val text-danger">{{ $data['kpis']['productos_sin_stock'] }}</div>
                    <div class="kpi-sub">Stock en 0 unidades</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- DETALLE DE EXISTENCIAS -->
    <div class="section-heading">Matriz de Existencias por Almacén</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 9%;">Código</th>
                <th>Producto</th>
                <th style="width: 12%;">Categoría</th>
                <th class="text-center" style="width: 5%;">U.M.</th>
                @foreach($data['almacenes'] as $alm)
                    <th class="text-end" style="width: {{ count($data['almacenes']) > 3 ? '9%' : '12%' }};">
                        {{ $alm['nombre'] }}
                    </th>
                @endforeach
                <th class="text-end" style="width: 10%;">Stock Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] as $item)
                <tr>
                    <td class="fw-bold">{{ $item['codigo_interno'] }}</td>
                    <td>
                        <strong>{{ $item['nombre'] }}</strong>
                    </td>
                    <td>{{ $item['categoria'] }}</td>
                    <td class="text-center text-muted">{{ $item['unidad_medida'] }}</td>
                    @foreach($data['almacenes'] as $alm)
                        @php $stockAlm = $item['stocks_por_almacen'][$alm['id']] ?? 0; @endphp
                        <td class="text-end {{ $stockAlm > 0 ? 'fw-bold' : 'text-muted' }}">
                            {{ number_format($stockAlm, 2, ',', '.') }}
                        </td>
                    @endforeach
                    <td class="text-end fw-bold {{ $item['stock_total'] > 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($item['stock_total'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + count($data['almacenes']) }}" class="text-center" style="padding: 20px; color: #64748b;">
                        No se encontraron productos registrados con los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($data['items']) > 0)
            <tfoot>
                <tr>
                    <td colspan="4" class="text-end uppercase">TOTALES GENERALES:</td>
                    @foreach($data['almacenes'] as $alm)
                        <td class="text-end">
                            {{ number_format($data['totales_por_almacen'][$alm['id']] ?? 0, 2, ',', '.') }}
                        </td>
                    @endforeach
                    <td class="text-end text-white">
                        {{ number_format($data['kpis']['total_unidades'], 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="footer-note">
        Documento de Control y Auditoría Interna de Existencias — Generado automáticamente por {{ config('app.name', 'Sistema POS') }} el {{ date('d/m/Y h:i:s A') }}
    </div>

</body>
</html>
