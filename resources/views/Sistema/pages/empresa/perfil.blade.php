@extends('Sistema.layouts.app')

@section('titulo', 'Perfiles y Combos')
@section('subtitulo', 'Administración de Perfiles')

@section('contenido')
    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-cubes"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Perfiles y Combos de Exámenes</h2>
                    <p class="text-muted mb-0 small">Paquetes clínicos, paneles preventivos y combos promocionales</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nuevo Perfil
            </button>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="datatable_perfiles" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Perfil / Combo</th>
                            <th class="border-0 text-center">Exámenes Incluidos</th>
                            <th class="border-0">Descripción</th>
                            <th class="border-0 text-center">Precio Combo</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Wizard Perfil -->
    <div id="modalPerfil" class="modal fade" data-bs-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="tituloModal" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-cubes me-2"></i> Nuevo Perfil de Exámenes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light">

                    <!-- Barra de Pasos / Stepper -->
                    <div class="position-relative mb-4 mt-2">
                        <div class="progress rounded-pill" style="height: 4px;">
                            <div id="progreso-wizard" class="progress-bar bg-primary" role="progressbar" style="width: 0%;"
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div
                            class="d-flex justify-content-between position-absolute top-50 start-0 translate-middle-y w-100 px-1">
                            <button id="pillo-paso-1" type="button"
                                class="btn btn-sm btn-primary rounded-circle p-0 d-flex align-items-center justify-content-center fw-bold shadow"
                                style="width: 32px; height: 32px; z-index: 2;">1</button>
                            <button id="pillo-paso-2" type="button"
                                class="btn btn-sm btn-secondary rounded-circle p-0 d-flex align-items-center justify-content-center fw-bold shadow"
                                style="width: 32px; height: 32px; z-index: 2;" disabled>2</button>
                            <button id="pillo-paso-3" type="button"
                                class="btn btn-sm btn-secondary rounded-circle p-0 d-flex align-items-center justify-content-center fw-bold shadow"
                                style="width: 32px; height: 32px; z-index: 2;" disabled>3</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between text-muted fw-bold mb-4 px-1" style="font-size: 0.75rem;">
                        <span class="text-primary text-start" style="width: 33%">1. Información General</span>
                        <span class="text-center" style="width: 33%">2. Asignar Exámenes</span>
                        <span class="text-end" style="width: 33%">3. Resumen y Confirmación</span>
                    </div>

                    <form id="formularioPerfil">
                        @csrf

                        <!-- FASE 1: DETALLES -->
                        <div id="fase-1" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-info-circle me-1"></i> Fase 1: Datos del Perfil
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-8 col-12">
                                    <label for="nombre" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Nombre del Perfil / Paquete <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-cubes"></i>
                                        </span>
                                        <input type="text" maxlength="150" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="nombre" name="nombre"
                                            placeholder="Ej. Perfil Pre-Operativo, Perfil Lipídico, Rutina Anual...">
                                    </div>
                                </div>

                                <div class="col-md-4 col-12">
                                    <label for="precio" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Precio Combo ($) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                        <input type="number" step="0.01" min="0" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="precio"
                                            name="precio" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="descripcion" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Descripción o Beneficios <span class="text-secondary fw-normal">(Opcional)</span>
                                    </label>
                                    <textarea class="form-control rounded-4 px-3 py-2" id="descripcion" name="descripcion"
                                        rows="3" placeholder="Ej. Incluye pruebas básicas de química y hematología requeridas para evaluaciones médicas previas a cirugías..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- FASE 2: ASIGNACIÓN DE EXÁMENES -->
                        <div id="fase-2" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border d-none">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-vials me-1"></i> Fase 2: Exámenes Incluidos en el Perfil
                            </h6>

                            <div class="row g-2 align-items-end mb-4 bg-light p-3 rounded-4 border shadow-xs">
                                <div class="col-md-9 col-12">
                                    <label for="select-examen-asociar" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Buscar Examen para Añadir
                                    </label>
                                    <select class="form-select select2-search px-3"
                                        id="select-examen-asociar" style="width: 100%;">
                                        <option value="">Seleccione o busque un examen...</option>
                                        @foreach ($examenes as $examen)
                                            <option value="{{ $examen->id }}" data-precio="{{ $examen->precio }}"
                                                data-categoria="{{ $examen->categoria->nombre ?? 'Sin área' }}">
                                                {{ $examen->nombre }} (${{ number_format($examen->precio, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 col-12">
                                    <button type="button" class="btn btn-success w-100 shadow-sm rounded-pill"
                                        onclick="agregarExamenALista()" style="height: 38px;">
                                        <i class="fas fa-plus me-1"></i> Vincular
                                    </button>
                                </div>
                            </div>

                            <div class="border rounded-4 overflow-hidden shadow-sm bg-white">
                                <div class="row g-0 bg-dark text-white p-2 fw-bold small text-center d-none d-md-flex">
                                    <div class="col-md-6 text-start ps-3">Examen Clínico</div>
                                    <div class="col-md-4">Área / Especialidad</div>
                                    <div class="col-md-2 text-end pe-3">Acción</div>
                                </div>

                                <div id="contenedor-examenes-asociados" style="min-height: 80px;">
                                </div>
                            </div>

                            <div id="alerta-examenes" class="alert alert-warning rounded-4 py-2 mt-3 mb-0 d-none"
                                style="font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Es obligatorio vincular al menos un examen para estructurar este perfil.
                            </div>
                        </div>

                        <!-- FASE 3: RESUMEN -->
                        <div id="fase-3" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border d-none">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-eye me-1"></i> Fase 3: Resumen del Combo Comercial
                            </h6>

                            <div class="table-responsive border rounded-4 mb-4 bg-light p-2">
                                <table class="table table-sm table-borderless mb-0 align-middle small"
                                    style="background: transparent;">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2" style="width: 35%;">Nombre del Perfil:</td>
                                            <td id="preview-nombre" class="fw-bold text-dark py-2">-</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Precio Comercial Especial:</td>
                                            <td id="preview-precio" class="fw-bold text-success py-2">$0.00</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Descripción:</td>
                                            <td id="preview-descripcion" class="text-muted fst-italic py-2">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-dark fw-bold mb-2 ms-1 small"><i class="fas fa-tasks me-1"></i> Desglose de Pruebas Incluidas:</h6>
                            <div class="border rounded-4 overflow-hidden bg-white shadow-sm mb-2">
                                <div class="row g-0 bg-dark text-white p-2 fw-bold small text-center d-none d-md-flex">
                                    <div class="col-md-7 text-start ps-3">Examen Clínico</div>
                                    <div class="col-md-5">Área / Especialidad</div>
                                </div>
                                <div id="preview-contenedor-examenes" class="p-2 bg-white">
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer bg-white border-top p-3">
                    <button type="button" id="btn-wizard-cancelar" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>

                    <button type="button" id="btn-wizard-anterior"
                        class="btn btn-secondary rounded-pill px-4 d-none" onclick="navegarFase(-1)">
                        <i class="fas fa-arrow-left me-1"></i> Anterior
                    </button>

                    <button type="button" id="btn-wizard-siguiente" class="btn btn-primary rounded-pill px-4 shadow-sm"
                        onclick="navegarFase(1)">
                        Siguiente <i class="fas fa-arrow-right ms-1"></i>
                    </button>

                    <button type="submit" form="formularioPerfil" id="guardarModal"
                        class="btn btn-success rounded-pill px-4 shadow-sm d-none">
                        <i class="fas fa-check-circle me-1"></i> Confirmar y Crear Combo
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Template Fila Examen Asociado -->
    <template id="template-examen-fila">
        <div class="row g-0 py-2 border-bottom align-items-center text-center text-dark small examen-asociado-item bg-white">
            <input type="hidden" class="examen-vinculado-id" name="examenes[__INDEX__]" value="__ID__">

            <div class="col-md-6 text-start ps-3 fw-bold examen-texto-nombre">
                <i class="fas fa-flask text-primary me-2"></i>__NOMBRE__
            </div>
            <div class="col-md-4 text-muted examen-texto-categoria">
                <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small">__CATEGORIA__</span>
            </div>
            <div class="col-md-2 text-end pe-3">
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-eliminar-examen-link"
                    onclick="removerExamenDeLista(this)" title="Desvincular Examen">
                    <i class="fas fa-trash me-1"></i> Quitar
                </button>
            </div>
        </div>
    </template>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/perfil.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/perfil.js')) ?: time() }}">
    </script>
    <script>
        $(document).ready(function() {
            $('.select2-search').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione o busque un examen...',
                dropdownParent: $('#modalPerfil')
            });
        });
    </script>
@endsection
