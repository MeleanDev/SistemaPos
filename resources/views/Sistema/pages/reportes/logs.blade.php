@extends('Sistema.layouts.app')

@section('titulo', 'Auditoría y Logs del Sistema')
@section('subtitulo', 'Supervisa y audita todas las actividades y cambios de los usuarios')

@section('contenido')
    <style>
        .kpi-log-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            transition: all 0.25s ease;
        }

        .kpi-log-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .filter-input-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
        }
    </style>

    <!-- Encabezado de la página -->
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Auditoría del Sistema</h2>
                    <p class="text-muted mb-0 small">Trazabilidad en tiempo real de operaciones, accesos y modificaciones</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-bold btn-sm"
                onclick="$('#datatable_logs').DataTable().ajax.reload();">
                <i class="fas fa-sync-alt me-1"></i> Actualizar Bitácora
            </button>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 1: KPIS DE ACTIVIDAD DE HOY
         ========================================== -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Total Eventos Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-log-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Acciones Hoy</span>
                        <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($kpis['eventos_hoy'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                            <i class="fas fa-bolt me-1"></i>Eventos
                        </span>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-clipboard-list fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Inicios de Sesión Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-log-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Inicios de Sesión</span>
                        <h3 class="fw-extrabold text-success mb-0 font-monospace">{{ number_format($kpis['inicios_sesion'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                            <i class="fas fa-sign-in-alt me-1"></i>Accesos
                        </span>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-user-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Usuarios Activos Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-log-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Usuarios Activos</span>
                        <h3 class="fw-extrabold text-dark mb-0 font-monospace">{{ number_format($kpis['usuarios_activos'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                            <i class="fas fa-users me-1"></i>Personal
                        </span>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Eventos Críticos (Eliminaciones / Anulaciones) -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-log-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Acciones Críticas</span>
                        <h3 class="fw-extrabold text-danger mb-0 font-monospace">{{ number_format($kpis['cambios_criticos'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill small px-2 mt-1">
                            <i class="fas fa-exclamation-triangle me-1"></i>Eliminaciones
                        </span>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-trash-alt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 2: FILTROS AVANZADOS Y TABLA
         ========================================== -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <!-- Filtros Avanzados -->
            <div class="filter-input-card mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label for="filtro_fecha" class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="fas fa-calendar-alt text-primary me-1"></i> Fecha
                        </label>
                        <input type="date" id="filtro_fecha" class="form-control rounded-pill bg-white border">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filtro_modulo" class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="fas fa-th-list text-primary me-1"></i> Módulo
                        </label>
                        <select id="filtro_modulo" class="form-select rounded-pill bg-white border">
                            <option value="">Todos los Módulos</option>
                            <option value="Autenticación">Autenticación</option>
                            <option value="Pacientes">Pacientes</option>
                            <option value="Órdenes de Servicio">Órdenes de Servicio</option>
                            <option value="Punto de Venta">Punto de Venta</option>
                            <option value="Facturación">Facturación</option>
                            <option value="Inventario">Inventario</option>
                            <option value="Configuración">Configuración</option>
                            <option value="Usuarios">Usuarios</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="filtro_accion" class="form-label text-muted small fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="fas fa-tag text-primary me-1"></i> Acción / Evento
                        </label>
                        <select id="filtro_accion" class="form-select rounded-pill bg-white border">
                            <option value="">Todas las Acciones</option>
                            <option value="LOGIN">INICIO SESIÓN</option>
                            <option value="LOGOUT">CIERRE SESIÓN</option>
                            <option value="CREAR">CREAR</option>
                            <option value="EDITAR">EDITAR</option>
                            <option value="ELIMINAR">ELIMINAR</option>
                            <option value="PROCESAR">PROCESAR</option>
                            <option value="PAGO">PAGO</option>
                            <option value="ANULAR">ANULAR</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 d-flex gap-2">
                        <button class="btn btn-primary rounded-pill w-100 shadow-sm fw-bold"
                            onclick="$('#datatable_logs').DataTable().ajax.reload();">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <button class="btn btn-light border rounded-pill px-3 shadow-xs" title="Limpiar Filtros"
                            onclick="$('#filtro_fecha, #filtro_modulo, #filtro_accion').val(''); $('#datatable_logs').DataTable().ajax.reload();">
                            <i class="fas fa-times text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Auditoría -->
            <div class="table-responsive">
                <table id="datatable_logs" class="table table-hover align-middle border-bottom mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th class="border-0 rounded-start text-center">Fecha y Hora</th>
                            <th class="border-0">Usuario</th>
                            <th class="border-0 text-center">Acción</th>
                            <th class="border-0 text-center">Módulo</th>
                            <th class="border-0 text-start">Descripción de la Actividad</th>
                            <th class="border-0 text-center rounded-end">Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody class="text-center align-middle">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/logs.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/logs.js')) ?: time() }}"></script>
@endsection

