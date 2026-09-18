@extends('Sistema.layouts.app')

@section('titulo', Auth::user()->hasRole('SuperAdmin') ? 'Control de Dueños de Laboratorios' : 'Personal y Permisos')
@section('subtitulo', Auth::user()->hasRole('SuperAdmin') ? 'Gestión global de administradores SaaS' : 'Administración de equipo clínico y asignación de permisos')

@section('contenido')
    <style>
        .kpi-user-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            transition: all 0.25s ease;
        }

        .kpi-user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }

        .permission-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #ffffff;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .permission-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.06);
            border-color: #3b82f6;
        }

        .permission-card:has(input:checked) {
            border-color: #3b82f6 !important;
            background-color: #f0f7ff !important;
        }

        .permission-card:has(input:checked) .perm-icon {
            background-color: #3b82f6 !important;
            color: white !important;
        }

        .user-nav-pills .nav-link {
            border-radius: 50rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: #64748b;
            padding: 0.5rem 1.25rem;
        }

        .user-nav-pills .nav-link.active {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
        }
    </style>

    <!-- Encabezado de la página -->
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm" style="width: 58px; height: 58px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-users fs-3"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">
                        @if (Auth::user()->hasRole('SuperAdmin'))
                            Dueños de Laboratorios SaaS
                        @else
                            Personal y Permisos del Laboratorio
                        @endif
                    </h2>
                    <p class="text-muted mb-0 small">
                        @if (Auth::user()->hasRole('SuperAdmin'))
                            Supervisa y gestiona las cuentas maestras de los laboratorios registrados
                        @else
                            Control de accesos, roles y permisos específicos para el equipo de trabajo
                        @endif
                    </p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="crearUsuario()">
                <i class="fas fa-user-plus me-2"></i>
                @if (Auth::user()->hasRole('SuperAdmin'))
                    Nuevo Administrador
                @else
                    Nuevo Personal
                @endif
            </button>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 1: KPIS DEL PERSONAL / USUARIOS
         ========================================== -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Total Usuarios -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-user-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Personal</span>
                        <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($kpis['total'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                            <i class="fas fa-users me-1"></i>Registrados
                        </span>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Personal Activo -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-user-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cuentas Activas</span>
                        <h3 class="fw-extrabold text-success mb-0 font-monospace">{{ number_format($kpis['activos'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                            <i class="fas fa-check-circle me-1"></i>Habilitados
                        </span>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        @if (Auth::user()->hasRole('SuperAdmin'))
            <!-- KPI 3: Cuentas Inactivas -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-user-card h-100 shadow-sm p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Inactivos</span>
                            <h3 class="fw-extrabold text-danger mb-0 font-monospace">{{ number_format($kpis['inactivos'] ?? 0, 0, ',', '.') }}</h3>
                            <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill small px-2 mt-1">
                                <i class="fas fa-times-circle me-1"></i>Suspendidos
                            </span>
                        </div>
                        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="fas fa-ban fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Total Empresas -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-user-card h-100 shadow-sm p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sedes SaaS</span>
                            <h3 class="fw-extrabold text-info mb-0 font-monospace">{{ number_format($kpis['empresas'] ?? 0, 0, ',', '.') }}</h3>
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                                <i class="fas fa-hospital-alt me-1"></i>Laboratorios
                            </span>
                        </div>
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="fas fa-hospital-alt fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- KPI 3: Administradores -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-user-card h-100 shadow-sm p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Administradores</span>
                            <h3 class="fw-extrabold text-info mb-0 font-monospace">{{ number_format($kpis['admins'] ?? 0, 0, ',', '.') }}</h3>
                            <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                                <i class="fas fa-shield-alt me-1"></i>Gestión Total
                            </span>
                        </div>
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="fas fa-shield-alt fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI 4: Personal Operativo -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card kpi-user-card h-100 shadow-sm p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Personal Operativo</span>
                            <h3 class="fw-extrabold text-dark mb-0 font-monospace">{{ number_format($kpis['personal'] ?? 0, 0, ',', '.') }}</h3>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill small px-2 mt-1">
                                <i class="fas fa-user-md me-1"></i>Bioanalistas / Staff
                            </span>
                        </div>
                        <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="fas fa-user-md fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- ==========================================
         SECCIÓN 2: TABLA PRINCIPAL DE USUARIOS
         ========================================== -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tablaUsuarios" class="table table-hover align-middle border-bottom mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th class="border-0 rounded-start text-start">Usuario & Rol</th>
                            <th class="border-0 text-start">Nombre Completo</th>
                            <th class="border-0 text-start">Correo Electrónico</th>
                            <th class="border-0 text-center">Cargo</th>
                            @if (Auth::user()->hasRole('SuperAdmin'))
                                <th class="border-0 text-center">Empresa / Sede</th>
                            @endif
                            <th class="border-0 text-center">Estado</th>
                            <th class="border-0 text-center">Fecha Registro</th>
                            <th class="border-0 text-center rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center align-middle">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 3: MODAL DE PERMISOS DETALLADOS
         ========================================== -->
    <div id="modals-container">
        @if (Auth::user()->hasRole('Admin'))
            <div class="modal fade" id="modalPermisos" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                        <div class="modal-header border-0 text-white pb-3 pt-4 px-4 position-relative" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                            <div class="position-absolute top-0 end-0 p-3 opacity-10">
                                <i class="fas fa-shield-alt fa-4x text-white"></i>
                            </div>
                            <div class="z-1 position-relative">
                                <h4 class="modal-title fw-bold mb-1 text-white" id="tituloModalPermisos">
                                    <i class="fas fa-shield-alt me-2 text-info"></i> Permisos de Acceso del Personal
                                </h4>
                                <p class="mb-0 text-white-50 small">Activa o desactiva las funciones y módulos a los que este usuario tendrá autorización.</p>
                            </div>
                            <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-1" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4 bg-light">
                            <form action="" method="POST" id="formPermisos">
                                @csrf

                                @php
                                    $permisosCategorizados = [
                                        '🧪 Laboratorio & Bioanálisis' => [
                                            'gestionar_ordenes' => ['titulo' => 'Órdenes de Servicio', 'desc' => 'Visualizar lista y cola de atención de pacientes', 'icon' => 'fas fa-vial'],
                                            'transcribir_resultados' => ['titulo' => 'Transcribir Resultados', 'desc' => 'Ingresar valores y resultados de los exámenes', 'icon' => 'fas fa-edit'],
                                            'validar_resultados' => ['titulo' => 'Validar Resultados', 'desc' => 'Firmar y autorizar exámenes completados', 'icon' => 'fas fa-check-circle'],
                                            'entregar_resultados' => ['titulo' => 'Entregar Resultados', 'desc' => 'Marcar órdenes e informes como entregados', 'icon' => 'fas fa-paper-plane'],
                                        ],
                                        '💰 Facturación & Atención al Paciente' => [
                                            'gestionar_pacientes' => ['titulo' => 'Pacientes y Caja Rápida', 'desc' => 'Registrar ingresos de pacientes y cobrar facturas', 'icon' => 'fas fa-bolt'],
                                            'gestionar_historial' => ['titulo' => 'Historial de Facturas', 'desc' => 'Búsqueda de facturas y recepción de abonos', 'icon' => 'fas fa-history'],
                                            'anular_facturas' => ['titulo' => 'Anular Facturas', 'desc' => 'Autorización para anular y revertir facturas', 'icon' => 'fas fa-ban'],
                                            'ver_ingresos_dashboard' => ['titulo' => 'Ver Ingresos en Panel', 'desc' => 'Visualizar montos monetarios en el Dashboard principal', 'icon' => 'fas fa-dollar-sign'],
                                        ],
                                        '⚙️ Configuración, Inventario & Reportes' => [
                                            'gestionar_configuracion_medica' => ['titulo' => 'Catálogo Médico', 'desc' => 'Crear y configurar exámenes, áreas y perfiles', 'icon' => 'fas fa-notes-medical'],
                                            'gestionar_configuracion_sistema' => ['titulo' => 'Configuración de la Empresa', 'desc' => 'Ajustar datos fiscales, logotipo y tasa oficial', 'icon' => 'fas fa-cogs'],
                                            'gestionar_inventario' => ['titulo' => 'Control de Inventario', 'desc' => 'Gestión de reactivos, insumos y kardex', 'icon' => 'fas fa-boxes'],
                                            'ver_reportes_basicos' => ['titulo' => 'Reportes y Estadísticas', 'desc' => 'Acceso a análisis gráficos e informes ejecutivos', 'icon' => 'fas fa-chart-line'],
                                            'descargar_reportes_pdf' => ['titulo' => 'Exportar a PDF', 'desc' => 'Descarga de reportes clínicos y financieros en PDF', 'icon' => 'fas fa-file-pdf'],
                                            'descargar_reportes_excel' => ['titulo' => 'Exportar a Excel', 'desc' => 'Descarga de listados y auditorías en Excel', 'icon' => 'fas fa-file-excel'],
                                        ],
                                    ];
                                @endphp

                                <div class="row g-4">
                                    @foreach ($permisosCategorizados as $categoria => $items)
                                        <div class="col-12">
                                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                                <h6 class="fw-bold text-dark mb-0">{{ $categoria }}</h6>
                                            </div>
                                            <div class="row g-3">
                                                @foreach ($items as $permisoKey => $meta)
                                                    <div class="col-12 col-md-6">
                                                        <label class="d-flex justify-content-between align-items-center p-3 permission-card shadow-xs h-100">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="perm-icon bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; min-width: 42px; transition: all 0.2s ease;">
                                                                    <i class="{{ $meta['icon'] ?? 'fas fa-check' }}"></i>
                                                                </div>
                                                                <div>
                                                                    <h6 class="mb-1 fw-bold text-dark small">{{ $meta['titulo'] }}</h6>
                                                                    <p class="text-muted mb-0" style="font-size: 0.75rem; line-height: 1.2;">{{ $meta['desc'] }}</p>
                                                                </div>
                                                            </div>
                                                            <div class="form-check form-switch fs-4 mb-0 ms-2">
                                                                <input class="form-check-input chk-permiso shadow-none" type="checkbox" role="switch" name="permisos[]" value="{{ $permisoKey }}" style="cursor: pointer;">
                                                            </div>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer bg-white border-top p-3 d-flex justify-content-between">
                            <button type="button" class="btn btn-light rounded-pill px-4 fw-medium text-secondary" data-bs-dismiss="modal">
                                Cancelar
                            </button>
                            <button type="submit" form="formPermisos" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center gap-2" id="btnGuardarPermisos">
                                <i class="fas fa-save"></i> <span id="textGuardarPermiso">Guardar Permisos</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- ==========================================
         SECCIÓN 4: MODAL FORMULARIO DE USUARIO
         ========================================== -->
    <div class="modal fade" id="modalFormularioUsuario" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                <div class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="modalFormularioTitulo">
                        <i class="fas fa-user-plus me-2"></i> Registrar Nuevo Personal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body bg-light p-4">
                    <form action="{{ url('administradores') }}" method="POST" id="formUsuario" enctype="multipart/form-data">
                        @csrf
                        <div id="formErrors" class="alert alert-danger rounded-4 d-none"></div>

                        <!-- Bloque 1: Datos Personales y de Acceso -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-3">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-id-card me-1"></i> Datos Personales y Credenciales
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-pill px-3" name="nombre" id="inpNombre" required placeholder="Ej: María">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Apellido <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-pill px-3" name="apellido" id="inpApellido" required placeholder="Ej: González">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Usuario de Acceso (Login) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control rounded-pill px-3" name="name" id="inpName" required placeholder="Ej: mgonzalez">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Correo Electrónico <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control rounded-pill px-3" name="email" id="inpEmail" required placeholder="correo@laboratorio.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">
                                        Contraseña <span id="hintPassword" class="text-muted fw-normal small"></span>
                                    </label>
                                    <input type="password" class="form-control rounded-pill px-3" name="password" id="inpPassword" required minlength="8" placeholder="••••••••">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Teléfono de Contacto</label>
                                    <input type="text" class="form-control rounded-pill px-3" name="telefono" id="inpTelefono" placeholder="04141234567" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                        </div>

                        <!-- Bloque 2: Perfil Profesional y Firma -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-3">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-user-md me-1"></i> Perfil Clínico y Firma Digital
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Cargo o Función</label>
                                    <input type="text" class="form-control rounded-pill px-3" name="cargo" id="inpCargo" placeholder="Ej: Bioanalista Principal, Recepcionista">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Credenciales / Matrícula Profesional</label>
                                    <input type="text" class="form-control rounded-pill px-3" name="credenciales" id="inpCredenciales" placeholder="Ej: M.P.P.S: 12345 / C.B.V: 6789">
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold mb-1 ms-2" style="font-size: 0.8rem;">Firma Digitalizada (Fondo Transparente PNG)</label>
                                    <input type="file" class="form-control rounded-pill px-3" name="firma" id="inpFirma" accept="image/png, image/jpeg">
                                    <small class="text-muted ms-2" style="font-size: 0.75rem;">Se estampará en los informes y resultados validados por este usuario.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Bloque 3: Asignación de Rol / Empresa -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-cogs me-1"></i> Asignación y Nivel de Acceso
                            </h6>
                            <div class="row">
                                @if (Auth::user()->hasRole('SuperAdmin'))
                                    <div class="col-12" id="divEmpresa">
                                        <label class="form-label fw-bold text-dark mb-1 ms-2" style="font-size: 0.8rem;">Asignar a Laboratorio (Empresa) <span class="text-danger">*</span></label>
                                        <select name="empresa_id" id="inpEmpresa" class="form-select rounded-pill px-3" required>
                                            <option value="" disabled selected>Seleccione la empresa dueña...</option>
                                            @foreach ($empresas as $empresa)
                                                @if ($empresa->id == 1)
                                                    <option value="{{ $empresa->id }}" class="fw-bold text-primary">⭐ {{ $empresa->nombre }} (Empresa Principal / SuperAdmin)</option>
                                                @else
                                                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }} ({{ $empresa->rif }})</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <div class="col-12" id="divRol">
                                        <label class="form-label fw-bold text-dark mb-1 ms-2" style="font-size: 0.8rem;">Nivel de Rol en el Laboratorio <span class="text-danger">*</span></label>
                                        <select name="rol" id="inpRol" class="form-select rounded-pill px-3" required>
                                            <option value="usuario" selected>Personal Clínico / Operativo</option>
                                            <option value="Admin">Administrador Secundario (Gestión Completa)</option>
                                        </select>
                                        <small class="text-muted ms-3 mt-1 d-block" style="font-size: 0.75rem;">
                                            <i class="fas fa-info-circle text-primary"></i> Los Administradores secundarios tendrán acceso total a todos los módulos y gestión de empleados.
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-white border-top p-3">
                    <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" form="formUsuario" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btnGuardarUsuario">
                        <span class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                        <i class="fas fa-save me-1" id="iconGuardar"></i> <span id="btnGuardarText">Guardar Personal</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script>
        const urlBase = "{{ url('administradores') }}";
        const isSuperAdmin = {{ Auth::user()->hasRole('SuperAdmin') ? 'true' : 'false' }};
        const isAdmin = {{ Auth::user()->hasRole('Admin') ? 'true' : 'false' }};

        const toast = typeof notificacion !== 'undefined' ? notificacion : Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });

        $(document).ready(function() {
            let cols = [
                {
                    data: "name",
                    name: "name",
                    className: "text-start",
                    render: function(data, type, row) {
                        let initial = (row.nombre ? row.nombre.substring(0, 1) : 'U').toUpperCase();
                        let isSuper = row.rol_nombre === 'SuperAdmin';
                        let isAdminRol = row.rol_nombre === 'Admin';
                        
                        let badgeBg = 'bg-secondary bg-opacity-10 text-secondary border';
                        let roleIcon = 'fas fa-user';
                        
                        if (isSuper) {
                            badgeBg = 'bg-danger bg-opacity-10 text-danger border border-danger';
                            roleIcon = 'fas fa-shield-alt';
                        } else if (isAdminRol) {
                            badgeBg = 'bg-primary bg-opacity-10 text-primary border border-primary';
                            roleIcon = 'fas fa-user-circle';
                        }

                        return `
                        <div class="d-flex align-items-center text-start">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center fw-bold me-3 shadow-xs" style="width: 42px; height: 42px; min-width: 42px; font-size: 1.1rem;">
                                ${initial}
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark font-monospace">${row.name}</h6>
                                <span class="badge ${badgeBg} rounded-pill px-2 py-0 small" style="font-size: 0.7rem;">
                                    <i class="${roleIcon} me-1"></i>${row.rol_nombre || 'Personal'}
                                </span>
                            </div>
                        </div>`;
                    }
                },
                {
                    data: "nombre",
                    name: "nombre",
                    className: "text-start",
                    render: function(data, type, row) {
                        return `<span class="fw-bold text-dark d-block">${row.nombre || ''} ${row.apellido || ''}</span>
                                ${row.credenciales ? `<small class="text-muted font-monospace"><i class="fas fa-certificate text-warning me-1"></i>${row.credenciales}</small>` : ''}`;
                    }
                },
                {
                    data: "email",
                    name: "email",
                    className: "text-start",
                    render: function(data, type, row) {
                        return `<div>
                            <a href="mailto:${data}" class="text-decoration-none text-dark fw-medium small d-block">
                                <i class="fas fa-envelope text-primary me-1"></i>${data}
                            </a>
                            ${row.telefono ? `<small class="text-muted"><i class="fas fa-phone-alt text-muted me-1"></i>${row.telefono}</small>` : ''}
                        </div>`;
                    }
                },
                {
                    data: "cargo",
                    name: "cargo",
                    className: "text-center",
                    render: function(data) {
                        return `<span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">${data || 'Personal'}</span>`;
                    }
                }
            ];

            if (isSuperAdmin) {
                cols.push({
                    data: "empresa_nombre",
                    name: "empresa.nombre",
                    className: "text-center",
                    render: function(data) {
                        return `<span class="fw-semibold text-dark small"><i class="fas fa-hospital-alt text-info me-1"></i>${data || 'Principal'}</span>`;
                    }
                });
            }

            cols.push({
                data: "estado",
                name: "estado",
                className: "text-center",
                render: function(data) {
                    if (data == 1) {
                        return `<span class="badge bg-success bg-opacity-10 text-success border border-success px-3 py-1 rounded-pill small fw-bold">
                            <i class="fas fa-check-circle me-1"></i>Activo
                        </span>`;
                    } else {
                        return `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-3 py-1 rounded-pill small fw-bold">
                            <i class="fas fa-times-circle me-1"></i>Inactivo
                        </span>`;
                    }
                }
            });

            cols.push({
                data: "fecha_registro",
                name: "created_at",
                className: "text-center",
                render: function(data) {
                    return `<span class="text-muted small font-monospace"><i class="far fa-calendar-alt me-1"></i>${data}</span>`;
                }
            });

            cols.push({
                data: "id",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function(data, type, row) {
                    if (row.id == {{ Auth::user()->id }}) {
                        return '<span class="badge bg-light text-muted border rounded-pill px-2 py-1"><i class="fas fa-lock me-1"></i>Tu Cuenta</span>';
                    }
                    
                    let html = `<div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle shadow-xs" onclick="editarUsuario(${row.id})" title="Editar Usuario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>`;

                    if (isAdmin) {
                        html += `
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle shadow-xs" onclick="configurarPermisos(${row.id})" title="Gestionar Permisos" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-shield-alt"></i>
                        </button>`;
                    }

                    if (row.estado == 1) {
                        html += `
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-xs" onclick="toggleEstado(${row.id}, 1)" title="Desactivar Usuario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-ban"></i>
                        </button>`;
                    } else {
                        html += `
                        <button type="button" class="btn btn-sm btn-outline-success rounded-circle shadow-xs" onclick="toggleEstado(${row.id}, 0)" title="Activar Usuario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check"></i>
                        </button>`;
                    }

                    html += `</div>`;
                    return html;
                }
            });

            var table = $('#tablaUsuarios').DataTable({
                ajax: urlBase + '/lista',
                serverSide: true,
                responsive: true,
                columns: cols,
                language: {
                    sSearch: "Buscar personal:",
                    zeroRecords: "No se encontraron usuarios registrados",
                    emptyTable: "Ningún personal disponible en este laboratorio",
                    lengthMenu: "Mostrar _MENU_ usuarios",
                    info: "Mostrando _START_ al _END_ de _TOTAL_ usuarios",
                    infoEmpty: "Mostrando 0 al 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros)",
                    oPaginate: {
                        sFirst: "Primero",
                        sLast: "Último",
                        sNext: "Siguiente",
                        sPrevious: "Anterior",
                    },
                    sProcessing: "Procesando...",
                },
            });

            $('#formUsuario').on('submit', submitUsuario);
            $('#formPermisos').on('submit', submitPermisos);
        });

        const consultar = async (id) => {
            const res = await fetch(urlBase + '/' + id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            return await res.json();
        };

        window.crearUsuario = function() {
            $('#formUsuario')[0].reset();
            $('#formUsuario').attr('action', urlBase);
            $('#modalFormularioTitulo').html('<i class="fas fa-user-plus me-2"></i> Registrar Nuevo Personal');
            $('#inpPassword').prop('required', true);
            $('#hintPassword').text('');
            if ($('#divEmpresa').length) $('#divEmpresa').show();
            if ($('#inpEmpresa').length) $('#inpEmpresa').prop('required', true);
            $('#btnGuardarText').text('Guardar Personal');
            $('#formErrors').addClass('d-none');
            $('#modalFormularioUsuario').modal('show');
        };

        window.editarUsuario = async function(id) {
            try {
                const data = await consultar(id);
                const u = data.user;

                $('#formUsuario')[0].reset();
                $('#formUsuario').attr('action', urlBase + '/actualizar/' + u.id);
                $('#modalFormularioTitulo').html('<i class="fas fa-edit me-2"></i> Editar Personal: ' + u.nombre);

                $('#inpNombre').val(u.nombre);
                $('#inpApellido').val(u.apellido);
                $('#inpName').val(u.name);
                $('#inpEmail').val(u.email);
                $('#inpTelefono').val(u.telefono);
                $('#inpCargo').val(u.cargo);
                $('#inpCredenciales').val(u.credenciales);
                $('#inpFirma').val('');

                $('#inpPassword').prop('required', false);
                $('#hintPassword').text('(Opcional: déjalo en blanco para conservarla)');

                if ($('#divEmpresa').length) $('#divEmpresa').hide();
                if ($('#inpEmpresa').length) $('#inpEmpresa').prop('required', false);

                if ($('#inpRol').length && data.roles && data.roles.length > 0) {
                    $('#inpRol').val(data.roles[0]);
                }

                $('#btnGuardarText').text('Actualizar Personal');
                $('#formErrors').addClass('d-none');
                $('#modalFormularioUsuario').modal('show');
            } catch (error) {
                toast.fire({
                    icon: 'error',
                    title: 'Error cargando datos del personal.'
                });
            }
        };

        window.configurarPermisos = async function(id) {
            try {
                const data = await consultar(id);
                const u = data.user;
                const perms = data.permissions;

                $('#formPermisos').attr('action', urlBase + '/permisos/' + u.id);
                $('#tituloModalPermisos').html(`<i class="fas fa-shield-alt me-2 text-info"></i> Permisos de: ${u.nombre} ${u.apellido}`);

                $('.chk-permiso').prop('checked', false);
                $('.chk-permiso').each(function() {
                    if (perms.includes($(this).val())) {
                        $(this).prop('checked', true);
                    }
                });

                $('#modalPermisos').modal('show');
            } catch (error) {
                toast.fire({
                    icon: 'error',
                    title: 'Error cargando permisos.'
                });
            }
        };

        window.toggleEstado = function(id, estadoActual) {
            let title = estadoActual == 1 ? '¿Desactivar personal?' : '¿Activar personal?';
            let text = estadoActual == 1 ? 'El usuario no podrá iniciar sesión en el laboratorio.' :
                'El usuario recuperará el acceso al sistema.';
            let confirmText = estadoActual == 1 ? 'Sí, desactivar' : 'Sí, activar';
            let confirmColor = estadoActual == 1 ? '#ef4444' : '#10b981';

            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#64748b',
                confirmButtonText: confirmText,
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'rounded-pill px-4',
                    cancelButton: 'rounded-pill px-4'
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const csrf = document.querySelector('input[name="_token"]').value;
                        const res = await fetch(urlBase + '/' + id, {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrf
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            toast.fire({
                                icon: 'success',
                                title: data.message
                            });
                            $('#tablaUsuarios').DataTable().ajax.reload(null, false);
                        } else {
                            toast.fire({
                                icon: 'error',
                                title: data.message || 'Error al modificar estado.'
                            });
                        }
                    } catch (e) {
                        toast.fire({
                            icon: 'error',
                            title: 'Error de conexión con el servidor.'
                        });
                    }
                }
            });
        };

        const submitUsuario = async function(e) {
            e.preventDefault();
            const btn = $('#btnGuardarUsuario');
            const spinner = btn.find('.spinner-border');
            const icon = btn.find('#iconGuardar');
            const errorDiv = $('#formErrors');
            const form = this;

            errorDiv.addClass('d-none').html('');
            btn.prop('disabled', true);
            spinner.removeClass('d-none');
            icon.addClass('d-none');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    $('#modalFormularioUsuario').modal('hide');
                    toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    $('#tablaUsuarios').DataTable().ajax.reload(null, false);
                } else {
                    let errors = data.message || 'Error al procesar la solicitud.';
                    if (data.errors) {
                        errors = Object.values(data.errors).flat().join('<br>');
                    }
                    errorDiv.removeClass('d-none').html(errors);
                }
            } catch (error) {
                errorDiv.removeClass('d-none').html('Ocurrió un error en la conexión.');
            } finally {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                icon.removeClass('d-none');
            }
        };

        const submitPermisos = async function(e) {
            e.preventDefault();
            const form = this;
            const btn = $('#btnGuardarPermisos');
            const btnText = $('#textGuardarPermiso');
            const originalText = btnText.html();

            btn.prop('disabled', true);
            btnText.html('Guardando...');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    $('#modalPermisos').modal('hide');
                    toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                } else {
                    toast.fire({
                        icon: 'error',
                        title: data.message || 'Error al actualizar permisos.'
                    });
                }
            } catch (error) {
                toast.fire({
                    icon: 'error',
                    title: 'Error de conexión con el servidor.'
                });
            } finally {
                btn.prop('disabled', false);
                btnText.html(originalText);
            }
        };
    </script>
@endsection
