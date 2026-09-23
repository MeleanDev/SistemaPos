@extends('Sistema.layouts.app')

@section('titulo', '🏍️ Recepción de Motos & Vehículos')
@section('subtitulo', 'Ingreso de compras por lotes, control estricto por seriales únicos (NIV, Chasis, Motor) y liquidación fiscal')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Recepción de Motos</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nueva Recepción de Motos"
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
    .input-serial-matrix {
        font-family: var(--bs-font-monospace);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
    }
    .input-serial-matrix:focus {
        background-color: #f0fdf4 !important;
        border-color: #16a34a !important;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15) !important;
    }
</style>
@endpush

@section('contenido')
    <!-- TABLA PRINCIPAL DE RECEPCIONES DE MOTOS -->
    <x-datatable
        id="datatable_recepcion_motos"
        :headers="[
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
        ]"
    />

    <!-- MODAL DE NUEVA RECEPCIÓN DE MOTOS EN 2 FASES -->
    <div class="modal fade" id="modalRecepcionMoto" tabindex="-1" aria-labelledby="modalRecepcionMotoLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 98vw; width: 98vw;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                
                <!-- HEADER DEL MODAL CON STEPPER EJECUTIVO -->
                <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-truck-ramp-box"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalRecepcionMotoLabel">Recepción de Motos & Vehículos</h5>
                            <small class="text-white-50">Ingreso de compras por lotes, captura de seriales (N.I.V., Chasis, Motor) y liquidación</small>
                        </div>
                    </div>

                    <!-- STEPPER VISUAL DE FASES -->
                    <div class="d-flex align-items-center gap-2 bg-white bg-opacity-10 p-1 rounded-pill border border-white border-opacity-25 shadow-xs">
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-paso-stepper active" id="btnPaso1Stepper" onclick="volverAFase1()">
                            <i class="fas fa-file-invoice me-1"></i> 1. Factura & Proveedor
                        </button>
                        <i class="fas fa-chevron-right text-white-50 small"></i>
                        <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold transition-all btn-paso-stepper text-white-50" id="btnPaso2Stepper" onclick="avanzarAFase2()">
                            <i class="fas fa-motorcycle me-1"></i> 2. Lotes & Seriales Únicos
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-warning text-dark fw-bold px-3 py-2 font-monospace" style="font-size: 0.85rem;" id="badgeCodigoRecepcion">
                            <i class="fas fa-hashtag me-1"></i>RECMOTO-00000
                        </span>
                        <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- BODY DEL MODAL -->
                <div class="modal-body p-4 bg-white">
                    <form id="formularioRecepcionMoto">
                        @csrf
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
                                        <h6 class="fw-bold text-dark mb-1"><i class="fas fa-money-bill-wave text-warning me-2"></i> Moneda de la Factura de Compra</h6>
                                        <small class="text-muted">Indica la moneda en la que viene expresada la compra física. El sistema convertirá los valores a la tasa oficial del día.</small>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1 fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                        <i class="fas fa-coins me-1"></i> Tasa Oficial: <span id="badgeTasaCambio">1.0000</span> Bs.
                                    </span>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="card card-moneda-seleccion h-100 p-3 rounded-4 border-2 cursor-pointer transition-all active-moneda" id="cardMonedaUsd" onclick="seleccionarMonedaDocumento('USD')">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="avatar-executive-sm rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="fw-bold text-dark mb-0">Facturado en Dólares ($ USD)</h6>
                                                        <small class="text-muted">Costos ingresados en $ y calculados automáticamente en Bolívares.</small>
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
                                                        <small class="text-muted">Costos ingresados en Bs. y convertidos a Dólares según tasa oficial.</small>
                                                    </div>
                                                </div>
                                                <div class="form-check m-0">
                                                    <input class="form-check-input" type="radio" name="moneda_factura_radio" id="radio_ves" value="VES" onchange="seleccionarMonedaDocumento('VES')">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAMPOS EJECUTIVOS PARA TASA DE COMPRA Y TASA DE VENTA -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label-executive">
                                                <i class="fas fa-shopping-cart text-warning me-1"></i> Tasa de Compra (Bs. / $) <span class="text-danger">*</span>
                                                <small class="text-muted fw-normal">(Tasa del proveedor)</small>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-dark fw-bold font-monospace">Bs.</span>
                                                <input type="number" step="any" min="0.0001" name="tasa_compra" id="tasa_compra" class="form-control form-control-executive font-monospace fw-bold" placeholder="1.0000" value="1.0000" oninput="actualizarTasasDesdeInput()" required>
                                                <button type="button" class="btn btn-outline-primary rounded-end-pill px-3 shadow-xs fw-semibold" onclick="restablecerTasaOficial('compra')" title="Restablecer a tasa oficial">
                                                    <i class="fas fa-sync-alt small"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label-executive">
                                                <i class="fas fa-cash-register text-success me-1"></i> Tasa de Venta (Bs. / $) <span class="text-danger">*</span>
                                                <small class="text-muted fw-normal">(Tasa oficial de fijación PVP)</small>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white text-success fw-bold font-monospace">Bs.</span>
                                                <input type="number" step="any" min="0.0001" name="tasa_venta" id="tasa_venta" class="form-control form-control-executive font-monospace fw-bold" placeholder="1.0000" value="1.0000" oninput="actualizarTasasDesdeInput()" required>
                                                <button type="button" class="btn btn-outline-primary rounded-end-pill px-3 shadow-xs fw-semibold" onclick="restablecerTasaOficial('venta')" title="Restablecer a tasa oficial">
                                                    <i class="fas fa-sync-alt small"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DATOS DEL DOCUMENTO & PROVEEDOR -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white">
                                <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                    <i class="fas fa-file-invoice text-primary me-2"></i> Datos del Comprobante Fiscal & Proveedor
                                </h6>

                                <div class="row g-3">
                                    <x-input name="numero_documento" id="numero_documento" label="N° Factura / Documento" icon="fas fa-hashtag text-primary" placeholder="Ej. FACT-009841" required maxlength="100" col="col-md-4" />
                                    <x-input name="numero_control" id="numero_control" label="N° de Control Fiscal" icon="fas fa-barcode text-secondary" placeholder="Ej. 00-9841" optionalText="Opcional" maxlength="100" col="col-md-4" />

                                    <x-select name="tipo_documento" id="tipo_documento" label="Tipo de Documento" icon="fas fa-file-alt text-primary" required col="col-md-4">
                                        <option value="factura">Factura Fiscal</option>
                                        <option value="nota_entrega">Nota de Entrega</option>
                                        <option value="guia_despacho">Guía de Despacho</option>
                                        <option value="orden_compra">Orden de Compra</option>
                                    </x-select>

                                    <x-select2
                                        name="proveedor_id"
                                        id="proveedor_id"
                                        label="Proveedor / Ensambladora"
                                        icon="fas fa-truck text-primary"
                                        placeholder="Seleccione proveedor..."
                                        modalParent="#modalRecepcionMoto"
                                        actionText="Nuevo"
                                        actionIcon="fas fa-plus"
                                        actionOnClick="abrirModalRapidoProveedor()"
                                        required
                                        col="col-md-6"
                                    >
                                        <option value="">Seleccione proveedor...</option>
                                    </x-select2>

                                    <div class="col-md-6">
                                        <label class="form-label-executive"><i class="fas fa-warehouse text-primary"></i> Almacén General de Entrada <span class="text-danger">*</span></label>
                                        <select name="almacen_id" id="almacen_id" class="form-select form-select-executive" required>
                                            <option value="">Seleccione almacén...</option>
                                        </select>
                                    </div>

                                    <x-input type="date" name="fecha_emision" id="fecha_emision" label="Fecha de Emisión" icon="fas fa-calendar-alt text-secondary" required col="col-md-3" />
                                    <x-input type="date" name="fecha_recepcion" id="fecha_recepcion" label="Fecha de Recepción" icon="fas fa-calendar-check text-secondary" required col="col-md-3" />

                                    <x-select name="condicion_pago" id="condicion_pago" label="Condición de Pago" icon="fas fa-credit-card text-secondary" onchange="toggleCondicionPago()" required col="col-md-3">
                                        <option value="contado">Contado</option>
                                        <option value="credito">Crédito (CXP)</option>
                                    </x-select>

                                    <div class="col-md-3" id="contenedorDiasCredito" style="display: none;">
                                        <label class="form-label-executive"><i class="fas fa-clock text-warning"></i> Días de Crédito</label>
                                        <div class="input-group">
                                            <input type="number" name="dias_credito" id="dias_credito" class="form-control form-control-executive font-monospace" value="30" min="1" max="365" oninput="calcularFechaVencimiento()">
                                            <span class="input-group-text bg-light text-muted small font-monospace" id="labelFechaVencimiento">Vence: --</span>
                                        </div>
                                    </div>

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
                        <!-- FASE 2: LOTES DE MOTOS, MATRIZ DE SERIALES Y LIQUIDACIÓN                  -->
                        <!-- ========================================================================= -->
                        <div id="seccionFase2" class="fase-recepcion-container" style="display: none;">
                            
                            <!-- BARRA RESUMEN DE FASE 1 CON INDICADOR DE CUADRE -->
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
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                            <i class="fas fa-shopping-cart text-warning me-1"></i> T. Compra: <strong class="font-monospace" id="badgeTasaCompraFase2">1.0000</strong> Bs.
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">
                                            <i class="fas fa-cash-register text-success me-1"></i> T. Venta: <strong class="font-monospace" id="badgeTasaVentaFase2">1.0000</strong> Bs.
                                        </span>
                                        <span class="badge rounded-pill px-3 py-2 fw-bold" id="badgeMontoBrutoFase2" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;">
                                            <i class="fas fa-receipt me-1"></i> Monto Fac: $ 0.00
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-pill px-3 py-2 fw-bold font-monospace" id="badgeEstadoCuadreFactura" style="background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">
                                            <i class="fas fa-scale-balanced me-1"></i> Sin renglones cargados
                                        </span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1 fw-semibold" onclick="volverAFase1()">
                                            <i class="fas fa-edit me-1"></i> Modificar Encabezado
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- GUÍA / INFORMATIVO MULTI-MODELO -->
                            <div class="card border border-primary-subtle rounded-4 p-3 mb-3 bg-white shadow-xs">
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px; background-color: #eff6ff; color: #2563eb; font-size: 1rem;">
                                        <i class="fas fa-info-circle"></i>
                                    </div>
                                    <div class="small text-dark">
                                        <strong>Factura con múltiples modelos:</strong> Ingresa el primer modelo (ej: 10 Bera SBR), completa sus seriales y haz clic en <strong>"Agregar este Modelo a la Factura"</strong>. El formulario se limpiará para que agregues el siguiente modelo (ej: 10 Kavak / Empire TX) en esta misma factura.
                                    </div>
                                </div>
                            </div>

                            <!-- CARD: CONSTRUCTOR DE LOTE / MODELO DE MOTOS & MATRIZ DINÁMICA DE SERIALES -->
                            <div class="card border rounded-4 p-4 shadow-xs mb-3 bg-white" id="cardConstructorLote" style="border-left: 5px solid #2563eb !important;">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-executive-xs rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-layer-group" id="iconoConstructorLote"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-0" id="tituloConstructorLote">
                                            1. Configurar Modelo / Lote de Motos
                                        </h6>
                                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-3 py-1" id="badgeEstadoEdicionLote">
                                            Nuevo Renglón
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-semibold" id="btnCancelarEdicionLote" onclick="cancelarEdicionLote()" style="display: none;">
                                        <i class="fas fa-times me-1"></i> Cancelar Edición
                                    </button>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-barcode text-secondary"></i> Referencia Numérica <span class="text-danger">*</span></label>
                                        <input type="text" id="lote_referencia" class="form-control form-control-executive font-monospace fw-bold" placeholder="Ej. 1001" maxlength="50" inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-copyright text-primary"></i> Marca <span class="text-danger">*</span></label>
                                        <input type="text" id="lote_marca" class="form-control form-control-executive" placeholder="Ej. Bera, Kavak, Empire, Yamaha..." maxlength="100">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-motorcycle text-primary"></i> Modelo <span class="text-danger">*</span></label>
                                        <input type="text" id="lote_modelo" class="form-control form-control-executive" placeholder="Ej. SBR 150, TX 200, Leon 150..." maxlength="100">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-calendar text-secondary"></i> Año</label>
                                        <input type="text" id="lote_anio" class="form-control form-control-executive font-monospace" placeholder="2025" value="{{ date('Y') }}" maxlength="10">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-palette text-secondary"></i> Color <span class="text-danger">*</span></label>
                                        <input type="text" id="lote_color" class="form-control form-control-executive" placeholder="Ej. Negro / Rojo" maxlength="100">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-tachometer-alt text-secondary"></i> Cilindrada (CC)</label>
                                        <input type="text" id="lote_cilindrada" class="form-control form-control-executive" placeholder="Ej. 150cc" value="150cc" maxlength="50">
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-hashtag text-primary"></i> Cantidad de Motos <span class="text-danger">*</span></label>
                                        <input type="number" min="1" max="100" id="lote_cantidad" class="form-control form-control-executive font-monospace fw-bold text-center border-primary" value="1" oninput="generarMatrizSeriales()">
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-tag text-success"></i> Costo Unitario <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white label-simbolo-moneda-fac text-success fw-bold">$</span>
                                            <input type="number" step="any" min="0.0001" id="lote_costo_unitario" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.0000" oninput="recalcularPreciosLote()">
                                        </div>
                                        <div class="mt-1 text-end">
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs d-inline-block" id="lote_costo_equivalente" style="background-color: #ecfdf5; color: #047857; border: 1.5px solid #6ee7b7; font-size: 0.85rem;">
                                                Equiv: Bs. 0.0000
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label-executive"><i class="fas fa-percent text-secondary"></i> Descuento (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" max="100" id="lote_descuento" class="form-control form-control-executive font-monospace text-center" value="0.00" oninput="recalcularPreciosLote()">
                                            <span class="input-group-text bg-white">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-receipt text-secondary"></i> IVA</label>
                                        <select id="lote_iva" class="form-select form-select-executive font-monospace" onchange="recalcularPreciosLote()">
                                            <option value="16">IVA 16%</option>
                                            <option value="8">IVA 8%</option>
                                            <option value="0">Exento (0%)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-chart-line text-primary"></i> Margen Detal (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" id="lote_margen_detal" class="form-control form-control-executive font-monospace text-center" value="25" oninput="calcularPrecioDetalLote()">
                                            <span class="input-group-text bg-white">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-store text-primary"></i> Precio Detal</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white text-primary fw-bold label-simbolo-moneda-fac">$</span>
                                            <input type="number" step="any" min="0" id="lote_precio_detal" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.0000" oninput="calcularMargenDetalLote()">
                                        </div>
                                        <div class="mt-1 text-end">
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs d-inline-block" id="lote_detal_bs" style="background-color: #eff6ff; color: #1d4ed8; border: 1.5px solid #93c5fd; font-size: 0.88rem;">
                                                Bs. 0.0000
                                            </span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive"><i class="fas fa-boxes text-secondary"></i> Margen Mayor (%)</label>
                                        <div class="input-group">
                                            <input type="number" step="any" min="0" id="lote_margen_mayorista" class="form-control form-control-executive font-monospace text-center" value="15" oninput="calcularPrecioMayoristaLote()">
                                            <span class="input-group-text bg-white">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label-executive" style="color: #7e22ce;"><i class="fas fa-truck-moving"></i> Precio Mayorista</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white fw-bold label-simbolo-moneda-fac" style="color: #7e22ce;">$</span>
                                            <input type="number" step="any" min="0" id="lote_precio_mayorista" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.0000" oninput="calcularMargenMayoristaLote()">
                                        </div>
                                        <div class="mt-1 text-end">
                                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs d-inline-block" id="lote_mayorista_bs" style="background-color: #faf5ff; color: #6b21a8; border: 1.5px solid #d8b4fe; font-size: 0.88rem;">
                                                Bs. 0.0000
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- MATRIZ DINÁMICA DE SERIALES POR UNIDAD -->
                                <div class="border rounded-4 p-3 bg-white shadow-xs mb-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-fingerprint text-success me-2"></i> 2. Matriz de Seriales Únicos para este Modelo</h6>
                                            <span class="badge rounded-pill bg-success text-white px-2 py-1 font-monospace" id="badgeCantidadSeriales">1 Moto</span>
                                        </div>
                                        <small class="text-muted"><kbd>Enter</kbd> o <kbd>Tab</kbd> para saltar rápidamente entre celdas</small>
                                    </div>

                                    <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                                        <table class="table table-sm table-bordered bg-white align-middle mb-0" id="tablaMatrizSeriales">
                                            <thead class="bg-white border-bottom sticky-top font-monospace" style="font-size: 0.76rem;">
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">#</th>
                                                    <th style="min-width: 170px;">N.I.V. (17 Caracteres) <span class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">N° Chasis <span class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">N° Motor <span class="text-danger">*</span></th>
                                                    <th style="min-width: 150px;">Certificado de Origen <span class="text-danger">*</span></th>
                                                    <th style="min-width: 100px;">Placa <small class="text-muted">(Opcional)</small></th>
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
                                        <i class="fas fa-lightbulb text-warning me-1"></i> Al presionar el botón, el modelo se guardará en la tabla de abajo y el formulario quedará libre para agregar otro modelo.
                                    </div>
                                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-xs d-flex align-items-center gap-2" id="btnAccionLote" onclick="agregarLoteAFactura()">
                                        <i class="fas fa-plus-circle" id="btnAccionLoteIcono"></i>
                                        <span id="btnAccionLoteTexto">Agregar este Modelo a la Factura</span>
                                    </button>
                                </div>
                            </div>

                            <!-- CARD: TABLA DE LOTES CARGADOS A LA FACTURA -->
                            <div class="card border rounded-4 shadow-xs mb-3 bg-white overflow-hidden">
                                <div class="p-3 border-bottom bg-white d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i> Modelos / Lotes Agregados a la Factura</h6>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold" id="contadorLotesMotos">0 Modelos (0 Motos)</span>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 340px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0" id="tablaRecepcionMotoDetalles">
                                        <thead class="bg-white border-bottom sticky-top font-monospace" style="font-size: 0.74rem;">
                                            <tr>
                                                <th style="width: 40px;" class="text-center">#</th>
                                                <th style="min-width: 180px;">Modelo / Marca</th>
                                                <th style="min-width: 100px;">Año / Color</th>
                                                <th class="text-center" style="min-width: 90px;">Cant. Motos</th>
                                                <th class="text-end" style="min-width: 110px;">Costo Unit.</th>
                                                <th class="text-center" style="min-width: 70px;">IVA</th>
                                                <th class="text-end" style="min-width: 110px;">Precio Detal</th>
                                                <th class="text-end" style="min-width: 110px;">Precio Mayor</th>
                                                <th class="text-end" style="min-width: 120px;">Total Lote</th>
                                                <th class="text-center" style="min-width: 120px;">Seriales</th>
                                                <th class="text-center" style="width: 90px;">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyRecepcionMotoDetalles">
                                            <tr id="filaSinLotes">
                                                <td colspan="11" class="text-center py-4 text-muted">
                                                    <i class="fas fa-motorcycle fs-3 d-block mb-2 opacity-50"></i>
                                                    No has agregado ningún modelo de motos a esta factura.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- MODAL / MODALETE PARA REVISAR SERIALES DE UN RENGLÓN YA AGREGADO -->
                            <div class="modal fade" id="modalVerSerialesLote" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                        <div class="modal-header bg-dark text-white py-3 px-4">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-fingerprint text-success fs-5"></i>
                                                <h6 class="modal-title fw-bold mb-0 text-white" id="modalVerSerialesTitulo">Seriales del Modelo</h6>
                                            </div>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-3 bg-white" id="modalVerSerialesCuerpo" style="max-height: 400px; overflow-y: auto;">
                                            <!-- Seriales generados dinámicamente -->
                                        </div>
                                        <div class="modal-footer bg-white border-top py-2 px-4">
                                            <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- RESUMEN DE TOTALES Y OBSERVACIONES -->
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <div class="card border rounded-4 p-3 shadow-xs h-100 bg-white">
                                        <label class="form-label-executive"><i class="fas fa-comment-alt text-secondary"></i> Observaciones Generales de la Compra</label>
                                        <textarea name="observaciones" id="observaciones" class="form-control form-control-executive" rows="3" placeholder="Información sobre la factura de compra, transporte, contenedor, precintos o condiciones especiales..."></textarea>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="card border-0 rounded-4 p-4 shadow-sm text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                                        <h6 class="fw-bold text-white-50 text-uppercase mb-3" style="letter-spacing: 0.05em; font-size: 0.8rem;">
                                            <i class="fas fa-calculator me-1"></i> Liquidación Fiscal Global
                                        </h6>

                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace">
                                            <span class="text-white-50">Subtotal Neto:</span>
                                            <span class="fw-bold fs-6" id="resumenSubtotalUsd">$ 0.0000</span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace">
                                            <span class="text-white-50">Total IVA:</span>
                                            <span class="fw-bold fs-6" id="resumenIvaUsd">$ 0.0000</span>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-2 font-monospace">
                                            <span class="text-white-50">Descuento Global:</span>
                                            <span class="fw-bold text-warning fs-6" id="resumenDescuentoUsd">-$ 0.0000</span>
                                        </div>

                                        <hr class="border-secondary my-2">

                                        <div class="d-flex justify-content-between align-items-center mb-1 font-monospace">
                                            <span class="fs-5 fw-bold text-white">TOTAL COMPRA ($):</span>
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
                    <div>
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-xs" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-xs" id="btnVolverFase1Modal" onclick="volverAFase1()" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i> Volver a Fase 1
                        </button>
                        
                        <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" id="btnAvanzarFase2Modal" onclick="avanzarAFase2()">
                            <span>Pasar a la siguiente fase</span>
                            <i class="fas fa-arrow-right ms-1"></i>
                        </button>

                        <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" id="btnProcesarRecepcionMoto" onclick="procesarRecepcionMoto()" style="display: none;">
                            <i class="fas fa-check-double me-1"></i> Procesar Recepción de Motos
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLES 360° DE RECEPCIÓN DE MOTOS -->
    <div class="modal fade" id="modalDetalleRecepcionMoto" tabindex="-1" aria-labelledby="modalDetalleRecepcionMotoLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="modalDetalleRecepcionMotoLabel">Detalles de la Recepción de Motos</h5>
                            <small class="text-white-50 font-monospace" id="detalleModalCodigo">RECMOTO-00000</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white" id="contenidoDetalleRecepcionMoto">
                    <!-- Contenido cargado dinámicamente -->
                </div>

                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-dark rounded-pill px-4 fw-bold shadow-xs" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                    <a href="javascript:void(0)" id="btnImprimirDetalle" target="_blank" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-xs">
                        <i class="fas fa-print me-1"></i> Imprimir Comprobante Físico
                    </a>
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
                        placeholder="Ej. Ensambladora Nacional" required maxlength="150" />
                </div>
                <div class="col-12">
                    <x-input name="razon_social" id="rapido_prov_razon_social" label="Razón Social / Nombre Legal" icon="fas fa-landmark"
                        placeholder="Ej. Ensambladora Nacional C.A." required maxlength="150" />
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

@endsection

@section('scripts')
   @include("Sistema.components.datatable")
    <script src="{{ asset('estilos/jsPropios/recepcionMoto.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/recepcionMoto.js')) ?: time() }}"></script>
@endsection
