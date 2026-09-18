@extends('Sistema.layouts.app')

@section('titulo', 'Áreas y Categorías')
@section('subtitulo', 'Administración de Áreas')

@section('contenido')
    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-tags"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Áreas y Categorías</h2>
                    <p class="text-muted mb-0 small">Organización de especialidades y departamentos de procesamiento clínico</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md text-md-end">
            <a href="{{ route('categorias.imprimir-examenes') }}" target="_blank" class="btn btn-outline-danger rounded-pill px-3 shadow-sm me-2">
                <i class="fas fa-file-pdf me-1"></i> Catálogo PDF
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nueva Área
            </button>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="datatable_categoria" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Área / Especialidad</th>
                            <th class="border-0 text-center">Exámenes Registrados</th>
                            <th class="border-0 text-center">Fecha de Registro</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Área / Categoría -->
    <div id="modalCategoria" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModal"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-tags me-2"></i> Nueva Área
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">
                    <form id="formularioCategoria">
                        @csrf

                        <!-- Detalles del Área -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-info-circle me-1"></i> Información del Área
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="nombre" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Nombre del Área / Categoría <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <input type="text" maxlength="100" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="nombre" name="nombre"
                                            placeholder="Ej. Hematología, Bioquímica, Inmunología...">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones del Modal -->
                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="submit" form="formularioCategoria" id="guardarModal"
                                class="btn btn-primary rounded-pill px-4 ms-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar Área
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/categoria.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/categoria.js')) ?: time() }}">
    </script>
@endsection
