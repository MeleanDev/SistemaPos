<style>
    .kpi-dash-card {
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        background: #ffffff;
        transition: all 0.25s ease;
        position: relative;
        overflow: hidden;
    }

    .kpi-dash-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.07);
        border-color: #cbd5e1;
    }

    .welcome-banner {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 1.25rem;
        color: white;
        padding: 2.25rem 2rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::after {
        content: '';
        position: absolute;
        right: -50px;
        top: -50px;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }

    .quick-btn {
        transition: all 0.2s ease;
    }

    .quick-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .quick-action-card {
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        background: #f8fafc;
        transition: all 0.2s ease;
        text-decoration: none !important;
        color: inherit;
    }

    .quick-action-card:hover {
        background: #ffffff;
        border-color: #3b82f6;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.08);
    }
</style>

<!-- Banner de Bienvenida y Accesos Rápidos -->
<div class="row mb-4">
    <div class="col-12">
        <div class="welcome-banner d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 64px; height: 64px;">
                    <i class="fas fa-hospital-alt fs-2 text-info"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="fw-bold mb-0 text-white h3">¡Hola, {{ Auth::user()->nombre ?? Auth::user()->name }}!</h2>
                        <span class="badge bg-primary bg-opacity-25 text-white border border-primary border-opacity-25 rounded-pill px-3 py-1 small">
                            <i class="fas fa-notes-medical me-1"></i>{{ Auth::user()->empresa->nombre ?? Auth::user()->empresa->nombre_empresa ?? 'Laboratorio Clínico' }}
                        </span>
                    </div>
                    <p class="mb-0 text-white-50 small">
                        <i class="far fa-calendar-alt me-1"></i> {{ ucfirst(\Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')) }}
                        &bull; <i class="fas fa-heartbeat text-danger me-1"></i> Centro de Operaciones y Control Clínico
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <a href="{{ route('ingreso') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-bold shadow-sm quick-btn">
                    <i class="fas fa-bolt me-1"></i> Nuevo Ingreso / Cobro
                </a>
                <a href="{{ route('ordenes.index') }}" class="btn btn-light rounded-pill px-3 py-2 fw-bold text-dark shadow-sm quick-btn">
                    <i class="fas fa-vial me-1"></i> Órdenes Clínicas
                </a>
                <a href="{{ route('paciente') }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-bold quick-btn">
                    <i class="fas fa-user-plus me-1"></i> Pacientes
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     SECCIÓN 1: KPIS OPERATIVOS PRINCIPALES
     ========================================== -->
<div class="row g-3 mb-4">
    <!-- KPI 1: Órdenes Pendientes -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="{{ route('ordenes.index') }}?estado=Pendiente" class="text-decoration-none">
            <div class="card kpi-dash-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pendientes</span>
                        <h3 class="fw-extrabold text-warning mb-0 font-monospace">{{ number_format($kpi['ordenes_pendientes'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill small px-2 mt-1">
                            <i class="fas fa-clock me-1"></i>Por procesar
                        </span>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-flask fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 2: Órdenes En Proceso -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="{{ route('ordenes.index') }}?estado=En+Proceso" class="text-decoration-none">
            <div class="card kpi-dash-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">En Análisis</span>
                        <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($kpi['ordenes_en_proceso'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                            <i class="fas fa-vial me-1"></i>En laboratorio
                        </span>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-vials fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 3: Completadas Hoy -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="{{ route('ordenes.index') }}?estado=Completada" class="text-decoration-none">
            <div class="card kpi-dash-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Completadas Hoy</span>
                        <h3 class="fw-extrabold text-success mb-0 font-monospace">{{ number_format($kpi['ordenes_completadas_hoy'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                            <i class="fas fa-check-circle me-1"></i>Resultados listos
                        </span>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <!-- KPI 4: Pacientes -->
    <div class="col-12 col-sm-6 col-xl">
        <a href="{{ route('paciente') }}" class="text-decoration-none">
            <div class="card kpi-dash-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pacientes</span>
                        <h3 class="fw-extrabold text-dark mb-0 font-monospace">{{ number_format($kpi['total_pacientes'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                            <i class="fas fa-users me-1"></i>+{{ $kpi['pacientes_mes'] ?? 0 }} este mes
                        </span>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    @if (Auth::user()->can('ver_ingresos_dashboard') || Auth::user()->hasRole('Admin'))
        <!-- KPI 5: Facturación Hoy -->
        <div class="col-12 col-sm-6 col-xl">
            <a href="{{ route('historial') }}" class="text-decoration-none">
                <div class="card kpi-dash-card h-100 shadow-sm p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Ingresos Hoy</span>
                            <h3 class="fw-extrabold text-success mb-0 font-monospace">$ {{ number_format($kpi['ingresos_hoy_usd'] ?? 0, 2, ',', '.') }}</h3>
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                                <i class="fas fa-dollar-sign me-1"></i>Bs. {{ number_format($kpi['ingresos_hoy_bs'] ?? 0, 2, ',', '.') }}
                            </span>
                        </div>
                        <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="fas fa-dollar-sign fs-4"></i>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endif
</div>

<!-- ==========================================
     SECCIÓN 2: GRÁFICOS Y ANALÍTICA OPERATIVA
     ========================================== -->
<div class="row g-4 mb-4">
    <!-- Gráfico 1: Actividad y Atenciones de los Últimos 7 Días -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-chart-area text-primary me-2"></i>Atención y Flujo de Órdenes (Últimos 7 Días)
                    </h5>
                    <p class="text-muted small mb-0">Comparativa de ingresos de órdenes vs resultados completados</p>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-1 small fw-bold">
                    <i class="fas fa-sync-alt me-1"></i>Tiempo Real
                </span>
            </div>
            <div style="position: relative; height: 260px;">
                <canvas id="chartTendenciaSemanal"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico 2: Distribución de Carga de Trabajo -->
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-chart-pie text-info me-2"></i>Estado del Laboratorio
                    </h5>
                    <p class="text-muted small mb-0">Distribución de carga activa</p>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-center" style="position: relative; height: 210px;">
                <canvas id="chartEstadoOrdenes"></canvas>
            </div>
            <div class="d-flex justify-content-around text-center pt-3 border-top mt-2">
                <div>
                    <span class="text-warning fw-bold d-block small"><i class="fas fa-circle me-1" style="font-size: 8px;"></i>Pendientes</span>
                    <span class="fw-bold font-monospace">{{ $kpi['distribucion_estados']['pendientes'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-primary fw-bold d-block small"><i class="fas fa-circle me-1" style="font-size: 8px;"></i>En Proceso</span>
                    <span class="fw-bold font-monospace">{{ $kpi['distribucion_estados']['en_proceso'] ?? 0 }}</span>
                </div>
                <div>
                    <span class="text-success fw-bold d-block small"><i class="fas fa-circle me-1" style="font-size: 8px;"></i>Completadas</span>
                    <span class="fw-bold font-monospace">{{ $kpi['distribucion_estados']['completadas'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     SECCIÓN 3: ÓRDENES RECIENTES Y ACCESOS
     ========================================== -->
<div class="row g-4 mb-4">
    <!-- Tabla de Últimas Órdenes Registradas -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm rounded-4 h-100 p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-list-alt text-primary me-2"></i>Últimas Órdenes Ingresadas
                    </h5>
                    <p class="text-muted small mb-0">Monitoreo rápido de las órdenes recibidas más recientes</p>
                </div>
                <a href="{{ route('ordenes.index') }}" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                    Ver Todas <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center small text-muted text-uppercase">
                            <th class="border-0 rounded-start text-start">Orden / Factura</th>
                            <th class="border-0 text-start">Paciente</th>
                            <th class="border-0 text-center">Exámenes</th>
                            <th class="border-0 text-center">Estado</th>
                            <th class="border-0 text-center">Fecha</th>
                            <th class="border-0 rounded-end text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kpi['ultimas_ordenes'] ?? [] as $orden)
                            <tr>
                                <td class="text-start">
                                    <span class="fw-bold text-dark font-monospace d-block">{{ $orden->codigo ?? 'ORD-'.$orden->id }}</span>
                                    <span class="badge bg-light text-muted border small">{{ $orden->factura->correlativo ?? 'FAC-'.$orden->id }}</span>
                                </td>
                                <td class="text-start">
                                    @php
                                        $paciente = $orden->paciente;
                                        $nombrePac = $paciente ? trim($paciente->nombreUno.' '.$paciente->apellidoUno) : 'N/A';
                                    @endphp
                                    <span class="fw-bold text-dark d-block">{{ $nombrePac }}</span>
                                    <span class="text-muted small font-monospace"><i class="far fa-id-card me-1"></i>{{ $paciente->cedula ?? 'S/C' }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border rounded-pill px-2 py-1">
                                        <i class="fas fa-vial text-primary me-1"></i>{{ $orden->detalles->count() }} pruebas
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($orden->estado === 'Pendiente')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-2 py-1 small fw-bold">
                                            <i class="fas fa-clock me-1"></i>Pendiente
                                        </span>
                                    @elseif($orden->estado === 'En Proceso')
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-2 py-1 small fw-bold">
                                            <i class="fas fa-vial me-1"></i>En Proceso
                                        </span>
                                    @elseif($orden->estado === 'Completada')
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-2 py-1 small fw-bold">
                                            <i class="fas fa-check-circle me-1"></i>Completada
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border rounded-pill px-2 py-1 small">
                                            {{ $orden->estado }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center small text-muted">
                                    <span class="d-block">{{ $orden->created_at->format('d/m/Y') }}</span>
                                    <span class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $orden->created_at->format('h:i A') }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('ordenes.procesar', $orden->id) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs fw-bold">
                                        <i class="fas fa-edit me-1"></i> Procesar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block text-muted opacity-50"></i>
                                    No hay órdenes registradas recientemente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Panel Lateral: Tasa del Día, Insumos y Accesos Rápidos -->
    <div class="col-12 col-xl-4">
        <div class="d-flex flex-column gap-3">
            <!-- Tarjeta Tasa del Día -->
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle">
                            <i class="fas fa-dollar-sign fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.7rem; letter-spacing: 0.5px;">Tasa de Cambio Oficial</span>
                            <h4 class="fw-extrabold text-dark mb-0 font-monospace">
                                Bs. {{ number_format($kpi['tasa_actual'] ?? 0, 2, ',', '.') }}
                            </h4>
                        </div>
                    </div>
                    <a href="{{ route('configuracion') }}" class="btn btn-light border rounded-pill btn-sm px-3 fw-bold" title="Configurar Tasa">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </div>

            <!-- Alerta Insumos Críticos -->
            @if(($kpi['insumos_bajo_stock'] ?? 0) > 0)
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-warning bg-opacity-10 border-start border-warning border-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Reactivos con Bajo Stock</h6>
                                <p class="text-muted small mb-0">Hay <strong>{{ $kpi['insumos_bajo_stock'] }}</strong> reactivos con cantidad crítica.</p>
                            </div>
                        </div>
                        <a href="{{ route('inventario') }}" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark">
                            Revisar
                        </a>
                    </div>
                </div>
            @endif

            <!-- Tarjeta de Accesos Directos Operativos -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold text-dark mb-3">
                    <i class="fas fa-th text-primary me-2"></i>Accesos Rápidos del Laboratorio
                </h6>
                <div class="row g-2">
                    <div class="col-6">
                        <a href="{{ route('ingreso') }}" class="quick-action-card p-3 d-flex flex-column align-items-center text-center">
                            <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-circle mb-2" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <span class="fw-bold small text-dark">Punto de Venta</span>
                            <span class="text-muted" style="font-size: 0.7rem;">Facturación</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('ordenes.index') }}" class="quick-action-card p-3 d-flex flex-column align-items-center text-center">
                            <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle mb-2" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-vial"></i>
                            </div>
                            <span class="fw-bold small text-dark">Resultados</span>
                            <span class="text-muted" style="font-size: 0.7rem;">Carga técnica</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('historial') }}" class="quick-action-card p-3 d-flex flex-column align-items-center text-center">
                            <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle mb-2" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-history"></i>
                            </div>
                            <span class="fw-bold small text-dark">Caja e Historial</span>
                            <span class="text-muted" style="font-size: 0.7rem;">Cobros y abonos</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('reportes') }}" class="quick-action-card p-3 d-flex flex-column align-items-center text-center">
                            <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-circle mb-2" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <span class="fw-bold small text-dark">Estadísticas</span>
                            <span class="text-muted" style="font-size: 0.7rem;">Informes analíticos</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

