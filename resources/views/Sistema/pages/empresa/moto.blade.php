@extends('Sistema.layouts.app')

@section('titulo', '🏍️ Motos & Catálogo de Modelos')
@section('subtitulo', 'Gestión de catálogo maestro de modelos, referencias únicas, trazabilidad de seriales (N.I.V., Chasis, Motor) y stock por sede')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Motos & Modelos</span>
@endsection

@section('acciones')
    <div class="d-flex align-items-center gap-2">
        <x-button variant="primary" icon="fas fa-plus" text="Nuevo Modelo de Moto" onclick="abrirModalCrearModelo()" />
        <x-button href="{{ route('recepcion_moto') }}" variant="outline-primary" icon="fas fa-truck-ramp-box" text="Nueva Recepción" />
    </div>
@endsection

@section('contenido')
    <!-- NAVEGACIÓN POR PESTAÑAS EJECUTIVAS -->
    <div class="card border rounded-4 p-2 shadow-xs mb-4 bg-white">
        <ul class="nav nav-pills nav-fill gap-2 p-1" id="pills-tab-motos" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2"
                    id="pills-modelos-tab" data-bs-toggle="pill" data-bs-target="#pills-modelos" type="button" role="tab"
                    aria-controls="pills-modelos" aria-selected="true">
                    <i class="fas fa-layer-group"></i>
                    <span>Catálogo de Modelos & Referencias</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2"
                    id="pills-unidades-tab" data-bs-toggle="pill" data-bs-target="#pills-unidades" type="button" role="tab"
                    aria-controls="pills-unidades" aria-selected="false">
                    <i class="fas fa-motorcycle"></i>
                    <span>Inventario Físico & Seriales Únicos</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- CONTENIDO DE LAS PESTAÑAS -->
    <div class="tab-content" id="pills-tabContentMotos">
        <!-- PESTAÑA 1: CATÁLOGO DE MODELOS -->
        <div class="tab-pane fade show active" id="pills-modelos" role="tabpanel" aria-labelledby="pills-modelos-tab">
            <x-datatable
                id="datatable_modelos"
                :headers="[
                    'Ref #',
                    'Marca / Modelo',
                    'Año / Color',
                    'Cilindrada',
                    'Stock Disponible',
                    'Total Ingresadas',
                    'Acciones',
                ]"
            />
        </div>

        <!-- PESTAÑA 2: INVENTARIO FÍSICO DE UNIDADES -->
        <div class="tab-pane fade" id="pills-unidades" role="tabpanel" aria-labelledby="pills-unidades-tab">
            <x-datatable
                id="datatable_motos"
                :headers="[
                    'Ref / Modelo',
                    'Año / Color',
                    'N.I.V. (VIN)',
                    'N° Chasis / Bastidor',
                    'N° Motor',
                    'Almacén Actual',
                    'Precio Detal',
                    'Precio Mayor',
                    'Estado',
                    'Acciones',
                ]"
            />
        </div>
    </div>

    <!-- MODAL: CREAR / EDITAR MODELO DE MOTO -->
    <x-modal
        id="modalModeloMoto"
        title="Registrar Modelo de Moto"
        subtitle="Defina las características del modelo y su referencia única para el catálogo y el POS"
        icon="fas fa-layer-group text-primary fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioModeloMoto"
        submitText="Guardar Modelo"
        submitIcon="fas fa-save me-1"
    >
        <form id="formularioModeloMoto">
            <input type="hidden" id="modelo_moto_id" name="id">

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-tag text-primary me-2"></i> Identificación & Especificaciones</h6>
                <div class="row g-3">
                    <x-input id="modelo_referencia" name="referencia" label="Referencia / Código Único" icon="fas fa-hashtag text-primary" placeholder="Ej. 1" required col="col-md-3" class="font-monospace fw-bold" inputmode="numeric" />
                    <x-input id="modelo_marca" name="marca" label="Marca" icon="fas fa-copyright text-secondary" placeholder="Ej. Bera, Empire, Toro..." required col="col-md-3" />
                    <x-input id="modelo_modelo" name="modelo" label="Modelo" icon="fas fa-motorcycle text-secondary" placeholder="Ej. SBR 150, Horse 150..." required col="col-md-3" />
                    <x-input type="number" id="modelo_anio" name="anio" label="Año" icon="fas fa-calendar text-secondary" min="1990" max="2099" value="{{ date('Y') }}" required col="col-md-3" class="font-monospace" />
                    <x-input id="modelo_color" name="color" label="Color" icon="fas fa-palette text-secondary" placeholder="Ej. Azul, Rojo, Negro, Blanco..." required col="col-md-6" />
                    <x-input id="modelo_cilindrada" name="cilindrada" label="Cilindrada" icon="fas fa-tachometer-alt text-secondary" placeholder="Ej. 150cc, 200cc, 250cc..." value="150cc" required col="col-md-6" class="font-monospace" />
                    <div class="col-12">
                        <label class="form-label-executive"><i class="fas fa-comment-dots text-secondary me-1"></i> Descripción / Detalles Adicionales</label>
                        <textarea id="modelo_descripcion" name="descripcion" class="form-control form-control-executive" rows="2" placeholder="Observaciones generales o detalles de equipamiento del modelo (opcional)"></textarea>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL: FICHA TÉCNICA 360° -->
    <x-modal
        id="modalFichaMoto"
        title="Ficha Técnica 360° del Vehículo"
        subtitle="NIV: --"
        icon="fas fa-motorcycle text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        :submitButton="false"
    >
        <div id="contenidoFichaMoto"></div>

        <x-slot:footer>
            <button type="button" class="btn btn-executive-cancel" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i> Cerrar
            </button>
            <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" id="btnEditarDesdeFicha">
                <i class="fas fa-edit me-1"></i> Editar Información
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- MODAL: EDITAR INFORMACIÓN DE MOTO INDIVIDUAL -->
    <x-modal
        id="modalEditarMoto"
        title="Modificar Datos del Vehículo"
        subtitle="Edición integral de modelo, seriales legales, precios y ubicación"
        icon="fas fa-edit text-info fs-5"
        size="modal-xl"
        headerColor="bg-dark text-white"
        formId="formularioEditarMoto"
        submitText="Guardar Cambios"
        submitIcon="fas fa-save me-1"
    >
        <form id="formularioEditarMoto">
            <input type="hidden" id="edit_moto_id" name="moto_id">

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-motorcycle text-primary me-2"></i> Información General del Modelo</h6>
                <div class="row g-3">
                    <x-input id="edit_marca" name="marca" label="Marca" icon="fas fa-tag text-secondary" placeholder="Ej. Bera, Empire, Yamaha..." required col="col-md-3" />
                    <x-input id="edit_modelo" name="modelo" label="Modelo" icon="fas fa-file-signature text-secondary" placeholder="Ej. SBR, BR 150, Arsen..." required col="col-md-3" />
                    <x-input id="edit_referencia" name="referencia" label="Referencia" icon="fas fa-hashtag text-secondary" placeholder="Ej. 1" col="col-md-2" class="font-monospace" />
                    <x-input type="number" id="edit_anio" name="anio" label="Año" icon="fas fa-calendar text-secondary" min="1990" max="2099" required col="col-md-2" class="font-monospace" />
                    <x-input id="edit_cilindrada" name="cilindrada" label="Cilindrada" icon="fas fa-tachometer-alt text-secondary" placeholder="Ej. 150cc" col="col-md-2" class="font-monospace" />
                </div>
            </div>

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-fingerprint text-success me-2"></i> Identificadores Legales & Seriales Únicos</h6>
                <div class="row g-3">
                    <x-input id="edit_numero_niv" name="numero_niv" label="N.I.V. (VIN - 17 Dígitos)" icon="fas fa-barcode text-primary" class="font-monospace text-uppercase fw-bold" maxlength="50" required col="col-md-4" />
                    <x-input id="edit_numero_chasis" name="numero_chasis" label="N° Chasis / Bastidor" icon="fas fa-shield-alt text-secondary" class="font-monospace text-uppercase" maxlength="50" required col="col-md-4" />
                    <x-input id="edit_numero_motor" name="numero_motor" label="N° de Motor" icon="fas fa-cogs text-secondary" class="font-monospace text-uppercase" maxlength="50" required col="col-md-4" />
                    <x-input id="edit_certificado_origen" name="certificado_origen" label="Certificado de Origen" icon="fas fa-certificate text-warning" class="font-monospace text-uppercase" maxlength="50" col="col-md-4" />
                    <x-input id="edit_color" name="color" label="Color" icon="fas fa-palette text-secondary" placeholder="Ej. Rojo, Azul, Negro..." maxlength="50" col="col-md-4" />
                    <x-input id="edit_placa" name="placa" label="Placa / Matrícula" icon="fas fa-id-card text-secondary" class="font-monospace text-uppercase" placeholder="Ej. AA1B23C" maxlength="20" col="col-md-4" />
                </div>
            </div>

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-map-marker-alt text-danger me-2"></i> Ubicación & Estado Operativo</h6>
                <div class="row g-3">
                    <x-select id="edit_almacen_id" name="almacen_id" label="Almacén de Ubicación" icon="fas fa-warehouse text-primary" required col="col-md-6">
                    </x-select>
                    <x-select id="edit_estado" name="estado" label="Estado del Vehículo" icon="fas fa-traffic-light text-warning" required col="col-md-6">
                        <option value="disponible">🟢 Disponible para Venta</option>
                        <option value="reservada">🟡 Reservada</option>
                        <option value="en_mantenimiento">🔧 En Mantenimiento / Taller</option>
                        <option value="vendida">🔵 Vendida</option>
                    </x-select>
                </div>
            </div>

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs" style="border-left: 5px solid #2563eb !important;">
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-coins text-warning me-2"></i> Estructura Completa de Costos y Precios de Venta</h6>
                
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <x-input type="number" step="any" min="0" id="edit_costo_base_usd" name="costo_base_usd" label="Costo Unitario Base ($)" icon="fas fa-tag text-success" addonText="$" placeholder="0.0000" col="col-12" class="font-monospace fw-bold text-end" />
                        <div class="mt-1 text-end">
                            <span class="badge rounded-pill px-2.5 py-0.5 font-monospace fw-semibold shadow-xs d-inline-block" id="edit_costo_base_bs_preview" style="background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.78rem;">
                                Base: Bs. 0.0000
                            </span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <x-input type="number" step="any" min="0" id="edit_flete_usd" name="flete_usd" label="Cuánto se Pagó por Flete ($)" icon="fas fa-truck-ramp-box text-warning" addonText="$" placeholder="0.0000" value="0.0000" col="col-12" class="font-monospace fw-bold text-end" />
                        <div class="mt-1 text-end">
                            <span class="badge rounded-pill px-2.5 py-0.5 font-monospace fw-semibold shadow-xs d-inline-block" id="edit_flete_bs_preview" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.78rem;">
                                Flete: Bs. 0.0000
                            </span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <x-select id="edit_iva_porcentaje" name="iva_porcentaje" label="IVA (%)" icon="fas fa-receipt text-secondary" col="col-12" class="font-monospace">
                            <option value="16">IVA 16%</option>
                            <option value="8">IVA 8%</option>
                            <option value="0">Exento (0%)</option>
                        </x-select>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label-executive"><i class="fas fa-coins text-dark me-1"></i> Costo Total Calculado (Base + IVA + Flete)</label>
                        <div class="p-2.5 rounded-3 bg-white border shadow-xs d-flex align-items-center justify-content-between" style="min-height: 46px;">
                            <span class="fw-bold font-monospace text-dark fs-6" id="edit_costo_total_usd">$ 0.0000</span>
                            <span class="badge rounded-pill bg-dark text-white font-monospace px-3 py-1.5" id="edit_costo_total_bs">Bs. 0.0000</span>
                        </div>
                        <input type="hidden" id="edit_precio_costo_usd" name="precio_costo_usd">
                        <input type="hidden" id="edit_precio_costo_bs" name="precio_costo_bs">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-executive"><i class="fas fa-chart-line text-primary me-1"></i> Margen Detal (%) & Precio Venta (Con IVA / PVP)</label>
                        <div class="input-group mb-2">
                            <input type="number" step="any" min="0" id="edit_margen_detal" name="margen_detal" class="form-control form-control-executive font-monospace text-center" style="max-width: 95px;" value="25">
                            <span class="input-group-text bg-white font-monospace">%</span>
                            <input type="number" step="any" min="0" id="edit_precio_detal_con_iva_usd" name="precio_detal_con_iva_usd" class="form-control form-control-executive font-monospace fw-bold text-end text-primary" placeholder="PVP Con IVA ($)">
                        </div>
                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-primary fw-bold"><i class="fas fa-tag me-1"></i>PVP (Con IVA):</small>
                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="edit_detal_con_iva_badge" style="background-color: #eff6ff; color: #1d4ed8; font-size: 0.84rem;">
                                    $ 0.00 | Bs. 0.00
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-semibold"><i class="fas fa-info-circle text-primary me-1"></i>Precio Sin IVA:</small>
                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="edit_detal_sin_iva_badge" style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                    $ 0.00 | Bs. 0.00
                                </span>
                            </div>
                        </div>
                        <input type="hidden" id="edit_precio_detal_usd" name="precio_detal_usd">
                        <input type="hidden" id="edit_precio_detal_bs" name="precio_detal_bs">
                        <input type="hidden" id="edit_precio_detal_con_iva_bs" name="precio_detal_con_iva_bs">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label-executive" style="color: #7e22ce;"><i class="fas fa-truck-moving me-1"></i> Margen Mayor (%) & Precio Mayor (Con IVA / PVP)</label>
                        <div class="input-group mb-2">
                            <input type="number" step="any" min="0" id="edit_margen_mayorista" name="margen_mayorista" class="form-control form-control-executive font-monospace text-center" style="max-width: 95px;" value="15">
                            <span class="input-group-text bg-white font-monospace">%</span>
                            <input type="number" step="any" min="0" id="edit_precio_mayorista_con_iva_usd" name="precio_mayorista_con_iva_usd" class="form-control form-control-executive font-monospace fw-bold text-end" style="color: #7e22ce;" placeholder="Mayor Con IVA ($)">
                        </div>
                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="fw-bold" style="color: #7e22ce;"><i class="fas fa-tag me-1"></i>Mayor (Con IVA):</small>
                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="edit_mayorista_con_iva_badge" style="background-color: #faf5ff; color: #6b21a8; font-size: 0.84rem;">
                                    $ 0.00 | Bs. 0.00
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-semibold" style="color: #7e22ce !important;"><i class="fas fa-info-circle me-1"></i>Mayor Sin IVA:</small>
                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="edit_mayorista_sin_iva_badge" style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                    $ 0.00 | Bs. 0.00
                                </span>
                            </div>
                        </div>
                        <input type="hidden" id="edit_precio_mayorista_usd" name="precio_mayorista_usd">
                        <input type="hidden" id="edit_precio_mayorista_bs" name="precio_mayorista_bs">
                        <input type="hidden" id="edit_precio_mayorista_con_iva_bs" name="precio_mayorista_con_iva_bs">
                    </div>
                </div>
            </div>

            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-comment-dots text-secondary me-2"></i> Observaciones Adicionales</h6>
                <textarea id="edit_observaciones" name="observaciones" class="form-control form-control-executive" rows="2" placeholder="Notas sobre el estado de la moto, detalles estéticos, condición física, etc."></textarea>
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/moto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/moto.js')) ?: time() }}"></script>
@endsection
