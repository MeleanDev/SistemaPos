@extends('Sistema.layouts.app')

@section('titulo', '🏍️ Motos & Seriales Únicos')
@section('subtitulo', 'Inventario individual de vehículos, trazabilidad legal de seriales (N.I.V., Chasis, Motor) y ficha técnica 360°')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Motos & Seriales</span>
@endsection

@section('acciones')
    <x-button href="{{ route('recepcion_moto') }}" variant="primary" icon="fas fa-truck-ramp-box" text="Nueva Recepción de Motos" />
@endsection

@section('contenido')
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

    <x-modal
        id="modalFichaMoto"
        title="Ficha Técnica 360° del Vehículo"
        subtitle="NIV: --"
        icon="fas fa-motorcycle text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        :submitButton="false"
    >
        <div id="contenidoFichaMoto">
        </div>

        <x-slot:footer>
            <button type="button" class="btn btn-executive-cancel" data-bs-dismiss="modal">
                <i class="fas fa-times me-1"></i> Cerrar
            </button>
            <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" id="btnEditarDesdeFicha">
                <i class="fas fa-edit me-1"></i> Editar Información
            </button>
        </x-slot:footer>
    </x-modal>

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
                    <x-input id="edit_referencia" name="referencia" label="Referencia" icon="fas fa-hashtag text-secondary" placeholder="Ej. 1001" col="col-md-2" class="font-monospace" />
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
                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-coins text-warning me-2"></i> Ubicación, Estado & Estructura de Precios</h6>
                <div class="row g-3">
                    <x-select id="edit_almacen_id" name="almacen_id" label="Almacén de Ubicación" icon="fas fa-warehouse text-primary" required col="col-md-3">
                    </x-select>
                    <x-select id="edit_estado" name="estado" label="Estado del Vehículo" icon="fas fa-traffic-light text-warning" required col="col-md-3">
                        <option value="disponible">🟢 Disponible para Venta</option>
                        <option value="reservada">🟡 Reservada</option>
                        <option value="en_mantenimiento">🔧 En Mantenimiento / Taller</option>
                        <option value="vendida">🔵 Vendida</option>
                    </x-select>
                    <div class="col-md-2">
                        <x-input type="number" step="any" min="0" id="edit_precio_costo_usd" name="precio_costo_usd" label="Costo ($ USD)" icon="fas fa-money-bill text-secondary" col="col-12" class="font-monospace fw-bold" />
                        <small class="text-muted font-monospace d-block text-end mt-1" id="edit_costo_bs_preview">≈ Bs. 0,00</small>
                    </div>
                    <div class="col-md-2">
                        <x-input type="number" step="any" min="0" id="edit_precio_detal_usd" name="precio_detal_usd" label="Precio Detal ($ USD)" icon="fas fa-store text-primary" required col="col-12" class="font-monospace fw-bold text-primary" />
                        <small class="text-muted font-monospace d-block text-end mt-1" id="edit_detal_bs_preview">≈ Bs. 0,00</small>
                    </div>
                    <div class="col-md-2">
                        <x-input type="number" step="any" min="0" id="edit_precio_mayorista_usd" name="precio_mayorista_usd" label="Mayorista ($ USD)" icon="fas fa-truck-moving" required col="col-12" class="font-monospace fw-bold" style="color: #7e22ce;" />
                        <small class="text-muted font-monospace d-block text-end mt-1" id="edit_mayorista_bs_preview">≈ Bs. 0,00</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label-executive"><i class="fas fa-comment-dots text-secondary"></i> Observaciones</label>
                        <textarea id="edit_observaciones" name="observaciones" class="form-control form-control-executive" rows="2" placeholder="Notas sobre el estado de la moto, detalles estéticos, condición física, etc."></textarea>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/moto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/moto.js')) ?: time() }}"></script>
@endsection
