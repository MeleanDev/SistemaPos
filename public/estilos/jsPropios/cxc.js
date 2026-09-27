const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlCatalogos = `${urlBase}/catalogos`;
const urlDetalleCliente = `${urlBase}/cliente/`;
const urlAbonarFactura = `${urlBase}/abonar-factura`;
const urlAbonarGeneral = `${urlBase}/abonar-general`;

let metodosPagoList = [];
let tasaBcvActiva = 1.0;
let clienteActivo = null;
let facturasPendientesLista = [];
let saldoFacturaSeleccionadaUsd = 0;
let saldoFacturaSeleccionadaBs = 0;
let filtroEstadoActual = "todos";
let clientesCache = [];
let tablaCxcInstancia = null;

$(document).ready(function () {
    cargarCatalogosCxc();
    inicializarTablaCxc();
    aplicarRestriccionesInput();
});

const cargarCatalogosCxc = async function () {
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
        $(inputId).attr("placeholder", "No requerida para efectivo");
    } else if (nombre.includes("pago móvil") || nombre.includes("pago movil") || nombre.includes("transferencia") || nombre.includes("zelle") || nombre.includes("punto") || nombre.includes("tarjeta")) {
        $(labelId).html('Referencia Bancaria <span class="text-danger">*</span>');
        $(inputId).attr("placeholder", "Ej. # Comprobante / Últimos 4 dígitos");
    } else {
        $(labelId).text("Referencia / Comprobante");
        $(inputId).attr("placeholder", "Ej. #123456");
    }
};

const inicializarTablaCxc = function () {
    recargarCxc();
};

const recargarCxc = async function () {
    try {
        const res = await peticionAjax({
            url: urlLista,
            type: "GET",
            mensajeCarga: "Cargando cartera de clientes...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo cargar la información de cuentas por cobrar.",
                });
            }
            return;
        }

        const kpis = res.data.kpis;
        clientesCache = res.data.clientes || [];

        $("#kpi_total_usd").text(`$${formatearNumero(kpis.total_por_cobrar_usd)}`);
        $("#kpi_total_bs").text(`Bs. ${formatearNumero(kpis.total_por_cobrar_bs)}`);
        $("#kpi_clientes_count").text(kpis.clientes_deudores_count);
        $("#kpi_facturas_count").text(kpis.facturas_pendientes_count);
        $("#kpi_vencidas_count").text(kpis.facturas_vencidas_count);
        $("#kpi_vencidas_monto").text(`$${formatearNumero(kpis.total_vencido_usd)} en mora`);

        if (kpis.tasa_cambio) {
            tasaBcvActiva = parseFloat(kpis.tasa_cambio);
            $("#kpi_tasa_bcv_badge").text(`${formatearNumero(tasaBcvActiva, 2)} Bs/$`);
        }

        renderizarClientesEnTabla();
    } catch (error) {
        console.error(error);
    }
};

const filtrarEstado = function (estado, btn) {
    filtroEstadoActual = estado;
    $("#filtro_estado_cxc button").removeClass("active btn-primary").addClass("text-secondary");
    $(btn).removeClass("text-secondary").addClass("active btn-primary");
    renderizarClientesEnTabla();
};

