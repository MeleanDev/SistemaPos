@extends('Sistema.layouts.app')

@section('title', 'Movimientos de Inventario & Kardex')

@section('contenido')
<div class="container-fluid p-4">

    <!-- HEADER EJECUTIVO -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge rounded-pill px-2.5 py-1 font-monospace fw-semibold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.78rem;">
                    <i class="fas fa-boxes-stacked me-1"></i> Control de Stock & Auditoría
                </span>
                <span class="text-muted small">•</span>
                <span class="text-muted small fw-medium">Kardex General Multialmacén</span>
            </div>
            <h3 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-dolly text-primary"></i> Movimientos Kardex & Traslados
            </h3>
            <p class="text-muted small mb-0">Auditoría cronológica, traslados entre almacenes y ajustes de entrada / salida de mercancía.</p>
        </div>

        <!-- BOTONES DE ACCIÓN RÁPIDA -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <x-button variant="outline-secondary" icon="fas fa-sync-alt" text="Actualizar" onclick="recargarTablaKardex()" title="Refrescar lista" />
            <x-button variant="primary" icon="fas fa-arrow-right-arrow-left" text="Nuevo Traslado" onclick="abrirModalTraslado()" />
            <x-button variant="success" icon="fas fa-plus-circle" text="Ajuste Entrada (+)" onclick="abrirModalAjuste('entrada')" />
            <x-button variant="danger" icon="fas fa-minus-circle" text="Ajuste Salida (-)" onclick="abrirModalAjuste('salida')" />
        </div>
    </div>

    <!-- TARJETAS DE KPIS Y RESUMEN DIARIO -->
    <div class="row g-3 mb-4">
        <!-- Total Movimientos Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Movimientos Hoy</span>
                        <h4 class="fw-bold mb-0 text-dark font-monospace" id="kpiTotalMovimientos">0</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">Registros generados</small>
                    </div>
                    <div class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.3rem;">
                        <i class="fas fa-list-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entradas de Stock Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Unidades Ingresadas</span>
                        <h4 class="fw-bold mb-0 text-success font-monospace" id="kpiEntradasHoy">0.00</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">Recepciones y Ajustes (+)</small>
                    </div>
                    <div class="avatar-executive-sm rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.3rem;">
                        <i class="fas fa-arrow-down-long"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salidas de Stock Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Unidades Egresadas</span>
                        <h4 class="fw-bold mb-0 text-danger font-monospace" id="kpiSalidasHoy">0.00</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">Ventas, Mermas y Ajustes (-)</small>
                    </div>
                    <div class="avatar-executive-sm rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.3rem;">
                        <i class="fas fa-arrow-up-long"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Traslados entre Almacenes Hoy -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">Traslados Realizados</span>
                        <h4 class="fw-bold mb-0 text-dark font-monospace" id="kpiTrasladosHoy">0</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">Transferencias entre almacenes</small>
                    </div>
                    <div class="avatar-executive-sm rounded-3 bg-warning bg-opacity-10 text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.3rem;">
                        <i class="fas fa-truck-ramp-box"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PANEL DE FILTROS AVANZADOS -->
    <div class="card border rounded-4 shadow-xs mb-4 bg-white">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-filter text-primary"></i> Filtros de Auditoría de Movimientos
            </h6>
            <x-button variant="link" size="sm" class="text-decoration-none p-0 text-muted" icon="fas fa-undo" text="Limpiar Filtros" onclick="limpiarFiltrosKardex()" />
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <x-select id="filtro_almacen_id" name="filtro_almacen_id" label="Almacén" icon="fas fa-warehouse text-secondary" col="col-md-3" onchange="aplicarFiltrosKardex()">
                    <option value="">Todos los Almacenes</option>
                </x-select>

                <x-select id="filtro_tipo_movimiento" name="filtro_tipo_movimiento" label="Tipo de Movimiento" icon="fas fa-tag text-secondary" col="col-md-3" onchange="aplicarFiltrosKardex()">
                    <option value="">Todos los Tipos</option>
                    <option value="ajuste_positivo">Ajuste Positivo (+)</option>
                    <option value="ajuste_negativo">Ajuste Negativo (-)</option>
                    <option value="traslado_salida">Traslado (Salida)</option>
                    <option value="traslado_entrada">Traslado (Entrada)</option>
                    <option value="entrada_recepcion">Entrada por Recepción</option>
                    <option value="salida_venta">Salida por Venta</option>
                    <option value="anulacion_recepcion">Anulación de Recepción</option>
                    <option value="anulacion_venta">Anulación de Venta</option>
                </x-select>

                <x-input type="date" id="filtro_fecha_desde" name="filtro_fecha_desde" label="Fecha Desde" icon="fas fa-calendar-alt text-secondary" col="col-md-3" class="font-monospace" onchange="aplicarFiltrosKardex()" />
                <x-input type="date" id="filtro_fecha_hasta" name="filtro_fecha_hasta" label="Fecha Hasta" icon="fas fa-calendar-check text-secondary" col="col-md-3" class="font-monospace" onchange="aplicarFiltrosKardex()" />
            </div>
        </div>
    </div>

    <!-- TABLA DE MOVIMIENTOS KARDEX -->
    <div class="card border rounded-4 shadow-xs bg-white overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <div>
                <h5 class="fw-bold text-dark mb-0"><i class="fas fa-list-check text-primary me-2"></i> Registro Histórico del Kardex</h5>
                <small class="text-muted">Movimientos de entrada, salida, transferencias y variaciones de inventario</small>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive p-3">
                <table class="table table-hover align-middle mb-0 w-100" id="datatable_kardex">
                    <thead class="table-light border-bottom font-monospace" style="font-size: 0.76rem;">
                        <tr>
                            <th style="width: 130px;">Fecha / Hora</th>
                            <th style="min-width: 180px;">Producto / SKU</th>
                            <th style="min-width: 120px;">Almacén</th>
                            <th class="text-center" style="min-width: 140px;">Tipo Movimiento</th>
                            <th class="text-center" style="min-width: 90px;">Cantidad</th>
                            <th class="text-center" style="min-width: 140px;">Stock Ant ➔ Nuevo</th>
                            <th class="text-end" style="min-width: 110px;">Costo Ref.</th>
                            <th style="min-width: 180px;">Motivo / Referencia</th>
                            <th style="min-width: 110px;">Usuario</th>
                            <th class="text-center" style="width: 70px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- ========================================================= -->
