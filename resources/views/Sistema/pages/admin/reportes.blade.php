@extends('Sistema.layouts.app')

@section('titulo', 'Reportes y Estadísticas')
@section('subtitulo', 'Genera y exporta balances financieros, operativos y médicos')

@section('contenido')
    <style>
        .card-reporte {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .card-reporte:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08) !important;
            border-color: #cbd5e1;
        }

        .card-reporte .card-accent-bar {
            height: 4px;
            width: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        .report-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: transform 0.2s ease;
        }

        .card-reporte:hover .report-icon-box {
            transform: scale(1.08);
        }

        .date-input-group {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.85rem;
            padding: 0.6rem 0.8rem;
            transition: all 0.2s ease;
        }

        .date-input-group:focus-within {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .report-filter-pill {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 0.4rem 1rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
        }

        .report-filter-pill:hover,
        .report-filter-pill.active {
            background: #0d6efd;
            border-color: #0d6efd;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
        }

        .switch-historial-pill {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 50rem;
            padding: 0.45rem 1rem;
            transition: all 0.2s ease;
        }

        .switch-historial-pill:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }
        .kpi-stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }

        .kpi-stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .chart-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .select2-container--default .select2-selection--single {
            border-radius: 50rem !important;
            height: 38px !important;
            border: 1px solid #cbd5e1 !important;
            background-color: #f8fafc !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 12px !important;
            transition: all 0.2s ease;
        }

        .select2-container--default.select2-container--open .select2-selection--single,
        .select2-container--default.select2-container--focus .select2-selection--single {
            background-color: #ffffff !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            padding-left: 4px !important;
            line-height: normal !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px !important;
            right: 10px !important;
        }

        .select2-dropdown {
            border-radius: 14px !important;
            border: 1px solid #cbd5e1 !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1) !important;
            font-size: 0.85rem !important;
            overflow: hidden !important;
            z-index: 1060 !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd !important;
        }
    </style>

    <!-- Encabezado de la página -->
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Centro de Análisis y Reportes</h2>
                    <p class="text-muted mb-0 small">Métricas ejecutivas en tiempo real y generador de balances exportables</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <!-- Píldoras de Filtro Rápido de Categoría -->
            <div class="d-flex flex-wrap gap-2" id="filtro-categorias">
                <button type="button" class="report-filter-pill active" data-categoria="todos">
                    <i class="fas fa-th-large me-1"></i> Todos los Reportes (9)
                </button>
                <button type="button" class="report-filter-pill" data-categoria="financiero">
                    <i class="fas fa-dollar-sign me-1"></i> Financieros
                </button>
                <button type="button" class="report-filter-pill" data-categoria="clinico">
                    <i class="fas fa-flask me-1"></i> Clínicos y Pacientes
                </button>
                <button type="button" class="report-filter-pill" data-categoria="operativo">
                    <i class="fas fa-boxes me-1"></i> Operativo / Kardex
                </button>
            </div>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 1: KPIS EJECUTIVOS EN TIEMPO REAL
         ========================================== -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Ingresos del Mes -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-stat-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Ingresos del Mes</span>
                        @if($analytics['puedeVerFinanzas'])
                            <h3 class="fw-extrabold text-success mb-0 font-monospace">${{ number_format($analytics['ingresosMes'], 2, ',', '.') }}</h3>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-monospace small px-2 mt-1">
                                <i class="fas fa-arrow-up me-1"></i>Cobrado
                            </span>
                        @else
                            <h4 class="fw-bold text-muted mb-0">Protegido</h4>
                            <span class="badge bg-light text-muted rounded-pill small mt-1">Sin acceso financiero</span>
                        @endif
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-dollar-sign fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Por Cobrar (Morosidad) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-stat-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cuentas por Cobrar</span>
                        @if($analytics['puedeVerFinanzas'])
                            <h3 class="fw-extrabold text-danger mb-0 font-monospace">${{ number_format($analytics['cuentasPorCobrar'], 2, ',', '.') }}</h3>
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-monospace small px-2 mt-1">
                                <i class="fas fa-clock me-1"></i>Pendiente
                            </span>
                        @else
                            <h4 class="fw-bold text-muted mb-0">Protegido</h4>
                            <span class="badge bg-light text-muted rounded-pill small mt-1">Sin acceso financiero</span>
                        @endif
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-calculator fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Órdenes Procesadas este Mes -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-stat-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Órdenes Atendidas</span>
                        <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($analytics['totalOrdenesMes'], 0, ',', '.') }}</h3>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                            <i class="fas fa-check-circle me-1"></i>Este Mes
                        </span>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-flask fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Nuevos Pacientes en el Mes -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-stat-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Nuevos Pacientes</span>
                        <h3 class="fw-extrabold text-dark mb-0 font-monospace">{{ number_format($analytics['nuevosPacientesMes'], 0, ',', '.') }}</h3>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                            <i class="fas fa-user-plus me-1"></i>Registrados
                        </span>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 2: GRÁFICAS INTERACTIVAS DE TENDENCIA
         ========================================== -->
    <div class="row g-4 mb-5">
        <!-- Gráfica 1: Tendencia últimos 7 días -->
        <div class="col-12 col-lg-8">
            <div class="card chart-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-chart-line text-primary"></i> Tendencia de Actividad (Últimos 7 Días)
                        </h6>
                        <small class="text-muted">Comparativa de ingresos diarios vs. volumen de órdenes atendidas</small>
                    </div>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="chartTendenciaSemanal"></canvas>
                </div>
            </div>
        </div>

        <!-- Gráfica 2: Top 5 Exámenes Más Demandados -->
        <div class="col-12 col-lg-4">
            <div class="card chart-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                            <i class="fas fa-vials text-warning"></i> Exámenes Top Demanda
                        </h6>
                        <small class="text-muted">Pruebas más solicitadas (30 días)</small>
                    </div>
                </div>
                <div style="position: relative; height: 260px; width: 100%; display: flex; align-items: center; justify-content: center;">
                    <canvas id="chartTopExamenes"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Separador y Título de Catálogo de Reportes -->
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
            <i class="fas fa-file-export text-primary"></i> Generador y Exportador de Reportes Oficiales
        </h5>
        <span class="text-muted small">Selecciona el reporte, ajusta los filtros de fecha y presiona <strong>PDF</strong> o <strong>Excel</strong></span>
    </div>

    <!-- Grid de Reportes -->
    <div class="row g-4 justify-content-start" id="grid-reportes">

        <!-- ==========================================
             TARJETA 1: REPORTE FINANCIERO (INGRESOS)
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="financiero">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #10b981, #059669);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-success bg-opacity-10 text-success shadow-xs">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Ingresos y Caja</h5>
                    <p class="text-muted small mb-3">Análisis de facturación, pagos en divisas y bolívares.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="ingresos">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoIngresos" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-success shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoIngresos" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 2: CUENTAS POR COBRAR
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="financiero">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #ef4444, #dc2626);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-danger bg-opacity-10 text-danger shadow-xs">
                            <i class="fas fa-calculator"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Cuentas por Cobrar</h5>
                    <p class="text-muted small mb-3">Reporte de morosidad y facturas pendientes por liquidar.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="cuentas_por_cobrar">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoCobrar" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-danger shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoCobrar" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 3: FACTURAS EN LOTE (CONTABILIDAD)
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="financiero">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #0ea5e9, #0284c7);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-info bg-opacity-10 text-info shadow-xs">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Lote de Facturas</h5>
                    <p class="text-muted small mb-3">Impresión masiva y consecutiva para auditoría fiscal.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="facturas_lote">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoFacturas" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-info shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoFacturas" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-12">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-primary btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-print"></i> Generar Lote PDF
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 4: ÓRDENES DE SERVICIO
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="clinico">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #6366f1, #4f46e5);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-primary bg-opacity-10 text-primary shadow-xs">
                            <i class="fas fa-notes-medical"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Órdenes de Servicio</h5>
                    <p class="text-muted small mb-3">Historial de órdenes atendidas, estados y pacientes.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="ordenes">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoOrdenes" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-primary shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoOrdenes" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 5: EXÁMENES MÁS SOLICITADOS
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="clinico">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #f59e0b, #d97706);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-warning bg-opacity-10 text-warning shadow-xs">
                            <i class="fas fa-vials"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Exámenes Populares</h5>
                    <p class="text-muted small mb-3">Ranking estadístico de pruebas clínicas y demanda.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="examenes_populares">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoEx" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-warning shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoEx" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 6: PRODUCTIVIDAD BIOANALISTAS
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="clinico">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #8b5cf6, #7c3aed);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box shadow-xs" style="background-color: rgba(139, 92, 246, 0.1); color: #7c3aed;">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Productividad Médica</h5>
                    <p class="text-muted small mb-3">Volumen de análisis y órdenes validadas por profesional.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="productividad_bioanalistas">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoBio" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-purple shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoBio" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 7: PACIENTES Y DEMOGRAFÍA
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="clinico">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #3b82f6, #1d4ed8);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-primary bg-opacity-10 text-primary shadow-xs">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Directorio Pacientes</h5>
                    <p class="text-muted small mb-3">Padrón demográfico filtrado por sexo, edad o zona.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="pacientes">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoPacientes" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-primary shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoPacientes" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-2">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Filtros Adicionales Desplegables -->
                        <div class="mb-3">
                            <a class="btn btn-sm btn-light border rounded-pill w-100 fw-bold small text-secondary d-flex align-items-center justify-content-between px-3"
                                data-bs-toggle="collapse" href="#filtrosAvanzadosPacientes" role="button" aria-expanded="false">
                                <span><i class="fas fa-filter me-1 text-primary"></i> Filtros Demográficos</span>
                                <i class="fas fa-angle-down"></i>
                            </a>
                            <div class="collapse mt-2 bg-light p-3 rounded-4 border" id="filtrosAvanzadosPacientes">
                                <div class="mb-2">
                                    <label class="form-label text-muted small fw-bold mb-1" style="font-size: 0.7rem;">Sexo</label>
                                    <select name="sexo" class="form-select form-select-sm rounded-pill bg-white border">
                                        <option value="">Ambos (Todos)</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Femenino">Femenino</option>
                                    </select>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label text-muted small fw-bold mb-1" style="font-size: 0.7rem;">Edad Mín</label>
                                        <input type="number" name="edad_min" class="form-control form-control-sm rounded-pill bg-white border text-center" placeholder="18" min="0">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted small fw-bold mb-1" style="font-size: 0.7rem;">Edad Máx</label>
                                        <input type="number" name="edad_max" class="form-control form-control-sm rounded-pill bg-white border text-center" placeholder="65" min="0">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label text-muted small fw-bold mb-1" style="font-size: 0.7rem;">Dirección / Zona</label>
                                    <input type="text" name="direccion" class="form-control form-control-sm rounded-pill bg-white border" placeholder="Ej: Centro">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 8: KARDEX DE INVENTARIO
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="operativo">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #f97316, #ea580c);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-warning bg-opacity-10 text-warning shadow-xs" style="color: #ea580c !important;">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Kardex de Inventario</h5>
                    <p class="text-muted small mb-3">Trazabilidad de entradas, salidas y ajustes de insumos.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="kardex">

                        <div class="switch-historial-pill d-flex align-items-center justify-content-between mb-3">
                            <label class="form-check-label text-dark small fw-bold mb-0" for="chkTodoKardex" style="cursor: pointer;">
                                <i class="fas fa-history text-muted me-1"></i> Todo el historial
                            </label>
                            <div class="form-check form-switch m-0 p-0">
                                <input class="form-check-input chk-todo border-warning shadow-none m-0" type="checkbox"
                                    name="todo_historial" value="1" id="chkTodoKardex" style="cursor: pointer;">
                            </div>
                        </div>

                        <div class="date-input-group mb-4">
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Desde</label>
                                    <input type="date" name="fecha_inicio"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Hasta</label>
                                    <input type="date" name="fecha_fin"
                                        class="form-control form-control-sm rounded-pill px-2 text-center inp-fecha bg-white border"
                                        value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ==========================================
             TARJETA 9: REPORTE DE EXÁMENES POR ÁREA
             ========================================== -->
        <div class="col-12 col-md-6 col-xl-3 item-reporte" data-cat="clinico">
            <div class="card card-reporte h-100 shadow-sm">
                <div class="card-accent-bar" style="background: linear-gradient(90deg, #0284c7, #0369a1);"></div>

                <div class="card-body p-4 text-center d-flex flex-column">
                    <div class="mb-3 mt-1">
                        <div class="report-icon-box bg-info bg-opacity-10 text-info shadow-xs">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-1">Exámenes por Área</h5>
                    <p class="text-muted small mb-3">Listado oficial de pruebas clínicas registradas por especialidad.</p>

                    <form action="{{ route('reportes.exportar') }}" method="POST" target="_blank"
                        class="form-reporte flex-grow-1 d-flex flex-column text-start mt-auto">
                        @csrf
                        <input type="hidden" name="tipo_reporte" value="catalogo_examenes">
                        <input type="hidden" name="todo_historial" value="1">

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase mb-2" style="font-size: 0.65rem; letter-spacing: 0.5px;">
                                <i class="fas fa-tags me-1 text-primary"></i> Área / Categoría
                            </label>
                            <select name="categoria_id" class="form-select select2-categoria" style="width: 100%;">
                                <option value="">Todas las Áreas (Catálogo Completo)</option>
                                @if(isset($categorias))
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="row g-2 mt-auto">
                            <div class="col-6">
                                <button type="submit" name="formato" value="pdf"
                                    class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="formato" value="excel"
                                    class="btn btn-success btn-sm w-100 rounded-pill fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1 text-white">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    @include('Sistema.components.select2')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        $(document).ready(function() {
            if ($.fn.select2) {
                $('.select2-categoria').select2({
                    placeholder: 'Todas las Áreas (Catálogo Completo)',
                    allowClear: true,
                    width: '100%'
                });
            }

            @if(session('error'))
                Swal.fire({
                    icon: 'error',
                    title: 'Operación denegada',
                    text: '{{ session('error') }}',
                    confirmButtonColor: '#0d6efd'
                });
            @endif

            // Inicializar Gráfica 1: Tendencia últimos 7 días
            let ctxTendencia = document.getElementById('chartTendenciaSemanal');
            if (ctxTendencia) {
                new Chart(ctxTendencia, {
                    type: 'line',
                    data: {
                        labels: @json($analytics['labelsDias']),
                        datasets: [
                            @if($analytics['puedeVerFinanzas'])
                            {
                                label: 'Ingresos ($)',
                                data: @json($analytics['dataIngresos7Dias']),
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#10b981',
                                pointRadius: 4,
                                yAxisID: 'y'
                            },
                            @endif
                            {
                                label: 'Órdenes Atendidas',
                                data: @json($analytics['dataOrdenes7Dias']),
                                borderColor: '#0d6efd',
                                backgroundColor: 'rgba(13, 110, 253, 0.08)',
                                borderWidth: 3,
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#0d6efd',
                                pointRadius: 4,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    boxWidth: 12,
                                    font: { family: 'inherit', weight: 'bold', size: 11 }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false }
                            },
                            @if($analytics['puedeVerFinanzas'])
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: { display: true, text: 'USD ($)', font: { size: 10, weight: 'bold' } },
                                grid: { color: 'rgba(0, 0, 0, 0.04)' }
                            },
                            @endif
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: { display: true, text: 'Órdenes', font: { size: 10, weight: 'bold' } },
                                grid: { display: false },
                                ticks: { precision: 0 }
                            }
                        }
                    }
                });
            }

            // Inicializar Gráfica 2: Top Exámenes Más Demandados
            let ctxTop = document.getElementById('chartTopExamenes');
            if (ctxTop) {
                let labelsTop = @json($analytics['topExamenesLabels']);
                let dataTop = @json($analytics['topExamenesData']);

                if (labelsTop.length === 0) {
                    labelsTop = ['Sin datos'];
                    dataTop = [1];
                }

                new Chart(ctxTop, {
                    type: 'doughnut',
                    data: {
                        labels: labelsTop,
                        datasets: [{
                            data: dataTop,
                            backgroundColor: [
                                '#3b82f6',
                                '#10b981',
                                '#f59e0b',
                                '#8b5cf6',
                                '#ec4899',
                                '#94a3b8'
                            ],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10 }
                                }
                            }
                        },
                        cutout: '68%'
                    }
                });
            }

            // Filtros de categorías (Pestañas/Pills de arriba)
            $('.report-filter-pill').on('click', function() {
                $('.report-filter-pill').removeClass('active');
                $(this).addClass('active');

                let cat = $(this).data('categoria');
                if (cat === 'todos') {
                    $('.item-reporte').fadeIn(200);
                } else {
                    $('.item-reporte').hide();
                    $(`.item-reporte[data-cat="${cat}"]`).fadeIn(200);
                }
            });

            // Switch "Todo el historial"
            $('.chk-todo').on('change', function() {
                let form = $(this).closest('form');
                if ($(this).is(':checked')) {
                    form.find('.inp-fecha').prop('disabled', true).addClass('opacity-50');
                } else {
                    form.find('.inp-fecha').prop('disabled', false).removeClass('opacity-50');
                }
            });

            // Validación de fechas y formato al enviar
            $('.form-reporte').on('submit', function(e) {
                let $form = $(this);
                let tipoReporte = $form.find('input[name="tipo_reporte"]').val();
                let formato = e.originalEvent && e.originalEvent.submitter ? e.originalEvent.submitter.value : 'pdf';
                let todoHistorial = $form.find('.chk-todo').is(':checked') || tipoReporte === 'catalogo_examenes';

                if (tipoReporte === 'catalogo_examenes') {
                    $form.find('input[name="formato"]').remove();
                    $form.append('<input type="hidden" name="formato" value="' + formato + '">');
                    return true;
                }

                if (!todoHistorial) {
                    let inicioVal = $form.find('input[name="fecha_inicio"]').val();
                    let finVal = $form.find('input[name="fecha_fin"]').val();

                    if (!inicioVal || !finVal) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Fechas requeridas',
                            text: 'Por favor seleccione las fechas de inicio y fin, o active la opción "Todo el historial".',
                            confirmButtonColor: '#0d6efd'
                        });
                        return false;
                    }

                    let inicio = new Date(inicioVal);
                    let fin = new Date(finVal);

                    if (fin < inicio) {
                        e.preventDefault();
                        Swal.fire({
                            icon: 'error',
                            title: 'Rango inválido',
                            text: 'La fecha de fin no puede ser anterior a la fecha de inicio.',
                            confirmButtonColor: '#0d6efd'
                        });
                        return false;
                    }
                }

                // Añadir campo oculto con el formato real que se presionó
                $form.find('input[name="formato"]').remove();
                $form.append('<input type="hidden" name="formato" value="' + formato + '">');
            });
        });
    </script>
@endsection
