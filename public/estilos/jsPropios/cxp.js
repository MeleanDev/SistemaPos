const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlCatalogos = `${urlBase}/catalogos`;
const urlDetalleProveedor = `${urlBase}/proveedor/`;
const urlAbonarFactura = `${urlBase}/abonar-factura`;
const urlAbonarGeneral = `${urlBase}/abonar-general`;

let metodosPagoList = [];
let tasaBcvActiva = 1.0;
let proveedorActivo = null;
let facturasPendientesLista = [];
let saldoFacturaSeleccionadaUsd = 0;
let saldoFacturaSeleccionadaBs = 0;
let filtroEstadoActual = "todos";
let proveedoresCache = [];
let tablaCxpInstancia = null;

$(document).ready(function () {
    cargarCatalogosCxp();
    inicializarTablaCxp();
    aplicarRestriccionesInput();

    $('button[data-bs-toggle="pill"]').on("shown.bs.tab", function () {
        $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
    });
});

const cargarCatalogosCxp = async function () {
    try {
        const res = await peticionAjax({
            url: urlCatalogos,
            type: "GET",
        });

        if (res && res.success && res.data) {
            metodosPagoList = res.data.metodos_pago || [];
            tasaBcvActiva = parseFloat(res.data.tasa_usd) || 1.0;
            $("#kpi_tasa_bcv_badge").text(`${formatearNumero(tasaBcvActiva, 2)} Bs/$`);
            poblarSelectsMetodos();
        }
    } catch (e) {
        console.error(e);
    }
};

const poblarSelectsMetodos = function () {
    const selects = ["#abono_metodo_pago_id", "#gen_metodo_pago_id"];
    selects.forEach(function (selId) {
        const $select = $(selId);
        if (!$select.length) return;
        $select.empty();
        $select.append('<option value="">Seleccione forma de pago...</option>');

        metodosPagoList.forEach(function (m) {
            const nombreMetodo = m.nombre || "Método";
            $select.append(`<option value="${m.id}" data-nombre="${nombreMetodo}">${nombreMetodo}</option>`);
        });
    });
};

const actualizarComportamientoReferencia = function (tipo) {
    const selectId = tipo === "abono" ? "#abono_metodo_pago_id" : "#gen_metodo_pago_id";
    const labelId = tipo === "abono" ? "#abono_label_referencia" : "#gen_label_referencia";
    const inputId = tipo === "abono" ? "#abono_referencia" : "#gen_referencia";

    const $selected = $(selectId).find("option:selected");
    const nombre = ($selected.data("nombre") || "").toLowerCase();

    if (nombre.includes("efectivo") || nombre.includes("cash") || nombre.includes("divisa")) {
        $(labelId).text("Referencia (Opcional)");
        $(inputId).attr("placeholder", "No requerida para pagos en efectivo");
    } else if (nombre.includes("pago móvil") || nombre.includes("pago movil") || nombre.includes("transferencia") || nombre.includes("zelle") || nombre.includes("punto") || nombre.includes("tarjeta") || nombre.includes("cheque")) {
        $(labelId).html('Referencia Bancaria <span class="text-danger">*</span>');
        $(inputId).attr("placeholder", "Ej. # Comprobante / Transf / Cheque");
    } else {
        $(labelId).text("N° Comprobante / Referencia");
        $(inputId).attr("placeholder", "Ej. #123456");
    }
};

const inicializarTablaCxp = function () {
    recargarCxp();
};

const recargarCxp = async function () {
    try {
        const res = await peticionAjax({
            url: urlLista,
            type: "GET",
            mensajeCarga: "Cargando cartera de proveedores...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo cargar la información de cuentas por pagar.",
                });
            }
            return;
        }

        const kpis = res.data.kpis;
        proveedoresCache = res.data.proveedores || [];

        $("#kpi_total_usd").text(`$${formatearNumero(kpis.total_por_pagar_usd)}`);
        $("#kpi_total_bs").text(`Bs. ${formatearNumero(kpis.total_por_pagar_bs)}`);
        $("#kpi_proveedores_count").text(kpis.proveedores_deudores_count);
        $("#kpi_facturas_count").text(kpis.facturas_pendientes_count);
        $("#kpi_vencidas_count").text(kpis.facturas_vencidas_count);
        $("#kpi_vencidas_monto").text(`$${formatearNumero(kpis.total_vencido_usd)} en mora`);

        if (kpis.tasa_cambio) {
            tasaBcvActiva = parseFloat(kpis.tasa_cambio);
            $("#kpi_tasa_bcv_badge").text(`${formatearNumero(tasaBcvActiva, 2)} Bs/$`);
        }

        renderizarTablaProveedores();
    } catch (error) {
        console.error(error);
    }
};

