@extends('Sistema.layouts.app')

@section('titulo', 'Tarifarios y Convenios')
@section('subtitulo', 'Administración de Listas de Precios')

@section('contenido')
    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-handshake fa-lg"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Tarifarios y Convenios</h2>
                    <p class="text-muted mb-0 small">Gestión de listas de precios diferenciadas para seguros, clínicas, empresas y particulares</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nuevo Tarifario / Convenio
            </button>
        </div>
    </div>

    <!-- Contenedor Principal de la Tabla -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="datatable_convenio" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Tarifario / Convenio</th>
                            <th class="border-0 text-center">Descuento General</th>
                            <th class="border-0 text-center">Precios Fijos</th>
                            <th class="border-0 text-center">Pacientes</th>
                            <th class="border-0 text-center">Tipo</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Registro / Edición de Convenio -->
    <div id="modalConvenio" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-handshake me-2"></i> Nuevo Tarifario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form id="formularioConvenio">
                        @csrf

                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-info-circle me-1"></i> Información del Convenio
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="nombre" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Nombre del Convenio / Tarifario <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-primary px-3">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <input type="text" maxlength="150" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="nombre" name="nombre"
                                            placeholder="Ej. Seguros Mercantil, Clínica Ávila, Particular...">
                                    </div>
                                </div>

                                <div class="col-md-12">
                                    <label for="descripcion" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Descripción / Notas (Opcional)
                                    </label>
                                    <textarea class="form-control rounded-4 px-3" id="descripcion" name="descripcion" rows="2"
                                        placeholder="Detalles sobre las condiciones del convenio..."></textarea>
                                </div>

                                <div class="col-md-12">
                                    <label for="porcentaje_general" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Descuento General Base (%)
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-primary px-3">
                                            <i class="fas fa-percent"></i>
                                        </span>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control rounded-end-pill px-3 border-start-0"
                                            id="porcentaje_general" name="porcentaje_general" value="0" placeholder="0.00">
                                    </div>
                                    <small class="text-secondary d-block mt-1 ms-1 fw-medium">
                                        Se aplicará automáticamente a los exámenes que no tengan un precio fijo específico configurado.
                                    </small>
                                </div>

                                <div class="col-md-12">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="es_predeterminado" name="es_predeterminado" value="1">
                                        <label class="form-check-label fw-bold text-dark small" for="es_predeterminado">
                                            Tarifario predeterminado para nuevos pacientes
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones del Modal -->
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="submit" form="formularioConvenio" id="guardarModal"
                                class="btn btn-primary rounded-pill px-4 ms-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar Tarifario
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Matriz de Precios por Examen y Perfil -->
    <div id="modalPreciosMatriz" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModalPrecios" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <div>
                        <h5 class="modal-title fw-bold d-flex align-items-center mb-0" id="tituloModalPrecios">
                            <i class="fas fa-dollar-sign me-2"></i> Configurar Precios Especiales
                        </h5>
                        <small class="text-white-50" id="subtituloModalPrecios">Convenio: ...</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- Barra de Búsqueda y Filtros Rápidos -->
                    <div class="bg-white p-3 rounded-4 shadow-sm border mb-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-12 col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-pill text-primary px-3">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="filtroPreciosMatriz" class="form-control rounded-end-pill px-3 border-start-0"
                                        placeholder="Buscar examen o perfil..." onkeyup="filtrarTablaPrecios()">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <select id="filtroTipoMatriz" class="form-select rounded-pill fw-semibold text-dark" onchange="filtrarTablaPrecios()">
                                    <option value="todos">Todos los servicios</option>
                                    <option value="examen">Solo Exámenes</option>
                                    <option value="perfil">Solo Perfiles</option>
                                    <option value="modificados">Solo con Precio Especial</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-4 text-end">
                                <span class="badge bg-white text-dark border shadow-xs rounded-pill px-3 py-2 fw-semibold">
                                    <span id="contadorMostrados" class="fw-bold text-primary">0</span> servicios visibles
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Precios -->
                    <div class="bg-white rounded-4 shadow-sm border overflow-hidden">
                        <div class="table-responsive" style="max-height: 52vh;">
                            <table class="table table-hover align-middle mb-0" id="tablaMatrizPrecios">
                                <thead class="table-light sticky-top" style="z-index: 10;">
                                    <tr>
                                        <th class="ps-3 border-0">Servicio / Examen</th>
                                        <th class="border-0">Tipo / Área</th>
                                        <th class="border-0 text-center">Precio Base</th>
                                        <th class="border-0 text-center" style="width: 200px;">Precio Convenio ($)</th>
                                        <th class="border-0 text-center">Precio Final ($)</th>
                                        <th class="border-0 text-end pe-3 rounded-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="cuerpoMatrizPrecios">
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-spinner fa-spin me-2"></i> Cargando catálogo...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-white border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <button type="button" id="btnGuardarMatriz" class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="guardarMatrizPrecios()">
                        <i class="fas fa-save me-1"></i> Guardar Todos los Precios
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/convenio.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/convenio.js')) ?: time() }}"></script>
@endsection
