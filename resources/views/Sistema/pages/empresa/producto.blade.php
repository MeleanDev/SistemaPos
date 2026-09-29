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
            <ul class="nav nav-pills nav-fill mb-4 p-1 bg-white rounded-pill border shadow-xs" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-semibold py-2" id="tab-basicos-btn" data-bs-toggle="pill" data-bs-target="#tab-basicos" type="button" role="tab">
                        <i class="fas fa-info-circle me-1"></i> Información General
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold py-2" id="tab-variantes-btn" data-bs-toggle="pill" data-bs-target="#tab-variantes" type="button" role="tab">
                        <i class="fas fa-layer-group me-1"></i> Variantes & Seriales
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-semibold py-2" id="tab-precios-btn" data-bs-toggle="pill" data-bs-target="#tab-precios" type="button" role="tab">
                        <i class="fas fa-coins me-1"></i> Precios & Costos
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
                
                <!-- PESTAÑA 1: INFORMACIÓN GENERAL -->
                <div class="tab-pane fade show active" id="tab-basicos" role="tabpanel">
                    <div class="row g-3">
                        <x-select2
                            name="categoria_id"
                            id="categoria_id"
                            label="Categoría"
                            icon="fas fa-tags text-primary"
                            placeholder="Seleccione una categoría..."
                            modalParent="#modalProducto"
                            required
                            col="col-md-6"
                        >
                            <option value="">Seleccione una categoría...</option>
                        </x-select2>

                        <x-input
                            name="codigo_interno"
                            id="codigo_interno"
                            label="Código Interno / SKU"
                            icon="fas fa-hashtag text-primary"
                            addonIcon="fas fa-lock text-muted"
                            placeholder="[Generado automáticamente]"
                            col="col-md-6"
                            readonly
                            class="font-monospace fw-bold"
                            optionalText="Autoincrementable"
                            helpText="Código correlativo único generado por el sistema."
                        />

                        <div class="col-md-8">
                            <x-input name="nombre" id="nombre" label="Nombre del Producto" icon="fas fa-box"
                                placeholder="Ej. Aceite 20W50 Mineral 1L, Filtro de Aire..." required maxlength="150" />
                        </div>

                        <x-select 
                            name="unidad_medida" 
                            id="unidad_medida" 
                            label="Unidad de Medida" 
                            icon="fas fa-balance-scale text-primary" 
                            required 
                            col="col-md-4"
                        >
                            <option value="unidad">Unidades (und)</option>
                            <option value="kilo">Kilogramos (kg)</option>
                            <option value="gramo">Gramos (g)</option>
                            <option value="litro">Litros (L)</option>
                            <option value="bulto">Bultos (blt)</option>
                            <option value="caja">Cajas (cja)</option>
                            <option value="paquete">Paquetes (paq)</option>
                            <option value="metro">Metros (m)</option>
                        </x-select>

                        <div class="col-12">
                            <x-input name="descripcion" id="descripcion" label="Descripción / Aplicación" icon="fas fa-align-left"
                                placeholder="Detalles técnicos, compatibilidad, modelos..." maxlength="1000" optionalText="Opcional" />
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA: VARIANTES & SERIALES (MUEBLES, CAMAS, ELECTRODOMÉSTICOS, AIRES, NEVERAS) -->
                <div class="tab-pane fade" id="tab-variantes" role="tabpanel">
                    <div class="row g-3">
                        <!-- CONTROL DE VARIANTES / CHECKLIST DE OPCIONES -->
                        <div class="col-12">
                            <div class="card border rounded-4 p-3.5 bg-white shadow-xs" style="border-left: 5px solid #6366f1 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-executive-xs rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fas fa-palette"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Checklist de Variantes / Opciones (Mueblería, Camas, Colores)</h6>
                                            <small class="text-muted">Define los colores u opciones disponibles sin registrar múltiples productos duplicados en el catálogo.</small>
                                        </div>
                                    </div>
                                    <x-checkbox
                                        switch="true"
                                        id="maneja_variantes"
                                        name="maneja_variantes"
                                        value="1"
                                        onchange="toggleVariantesProducto()"
                                        label="Habilitar Variantes"
                                        labelClass="fw-bold text-dark small"
                                        style="cursor: pointer; width: 2.5em; height: 1.3em;"
                                    />
                                </div>

                                <div id="seccionConfigVariantes" class="mt-3 pt-3 border-top" style="display: none;">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-md-4">
                                            <label class="form-label-executive"><i class="fas fa-tag text-primary me-1"></i> Tipo de Atributo / Nombre</label>
                                            <input type="text" id="nombre_atributo_variante" name="atributos_variantes[nombre]" class="form-control form-control-executive font-monospace" placeholder="Ej. Color, Medida, Material" value="Color">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label-executive"><i class="fas fa-plus-circle text-success me-1"></i> Escriba una Opción / Color y presione Enter o Agregar</label>
                                            <div class="input-group">
                                                <input type="text" id="input_nueva_opcion_variante" class="form-control form-control-executive font-monospace" placeholder="Ej. Gris, Negro, Beige, Azul...">
                                                <button type="button" class="btn btn-outline-primary rounded-end-pill px-3 fw-bold" onclick="agregarOpcionVariante()">
                                                    <i class="fas fa-plus me-1"></i> Agregar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5" onclick="limpiarOpcionesVariantes()" title="Limpiar todas las opciones">
                                                <i class="fas fa-trash-alt me-1"></i> Limpiar
                                            </button>
                                        </div>
                                    </div>

                                    <!-- CONTENEDOR DE CHIPS / BADGES DE OPCIONES REGISTRADAS -->
                                    <div class="mt-3 p-3 rounded-3 bg-white border shadow-xs">
                                        <small class="text-muted text-uppercase fw-bold d-block mb-2" style="font-size: 0.70rem; letter-spacing: 0.05em;">
                                            <i class="fas fa-check-double text-primary me-1"></i> Opciones / Colores Disponibles en este Producto:
                                        </small>
                                        <div id="contenedorChipsVariantes" class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="text-muted fst-italic small" id="placeholderSinVariantes">No hay opciones añadidas. Escriba un color arriba para agregarlo.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CONTROL DE SERIALES ÚNICOS (ELECTRODOMÉSTICOS, AIRES, NEVERAS, FREEZERS) -->
                        <div class="col-12">
                            <div class="card border rounded-4 p-3.5 bg-white shadow-xs" style="border-left: 5px solid #10b981 !important;">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-executive-xs rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                            <i class="fas fa-barcode"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-0">Control Estricto por Serial Único (Neveras, Aires, Freezers, Equipos)</h6>
                                            <small class="text-muted">Al activar, cada unidad física que ingrese por compras tendrá su propio número de serial único para seguimiento de garantías y ventas.</small>
                                        </div>
                                    </div>
                                    <x-checkbox
                                        switch="true"
                                        id="maneja_seriales"
                                        name="maneja_seriales"
                                        value="1"
                                        label="Habilitar Seriales"
                                        labelClass="fw-bold text-dark small"
                                        style="cursor: pointer; width: 2.5em; height: 1.3em;"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 2: PRECIOS, COSTOS & MÁRGENES (DATOS DE RECEPCIÓN / VENTA) -->
                <div class="tab-pane fade" id="tab-precios" role="tabpanel">
                    <div class="row g-3">

                        <!-- TASAS DE CAMBIO (COMPRA & VENTA) -->
                        <div class="col-12">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-calculator text-primary me-2"></i> Tasas de Conversión para Fijación de Precios</h6>
                                    <x-button variant="outline-primary" size="sm" class="font-monospace" onclick="restablecerTasasModal()" title="Restablecer a tasa oficial de la empresa" icon="fas fa-sync-alt">
                                        Tasa Oficial: <span id="badgeTasaOficialEmpresa">1.0000</span> Bs./$
                                    </x-button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label-executive">
                                            <i class="fas fa-shopping-cart text-warning me-1"></i> Tasa de Compra (Bs. / $)
                                            <small class="text-muted fw-normal">(Tasa del proveedor)</small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-muted fw-bold font-monospace">Bs.</span>
                                            <input type="number" step="any" min="0.0001" name="tasa_compra" id="tasa_compra" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="1.0000" oninput="alCambiarTasasModal()">
                                            <x-button variant="outline-primary" rounded="0" class="rounded-end-pill px-3 shadow-xs" onclick="restablecerTasaCompraModal()" title="Restablecer a tasa oficial" icon="fas fa-sync-alt small" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label-executive">
                                            <i class="fas fa-cash-register text-success me-1"></i> Tasa de Venta (Bs. / $)
                                            <small class="text-muted fw-normal">(Tasa para fijación de PVP)</small>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-success fw-bold font-monospace">Bs.</span>
                                            <input type="number" step="any" min="0.0001" name="tasa_venta" id="tasa_venta" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="1.0000" oninput="alCambiarTasasModal()">
                                            <x-button variant="outline-primary" rounded="0" class="rounded-end-pill px-3 shadow-xs" onclick="restablecerTasaVentaModal()" title="Restablecer a tasa oficial" icon="fas fa-sync-alt small" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- COSTO DE COMPRA -->
                        <div class="col-12">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-tag text-success me-2"></i> Costo Base de Compra</h6>
                                    <small class="text-muted">Ingresa el costo en $ o en Bs. para calcular automáticamente</small>
                                </div>
                                <div class="row g-3">
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_costo_usd" 
                                        id="precio_costo_usd" 
                                        label="Costo Unitario ($ USD)" 
                                        addonText="$" 
                                        placeholder="0.00" 
                                        col="col-md-6" 
                                        class="font-monospace fw-bold text-end" 
                                        oninput="alCambiarCostoUsd()" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_costo_bs" 
                                        id="precio_costo_bs" 
                                        label="Costo Unitario (Bs. VES)" 
                                        addonText="Bs." 
                                        placeholder="0.00" 
                                        col="col-md-6" 
                                        class="font-monospace fw-bold text-end" 
                                        oninput="alCambiarCostoBs()" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ESTRUCTURA DE PRECIO DETAL -->
                        <div class="col-md-6">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-store me-1"></i> Precio Venta al Detal</h6>
                                <div class="row g-2">
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="ultimo_margen_detal" 
                                        id="ultimo_margen_detal" 
                                        label="Margen de Ganancia Detal (%)" 
                                        addonText="%" 
                                        addonPosition="right" 
                                        value="30.00" 
                                        col="col-12" 
                                        class="font-monospace fw-bold text-center" 
                                        oninput="calcularPrecioDetalDesdeMargenForm()" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_detal_usd" 
                                        id="precio_detal_usd" 
                                        label="PVP Detal ($ USD)" 
                                        addonText="$" 
                                        placeholder="0.00" 
                                        col="col-6" 
                                        class="font-monospace fw-bold text-end text-primary" 
                                        oninput="calcularMargenDetalDesdePrecioForm()" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_detal_bs" 
                                        id="precio_detal_bs" 
                                        label="PVP Detal (Bs.)" 
                                        addonText="Bs." 
                                        placeholder="0.00" 
                                        col="col-6" 
                                        class="font-monospace fw-bold text-end" 
                                        oninput="calcularMargenDetalDesdePrecioBsForm()" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- ESTRUCTURA DE PRECIO MAYORISTA -->
                        <div class="col-md-6">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                                <h6 class="fw-bold mb-3" style="color: #7e22ce;"><i class="fas fa-boxes-stacked me-1"></i> Precio Venta al Mayor</h6>
                                <div class="row g-2">
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="ultimo_margen_mayorista" 
                                        id="ultimo_margen_mayorista" 
                                        label="Margen de Ganancia Mayorista (%)" 
                                        addonText="%" 
                                        addonPosition="right" 
                                        value="15.00" 
                                        col="col-12" 
                                        class="font-monospace fw-bold text-center" 
                                        oninput="calcularPrecioMayorDesdeMargenForm()" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_mayorista_usd" 
                                        id="precio_mayorista_usd" 
                                        label="PVP Mayor ($ USD)" 
                                        addonText="$" 
                                        placeholder="0.00" 
                                        col="col-6" 
                                        class="font-monospace fw-bold text-end" 
                                        style="color: #7e22ce;" 
                                        oninput="calcularMargenMayorDesdePrecioForm()" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="precio_mayorista_bs" 
                                        id="precio_mayorista_bs" 
                                        label="PVP Mayor (Bs.)" 
                                        addonText="Bs." 
                                        placeholder="0.00" 
                                        col="col-6" 
                                        class="font-monospace fw-bold text-end" 
                                        oninput="calcularMargenMayorDesdePrecioBsForm()" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- CONTROL DE EXISTENCIAS MÍNIMAS Y MÁXIMAS -->
                        <div class="col-md-6">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs">
                                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-layer-group text-secondary me-1"></i> Control de Stock</h6>
                                <div class="row g-2">
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="stock_minimo" 
                                        id="stock_minimo" 
                                        label="Stock Mínimo" 
                                        value="0" 
                                        col="col-6" 
                                        class="font-monospace text-center" 
                                    />
                                    <x-input 
                                        type="number" 
                                        step="any" 
                                        min="0" 
                                        name="stock_maximo" 
                                        id="stock_maximo" 
                                        label="Stock Máximo" 
                                        optionalText="Opcional" 
                                        placeholder="Sin límite" 
                                        col="col-6" 
                                        class="font-monospace text-center" 
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- RÉGIMEN FISCAL (IVA E IGTF) -->
                        <div class="col-md-6">
                            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100 d-flex flex-column justify-content-between">
                                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-file-invoice-dollar text-warning me-1"></i> Régimen Fiscal</h6>
                                <div class="row g-2">
                                    <!-- IVA -->
                                    <div class="col-12 d-flex align-items-center justify-content-between gap-2">
                                        <x-checkbox
                                            switch="true"
                                            id="aplica_iva"
                                            name="aplica_iva"
                                            value="1"
                                            checked="true"
                                            onchange="toggleIvaInput()"
                                            label="Aplica IVA"
                                            labelClass="fw-bold text-dark small"
                                            style="cursor: pointer; width: 2.5em; height: 1.3em;"
                                        />
                                        <div style="width: 120px;">
                                            <div class="input-group input-group-executive">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-executive text-end fw-bold font-monospace" id="iva_porcentaje" name="iva_porcentaje" value="16.00">
                                                <span class="input-group-text bg-white text-muted small">%</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- IGTF -->
                                    <div class="col-12 d-flex align-items-center justify-content-between gap-2">
                                        <x-checkbox
                                            switch="true"
                                            id="aplica_igtf"
                                            name="aplica_igtf"
                                            value="1"
                                            checked="true"
                                            onchange="toggleIgtfInput()"
                                            label="Aplica IGTF"
                                            labelClass="fw-bold text-dark small"
                                            style="cursor: pointer; width: 2.5em; height: 1.3em;"
                                        />
                                        <div style="width: 120px;">
                                            <div class="input-group input-group-executive">
                                                <input type="number" step="0.01" min="0" max="100" class="form-control form-control-executive text-end fw-bold font-monospace" id="igtf_porcentaje" name="igtf_porcentaje" value="3.00">
                                                <span class="input-group-text bg-white text-muted small">%</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- PESTAÑA 3: CÓDIGOS DE BARRA / QR OPCIONALES -->
                <div class="tab-pane fade" id="tab-codigos" role="tabpanel">
                    <x-section-header
                        title="Códigos de Barra / QR Escaneables (Opcionales)"
                        description="Un producto puede tener múltiples códigos QR o de barra. Cada código debe ser único en tu empresa."
                        icon="fas fa-qrcode"
                    >
                        <x-button variant="outline-primary" size="sm" icon="fas fa-plus" text="Agregar Código / QR" onclick="agregarFilaCodigoBarra()" />
                    </x-section-header>

                    <x-table-dynamic
                        id="tablaCodigosBarra"
                        bodyId="contenedorFilasCodigos"
                        :headers="[
                            ['label' => 'Código de Barra / QR', 'width' => '45%'],
                            ['label' => 'Descripción / Presentación', 'width' => '45%'],
                            ['label' => 'Acción', 'width' => '10%', 'class' => 'text-center']
                        ]"
                    />
                </div>

                <!-- PESTAÑA 4: PROVEEDORES ASOCIADOS -->
                <div class="tab-pane fade" id="tab-proveedores" role="tabpanel">
                    <x-section-header
                        title="Proveedores Sugeridos (Opcional)"
                        description="Vincula los proveedores que surten este producto con sus códigos y últimos costos."
                        icon="fas fa-truck-moving"
                    >
                        <x-button variant="outline-success" size="sm" icon="fas fa-plus-circle" text="Crear Proveedor" onclick="abrirModalRapidoProveedor()" />
                        <x-button variant="outline-primary" size="sm" icon="fas fa-plus" text="Vincular Proveedor" onclick="agregarFilaProveedor()" />
                    </x-section-header>

                    <x-table-dynamic
                        id="tablaProveedores"
                        bodyId="contenedorFilasProveedores"
                        :headers="[
                            ['label' => 'Proveedor', 'width' => '45%'],
                            ['label' => 'Código de Referencia del Proveedor', 'width' => '45%'],
                            ['label' => 'Acción', 'width' => '10%', 'class' => 'text-center']
                        ]"
                    />
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