const filtrarEstado = function (estado, btn) {
    filtroEstadoActual = estado;
    $("#filtro_estado_cxp button").removeClass("active btn-primary").addClass("text-secondary");
    $(btn).removeClass("text-secondary").addClass("active btn-primary");
    renderizarTablaProveedores();
};

const renderizarTablaProveedores = function () {
    if ($.fn.DataTable.isDataTable("#tabla_cxp_proveedores")) {
        $("#tabla_cxp_proveedores").DataTable().destroy();
    }

    const $tbody = $("#tabla_cxp_proveedores tbody");
    $tbody.empty();

    let listaFiltrada = proveedoresCache;
    if (filtroEstadoActual !== "todos") {
        listaFiltrada = proveedoresCache.filter(function (p) {
            return p.estado_alerta === filtroEstadoActual;
        });
    }

    listaFiltrada.forEach(function (p) {
        let badgeAlerta = "";
        if (p.estado_alerta === "vencida") {
            badgeAlerta = '<span class="badge bg-danger rounded-pill px-3 py-1 fw-bold"><i class="fas fa-exclamation-circle me-1"></i>Vencida</span>';
        } else if (p.estado_alerta === "por_vencer") {
            badgeAlerta = '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><i class="fas fa-clock me-1"></i>Por Vencer</span>';
        } else {
            badgeAlerta = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i>Al Día</span>';
        }

        const tieneTelefono = p.proveedor_telefono && p.proveedor_telefono !== "No registrado" && p.proveedor_telefono !== "Sin teléfono";
        const telefonoHtml = tieneTelefono
            ? `<span class="small fw-semibold text-dark"><i class="fas fa-phone-alt text-primary me-1 small"></i>${p.proveedor_telefono}</span>`
            : '<span class="text-muted small fst-italic"><i class="fas fa-phone-slash me-1"></i>Sin teléfono</span>';

        const botonWhatsApp = tieneTelefono
            ? `<button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="contactarProveedorWhatsApp(${p.proveedor_id});" title="Contactar por WhatsApp" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fab fa-whatsapp"></i>
               </button>`
            : `<button type="button" class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm opacity-50" disabled title="Sin número telefónico registrado" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fab fa-whatsapp"></i>
               </button>`;

        const tr = `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2 py-1">
                        <div class="avatar-executive-sm me-1 bg-light-primary text-primary shadow-xs" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5;">
                            <i class="fas fa-truck-moving" style="font-size: 0.90rem;"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark text-capitalize" style="font-size: 0.92rem;">${p.proveedor_nombre}</span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-white text-secondary border rounded-pill px-2 py-0 small">${p.proveedor_rif}</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div><i class="fas fa-user-circle text-muted me-1 small"></i><span class="small fw-semibold text-dark">${p.proveedor_contacto || "No registrado"}</span></div>
                    ${telefonoHtml}
                </td>
                <td class="text-center">
                    <span class="badge bg-warning bg-opacity-25 text-dark fw-bold rounded-pill px-3 py-1">
                        ${p.total_facturas_pendientes}
                    </span>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${p.factura_mas_antigua_fecha}</div>
                    <small class="text-muted">${p.dias_antiguedad} día(s) transcurridos</small>
                </td>
                <td>${badgeAlerta}</td>
                <td class="text-end fw-bold text-danger fs-6">$${formatearNumero(p.total_deuda_usd)}</td>
                <td class="text-end fw-bold text-dark">Bs. ${formatearNumero(p.total_deuda_bs)}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        ${botonWhatsApp}
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="abrirDetalleProveedor(${p.proveedor_id});" title="Ver facturas y estado de cuenta" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="abrirModalAbonoGeneral(${p.proveedor_id});" title="Abono Rápido FIFO" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-coins"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        $tbody.append(tr);
    });

    const idioma = Object.assign({}, window.CONFIG_POS?.DATATABLE_LENGUAJE_ES || {
        sSearch: "Buscar proveedor, RIF...",
        zeroRecords: "No se encontraron proveedores con deudas pendientes",
        emptyTable: "No hay cuentas por pagar registradas",
        info: "Mostrando _START_ a _END_ de _TOTAL_ proveedores",
        oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" },
    });

    tablaCxpInstancia = $("#tabla_cxp_proveedores").DataTable({
        responsive: true,
        order: [[5, "desc"]],
        language: idioma,
        pageLength: 10,
    });
};

const abrirDetalleProveedor = async function (proveedorId) {
    try {
        const res = await peticionAjax({
            url: `${urlDetalleProveedor}${proveedorId}`,
            type: "GET",
            mensajeCarga: "Consultando estado de cuenta...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo obtener el estado de cuenta del proveedor.",
                });
            }
            return;
        }

        const data = res.data;
        proveedorActivo = data.proveedor;
        facturasPendientesLista = data.facturas_pendientes || [];

        $("#det_proveedor_nombre").text(data.proveedor.nombre);
        $("#det_proveedor_rif").text(data.proveedor.rif);

        const contactoDetalle = `Contacto: ${data.proveedor.nombre_contacto || "No registrado"} | Teléfono: ${data.proveedor.telefono || "No registrado"}`;
        $("#det_proveedor_contacto").text(contactoDetalle);

        $("#det_deuda_total_usd").text(`$${formatearNumero(data.proveedor.total_deuda_usd)}`);
        $("#det_deuda_total_bs").text(`Bs. ${formatearNumero(data.proveedor.total_deuda_bs)}`);
        $("#det_facturas_pendientes_count").text(facturasPendientesLista.length);
        $("#det_facturas_pagadas_count").text((data.facturas_pagadas || []).length);
        $("#det_tasa_bcv").text(`${formatearNumero(data.tasa_cambio, 2)} Bs/$`);

        $("#badge_count_cxp_pendientes").text(facturasPendientesLista.length);
        $("#badge_count_cxp_pagadas").text((data.facturas_pagadas || []).length);

        const $btnWa = $("#btn_whatsapp_proveedor");
        const tieneTel = data.proveedor.telefono && data.proveedor.telefono !== "No registrado" && data.proveedor.telefono !== "Sin teléfono";
        if (tieneTel) {
            $btnWa.show().off("click").on("click", function () {
                contactarProveedorWhatsApp(data.proveedor.id);
            });
        } else {
            $btnWa.hide();
        }

        $("#btn_abono_general_proveedor").off("click").on("click", function () {
            const modalDetalle = bootstrap.Modal.getInstance(document.getElementById("modalDetalleProveedor"));
            if (modalDetalle) {
                modalDetalle.hide();
            }
            abrirModalAbonoGeneral(data.proveedor.id);
        });

        renderizarFacturasPendientesCxp(facturasPendientesLista);
        renderizarFacturasPagadasCxp(data.facturas_pagadas || []);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalleProveedor"));
        modal.show();
    } catch (e) {
        console.error(e);
    }
};

const renderizarFacturasPendientesCxp = function (facturas) {
    const $tbody = $("#tbody_cxp_pendientes");
    $tbody.empty();

    if (!facturas || facturas.length === 0) {
        $tbody.html('<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success fs-4 d-block mb-2"></i>No hay facturas pendientes con este proveedor.</td></tr>');
        return;
    }

    facturas.forEach(function (f) {
        let badgeMora = "";
        if (f.es_vencida) {
            badgeMora = `<span class="badge bg-danger rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>${Math.abs(f.dias_vencimiento)}d vencida</span>`;
        } else if (f.dias_vencimiento <= 3) {
            badgeMora = `<span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-clock me-1"></i>Vence en ${f.dias_vencimiento}d</span>`;
        } else {
            badgeMora = `<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-calendar-check me-1"></i>${f.dias_vencimiento}d restantes</span>`;
        }

        const abonosEncoded = encodeURIComponent(JSON.stringify(f.abonos || []));
        const tieneAbonos = f.abonos && f.abonos.length > 0;

        const botonHistorial = tieneAbonos
            ? `<button type="button" class="btn btn-outline-warning btn-sm rounded-circle shadow-sm" onclick="abrirHistorialAbonosFactura('${abonosEncoded}', '${f.numero_factura}');" title="Ver historial de abonos" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-history"></i>
               </button>`
            : "";

        const tr = `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${f.numero_factura}</div>
                    <small class="text-muted">Recepción: ${f.recepcion_codigo || f.numero_factura}</small>
                </td>
                <td><i class="far fa-calendar-alt text-muted me-1"></i>${f.fecha_emision}</td>
                <td><i class="far fa-calendar-times text-muted me-1"></i>${f.fecha_vencimiento}</td>
                <td>${badgeMora}</td>
                <td class="text-end fw-bold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
                <td class="text-end text-success fw-semibold">$${formatearNumero(f.monto_pagado_usd)}</td>
                <td class="text-end fw-bold text-danger fs-6">$${formatearNumero(f.saldo_pendiente_usd)}</td>
                <td class="text-center pe-4">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonarFactura(${f.id});">
                            <i class="fas fa-coins me-1"></i>Pagar
                        </button>
                        ${botonHistorial}
                    </div>
                </td>
            </tr>
        `;
        $tbody.append(tr);
    });
};