<!-- MODAL: TRASLADO DE STOCK ENTRE ALMACENES                 -->
<!-- ========================================================= -->
<div class="modal fade" id="modalTraslado" tabindex="-1" aria-labelledby="modalTrasladoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.25rem;">
                        <i class="fas fa-arrow-right-arrow-left"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalTrasladoLabel">Nuevo Traslado entre Almacenes</h5>
                        <small class="text-white-50">Transfiere existencias de un almacén emisor a un almacén receptor</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <form id="formularioTraslado">
                    @csrf
                    
                    <!-- RUTA DE TRASLADO (ORIGEN -> DESTINO) -->
                    <div class="card border rounded-4 p-3 shadow-xs mb-4 bg-light">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label-executive fw-bold text-danger">
                                    <i class="fas fa-warehouse text-danger me-1"></i> Almacén Origen (Emisor) <span class="text-danger">*</span>
                                </label>
                                <select name="almacen_origen_id" id="traslado_almacen_origen_id" class="form-select form-select-executive" required onchange="actualizarStockAlmacenOrigenTraslado()">
                                    <option value="">Seleccione almacén origen...</option>
                                </select>
                            </div>

                            <div class="col-md-2 text-center d-none d-md-block">
                                <div class="avatar-executive-sm mx-auto rounded-circle bg-white shadow-xs text-primary d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 1.1rem; border: 1.5px solid #bfdbfe;">
                                    <i class="fas fa-arrow-right"></i>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label-executive fw-bold text-success">
                                    <i class="fas fa-warehouse text-success me-1"></i> Almacén Destino (Receptor) <span class="text-danger">*</span>
                                </label>
                                <select name="almacen_destino_id" id="traslado_almacen_destino_id" class="form-select form-select-executive" required>
                                    <option value="">Seleccione almacén destino...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- MOTIVO Y FECHA -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label-executive"><i class="fas fa-comment-alt text-primary me-1"></i> Motivo del Traslado <span class="text-danger">*</span></label>
                            <select name="motivo_select" id="traslado_motivo_select" class="form-select form-select-executive mb-2" onchange="seleccionarMotivoTraslado(this.value)">
                                <option value="">Seleccione motivo frecuente...</option>
                            </select>
                            <input type="text" name="motivo" id="traslado_motivo" class="form-control form-control-executive" placeholder="O escribe una justificación personalizada..." required maxlength="255">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-executive"><i class="fas fa-calendar-alt text-secondary me-1"></i> Fecha de Traslado</label>
                            <input type="date" name="fecha" id="traslado_fecha" class="form-control form-control-executive">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-executive"><i class="fas fa-sticky-note text-secondary me-1"></i> Observaciones (Opcional)</label>
                            <input type="text" name="observaciones" id="traslado_observaciones" class="form-control form-control-executive" placeholder="Notas de despacho...">
                        </div>
                    </div>

                    <!-- CONSTRUCTOR DE RENGLÓN DE TRASLADO -->
                    <div class="card border rounded-4 p-3 shadow-xs mb-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-plus-circle text-primary me-2"></i> Seleccionar Producto a Trasladar</h6>
                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="badgeStockOrigenPreview" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                Stock Disponible en Origen: --
                            </span>
                        </div>

                        <div class="row g-3 align-items-end">
                            <x-select2 name="traslado_producto_select" id="traslado_producto_select" label="Producto del Catálogo" icon="fas fa-box text-primary" placeholder="Buscar producto por nombre o SKU..." modalParent="#modalTraslado" col="col-md-6" onchange="seleccionarProductoTraslado()">
                                <option value="">Buscar o seleccionar producto...</option>
                            </x-select2>

                            <div class="col-md-3">
                                <label class="form-label-executive"><i class="fas fa-calculator text-secondary me-1"></i> Cantidad a Trasladar</label>
                                <input type="number" step="any" min="0.001" id="traslado_item_cantidad" class="form-control form-control-executive font-monospace text-center fw-bold" placeholder="0.00">
                            </div>

                            <div class="col-md-3">
                                <button type="button" class="btn btn-primary rounded-pill w-100 py-2 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2" onclick="agregarRenglonTraslado()">
                                    <i class="fas fa-plus"></i>
                                    <span>Agregar al Traslado</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DE ARTÍCULOS A TRASLADAR -->
                    <div class="card border rounded-4 shadow-xs overflow-hidden mb-3 bg-white">
                        <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small"><i class="fas fa-list me-1 text-primary"></i> Artículos en esta Guía de Traslado</span>
                            <span class="badge bg-primary rounded-pill px-2.5 py-1" id="contadorItemsTraslado">0 Productos</span>
                        </div>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="tablaDetallesTraslado">
                                <thead class="table-light border-bottom font-monospace" style="font-size: 0.74rem;">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width: 120px;">Stock Origen</th>
                                        <th class="text-center" style="width: 120px;">Cant. Traslado</th>
                                        <th class="text-center" style="width: 120px;">Stock Resultante</th>
                                        <th class="text-center" style="width: 60px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDetallesTraslado">
                                    <tr id="filaSinItemsTraslado">
                                        <td colspan="6" class="text-center py-4 text-muted small">
                                            <i class="fas fa-dolly fa-2x mb-2 d-block opacity-50"></i>
                                            Aún no has agregado ningún producto para trasladar.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-xs d-flex align-items-center gap-2" id="btnProcesarTraslado" onclick="procesarTraslado()">
                    <i class="fas fa-check-circle"></i>
                    <span>Confirmar y Procesar Traslado</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL: AJUSTE DE INVENTARIO (ENTRADA / SALIDA)           -->
