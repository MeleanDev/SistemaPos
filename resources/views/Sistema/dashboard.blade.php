@extends('Sistema.layouts.app')

@section('titulo', '📊 Panel Principal')
@section('subtitulo', 'Control en tiempo real de operaciones, ventas, inventario y métricas ejecutivas')

@section('rutas')
    <span class="active">Dashboard</span>
@endsection

@section('contenido')
<div class="row g-4 mb-4">
    {{-- Executive Welcome & Quick Action Banner --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 text-white overflow-hidden"
             style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #1d4ed8 100%);">
            <div class="card-body p-4 p-md-4">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0"
                             style="width: 52px; height: 52px; background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(8px);">
                            <i class="fas fa-user-circle fs-3"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h4 class="fw-bold mb-0 text-white">¡Hola, {{ $dashboard['usuario']['nombre_completo'] }}!</h4>
                                <span class="badge rounded-pill px-3 py-1 fw-semibold text-white"
                                      style="background: rgba(255, 255, 255, 0.2); font-size: 0.75rem;">
                                    {{ $dashboard['usuario']['rol_principal'] }}
                                </span>
                            </div>
                            <p class="mb-0 text-white-50 small d-flex align-items-center gap-2 flex-wrap">
                                <span><i class="fas fa-building text-info me-1"></i> {{ $dashboard['empresa']['nombre_comercial'] }}</span>
                                <span>&bull;</span>
                                <span><i class="fas fa-calendar-day text-warning me-1"></i> {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</span>
                                <span>&bull;</span>
                                <span class="font-monospace text-white-50"><i class="fas fa-money-bill-wave text-success me-1"></i> Tasa BCV: ${{ number_format($dashboard['empresa']['tasa_usd'], 2) }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Quick Action Buttons Filtered by User Permissions --}}
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if($dashboard['permisos']['pos_acceso'])
                            <a href="{{ route('pos') }}" class="btn btn-light rounded-pill px-3.5 py-2 fw-bold text-primary shadow-sm">
                                <i class="fas fa-cash-register me-1.5"></i> Terminal POS
                            </a>
                        @endif

                        @if($dashboard['permisos']['productos_crear'])
                            <a href="{{ route('producto') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                <i class="fas fa-plus me-1.5"></i> Producto
                            </a>
                        @endif

                        @if($dashboard['permisos']['clientes_crear'])
                            <a href="{{ route('cliente') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                <i class="fas fa-user-plus me-1.5"></i> Cliente
                            </a>
                        @endif

                        @if($dashboard['permisos']['cajas_aperturar'])
                            <a href="{{ route('caja') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                <i class="fas fa-vault me-1.5"></i> Cajas
                            </a>
                        @endif

                        @if($dashboard['permisos']['compras_recepcion'])
                            <a href="{{ route('recepcion') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                <i class="fas fa-truck-ramp-box me-1.5"></i> Recepción
                            </a>
                        @endif

                        @can('consultor.ver')
                            <a href="{{ route('consultor_precios') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                <i class="fas fa-barcode me-1.5"></i> Precios
                            </a>
                        @endcan

                        <button id="btnRefrescarDashboard" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold" type="button" title="Actualizar métricas">
                            <i class="fas fa-arrows-rotate" id="iconoRefrescar"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Metric Cards Grid --}}
    @if($dashboard['permisos']['ventas_ver'])
        {{-- Card: Ventas de Hoy --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Ventas de Hoy</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #ecfdf5; color: #059669;">
                            <i class="fas fa-dollar-sign fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiVentasHoyUsd">
                            ${{ number_format($dashboard['kpis']['ventas_hoy_usd'] ?? 0, 2) }}
                        </h3>
                        <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #ecfdf5; color: #059669;">
                            @if(($dashboard['kpis']['crecimiento_ventas'] ?? 0) >= 0)
                                <i class="fas fa-arrow-trend-up me-1"></i>+{{ $dashboard['kpis']['crecimiento_ventas'] ?? 0 }}%
                            @else
                                <i class="fas fa-arrow-trend-down me-1"></i>{{ $dashboard['kpis']['crecimiento_ventas'] ?? 0 }}%
                            @endif
                        </span>
                    </div>
                    <div class="text-secondary small font-monospace" id="kpiVentasHoyBs">
                        Bs. {{ number_format($dashboard['kpis']['ventas_hoy_bs'] ?? 0, 2) }}
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-clock text-primary me-1"></i> Ventas procesadas hoy
                    </p>
                </div>
            </div>
        </div>

        {{-- Card: Tickets / Facturas --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Tickets / Facturas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #eff6ff; color: #2563eb;">
                            <i class="fas fa-receipt fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiFacturasHoyCount">
                            {{ $dashboard['kpis']['facturas_hoy_count'] ?? 0 }}
                        </h3>
                        <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #eff6ff; color: #2563eb;">
                            Hoy
                        </span>
                    </div>
                    <div class="text-secondary small">
                        Ticket Promedio: <strong class="text-dark font-monospace" id="kpiTicketPromedio">${{ number_format($dashboard['kpis']['ticket_promedio_usd'] ?? 0, 2) }}</strong>
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-check-circle text-success me-1"></i> Transacciones completadas
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($dashboard['permisos']['productos_ver'])
        {{-- Card: Catálogo de Productos --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Productos en Catálogo</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #fffbeb; color: #d97706;">
                            <i class="fas fa-boxes-stacked fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiTotalProductos">
                            {{ $dashboard['kpis']['total_productos'] ?? 0 }}
                        </h3>
                        @if(($dashboard['kpis']['productos_bajo_stock_count'] ?? 0) > 0)
                            <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #fef2f2; color: #dc2626;">
                                <i class="fas fa-triangle-exclamation me-1"></i>{{ $dashboard['kpis']['productos_bajo_stock_count'] }} bajo stock
                            </span>
                        @else
                            <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #ecfdf5; color: #059669;">
                                Stock óptimo
                            </span>
                        @endif
                    </div>
                    <div class="text-secondary small" id="kpiTotalServicios">
                        {{ $dashboard['kpis']['total_servicios'] ?? 0 }} servicios activos
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-warehouse text-warning me-1"></i> Existencias activas
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($dashboard['permisos']['clientes_ver'])
        {{-- Card: Clientes --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Clientes Registrados</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #f0fdfa; color: #0d9488;">
                            <i class="fas fa-users fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiTotalClientes">
                            {{ $dashboard['kpis']['total_clientes'] ?? 0 }}
                        </h3>
                        <span class="badge rounded-pill px-2.5 py-1 small fw-semibold font-monospace" style="background-color: #f0fdfa; color: #0d9488;" id="kpiClientesNuevosMes">
                            +{{ $dashboard['kpis']['clientes_nuevos_mes'] ?? 0 }} este mes
                        </span>
                    </div>
                    <div class="text-secondary small">
                        Directorio comercial general
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-id-card text-info me-1"></i> Detal y Mayoristas
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($dashboard['permisos']['cxc_ver'])
        {{-- Card: Cuentas por Cobrar --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Cuentas por Cobrar</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #faf5ff; color: #9333ea;">
                            <i class="fas fa-hand-holding-dollar fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiCxcPendienteUsd">
                            ${{ number_format($dashboard['kpis']['cxc_pendiente_usd'] ?? 0, 2) }}
                        </h3>
                        <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #faf5ff; color: #9333ea;">
                            {{ $dashboard['kpis']['cxc_conteo'] ?? 0 }} por cobrar
                        </span>
                    </div>
                    <div class="text-secondary small font-monospace" id="kpiCxcPendienteBs">
                        Bs. {{ number_format($dashboard['kpis']['cxc_pendiente_bs'] ?? 0, 2) }}
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-clock-rotate-left text-primary me-1"></i> Créditos otorgados
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($dashboard['permisos']['cxp_ver'])
        {{-- Card: Cuentas por Pagar --}}
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="text-secondary fw-semibold small text-uppercase">Cuentas por Pagar</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 44px; height: 44px; background-color: #fff1f2; color: #e11d48;">
                            <i class="fas fa-file-invoice-dollar fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between mb-1">
                        <h3 class="fw-bold text-dark mb-0 font-monospace" id="kpiCxpPendienteUsd">
                            ${{ number_format($dashboard['kpis']['cxp_pendiente_usd'] ?? 0, 2) }}
                        </h3>
                        <span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #fff1f2; color: #e11d48;">
                            {{ $dashboard['kpis']['cxp_conteo'] ?? 0 }} por pagar
                        </span>
                    </div>
                    <div class="text-secondary small font-monospace" id="kpiCxpPendienteBs">
                        Bs. {{ number_format($dashboard['kpis']['cxp_pendiente_bs'] ?? 0, 2) }}
                    </div>
                    <p class="text-secondary small mb-0 mt-3 pt-2 border-top">
                        <i class="fas fa-truck-moving text-danger me-1"></i> Compromisos con proveedores
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Middle Section: Sales Chart & Cash Register Status --}}
<div class="row g-4 mb-4">
    @if($dashboard['permisos']['ventas_ver'])
        {{-- Sales Chart --}}
        <div class="col-12 {{ $dashboard['permisos']['cajas_ver'] ? 'col-xl-8' : 'col-xl-12' }}">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="fas fa-chart-line text-primary me-2"></i>Ventas de los Últimos 7 Días
                        </h5>
                        <p class="text-secondary small mb-0">Evolución de ingresos y facturación diaria en USD y Bolívares</p>
                    </div>
                    <span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #eff6ff; color: #2563eb;">
                        <i class="fas fa-calendar-week me-1"></i> Semana
                    </span>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; height: 300px; width: 100%;">
                        <canvas id="ventasSemanaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @elseif($dashboard['permisos']['pos_acceso'])
        {{-- POS Operator Terminal Card for users without ventas.ver --}}
        <div class="col-12 {{ $dashboard['permisos']['cajas_ver'] ? 'col-xl-8' : 'col-xl-12' }}">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white p-4">
                <div class="d-flex flex-column justify-content-center align-items-center text-center h-100 py-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-3"
                         style="width: 72px; height: 72px; background-color: #eff6ff; color: #2563eb;">
                        <i class="fas fa-cash-register fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Punto de Venta Disponible</h4>
                    <p class="text-secondary small mb-4" style="max-width: 480px;">
                        Comienza a facturar productos, emitir tickets térmicos y cobrar en multimoneda en tiempo real.
                    </p>
                    <div class="d-flex gap-2 flex-wrap justify-content-center">
                        <a href="{{ route('pos') }}" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-xs">
                            <i class="fas fa-cash-register me-1.5"></i> Abrir Terminal POS
                        </a>
                        @can('consultor.ver')
                            <a href="{{ route('consultor_precios') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2.5 fw-semibold">
                                <i class="fas fa-barcode me-1.5"></i> Consultar Precios
                            </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Cash Register Shift Status --}}
    @if($dashboard['permisos']['cajas_ver'])
        <div class="col-12 {{ $dashboard['permisos']['ventas_ver'] || $dashboard['permisos']['pos_acceso'] ? 'col-xl-4' : 'col-xl-6' }}">
            <div class="card border-0 shadow-xs rounded-4 h-100 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-vault text-primary me-2"></i>Estado de Caja
                    </h5>
                    <p class="text-secondary small mb-0">Control del turno de cobro activo</p>
                </div>
                <div class="card-body p-4 d-flex flex-column justify-content-between" id="contenedorEstadoCaja">
                    @if(!empty($dashboard['caja_estado']['tiene_turno_abierto']))
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #ecfdf5; color: #059669;">
                                    <i class="fas fa-circle me-1" style="font-size: 0.55rem;"></i> Turno Abierto
                                </span>
                                <span class="small text-secondary font-monospace">
                                    {{ $dashboard['caja_estado']['fecha_apertura'] }} {{ $dashboard['caja_estado']['hora_apertura'] }}
                                </span>
                            </div>
                            <div class="mb-3">
                                <div class="fw-bold text-dark fs-5 mb-1 d-flex align-items-center gap-2">
                                    <i class="fas fa-cash-register text-primary"></i> {{ $dashboard['caja_estado']['caja_nombre'] }}
                                </div>
                                <div class="text-secondary small d-flex align-items-center gap-1.5">
                                    <i class="fas fa-user-shield text-info"></i> Cajero: <strong class="text-dark">{{ $dashboard['caja_estado']['cajero_nombre'] }}</strong>
                                </div>
                            </div>
                            <div class="p-3 rounded-4 border mb-4 bg-white shadow-xs">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small">Monto Apertura:</span>
                                    <span class="fw-semibold text-dark font-monospace">${{ number_format($dashboard['caja_estado']['monto_apertura_usd'], 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small">Ventas Efectivo:</span>
                                    <span class="fw-semibold font-monospace" style="color: #059669;">${{ number_format($dashboard['caja_estado']['ventas_efectivo_usd'], 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small">Pagos Digitales:</span>
                                    <span class="fw-semibold text-primary font-monospace">${{ number_format($dashboard['caja_estado']['pagos_digitales_usd'], 2) }}</span>
                                </div>
                                <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-baseline">
                                    <span class="fw-bold text-dark">Total en Caja:</span>
                                    <span class="fw-bold fs-4 text-dark font-monospace">${{ number_format($dashboard['caja_estado']['total_en_caja_usd'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            @if($dashboard['permisos']['pos_acceso'])
                                <a href="{{ route('pos') }}" class="btn btn-primary rounded-pill py-2.5 fw-bold shadow-xs">
                                    <i class="fas fa-cash-register me-1.5"></i> Ir al Terminal POS
                                </a>
                            @endif
                            <a href="{{ route('caja') }}" class="btn btn-outline-secondary rounded-pill py-2 small fw-semibold">
                                <i class="fas fa-vault me-1.5"></i> Gestión de Cajas
                            </a>
                        </div>
                    @else
                        <div class="text-center py-4 my-auto">
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 64px; height: 64px; background-color: #f8fafc; color: #94a3b8;">
                                <i class="fas fa-cash-register fs-3"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Sin Turno de Caja Activo</h6>
                            <p class="text-secondary small mb-4 px-2">No se encuentra ninguna sesión de caja abierta en este momento.</p>
                            @if($dashboard['permisos']['cajas_aperturar'])
                                <a href="{{ route('caja') }}" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-xs">
                                    <i class="fas fa-key me-1.5"></i> Aperturar Turno
                                </a>
                            @else
                                <span class="badge rounded-pill px-3 py-1.5 text-secondary" style="background-color: #f1f5f9;">
                                    Consulte con el administrador para abrir turno
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Bottom Row: Recent Transactions & Low Stock Alerts --}}
<div class="row g-4">
    @if($dashboard['permisos']['ventas_ver'])
        {{-- Recent Sales Table --}}
        <div class="col-12 {{ !empty($dashboard['alertas_stock']) ? 'col-xl-8' : 'col-12' }}">
            <div class="card border-0 shadow-xs rounded-4 bg-white">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="fas fa-receipt text-primary me-2"></i>Transacciones Recientes
                        </h5>
                        <p class="text-secondary small mb-0">Últimas operaciones procesadas en el punto de venta</p>
                    </div>
                    <a href="{{ route('factura') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold">
                        Ver todas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tablaVentasRecientes">
                            <thead>
                                <tr style="border-bottom: 2px solid #f1f5f9;">
                                    <th class="ps-3 text-secondary text-uppercase small fw-bold"># Ticket</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Cliente</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Método Pago</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Total</th>
                                    <th class="text-secondary text-uppercase small fw-bold">Fecha / Hora</th>
                                    <th class="text-end pe-3 text-secondary text-uppercase small fw-bold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tablaVentasRecientesBody">
                                @forelse($dashboard['transacciones_recientes'] as $venta)
                                    <tr class="align-middle">
                                        <td class="ps-3 py-3 font-monospace fw-bold text-dark">
                                            {{ $venta['codigo'] }}
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark">{{ $venta['cliente'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-2.5 py-1 fw-semibold font-monospace" style="background-color: #f1f5f9; color: #475569;">
                                                {{ $venta['metodos'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark font-monospace">${{ number_format($venta['total_usd'], 2) }}</div>
                                            <div class="text-secondary small font-monospace" style="font-size: 0.75rem;">Bs. {{ number_format($venta['total_bs'], 2) }}</div>
                                        </td>
                                        <td class="text-secondary small font-monospace">
                                            {{ $venta['fecha_hora'] }}
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="{{ route('pos.imprimir_ticket', $venta['id']) }}" target="_blank"
                                               class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1"
                                               title="Ver Ticket Térmico">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-secondary">
                                            <div class="py-2">
                                                <i class="fas fa-receipt fs-1 text-secondary opacity-25 d-block mb-3"></i>
                                                <span class="fw-semibold d-block text-dark">No hay transacciones registradas</span>
                                                <span class="small text-secondary">Las ventas procesadas en el punto de venta aparecerán aquí en tiempo real.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Low Stock Alerts Card --}}
    @if($dashboard['permisos']['productos_ver'] && !empty($dashboard['alertas_stock']))
        <div class="col-12 {{ $dashboard['permisos']['ventas_ver'] ? 'col-xl-4' : 'col-12' }}">
            <div class="card border-0 shadow-xs rounded-4 bg-white h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="fas fa-triangle-exclamation text-danger me-2"></i>Stock Mínimo
                        </h5>
                        <p class="text-secondary small mb-0">Artículos con inventario crítico</p>
                    </div>
                    @if($dashboard['permisos']['compras_recepcion'])
                        <a href="{{ route('recepcion') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 fw-semibold">
                            Reponer <i class="fas fa-plus ms-1"></i>
                        </a>
                    @endif
                </div>
                <div class="card-body px-4 pb-4 pt-2">
                    <div class="d-flex flex-column gap-3">
                        @foreach($dashboard['alertas_stock'] as $item)
                            <div class="p-3 rounded-4 border bg-white shadow-xs d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold text-dark mb-0">{{ $item['nombre'] }}</div>
                                    <div class="text-secondary small font-monospace">
                                        <i class="fas fa-barcode text-primary me-1"></i>{{ $item['codigo'] }} &bull; Mínimo: {{ $item['stock_minimo'] }} {{ $item['unidad_medida'] }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill px-2.5 py-1 fw-bold font-monospace" style="background-color: #fef2f2; color: #dc2626;">
                                        {{ $item['stock_actual'] }} {{ $item['unidad_medida'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Safe JSON Data injection for client-side scripts --}}
<script type="application/json" id="dashboard-initial-data">
    {!! json_encode($dashboard) !!}
</script>
@endsection

@section('scripts')
<script src="{{ asset('estilos/jsPropios/dashboard.js') }}"></script>
@endsection