const renderizarFacturasPagadasCxp = function (facturas) {
    const $tbody = $("#tbody_cxp_pagadas");
    $tbody.empty();

    if (!facturas || facturas.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-folder-open text-muted fs-4 d-block mb-2"></i>No hay historial de facturas saldadas aún.</td></tr>');
        return;
    }

    facturas.forEach(function (f) {
        const abonosEncoded = encodeURIComponent(JSON.stringify(f.abonos || []));
        const tieneAbonos = f.abonos && f.abonos.length > 0;

        const botonHistorial = tieneAbonos
            ? `<button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="abrirHistorialAbonosFactura('${abonosEncoded}', '${f.numero_factura}');" title="Ver historial de abonos" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-receipt"></i>
               </button>`
            : '<span class="text-muted small">-</span>';

        const tr = `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${f.numero_factura}</div>
                    <small class="text-muted">Recepción: ${f.recepcion_codigo || f.numero_factura}</small>
                </td>
                <td><i class="far fa-calendar-alt text-muted me-1"></i>${f.fecha_emision}</td>
                <td><i class="far fa-calendar-times text-muted me-1"></i>${f.fecha_vencimiento}</td>
                <td class="text-end fw-bold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
                <td class="text-end fw-bold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
                <td class="text-center">
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold">
                        <i class="fas fa-check-circle me-1"></i>Saldada
                    </span>
                </td>
                <td class="text-center pe-4">${botonHistorial}</td>
            </tr>
        `;
        $tbody.append(tr);
    });
};

