@extends('Sistema.layouts.app')

@section('titulo', '🏍️ Motos & Seriales Únicos')
@section('subtitulo', 'Inventario individual de vehículos, trazabilidad legal de seriales (N.I.V., Chasis, Motor) y ficha técnica 360°')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Motos & Seriales</span>
@endsection

@section('acciones')
    <a href="{{ route('recepcion_moto') }}" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
        <i class="fas fa-truck-ramp-box"></i>
        <span>Nueva Recepción de Motos</span>
    </a>
@endsection

@section('contenido')
    <!-- TABLA PRINCIPAL DE MOTOS EN STOCK -->
    <x-datatable
        id="datatable_motos"
        :headers="[
            'Modelo / Marca',
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

    <!-- MODAL FICHA TÉCNICA 360° DE LA MOTO -->
    <div class="modal fade" id="modalFichaMoto" tabindex="-1" aria-labelledby="modalFichaMotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                            <i class="fas fa-motorcycle"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalFichaMotoLabel">Ficha Técnica 360° del Vehículo</h5>
                            <small class="text-white-50 font-monospace" id="fichaMotoNivHeader">NIV: --</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light-subtle" id="contenidoFichaMoto">
                    <!-- Contenido cargado dinámicamente -->
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnEditarDesdeFicha">
                        <i class="fas fa-edit me-1"></i> Editar Información
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL DE EDICIÓN COMPLETA DE LA MOTO Y SERIALES -->
    <div class="modal fade" id="modalEditarMoto" tabindex="-1" aria-labelledby="modalEditarMotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalEditarMotoLabel">Modificar Datos del Vehículo</h5>
                            <small class="text-white-50" id="subtituloEditarMoto">Edición integral de modelo, seriales legales, precios y ubicación</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light-subtle">
                    <form id="formularioEditarMoto">
                        @csrf
                        <input type="hidden" id="edit_moto_id" name="moto_id">

                        <!-- 1. DATOS BÁSICOS DEL VEHÍCULO -->
                        <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-motorcycle text-primary me-2"></i> Información General del Modelo</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label-executive"><i class="fas fa-tag text-secondary"></i> Marca <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_marca" name="marca" class="form-control form-control-executive" placeholder="Ej. Bera, Empire, Yamaha..." required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-executive"><i class="fas fa-file-signature text-secondary"></i> Modelo <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_modelo" name="modelo" class="form-control form-control-executive" placeholder="Ej. SBR, BR 150, Arsen..." required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive"><i class="fas fa-hashtag text-secondary"></i> Referencia</label>
                                    <input type="text" id="edit_referencia" name="referencia" class="form-control form-control-executive font-monospace" placeholder="Ej. 1001">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive"><i class="fas fa-calendar text-secondary"></i> Año <span class="text-danger">*</span></label>
                                    <input type="number" id="edit_anio" name="anio" class="form-control form-control-executive font-monospace" min="1990" max="2099" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive"><i class="fas fa-tachometer-alt text-secondary"></i> Cilindrada</label>
                                    <input type="text" id="edit_cilindrada" name="cilindrada" class="form-control form-control-executive font-monospace" placeholder="Ej. 150cc">
                                </div>
                            </div>
                        </div>

                        <!-- 2. IDENTIFICADORES ÚNICOS Y TRAZABILIDAD LEGAL -->
                        <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-fingerprint text-success me-2"></i> Identificadores Legales & Seriales Únicos</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-barcode text-primary"></i> N.I.V. (VIN - 17 Dígitos) <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_numero_niv" name="numero_niv" class="form-control form-control-executive font-monospace text-uppercase fw-bold" maxlength="50" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-shield-alt text-secondary"></i> N° Chasis / Bastidor <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_numero_chasis" name="numero_chasis" class="form-control form-control-executive font-monospace text-uppercase" maxlength="50" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-cogs text-secondary"></i> N° de Motor <span class="text-danger">*</span></label>
                                    <input type="text" id="edit_numero_motor" name="numero_motor" class="form-control form-control-executive font-monospace text-uppercase" maxlength="50" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-certificate text-warning"></i> Certificado de Origen</label>
                                    <input type="text" id="edit_certificado_origen" name="certificado_origen" class="form-control form-control-executive font-monospace text-uppercase" maxlength="50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-palette text-secondary"></i> Color</label>
                                    <input type="text" id="edit_color" name="color" class="form-control form-control-executive" placeholder="Ej. Rojo, Azul, Negro..." maxlength="50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-executive"><i class="fas fa-id-card text-secondary"></i> Placa / Matrícula</label>
                                    <input type="text" id="edit_placa" name="placa" class="form-control form-control-executive font-monospace text-uppercase" placeholder="Ej. AA1B23C" maxlength="20">
                                </div>
                            </div>
                        </div>

                        <!-- 3. PRECIOS, COSTO, ALMACÉN Y ESTADO -->
                        <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-coins text-warning me-2"></i> Ubicación, Estado & Estructura de Precios</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label-executive"><i class="fas fa-warehouse text-primary"></i> Almacén de Ubicación <span class="text-danger">*</span></label>
                                    <select id="edit_almacen_id" name="almacen_id" class="form-select form-select-executive" required>
                                        <!-- Opciones cargadas dinámicamente -->
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label-executive"><i class="fas fa-traffic-light text-warning"></i> Estado del Vehículo <span class="text-danger">*</span></label>
                                    <select id="edit_estado" name="estado" class="form-select form-select-executive" required>
                                        <option value="disponible">🟢 Disponible para Venta</option>
                                        <option value="reservada">🟡 Reservada</option>
                                        <option value="en_mantenimiento">🔧 En Mantenimiento / Taller</option>
                                        <option value="vendida">🔵 Vendida</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive"><i class="fas fa-money-bill text-secondary"></i> Costo ($ USD)</label>
                                    <input type="number" step="any" min="0" id="edit_precio_costo_usd" name="precio_costo_usd" class="form-control form-control-executive font-monospace fw-bold">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive"><i class="fas fa-store text-primary"></i> Precio Detal ($ USD) <span class="text-danger">*</span></label>
                                    <input type="number" step="any" min="0" id="edit_precio_detal_usd" name="precio_detal_usd" class="form-control form-control-executive font-monospace fw-bold text-primary" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label-executive" style="color: #7e22ce;"><i class="fas fa-truck-moving"></i> Mayorista ($ USD) <span class="text-danger">*</span></label>
                                    <input type="number" step="any" min="0" id="edit_precio_mayorista_usd" name="precio_mayorista_usd" class="form-control form-control-executive font-monospace fw-bold" style="color: #7e22ce;" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-executive"><i class="fas fa-comment-dots text-secondary"></i> Observaciones</label>
                                    <textarea id="edit_observaciones" name="observaciones" class="form-control form-control-executive" rows="2" placeholder="Notas sobre el estado de la moto, detalles estéticos, condición física, etc."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" onclick="guardarEdicionMoto()">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/moto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/moto.js')) ?: time() }}"></script>
@endsection
