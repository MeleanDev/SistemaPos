<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Créditos y Cartera (CXC / CXP)</title>
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
        .text-danger { color: #dc2626; }
        .text-warning { color: #d97706; }
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
                <div class="report-title">ESTADO DE CRÉDITOS Y CARTERA</div>
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
                <div class="kpi-box" style="border-left: 3px solid #dc2626;">
                    <div class="kpi-label">Por Cobrar Clientes (CXC)</div>
                    <div class="kpi-val-usd text-danger">${{ number_format($data['kpis']['total_cxc_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_cxc_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #059669;">
                    <div class="kpi-label">Abonos Cobrados (Período)</div>
                    <div class="kpi-val-usd text-success">${{ number_format($data['kpis']['total_abonos_cxc_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_abonos_cxc_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #0f172a;">
                    <div class="kpi-label">Por Pagar Proveedores (CXP)</div>
                    <div class="kpi-val-usd">${{ number_format($data['kpis']['total_cxp_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_cxp_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="kpi-box" style="border-left: 3px solid #2563eb;">
                    <div class="kpi-label">Abonos a Proveedores</div>
                    <div class="kpi-val-usd" style="color: #2563eb;">${{ number_format($data['kpis']['total_abonos_cxp_usd'], 2, ',', '.') }}</div>
                    <div class="kpi-val-bs">Bs. {{ number_format($data['kpis']['total_abonos_cxp_bs'], 2, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 1. ANTIGÜEDAD DE DEUDA CXC -->
    <div class="section-heading">1. Resumen de Antigüedad de Deuda de Clientes (CXC)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center">Al Día (>7 días)</th>
                <th class="text-center">Por Vencer (1-7 días)</th>
                <th class="text-center">Mora 1 a 15 días</th>
                <th class="text-center">Mora 16 a 30 días</th>
                <th class="text-center">Mora Mayor a 30 días</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center fw-bold text-success">${{ number_format($data['antiguedad_cxc']['al_dia'], 2, ',', '.') }}</td>
                <td class="text-center fw-bold text-warning">${{ number_format($data['antiguedad_cxc']['por_vencer_7d'], 2, ',', '.') }}</td>
                <td class="text-center fw-bold text-danger">${{ number_format($data['antiguedad_cxc']['mora_1_15d'], 2, ',', '.') }}</td>
                <td class="text-center fw-bold text-danger">${{ number_format($data['antiguedad_cxc']['mora_16_30d'], 2, ',', '.') }}</td>
                <td class="text-center fw-bold text-danger">${{ number_format($data['antiguedad_cxc']['mora_mas_30d'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 2. CUENTAS POR COBRAR PENDIENTES -->
    <div class="section-heading">2. Detalle de Clientes con Facturas Pendientes (CXC)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Cédula / RIF</th>
                <th>Teléfono</th>
                <th>Factura</th>
                <th>Emisión</th>
                <th>Vencimiento</th>
                <th class="text-end">Monto Factura ($)</th>
                <th class="text-end">Saldo Pendiente ($)</th>
                <th class="text-end">Saldo Pendiente (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['detalle_cxc'] as $c)
                <tr>
                    <td class="fw-bold">{{ $c['cliente_nombre'] }}</td>
                    <td>{{ $c['cliente_cedula'] }}</td>
                    <td>{{ $c['cliente_telefono'] }}</td>
                    <td>{{ $c['numero_factura'] }}</td>
                    <td>{{ $c['fecha_emision'] }}</td>
                    <td>{{ $c['fecha_vencimiento'] }} {!! $c['es_vencida'] ? '<span class="text-danger fw-bold">(VENCIDA)</span>' : '' !!}</td>
                    <td class="text-end">${{ number_format($c['monto_total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">${{ number_format($c['saldo_pendiente_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">Bs. {{ number_format($c['saldo_pendiente_bs'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center" style="color: #94a3b8; padding: 15px;">No hay cuentas por cobrar pendientes</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- 3. CUENTAS POR PAGAR PENDIENTES -->
    <div class="section-heading">3. Detalle de Cuentas por Pagar a Proveedores (CXP)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>RIF</th>
                <th>Factura Proveedor</th>
                <th>Emisión</th>
                <th>Vencimiento</th>
                <th class="text-end">Total Compra ($)</th>
                <th class="text-end">Saldo Pendiente ($)</th>
                <th class="text-end">Saldo Pendiente (Bs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['detalle_cxp'] as $p)
                <tr>
                    <td class="fw-bold">{{ $p['proveedor_nombre'] }}</td>
                    <td>{{ $p['proveedor_rif'] }}</td>
                    <td>{{ $p['numero_factura'] }}</td>
                    <td>{{ $p['fecha_emision'] }}</td>
                    <td>{{ $p['fecha_vencimiento'] }} {!! $p['es_vencida'] ? '<span class="text-danger fw-bold">(VENCIDA)</span>' : '' !!}</td>
                    <td class="text-end">${{ number_format($p['monto_total_usd'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold">${{ number_format($p['saldo_pendiente_usd'], 2, ',', '.') }}</td>
                    <td class="text-end">Bs. {{ number_format($p['saldo_pendiente_bs'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center" style="color: #94a3b8; padding: 15px;">No hay cuentas por pagar pendientes</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-note">
        Documento generado automáticamente por el Sistema POS • {{ $empresa->nombre_comercial }} • Página 1
    </div>

</body>
</html>