const abrirModalAbonarFactura = function (facturaId) {
    const f = facturasPendientesLista.find(function (item) {
        return item.id === facturaId;
    });

    if (!f) return;

    $("#formAbonarFactura")[0].reset();
    $("#modalAbonarFactura .is-invalid").removeClass("is-invalid");
    $("#modalAbonarFactura .invalid-feedback").remove();

    saldoFacturaSeleccionadaUsd = parseFloat(f.saldo_pendiente_usd) || 0;
    saldoFacturaSeleccionadaBs = roundNum(saldoFacturaSeleccionadaUsd * tasaBcvActiva, 2);

    $("#abono_cuenta_id").val(f.id);
    $("#abono_tasa_cambio").val(tasaBcvActiva);
    $("#abono_factura_label").text(`#${f.numero_factura} (${f.recepcion_codigo || "Recepción"})`);
    $("#abono_saldo_label").text(`$${formatearNumero(saldoFacturaSeleccionadaUsd)}`);
    $("#abono_saldo_bs_label").text(`Bs. ${formatearNumero(saldoFacturaSeleccionadaBs)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)} Bs/$)`);

    $("#abono_monto").val("");
    $("#abono_moneda").val("USD");
    $("#abono_moneda_sym").text("$");
    $("#abono_referencia").val("");
    $("#abono_observaciones").val("");
    $("#abono_equivalente_label").text("Equivalente: Bs. 0.00");

    poblarSelectsMetodos();
    actualizarComportamientoReferencia("abono");
    recalcularAbonoEspecifico();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAbonarFactura"));
    modal.show();
};

