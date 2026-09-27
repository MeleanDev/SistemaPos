@extends('Sistema.layouts.app')

@section('titulo', '📊 Reporte de Ventas y Comisiones por Vendedor')
@section('subtitulo', 'Auditoría de rendimiento comercial, facturación atribuida y cálculo de comisiones ganadas')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Reporte de Vendedores</span>
@endsection

@section('acciones')
    <x-button href="{{ route('vendedor') }}" variant="primary" icon="fas fa-user-tie" text="Gestionar Catálogo de Vendedores" />
@endsection

@section('contenido')
<div class="container-fluid px-0">

    <!-- 1. KPIS Y TARJETAS RESUMEN DE VENTAS POR VENDEDOR -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">TOTAL VENTAS ASESORADAS</span>
                    <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-dark font-monospace" id="kpiVentasUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" id="kpiVentasBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace"><span id="kpiConteoFacturas">0</span> Facturas</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">TOTAL COMISIONES A PAGAR</span>
                    <div class="avatar-executive-sm rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success font-monospace" id="kpiComisionesUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-success-subtle text-success rounded-pill font-monospace" id="kpiComisionesBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace">Comisión Acumulada</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">VENDEDOR ESTRELLA</span>
                    <div class="avatar-executive-sm rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-trophy"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-0 text-warning-emphasis text-truncate" id="kpiTopVendedor">--</h5>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill font-monospace">Mayor Facturación</span>
                    <small class="text-muted font-monospace">Líder del Período</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">ASESORES ACTIVOS</span>
                    <div class="avatar-executive-sm rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-info font-monospace">{{ $vendedores->count() }}</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-info-subtle text-info rounded-pill font-monospace">Catálogo Empresa</span>
                    <small class="text-muted font-monospace">Registrados</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE FILTROS AVANZADOS -->
    <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
        <div class="row g-2 align-items-end">
            <x-input name="filtroFechaInicio" id="filtroFechaInicio" type="date" label="Desde Fecha" icon="fas fa-calendar-alt" col="col-12 col-md-3" onchange="recargarReportes()" />

            <x-input name="filtroFechaFin" id="filtroFechaFin" type="date" label="Hasta Fecha" icon="fas fa-calendar-alt" col="col-12 col-md-3" onchange="recargarReportes()" />

            <x-select name="filtroVendedor" id="filtroVendedor" label="Filtrar por Vendedor" icon="fas fa-user-tie" col="col-12 col-md-4" onchange="recargarReportes()">
                <option value="">-- Todos los Vendedores --</option>
                @foreach($vendedores as $vend)
                    <option value="{{ $vend->id }}">{{ $vend->nombre }} ({{ $vend->comision_porcentaje }}%)</option>
                @endforeach
            </x-select>

            <div class="col-12 col-md-2 mb-1">
                <button type="button" class="btn btn-outline-secondary w-100 rounded-pill fw-semibold py-2" onclick="limpiarFiltros()">
                    <i class="fas fa-eraser me-1"></i> Limpiar
                </button>
            </div>
        </div>
    </div>

    <!-- 3. NAVEGACIÓN Y TABLAS -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-bottom border-light-subtle p-3 rounded-top-4">
            <ul class="nav nav-pills card-header-pills gap-2" id="reporteTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-semibold px-4" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#tab-resumen" type="button" role="tab">
                        <i class="fas fa-chart-pie me-2"></i>Resumen Consolidado por Vendedor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold px-4" id="facturas-tab" data-bs-toggle="tab" data-bs-target="#tab-facturas" type="button" role="tab">
                        <i class="fas fa-file-invoice me-2"></i>Facturas y Comisiones Detalladas
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            <div class="tab-content" id="reporteTabsContent">
                <!-- Pestaña 1: Resumen -->
                <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla_resumen_vendedores">
                            <thead class="table-light">
                                <tr>
                                    <th>Vendedor / Documento</th>
                                    <th class="text-center">% Comisión</th>
                                    <th class="text-center">Cant. Facturas</th>
                                    <th class="text-end">Total Vendido ($)</th>
                                    <th class="text-end">Total Vendido (Bs.)</th>
                                    <th class="text-end">Comisión Ganada ($)</th>
                                    <th class="text-end">Comisión Ganada (Bs.)</th>
                                </tr>
                            </thead>
                            <tbody id="filasResumenVendedores">
                                <tr><td colspan="7" class="text-center text-muted py-4">Cargando consolidado...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pestaña 2: Facturas -->
                <div class="tab-pane fade" id="tab-facturas" role="tabpanel">
                    <x-datatable
                        id="datatable_facturas_vendedores"
                        :headers="[
                            'Factura / Control',
                            'Fecha / Hora',
                            'Vendedor Asignado',
                            'Cliente',
                            'Total Venta',
                            '% Com.',
                            'Monto Comisión',
                        ]"
                    />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script>
        const urlReporteKpis = "{{ url('/reportes/vendedores/kpis') }}";
        const urlReporteLista = "{{ url('/reportes/vendedores/lista') }}";
        const urlReporteResumen = "{{ url('/reportes/vendedores/resumen') }}";
    </script>
    <script
        src="{{ asset('estilos/jsPropios/reporteVendedores.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/reporteVendedores.js')) ?: time() }}">
    </script>
@endsection
