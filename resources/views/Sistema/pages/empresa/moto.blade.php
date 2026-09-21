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

    <!-- MODAL DE EDICIÓN DE PRECIOS Y ESTADO DE LA MOTO -->
    <div class="modal fade" id="modalEditarMoto" tabindex="-1" aria-labelledby="modalEditarMotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalEditarMotoLabel">Modificar Vehículo</h5>
                            <small class="text-white-50" id="subtituloEditarMoto">Actualización de precios, almacén y estado</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <form id="formularioEditarMoto">
                        @csrf
                        <input type="hidden" id="edit_moto_id" name="moto_id">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label-executive"><i class="fas fa-warehouse text-primary"></i> Almacén Actual</label>
                                <select id="edit_almacen_id" name="almacen_id" class="form-select form-select-executive" required>
                                    <!-- Opciones cargadas dinámicamente -->
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive"><i class="fas fa-traffic-light text-warning"></i> Estado del Vehículo</label>
                                <select id="edit_estado" name="estado" class="form-select form-select-executive" required>
                                    <option value="disponible">🟢 Disponible para Venta</option>
                                    <option value="reservada">🟡 Reservada</option>
                                    <option value="en_mantenimiento">🔧 En Mantenimiento / Taller</option>
                                    <option value="vendida">🔵 Vendida</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive"><i class="fas fa-palette text-secondary"></i> Color</label>
                                <input type="text" id="edit_color" name="color" class="form-control form-control-executive" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive"><i class="fas fa-id-card text-secondary"></i> Placa / Registro</label>
                                <input type="text" id="edit_placa" name="placa" class="form-control form-control-executive font-monospace text-uppercase" placeholder="Ej. AA1B23C" maxlength="50">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive"><i class="fas fa-store text-primary"></i> Precio Detal ($ USD)</label>
                                <input type="number" step="any" min="0" id="edit_precio_detal_usd" name="precio_detal_usd" class="form-control form-control-executive font-monospace fw-bold" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive" style="color: #7e22ce;"><i class="fas fa-truck-moving"></i> Precio Mayorista ($ USD)</label>
                                <input type="number" step="any" min="0" id="edit_precio_mayorista_usd" name="precio_mayorista_usd" class="form-control form-control-executive font-monospace fw-bold" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label-executive"><i class="fas fa-comment-dots text-secondary"></i> Observaciones</label>
                                <textarea id="edit_observaciones" name="observaciones" class="form-control form-control-executive" rows="2" placeholder="Notas sobre el estado de la moto, detalles estéticos, etc."></textarea>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" onclick="guardarEdicionMoto()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/moto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/moto.js')) ?: time() }}"></script>
@endsection