const recalcularAbonoEspecifico = function () {
    const moneda = $("#abono_moneda").val();
    const monto = parseFloat($("#abono_monto").val()) || 0;
    const $sym = $("#abono_moneda_sym");
    const $eqLabel = $("#abono_equivalente_label");

    if (moneda === "USD") {
        $sym.text("$");
        const bs = roundNum(monto * tasaBcvActiva, 2);
        $eqLabel.text(`Equivalente: Bs. ${formatearNumero(bs)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)})`);
    } else {
        $sym.text("Bs");
        const usd = tasaBcvActiva > 0 ? roundNum(monto / tasaBcvActiva, 2) : 0;
        $eqLabel.text(`Equivalente: $${formatearNumero(usd)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)})`);
    }
};

const pagarTotalidadFactura = function () {
    const moneda = $("#abono_moneda").val();
    if (moneda === "USD") {
        $("#abono_monto").val(saldoFacturaSeleccionadaUsd.toFixed(2));
    } else {
        $("#abono_monto").val(saldoFacturaSeleccionadaBs.toFixed(2));
    }
    recalcularAbonoEspecifico();
};

$("#formAbonarFactura").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAbonarFactura,
        isEditar: false,
        modalSelector: "#modalAbonarFactura",
        btnSubmit: "#modalAbonarFacturaBtnGuardar",
        textoGuardarOriginal: "Confirmar Pago",
        onSuccess: function (res) {
            if (res.data && res.data.abono_id) {
                if (window.Swal) {
                    Swal.fire({
                        title: "¡Pago Registrado!",
                        text: res.message || "El pago a la factura del proveedor se procesó con éxito.",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                        cancelButtonText: "Cerrar",
                        confirmButtonColor: "#059669",
                        cancelButtonColor: "#6b7280",
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            abrirVentanaTicket(`${urlBase}/ticket/${res.data.abono_id}`);
                        }
                    });
                }
            }

            recargarCxp();
            if (proveedorActivo) {
                abrirDetalleProveedor(proveedorActivo.id);
            }
        },
    });
});

const abrirModalAbonoGeneral = async function (proveedorId) {
    try {
        const res = await peticionAjax({
            url: `${urlDetalleProveedor}${proveedorId}`,
            type: "GET",
            mensajeCarga: "Consultando facturas del proveedor...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo consultar el proveedor.",
                });
            }
            return;
        }

        proveedorActivo = res.data.proveedor;
        facturasPendientesLista = res.data.facturas_pendientes || [];

        if (facturasPendientesLista.length === 0) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "info",
                    title: "Aviso",
                    text: "Este proveedor no tiene facturas con saldo pendiente.",
                });
            }
            return;
        }

        $("#formAbonoGeneral")[0].reset();
        $("#modalAbonoGeneral .is-invalid").removeClass("is-invalid");
        $("#modalAbonoGeneral .invalid-feedback").remove();

        $("#gen_proveedor_id").val(proveedorActivo.id);
        $("#gen_tasa_cambio").val(tasaBcvActiva);
        $("#gen_proveedor_label").text(`${proveedorActivo.nombre} (${proveedorActivo.rif})`);
        $("#gen_deuda_total_label").text(`$${formatearNumero(proveedorActivo.total_deuda_usd)}`);
        $("#gen_deuda_total_bs_label").text(`Bs. ${formatearNumero(proveedorActivo.total_deuda_bs)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)} Bs/$)`);

        $("#gen_monto").val("");
        $("#gen_moneda").val("USD");
        $("#gen_moneda_sym").text("$");
        $("#gen_referencia").val("");
        $("#gen_observaciones").val("");
        $("#gen_equivalente_label").text("Equivalente: Bs. 0.00");

        poblarSelectsMetodos();
        actualizarComportamientoReferencia("gen");
        recalcularAbonoGeneral();

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAbonoGeneral"));
        modal.show();
    } catch (e) {
        console.error(e);
    }
};

