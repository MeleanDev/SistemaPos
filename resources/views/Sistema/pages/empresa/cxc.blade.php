@extends('Sistema.layouts.app')

@section('titulo', '💰 Cuentas por Cobrar (CXC)')
@section('subtitulo', 'Control de créditos de clientes, facturas pendientes, abonos específicos y amortizaciones FIFO')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Cuentas por Cobrar</span>
@endsection

@section('acciones')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-white text-dark border rounded-pill px-3 py-2 shadow-xs fw-semibold d-none d-md-inline-flex align-items-center">
            <i class="fas fa-chart-line text-primary me-2"></i>Tasa BCV del Día: <strong class="ms-1 text-primary" id="kpi_tasa_bcv_badge">0.00 Bs/$</strong>
        </span>
        <x-btn-action
            icon="fas fa-sync-alt"
            text="Actualizar"
            onclick="recargarCxc()"
        />
    </div>
@endsection

@section('contenido')
    <!-- KPIS SUPERIORES -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-primary border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Total por Cobrar</span>
                        <div class="avatar-executive-sm bg-light-primary text-primary" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5;">
                            <i class="fas fa-hand-holding-usd fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark" id="kpi_total_usd">$0.00</h3>
                    <div class="small text-primary fw-semibold" id="kpi_total_bs">Bs. 0.00</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-info border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Clientes Deudores</span>
                        <div class="avatar-executive-sm text-info" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #e0f2fe; color: #0284c7;">
                            <i class="fas fa-users fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark" id="kpi_clientes_count">0</h3>
                    <div class="small text-muted">Con crédito activo pendiente</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-warning border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Facturas Pendientes</span>
                        <div class="avatar-executive-sm text-warning" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #fef3c7; color: #d97706;">
                            <i class="fas fa-file-invoice-dollar fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark" id="kpi_facturas_count">0</h3>
                    <div class="small text-muted">Documentos por liquidar</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Facturas Vencidas</span>
                        <div class="avatar-executive-sm text-danger" style="width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #fee2e2; color: #dc2626;">
                            <i class="fas fa-exclamation-triangle fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-danger" id="kpi_vencidas_count">0</h3>
                    <div class="small text-danger fw-semibold" id="kpi_vencidas_monto">$0.00 en mora</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DATATABLE PRINCIPAL -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Resumen Consolidado de Clientes con Deuda</h5>
                <p class="text-muted small mb-0">Gestión de cartera de créditos indexados, estado de cuenta y amortización de pagos</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="btn-group rounded-pill p-1 bg-white border shadow-xs" role="group" id="filtro_estado_cxc">
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold active btn-primary" onclick="filtrarEstado('todos', this)">
                        Todos
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-danger" onclick="filtrarEstado('vencida', this)">
                        <i class="fas fa-exclamation-circle me-1"></i>En Mora
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-warning" onclick="filtrarEstado('por_vencer', this)">
                        <i class="fas fa-clock me-1"></i>Por Vencer
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 fw-semibold text-success" onclick="filtrarEstado('al_dia', this)">
                        <i class="fas fa-check-circle me-1"></i>Al Día
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-4 pt-1">
            <div class="table-responsive">
                <table id="tabla_cxc_clientes" class="table table-hover align-middle w-100">
                    <thead class="table-dark">
                        <tr>
                            <th class="rounded-start">Cliente / RIF - Cédula</th>
                            <th>Teléfono</th>
                            <th class="text-center">Facturas</th>
                            <th>Deuda Más Antigua</th>
                            <th>Estado / Alerta</th>
                            <th class="text-end">Total Deuda ($)</th>
                            <th class="text-end">Total Deuda (Bs)</th>
                            <th class="text-center rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL 1: DETALLE Y ESTADO DE CUENTA DEL CLIENTE -->
    <div class="modal fade" id="modalDetalleCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-executive-md rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 48px; height: 48px; background-color: #eef2ff; color: #4f46e5;">
                            <i class="fas fa-user-tag fs-5"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h5 class="modal-title fw-bold mb-0 text-white" id="det_cliente_nombre">Cargando cliente...</h5>
                                <span class="badge bg-white text-dark rounded-pill px-2 py-1 small fw-bold" id="det_cliente_cedula">V-00000000</span>
                                <span class="badge bg-primary bg-opacity-25 text-white rounded-pill px-2 py-1 small" id="det_cliente_tipo">DETAL</span>
                            </div>
                            <span class="text-white-50 small" id="det_cliente_contacto">Teléfono: - | Límite Crédito: $0.00</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="javascript:void(0)" class="btn btn-outline-success btn-sm rounded-pill px-3 shadow-none fw-semibold text-white border-success" id="btn_whatsapp_cliente" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i> Notificar Cobro
                        </a>
                        <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-bold" id="btn_abono_general_cliente">
                            <i class="fas fa-coins me-1"></i> Abono Rápido (FIFO)
                        </button>
                        <button type="button" class="btn-close btn-close-white shadow-none ms-2" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-white">
                    <!-- CARDS RESUMEN DE SALDO -->
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card border rounded-4 shadow-xs p-3 bg-white h-100 border-start border-danger border-4">
                                <span class="text-muted small fw-bold text-uppercase">Deuda Total Actual</span>
                                <h4 class="fw-bold text-danger mb-0 mt-1" id="det_deuda_total_usd">$0.00</h4>
                                <small class="text-muted" id="det_deuda_total_bs">Bs. 0.00</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card border rounded-4 shadow-xs p-3 bg-white h-100 border-start border-warning border-4">
                                <span class="text-muted small fw-bold text-uppercase">Facturas Pendientes</span>
                                <h4 class="fw-bold text-dark mb-0 mt-1" id="det_facturas_pendientes_count">0</h4>
                                <small class="text-muted">Por cancelar</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card border rounded-4 shadow-xs p-3 bg-white h-100 border-start border-primary border-4">
                                <span class="text-muted small fw-bold text-uppercase">Límite de Crédito</span>
                                <h4 class="fw-bold text-primary mb-0 mt-1" id="det_limite_credito">$0.00</h4>
                                <small class="text-muted" id="det_dias_credito">0 días de plazo</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card border rounded-4 shadow-xs p-3 bg-white h-100 border-start border-info border-4">
                                <span class="text-muted small fw-bold text-uppercase">Tasa BCV del Día</span>
                                <h4 class="fw-bold text-dark mb-0 mt-1" id="det_tasa_bcv">0.00 Bs/$</h4>
                                <small class="text-primary fw-semibold">Conversión oficial activa</small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑAS: PENDIENTES VS HISTORIAL PAGADAS -->
                    <ul class="nav nav-pills nav-fill bg-white p-1 rounded-pill border shadow-xs mb-3 gap-2" id="pills-tab-cxc" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" id="tab-pendientes-btn" data-bs-toggle="pill" data-bs-target="#tab-pendientes" type="button" role="tab">
                                <i class="fas fa-clock me-1 text-warning"></i> Facturas Pendientes (<span id="badge_count_pendientes">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="tab-pagadas-btn" data-bs-toggle="pill" data-bs-target="#tab-pagadas" type="button" role="tab">
                                <i class="fas fa-check-circle me-1 text-success"></i> Historial Pagadas (<span id="badge_count_pagadas">0</span>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent-cxc">
                        <!-- TAB PENDIENTES -->
                        <div class="tab-pane fade show active" id="tab-pendientes" role="tabpanel">
                            <div class="card border rounded-4 shadow-xs overflow-hidden bg-white">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tabla_facturas_pendientes">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="ps-4">N° Factura / Venta</th>
                                                <th>Emisión</th>
                                                <th>Vencimiento</th>
                                                <th>Estado Mora</th>
                                                <th class="text-end">Total Factura</th>
                                                <th class="text-end">Abonado</th>
                                                <th class="text-end text-danger">Saldo Restante</th>
                                                <th class="text-center pe-4">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_facturas_pendientes"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB PAGADAS -->
                        <div class="tab-pane fade" id="tab-pagadas" role="tabpanel">
                            <div class="card border rounded-4 shadow-xs overflow-hidden bg-white">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tabla_facturas_pagadas">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="ps-4">N° Factura / Venta</th>
                                                <th>Emisión</th>
                                                <th>Vencimiento</th>
                                                <th class="text-end">Total Factura</th>
                                                <th class="text-end">Total Pagado</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center pe-4">Abonos</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_facturas_pagadas"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: ABONAR A FACTURA ESPECÍFICA -->
    <x-modal
        id="modalAbonarFactura"
        title="Abonar a Factura Específica"
        subtitle="Registra el cobro y actualiza el saldo deudor de la factura"
        icon="fas fa-hand-holding-usd text-warning fs-5"
        size="modal-md"
        headerColor="bg-dark text-white"
        formId="formAbonarFactura"
        submitText="Confirmar Abono"
    >
        <form id="formAbonarFactura">
            @csrf
            <input type="hidden" name="cuenta_id" id="abono_cuenta_id">
            <input type="hidden" name="tasa_cambio" id="abono_tasa_cambio">

            <!-- BANNER RESUMEN FACTURA -->
            <div class="card border rounded-4 shadow-xs p-3 mb-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="text-muted small fw-semibold">Factura a Abonar:</span>
                    <span class="badge bg-dark text-white rounded-pill px-3 py-1 fw-bold" id="abono_factura_label">#0000</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small fw-semibold">Saldo Pendiente:</span>
                    <span class="fw-bold text-danger fs-5" id="abono_saldo_label">$0.00</span>
                </div>
                <div class="text-end text-muted small" id="abono_saldo_bs_label">Bs. 0.00 (Tasa BCV del Día)</div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Método de Pago *</label>
                    <select class="form-select rounded-3 shadow-none border" name="metodo_pago_id" id="abono_metodo_pago_id" required onchange="actualizarComportamientoReferencia('abono')">
                        <option value="">Seleccione forma de pago...</option>
                    </select>
                </div>

                <div class="col-6">
                    <label class="form-label small fw-bold text-dark mb-1">Moneda del Pago *</label>
                    <select class="form-select rounded-3 shadow-none border" name="moneda" id="abono_moneda" required onchange="recalcularAbonoEspecifico()">
                        <option value="USD">Dólares ($ USD)</option>
                        <option value="VES">Bolívares (Bs. VES)</option>
                    </select>
                </div>

                <div class="col-6">
                    <label class="form-label small fw-bold text-dark mb-1">Monto a Abonar *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border border-end-0 fw-bold text-muted" id="abono_moneda_sym">$</span>
                        <input type="number" step="0.01" min="0.01" class="form-control rounded-end-3 shadow-none fw-bold" name="monto" id="abono_monto" required placeholder="0.00" oninput="recalcularAbonoEspecifico()">
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center">
                    <span class="small text-primary fw-semibold" id="abono_equivalente_label">Equivalente: Bs. 0.00</span>
                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-3 py-1 small fw-bold" onclick="pagarTotalidadFactura()">
                        <i class="fas fa-check me-1"></i>Pagar Totalidad
                    </button>
                </div>

                <div class="col-6">
                    <label class="form-label small fw-bold text-dark mb-1">Fecha de Abono</label>
                    <input type="date" class="form-control rounded-3 shadow-none border" name="fecha_abono" id="abono_fecha" value="{{ date('Y-m-d') }}">
                </div>

                <div class="col-6">
                    <label class="form-label small fw-bold text-dark mb-1" id="abono_label_referencia">Referencia / Comprobante</label>
                    <input type="text" class="form-control rounded-3 shadow-none border" name="referencia" id="abono_referencia" placeholder="Ej. #123456" maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Notas / Observaciones</label>
                    <textarea class="form-control rounded-3 shadow-none border" name="observaciones" id="abono_observaciones" rows="2" placeholder="Detalle adicional del pago (opcional)..." maxlength="255"></textarea>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL 3: ABONO GENERAL A LA DEUDA (ALGORITMO FIFO) -->
    <x-modal
        id="modalAbonoGeneral"
        title="Abono General a la Deuda (Cascada FIFO)"
        subtitle="El monto se amortizará automáticamente desde la deuda más antigua"
        icon="fas fa-layer-group text-success fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formAbonoGeneral"
        submitText="Procesar Abono General"
    >
        <form id="formAbonoGeneral">
            @csrf
            <input type="hidden" name="cliente_id" id="gen_cliente_id">
            <input type="hidden" name="tasa_cambio" id="gen_tasa_cambio">

            <!-- BANNER DEUDA TOTAL CONSOLIDADA -->
            <div class="card border rounded-4 shadow-xs p-3 mb-3 bg-white border-start border-success border-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase">Cliente Titular:</span>
                        <h6 class="fw-bold text-dark mb-0" id="gen_cliente_label">Cliente</h6>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small fw-bold text-uppercase">Deuda Consolidada:</span>
                        <h5 class="fw-bold text-danger mb-0" id="gen_deuda_total_label">$0.00</h5>
                        <small class="text-muted" id="gen_deuda_total_bs_label">Bs. 0.00</small>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-dark mb-1">Método de Pago *</label>
                    <select class="form-select rounded-3 shadow-none border" name="metodo_pago_id" id="gen_metodo_pago_id" required onchange="actualizarComportamientoReferencia('gen')">
                        <option value="">Seleccione forma de pago...</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark mb-1">Moneda *</label>
                    <select class="form-select rounded-3 shadow-none border" name="moneda" id="gen_moneda" required onchange="recalcularAbonoGeneral()">
                        <option value="USD">Dólares ($ USD)</option>
                        <option value="VES">Bolívares (Bs. VES)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold text-dark mb-1">Monto a Entregar *</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border border-end-0 fw-bold text-muted" id="gen_moneda_sym">$</span>
                        <input type="number" step="0.01" min="0.01" class="form-control rounded-end-3 shadow-none fw-bold" name="monto" id="gen_monto" required placeholder="0.00" oninput="recalcularAbonoGeneral()">
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center">
                    <span class="small text-primary fw-semibold" id="gen_equivalente_label">Equivalente: Bs. 0.00</span>
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 small fw-bold" onclick="pagarDeudaTotalGeneral()">
                        <i class="fas fa-check-double me-1"></i>Pagar Toda la Deuda
                    </button>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-dark mb-1">Fecha de Abono</label>
                    <input type="date" class="form-control rounded-3 shadow-none border" name="fecha_abono" id="gen_fecha" value="{{ date('Y-m-d') }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-dark mb-1" id="gen_label_referencia">Referencia Bancaria</label>
                    <input type="text" class="form-control rounded-3 shadow-none border" name="referencia" id="gen_referencia" placeholder="Ej. Transf / Comprobante" maxlength="100">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-dark mb-1">Observaciones</label>
                    <textarea class="form-control rounded-3 shadow-none border" name="observaciones" id="gen_observaciones" rows="2" placeholder="Detalles adicionales del abono general..." maxlength="255"></textarea>
                </div>

                <!-- PREVISUALIZACIÓN EN VIVO FIFO -->
                <div class="col-12 mt-2">
                    <div class="card border rounded-4 shadow-xs p-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold text-success small"><i class="fas fa-stream me-1"></i> Previsualización de Distribución FIFO:</span>
                            <span class="badge bg-success rounded-pill px-2 py-1 small" id="gen_preview_resumen">0 facturas cubiertas</span>
                        </div>
                        <div class="table-responsive rounded-3 border">
                            <table class="table table-sm align-middle mb-0 small" id="tabla_preview_fifo">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Factura</th>
                                        <th>Fecha Emisión</th>
                                        <th class="text-end">Saldo Actual</th>
                                        <th class="text-end text-success">Monto Aplicado</th>
                                        <th class="text-end">Nuevo Saldo</th>
                                        <th class="text-center">Estado Resultante</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_preview_fifo">
                                    <tr><td colspan="6" class="text-center text-muted py-3">Ingresa un monto para ver la cascada de amortización</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>

    <!-- MODAL 4: HISTORIAL DE ABONOS DE FACTURA -->
    <div class="modal fade" id="modalHistorialAbonos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-receipt text-warning fs-5"></i>
                        <h6 class="modal-title fw-bold text-white mb-0" id="hist_modal_title">Historial de Abonos - Factura</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="table-responsive rounded-3 border">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Forma de Pago</th>
                                    <th>Referencia</th>
                                    <th class="text-end">Abono ($)</th>
                                    <th class="text-end">Abono (Bs)</th>
                                    <th>Registrado por</th>
                                    <th class="text-center">Ticket</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_historial_abonos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/cxc.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/cxc.js')) ?: time() }}"></script>
@endsection
