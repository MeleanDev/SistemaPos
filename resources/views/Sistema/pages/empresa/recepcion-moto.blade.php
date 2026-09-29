@extends('Sistema.layouts.app')

@section('titulo', '🏍️ Recepción de Motos & Vehículos')
@section('subtitulo',
    'Ingreso de compras por lotes, control estricto por seriales únicos (NIV, Chasis, Motor) y
    liquidación fiscal')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Recepción de Motos</span>
@endsection

@section('acciones')
    <div class="d-flex align-items-center gap-2">
        <x-button variant="outline-primary" icon="fas fa-folder-open" text="Borradores Guardados" badge="0"
            badgeId="badgeConteoBorradores" onclick="abrirModalBorradores()" id="btnAbrirBorradores"
            class="font-monospace" />
        <x-btn-action icon="fas fa-plus" text="Nueva Recepción de Motos" onclick="crear()" />
    </div>
@endsection

@section('contenido')
    <!-- TABLA PRINCIPAL DE RECEPCIONES DE MOTOS -->
    <x-datatable id="datatable_recepcion_motos" :headers="[
        'Recepción',
        'Doc. Proveedor',
        'N° Control',
        'Proveedor',
        'Almacén Destino',
        'Unidades',
        'Total Compra',
        'Condición',
        'Estado',
        'Acciones',
    ]" />

    <!-- MODAL DE NUEVA RECEPCIÓN DE MOTOS EN 2 FASES -->
    <div class="modal fade" id="modalRecepcionMoto" tabindex="-1" aria-labelledby="modalRecepcionMotoLabel"
        aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen-xl modal-dialog-centered modal-dialog-scrollable"
            style="max-width: 98vw; width: 98vw;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- HEADER DEL MODAL CON STEPPER EJECUTIVO -->
                <div
                    class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center"
                            style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-truck-ramp-box"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalRecepcionMotoLabel">Recepción de Motos
                                & Vehículos</h5>
                            <small class="text-white-50">Ingreso de compras por lotes, captura de seriales (N.I.V., Chasis,
                                Motor) y liquidación</small>
                        </div>
                    </div>

                    <!-- STEPPER VISUAL DE FASES -->
                    <div
                        class="d-flex align-items-center gap-2 bg-white bg-opacity-10 p-1 rounded-pill border border-white border-opacity-25 shadow-xs">
                        <x-button size="sm" class="btn-paso-stepper active px-3 py-1" id="btnPaso1Stepper"
                            onclick="volverAFase1()" icon="fas fa-file-invoice" text="1. Factura & Proveedor" />
                        <i class="fas fa-chevron-right text-white-50 small"></i>
                        <x-button size="sm" class="btn-paso-stepper text-white-50 px-3 py-1" id="btnPaso2Stepper"
                            onclick="avanzarAFase2()" icon="fas fa-motorcycle" text="2. Lotes & Seriales Únicos" />
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span
                            class="badge rounded-pill bg-white bg-opacity-10 text-white border border-white border-opacity-25 px-2.5 py-1.5 font-monospace"
                            id="badgeAutoSaveStatus" style="font-size: 0.75rem;">
                            <i class="fas fa-shield-alt text-success me-1"></i> Auto-guardado activo
                        </span>
                        <span class="badge rounded-pill bg-warning text-dark fw-bold px-3 py-2 font-monospace"
                            style="font-size: 0.85rem;" id="badgeCodigoRecepcion">
                            <i class="fas fa-hashtag me-1"></i>RECMOTO-00000
                        </span>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>

                <!-- BODY DEL MODAL -->
                <div class="modal-body p-4 bg-white">

                    <!-- ALERTA EJECUTIVA DE BORRADOR / PROGRESO DETECTADO -->
                    <div id="alertaBorradorDetectado"
                        class="card border border-warning rounded-4 p-3 bg-white shadow-sm mb-3"
                        style="display: none; border-left: 5px solid #f59e0b !important;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-executive-sm rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px; font-size: 1.15rem;">
                                    <i class="fas fa-history"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0"><i
                                            class="fas fa-exclamation-triangle text-warning me-1"></i> Progreso en Espera /
                                        Borrador Detectado</h6>
                                    <small class="text-muted" id="textoAlertaBorrador">Existe una recepción guardada
                                        previamente con lotes y seriales pendientes.</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <x-button variant="warning" size="sm" icon="fas fa-undo-alt" text="Restaurar Progreso"
                                    onclick="restaurarBorradorDetectado()" class="font-monospace text-dark" />
                                <x-button variant="outline-danger" size="sm" icon="fas fa-trash-alt" text="Descartar"
                                    onclick="descartarBorradorDetectado()" class="font-monospace" />
                            </div>
                        </div>
                    </div>

                    <form id="formularioRecepcionMoto">
                        @csrf
                        <input type="hidden" name="borrador_id" id="borrador_id" value="">
                        <input type="hidden" name="tasa_cambio" id="tasa_cambio" value="1.0000">
                        <input type="hidden" name="moneda_documento" id="moneda_documento" value="USD">

                        <!-- ========================================== -->
                        <!-- FASE 1: DATOS PRINCIPALES DEL DOCUMENTO    -->
                        <!-- ========================================== -->
                        <div id="seccionFase1" class="fase-recepcion-container">

                            <!-- SELECCIÓN DE MONEDA -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-4 bg-white">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><i
                                                class="fas fa-money-bill-wave text-warning me-2"></i> Moneda de la Factura
                                            de Compra</h6>
                                        <small class="text-muted">Indica la moneda en la que viene expresada la compra
                                            física. El sistema convertirá los valores a la tasa oficial del día.</small>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1 fw-bold"
                                        style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                        <i class="fas fa-coins me-1"></i> Tasa Oficial: <span
                                            id="badgeTasaCambio">1.0000</span> Bs.
                                    </span>
                                </div>

                                <div class="row g-3">
                                    <x-radio card="true" name="moneda_factura_radio" id="radio_usd" value="USD"
                                        :checked="true" cardId="cardMonedaUsd"
                                        onclick="seleccionarMonedaDocumento('USD')" icon="fas fa-dollar-sign"
                                        iconColor="text-success" iconBg="bg-success bg-opacity-10"
                                        label="Facturado en Dólares ($ USD)"
                                        description="Costos ingresados en $ y calculados automáticamente en Bolívares."
                                        onchange="seleccionarMonedaDocumento('USD')" />

                                    <x-radio card="true" name="moneda_factura_radio" id="radio_ves" value="VES"
                                        cardId="cardMonedaVes" onclick="seleccionarMonedaDocumento('VES')"
                                        icon="fas fa-money-bill-wave" iconColor="text-primary"
                                        iconBg="bg-primary bg-opacity-10" label="Facturado en Bolívares (Bs. VES)"
                                        description="Costos ingresados en Bs. y convertidos a Dólares según tasa oficial."
                                        onchange="seleccionarMonedaDocumento('VES')" />
                                </div>

                                <!-- CAMPOS EJECUTIVOS PARA TASA DE COMPRA Y TASA DE VENTA -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label-executive">
                                                <i class="fas fa-shopping-cart text-warning me-1"></i> Tasa de Compra (Bs.
                                                / $) <span class="text-danger">*</span>
                                                <small class="text-muted fw-normal">(Tasa del proveedor)</small>
                                            </label>
                                            <div class="input-group">
                                                <span
                                                    class="input-group-text bg-white text-dark fw-bold font-monospace">Bs.</span>
                                                <input type="number" step="any" min="0.0001" name="tasa_compra"
                                                    id="tasa_compra"
                                                    class="form-control form-control-executive font-monospace fw-bold"
                                                    placeholder="1.0000" value="1.0000"
                                                    oninput="actualizarTasasDesdeInput()" required>
                                                <x-button variant="outline-primary" rounded="0"
                                                    class="rounded-end-pill px-3 shadow-xs"
                                                    onclick="restablecerTasaOficial('compra')"
                                                    title="Restablecer a tasa oficial" icon="fas fa-sync-alt" />
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label-executive">
                                                <i class="fas fa-cash-register text-success me-1"></i> Tasa de Venta (Bs. /
                                                $) <span class="text-danger">*</span>
                                                <small class="text-muted fw-normal">(Tasa oficial de fijación PVP)</small>
                                            </label>
                                            <div class="input-group">
                                                <span
                                                    class="input-group-text bg-white text-success fw-bold font-monospace">Bs.</span>
                                                <input type="number" step="any" min="0.0001" name="tasa_venta"
                                                    id="tasa_venta"
                                                    class="form-control form-control-executive font-monospace fw-bold"
                                                    placeholder="1.0000" value="1.0000"
                                                    oninput="actualizarTasasDesdeInput()" required>
                                                <x-button variant="outline-primary" rounded="0"
                                                    class="rounded-end-pill px-3 shadow-xs"
                                                    onclick="restablecerTasaOficial('venta')"
                                                    title="Restablecer a tasa oficial" icon="fas fa-sync-alt" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DATOS DEL DOCUMENTO & PROVEEDOR -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white">
                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                    <i class="fas fa-file-invoice text-primary me-2"></i> Datos del Comprobante Fiscal &
                                    Proveedor
                                </h6>

                                <div class="row g-3">
                                    <x-input name="numero_documento" id="numero_documento" label="N° Factura / Documento"
                                        icon="fas fa-hashtag text-primary" placeholder="Ej. FACT-009841" required
                                        maxlength="100" col="col-md-4" />
                                    <x-input name="numero_control" id="numero_control" label="N° de Control Fiscal"
                                        icon="fas fa-barcode text-secondary" placeholder="Ej. 00-9841"
                                        optionalText="Opcional" maxlength="100" col="col-md-4" />

                                    <x-select name="tipo_documento" id="tipo_documento" label="Tipo de Documento"
                                        icon="fas fa-file-alt text-primary" required col="col-md-4">
                                        <option value="factura">Factura Fiscal</option>
                                        <option value="nota_entrega">Nota de Entrega</option>
                                        <option value="guia_despacho">Guía de Despacho</option>
                                        <option value="orden_compra">Orden de Compra</option>
                                    </x-select>

                                    <x-select2 name="proveedor_id" id="proveedor_id" label="Proveedor / Ensambladora"
                                        icon="fas fa-truck text-primary" placeholder="Seleccione proveedor..."
                                        modalParent="#modalRecepcionMoto" actionText="Nuevo" actionIcon="fas fa-plus"
                                        actionOnClick="abrirModalRapidoProveedor()" required col="col-md-6">
                                        <option value="">Seleccione proveedor...</option>
                                    </x-select2>

                                    <x-select name="almacen_id" id="almacen_id" label="Almacén General de Entrada"
                                        icon="fas fa-warehouse text-primary" required col="col-md-6">
                                        <option value="">Seleccione almacén...</option>
                                    </x-select>

                                    <x-input type="date" name="fecha_emision" id="fecha_emision"
                                        label="Fecha de Emisión" icon="fas fa-calendar-alt text-secondary" required
                                        col="col-md-3" />
                                    <x-input type="date" name="fecha_recepcion" id="fecha_recepcion"
                                        label="Fecha de Recepción" icon="fas fa-calendar-check text-secondary" required
                                        col="col-md-3" />

                                    <x-select name="condicion_pago" id="condicion_pago" label="Condición de Pago"
                                        icon="fas fa-credit-card text-secondary" onchange="toggleCondicionPago()" required
                                        col="col-md-3">
                                        <option value="contado">Contado</option>
                                        <option value="credito">Crédito (CXP)</option>
                                    </x-select>

                                    <div class="col-md-3" id="contenedorDiasCredito" style="display: none;">
                                        <label class="form-label-executive"><i class="fas fa-clock text-warning"></i> Días
                                            de Crédito</label>
                                        <div class="input-group">
                                            <input type="number" name="dias_credito" id="dias_credito"
                                                class="form-control form-control-executive font-monospace" value="30"
                                                min="1" max="365" oninput="calcularFechaVencimiento()">
                                            <span class="input-group-text bg-white text-dark small font-monospace"
                                                id="labelFechaVencimiento">Vence: --</span>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-receipt text-secondary"></i>
                                            Monto Total Factura <span class="text-danger">*</span> <small class="text-muted fw-normal">(Total según documento físico)</small></label>
                                        <div class="input-group">
                                            <span
                                                class="input-group-text bg-white text-primary fw-bold label-simbolo-moneda-fac">$</span>
                                            <input type="number" step="any" min="0" name="monto_bruto_input"
                                                id="monto_bruto_input"
                                                class="form-control form-control-executive font-monospace"
                                                placeholder="0.00" required
                                                oninput="recalcularTotalesGenerales(); dispararAutoGuardado();">
                                        </div>
                                    </div>

                                    <x-input type="number" step="any" min="0" max="100"
                                        name="descuento_global_porcentaje" id="descuento_global_porcentaje"
                                        label="Descuento Global Fac." icon="fas fa-percent text-secondary"
                                        optionalText="%" value="0.00" addonText="%" addonPosition="right"
                                        col="col-md-6" class="font-monospace"
                                        oninput="recalcularTotalesGenerales(); dispararAutoGuardado();" />
                                </div>
                            </div>
                        </div>

                        <!-- ========================================================================= -->
                        <!-- FASE 2: LOTES DE MOTOS, MATRIZ DE SERIALES Y LIQUIDACIÓN                  -->
                        <!-- ========================================================================= -->
                        <div id="seccionFase2" class="fase-recepcion-container" style="display: none;">

                            <!-- BARRA RESUMEN DE FASE 1 CON INDICADOR DE CUADRE -->
                            <div class="card border rounded-4 p-3 shadow-xs mb-3 bg-white">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge rounded-pill px-3 py-2 fw-bold" id="badgeMonedaFase2"
                                            style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">
                                            <i class="fas fa-dollar-sign me-1"></i> Factura en $ USD
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeDocFase2"
                                            style="background-color: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">
                                            <i class="fas fa-file-invoice text-primary me-1"></i> Doc: --
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeProvFase2"
                                            style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                            <i class="fas fa-truck text-primary me-1"></i> Prov: --
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badgeAlmFase2"
                                            style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;">
                                            <i class="fas fa-warehouse text-secondary me-1"></i> Almacén: --
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold"
                                            style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            <i class="fas fa-shopping-cart text-warning me-1"></i> T. Compra: <strong
                                                class="font-monospace" id="badgeTasaCompraFase2">1.0000</strong> Bs.
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold"
                                            style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            <i class="fas fa-cash-register text-success me-1"></i> T. Venta: <strong
                                                class="font-monospace" id="badgeTasaVentaFase2">1.0000</strong> Bs.
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-bold" id="badgeMontoBrutoFase2"
                                            style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;">
                                            <i class="fas fa-receipt me-1"></i> Monto Fac: $ 0.00
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span
                                            class="badge rounded-pill px-3 py-2 fw-bold font-monospace bg-white text-secondary border shadow-xs"
                                            id="badgeEstadoCuadreFactura">
                                            <i class="fas fa-scale-balanced me-1"></i> Sin renglones cargados
                                        </span>
                                        <x-button variant="outline-primary" size="sm" icon="fas fa-edit"
                                            text="Modificar Encabezado" onclick="volverAFase1()" />
                                    </div>
                                </div>
                            </div>

                            <!-- GUÍA / INFORMATIVO MULTI-MODELO -->
                            <div class="card border border-primary-subtle rounded-4 p-3 mb-3 bg-white shadow-xs">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width: 36px; height: 36px; min-width: 36px; background-color: #eff6ff; color: #2563eb; font-size: 1rem;">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="small text-dark">
                                        <strong>Factura con múltiples modelos:</strong> Ingresa el primer modelo (ej: 10
                                        Bera SBR), completa sus seriales y haz clic en <strong>"Agregar este Modelo a la
                                            Factura"</strong>. El formulario se limpiará para que agregues el siguiente
                                        modelo (ej: 10 Kavak / Empire TX) en esta misma factura.
                                    </div>
                                </div>
                            </div>

                            <!-- SELECTOR DE MODO DE RENGLÓN: MOTO CON SERIALES VS PRODUCTO / REPUESTO -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                <div class="d-flex align-items-center gap-2 bg-white p-1.5 rounded-pill border shadow-xs"
                                    style="max-width: 480px; width: 100%;">
                                    <x-button size="sm" variant="primary" id="btnModoItemMoto"
                                        onclick="cambiarModoItem('moto')" class="w-50" icon="fas fa-motorcycle"
                                        text="🏍️ Moto con Seriales" />
                                    <x-button size="sm" variant="outline-success" id="btnModoItemProducto"
                                        onclick="cambiarModoItem('producto')" class="w-50" icon="fas fa-boxes-stacked"
                                        text="📦 Producto / Repuesto" />
                                </div>
                                <span
                                    class="badge rounded-pill bg-info-subtle text-info border border-info-subtle px-3 py-1.5 font-monospace fw-bold">
                                    <i class="fas fa-layer-group me-1"></i> Recepción Mixta Multirubro Habilitada
                                </span>
                            </div>

                            <!-- CARD: CONSTRUCTOR DE MOTO CON SERIALES -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white" id="cardConstructorMoto"
                                style="border-left: 5px solid #2563eb !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-executive-xs rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="fas fa-motorcycle"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            Configurar Modelo / Lote de Motos
                                        </h6>
                                        <span
                                            class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-1"
                                            id="badgeEstadoEdicionLote">
                                            Nuevo Renglón de Moto
                                        </span>
                                    </div>
                                    <x-button variant="outline-danger" size="sm" id="btnCancelarEdicionLote"
                                        onclick="cancelarEdicionLote()" style="display: none;" icon="fas fa-times"
                                        text="Cancelar Edición" />
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-9">
                                        <x-select2 name="lote_modelo_moto_id" id="lote_modelo_moto_id"
                                            label="Modelo de Moto (Catálogo Maestro)" icon="fas fa-motorcycle text-primary"
                                            placeholder="Seleccione por Referencia, Marca, Modelo o Color..."
                                            modalParent="#modalRecepcionMoto" actionText="Nuevo Modelo"
                                            actionIcon="fas fa-plus" actionOnClick="abrirModalRapidoModelo()"
                                            onchange="seleccionarModeloMotoCatalogo(this.value)" required col="col-12">
                                            <option value="">Seleccione por Referencia, Marca, Modelo o Color...</option>
                                        </x-select2>
                                    </div>

                                    <x-input type="number" min="1" max="100" name="lote_cantidad"
                                        id="lote_cantidad" label="Cantidad de Unidades" icon="fas fa-hashtag text-primary"
                                        value="1" required col="col-md-3"
                                        class="font-monospace fw-bold text-center border-primary fs-6"
                                        oninput="generarMatrizSeriales()" />
                                </div>

                                <!-- PREVIEW EJECUTIVO DE CARACTERÍSTICAS DEL MODELO -->
                                <div class="card border rounded-3 p-3 mb-3 shadow-xs" id="contenedorPreviewModelo"
                                    style="background-color: #f8fafc; display: none;">
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge rounded-pill px-3 py-1.5 fw-bold font-monospace shadow-xs" id="chipModeloRef"
                                                style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.85rem;">
                                                <i class="fas fa-hashtag me-1"></i> Ref: #--
                                            </span>
                                            <span class="badge rounded-pill px-3 py-1.5 fw-bold text-dark shadow-xs bg-white border" id="chipModeloNombre" style="font-size: 0.85rem;">
                                                <i class="fas fa-motorcycle text-primary me-1"></i> --
                                            </span>
                                            <span class="badge rounded-pill px-3 py-1.5 fw-semibold shadow-xs bg-white border text-secondary" id="chipModeloSpecs" style="font-size: 0.82rem;">
                                                Año: -- | Color: -- | Cilindrada: --
                                            </span>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold" onclick="abrirModalRapidoModelo(true)">
                                            <i class="fas fa-edit me-1"></i> Editar en Catálogo
                                        </button>
                                    </div>
                                </div>

                                <!-- CAMPOS OCULTOS PARA HISTORIAL FISCAL -->
                                <input type="hidden" name="lote_referencia" id="lote_referencia">
                                <input type="hidden" name="lote_marca" id="lote_marca">
                                <input type="hidden" name="lote_modelo" id="lote_modelo">
                                <input type="hidden" name="lote_anio" id="lote_anio">
                                <input type="hidden" name="lote_color" id="lote_color">
                                <input type="hidden" name="lote_cilindrada" id="lote_cilindrada">

                                <div class="row g-3 mb-3">

                                    <div class="col-md-3">
                                        <x-input type="number" step="any" min="0.0001" name="lote_costo_unitario"
                                            id="lote_costo_unitario" label="Costo Base Unit."
                                            icon="fas fa-tag text-success" addonText="$" placeholder="0.0000" required
                                            col="col-12" class="font-monospace fw-bold text-end"
                                            oninput="recalcularPreciosLote()" />
                                        <div class="mt-1 text-end">
                                            <span
                                                class="badge rounded-pill px-2.5 py-0.5 font-monospace fw-semibold shadow-xs d-inline-block"
                                                id="lote_costo_equivalente"
                                                style="background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.78rem;">
                                                Base: Bs. 0.0000
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <x-input type="number" step="any" min="0" name="lote_flete_unitario"
                                            id="lote_flete_unitario" label="Flete Unitario"
                                            icon="fas fa-truck-ramp-box text-warning" addonText="$" placeholder="0.0000"
                                            value="0.0000" required col="col-12"
                                            class="font-monospace fw-bold text-end" oninput="recalcularPreciosLote()" />
                                        <div class="mt-1 text-end">
                                            <span
                                                class="badge rounded-pill px-2.5 py-0.5 font-monospace fw-semibold shadow-xs d-inline-block"
                                                id="lote_flete_bs"
                                                style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; font-size: 0.78rem;">
                                                Flete: Bs. 0.0000
                                            </span>
                                        </div>
                                    </div>

                                    <x-input type="number" step="any" min="0" max="100"
                                        name="lote_descuento" id="lote_descuento" label="Descuento (%)"
                                        icon="fas fa-percent text-secondary" addonText="%" value="0.00"
                                        col="col-md-2" class="font-monospace text-center"
                                        oninput="recalcularPreciosLote()" />

                                    <x-select name="lote_iva" id="lote_iva" label="IVA Compra"
                                        icon="fas fa-receipt text-secondary" col="col-md-4" class="font-monospace"
                                        onchange="recalcularPreciosLote()">
                                        <option value="16">IVA 16%</option>
                                        <option value="8">IVA 8%</option>
                                        <option value="0">Exento (0%)</option>
                                    </x-select>

                                    <div class="col-md-8">
                                        <label class="form-label-executive"><i class="fas fa-coins text-dark"></i> Costo
                                            Total Calculado (Base + IVA + Flete)</label>
                                        <div class="p-2 rounded-3 bg-white border shadow-xs d-flex align-items-center justify-content-between"
                                            style="min-height: 42px;">
                                            <span class="fw-bold font-monospace text-dark fs-6"
                                                id="lote_costo_total_usd">$ 0.0000</span>
                                            <span class="badge rounded-pill bg-dark text-white font-monospace px-3 py-1.5"
                                                id="lote_costo_total_bs">Bs. 0.0000</span>
                                        </div>
                                    </div>

                                    <!-- PRECIO DETAL SIN IVA Y CON IVA -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-chart-line text-primary"></i>
                                            Margen Detal (%) & Precio Venta (Con IVA / PVP)</label>
                                        <div class="input-group mb-2">
                                            <input type="number" step="any" min="0" id="lote_margen_detal"
                                                class="form-control form-control-executive font-monospace text-center"
                                                style="max-width: 95px;" value="25"
                                                oninput="calcularPrecioDetalLote()">
                                            <span class="input-group-text bg-white">%</span>
                                            <input type="number" step="any" min="0" id="lote_precio_detal"
                                                class="form-control form-control-executive font-monospace fw-bold text-end"
                                                placeholder="PVP Con IVA" oninput="calcularMargenDetalLote()">
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-primary fw-bold"><i class="fas fa-tag me-1"></i>PVP
                                                    (Con IVA):</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="lote_detal_con_iva_badge"
                                                    style="background-color: #eff6ff; color: #1d4ed8; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted fw-semibold"><i
                                                        class="fas fa-info-circle text-primary me-1"></i>Precio Sin
                                                    IVA:</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="lote_detal_sin_iva"
                                                    style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PRECIO MAYORISTA SIN IVA Y CON IVA -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive" style="color: #7e22ce;"><i
                                                class="fas fa-truck-moving"></i> Margen Mayor (%) & Precio Mayor (Con IVA /
                                            PVP)</label>
                                        <div class="input-group mb-2">
                                            <input type="number" step="any" min="0"
                                                id="lote_margen_mayorista"
                                                class="form-control form-control-executive font-monospace text-center"
                                                style="max-width: 95px;" value="15"
                                                oninput="calcularPrecioMayoristaLote()">
                                            <span class="input-group-text bg-white">%</span>
                                            <input type="number" step="any" min="0"
                                                id="lote_precio_mayorista"
                                                class="form-control form-control-executive font-monospace fw-bold text-end"
                                                placeholder="Mayor Con IVA" oninput="calcularMargenMayoristaLote()">
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="fw-bold" style="color: #7e22ce;"><i
                                                        class="fas fa-tag me-1"></i>Mayor (Con IVA):</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="lote_mayorista_con_iva_badge"
                                                    style="background-color: #faf5ff; color: #6b21a8; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted fw-semibold"
                                                    style="color: #7e22ce !important;"><i
                                                        class="fas fa-info-circle me-1"></i>Mayor Sin IVA:</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="lote_mayorista_sin_iva"
                                                    style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- MATRIZ DINÁMICA DE SERIALES POR UNIDAD -->
                                <div class="border rounded-4 p-3 bg-white shadow-xs mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold text-dark mb-0"><i
                                                    class="fas fa-fingerprint text-success me-2"></i> Matriz de Seriales
                                                Únicos para este Modelo</h6>
                                            <span class="badge rounded-pill bg-success text-white px-2 py-1 font-monospace"
                                                id="badgeCantidadSeriales">1 Moto</span>
                                        </div>
                                        <small class="text-muted"><kbd>Enter</kbd> o <kbd>Tab</kbd> para saltar rápidamente
                                            entre celdas</small>
                                    </div>

                                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered bg-white align-middle mb-0"
                                            id="tablaMatrizSeriales">
                                            <thead class="bg-white border-bottom sticky-top font-monospace"
                                                style="font-size: 0.76rem;">
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">#</th>
                                                    <th style="min-width: 170px;">N.I.V. (17 Caracteres) <span
                                                            class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">N° Chasis <span
                                                            class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">N° Motor <span
                                                            class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">Certificado de Origen <span
                                                            class="text-danger">*</span></th>
                                                    <th style="min-width: 100px;">Placa <small
                                                            class="text-muted">(Opcional)</small></th>
                                                    <th style="min-width: 140px;">Almacén Destino</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbodyMatrizSeriales">
                                                <!-- Filas de seriales generadas dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- BOTÓN PARA INSERTAR O GUARDAR EL LOTE A LA FACTURA -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="fas fa-lightbulb text-warning me-1"></i> Al presionar el botón, el modelo
                                        de moto se guardará en el detalle de la factura.
                                    </div>
                                    <x-button variant="primary" id="btnAccionLote" onclick="agregarLoteAFactura()">
                                        <i class="fas fa-plus-circle" id="btnAccionLoteIcono"></i>
                                        <span id="btnAccionLoteTexto">Agregar este Modelo de Moto</span>
                                    </x-button>
                                </div>
                            </div>

                            <!-- CARD: CONSTRUCTOR DE PRODUCTOS / REPUESTOS / ACCESORIOS -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white" id="cardConstructorProducto"
                                style="display: none; border-left: 5px solid #059669 !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-executive-xs rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center"
                                            style="width: 32px; height: 32px;">
                                            <i class="fas fa-box-open"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-0">
                                            Configurar Producto / Repuesto / Accesorio
                                        </h6>
                                        <span
                                            class="badge rounded-pill bg-success-subtle text-success border border-success-subtle font-monospace px-3 py-1"
                                            id="badgeEstadoEdicionProducto">
                                            Nuevo Renglón de Producto
                                        </span>
                                    </div>
                                    <x-button variant="outline-danger" size="sm" id="btnCancelarEdicionProducto"
                                        onclick="cancelarEdicionProducto()" style="display: none;" icon="fas fa-times"
                                        text="Cancelar Edición" />
                                </div>

                                <div class="row g-3 mb-3">
                                    <x-select2 name="prod_select_id" id="prod_select_id" label="Producto del Catálogo"
                                        icon="fas fa-box text-success" placeholder="Buscar producto por nombre o SKU..."
                                        modalParent="#modalRecepcionMoto" actionText="Nuevo" actionIcon="fas fa-plus"
                                        actionOnClick="abrirModalRapidoProducto()" required col="col-md-6">
                                        <option value="">Buscar o seleccionar producto...</option>
                                    </x-select2>

                                    <x-select name="prod_almacen_id" id="prod_almacen_id" label="Almacén Destino"
                                        icon="fas fa-warehouse text-secondary" required col="col-md-6">
                                        <option value="">Seleccione almacén...</option>
                                    </x-select>

                                    <x-input type="number" min="1" step="any" name="prod_cantidad"
                                        id="prod_cantidad" label="Cantidad" icon="fas fa-hashtag text-success"
                                        value="1" required col="col-md-3"
                                        class="font-monospace fw-bold text-center border-success"
                                        oninput="recalcularPreciosProducto()" />
                                    <x-input type="number" step="any" min="0.0001" name="prod_costo_unitario"
                                        id="prod_costo_unitario" label="Costo Unit." icon="fas fa-tag text-success"
                                        addonText="$" placeholder="0.0000" required col="col-md-3"
                                        class="font-monospace fw-bold text-end" oninput="recalcularPreciosProducto()" />
                                    <x-input type="number" step="any" min="0" max="100"
                                        name="prod_descuento" id="prod_descuento" label="Descuento (%)"
                                        icon="fas fa-percent text-secondary" addonText="%" value="0.00"
                                        col="col-md-3" class="font-monospace text-center"
                                        oninput="recalcularPreciosProducto()" />

                                    <x-select name="prod_iva" id="prod_iva" label="IVA"
                                        icon="fas fa-receipt text-secondary" col="col-md-3" class="font-monospace"
                                        onchange="recalcularPreciosProducto()">
                                        <option value="16">IVA 16%</option>
                                        <option value="8">IVA 8%</option>
                                        <option value="0">Exento (0%)</option>
                                    </x-select>

                                    <!-- Margen Detal % & Precios Sin/Con IVA -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-chart-line text-success"></i>
                                            Margen Detal (%) & Precio Venta (Con IVA / PVP)</label>
                                        <div class="input-group mb-2">
                                            <input type="number" step="any" min="0" id="prod_margen_detal"
                                                class="form-control form-control-executive font-monospace text-center"
                                                style="max-width: 95px;" value="30"
                                                oninput="calcularPrecioDetalProducto()">
                                            <span class="input-group-text bg-white">%</span>
                                            <input type="number" step="any" min="0" id="prod_precio_detal"
                                                class="form-control form-control-executive font-monospace fw-bold text-end"
                                                placeholder="PVP Con IVA" oninput="calcularMargenDetalProducto()">
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-success fw-bold"><i class="fas fa-tag me-1"></i>PVP
                                                    (Con IVA):</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="prod_detal_con_iva_badge"
                                                    style="background-color: #ecfdf5; color: #047857; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted fw-semibold"><i
                                                        class="fas fa-info-circle text-success me-1"></i>Precio Sin
                                                    IVA:</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="prod_detal_sin_iva"
                                                    style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Margen Mayorista % & Precios Sin/Con IVA -->
                                    <div class="col-md-6">
                                        <label class="form-label-executive" style="color: #7e22ce;"><i
                                                class="fas fa-truck-moving"></i> Margen Mayor (%) & Precio Mayor (Con IVA /
                                            PVP)</label>
                                        <div class="input-group mb-2">
                                            <input type="number" step="any" min="0"
                                                id="prod_margen_mayorista"
                                                class="form-control form-control-executive font-monospace text-center"
                                                style="max-width: 95px;" value="15"
                                                oninput="calcularPrecioMayoristaProducto()">
                                            <span class="input-group-text bg-white">%</span>
                                            <input type="number" step="any" min="0"
                                                id="prod_precio_mayorista"
                                                class="form-control form-control-executive font-monospace fw-bold text-end"
                                                placeholder="Mayor Con IVA" oninput="calcularMargenMayoristaProducto()">
                                        </div>
                                        <div class="d-flex flex-column gap-1.5 p-1 bg-transparent">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="fw-bold" style="color: #7e22ce;"><i
                                                        class="fas fa-tag me-1"></i>Mayor (Con IVA):</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="prod_mayorista_con_iva_badge"
                                                    style="background-color: #faf5ff; color: #6b21a8; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted fw-semibold"
                                                    style="color: #7e22ce !important;"><i
                                                        class="fas fa-info-circle me-1"></i>Mayor Sin IVA:</small>
                                                <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold"
                                                    id="prod_mayorista_sin_iva"
                                                    style="background-color: #f1f5f9; color: #1e293b; font-size: 0.84rem;">
                                                    $ 0.00 | Bs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="fas fa-lightbulb text-success me-1"></i> Este producto ingresará
                                        directamente al inventario general y generará su movimiento en el Kardex.
                                    </div>
                                    <x-button variant="success" id="btnAccionProducto"
                                        onclick="agregarProductoAFactura()">
                                        <i class="fas fa-plus-circle" id="btnAccionProductoIcono"></i>
                                        <span id="btnAccionProductoTexto">Agregar este Producto a la Factura</span>
                                    </x-button>
                                </div>
                            </div>

                            <!-- CARD: TABLA DE RENGLONES CARGADOS A LA FACTURA (MOTOS + PRODUCTOS) -->
                            <div class="card border rounded-4 shadow-xs mb-3 bg-white overflow-hidden">
                                <div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0"><i
                                                class="fas fa-list-check text-primary me-2"></i> Renglones Cargados a la
                                            Factura (Motos y Productos)</h6>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold"
                                            id="contadorLotesMotos">0 Renglones (0 Unidades)</span>
                                    </div>
                                </div>

                                <div style="max-height: 340px; overflow-y: auto;">
                                    <x-table-dynamic id="tablaRecepcionMotoDetalles" bodyId="tbodyRecepcionMotoDetalles"
                                        :headers="[
                                            ['label' => '#', 'class' => 'text-center', 'width' => '35px'],
                                            ['label' => 'Tipo', 'class' => 'text-center', 'width' => '70px'],
                                            [
                                                'label' => 'Descripción / Modelo',
                                                'class' => 'text-start',
                                                'width' => '170px',
                                            ],
                                            ['label' => 'Cant.', 'class' => 'text-center', 'width' => '80px'],
                                            ['label' => 'Costo Base', 'class' => 'text-end', 'width' => '100px'],
                                            ['label' => 'Flete Unit.', 'class' => 'text-end', 'width' => '90px'],
                                            ['label' => 'Costo Total', 'class' => 'text-end', 'width' => '100px'],
                                            ['label' => 'IVA', 'class' => 'text-center', 'width' => '65px'],
                                            [
                                                'label' => 'PVP Detal (Sin/Con)',
                                                'class' => 'text-end',
                                                'width' => '110px',
                                            ],
                                            [
                                                'label' => 'PVP Mayor (Sin/Con)',
                                                'class' => 'text-end',
                                                'width' => '110px',
                                            ],
                                            ['label' => 'Subtotal Renglón', 'class' => 'text-end', 'width' => '110px'],
                                            ['label' => 'Seriales', 'class' => 'text-center', 'width' => '80px'],
                                            ['label' => 'Acción', 'class' => 'text-center', 'width' => '80px'],
                                        ]">
                                        <tr id="filaSinLotes">
                                            <td colspan="13" class="text-center py-4 text-muted">
                                                <i class="fas fa-motorcycle fs-3 d-block mb-2 opacity-50"></i>
                                                No has agregado ningún renglón (moto o producto) a esta factura.
                                            </td>
                                        </tr>
                                    </x-table-dynamic>
                                </div>
                            </div>

                            <!-- MODAL / MODALETE PARA REVISAR SERIALES DE UN RENGLÓN YA AGREGADO -->
                            <x-modal id="modalVerSerialesLote" title="Seriales del Modelo"
                                subtitle="Seriales y certificados registrados para este lote"
                                icon="fas fa-fingerprint text-success fs-5" size="modal-lg" :submitButton="false">
                                <div id="modalVerSerialesCuerpo" style="max-height: 400px; overflow-y: auto;">
                                    <!-- Seriales generados dinámicamente -->
                                </div>
                                <x-slot:footer>
                                    <x-button variant="outline-danger" data-bs-dismiss="modal" icon="fas fa-times"
                                        text="Cerrar" />
                                </x-slot:footer>
                            </x-modal>

                            <!-- RESUMEN DE TOTALES Y OBSERVACIONES -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card border rounded-4 p-3 shadow-xs h-100 bg-white">
                                        <label class="form-label-executive mb-2"><i
                                                class="fas fa-comment-alt text-secondary me-1"></i> Observaciones Generales de la Compra</label>
                                        <textarea name="observaciones" id="observaciones" class="form-control form-control-executive" rows="7"
                                            placeholder="Información sobre la factura de compra, transporte, fletes, contenedor, precintos o condiciones especiales..."></textarea>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-0 rounded-4 p-4 shadow-sm text-white"
                                        style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="fw-bold text-white text-uppercase mb-0"
                                                style="letter-spacing: 0.05em; font-size: 0.85rem;">
                                                <i class="fas fa-calculator text-warning me-1"></i> Liquidación Fiscal
                                                Global
                                            </h6>
                                            <span
                                                class="badge rounded-pill bg-white bg-opacity-10 text-white-50 px-2.5 py-1 font-monospace small">
                                                Base + IVA + Flete
                                            </span>
                                        </div>

                                        <div
                                            class="d-flex justify-content-between align-items-center mb-1.5 font-monospace">
                                            <span class="text-white-50">Base Imponible:</span>
                                            <span class="fw-bold text-white fs-6" id="resumenBaseImponible">$ 0.00 | Bs. 0.00</span>
                                        </div>

                                        <div
                                            class="d-flex justify-content-between align-items-center mb-1.5 font-monospace">
                                            <span class="text-white-50">Descuento:</span>
                                            <span class="fw-bold text-danger fs-6" id="resumenDescuentoUsd">-$ 0.00 | -Bs. 0.00</span>
                                        </div>

                                        <div
                                            class="d-flex justify-content-between align-items-center mb-1.5 font-monospace">
                                            <span class="text-white-50">Exento (0% IVA):</span>
                                            <span class="fw-bold text-white-50 fs-6" id="resumenExento">$ 0.00 | Bs. 0.00</span>
                                        </div>

                                        <div
                                            class="d-flex justify-content-between align-items-center mb-2 font-monospace">
                                            <span class="text-white-50" id="labelResumenIva">IVA (16%):</span>
                                            <span class="fw-bold text-white fs-6" id="resumenIvaUsd">$ 0.00 | Bs. 0.00</span>
                                        </div>

                                        <!-- SECCIÓN DE FLETE EJECUTIVA INTEGRADA -->
                                        <div class="rounded-4 p-3 my-2" style="background: rgba(255, 255, 255, 0.06); border: 1px solid rgba(255, 255, 255, 0.12);">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                                        style="width: 32px; height: 32px; background: rgba(245, 158, 11, 0.18); color: #fbbf24; font-size: 0.95rem;">
                                                        <i class="fas fa-truck-fast"></i>
                                                    </div>
                                                    <div>
                                                        <span class="fw-bold text-white small d-block">Flete Total Motos</span>
                                                        <small class="text-white-50 font-monospace" style="font-size: 0.72rem;">Acumulado de flete</small>
                                                    </div>
                                                </div>
                                                <div class="text-end font-monospace">
                                                    <span class="fw-bold text-warning fs-6 d-block" id="resumenFleteTotal">$ 0.00 | Bs. 0.00</span>
                                                </div>
                                            </div>

                                            <div class="pt-2 border-top border-white border-opacity-10 d-flex align-items-center justify-content-between">
                                                <x-checkbox
                                                    switch="true"
                                                    id="switchIncluirFleteFactura"
                                                    name="incluir_flete_en_factura"
                                                    value="1"
                                                    onchange="recalcularTotalesGenerales(); dispararAutoGuardado();"
                                                    label="<i class='fas fa-file-invoice-dollar text-info me-1'></i> ¿Incluir flete en el total de la factura fiscal?"
                                                    labelClass="fw-semibold text-white small mb-0 cursor-pointer d-block"
                                                    description="Suma el flete al monto total fiscal a liquidar"
                                                    wrapperClass="w-100 d-flex align-items-center justify-content-between flex-row-reverse"
                                                    style="cursor: pointer; width: 2.6em; height: 1.35em;"
                                                />
                                            </div>
                                        </div>

                                        <hr class="border-secondary my-2">

                                        <div class="d-flex justify-content-between align-items-center mb-1 font-monospace">
                                            <span class="fs-5 fw-bold text-white">MONTO TOTAL FACTURA:</span>
                                            <span class="fs-4 fw-bolder text-warning" id="resumenTotalUsd">$ 0.0000</span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center font-monospace">
                                            <span class="small text-white-50">Equivalente Total (Bs.):</span>
                                            <span class="fw-bold text-success fs-6" id="resumenTotalBs">Bs. 0.0000</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </form>
                </div>

                <!-- FOOTER DEL MODAL -->
                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <x-button variant="outline-danger" data-bs-dismiss="modal" icon="fas fa-times"
                            text="Cancelar" />
                        <x-button variant="outline-warning" id="btnGuardarBorradorModal"
                            onclick="guardarBorradorEnServidor()" title="Guardar progreso actual en la base de datos"
                            icon="fas fa-pause-circle" text="Guardar Borrador" />
                    </div>

                    <div class="d-flex gap-2">
                        <x-button variant="outline-primary" id="btnVolverFase1Modal" onclick="volverAFase1()"
                            style="display: none;" icon="fas fa-arrow-left" text="Volver a Fase 1" />
                        <x-button variant="primary" id="btnAvanzarFase2Modal" onclick="avanzarAFase2()"
                            icon="fas fa-arrow-right" iconPosition="right" text="Pasar a la siguiente fase" />
                        <x-button variant="success" id="btnProcesarRecepcionMoto" onclick="procesarRecepcionMoto()"
                            style="display: none;" icon="fas fa-check-double" text="Procesar Recepción de Motos" />
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLES 360° DE RECEPCIÓN DE MOTOS -->
    <x-modal id="modalDetalleRecepcionMoto" title="Detalles de la Recepción de Motos" subtitle="RECMOTO-00000"
        icon="fas fa-eye text-info fs-5" size="modal-xl" :submitButton="false">
        <div id="contenidoDetalleRecepcionMoto">
            <!-- Contenido cargado dinámicamente -->
        </div>
        <x-slot:footer>
            <x-button variant="outline-danger" data-bs-dismiss="modal" icon="fas fa-times" text="Cerrar" />
            <x-button variant="outline-primary" href="javascript:void(0)" id="btnImprimirDetalle" target="_blank"
                icon="fas fa-print" text="Imprimir Comprobante Físico" />
        </x-slot:footer>
    </x-modal>

    <!-- MODAL RÁPIDO: REGISTRAR PROVEEDOR DIRECTO CON COMPONENTE EJECUTIVO -->
    <x-modal id="modalRapidoProveedor" title="Nuevo Proveedor Rápido"
        subtitle="Registra el proveedor sin perder el progreso de la recepción" icon="fas fa-truck text-warning fs-5"
        size="modal-lg" headerColor="bg-dark text-white" formId="formularioRapidoProveedor"
        submitText="Registrar Proveedor">
        <form id="formularioRapidoProveedor">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <x-input name="rif" id="rapido_prov_rif" label="RIF / Identificación Fiscal"
                        icon="fas fa-id-card" placeholder="Ej. J-12345678-9" required maxlength="20" />
                </div>
                <div class="col-md-6">
                    <x-input name="nombre" id="rapido_prov_nombre" label="Nombre Comercial" icon="fas fa-store"
                        placeholder="Ej. Ensambladora Nacional" required maxlength="150" />
                </div>
                <div class="col-12">
                    <x-input name="razon_social" id="rapido_prov_razon_social" label="Razón Social / Nombre Legal"
                        icon="fas fa-landmark" placeholder="Ej. Ensambladora Nacional C.A." required maxlength="150" />
                </div>
                <div class="col-md-6">
                    <x-input name="nombre_contacto" id="rapido_prov_contacto" label="Persona de Contacto"
                        icon="fas fa-user-tie" placeholder="Ej. Juan Pérez" maxlength="100" optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input name="telefono" id="rapido_prov_telefono" label="Teléfono de Contacto" icon="fas fa-phone"
                        placeholder="Ej. 0414-1234567" maxlength="25" optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input type="email" name="correo" id="rapido_prov_correo" label="Correo Electrónico"
                        icon="fas fa-envelope" placeholder="ejemplo@proveedor.com" maxlength="150"
                        optionalText="Opcional" />
                </div>
                <div class="col-md-6">
                    <x-input name="direccion" id="rapido_prov_direccion" label="Dirección Fiscal"
                        icon="fas fa-map-marker-alt" placeholder="Ciudad, Sector, Calle..." maxlength="255"
                        optionalText="Opcional" />
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL RÁPIDO: REGISTRAR PRODUCTO DIRECTO CON COMPONENTE EJECUTIVO -->
    <x-modal id="modalRapidoProducto" title="Nuevo Producto Rápido"
        subtitle="Registra el producto sin perder el progreso de la recepción" icon="fas fa-box-open text-warning fs-5"
        size="modal-lg" headerColor="bg-dark text-white" formId="formularioRapidoProducto"
        submitText="Crear y Seleccionar Producto">
        <form id="formularioRapidoProducto">
            @csrf
            <input type="hidden" name="tipo" value="producto">
            <input type="hidden" name="aplica_igtf" value="0">
            <input type="hidden" name="stock_minimo" value="0">

            <div class="row g-3">
                <div class="col-md-6">
                    <x-input name="codigo_interno" id="rapido_prod_codigo" label="Código Interno Numérico" icon="fas fa-hashtag"
                        placeholder="[Generado automáticamente]" readonly maxlength="50" optionalText="Numérico auto" />
                </div>
                <div class="col-md-6">
                    <x-input name="codigo_barra_principal" id="rapido_prod_barcode" label="Código de Barras" icon="fas fa-barcode"
                        placeholder="Ej. 7591234567890" maxlength="100" optionalText="Opcional" />
                </div>
                <div class="col-12">
                    <x-input name="nombre" id="rapido_prod_nombre" label="Nombre o Descripción del Producto" icon="fas fa-tag"
                        placeholder="Ej. Casco Integral / Repuesto" required maxlength="150" />
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

                <!-- VARIANTES & CHECKLIST DE OPCIONES (MUEBLERÍA, CAMAS, COLORES) -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 bg-white shadow-xs" style="border-left: 5px solid #6366f1 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-executive-xs rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Checklist de Variantes / Opciones (Mueblería, Camas, Colores)</h6>
                                    <small class="text-muted">Define opciones o colores para seleccionarlos en la recepción sin registrar múltiples productos.</small>
                                </div>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="maneja_variantes" id="rapido_prod_maneja_variantes" value="1" onchange="toggleVariantesRapidoProducto()" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                            </div>
                        </div>

                        <div id="rapido_seccion_variantes" class="mt-3 pt-3 border-top" style="display: none;">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label-executive small"><i class="fas fa-tag text-primary me-1"></i> Tipo de Atributo</label>
                                    <input type="text" id="rapido_prod_attr_nombre" name="atributos_variantes[nombre]" class="form-control form-control-executive form-control-sm font-monospace" placeholder="Ej. Color, Medida" value="Color">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-executive small"><i class="fas fa-plus-circle text-success me-1"></i> Opción / Color</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" id="rapido_prod_opcion_input" class="form-control form-control-executive font-monospace" placeholder="Ej. Gris, Negro, Beige...">
                                        <button type="button" class="btn btn-outline-primary px-3 fw-bold" id="btnRapidoAgregarOpcionVariante" onclick="agregarOpcionVarianteRapido()">
                                            <i class="fas fa-plus me-1"></i> Agregar
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5" onclick="limpiarOpcionesVariantesRapido()" title="Limpiar opciones">
                                        <i class="fas fa-trash-alt me-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>

                            <div class="mt-2 p-2.5 rounded-3 bg-light border">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;">
                                    <i class="fas fa-check-double text-primary me-1"></i> Opciones / Colores Disponibles:
                                </small>
                                <div id="rapido_contenedor_chips_variantes" class="d-flex flex-wrap align-items-center gap-1.5">
                                    <span class="text-muted fst-italic small" id="rapido_placeholder_sin_variantes" style="font-size: 0.78rem;">No hay opciones añadidas. Escriba arriba y presione Agregar.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTROL DE SERIALES ÚNICOS (ELECTRODOMÉSTICOS, AIRES, NEVERAS, FREEZERS) -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 bg-white shadow-xs" style="border-left: 5px solid #10b981 !important;">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-executive-xs rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="fas fa-barcode"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0">Control Estricto por Serial Único Físico (Neveras, Aires, Equipos)</h6>
                                    <small class="text-muted">Cada unidad ingresada requerirá su número de serial físico para seguimiento de garantías y ventas.</small>
                                </div>
                            </div>
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input" type="checkbox" name="maneja_seriales" id="rapido_prod_maneja_seriales" value="1" style="cursor: pointer; width: 2.5em; height: 1.3em;">
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </x-modal>

    <!-- MODAL RÁPIDO: REGISTRAR MODELO DE MOTO DIRECTO EN RECEPCIÓN -->
    <x-modal id="modalRapidoModeloMoto" title="Nuevo Modelo de Moto"
        subtitle="Registra el modelo en el catálogo sin salir de la recepción" icon="fas fa-layer-group text-primary fs-5"
        size="modal-lg" headerColor="bg-dark text-white" formId="formularioRapidoModeloMoto"
        submitText="Guardar y Seleccionar Modelo">
        <form id="formularioRapidoModeloMoto">
            @csrf
            <input type="hidden" name="id" id="rapido_modelo_id">
            <div class="row g-3">
                <div class="col-md-3">
                    <x-input name="referencia" id="rapido_modelo_referencia" label="Referencia / Código"
                        icon="fas fa-hashtag text-primary" placeholder="Ej. 1" required class="font-monospace fw-bold" inputmode="numeric" />
                </div>
                <div class="col-md-3">
                    <x-input name="marca" id="rapido_modelo_marca" label="Marca" icon="fas fa-copyright text-secondary"
                        placeholder="Ej. Bera, Empire..." required maxlength="100" />
                </div>
                <div class="col-md-3">
                    <x-input name="modelo" id="rapido_modelo_modelo" label="Modelo" icon="fas fa-motorcycle text-secondary"
                        placeholder="Ej. SBR 150, TX 200..." required maxlength="100" />
                </div>
                <div class="col-md-3">
                    <x-input type="number" name="anio" id="rapido_modelo_anio" label="Año" icon="fas fa-calendar text-secondary"
                        value="{{ date('Y') }}" required min="1990" max="2099" class="font-monospace" />
                </div>
                <div class="col-md-6">
                    <x-input name="color" id="rapido_modelo_color" label="Color" icon="fas fa-palette text-secondary"
                        placeholder="Ej. Azul, Rojo, Negro..." required maxlength="100" />
                </div>
                <div class="col-md-6">
                    <x-input name="cilindrada" id="rapido_modelo_cilindrada" label="Cilindrada" icon="fas fa-tachometer-alt text-secondary"
                        placeholder="Ej. 150cc, 200cc..." value="150cc" required maxlength="50" class="font-monospace" />
                </div>
                <div class="col-12">
                    <label class="form-label-executive"><i class="fas fa-comment-dots text-secondary me-1"></i> Descripción / Detalles</label>
                    <textarea name="descripcion" id="rapido_modelo_descripcion" class="form-control form-control-executive" rows="2" placeholder="Observaciones generales (opcional)"></textarea>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL DE GESTIÓN DE BORRADORES GUARDADOS -->
    <x-modal id="modalBorradoresRecepcionMoto" title="Borradores de Recepción Guardados"
        subtitle="Retoma recepciones de motos pausadas o no finalizadas" icon="fas fa-folder-open text-warning fs-5"
        size="modal-lg" :submitButton="false">
        <div id="contenedorListaBorradores">
            <div class="text-center py-4 text-muted font-monospace">
                <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
                <p class="mb-0">Cargando borradores guardados...</p>
            </div>
        </div>
        <x-slot:footer>
            <x-button variant="outline-danger" data-bs-dismiss="modal" icon="fas fa-times" text="Cerrar" />
        </x-slot:footer>
    </x-modal>

@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/recepcionMoto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/recepcionMoto.js')) ?: time() }}">
    </script>
@endsection