const recalcularAbonoGeneral = function () {
    const moneda = $("#gen_moneda").val();
    const monto = parseFloat($("#gen_monto").val()) || 0;
    const $sym = $("#gen_moneda_sym");
    const $eqLabel = $("#gen_equivalente_label");

    let montoUsd = 0;
    if (moneda === "USD") {
        $sym.text("$");
        montoUsd = monto;
        const bs = roundNum(monto * tasaBcvActiva, 2);
        $eqLabel.text(`Equivalente: Bs. ${formatearNumero(bs)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)})`);
    } else {
        $sym.text("Bs");
        montoUsd = tasaBcvActiva > 0 ? roundNum(monto / tasaBcvActiva, 2) : 0;
        $eqLabel.text(`Equivalente: $${formatearNumero(montoUsd)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)})`);
    }

    simularCascadaFifo(montoUsd);
};

const simularCascadaFifo = function (montoTotalUsd) {
    const $tbody = $("#tabla_cxp_preview_fifo tbody");
    const $badgeResumen = $("#gen_preview_resumen");
    $tbody.empty();

    if (montoTotalUsd <= 0 || facturasPendientesLista.length === 0) {
        $tbody.html('<tr><td colspan="6" class="text-center text-muted py-3">Ingresa un monto para ver la cascada de amortización automática</td></tr>');
        $badgeResumen.text("0 facturas cubiertas");
        return;
    }

    let restanteUsd = montoTotalUsd;
    let cubiertasCompletas = 0;
    let facturasAfectadasCount = 0;

    facturasPendientesLista.forEach(function (f) {
        if (restanteUsd <= 0.001) return;

        const saldoActual = parseFloat(f.saldo_pendiente_usd);
        const aplicar = Math.min(restanteUsd, saldoActual);
        const nuevoSaldo = Math.max(0, saldoActual - aplicar);
        const quedaPagada = nuevoSaldo <= 0.001;

        if (quedaPagada) cubiertasCompletas++;
        facturasAfectadasCount++;

        const tr = `
            <tr class="${quedaPagada ? "table-success bg-opacity-25" : "table-warning bg-opacity-25"}">
                <td class="fw-bold">${f.numero_factura}</td>
                <td>${f.fecha_emision}</td>
                <td class="text-end">$${formatearNumero(saldoActual)}</td>
                <td class="text-end fw-bold text-success">+$${formatearNumero(aplicar)}</td>
                <td class="text-end fw-bold ${quedaPagada ? "text-success" : "text-danger"}">$${formatearNumero(nuevoSaldo)}</td>
                <td class="text-center">
                    ${quedaPagada
                        ? '<span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i>Pagada 100%</span>'
                        : '<span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-adjust me-1"></i>Pago Parcial</span>'}
                </td>
            </tr>
        `;
        $tbody.append(tr);

        restanteUsd -= aplicar;
    });

    $badgeResumen.text(`${facturasAfectadasCount} factura(s) amortizadas (${cubiertasCompletas} al 100%)`);
};

const pagarDeudaTotalGeneral = function () {
    if (!proveedorActivo) return;
    const moneda = $("#gen_moneda").val();
    const deudaUsd = parseFloat(proveedorActivo.total_deuda_usd);

    if (moneda === "USD") {
        $("#gen_monto").val(deudaUsd.toFixed(2));
    } else {
        $("#gen_monto").val((deudaUsd * tasaBcvActiva).toFixed(2));
    }
    recalcularAbonoGeneral();
};

