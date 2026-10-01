<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos y Métodos de Pago</title>
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
            color: #2563eb;
            font-weight: 600;
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
        .text-primary { color: #2563eb; }
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

    <!-- CABECERA DE REPORTE -->
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                <div class="empresa-title">{{ $empresa->nombre_comercial }}</div>
                <div class="empresa-info">
                    <strong>Razón Social:</strong> {{ $empresa->razon_social }} | <strong>RIF:</strong> {{ $empresa->rif }}<br>
                    <strong>Dirección:</strong> {{ $empresa->direccion }}<br>
                    <strong>Teléfono:</strong> {{ $empresa->telefono ?: 'S/D' }} | <strong>Email:</strong> {{ $empresa->correo ?: 'S/D' }}
                </div>
            </td>
            <td style="width: 45%;" class="text-end">
                <div class="report-title">REPORTE DE INGRESOS Y CAJA</div>
                <div class="report-subtitle">
                    <strong>Período:</strong> {{ $data['kpis']['periodo_texto'] }}<br>
                    <strong>Tasa Oficial:</strong> {{ number_format($data['kpis']['tasa_cambio'], 2, ',', '.') }} Bs/$ | <strong>Emisión:</strong> {{ date('d/m/Y h:i A') }}<br>
                    <strong>Generado por:</strong> {{ auth()->user()->name ?? 'Sistema' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- KPIS EJECUTIVOS -->
    <table class="kpi-container" cellspacing="6">
        <tr>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-label">Total Facturado</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_facturado_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_facturado_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Total Recaudado (Caja)</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['total_pagado_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_pagado_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box">
                    <div class="kpi-label">Ventas Procesadas</div>
                    <div class="kpi-val-usd">{{ $data['kpis']['conteo_ventas'] }}</div>
                    <div class="kpi-val-bs">Prom: ${{ number_format($data['kpis']['ticket_promedio_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #d97706;">
                    <div class="kpi-label">Por Cobrar (Créditos)</div>
                    <div class="kpi-val-usd" style="color: #d97706;">${{ number_format($data['kpis']['total_por_cobrar_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Vueltos: ${{ number_format($data['kpis']['total_vuelto_usd'], 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. DESGLOSE POR MÉTODO DE PAGO -->
    <div class="section-heading">1. Resumen de Recaudación por Forma de Pago</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35%;">Método de Pago</th>
                <th class="text-center" style="width: 15%;">Transacciones</th>
                <th class="text-end" style="width: 20%;">Monto ($ USD)</th>
                <th class="text-end" style="width: 20%;">Monto (Bs. VES)</th>
                <th class="text-end" style="width: 10%;">% Part.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['desglose_metodos'] as $m)
                <tr>
                    <td class="fw-bold">{{ $m['nombre'] }}</td>
                    <td class="text-center">{{ $m['transacciones_count'] }}</td>
                    <td class="text-end fw-bold text-success">${{ number_format($m['total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">Bs. {{ number_format($m['total_bs'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ $m['porcentaje'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center" style="color: #94a3b8; padding: 15px;">No hay pagos registrados en este período</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- 2. DETALLE DE VENTAS -->
    <div class="section-heading">2. Detalle de Ventas y Facturas del Período</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Factura / Código</th>
                <th>Fecha / Hora</th>
                <th>Cliente</th>
                <th>Caja</th>
                <th>Métodos de Pago</th>
                <th class="text-end">Total Facturado ($)</th>
                <th class="text-end">Monto Cobrado ($)</th>
                <th class="text-end">Saldo Crédito ($)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['transacciones'] as $t)
                <tr>
                    <td class="fw-bold">{{ $t['codigo'] }}</td>
                    <td>{{ $t['fecha'] }} {{ $t['hora'] }}</td>
                    <td>{{ $t['cliente'] }}</td>
                    <td>{{ $t['caja'] }}</td>
                    <td>{{ $t['metodos'] }}</td>
                    <td class="text-end fw-bold">${{ number_format($t['total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end text-success">${{ number_format($t['monto_pagado_usd'], 2, ',', '.') }}</td>
                    <td class="text-end {{ $t['saldo_pendiente_usd'] > 0 ? 'text-danger fw-bold' : '' }}">
                        ${{ number_format($t['saldo_pendiente_usd'], 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center" style="color: #94a3b8; padding: 15px;">No se registraron ventas en el período</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema POS • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
