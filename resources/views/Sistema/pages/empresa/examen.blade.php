@extends('Sistema.layouts.app')

@section('titulo', 'Exámenes y Parámetros')
@section('subtitulo', 'Administración de Exámenes')

@section('contenido')
    <style>
        .select2-container--bootstrap-5 .select2-selection,
        .select2-container .select2-selection--single {
            border-radius: 50rem !important;
            height: 38px !important;
            padding: 0.375rem 0.85rem !important;
            border: 1px solid #dee2e6 !important;
            display: flex !important;
            align-items: center !important;
            background-color: #ffffff !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
        .select2-container .select2-selection--single .select2-selection__rendered {
            padding-left: 0.25rem !important;
            color: #212529 !important;
            font-size: 0.9rem !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow,
        .select2-container .select2-selection--single .select2-selection__arrow {
            right: 14px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
    </style>

    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-flask"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Exámenes y Parámetros Clínicos</h2>
                    <p class="text-muted mb-0 small">Catálogo de pruebas diagnósticas, valores de referencia y recetas de insumos</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nuevo Examen
            </button>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">

            <!-- Filtros Rápidos de Modalidad -->
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="filtros-examenes-rapido">
                <span class="text-muted small fw-bold mt-1 me-2">
                    <i class="fas fa-filter text-primary"></i> Filtro rápido:
                </span>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                    onclick="filtrarRapidoVenta('', this)">
                    <i class="fas fa-flask me-1"></i> Todos
                </button>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill"
                    onclick="filtrarRapidoVenta('individual', this)">
                    <i class="fas fa-check-circle me-1"></i> Venta Individual
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill"
                    onclick="filtrarRapidoVenta('perfil', this)">
                    <i class="fas fa-cubes me-1"></i> Solo en Perfiles
                </button>
                <button type="button" class="btn btn-outline-info btn-sm rounded-pill"
                    onclick="filtrarRapidoVenta('antibiograma', this)">
                    <i class="fas fa-dna me-1"></i> Con Antibiograma
                </button>
            </div>

            <!-- Filtro Avanzado por Área / Categoría -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                    <label for="filtro_categoria" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-tags text-primary me-1"></i> Filtrar por Área / Especialidad
                    </label>
                    <select id="filtro_categoria" class="form-select rounded-pill">
                        <option value="">Todas las Áreas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-3 col-lg-2">
                    <button type="button" class="btn btn-outline-danger rounded-pill w-100 shadow-sm"
                        onclick="limpiarFiltrosExamenes()" title="Restablecer todos los filtros">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla de Exámenes -->
            <div class="table-responsive">
                <table id="datatable_examenes" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Examen Clínico</th>
                            <th class="border-0">Área / Especialidad</th>
                            <th class="border-0 text-center">Precio Unitario</th>
                            <th class="border-0 text-center">Modalidad</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Wizard Registrar / Editar / Ver Examen -->
    <div id="modalExamen" class="modal fade" data-bs-backdrop="static" tabindex="-1" role="dialog"
        aria-labelledby="tituloModal" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-flask me-2"></i> Nuevo Registro de Examen
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
                            <button id="pillo-paso-4" type="button"
                                class="btn btn-sm btn-secondary rounded-circle p-0 d-flex align-items-center justify-content-center fw-bold shadow"
                                style="width: 32px; height: 32px; z-index: 2;" disabled>4</button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between text-muted fw-bold mb-4 px-1" style="font-size: 0.75rem;">
                        <span class="text-primary text-start" style="width: 25%">1. Datos Generales</span>
                        <span class="text-center" style="width: 25%">2. Parámetros</span>
                        <span class="text-center" style="width: 25%">3. Receta Insumos</span>
                        <span class="text-end" style="width: 25%">4. Confirmación</span>
                    </div>

                    <form id="formularioExamen">
                        @csrf

                        <!-- FASE 1: DATOS GENERALES -->
                        <div id="fase-1" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-info-circle me-1"></i> Fase 1: Datos Generales del Examen
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6 col-12">
                                    <label for="nombre" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Nombre del Examen <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-flask"></i>
                                        </span>
                                        <input type="text" maxlength="150" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="nombre" name="nombre"
                                            placeholder="Ej. Hematología Completa, Perfil Lipídico...">
                                    </div>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label for="categoria_id" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Área / Especialidad <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select rounded-pill px-3 select2-search" id="categoria_id"
                                        name="categoria_id" required style="width: 100%;">
                                        <option value="">Seleccione un área...</option>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 col-12">
                                    <label for="precio" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Precio Individual ($) <span class="text-danger">*</span>
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

                                <div class="col-md-3 col-12 d-flex align-items-center mt-md-4">
                                    <div class="form-check form-switch ms-2">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="venta_individual" name="venta_individual" value="1" checked>
                                        <label class="form-check-label text-dark fw-bold ms-1"
                                            for="venta_individual"><small>Venta individual</small></label>
                                    </div>
                                </div>

                                <div class="col-md-3 col-12 d-flex align-items-center mt-md-4">
                                    <div class="form-check form-switch ms-2">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="requiere_antibiograma" name="requiere_antibiograma" value="1">
                                        <label class="form-check-label text-info fw-bold ms-1"
                                            for="requiere_antibiograma" title="Habilita módulo bacteriológico"><small><i class="fas fa-dna me-1"></i>Antibiograma</small></label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="descripcion" class="form-label text-muted fw-bold mb-1 ms-1 small">
                                        Indicaciones o Descripción <span class="text-secondary fw-normal">(Opcional)</span>
                                    </label>
                                    <textarea class="form-control rounded-4 px-3 py-2" id="descripcion" name="descripcion"
                                        rows="3" placeholder="Requerimientos de preparación del paciente (ayuno, recolección), reactivos o notas internas..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- FASE 2: PARÁMETROS -->
                        <div id="fase-2" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border d-none">
                            <div
                                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center border-bottom pb-2 mb-3">
                                <h6 class="text-primary fw-bold mb-3 mb-md-0">
                                    <i class="fas fa-list-ul me-1"></i> Fase 2: Parámetros y Valores de Referencia
                                </h6>
                                <div class="d-flex gap-2">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                        onclick="agregarParametroSuelto()">
                                        <i class="fas fa-plus me-1"></i> Parámetro Suelto
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm"
                                        onclick="agregarSeccion()">
                                        <i class="fas fa-folder-plus me-1"></i> Nueva Sección
                                    </button>
                                </div>
                            </div>

                            <div id="contenedor-parametros">
                            </div>

                            <div id="alerta-parametros" class="alert alert-warning rounded-4 py-2 mt-2 mb-0 d-none"
                                style="font-size: 0.85rem;">
                                <i class="fas fa-exclamation-triangle me-1"></i> Es obligatorio configurar al menos un
                                parámetro para este examen médico.
                            </div>
                        </div>

                        <!-- FASE 3: RECETA DE INSUMOS -->
                        <div id="fase-3" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border d-none">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="text-primary fw-bold mb-0">
                                    <i class="fas fa-boxes me-1"></i> Fase 3: Receta de Insumos (Opcional)
                                </h6>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm"
                                    onclick="agregarInsumo()">
                                    <i class="fas fa-plus me-1"></i> Agregar Insumo
                                </button>
                            </div>
                            <p class="text-muted small mb-3">Selecciona los productos del inventario que se consumirán automáticamente al procesar este examen.</p>

                            <div id="contenedor-insumos">
                                <!-- Insumos dinámicos aquí -->
                            </div>
                        </div>

                        <!-- FASE 4: VISTA PREVIA -->
                        <div id="fase-4" class="fase-wizard bg-white p-4 rounded-4 shadow-sm border d-none">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold">
                                <i class="fas fa-eye me-1"></i> Fase 4: Confirmación Visual y Resumen
                            </h6>

                            <div class="table-responsive border rounded-4 mb-4 bg-light p-2">
                                <table class="table table-sm table-borderless mb-0 align-middle small"
                                    style="background: transparent;">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2" style="width: 30%;">Nombre:</td>
                                            <td id="preview-nombre" class="fw-bold text-dark py-2">-</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Área / Especialidad:</td>
                                            <td id="preview-categoria" class="text-dark py-2">-</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Precio Individual:</td>
                                            <td id="preview-precio" class="fw-bold text-success py-2">$0.00</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Modalidad de Venta:</td>
                                            <td id="preview-venta" class="text-dark py-2">-</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted ps-3 py-2">Indicaciones:</td>
                                            <td id="preview-descripcion" class="text-muted fst-italic py-2">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="text-dark fw-bold mb-2 ms-1 small"><i class="fas fa-vial me-1"></i> Campos
                                Estructurados para Resultados:</h6>

                            <div class="border rounded-4 overflow-hidden bg-white shadow-sm mb-2">
                                <div class="row g-0 bg-dark text-white p-2 fw-bold small text-center d-none d-md-flex">
                                    <div class="col-md-4 text-start ps-3">Parámetro</div>
                                    <div class="col-md-3">Tipo de Entrada</div>
                                    <div class="col-md-2">Unidad</div>
                                    <div class="col-md-3">Rango Referencial</div>
                                </div>
                                <div id="preview-contenedor-parametros" class="p-2 bg-white">
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

                    <button type="submit" form="formularioExamen" id="guardarModal"
                        class="btn btn-success rounded-pill px-4 shadow-sm d-none">
                        <i class="fas fa-check-circle me-1"></i> Confirmar y Guardar
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Templates -->
    <template id="template-parametro">
        <div class="parametro-item bg-light p-3 rounded-4 border shadow-xs mb-3">
            <input type="hidden" class="parametro-id" name="parametros[__INDEX__][id]" value="">
            <input type="hidden" class="parametro-seccion" name="parametros[__INDEX__][seccion]" value="">

            <div class="row g-2">
                <div class="col-md-6 col-12">
                    <label class="form-label text-muted mb-0 ms-1 small fw-bold">Nombre del Parámetro <span class="text-danger">*</span></label>
                    <input type="text" maxlength="100"
                        class="form-control rounded-pill px-3 parametro-nombre"
                        name="parametros[__INDEX__][nombre]" placeholder="Ej. Hemoglobina, Leucocitos, Glucosa" required>
                </div>

                <div class="col-md-6 col-12">
                    <label class="form-label text-muted mb-0 ms-1 small fw-bold">Tipo de Entrada (Carga) <span class="text-danger">*</span></label>
                    <select class="form-select rounded-pill px-3 parametro-tipo"
                        name="parametros[__INDEX__][tipo_input]" onchange="toggleOpcionesInput(this)" required>
                        <option value="texto">Texto Libre (Alfanumérico)</option>
                        <option value="numero">Numérico puro</option>
                        <option value="opciones">Lista Desplegable (Checklist)</option>
                    </select>
                </div>

                <div class="col-md-12 col-12 d-none contenedor-opciones mt-2">
                    <label class="form-label text-muted mb-1 ms-1 small fw-bold text-primary">Opciones de la Lista * <span class="text-muted fw-normal">(Separadas por comas. Ej: Amarillo, Ámbar, Rojo, Incoloro)</span></label>
                    <textarea rows="2" class="form-control rounded-3 px-3 py-2 parametro-opciones shadow-none"
                        name="parametros[__INDEX__][opciones]" placeholder="Opción 1, Opción 2, Opción 3, Opción 4..."></textarea>
                </div>

                <div class="col-md-4 col-12">
                    <label class="form-label text-muted mb-0 ms-1 small">Unidad de Medida</label>
                    <input type="text" maxlength="50"
                        class="form-control rounded-pill px-3 parametro-unidad"
                        name="parametros[__INDEX__][unidad_medida]" placeholder="Ej. g/dL, %, mg/L">
                </div>

                <div class="col-md-6 col-10">
                    <label class="form-label text-muted mb-0 ms-1 small">Valores de Referencia</label>
                    <input type="text" maxlength="150"
                        class="form-control rounded-pill px-3 parametro-rango"
                        name="parametros[__INDEX__][rango_referencia]" placeholder="Ej. 12.0 - 16.0 g/dL">
                </div>

                <div class="col-md-2 col-2 d-flex align-items-end">
                    <button type="button"
                        class="btn btn-outline-danger w-100 rounded-pill btn-eliminar-param d-flex align-items-center justify-content-center"
                        onclick="eliminarParametro(this)" title="Remover Parámetro" style="height: 38px;">
                        <i class="fas fa-trash me-1"></i> <span class="d-none d-md-inline small">Quitar</span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template id="template-seccion">
        <div class="seccion-item bg-white p-3 rounded-4 border shadow-sm mb-4" style="border-left: 4px solid #0d6efd !important;">
            <div
                class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 border-bottom pb-2">
                <div class="input-group w-100 w-md-50 mb-2 mb-md-0">
                    <span class="input-group-text bg-primary text-white border-primary rounded-start-pill ps-3">
                        <i class="fas fa-folder"></i>
                    </span>
                    <input type="text"
                        class="form-control fw-bold border-primary rounded-end-pill px-3 seccion-nombre text-primary"
                        placeholder="Nombre de la Sección (Ej. ANÁLISIS QUÍMICO)"
                        onkeyup="actualizarSeccionParametros(this)">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs"
                        onclick="agregarParametroASeccion(this)">
                        <i class="fas fa-plus me-1"></i> Parámetro
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-xs"
                        onclick="eliminarSeccion(this)" title="Eliminar Sección Completa">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="contenedor-parametros-seccion px-2">
            </div>
        </div>
    </template>

    <template id="template-insumo">
        <div class="insumo-item bg-light p-3 rounded-4 border shadow-xs mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6 col-12">
                    <label class="form-label text-muted mb-0 ms-1 small fw-bold">Producto de Inventario <span class="text-danger">*</span></label>
                    <select class="form-select rounded-pill px-3 insumo-select"
                        name="insumos[__INDEX__][inventario_id]" required>
                        <option value="">Seleccione un insumo...</option>
                        @foreach ($inventarios as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->nombre }} (Stock: {{ $inv->cantidad }}
                                {{ $inv->unidadMedida }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-10">
                    <label class="form-label text-muted mb-0 ms-1 small fw-bold">Cantidad a Descontar <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01"
                        class="form-control rounded-pill px-3 insumo-cantidad"
                        name="insumos[__INDEX__][cantidad]" placeholder="Ej. 1.5" required>
                </div>
                <div class="col-md-2 col-2 d-flex">
                    <button type="button"
                        class="btn btn-outline-danger w-100 rounded-pill d-flex align-items-center justify-content-center"
                        onclick="this.closest('.insumo-item').remove()" title="Remover Insumo" style="height: 38px;">
                        <i class="fas fa-trash me-1"></i> <span class="d-none d-md-inline small">Quitar</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/examen.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/examen.js')) ?: time() }}">
    </script>
    <script>
        $(document).ready(function() {
            $('.select2-search').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione un área...',
                dropdownParent: $('#modalExamen')
            });
        });
    </script>
@endsection
