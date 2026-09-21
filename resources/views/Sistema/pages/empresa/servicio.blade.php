@extends('Sistema.layouts.app')

@section('titulo', '🛠️ Servicios')
@section('subtitulo', 'Catálogo de mano de obra, mantenimiento, asesorías y servicios profesionales')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Servicios</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nuevo Servicio"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <!-- TABLA PRINCIPAL DE SERVICIOS -->
    <x-datatable
        id="datatable_servicios"
        :headers="[
            'Servicio',
            'Categoría',
            'Precios (USD / Bs.)',
            'Régimen Fiscal',
            'Acciones',
        ]"
    />

    <!-- MODAL DE CREACIÓN / EDICIÓN -->
    <x-modal
        id="modalServicio"
        title="Nuevo Servicio"
        subtitle="Completa la información del servicio profesional"
        icon="fas fa-wrench text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioServicio"
        submitText="Guardar"
    >
        <form id="formularioServicio">
            @csrf
            
            <div class="row g-3">
                <!-- CATEGORÍA Y CÓDIGO -->
                <div class="col-md-6">
                    <label class="form-label-executive"><i class="fas fa-tags text-primary me-1"></i> Categoría <span class="text-danger">*</span></label>
                    <select name="categoria_id" id="categoria_id" class="form-select form-select-executive" required>
                        <option value="">Seleccione una categoría...</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <x-input name="codigo" id="codigo" label="Código / SKU del Servicio" icon="fas fa-hashtag"
                        placeholder="Ej. SRV-001, MAN-01" required maxlength="50" />
                </div>

                <!-- NOMBRE DEL SERVICIO -->
                <div class="col-12">
                    <x-input name="nombre" id="nombre" label="Nombre del Servicio" icon="fas fa-wrench"
                        placeholder="Ej. Mantenimiento Preventivo, Cambio de Aceite, Instalación..." required maxlength="150" />
                </div>

                <!-- DESCRIPCIÓN -->
                <div class="col-12">
                    <x-input name="descripcion" id="descripcion" label="Descripción / Alcance del Servicio" icon="fas fa-align-left"
                        placeholder="Detalles de la labor, tiempo estimado o especificaciones..." maxlength="1000" optionalText="Opcional" />
                </div>

                <!-- ESTRUCTURA DE PRECIOS -->
                <div class="col-md-7">
                    <div class="card border rounded-4 p-3 bg-light-subtle h-100">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-coins text-warning me-2"></i> Precio del Servicio</h6>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-2.5 py-1" style="font-size: 0.75rem;">
                                1 $ = <span id="badgeTasaUsd">1.0000</span> Bs.
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label-executive"><i class="fas fa-dollar-sign text-primary me-1"></i> Precio ($ USD) <span class="text-danger">*</span></label>
                                <input type="number" step="0.0001" min="0" class="form-control form-control-executive text-end fw-bold text-dark" id="precio_venta_usd" name="precio_venta_usd" placeholder="0.00" required oninput="calcularPreciosBsDesdeUsd()">
                            </div>
                            <div class="col-6">
                                <label class="form-label-executive"><i class="fas fa-coins text-success me-1"></i> Precio (Bs.)</label>
                                <input type="number" step="0.0001" min="0" class="form-control form-control-executive text-end fw-bold text-dark" id="precio_venta_bs" name="precio_venta_bs" placeholder="0.00" oninput="calcularPreciosUsdDesdeBs()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RÉGIMEN FISCAL (IVA) -->
                <div class="col-md-5">
                    <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-file-invoice-dollar text-warning me-1"></i> Régimen Fiscal</h6>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between border rounded-3 p-2 bg-light-subtle">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" role="switch" id="aplica_iva" name="aplica_iva" value="1" onchange="toggleIvaInput()">
                                        <label class="form-check-label fw-bold text-dark small" for="aplica_iva">Aplica IVA</label>
                                    </div>
                                    <div style="width: 100px;" id="contenedorIvaPorcentaje">
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.01" min="0" max="100" class="form-control text-end fw-bold font-monospace" id="iva_porcentaje" name="iva_porcentaje" value="16.00">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted d-block mt-1 ps-1" style="font-size: 0.73rem;">
                                    Si se desactiva, el servicio quedará <strong>Exento de IVA</strong>.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/servicio.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/servicio.js')) ?: time() }}">
    </script>
@endsection