<!-- ========================================================= -->
<div class="modal fade" id="modalAjuste" tabindex="-1" aria-labelledby="modalAjusteLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header border-0 py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between" id="headerModalAjuste" style="background-color: #1e293b; color: #ffffff;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-3 d-flex align-items-center justify-content-center" id="iconoHeaderAjuste" style="width: 44px; height: 44px; font-size: 1.25rem; background-color: rgba(255,255,255,0.15);">
                        <i class="fas fa-plus-circle text-success"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalAjusteLabel">Ajuste de Inventario</h5>
                        <small class="text-white-50" id="subtituloModalAjuste">Incrementa o decrementa existencias por conteo, merma o regularización</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <form id="formularioAjuste">
                    @csrf
                    <input type="hidden" name="tipo_ajuste" id="ajuste_tipo_ajuste" value="entrada">

                    <!-- SELECTOR DE ALMACÉN Y TIPO -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label-executive fw-bold"><i class="fas fa-warehouse text-primary me-1"></i> Almacén de Aplicación <span class="text-danger">*</span></label>
                            <select name="almacen_id" id="ajuste_almacen_id" class="form-select form-select-executive" required onchange="actualizarStockAlmacenAjuste()">
                                <option value="">Seleccione almacén...</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-executive fw-bold"><i class="fas fa-calendar-alt text-secondary me-1"></i> Fecha del Ajuste</label>
                            <input type="date" name="fecha" id="ajuste_fecha" class="form-control form-control-executive">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-executive fw-bold"><i class="fas fa-comment-alt text-primary me-1"></i> Motivo / Justificación <span class="text-danger">*</span></label>
                            <select id="ajuste_motivo_select" class="form-select form-select-executive mb-2" onchange="seleccionarMotivoAjuste(this.value)">
                                <option value="">Seleccione motivo predeterminado...</option>
                            </select>
                            <input type="text" name="motivo" id="ajuste_motivo" class="form-control form-control-executive" placeholder="Escribe el motivo o justificación del ajuste..." required maxlength="255">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label-executive"><i class="fas fa-sticky-note text-secondary me-1"></i> Observaciones de Auditoría (Opcional)</label>
                            <textarea name="observaciones" id="ajuste_observaciones" class="form-control form-control-executive" rows="2" placeholder="Detalles de conteo o acta..."></textarea>
                        </div>
                    </div>

                    <!-- CONSTRUCTOR DE RENGLÓN DE AJUSTE -->
                    <div class="card border rounded-4 p-3 shadow-xs mb-3 bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-box-open text-primary me-2"></i> Agregar Producto al Ajuste</h6>
                            <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold" id="badgeStockAjustePreview" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                Stock Actual en Almacén: --
                            </span>
                        </div>

                        <div class="row g-3 align-items-end">
                            <x-select2 name="ajuste_producto_select" id="ajuste_producto_select" label="Producto del Catálogo" icon="fas fa-box text-primary" placeholder="Buscar producto por nombre o SKU..." modalParent="#modalAjuste" col="col-md-5" onchange="seleccionarProductoAjuste()">
                                <option value="">Buscar o seleccionar producto...</option>
                            </x-select2>

                            <div class="col-md-3">
                                <label class="form-label-executive"><i class="fas fa-calculator text-secondary me-1"></i> Cantidad a Ajustar</label>
                                <input type="number" step="any" min="0.001" id="ajuste_item_cantidad" class="form-control form-control-executive font-monospace text-center fw-bold" placeholder="0.00">
                            </div>

                            <div class="col-md-2" id="contenedorCostoAjuste">
                                <label class="form-label-executive"><i class="fas fa-dollar-sign text-success me-1"></i> Costo Ref. ($)</label>
                                <input type="number" step="any" min="0" id="ajuste_item_costo" class="form-control form-control-executive font-monospace text-end" placeholder="0.0000">
                            </div>

                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary rounded-pill w-100 py-2 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-2" id="btnAgregarRenglonAjuste" onclick="agregarRenglonAjuste()">
                                    <i class="fas fa-plus"></i>
                                    <span>Añadir</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DE RENGLONES A AJUSTAR -->
                    <div class="card border rounded-4 shadow-xs overflow-hidden mb-3 bg-white">
                        <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark small"><i class="fas fa-list me-1 text-primary"></i> Renglones en este Ajuste</span>
                            <span class="badge bg-primary rounded-pill px-2.5 py-1" id="contadorItemsAjuste">0 Productos</span>
                        </div>
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0" id="tablaDetallesAjuste">
                                <thead class="table-light border-bottom font-monospace" style="font-size: 0.74rem;">
                                    <tr>
                                        <th style="width: 35px;" class="text-center">#</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width: 120px;">Stock Actual</th>
                                        <th class="text-center" style="width: 120px;">Cant. Ajuste</th>
                                        <th class="text-center" style="width: 120px;">Stock Proyectado</th>
                                        <th class="text-end" style="width: 110px;">Costo Ref.</th>
                                        <th class="text-center" style="width: 60px;">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDetallesAjuste">
                                    <tr id="filaSinItemsAjuste">
                                        <td colspan="7" class="text-center py-4 text-muted small">
                                            <i class="fas fa-dolly fa-2x mb-2 d-block opacity-50"></i>
                                            Aún no has agregado ningún producto al ajuste.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-bold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-xs d-flex align-items-center gap-2" id="btnProcesarAjuste" onclick="procesarAjuste()">
                    <i class="fas fa-save"></i>
                    <span id="btnProcesarAjusteTexto">Guardar y Aplicar Ajuste</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- MODAL: CONSULTA 360° STOCK DE PRODUCTO POR ALMACÉN        -->
<!-- ========================================================= -->
<div class="modal fade" id="modalConsultaStock" tabindex="-1" aria-labelledby="modalConsultaStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold mb-0 text-white" id="modalConsultaStockLabel">Stock por Almacén</h5>
                    <small class="text-white-50" id="consultaStockProdNombre">Cargando datos...</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white" id="cuerpoModalConsultaStock">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/kardex.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/kardex.js')) ?: time() }}"></script>
@endsection
