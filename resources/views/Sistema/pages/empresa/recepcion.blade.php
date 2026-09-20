@extends('Sistema.layouts.app')

@section('titulo', '🚚 Recepción de Mercancía')
@section('subtitulo', 'Ingreso de compras físicas a almacenes, costos, actualización de precios y Kardex')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Recepción de Mercancía</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nueva Recepción"
        onclick="crear()"
    />
@endsection

@push('css')
<style>
    .card-moneda-seleccion {
        border: 2px solid #e2e8f0;
        background-color: #ffffff;
        cursor: pointer;
        transition: all 0.25s ease-in-out;
    }
    .card-moneda-seleccion:hover {
        border-color: #3b82f6;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.07), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
    }
    .card-moneda-seleccion.active-moneda {
        border-color: #2563eb !important;
        background-color: #f8faff !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
    }
    .card-moneda-seleccion.disabled-moneda {
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
    }
    .btn-paso-stepper {
        border: none;
        color: rgba(255, 255, 255, 0.6);
        background: transparent;
        transition: all 0.2s ease-in-out;
    }
    .btn-paso-stepper.active {
        background-color: #ffffff !important;
        color: #0f172a !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    .btn-paso-stepper:hover:not(.active) {
        color: #ffffff;
        background-color: rgba(255, 255, 255, 0.15);
    }
    .resultados-busqueda-dropdown {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 16px !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08) !important;
        z-index: 1080 !important;
    }
    .item-busqueda-producto {
        background-color: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
        transition: all 0.2s ease-in-out !important;
        cursor: pointer !important;
    }
    .item-busqueda-producto:last-child {
        border-bottom: none !important;
    }
    .item-busqueda-producto:hover {
        background-color: #f0f7ff !important;
        border-left: 4px solid #2563eb !important;
    }
</style>
@endpush

