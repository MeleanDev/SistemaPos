@extends('Sistema.layouts.app')

@section('subtitulo', 'Administración de pacientes')

@section('contenido')
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-users"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Administración de Pacientes</h2>
                    <p class="text-muted mb-0 small">Consulta y gestiona los pacientes registrados</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto ms-auto text-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-user-plus me-2"></i> Nuevo Paciente
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <!-- Filtros Rápidos -->
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="filtros-pacientes">
                <span class="text-muted small fw-bold mt-1 me-2"><i class="fas fa-filter text-primary"></i> Filtros rápidos:</span>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                    onclick="filtrarTablaPacientes('', this)">
                    Todos
                </button>
                <button type="button" class="btn btn-outline-info btn-sm rounded-pill"
                    onclick="filtrarTablaPacientes('MENOR', this)">
                    <i class="fas fa-child me-1"></i> Solo Niños (&lt; 18)
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill"
                    onclick="filtrarTablaPacientes('ADULTO', this)">
                    <i class="fas fa-user me-1"></i> Adultos (18 - 59)
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill"
                    onclick="filtrarTablaPacientes('MAYOR', this)">
                    <i class="fas fa-user-check me-1"></i> Adultos Mayores (60+)
                </button>
            </div>

            <!-- Filtros Avanzados -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_edad_min" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Edad Mínima
                    </label>
                    <div class="input-group">
                        <input type="number" id="filtro_edad_min" class="form-control rounded-start-pill border-end-0"
                            placeholder="Ej. 0" min="0">
                        <span class="input-group-text bg-white border-start-0 text-muted rounded-end-pill small">años</span>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_edad_max" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Edad Máxima
                    </label>
                    <div class="input-group">
                        <input type="number" id="filtro_edad_max" class="form-control rounded-start-pill border-end-0"
                            placeholder="Ej. 18" min="0">
                        <span class="input-group-text bg-white border-start-0 text-muted rounded-end-pill small">años</span>
                    </div>
                </div>
                <div class="col-12 col-sm-8 col-md-4">
                    <label for="filtro_direccion" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-map-marker-alt text-primary me-1"></i> Dirección
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 rounded-start-pill">
                            <i class="fas fa-map-marker-alt text-muted"></i>
                        </span>
                        <input type="text" id="filtro_direccion" class="form-control border-start-0 rounded-end-pill"
                            placeholder="Escribe una dirección..." autocomplete="off">
                    </div>
                </div>
                <div class="col-12 col-sm-4 col-md-2">
                    <button type="button" class="btn btn-outline-danger rounded-pill w-100 shadow-sm"
                        onclick="limpiarFiltrosAvanzados()" title="Restablecer todos los filtros">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable_pacientes" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th class="border-0 rounded-start">Nombres</th>
                            <th class="border-0">Apellidos</th>
                            <th class="border-0">Cédula / Código</th>
                            <th class="border-0">Teléfono</th>
                            <th class="border-0 text-center">Tarifario / Convenio</th>
                            <th class="border-0">Edad</th>
                            <th class="border-0 text-center rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="text-center align-middle">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Paciente -->
    <div id="modalPaciente" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-user-plus me-2"></i> Nuevo Paciente <button type="button" class="btn btn-sm btn-light text-primary ms-3 fw-bold rounded-pill shadow-sm" id="btn-generar-anonimo" onclick="generarDatosAnonimosPaciente()"><i class="fas fa-user-secret me-1"></i> Generar Anonimo</button>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form id="formularioPaciente">
                        @csrf

                        <!-- Datos Personales -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-id-card me-1"></i> Datos Personales
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombreUno" class="form-label text-muted fw-bold mb-1 ms-2"><small>Primer
                                            Nombre</small> <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="50" autocomplete="off" required
                                        class="form-control form-control-sm rounded-pill px-3" id="nombreUno"
                                        name="nombreUno" placeholder="Ej. Juan">
                                </div>

                                <div class="col-md-6">
                                    <label for="nombreDos" class="form-label text-muted fw-bold mb-1 ms-2"><small>Segundo
                                            Nombre</small></label>
                                    <input type="text" maxlength="50" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="nombreDos"
                                        name="nombreDos" placeholder="Ej. Carlos">
                                </div>

                                <div class="col-md-6">
                                    <label for="apellidoUno" class="form-label text-muted fw-bold mb-1 ms-2"><small>Primer
                                            Apellido</small> <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="50" autocomplete="off" required
                                        class="form-control form-control-sm rounded-pill px-3" id="apellidoUno"
                                        name="apellidoUno" placeholder="Ej. Pérez">
                                </div>

                                <div class="col-md-6">
                                    <label for="apellidoDos" class="form-label text-muted fw-bold mb-1 ms-2"><small>Segundo
                                            Apellido</small></label>
                                    <input type="text" maxlength="50" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="apellidoDos"
                                        name="apellidoDos" placeholder="Ej. Gómez">
                                </div>

                                <!-- Toggle menor de edad -->
                                <div class="col-12">
                                    <div class="form-check form-switch ps-0 d-flex align-items-center gap-3">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch"
                                            id="es_menor" name="es_menor" value="1"
                                            style="width:2.5em; height:1.3em;">
                                        <label class="form-check-label fw-bold text-warning" for="es_menor">
                                            <i class="fas fa-child me-1"></i> Menor de edad / Sin cédula
                                        </label>
                                    </div>
                                </div>

                                <!-- Campo Cédula -->
                                <div class="col-md-6" id="bloque_cedula">
                                    <label for="cedula" id="label_cedula"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Cédula</small> <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select bg-light rounded-start-pill px-3" id="tipo_cedula"
                                            style="max-width: 80px;">
                                            <option value="V-">V-</option>
                                            <option value="E-">E-</option>
                                            <option value="P-">P-</option>
                                            <option value="J-">J-</option>
                                            <option value="X-">X-</option>
                                        </select>
                                        <input type="text" maxlength="20" autocomplete="off"
                                            class="form-control rounded-end-pill px-3" id="cedula_numero"
                                            placeholder="12345678"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label for="fechaNacimiento"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Fec. Nacimiento</small>
                                        <span class="text-danger">*</span></label>
                                    <input type="date" required class="form-control form-control-sm rounded-pill px-3"
                                        id="fechaNacimiento" name="fechaNacimiento" max="{{ date('Y-m-d') }}">
                                </div>

                                <div class="col-md-2">
                                    <label for="sexo"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Sexo</small> <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm rounded-pill px-3" id="sexo"
                                        name="sexo" required>
                                        <option value="" selected disabled>...</option>
                                        <option value="M">M</option>
                                        <option value="F">F</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Representante Legal (visible solo para menores) -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-4 d-none" id="bloque_representante"
                            style="border-left: 4px solid #0d6efd !important;">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                <div class="bg-primary bg-opacity-10 rounded-circle p-2"
                                    style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-shield-alt text-primary" style="font-size:0.85rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">Representante Legal</h6>
                                    <small class="text-muted">Datos del padre, madre o tutor del menor</small>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombre_representante"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Nombre del
                                            Representante</small> <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="255" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3 bg-white"
                                        id="nombre_representante" name="nombre_representante"
                                        placeholder="Ej. María Pérez">
                                </div>
                                <div class="col-md-6">
                                    <label for="cedula_representante"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Cédula del
                                            Representante</small> <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select bg-white rounded-start-pill px-3" id="tipo_cedula_rep"
                                            style="max-width: 80px;">
                                            <option value="V-">V-</option>
                                            <option value="E-">E-</option>
                                            <option value="P-">P-</option>
                                            <option value="X-">X-</option>
                                        </select>
                                        <input type="text" maxlength="20" autocomplete="off"
                                            class="form-control rounded-end-pill px-3 bg-white" id="cedula_rep_numero"
                                            name="cedula_rep_numero" placeholder="12345678"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto y Ubicación -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <span id="label_seccion_contacto">Contacto y Ubicación</span>
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="correo" class="form-label text-muted fw-bold mb-1 ms-2">
                                        <small id="label_correo">Correo Electrónico (Opcional)</small>
                                    </label>
                                    <input type="email" autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="correo"
                                        name="correo" placeholder="correo@ejemplo.com">
                                </div>

                                <div class="col-md-6">
                                    <label for="telefono_numero" class="form-label text-muted fw-bold mb-1 ms-2">
                                        <small id="label_telefono">Teléfono</small>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select bg-light rounded-start-pill px-3" id="codigo_pais"
                                            style="max-width: 90px;">
                                            <option value="+58" selected>+58</option>
                                            <option value="+1">+1</option>
                                            <option value="+57">+57</option>
                                        </select>
                                        <input type="text" maxlength="50" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3" id="telefono_numero"
                                            placeholder="4121234567"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="tel_emergencia_numero"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Telf.
                                            Emergencia</small></label>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select bg-light rounded-start-pill px-3"
                                            id="codigo_pais_emergencia" style="max-width: 90px;">
                                            <option value="+58" selected>+58</option>
                                            <option value="+1">+1</option>
                                            <option value="+57">+57</option>
                                        </select>
                                        <input type="text" maxlength="50" autocomplete="off"
                                            class="form-control rounded-end-pill px-3" id="tel_emergencia_numero"
                                            placeholder="4141234567"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="direccion"
                                        class="form-label text-muted fw-bold mb-1 ms-2"><small>Dirección</small> <span
                                            class="text-danger">*</span></label>
                                    <input type="text" maxlength="255" required autocomplete="off"
                                        class="form-control form-control-sm rounded-pill px-3" id="direccion"
                                        name="direccion" placeholder="Calle 123, Casa 456">
                                </div>
                            </div>
                        </div>

                        <!-- Tarifario / Convenio Asignado -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mt-4">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-handshake me-1"></i> Tarifario / Seguro Asignado
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="convenio_id" class="form-label text-muted fw-bold mb-1 ms-2">
                                        <small>Plan / Tarifario Aplicable</small>
                                    </label>
                                    <select class="form-select form-select-sm rounded-pill px-3" id="convenio_id" name="convenio_id">
                                        <option value="">Particular / Precio Estándar</option>
                                    </select>
                                    <small class="text-muted ms-2 mt-1 d-block">Aplica automáticamente los precios del convenio en los exámenes al facturar.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones del Modal -->
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="submit" form="formularioPaciente" id="guardarModal"
                                class="btn btn-primary rounded-pill px-4 ms-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Selección Historial -->
    <div id="modalSeleccionHistorial" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="fas fa-file-medical me-2"></i> Historial Clínico - <span id="historialNombrePaciente" class="ms-1"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <input type="hidden" id="historialPacienteId">
                    <p class="text-muted mb-3"><small>Seleccione los reportes de resultados que desea incluir en la impresión o enviar por correo.</small></p>
                    
                    <div class="table-responsive bg-white rounded-4 shadow-sm border p-3">
                        <table class="table table-hover align-middle mb-0" id="tablaHistorialOrdenes">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center align-middle">
                                        <input class="form-check-input shadow-sm" type="checkbox" id="checkAllHistorial" style="width: 1.5em; height: 1.5em; border: 2px solid #adb5bd; cursor: pointer;">
                                    </th>
                                    <th class="align-middle">Fecha</th>
                                    <th>Nro. Orden</th>
                                    <th>Exámenes Realizados</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="listaHistorialOrdenes">
                                <!-- Contenido inyectado por JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Acciones del Modal -->
                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        
                        <div>
                            <button type="button" id="btnImprimirHistorial" class="btn btn-outline-primary rounded-pill px-4 shadow-sm me-2" onclick="procesarHistorial('imprimir')">
                                <i class="fas fa-print me-1"></i> Imprimir Selección
                            </button>
                            <button type="button" id="btnEnviarCorreoHistorial" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="procesarHistorial('correo')">
                                <i class="fas fa-envelope me-1"></i> Enviar Correo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/paciente.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/paciente.js')) ?: time() }}">
    </script>
@endsection






