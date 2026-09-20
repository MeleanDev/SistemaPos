@extends('Sistema.layouts.app')

@section('titulo', '📦 Productos')
@section('subtitulo', 'Catálogo general de inventario físico, precios multimoneda y distribución de stock')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Productos</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nuevo Producto"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <!-- TABLA PRINCIPAL DE PRODUCTOS -->
    <x-datatable
        id="datatable_productos"
        :headers="[
            'Producto',
            'Categoría',
            'Códigos de Barra',
            'Stock Total',
            'Precios (USD / Bs.)',
            'Impuestos',
            'Acciones',
        ]"
    />

    <!-- MODAL DE CREACIÓN / EDICIÓN -->
    <x-modal
        id="modalProducto"
        title="Nuevo Producto"
        subtitle="Completa la información del producto físico"
        icon="fas fa-boxes-stacked text-warning fs-5"
        size="modal-xl"
        headerColor="bg-dark text-white"
        formId="formularioProducto"
        submitText="Guardar"
    >
        <form id="formularioProducto">
            @csrf
            <input type="hidden" name="tipo" id="tipo" value="producto">
            
            <!-- NAVEGACIÓN POR PESTAÑAS DEL FORMULARIO -->
            <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded-pill border" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-semibold py-2" id="tab-basicos-btn" data-bs-toggle="pill" data-bs-target="#tab-basicos" type="button" role="tab">
                        <i class="fas fa-info-circle me-1"></i> Información General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold py-2" id="tab-codigos-btn" data-bs-toggle="pill" data-bs-target="#tab-codigos" type="button" role="tab">
                        <i class="fas fa-barcode me-1"></i> Códigos de Barra
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold py-2" id="tab-proveedores-btn" data-bs-toggle="pill" data-bs-target="#tab-proveedores" type="button" role="tab">
                        <i class="fas fa-truck me-1"></i> Proveedores
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                
                <!-- PESTAÑA 1: INFORMACIÓN GENERAL & FISCAL -->
                <div class="tab-pane fade show active" id="tab-basicos" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-executive"><i class="fas fa-tags"></i> Categoría <span class="text-danger">*</span></label>
                            <select name="categoria_id" id="categoria_id" class="form-select form-select-executive" required>
                                <option value="">Seleccione una categoría...</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <x-input name="codigo_interno" id="codigo_interno" label="Código SKU / Interno" icon="fas fa-hashtag"
                                placeholder="Ej. PROD-001 / REP-45" required maxlength="50" />
                        </div>

                        <div class="col-md-8">
                            <x-input name="nombre" id="nombre" label="Nombre del Producto" icon="fas fa-box"
                                placeholder="Ej. Aceite 20W50 Mineral 1L, Filtro de Aire..." required maxlength="150" />
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-executive"><i class="fas fa-balance-scale"></i> Unidad de Medida <span class="text-danger">*</span></label>
                            <select name="unidad_medida" id="unidad_medida" class="form-select form-select-executive" required>
                                <option value="unidad">Unidades (und)</option>
                                <option value="kilo">Kilogramos (kg)</option>
                                <option value="gramo">Gramos (g)</option>
                                <option value="litro">Litros (L)</option>
                                <option value="bulto">Bultos (blt)</option>
                                <option value="caja">Cajas (cja)</option>
                                <option value="paquete">Paquetes (paq)</option>
                                <option value="metro">Metros (m)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <x-input name="descripcion" id="descripcion" label="Descripción / Aplicación" icon="fas fa-align-left"
                                placeholder="Detalles técnicos, compatibilidad, modelos..." maxlength="1000" optionalText="Opcional" />
                        </div>

                        <!-- REGIMEN FISCAL: IVA E IGTF -->
                        <div class="col-12 mt-2">
                            <div class="card border rounded-4 p-3 bg-light-subtle">
                                <h6 class="fw-bold text-dark mb-3"><i class="fas fa-file-invoice-dollar text-warning me-2"></i> Régimen Fiscal e Impuestos</h6>
                                <div class="row g-3 align-items-center">
                                    <!-- IVA -->
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between border rounded-3 p-3 bg-white">
                                            <div>
                                                <div class="form-check form-switch mb-1">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="aplica_iva" name="aplica_iva" value="1" checked onchange="toggleIvaInput()">
                                                    <label class="form-check-label fw-bold text-dark" for="aplica_iva">Aplica IVA</label>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.74rem;">Si se desmarca, el producto es Exento</small>
                                            </div>
                                            <div style="width: 100px;">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control text-end fw-bold" id="iva_porcentaje" name="iva_porcentaje" value="16.00">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- IGTF -->
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center justify-content-between border rounded-3 p-3 bg-white">
                                            <div>
                                                <div class="form-check form-switch mb-1">
                                                    <input class="form-check-input" type="checkbox" role="switch" id="aplica_igtf" name="aplica_igtf" value="1" checked onchange="toggleIgtfInput()">
                                                    <label class="form-check-label fw-bold text-dark" for="aplica_igtf">Aplica IGTF</label>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.74rem;">Impuesto a pagos en divisas</small>
                                            </div>
                                            <div style="width: 100px;">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="0.01" min="0" max="100" class="form-control text-end fw-bold" id="igtf_porcentaje" name="igtf_porcentaje" value="3.00">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 3: CÓDIGOS DE BARRA OPCIONALES -->
                <div class="tab-pane fade" id="tab-codigos" role="tabpanel">
                    <div class="alert alert-light border rounded-4 d-flex align-items-center justify-content-between p-3 mb-3">
                        <div>
                            <h6 class="mb-0 fw-bold"><i class="fas fa-barcode text-primary me-2"></i> Códigos de Barra Escaneables (Opcionales)</h6>
                            <small class="text-muted">Si tu producto no tiene código de barra (ej. taller, granel), puedes dejar esta sección vacía.</small>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="agregarFilaCodigoBarra()">
                            <i class="fas fa-plus me-1"></i> Agregar Código
                        </button>
                    </div>

                    <div class="table-responsive border rounded-4">
                        <table class="table table-hover align-middle mb-0" id="tablaCodigosBarra">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Código de Barra</th>
                                    <th style="width: 45%;">Descripción / Presentación</th>
                                    <th style="width: 10%;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasCodigos">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PESTAÑA 4: PROVEEDORES ASOCIADOS -->
                <div class="tab-pane fade" id="tab-proveedores" role="tabpanel">
                    <div class="alert alert-light border rounded-4 d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-3">
                        <div>
                            <h6 class="mb-0 fw-bold"><i class="fas fa-truck-moving text-primary me-2"></i> Proveedores Sugeridos (Opcional)</h6>
                            <small class="text-muted">Vincula los proveedores que surten este producto con sus códigos y últimos costos.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3" onclick="abrirModalRapidoProveedor()">
                                <i class="fas fa-plus-circle me-1"></i> Crear Proveedor
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="agregarFilaProveedor()">
                                <i class="fas fa-plus me-1"></i> Vincular Proveedor
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive border rounded-4">
                        <table class="table table-hover align-middle mb-0" id="tablaProveedores">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45%;">Proveedor</th>
                                    <th style="width: 45%;">Código del Proveedor</th>
                                    <th style="width: 10%;" class="text-center">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasProveedores">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </form>
    </x-modal>

    <!-- MODAL RÁPIDO DE CREACIÓN DE PROVEEDOR -->
    <x-modal
        id="modalRapidoProveedor"
        title="Nuevo Proveedor"
        subtitle="Registra el proveedor sin salir ni perder el progreso del producto"
        icon="fas fa-truck text-success fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioRapidoProveedor"
        submitText="Guardar Proveedor"
    >
        <form id="formularioRapidoProveedor">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-input name="rif" id="rapido_prov_rif" label="RIF / Documento" icon="fas fa-id-card"
                        placeholder="Ej. J-12345678-9" required maxlength="20" />
                </div>
                <div class="col-md-6">
                    <x-input name="nombre" id="rapido_prov_nombre" label="Nombre Comercial" icon="fas fa-store"
                        placeholder="Ej. Distribuidora Central C.A." required maxlength="150" />
                </div>
                <div class="col-12">
                    <x-input name="razon_social" id="rapido_prov_razon_social" label="Razón Social / Nombre Legal" icon="fas fa-building"
                        placeholder="Ej. Distribuidora Central Compañía Anónima" required maxlength="150" />
                </div>
                <div class="col-md-6">
                    <x-input name="nombre_contacto" id="rapido_prov_contacto" label="Persona de Contacto" icon="fas fa-user-tie"
                        placeholder="Ej. Juan Pérez" maxlength="100" optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input name="telefono" id="rapido_prov_telefono" label="Teléfono de Contacto" icon="fas fa-phone"
                        placeholder="Ej. 0414-1234567" maxlength="25" optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input type="email" name="correo" id="rapido_prov_correo" label="Correo Electrónico" icon="fas fa-envelope"
                        placeholder="ejemplo@proveedor.com" maxlength="150" optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input name="direccion" id="rapido_prov_direccion" label="Dirección Fiscal / Física" icon="fas fa-map-marker-alt"
                        placeholder="Ciudad, Sector, Calle..." maxlength="255" optionalText="Opcional" />
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL FICHA TÉCNICA 360° (VER DETALLES) -->
    <x-modal
        id="modalFichaProducto"
        title="Ficha Técnica del Producto"
        subtitle="Consulta de stock por almacén, códigos de barra y detalle fiscal"
        icon="fas fa-eye text-info fs-5"
        size="modal-xl"
        headerColor="bg-dark text-white"
        :submitButton="false"
    >
        <div id="contenidoFichaProducto">
            <!-- Cargado dinámicamente vía AJAX -->
        </div>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/producto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/producto.js')) ?: time() }}">
    </script>
@endsection