$("#formAbonoGeneral").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAbonarGeneral,
        isEditar: false,
        modalSelector: "#modalAbonoGeneral",
        btnSubmit: "#modalAbonoGeneralBtnGuardar",
        textoGuardarOriginal: "Procesar Pago General",
        onSuccess: function (res) {
            if (res.data && res.data.primer_abono_id) {
                if (window.Swal) {
                    Swal.fire({
                        title: "¡Abono FIFO Aplicado!",
                        text: res.message || "El abono en cascada a facturas del proveedor se procesó exitosamente.",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                        cancelButtonText: "Cerrar",
                        confirmButtonColor: "#059669",
                        cancelButtonColor: "#6b7280",
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            abrirVentanaTicket(`${urlBase}/ticket/${res.data.primer_abono_id}`);
                        }
                    });
                }
            }

            recargarCxp();
        },
    });
});

const abrirHistorialAbonosFactura = function (abonosEncoded, numeroFactura) {
    let abonos = [];
    try {
        abonos = JSON.parse(decodeURIComponent(abonosEncoded));
    } catch (e) {
        console.error(e);
    }

    $("#hist_modal_title").text(`Historial de Pagos - Factura #${numeroFactura}`);
    const $tbody = $("#tbody_historial_abonos");
    $tbody.empty();

    if (!abonos || abonos.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-center text-muted py-3">No hay pagos registrados para esta factura.</td></tr>');
    } else {
        abonos.forEach(function (a) {
            const tr = `
                <tr>
                    <td><i class="far fa-calendar-alt text-muted me-1"></i>${a.fecha}</td>
                    <td><span class="badge bg-white text-dark border rounded-pill px-2 py-1 shadow-xs">${a.metodo_pago}</span></td>
                    <td><small class="text-muted">${a.referencia || "N/A"}</small></td>
                    <td class="text-end fw-bold text-success">+$${formatearNumero(a.monto_usd)}</td>
                    <td class="text-end text-dark">Bs. ${formatearNumero(a.monto_bs)}</td>
                    <td><small class="text-muted"><i class="fas fa-user-edit me-1"></i>${a.usuario || "Sistema"}</small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-dark btn-sm rounded-circle shadow-sm" onclick="abrirVentanaTicket('${urlBase}/ticket/${a.id}');" title="Imprimir Ticket" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(tr);
        });
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalHistorialAbonos"));
    modal.show();
};

const contactarProveedorWhatsApp = function (proveedorId) {
    const prov = proveedoresCache.find(function (p) {
        return p.proveedor_id === proveedorId;
    });

    if (!prov) return;

    let telefono = (prov.proveedor_telefono || "").replace(/\D/g, "");
    if (!telefono) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Sin Teléfono",
                text: "El proveedor no tiene un número telefónico registrado para enviar WhatsApp.",
            });
        }
        return;
    }

    if (telefono.startsWith("0")) {
        telefono = "58" + telefono.substring(1);
    } else if (telefono.length === 10 && (telefono.startsWith("412") || telefono.startsWith("414") || telefono.startsWith("424") || telefono.startsWith("416") || telefono.startsWith("426"))) {
        telefono = "58" + telefono;
    }

    const mensaje = `Hola, estimado(a) *${prov.proveedor_nombre}*. Nos comunicamos para coordinar los pagos correspondientes a nuestras facturas de compra pendientes por un total de *$${formatearNumero(prov.total_deuda_usd)}* (equivalente a *Bs. ${formatearNumero(prov.total_deuda_bs)}* a tasa oficial). ¡Saludos cordiales!`;

    const urlWa = `https://api.whatsapp.com/send?phone=${telefono}&text=${encodeURIComponent(mensaje)}`;
    window.open(urlWa, "_blank");
};

const abrirVentanaTicket = function (url) {
    const w = 420;
    const h = 600;
    const left = screen.width / 2 - w / 2;
    const top = screen.height / 2 - h / 2;
    window.open(url, "TicketAbonoCXP", `width=${w},height=${h},top=${top},left=${left},scrollbars=yes,status=no`);
};

const formatearNumero = function (valor, decimales = 2) {
    const num = parseFloat(valor) || 0;
    return num.toLocaleString("es-VE", {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
};

const roundNum = function (valor, decimales) {
    return Number(Math.round(valor + "e" + decimales) + "e-" + decimales);
};