@section('contenido')
    <!-- TABLA PRINCIPAL DE RECEPCIONES -->
    <x-datatable
        id="datatable_recepciones"
        :headers="[
            'Recepción',
            'Doc. Proveedor',
            'N° Control',
            'Proveedor',
            'Almacén Destino',
            'Ítems / Unidades',
            'Total Compra',
            'Condición',
            'Estado',
            'Acciones',
        ]"
    />

    <!-- MODAL DE NUEVA RECEPCIÓN DE MERCANCÍA EN 2 FASES -->
    <div class="modal fade" id="modalRecepcion" tabindex="-1" aria-labelledby="modalRecepcionLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 98vw; width: 98vw;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                
                <!-- HEADER DEL MODAL CON STEPPER EJECUTIVO -->
                <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-truck-loading"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalRecepcionLabel">Nueva Recepción de Mercancía</h5>
                            <small class="text-white-50">Ingreso de compras, costos, distribución por almacén y actualización de precios</small>
                        </div>
                    </div>

                    <!-- STEPPER VISUAL DE FASES -->
                    <div class="d-flex align-items-center gap-2 bg-white bg-opacity-10 p-1 rounded-pill border border-white border-opacity-25 shadow-xs">
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-paso-stepper active" id="btnPaso1Stepper" onclick="volverAFase1()">
                            <i class="fas fa-file-invoice me-1"></i> 1. Datos Principales
                        </button>
                        <i class="fas fa-chevron-right text-white-50 small"></i>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-paso-stepper text-white-50" id="btnPaso2Stepper" onclick="avanzarAFase2()">
                            <i class="fas fa-boxes-stacked me-1"></i> 2. Productos & Liquidación
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-warning text-dark fw-bold px-3 py-2 font-monospace" style="font-size: 0.85rem;" id="badgeCodigoRecepcion">
                            <i class="fas fa-hashtag me-1"></i>REC-00000
                        </span>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- BODY DEL MODAL -->
                <div class="modal-body p-4 bg-light-subtle">
                    <form id="formularioRecepcion">
                        @csrf
                        <input type="hidden" name="tasa_cambio" id="tasa_cambio" value="1.0000">
                        <input type="hidden" name="moneda_documento" id="moneda_documento" value="USD">

                        <!-- ========================================== -->
                        <!-- FASE 1: DATOS PRINCIPALES DEL DOCUMENTO    -->
                        <!-- ========================================== -->
                        <div id="seccionFase1" class="fase-recepcion-container">
                            
                            <!-- SELECCIÓN OBLIGATORIA DE MONEDA AL INICIO -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-4 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><i class="fas fa-money-bill-wave text-warning me-2"></i> Moneda del Documento / Factura</h6>
                                        <small class="text-muted">Indica con cuál moneda viene emitida la factura del proveedor. Una vez cargados los productos, esta moneda quedará fijada para la recepción.</small>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1 fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                        <i class="fas fa-coins me-1"></i> Tasa del Sistema: <span id="badgeTasaCambio">1.0000</span> Bs.
                                    </span>
                                </div>

                                <div class="row g-3" id="contenedorSelectorMoneda">
                                    <div class="col-md-6">
                                        <div class="card card-moneda-seleccion h-100 p-3 rounded-4 border-2 cursor-pointer transition-all active-moneda" id="cardMonedaUsd" onclick="seleccionarMonedaDocumento('USD')">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-executive-sm rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-dark mb-0">Facturado en Dólares ($ USD)</h6>
                                                        <small class="text-muted">Los costos se ingresan en $ y el sistema calcula los valores en Bolívares.</small>
                                                    </div>
                                                </div>
                                                <div class="form-check m-0">
                                                    <input class="form-check-input" type="radio" name="moneda_factura_radio" id="radio_usd" value="USD" checked onchange="seleccionarMonedaDocumento('USD')">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card card-moneda-seleccion h-100 p-3 rounded-4 border-2 cursor-pointer transition-all" id="cardMonedaVes" onclick="seleccionarMonedaDocumento('VES')">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-dark mb-0">Facturado en Bolívares (Bs. VES)</h6>
                                                        <small class="text-muted">Los costos se ingresan en Bs. y el sistema calcula los valores en Dólares a la tasa oficial.</small>
                                                    </div>
                                                </div>
                                                <div class="form-check m-0">
                                                    <input class="form-check-input" type="radio" name="moneda_factura_radio" id="radio_ves" value="VES" onchange="seleccionarMonedaDocumento('VES')">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DATOS DEL DOCUMENTO, PROVEEDOR Y CONDICIONES -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white">
                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                    <i class="fas fa-file-invoice text-primary me-2"></i> Datos del Comprobante Fiscal & Proveedor
                                </h6>

                                <div class="row g-3">
                                    <!-- N° Factura / Documento Proveedor -->
                                    <div class="col-md-4">
                                        <label class="form-label-executive"><i class="fas fa-hashtag text-primary"></i> N° Factura / Documento <span class="text-danger">*</span></label>
                                        <input type="text" name="numero_documento" id="numero_documento" class="form-control form-control-executive" placeholder="Ej. FACT-004892" required maxlength="100">
                                    </div>

                                    <!-- N° Control de Factura (Opcional) -->
                                    <div class="col-md-4">
                                        <label class="form-label-executive"><i class="fas fa-barcode text-secondary"></i> N° de Control Fiscal <small class="text-muted fw-normal">(Opcional)</small></label>
                                        <input type="text" name="numero_control" id="numero_control" class="form-control form-control-executive" placeholder="Ej. 00-12345" maxlength="100">
                                    </div>

                                    <!-- Tipo de Documento -->
                                    <div class="col-md-4">
                                        <label class="form-label-executive"><i class="fas fa-file-alt text-primary"></i> Tipo de Documento <span class="text-danger">*</span></label>
                                        <select name="tipo_documento" id="tipo_documento" class="form-select form-select-executive" required>
                                            <option value="factura">Factura Fiscal</option>
                                            <option value="nota_entrega">Nota de Entrega</option>
                                            <option value="guia_despacho">Guía de Despacho</option>
                                            <option value="orden_compra">Orden de Compra</option>
                                        </select>
                                    </div>

                                    <!-- Proveedor con Botón Rápido -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-truck text-primary"></i> Proveedor Emisor <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <select name="proveedor_id" id="proveedor_id" class="form-select form-select-executive" required>
                                                <option value="">Seleccione un proveedor...</option>
                                            </select>
                                            <button class="btn btn-outline-primary rounded-end-pill px-3" type="button" onclick="abrirModalRapidoProveedor()" title="Crear Nuevo Proveedor">
                                                <i class="fas fa-plus me-1"></i> Nuevo
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Almacén Predeterminado -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-warehouse text-primary"></i> Almacén Predeterminado de Entrada <span class="text-danger">*</span></label>
                                        <select name="almacen_id" id="almacen_id" class="form-select form-select-executive" onchange="cambiarAlmacenPredeterminado()" required>
                                            <option value="">Seleccione almacén predeterminado...</option>
                                        </select>
                                    </div>

                                    <!-- Fechas -->
                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-calendar-alt text-secondary"></i> Fecha de Emisión <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_emision" id="fecha_emision" class="form-control form-control-executive" required>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-calendar-check text-secondary"></i> Fecha de Recepción <span class="text-danger">*</span></label>
                                        <input type="date" name="fecha_recepcion" id="fecha_recepcion" class="form-control form-control-executive" required>
                                    </div>

                                    <!-- Condición de Pago -->
                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-credit-card text-secondary"></i> Condición de Pago <span class="text-danger">*</span></label>
                                        <select name="condicion_pago" id="condicion_pago" class="form-select form-select-executive" onchange="toggleCondicionPago()" required>
                                            <option value="contado">Contado</option>
                                            <option value="credito">Crédito (CXP)</option>
                                        </select>
                                    </div>

                                    <!-- Días de Crédito (Condicional) -->
                                    <div class="col-md-3" id="contenedorDiasCredito" style="display: none;">
                                        <label class="form-label-executive"><i class="fas fa-clock text-warning"></i> Días de Crédito</label>
                                        <div class="input-group">
                                            <input type="number" name="dias_credito" id="dias_credito" class="form-control form-control-executive font-monospace" value="15" min="1" max="365" oninput="calcularFechaVencimiento()">
                                            <span class="input-group-text bg-light text-muted small font-monospace" id="labelFechaVencimiento">Vence: --</span>
                                        </div>
                                    </div>

                                    <!-- Monto Bruto Factura & Descuento Global -->
                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-receipt text-secondary"></i> Monto Bruto Fac. <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-primary fw-bold label-simbolo-moneda-fac">$</span>
                                            <input type="number" step="any" min="0" name="monto_bruto_input" id="monto_bruto_input" class="form-control form-control-executive font-monospace" placeholder="0.00" required>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-percent text-secondary"></i> Descuento Global Fac. <small class="text-muted fw-normal">(%)</small></label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" max="100" name="descuento_global_porcentaje" id="descuento_global_porcentaje" class="form-control form-control-executive font-monospace" value="0.00" oninput="recalcularTotalesGenerales()">
                                            <span class="input-group-text bg-white text-muted">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- FASE 2: PRODUCTOS, RESUMEN DE LIQUIDACIÓN Y OBSERVACIONES                -->
                        <!-- ========================================================================= -->
                        <div id="seccionFase2" class="fase-recepcion-container" style="display: none;">
                            
                            <!-- BARRA RESUMEN DE FASE 1 (COMPROBANTE & MONEDA) -->
                            <div class="card border rounded-4 p-3 shadow-xs mb-3 bg-white">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge rounded-pill px-3 py-2 fw-bold" id="badgeMonedaFase2" style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
                                            <i class="fas fa-dollar-sign me-1"></i> Factura en $ USD
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeDocFase2" style="background-color: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">
                                            <i class="fas fa-file-invoice text-primary me-1"></i> Doc: --
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeProvFase2" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            <i class="fas fa-truck text-primary me-1"></i> Prov: --
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeAlmFase2" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;">
                                            <i class="fas fa-warehouse text-secondary me-1"></i> Almacén: --
                                        </span>
                                    </div>

                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" onclick="volverAFase1()">
                                        <i class="fas fa-edit me-1"></i> Modificar Datos Principales
                                    </button>
                                </div>
                            </div>

                            <!-- CARD SUPERIOR: CARGA Y EDICIÓN RÁPIDA DEL PRODUCTO -->
                            <div class="card border rounded-4 p-3 shadow-xs mb-3 bg-white" id="cardCargaProducto">
                                
                                <!-- 1. BUSCADOR / ESCÁNER -->
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="fw-bold text-dark mb-0">
                                        <i class="fas fa-barcode text-primary me-2"></i> 1. Buscar o Escanear Artículo
                                    </h6>
                                    <span class="badge rounded-pill bg-light text-secondary border font-monospace px-3 py-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-keyboard me-1"></i> Pistola / Código de Barra / SKU / Nombre
                                    </span>
                                </div>

                                <div class="row g-2 align-items-center position-relative mb-3">
                                    <div class="col-12">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill ps-3"><i class="fas fa-search text-primary"></i></span>
                                            <input type="text" id="inputEscaneoProducto" class="form-control form-control-executive border-start-0 ps-2" placeholder="Escanear código de barra, SKU o escribir nombre del producto..." autocomplete="off">
                                            <button class="btn btn-outline-primary rounded-end-pill px-3 fw-semibold d-flex align-items-center gap-1" type="button" onclick="abrirModalRapidoProducto()" title="Crear Nuevo Producto">
                                                <i class="fas fa-plus"></i>
                                                <span class="d-none d-sm-inline">Nuevo Producto</span>
                                            </button>
                                        </div>
                                        <div id="resultadosBusqueda" class="list-group resultados-busqueda-dropdown position-absolute w-100 mt-1" style="background-color: #ffffff !important; background: #ffffff !important; border: 1px solid #cbd5e1 !important; border-radius: 16px !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1) !important; z-index: 1080; display: none; max-height: 350px; overflow-y: auto; overflow-x: hidden;"></div>
                                    </div>
                                </div>

                                <!-- 2. FICHA INFORMATIVA DEL PRODUCTO SELECCIONADO (CAMPOS VISUALES READ-ONLY) -->
                                <div id="panelInfoProductoSeleccionado" class="p-3 rounded-4 mb-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0; display: none;">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0" id="infoProdNombre">--</h6>
                                                <div class="d-flex align-items-center gap-2 small text-muted font-monospace">
                                                    <span>Código: <strong id="infoProdCodigo">--</strong></span>
                                                    <span>•</span>
                                                    <span>Unidad: <strong id="infoProdUnidad">--</strong></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-semibold" id="infoProdIvaBadge" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                                                IVA: --
                                            </span>
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="infoProdStockBadge" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                                Stock Total: --
                                            </span>
                                        </div>
                                    </div>

                                    <!-- CHIPS DE VALORES HISTÓRICOS / ACTUALES -->
                                    <div class="row g-2 font-monospace small">
                                        <div class="col-md-4">
                                            <div class="p-2 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                                                <span class="text-secondary fw-semibold">Costo Actual:</span>
                                                <strong class="text-dark" id="infoProdCostoActual">$ 0.00 / Bs. 0.00</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-2 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                                                <span class="text-secondary fw-semibold">Precio Detal Actual:</span>
                                                <strong class="text-primary" id="infoProdDetalActual">$ 0.00 / Bs. 0.00</strong>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="p-2 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                                                <span class="text-secondary fw-semibold">Precio Mayorista Actual:</span>
                                                <strong style="color: #7e22ce;" id="infoProdMayoristaActual">$ 0.00 / Bs. 0.00</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. FORMULARIO DE INGRESO DEL ARTÍCULO (CAMPOS MODIFICABLES EJECUTIVOS) -->
                                <div id="panelFormularioRenglon" style="display: none;">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold text-dark mb-0">
                                            <i class="fas fa-edit text-primary me-2"></i> <span id="labelTituloFormRenglon">2. Datos de Entrada del Renglón</span>
                                        </h6>
                                        <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-3 py-1" id="badgeModoEdicion" style="display: none;">
                                            <i class="fas fa-pencil-alt me-1"></i> Editando renglón cargado
                                        </span>
                                    </div>

                                    <div class="row g-3 p-3 rounded-4 bg-light bg-opacity-50 border mb-3">
                                        
                                        <!-- Almacén Destino -->
                                        <div class="col-md-3">
                                            <label class="form-label-executive"><i class="fas fa-warehouse text-primary"></i> Almacén Destino <span class="text-danger">*</span></label>
                                            <select id="form_renglon_almacen_id" class="form-select form-select-executive" onchange="actualizarStockAlmacenFormulario()">
                                                <!-- Opciones cargadas dinámicamente -->
                                            </select>
                                            <small class="text-muted font-monospace d-block mt-1" style="font-size: 0.72rem;">
                                                Stock en almacén: <strong id="form_renglon_stock_actual" class="text-dark">0</strong>
                                            </small>
                                        </div>

                                        <!-- Bultos & Unidades por Bulto -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-cubes text-secondary"></i> Bultos / Cajas <span class="text-danger">*</span></label>
                                            <input type="number" step="any" min="0.001" id="form_renglon_bultos" class="form-control form-control-executive font-monospace text-center fw-bold" placeholder="0" oninput="calcularCantidadDesdeBultos()">
                                        </div>

                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-box-open text-secondary"></i> Unid. / Bulto <span class="text-danger">*</span></label>
                                            <input type="number" step="any" min="1" id="form_renglon_unid_bulto" class="form-control form-control-executive font-monospace text-center" placeholder="1" value="1" oninput="calcularCantidadDesdeBultos()">
                                        </div>

                                        <!-- Cantidad Total de Unidades (Cálculo Automático Readonly) -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-calculator text-primary"></i> Cantidad Total</label>
                                            <input type="number" step="any" id="form_renglon_cantidad" class="form-control form-control-executive font-monospace fw-bold text-center bg-light text-primary border-primary" placeholder="0" value="0" readonly tabindex="-1" title="Calculado automáticamente: Bultos * Unid./Bulto">
                                        </div>

                                        <!-- Costo por Bulto (Obligatorio) -->
                                        <div class="col-md-3">
                                            <label class="form-label-executive"><i class="fas fa-tag text-success"></i> Costo por Bulto <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white label-simbolo-moneda-fac text-success fw-bold">$</span>
                                                <input type="number" step="any" min="0.0001" id="form_renglon_costo_bulto" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.00" oninput="calcularCostoDesdeBulto()">
                                            </div>
                                        </div>

                                        <!-- Costo Unitario de Compra (Cálculo Automático) -->
                                        <div class="col-md-3">
                                            <label class="form-label-executive"><i class="fas fa-dollar-sign text-secondary"></i> Costo Unitario Calculado</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light label-simbolo-moneda-fac">$</span>
                                                <input type="number" step="any" min="0" id="form_renglon_costo_unitario" class="form-control form-control-executive font-monospace text-end bg-light" placeholder="0.00" readonly tabindex="-1">
                                            </div>
                                            <small class="text-muted font-monospace d-block mt-1 text-end" id="form_renglon_costo_equivalente" style="font-size: 0.72rem;">
                                                Equiv: Bs. 0.00
                                            </small>
                                        </div>

                                        <!-- Descuento Comercial % -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-percent text-secondary"></i> Descuento (%)</label>
                                            <div class="input-group">
                                                <input type="number" step="any" min="0" max="100" id="form_renglon_descuento" class="form-control form-control-executive font-monospace text-center" value="0.00" oninput="recalcularFormularioRenglon()">
                                                <span class="input-group-text bg-white">%</span>
                                            </div>
                                        </div>

                                        <!-- IVA -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-receipt text-secondary"></i> IVA</label>
                                            <select id="form_renglon_iva" class="form-select form-select-executive font-monospace" onchange="recalcularFormularioRenglon()">
                                                <option value="16">IVA 16%</option>
                                                <option value="8">IVA 8%</option>
                                                <option value="0">Exento (0%)</option>
                                            </select>
                                        </div>

                                        <!-- Margen Detal % & Nuevo Precio Detal -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-chart-line text-primary"></i> Margen Detal (%)</label>
                                            <div class="input-group">
                                                <input type="number" step="any" min="0" id="form_renglon_margen_detal" class="form-control form-control-executive font-monospace text-center" value="30" oninput="calcularPrecioDetalDesdeMargen()">
                                                <span class="input-group-text bg-white">%</span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label-executive"><i class="fas fa-store text-primary"></i> Nuevo Precio Detal</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-primary fw-bold">$</span>
                                                <input type="number" step="any" min="0" id="form_renglon_precio_detal" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.00" oninput="calcularMargenDetalDesdePrecio()">
                                            </div>
                                            <small class="text-muted font-monospace d-block mt-1 text-end" id="form_renglon_detal_bs" style="font-size: 0.72rem;">
                                                Bs. 0.00
                                            </small>
                                        </div>

                                        <!-- Margen Mayorista % & Nuevo Precio Mayorista -->
                                        <div class="col-md-2">
                                            <label class="form-label-executive"><i class="fas fa-boxes text-secondary"></i> Margen Mayor (%)</label>
                                            <div class="input-group">
                                                <input type="number" step="any" min="0" id="form_renglon_margen_mayorista" class="form-control form-control-executive font-monospace text-center" value="15" oninput="calcularPrecioMayoristaDesdeMargen()">
                                                <span class="input-group-text bg-white">%</span>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label-executive" style="color: #7e22ce;"><i class="fas fa-truck-moving"></i> Nuevo Precio Mayorista</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white fw-bold" style="color: #7e22ce;">$</span>
                                                <input type="number" step="any" min="0" id="form_renglon_precio_mayorista" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.00" oninput="calcularMargenMayoristaDesdePrecio()">
                                            </div>
                                            <small class="text-muted font-monospace d-block mt-1 text-end" id="form_renglon_mayorista_bs" style="font-size: 0.72rem;">
                                                Bs. 0.00
                                            </small>
                                        </div>

                                        <!-- Subtotal Renglón & Botón de Inserción -->
                                        <div class="col-md-4 d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border">
                                            <div>
                                                <span class="text-muted small d-block">Subtotal Renglón Neto:</span>
                                                <h5 class="fw-bold mb-0 text-dark font-monospace" id="form_renglon_subtotal_usd">$ 0.00</h5>
                                                <small class="text-muted font-monospace" id="form_renglon_subtotal_bs">Bs. 0.00</small>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold" onclick="cancelarEdicionRenglon()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-xs d-flex align-items-center gap-2" id="btnGuardarRenglon" onclick="agregarOActualizarRenglon()">
                                                    <i class="fas fa-plus-circle"></i>
                                                    <span id="btnGuardarRenglonTexto">Añadir Artículo</span>
                                                </button>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <!-- CARD INFERIOR: TABLA DE RENGLONES CARGADOS (DETALLE DE FACTURA) -->
                            <div class="card border rounded-4 shadow-xs mb-3 bg-white overflow-hidden">
                                <div class="p-3 border-bottom bg-light d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i> Detalle de Factura (Artículos Cargados)</h6>
                                        <small class="text-muted">Revisa y modifica los renglones agregados a la recepción</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="limpiarTablaProductos()">
                                            <i class="fas fa-eraser me-1"></i> Limpiar Todo
                                        </button>
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold" id="contadorRenglones">0 Productos</span>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0" id="tablaRecepcionDetalles">
                                        <thead class="table-light sticky-top font-monospace" style="z-index: 5; font-size: 0.74rem;">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th style="min-width: 180px;">Producto / SKU</th>
                                                <th style="min-width: 130px;">Almacén</th>
                                                <th class="text-center" style="min-width: 100px;">Cant.</th>
                                                <th class="text-end" style="min-width: 120px;">Costo Unit.</th>
                                                <th class="text-center" style="min-width: 80px;">Desc</th>
                                                <th class="text-center" style="min-width: 80px;">IVA</th>
                                                <th class="text-end" style="min-width: 130px;">Precio Detal</th>
                                                <th class="text-end" style="min-width: 130px;">Precio Mayor.</th>
                                                <th class="text-end" style="min-width: 130px;">Subtotal</th>
                                                <th class="text-center" style="width: 80px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="contenedorFilasRecepcion">
                                            <!-- FILA VACÍA INICIAL -->
                                            <tr id="filaVaciaMensaje">
                                                <td colspan="11" class="text-center py-5 text-muted">
                                                    <i class="fas fa-dolly fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                                    <span class="fw-semibold">No hay productos agregados a la recepción.</span>
                                                    <br>
                                                    <small class="text-muted">Busca o escanea un producto en la parte superior para agregarlo al comprobante.</small>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- CONTENEDOR OCULTO PARA SUBMIT DEL FORMULARIO -->
                            <div id="contenedorInputsHiddenDetalles"></div>

                            <!-- OBSERVACIONES Y RESUMEN DE LIQUIDACIÓN -->
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <div class="card border rounded-4 p-3 h-100 shadow-xs bg-white">
                                        <label class="form-label-executive mb-2"><i class="fas fa-comment-alt text-secondary"></i> Observaciones / Notas de Recepción</label>
                                        <textarea name="observaciones" id="observaciones" rows="4" class="form-control form-control-executive" placeholder="Detalles de la entrega, transportista, condiciones de empaque, precintos..."></textarea>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="card border rounded-4 p-3 shadow-xs bg-white">
                                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="fas fa-calculator text-primary me-2"></i> Resumen de Liquidación</h6>
                                        
                                        <div class="d-flex justify-content-between mb-1 small">
                                            <span class="text-muted">Total Piezas / Unidades:</span>
                                            <strong class="font-monospace text-dark" id="resumenTotalUnidades">0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 small">
                                            <span class="text-muted">Monto Bruto:</span>
                                            <strong class="font-monospace text-dark" id="resumenMontoBrutoUsd">$ 0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 small">
                                            <span class="text-muted">Descuentos Aplicados:</span>
                                            <strong class="font-monospace text-danger" id="resumenDescuentosUsd">-$ 0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1 small">
                                            <span class="text-muted">Subtotal Neto:</span>
                                            <strong class="font-monospace text-dark" id="resumenSubtotalUsd">$ 0.00</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">IVA de Compra:</span>
                                            <strong class="font-monospace text-dark" id="resumenIvaUsd">$ 0.00</strong>
                                        </div>

                                        <div class="p-3 rounded-3 mt-2" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-white-50 small">Total a Pagar USD:</span>
                                                <h4 class="fw-bold mb-0 text-warning font-monospace" id="resumenTotalGeneralUsd">$ 0.00</h4>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-white-50 small">Equivalente en Bolívares:</span>
                                                <h6 class="fw-bold mb-0 text-white font-monospace" id="resumenTotalGeneralBs">Bs. 0.00</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </form>
                </div>

                <!-- FOOTER DEL MODAL -->
                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-executive-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    
                    <div class="d-flex gap-2" id="footerAccionesRecepcion">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" id="btnVolverFase1Footer" style="display: none;" onclick="volverAFase1()">
                            <i class="fas fa-arrow-left me-1"></i> Volver a Fase 1
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnAvanzarFase2Footer" onclick="avanzarAFase2()">
                            <span>Continuar a Fase 2</span> <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        <button type="button" class="btn btn-executive-submit" id="btnGuardarRecepcion" style="display: none;" onclick="guardarRecepcion()">
                            <i class="fas fa-check-circle me-1"></i> Procesar Recepción e Ingresar al Inventario
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLE 360° / COMPROBANTE DE RECEPCIÓN -->
    <div class="modal fade" id="modalFichaRecepcion" tabindex="-1" aria-labelledby="modalFichaRecepcionLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-file-invoice text-warning fs-5"></i>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalFichaRecepcionLabel">Comprobante de Recepción de Mercancía</h5>
                            <small class="text-white-50">Auditoría completa de compra física y actualización de inventario</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light-subtle" id="contenidoFichaRecepcion">
                    <!-- CARGADO DINÁMICAMENTE POR JS -->
                </div>
                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary rounded-pill px-3" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL RÁPIDO: REGISTRAR PROVEEDOR DIRECTO CON COMPONENTE EJECUTIVO -->
    <x-modal
        id="modalRapidoProveedor"
        title="Nuevo Proveedor Rápido"
        subtitle="Registra el proveedor sin perder el progreso de la recepción"
        icon="fas fa-truck text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioRapidoProveedor"
        submitText="Registrar Proveedor"
    >
        <form id="formularioRapidoProveedor">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-input name="rif" id="rapido_prov_rif" label="RIF / Identificación Fiscal" icon="fas fa-id-card"
                        placeholder="Ej. J-12345678-9" required maxlength="20" />
                </div>
                <div class="col-md-6">
                    <x-input name="nombre" id="rapido_prov_nombre" label="Nombre Comercial" icon="fas fa-store"
                        placeholder="Ej. Distribuidora Los Andes C.A." required maxlength="150" />
                </div>
                <div class="col-12">
                    <x-input name="razon_social" id="rapido_prov_razon_social" label="Razón Social / Nombre Legal" icon="fas fa-landmark"
                        placeholder="Ej. Inversiones Los Andes 2026, C.A." required maxlength="150" />
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
                    <x-input name="direccion" id="rapido_prov_direccion" label="Dirección Fiscal" icon="fas fa-map-marker-alt"
                        placeholder="Ciudad, Sector, Calle..." maxlength="255" optionalText="Opcional" />
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL RÁPIDO: REGISTRAR PRODUCTO DIRECTO CON COMPONENTE EJECUTIVO -->
    <x-modal
        id="modalRapidoProducto"
        title="Nuevo Producto Rápido"
        subtitle="Registra el producto sin salir ni perder el progreso de la recepción"
        icon="fas fa-box-open text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioRapidoProducto"
        submitText="Crear y Cargar al Formulario"
    >
        <form id="formularioRapidoProducto">
            @csrf
            <input type="hidden" name="tipo" value="producto">
            <input type="hidden" name="aplica_igtf" value="0">
            <input type="hidden" name="stock_minimo" value="0">

            <div class="row g-3">
                <div class="col-md-6">
                    <x-input name="codigo_interno" id="rapido_prod_codigo" label="Código Interno / SKU" icon="fas fa-hashtag"
                        placeholder="Ej. PROD-00123" required maxlength="50" />
                </div>
                <div class="col-md-6">
                    <x-input name="codigo_barra_principal" id="rapido_prod_barcode" label="Código de Barras" icon="fas fa-barcode"
                        placeholder="Ej. 7591234567890" maxlength="100" optionalText="Opcional" />
                </div>
                <div class="col-12">
                    <x-input name="nombre" id="rapido_prod_nombre" label="Nombre o Descripción del Producto" icon="fas fa-tag"
                        placeholder="Ej. Harina de Trigo Todo Uso 1kg" required maxlength="150" />
                </div>
                <div class="col-md-6">
                    <x-select name="categoria_id" id="rapido_prod_categoria_id" label="Categoría" icon="fas fa-folder" required>
                        <option value="">Seleccione categoría...</option>
                    </x-select>
                </div>
                <div class="col-md-6">
                    <x-select name="unidad_medida" id="rapido_prod_unidad" label="Unidad de Medida" icon="fas fa-balance-scale" required>
                        <option value="UND" selected>UND (Unidad)</option>
                        <option value="KG">KG (Kilogramo)</option>
                        <option value="GR">GR (Gramo)</option>
                        <option value="LTS">LTS (Litro)</option>
                        <option value="ML">ML (Mililitro)</option>
                        <option value="MTS">MTS (Metro)</option>
                        <option value="CAJA">CAJA (Caja)</option>
                        <option value="PAQ">PAQ (Paquete)</option>
                        <option value="BULTO">BULTO (Bulto)</option>
                        <option value="SACO">SACO (Saco)</option>
                        <option value="DOC">DOC (Docena)</option>
                    </x-select>
                </div>

                <!-- Toggle Fiscal IVA -->
                <div class="col-12">
                    <div class="p-3 rounded-4 border bg-white shadow-xs">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-percentage text-primary fs-5"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">¿Aplica Impuesto al Valor Agregado (IVA)?</h6>
                                    <small class="text-muted">Si está activo, se calculará el impuesto en las compras y ventas.</small>
                                </div>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="aplica_iva" id="rapido_prod_aplica_iva" value="1" checked onchange="toggleIvaRapidoProducto()">
                            </div>
                        </div>

                        <div class="row g-2 mt-2 pt-2 border-top" id="contenedorIvaPorcentajeRapido">
                            <div class="col-md-6">
                                <label class="form-label-executive small"><i class="fas fa-coins text-secondary"></i> Alícuota de IVA (%)</label>
                                <div class="input-group">
                                    <input type="number" step="any" min="0" max="100" name="iva_porcentaje" id="rapido_prod_iva_porcentaje" class="form-control form-control-executive font-monospace" value="16.00">
                                    <span class="input-group-text bg-white text-muted">%</span>
                                </div>
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
        src="{{ asset('estilos/jsPropios/recepcion.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/recepcion.js')) ?: time() }}">
    </script>
@endsection
