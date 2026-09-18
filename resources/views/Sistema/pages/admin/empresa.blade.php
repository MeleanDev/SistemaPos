@extends('Sistema.layouts.app')

@section('titulo', 'Gestión de Laboratorios y Sedes SaaS')
@section('subtitulo', 'Administración centralizada de sedes, planes y suscripciones')

@section('contenido')
    <style>
        .kpi-empresa-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            transition: all 0.25s ease;
        }

        .kpi-empresa-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            border-color: #cbd5e1;
        }
    </style>

    <!-- Encabezado de la página -->
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm" style="width: 58px; height: 58px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-hospital-alt fs-3"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Laboratorios y Sedes SaaS</h2>
                    <p class="text-muted mb-0 small">Supervisa y gestiona las empresas clientes, planes de suscripción y estado operativo</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nuevo Laboratorio
            </button>
        </div>
    </div>

    <!-- ==========================================
         SECCIÓN 1: KPIS DE EMPRESAS SAAS
         ========================================== -->
    <div class="row g-3 mb-4">
        <!-- KPI 1: Total Empresas -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-empresa-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Sedes</span>
                        <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($kpis['total'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                            <i class="fas fa-hospital-alt me-1"></i>Laboratorios
                        </span>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-building fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 2: Sedes Activas -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-empresa-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Sedes Activas</span>
                        <h3 class="fw-extrabold text-success mb-0 font-monospace">{{ number_format($kpis['activas'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                            <i class="fas fa-check-circle me-1"></i>Operando
                        </span>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Plan Básico -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-empresa-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Plan Básico</span>
                        <h3 class="fw-extrabold text-info mb-0 font-monospace">{{ number_format($kpis['basico'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                            <i class="fas fa-cube me-1"></i>Suscripción
                        </span>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-cube fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 4: Plan Premium -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card kpi-empresa-card h-100 shadow-sm p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Plan Premium</span>
                        <h3 class="fw-extrabold text-warning mb-0 font-monospace">{{ number_format($kpis['premium'] ?? 0, 0, ',', '.') }}</h3>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill small px-2 mt-1">
                            <i class="fas fa-star me-1"></i>Avanzado
                        </span>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-star fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjeta Principal con la Tabla -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="datatable_empresas" class="table table-hover align-middle border-bottom mb-0" style="width:100%">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th class="border-0 rounded-start text-center">Logo</th>
                            <th class="border-0 text-start">Empresa / RIF</th>
                            <th class="border-0 text-center">Estado / Suscripción</th>
                            <th class="border-0 text-center">Plan</th>
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

    <!-- Modal Empresa -->
    <div id="modalEmpresa" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i id="iconoModal" class="fas fa-building me-2"></i> <!-- Título dinámico por JS -->
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form id="formulario">
                        @csrf

                        <!-- Sección: Logo y Datos Principales -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-3">
                            <h6 class="text-primary border-bottom pb-2 mb-4 fw-bold">
                                <i class="fas fa-id-card me-1"></i> Datos Principales
                            </h6>

                            <div class="row g-3">
                                <!-- Previsualización de Logo -->
                                <div
                                    class="col-12 col-md-4 d-flex flex-column align-items-center justify-content-center border-end">
                                    <label class="form-label text-muted fw-bold mb-2 small text-center">Logo de
                                        Empresa</label>
                                    <div id="previewContainer"
                                        class="rounded-circle d-flex align-items-center justify-content-center bg-light shadow-sm"
                                        style="width: 110px; height: 110px; overflow: hidden; border: 3px dashed #dee2e6;">
                                        <img id="imgPreview" src="" class="img-fluid"
                                            style="display: none; object-fit: cover; width: 100%; height: 100%;">
                                        <i id="imgPlaceholder" class="fas fa-image text-muted fs-1"></i>
                                    </div>
                                    <div class="mt-3 w-100" id="container-input-img">
                                        <input type="file" class="form-control form-control-sm rounded-pill"
                                            id="logo" name="logo" accept="image/png, image/jpeg, image/jpg"
                                            onchange="previsualizar(this)">
                                    </div>
                                </div>

                                <!-- Datos Generales -->
                                <div class="col-12 col-md-8">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="nombre"
                                                class="form-label text-muted fw-bold mb-1 ms-2"><small>Nombre de la
                                                    Empresa</small> <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                        class="fas fa-briefcase"></i></span>
                                                <input type="text" autocomplete="off"
                                                    class="form-control rounded-end-pill px-3" id="nombre" name="nombre"
                                                    maxlength="100" placeholder="Ej. Laboratorio Central">
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label for="tipo_rif"
                                                class="form-label text-muted fw-bold mb-1 ms-2"><small>RIF</small> <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-sm">
                                                <select class="form-select bg-light rounded-start-pill ps-3" id="tipo_rif"
                                                    name="tipo_rif" style="max-width: 80px;">
                                                    <option value="J-" selected>J-</option>
                                                    <option value="V-">V-</option>
                                                    <option value="E-">E-</option>
                                                    <option value="G-">G-</option>
                                                </select>
                                                <input type="text" autocomplete="off"
                                                    class="form-control rounded-end-pill px-3" id="rif"
                                                    name="rif_input" maxlength="15" placeholder="123456789"
                                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="plan"
                                                class="form-label text-muted fw-bold mb-1 ms-2"><small>Plan</small> <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm rounded-pill px-3" id="plan"
                                                name="plan">
                                                <option value="basico" selected>Básico</option>
                                                <option value="premium">Premium</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="estado"
                                                class="form-label text-muted fw-bold mb-1 ms-2"><small>Estado</small> <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select form-select-sm rounded-pill px-3" id="estado"
                                                name="estado">
                                                <option value="1" selected>Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Contacto -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-3">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-address-book me-1"></i> Información de Contacto
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="correo" class="form-label text-muted fw-bold mb-1 ms-2"><small>Correo
                                            Principal</small> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                class="fas fa-envelope"></i></span>
                                        <input type="email" autocomplete="off"
                                            class="form-control rounded-end-pill px-3" id="correo" name="correo"
                                            maxlength="100" placeholder="correo@ejemplo.com">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="correoSecundario"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Correo
                                            Secundario</small></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                class="fas fa-envelope"></i></span>
                                        <input type="email" autocomplete="off"
                                            class="form-control rounded-end-pill px-3" id="correoSecundario"
                                            name="correoSecundario" maxlength="100" placeholder="opcional@ejemplo.com">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="telefonoUno"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Teléfono 1</small> <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control rounded-end-pill px-3" id="telefonoUno"
                                            name="telefonoUno" maxlength="20" placeholder="+584121234567"
                                            oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="telefonoDos"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Teléfono 2</small></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control rounded-end-pill px-3" id="telefonoDos"
                                            name="telefonoDos" maxlength="20" placeholder="+584141234567"
                                            oninput="this.value = this.value.replace(/[^0-9+]/g, '')">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Ubicación y Suscripción -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-map-marker-alt me-1"></i> Ubicación y Suscripción
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="pais"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>País</small> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="pais"
                                        name="pais" maxlength="100" placeholder="Ej. Venezuela">
                                </div>

                                <div class="col-md-4">
                                    <label for="estadoPais" class="form-label text-muted fw-bold mb-1 ms-2"><small>Estado
                                            / Provincia</small> <span class="text-danger">*</span></label>
                                    <input type="text" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="estadoPais"
                                        name="estadoPais" maxlength="100" placeholder="Ej. Zulia">
                                </div>

                                <div class="col-md-4">
                                    <label for="ciudad"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Ciudad</small> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="ciudad"
                                        name="ciudad" maxlength="100" placeholder="Ej. Maracaibo">
                                </div>

                                <div class="col-md-8">
                                    <label for="direccion"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Dirección Completa</small>
                                        <span class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-sm rounded-4 px-3 py-2" id="direccion" name="direccion" rows="2"
                                        maxlength="255" placeholder="Av. 15 Delicias..."></textarea>
                                </div>

                                <div class="col-md-4">
                                    <label for="fechaFinalSuscripcion"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Vencimiento
                                            Plan</small></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light rounded-start-pill ps-3"><i
                                                class="fas fa-calendar-alt"></i></span>
                                        <input type="date" class="form-control rounded-end-pill px-3"
                                            id="fechaFinalSuscripcion" name="fechaFinalSuscripcion">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-white border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" form="formulario" id="guardarModal"
                        class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/empresa.js') }}?v={{ time() }}"></script>
@endsection