const renderizarClientesEnTabla = function () {
    if ($.fn.DataTable.isDataTable("#tabla_cxc_clientes")) {
        $("#tabla_cxc_clientes").DataTable().destroy();
    }

    const $tbody = $("#tabla_cxc_clientes tbody");
    $tbody.empty();

    let listaFiltrada = clientesCache;
    if (filtroEstadoActual !== "todos") {
        listaFiltrada = clientesCache.filter(function (c) {
            return c.estado_alerta === filtroEstadoActual;
        });
    }

    listaFiltrada.forEach(function (c) {
        let badgeAlerta = "";
        if (c.estado_alerta === "vencida") {
            badgeAlerta = '<span class="badge bg-danger rounded-pill px-3 py-1 fw-bold"><i class="fas fa-exclamation-circle me-1"></i>Vencida</span>';
        } else if (c.estado_alerta === "por_vencer") {
            badgeAlerta = '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><i class="fas fa-clock me-1"></i>Por Vencer</span>';
        } else {
            badgeAlerta = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i>Al Día</span>';
        }

        const tieneTelefono = c.cliente_telefono && c.cliente_telefono !== "No registrado" && c.cliente_telefono !== "Sin teléfono";
        const telefonoHtml = tieneTelefono
            ? `<span class="small fw-semibold text-dark"><i class="fas fa-phone-alt text-primary me-1 small"></i>${c.cliente_telefono}</span>`
            : '<span class="text-muted small fst-italic"><i class="fas fa-phone-slash me-1"></i>Sin teléfono</span>';

        const botonWhatsApp = tieneTelefono
            ? `<button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="enviarRecordatorioWhatsApp(${c.cliente_id});" title="Notificar por WhatsApp" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fab fa-whatsapp"></i>
               </button>`
            : `<button type="button" class="btn btn-outline-secondary btn-sm rounded-circle shadow-sm opacity-50" disabled title="Sin número de teléfono registrado" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fab fa-whatsapp"></i>
               </button>`;

        const tr = `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2 py-1">
                        <div class="avatar-executive-sm me-1 bg-light-primary text-primary shadow-xs" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5;">
                            <i class="fas fa-user-tag" style="font-size: 0.90rem;"></i>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark text-capitalize" style="font-size: 0.92rem;">${c.cliente_nombre}</span>
                            <div class="d-flex align-items-center gap-1">
                                <span class="badge bg-white text-secondary border rounded-pill px-2 py-0 small">${c.cliente_cedula}</span>
                                <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-0 small text-uppercase">${c.cliente_tipo}</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>${telefonoHtml}</td>
                <td class="text-center">
                    <span class="badge bg-warning bg-opacity-25 text-dark fw-bold rounded-pill px-3 py-1">
                        ${c.total_facturas_pendientes}
                    </span>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${c.factura_mas_antigua_fecha}</div>
                    <small class="text-muted">${c.dias_antiguedad} día(s) transcurridos</small>
                </td>
                <td>${badgeAlerta}</td>
                <td class="text-end fw-bold text-danger fs-6">$${formatearNumero(c.total_deuda_usd)}</td>
                <td class="text-end fw-bold text-dark">Bs. ${formatearNumero(c.total_deuda_bs)}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        ${botonWhatsApp}
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="abrirDetalleCliente(${c.cliente_id});" title="Ver estado de cuenta y facturas" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="abrirModalAbonoGeneral(${c.cliente_id});" title="Abono Rápido FIFO" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-coins"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        $tbody.append(tr);
    });

    const idioma = Object.assign({}, window.CONFIG_POS?.DATATABLE_LENGUAJE_ES || {
        sSearch: "Buscar cliente, cédula...",
        zeroRecords: "No se encontraron clientes con cuentas pendientes",
        emptyTable: "No hay cuentas por cobrar registradas",
        info: "Mostrando _START_ a _END_ de _TOTAL_ clientes",
        oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" },
    });

    tablaCxcInstancia = $("#tabla_cxc_clientes").DataTable({
        responsive: true,
        order: [[5, "desc"]],
        language: idioma,
        pageLength: 10,
    });
};

const abrirDetalleCliente = async function (clienteId) {
    try {
        const res = await peticionAjax({
            url: `${urlDetalleCliente}${clienteId}`,
            type: "GET",
            mensajeCarga: "Consultando estado de cuenta...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo obtener el estado de cuenta del cliente.",
                });
            }
            return;
        }

        const data = res.data;
        clienteActivo = data.cliente;
        facturasPendientesLista = data.facturas_pendientes || [];

        $("#det_cliente_nombre").text(data.cliente.nombre);
        $("#det_cliente_cedula").text(data.cliente.cedula);
        $("#det_cliente_tipo").text(data.cliente.tipo_cliente || "DETAL");
        $("#det_cliente_contacto").text(`Teléfono: ${data.cliente.telefono} | Correo: ${data.cliente.correo} | Dirección: ${data.cliente.direccion}`);
        $("#det_deuda_total_usd").text(`$${formatearNumero(data.cliente.total_deuda_usd)}`);
        $("#det_deuda_total_bs").text(`Bs. ${formatearNumero(data.cliente.total_deuda_bs)}`);
        $("#det_facturas_pendientes_count").text(facturasPendientesLista.length);
        $("#det_limite_credito").text(`$${formatearNumero(data.cliente.limite_credito)}`);
        $("#det_dias_credito").text(`${data.cliente.dias_credito} días de plazo`);
        $("#det_tasa_bcv").text(`${formatearNumero(data.tasa_cambio, 2)} Bs/$`);

        $("#badge_count_pendientes").text(facturasPendientesLista.length);
        $("#badge_count_pagadas").text((data.facturas_pagadas || []).length);

        const tieneTelefono = data.cliente.telefono && data.cliente.telefono !== "No registrado" && data.cliente.telefono !== "Sin teléfono";
        const $btnWa = $("#btn_whatsapp_cliente");
        if (tieneTelefono) {
            $btnWa.removeClass("disabled opacity-50").attr("onclick", `enviarRecordatorioWhatsApp(${data.cliente.id});`).show();
        } else {
            $btnWa.addClass("disabled opacity-50").removeAttr("onclick");
        }

        $("#btn_abono_general_cliente").off("click").on("click", function () {
            const modalDetalle = bootstrap.Modal.getInstance(document.getElementById("modalDetalleCliente"));
            if (modalDetalle) modalDetalle.hide();
            abrirModalAbonoGeneral(clienteId);
        });

        renderizarFacturasPendientesModal(facturasPendientesLista);
        renderizarFacturasPagadasModal(data.facturas_pagadas || []);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalleCliente"));
        modal.show();
    } catch (e) {
        console.error(e);
    }
};

const renderizarFacturasPendientesModal = function (facturas) {
    const $tbody = $("#tbody_facturas_pendientes");
    $tbody.empty();

    if (!facturas || facturas.length === 0) {
        $tbody.html('<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success fs-3 d-block mb-2"></i>El cliente no posee facturas pendientes en este momento.</td></tr>');
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

        const abonosJson = encodeURIComponent(JSON.stringify(f.abonos || []));

        const tr = `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${f.numero_factura}</div>
                    <small class="text-muted">Venta: ${f.venta_codigo}</small>
                </td>
                <td><i class="far fa-calendar-alt text-muted me-1"></i>${f.fecha_emision}</td>
                <td><i class="far fa-calendar-times text-muted me-1"></i>${f.fecha_vencimiento}</td>
                <td>${badgeMora}</td>
                <td class="text-end fw-semibold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
                <td class="text-end fw-semibold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
                <td class="text-end fw-bold text-danger fs-6">
                    <div>$${formatearNumero(f.saldo_pendiente_usd)}</div>
                    <small class="text-muted">Bs. ${formatearNumero(f.saldo_pendiente_bs)}</small>
                </td>
                <td class="text-center pe-4">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonoFactura(${f.id}, '${f.numero_factura}', ${f.saldo_pendiente_usd}, ${f.saldo_pendiente_bs});">
                            <i class="fas fa-hand-holding-usd me-1"></i>Abonar
                        </button>
                        ${f.abonos_count > 0 ? `
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle shadow-sm" onclick="abrirHistorialAbonosFactura('${abonosJson}', '${f.numero_factura}');" title="Ver historial de abonos" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-history"></i>
                            </button>
                        ` : ""}
                    </div>
                </td>
            </tr>
        `;
        $tbody.append(tr);
    });
};

const renderizarFacturasPagadasModal = function (facturas) {
    const $tbody = $("#tbody_facturas_pagadas");
    $tbody.empty();

    if (!facturas || facturas.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-center text-muted py-4">No hay facturas totalmente saldadas en el historial reciente.</td></tr>');
        return;
    }

    facturas.forEach(function (f) {
        const abonosJson = encodeURIComponent(JSON.stringify(f.abonos || []));

        const tr = `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold text-dark">${f.numero_factura}</div>
                    <small class="text-muted">Venta: ${f.venta_codigo}</small>
                </td>
                <td>${f.fecha_emision}</td>
                <td>${f.fecha_vencimiento}</td>
                <td class="text-end fw-semibold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
                <td class="text-end fw-semibold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
                <td class="text-center">
                    <span class="badge bg-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-double me-1"></i>100% Pagada</span>
                </td>
                <td class="text-center pe-4">
                    ${f.abonos && f.abonos.length > 0 ? `
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle shadow-sm" onclick="abrirHistorialAbonosFactura('${abonosJson}', '${f.numero_factura}');" title="Ver abonos" style="width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-history"></i>
                        </button>
                    ` : '<span class="text-muted small">-</span>'}
                </td>
            </tr>
        `;
        $tbody.append(tr);
    });
};

const abrirModalAbonoFactura = function (cuentaId, numeroFactura, saldoUsd, saldoBs) {
    saldoFacturaSeleccionadaUsd = parseFloat(saldoUsd);
    saldoFacturaSeleccionadaBs = parseFloat(saldoBs);

    $("#formAbonarFactura")[0].reset();
    $("#modalAbonarFactura .is-invalid").removeClass("is-invalid");
    $("#modalAbonarFactura .invalid-feedback").remove();

    $("#abono_cuenta_id").val(cuentaId);
    $("#abono_tasa_cambio").val(tasaBcvActiva);
    $("#abono_factura_label").text(`Factura #${numeroFactura}`);
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
        $("#abono_monto").val((saldoFacturaSeleccionadaUsd * tasaBcvActiva).toFixed(2));
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
        textoGuardarOriginal: "Confirmar Abono",
        onSuccess: function (res) {
            if (res.data && res.data.abono_id) {
                if (window.Swal) {
                    Swal.fire({
                        title: "¡Abono Registrado!",
                        text: res.message || "El abono fue procesado exitosamente.",
                        icon: "success",
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                        cancelButtonText: "Cerrar",
                        confirmButtonColor: "#4f46e5",
                        cancelButtonColor: "#6b7280",
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            abrirVentanaTicket(`${urlBase}/ticket/${res.data.abono_id}`);
                        }
                    });
                }
            }

            recargarCxc();
            if (clienteActivo) {
                abrirDetalleCliente(clienteActivo.id);
            }
        },
    });
});

