@extends('Sistema.layouts.app')

@section('titulo', '👥 Usuarios y Roles')
@section('subtitulo', 'Directorio ejecutivo de usuarios, credenciales de acceso, asignación de roles y sedes
    autorizadas')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Usuarios</span>
@endsection

@section('acciones')
    @can('usuarios.crear')
        <x-btn-action icon="fas fa-user-plus" text="Nuevo Usuario" onclick="crear()" />
    @endcan
@endsection

@section('contenido')
    <!-- BARRA EJECUTIVA DE CONTROL, BÚSQUEDA Y FILTROS USANDO COMPONENTE OFICIAL TOTALMENTE RESPONSIVE -->
    <x-search-filter inputId="buscadorUsuarios" counterId="contadorUsuarios"
        placeholder="Buscar por cédula, nombre, correo, rol o sede..." loadingText="Cargando usuarios..."
        counterIcon="fas fa-users">
        <x-slot:filters>
            <select class="form-select font-monospace fw-semibold rounded-pill bg-white shadow-xs" id="filtroEmpresa"
                style="border-color: #e2e8f0; font-size: 0.84rem; height: 42px; min-width: 180px;">
                <option value="" selected>🏢 Todas las Sedes</option>
                @if (!empty($catalogos['empresas']))
                    @foreach ($catalogos['empresas'] as $empresa)
                        <option value="{{ $empresa->nombre }}">{{ $empresa->nombre }}</option>
                    @endforeach
                @endif
            </select>
        </x-slot:filters>

        <x-slot:actions>
            <div class="btn-group p-1 bg-white border rounded-pill shadow-xs flex-shrink-0" role="group"
                aria-label="Cambiar vista">
                <button type="button"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-toggle-vista active"
                    id="btnVistaGrid" data-vista="grid" title="Vista Tarjetas">
                    <i class="fas fa-th-large me-1"></i> Tarjetas
                </button>
                <button type="button"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-toggle-vista text-muted"
                    id="btnVistaTabla" data-vista="tabla" title="Vista Tabla">
                    <i class="fas fa-table-list me-1"></i> Tabla
                </button>
            </div>
        </x-slot:actions>

        <x-slot:bottom>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <small class="text-muted fw-bold me-1 text-uppercase"
                    style="font-size: 0.70rem; letter-spacing: 0.04em;">Filtrar por Rol:</small>
                <button type="button"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-semibold filter-role-chip active bg-primary text-white shadow-xs"
                    data-rol="" style="font-size: 0.78rem;">
                    Todos (<span id="countRolTodos">0</span>)
                </button>
                @if (auth()->user()?->hasRole('SuperAdmin'))
                    <button type="button"
                        class="btn btn-sm rounded-pill px-3 py-1 fw-semibold filter-role-chip bg-white border text-dark shadow-xs"
                        data-rol="SuperAdmin" style="font-size: 0.78rem;">
                        <i class="fas fa-crown text-warning me-1"></i> SuperAdmin (<span id="countRolSuperAdmin">0</span>)
                    </button>
                @endif
                <button type="button"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-semibold filter-role-chip bg-white border text-dark shadow-xs"
                    data-rol="Admin" style="font-size: 0.78rem;">
                    <i class="fas fa-user-shield text-primary me-1"></i> Administrador (<span id="countRolAdmin">0</span>)
                </button>
                <button type="button"
                    class="btn btn-sm rounded-pill px-3 py-1 fw-semibold filter-role-chip bg-white border text-dark shadow-xs"
                    data-rol="Operador" style="font-size: 0.78rem;">
                    <i class="fas fa-headset text-info me-1"></i> Operador (<span id="countRolOperador">0</span>)
                </button>
            </div>
        </x-slot:bottom>
    </x-search-filter>

    <!-- CONTENEDOR 1: GRID DE TARJETAS EJECUTIVAS -->
    <div class="row g-4 mb-4" id="contenedorUsuariosGrid">
    </div>

    <!-- CONTENEDOR 2: VISTA TABLA EJECUTIVA CON COMPONENTE OFICIAL DEL SISTEMA -->
    <div class="d-none mb-4" id="contenedorUsuariosTabla">
        <x-datatable
            id="datatable_usuarios"
            :headers="[
                'Usuario & Nombre',
                'Cédula',
                'Correo Electrónico',
                'Rol',
                'Sedes Autorizadas',
                'Permisos',
                'Acciones',
            ]"
        />
    </div>

    <!-- MODAL PRINCIPAL: CREACIÓN / EDICIÓN DE USUARIO -->
    <x-modal id="modalUsuario" title="Nuevo Usuario" subtitle="Completa los datos de acceso, rol y sedes del usuario"
        icon="fas fa-user-shield text-warning fs-5" size="modal-lg" headerColor="bg-dark text-white"
        formId="formularioUsuario" submitText="Guardar">
        <form id="formularioUsuario">
            @csrf
            <div class="row g-3">
                <!-- Cédula de Identidad (Login) -->
                <x-input-documento selectName="tipo_cedula" inputName="cedula_numero"
                    label="Identificación / Cédula (Login)" defaultType="V-" required col="col-md-6" />

                <!-- Correo Electrónico -->
                <x-input name="email" id="email" type="email" label="Correo Electrónico" icon="fas fa-envelope"
                    placeholder="usuario@ejemplo.com" required maxlength="150" col="col-md-6" />

                <!-- Nombre -->
                <x-input name="nombre" label="Nombre" icon="fas fa-user" placeholder="Ej. Carlos" required maxlength="100"
                    col="col-md-6" />

                <!-- Apellido -->
                <x-input name="apellido" label="Apellido" icon="fas fa-user" placeholder="Ej. Pérez" required
                    maxlength="100" col="col-md-6" />

                <!-- Contraseña -->
                <x-input name="password" id="password" type="password" label="Contraseña de Acceso" icon="fas fa-lock"
                    placeholder="Mínimo 6 caracteres" required maxlength="100" col="col-md-6" />

                <!-- Rol en el Sistema -->
                <div class="col-md-6">
                    <label class="form-label-executive" for="rol">
                        <i class="fas fa-shield-alt"></i> Rol en el Sistema <span class="text-danger">*</span>
                    </label>
                    <select class="form-select form-control-executive" name="rol" id="rol" required>
                        <option value="Operador" selected>Operador (POS, Ventas y Operaciones)</option>
                        <option value="Admin">Administrador (Gestión de Empresa)</option>
                        @if (auth()->user()?->hasRole('SuperAdmin'))
                            <option value="SuperAdmin">SuperAdministrador (Acceso Global Total)</option>
                        @endif
                    </select>
                </div>

                <!-- SECCIÓN: EMPRESAS AUTORIZADAS (EXECUTIVE SELECTABLE CARDS) -->
                <div class="col-12" id="contenedorSeccionEmpresas">
                    <div class="card bg-white shadow-xs border rounded-4 p-3 mt-1">
                        <div
                            class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                            <div>
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2"
                                    style="font-size: 0.95rem;">
                                    <div class="rounded-3 p-1 bg-primary text-white d-flex align-items-center justify-content-center"
                                        style="width: 26px; height: 26px; font-size: 0.75rem;">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <span>Empresas / Sedes Autorizadas</span>
                                </h6>
                                <small class="text-muted" style="font-size: 0.78rem;">Selecciona las sedes donde este
                                    usuario tendrá autorización para ingresar y operar.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold"
                                style="font-size: 0.75rem;" onclick="marcarTodasLasEmpresas()">
                                <i class="fas fa-check-double me-1"></i> Seleccionar Todas
                            </button>
                        </div>

                        <div class="row g-3" id="listaEmpresasCheckboxes">
                            @if (!empty($catalogos['empresas']) && count($catalogos['empresas']) > 0)
                                @foreach ($catalogos['empresas'] as $empresa)
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card-empresa-select card rounded-4 p-3 mb-0 h-100 d-flex flex-row align-items-center justify-content-between cursor-pointer"
                                            onclick="toggleEmpresaCard(this)" id="card_empresa_{{ $empresa->id }}"
                                            style="border: 1.5px solid #e2e8f0; background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                                            <div class="d-flex align-items-center gap-3 overflow-hidden me-2">
                                                <div class="rounded-3 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width: 40px; height: 40px; min-width: 40px; font-size: 1.15rem;">
                                                    <i class="fas fa-store"></i>
                                                </div>
                                                <div class="d-flex flex-column overflow-hidden justify-content-center">
                                                    <span class="fw-bold text-dark text-truncate"
                                                        style="font-size: 0.90rem;">{{ $empresa->nombre }}</span>
                                                    <span class="text-muted small font-monospace text-truncate"
                                                        style="font-size: 0.76rem;">
                                                        RIF: {{ $empresa->rif }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="check-empresa-indicator rounded-circle border d-flex align-items-center justify-content-center"
                                                style="width: 22px; height: 22px; min-width: 22px; border-color: #cbd5e1; background: #ffffff; transition: all 0.2s ease;">
                                                <i class="fas fa-check text-white d-none" style="font-size: 0.65rem;"></i>
                                            </div>
                                            <input class="form-check-input check-empresa d-none" type="checkbox"
                                                name="empresas[]" value="{{ $empresa->id }}"
                                                id="empresa_chk_{{ $empresa->id }}">
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-12 text-center py-3 text-muted small bg-white rounded-4 border">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> No hay empresas registradas aún.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL DEDICADO: PERMISOS GRANULARES DEL OPERADOR -->
    <x-modal id="modalPermisosUsuario" title="Permisos del Operador"
        subtitle="Concede o revoca facultades de acceso por módulo para este operador"
        icon="fas fa-user-lock text-warning fs-5" size="modal-xl" headerColor="bg-dark text-white"
        formId="formularioPermisosUsuario" submitText="Guardar Permisos">
        <form id="formularioPermisosUsuario">
            @csrf
            <input type="hidden" id="permisos_usuario_id" name="usuario_id">

            <!-- BANNER DEL OPERADOR -->
            <div class="card bg-white border rounded-4 shadow-xs p-3 mb-4 d-flex flex-row align-items-center justify-content-between flex-wrap gap-3"
                style="border-color: #e2e8f0 !important;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm text-white fw-bold rounded-4 d-flex align-items-center justify-content-center shadow-sm"
                        id="permisos_avatar_display"
                        style="width: 48px; height: 48px; min-width: 48px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 1.15rem;">
                        OP
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold text-dark mb-0" id="permisos_nombre_display" style="font-size: 1.05rem;">
                                Operador
                            </h6>
                            <span class="badge px-2.5 py-0.5 rounded-pill fw-bold"
                                style="background: #e0f2fe; color: #0369a1; font-size: 0.72rem;">
                                <i class="fas fa-headset me-1"></i>Operador
                            </span>
                        </div>
                        <span class="d-inline-flex align-items-center gap-2 font-monospace fw-semibold text-secondary mt-1"
                            id="permisos_cedula_display" style="font-size: 0.82rem;">
                            <i class="fas fa-id-card text-primary"></i>
                            <span>V-00000000</span>
                        </span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button"
                        class="btn btn-sm btn-outline-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-sm"
                        style="font-size: 0.78rem;" onclick="marcarTodosLosPermisosModal(true)">
                        <i class="fas fa-check-double me-1"></i> Seleccionar Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3.5 py-1.5 fw-semibold"
                        style="font-size: 0.78rem;" onclick="marcarTodosLosPermisosModal(false)">
                        <i class="fas fa-times me-1"></i> Desmarcar Todos
                    </button>
                </div>
            </div>

            <!-- GRID DE MÓDULOS ACTIVOS -->
            <div class="row g-4">
                @if (!empty($catalogos['permisos_modulos']))
                    @foreach ($catalogos['permisos_modulos'] as $moduloInfo)
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100 border rounded-4 bg-white shadow-sm card-modulo-permiso">
                                <!-- HEADER DEL MÓDULO CON ICONO LIMPIO SIN BORDES FEOS -->
                                <div class="card-header bg-white border-bottom px-3 py-2.5 d-flex align-items-center justify-content-between gap-2"
                                    style="border-color: #f1f5f9 !important;">
                                    <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"
                                            style="width: 38px; height: 38px; min-width: 38px; font-size: 1.05rem; border: none !important;">
                                            <i class="{{ $moduloInfo['icono'] }}"></i>
                                        </div>
                                        <div class="overflow-hidden">
                                            <span class="fw-bold text-dark d-block text-truncate"
                                                style="font-size: 0.95rem; letter-spacing: -0.01em;">{{ $moduloInfo['modulo'] }}</span>
                                            <span class="text-muted d-block text-truncate"
                                                style="font-size: 0.73rem;">{{ $moduloInfo['descripcion'] }}</span>
                                        </div>
                                    </div>
                                    <button type="button"
                                        class="btn btn-sm btn-white border rounded-pill px-2.5 py-1 fw-bold text-primary shadow-xs flex-shrink-0"
                                        style="font-size: 0.73rem; background: #ffffff;"
                                        onclick="toggleModuloPermisosModal(this)">
                                        <i class="fas fa-check me-1 text-primary"></i> Todos
                                    </button>
                                </div>

                                <!-- CUERPO: LISTA ELEGANTE DE PERMISOS -->
                                <div class="card-body p-2 d-flex flex-column gap-1">
                                    @foreach ($moduloInfo['permisos'] as $permiso)
                                        @php
                                            $iconoAccion = 'fas fa-shield-alt text-muted';
                                            if (str_contains($permiso['name'], '.ver')) {
                                                $iconoAccion = 'fas fa-eye text-primary';
                                            } elseif (str_contains($permiso['name'], '.crear')) {
                                                $iconoAccion = 'fas fa-plus-circle text-success';
                                            } elseif (str_contains($permiso['name'], '.editar')) {
                                                $iconoAccion = 'fas fa-pen text-warning';
                                            } elseif (str_contains($permiso['name'], '.eliminar')) {
                                                $iconoAccion = 'fas fa-trash-alt text-danger';
                                            }
                                        @endphp
                                        <div
                                            class="permiso-row-sleek d-flex align-items-center justify-content-between py-2 px-2.5 rounded-3">
                                            <div class="d-flex align-items-center gap-2 flex-grow-1 pe-2 overflow-hidden">
                                                <span class="d-flex align-items-center justify-content-center"
                                                    style="width: 22px; min-width: 22px; font-size: 0.8rem; opacity: 0.85;">
                                                    <i class="{{ $iconoAccion }}"></i>
                                                </span>
                                                <label
                                                    class="form-check-label mb-0 text-dark fw-medium cursor-pointer flex-grow-1 text-truncate"
                                                    for="chk_perm_{{ str_replace('.', '_', $permiso['name']) }}"
                                                    style="font-size: 0.84rem; user-select: none;">
                                                    {{ $permiso['label'] }}
                                                </label>
                                            </div>
                                            <div class="form-check form-switch m-0 p-0 d-flex align-items-center">
                                                <input class="form-check-input modal-permiso-chk m-0 cursor-pointer"
                                                    type="checkbox" name="permisos[]" value="{{ $permiso['name'] }}"
                                                    id="chk_perm_{{ str_replace('.', '_', $permiso['name']) }}"
                                                    style="cursor: pointer; width: 2.25em; height: 1.2em;">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    <style>
        .search-input-wrapper:focus-within {
            background-color: #ffffff !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12) !important;
        }

        .search-input-wrapper input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .btn-toggle-vista.active {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
        }

        .filter-role-chip {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .filter-role-chip:hover:not(.active) {
            border-color: #94a3b8 !important;
            background-color: #f8fafc !important;
        }

        .filter-role-chip.active {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #ffffff !important;
        }

        .card-modulo-permiso {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 1rem !important;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .card-modulo-permiso:hover {
            border-color: #cbd5e1 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06) !important;
        }

        .permiso-row-sleek {
            transition: background-color 0.15s ease;
            cursor: pointer;
        }

        .permiso-row-sleek:hover {
            background-color: #f8fafc;
        }

        .permiso-row-sleek .form-check-input {
            cursor: pointer;
            background-color: #cbd5e1;
            border-color: #cbd5e1;
            transition: all 0.2s ease;
        }

        .permiso-row-sleek .form-check-input:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.35);
        }

        .card-search-filter .search-input-wrapper {
            max-width: 440px;
            width: 100%;
        }

        #contadorUsuarios {
            min-width: 130px;
            justify-content: center;
        }
    </style>
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/usuario.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/usuario.js')) ?: time() }}">
    </script>
@endsection
