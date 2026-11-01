@extends('Sistema.layouts.app')

@section('titulo', '📊 Reporte de Ventas y Comisiones por Vendedor')
@section('subtitulo', 'Auditoría comercial de asesores de ventas, facturación atribuida, comisiones ganadas y liquidación de incentivos')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <a href="{{ route('reportes') }}">Informes & Auditoría</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Ventas & Comisiones</span>
@endsection

@section('acciones')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <x-button
            variant="outline-dark"
            icon="fas fa-file-pdf"
            iconColor="text-danger"
            text="Ver PDF"
            onclick="exportarReporte('pdf_ver')"
        />
        <x-button
            variant="outline-danger"
            icon="fas fa-download"
            text="Descargar PDF"
            onclick="exportarReporte('pdf_descargar')"
        />
        <x-button
            variant="outline-success"
            icon="fas fa-file-excel"
            text="Excel"
            onclick="exportarReporte('excel')"
        />
        <x-btn-action
            href="{{ route('vendedor') }}"
            icon="fas fa-user-tie"
            text="Catálogo de Vendedores"
        />
    </div>
@endsection

@section('contenido')
<div class="container-fluid px-0">

    <!-- BANNER EJECUTIVO CORPORATIVO -->
    <div class="card border-0 rounded-4 shadow-sm text-white mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white-50 small mb-2">
                        <i class="fas fa-award text-warning"></i>
                        <span>Módulo de Liquidación Comercial & Comisiones</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-white">Rendimiento de Ventas & Comisiones</h3>
                    <p class="text-white-50 mb-0">
                        Auditoría en tiempo real del desempeño de su fuerza de ventas, cálculo transparente de comisiones ganadas y liquidación oficial de incentivos comerciales.
                    </p>
                </div>
                <div class="col-12 col-lg-4 text-start text-lg-end">
                    <div class="d-inline-flex flex-column align-items-start align-items-lg-end bg-white bg-opacity-10 p-3 rounded-4">
                        <span class="text-white-50 small fw-semibold text-uppercase">Tasa de Cambio Oficial</span>
                        <span class="fs-5 fw-bold text-white font-monospace">
                            <i class="fas fa-chart-line text-primary me-1"></i><span id="bannerTasa">0.00</span> Bs/$
                        </span>
                        <small class="text-white-50 mt-1">
                            <i class="fas fa-users text-info me-1"></i><span id="bannerConteoAsesores">0</span> Asesores Registrados
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. KPIS Y TARJETAS RESUMEN DE VENTAS POR ASESOR -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">TOTAL FACTURADO</span>
                    <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-shopping-bag fs-6"></i>
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
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">COMISIONES GANADAS</span>
                    <div class="avatar-executive-sm rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-hand-holding-dollar fs-6"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-success font-monospace" id="kpiComisionesUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-success-subtle text-success rounded-pill font-monospace" id="kpiComisionesBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace">Incentivos a Liquidar</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">ASESOR LÍDER (TOP)</span>
                    <div class="avatar-executive-sm rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-trophy fs-6"></i>
                    </div>
                </div>
                <h5 class="fw-bold mb-0 text-warning-emphasis text-truncate" id="kpiTopVendedor" title="Líder en ventas">--</h5>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill font-monospace">Mayor Facturación</span>
                    <small class="text-muted font-monospace">Líder del Período</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">FUERZA DE VENTAS</span>
                    <div class="avatar-executive-sm rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-users-gear fs-6"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-info font-monospace" id="kpiAsesores">0</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-info-subtle text-info rounded-pill font-monospace">Catálogo Activo</span>
                    <small class="text-muted font-monospace">Asesores Asignados</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE FILTROS AVANZADOS & ACCESOS RÁPIDOS -->
    <div class="card border rounded-4 shadow-xs bg-white p-3 p-md-4 mb-4">
        <x-section-header
            title="Parámetros de Auditoría & Período"
            description="Seleccione el rango de fechas y asesor para calcular ventas, facturas atribuidas y comisiones correspondientes"
            icon="fas fa-filter"
            class="mb-3 border-0 px-0 pt-0"
        >
            <div class="btn-group btn-group-sm rounded-pill shadow-xs overflow-hidden" role="group" aria-label="Filtros rápidos de fecha">
                <button type="button" class="btn btn-outline-secondary fw-semibold btn-periodo" data-periodo="hoy" onclick="establecerRangoRapido('hoy')">Hoy</button>
                <button type="button" class="btn btn-outline-secondary fw-semibold btn-periodo" data-periodo="semana" onclick="establecerRangoRapido('semana')">Esta Semana</button>
                <button type="button" class="btn btn-primary fw-semibold btn-periodo active" data-periodo="mes" onclick="establecerRangoRapido('mes')">Este Mes</button>
                <button type="button" class="btn btn-outline-secondary fw-semibold btn-periodo" data-periodo="mes_anterior" onclick="establecerRangoRapido('mes_anterior')">Mes Anterior</button>
                <button type="button" class="btn btn-outline-secondary fw-semibold btn-periodo" data-periodo="ano" onclick="establecerRangoRapido('ano')">Año Actual</button>
            </div>
        </x-section-header>

        <div class="row g-3 align-items-end">
            <x-input
                name="filtroFechaInicio"
                id="filtroFechaInicio"
                type="date"
                label="Desde Fecha"
                icon="fas fa-calendar-alt text-secondary"
                col="col-12 col-sm-6 col-md-3"
                onchange="recargarReportes()"
            />

            <x-input
                name="filtroFechaFin"
                id="filtroFechaFin"
                type="date"
                label="Hasta Fecha"
                icon="fas fa-calendar-alt text-secondary"
                col="col-12 col-sm-6 col-md-3"
                onchange="recargarReportes()"
            />

            <x-select2
                name="filtroVendedor"
                id="filtroVendedor"
                label="Filtrar por Asesor"
                icon="fas fa-user-tie text-secondary"
                placeholder="-- Todos los Asesores --"
                col="col-12 col-md-4"
            >
                <option value="">-- Todos los Asesores --</option>
            </x-select2>

            <div class="col-12 col-md-2">
                <div class="d-flex gap-2">
                    <x-button
                        type="button"
                        variant="primary"
                        icon="fas fa-search"
                        text="Filtrar"
                        class="w-100"
                        onclick="recargarReportes()"
                    />
                    <x-button
                        type="button"
                        variant="outline-secondary"
                        icon="fas fa-eraser"
                        text=""
                        title="Limpiar Filtros"
                        onclick="limpiarFiltros()"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- 3. COMPARATIVA VISUAL DE VENTAS Y COMISIONES (CHART.JS) -->
    <div class="card border rounded-4 shadow-xs bg-white p-3 p-md-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="fas fa-chart-column"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Comparativa de Facturación vs Comisiones</h6>
                    <small class="text-muted">Distribución de ingresos generados y comisiones por cada asesor comercial</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="d-inline-flex align-items-center gap-1.5 small fw-semibold text-secondary">
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #4f46e5;"></span> Ventas ($)
                </span>
                <span class="d-inline-flex align-items-center gap-1.5 small fw-semibold text-secondary">
                    <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: #10b981;"></span> Comisiones ($)
                </span>
            </div>
        </div>
        <div style="height: 250px; position: relative;">
            <canvas id="graficoVendedores"></canvas>
            <div id="graficoVacio" class="position-absolute top-50 start-50 translate-middle text-center text-muted d-none">
                <i class="fas fa-chart-pie fs-1 mb-2 text-secondary opacity-50"></i>
                <p class="mb-0 small fw-semibold">No hay suficientes datos de facturación para el período seleccionado</p>
            </div>
        </div>
    </div>

    <!-- 4. PESTAÑAS DE TABLAS DE DATOS -->
    <div class="card border rounded-4 p-2 shadow-xs mb-4 bg-white">
        <ul class="nav nav-pills gap-2" id="reporteTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-semibold px-4" id="resumen-tab" data-bs-toggle="pill" data-bs-target="#tab-resumen" type="button" role="tab">
                    <i class="fas fa-chart-pie me-2"></i>Resumen Consolidado por Asesor
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-semibold px-4" id="facturas-tab" data-bs-toggle="pill" data-bs-target="#tab-facturas" type="button" role="tab">
                    <i class="fas fa-file-invoice me-2"></i>Facturas & Comisiones Detalladas
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content mb-4" id="reporteTabsContent">

                <!-- Pestaña 1: Resumen Consolidado -->
                <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">
                    <x-table-dynamic
                        id="tabla_resumen_vendedores"
                        bodyId="filasResumenVendedores"
                        :headers="[
                            ['label' => 'Asesor Comercial / Documento', 'width' => '24%'],
                            ['label' => 'Contacto', 'width' => '18%'],
                            ['label' => '% Comisión', 'class' => 'text-center', 'width' => '10%'],
                            ['label' => 'Cant. Facturas', 'class' => 'text-center', 'width' => '10%'],
                            ['label' => 'Total Vendido ($ / Bs.)', 'class' => 'text-end', 'width' => '16%'],
                            ['label' => 'Comisión Ganada ($ / Bs.)', 'class' => 'text-end', 'width' => '16%'],
                            ['label' => 'Acción', 'class' => 'text-center', 'width' => '6%'],
                        ]"
                    >
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Cargando consolidado de asesores...
                            </td>
                        </tr>
                    </x-table-dynamic>
                </div>

                <!-- Pestaña 2: Facturas Detalladas -->
                <div class="tab-pane fade" id="tab-facturas" role="tabpanel">
                    <x-datatable
                        id="datatable_facturas_vendedores"
                        theadClass="bg-dark text-white text-center"
                        :headers="[
                            'Factura / Control',
                            'Fecha / Hora',
                            'Asesor Asignado',
                            'Cliente',
                            'Total Venta',
                            '% Com.',
                            'Monto Comisión',
                            'Acciones',
                        ]"
                    />
                </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script>
        const urlReporteCatalogos = "{{ url('/reportes/vendedores/catalogos') }}";
        const urlReporteKpis = "{{ url('/reportes/vendedores/kpis') }}";
        const urlReporteLista = "{{ url('/reportes/vendedores/lista') }}";
        const urlReporteResumen = "{{ url('/reportes/vendedores/resumen') }}";
        const urlExportarPdf = "{{ route('reportes.vendedores.pdf') }}";
        const urlExportarExcel = "{{ route('reportes.vendedores.excel') }}";
        const urlImprimirTicket = "{{ url('/pos/imprimir-ticket') }}";
        const urlImprimirCarta = "{{ url('/pos/imprimir-carta') }}";
    </script>
    <script
        src="{{ asset('estilos/jsPropios/reporteVendedores.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/reporteVendedores.js')) ?: time() }}">
    </script>
@endsection