const abrirModalAbonoGeneral = async function (clienteId) {
    try {
        const res = await peticionAjax({
            url: `${urlDetalleCliente}${clienteId}`,
            type: "GET",
            mensajeCarga: "Consultando deuda consolidada...",
        });

        if (!res || !res.success) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res?.message || "No se pudo consultar el cliente.",
                });
            }
            return;
        }

        clienteActivo = res.data.cliente;
        facturasPendientesLista = res.data.facturas_pendientes || [];

        if (facturasPendientesLista.length === 0) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "info",
                    title: "Aviso",
                    text: "Este cliente no tiene facturas con saldo pendiente.",
                });
            }
            return;
        }

        $("#formAbonoGeneral")[0].reset();
        $("#modalAbonoGeneral .is-invalid").removeClass("is-invalid");
        $("#modalAbonoGeneral .invalid-feedback").remove();

        $("#gen_cliente_id").val(clienteActivo.id);
        $("#gen_tasa_cambio").val(tasaBcvActiva);
        $("#gen_cliente_label").text(`${clienteActivo.nombre} (${clienteActivo.cedula})`);
        $("#gen_deuda_total_label").text(`$${formatearNumero(clienteActivo.total_deuda_usd)}`);
        $("#gen_deuda_total_bs_label").text(`Bs. ${formatearNumero(clienteActivo.total_deuda_bs)} (Tasa: ${formatearNumero(tasaBcvActiva, 2)} Bs/$)`);

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
    const $tbody = $("#tbody_preview_fifo");
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
            <tr class="${quedaPagada ? 'table-success bg-opacity-25' : 'table-warning bg-opacity-25'}">
                <td class="fw-bold">${f.numero_factura}</td>
                <td>${f.fecha_emision}</td>
                <td class="text-end">$${formatearNumero(saldoActual)}</td>
                <td class="text-end fw-bold text-success">+$${formatearNumero(aplicar)}</td>
                <td class="text-end fw-bold ${quedaPagada ? 'text-success' : 'text-danger'}">$${formatearNumero(nuevoSaldo)}</td>
                <td class="text-center">
                    ${quedaPagada
                        ? '<span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i>Pagada 100%</span>'
                        : '<span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-adjust me-1"></i>Abono Parcial</span>'}
                </td>
            </tr>
        `;
        $tbody.append(tr);

        restanteUsd -= aplicar;
    });

    $badgeResumen.text(`${facturasAfectadasCount} factura(s) amortizadas (${cubiertasCompletas} al 100%)`);
};

const pagarDeudaTotalGeneral = function () {
    if (!clienteActivo) return;
    const moneda = $("#gen_moneda").val();
    const deudaUsd = parseFloat(clienteActivo.total_deuda_usd);

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
        textoGuardarOriginal: "Procesar Abono General",
        onSuccess: function (res) {
            if (res.data && res.data.primer_abono_id) {
                if (window.Swal) {
                    Swal.fire({
                        title: "¡Abono FIFO Aplicado!",
                        text: res.message || "El abono en cascada se procesó exitosamente.",
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

            recargarCxc();
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

    $("#hist_modal_title").text(`Historial de Abonos - Factura #${numeroFactura}`);
    const $tbody = $("#tbody_historial_abonos");
    $tbody.empty();

    if (!abonos || abonos.length === 0) {
        $tbody.html('<tr><td colspan="7" class="text-center text-muted py-3">No hay abonos registrados para esta factura.</td></tr>');
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

const enviarRecordatorioWhatsApp = function (clienteId) {
    const cliente = clientesCache.find(function (c) {
        return c.cliente_id === clienteId;
    });

    if (!cliente) return;

    let telefono = (cliente.cliente_telefono || "").replace(/\D/g, "");
    if (!telefono) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Sin Teléfono",
                text: "El cliente no tiene un número telefónico registrado para enviar WhatsApp.",
            });
        }
        return;
    }

    if (telefono.startsWith("0")) {
        telefono = "58" + telefono.substring(1);
    } else if (telefono.length === 10 && (telefono.startsWith("412") || telefono.startsWith("414") || telefono.startsWith("424") || telefono.startsWith("416") || telefono.startsWith("426"))) {
        telefono = "58" + telefono;
    }

    const mensaje = `Hola, estimado(a) *${cliente.cliente_nombre}*. Le contactamos para informarle que presenta un saldo pendiente de *$${formatearNumero(cliente.total_deuda_usd)}* (equivalente a *Bs. ${formatearNumero(cliente.total_deuda_bs)}* a tasa oficial). Agradecemos coordinar su abono o pago a la brevedad. ¡Muchas gracias!`;

    const urlWa = `https://api.whatsapp.com/send?phone=${telefono}&text=${encodeURIComponent(mensaje)}`;
    window.open(urlWa, "_blank");
};

const abrirVentanaTicket = function (url) {
    const w = 420;
    const h = 600;
    const left = screen.width / 2 - w / 2;
    const top = screen.height / 2 - h / 2;
    window.open(url, "TicketAbonoCXC", `width=${w},height=${h},top=${top},left=${left},scrollbars=yes,status=no`);
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
