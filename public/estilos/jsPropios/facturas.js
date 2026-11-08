let datatableFacturas = null;
let turnosAbiertosFacturas = [];
let turnoPropioFacturaId = null;

const formatearFechaLegal = function (fechaStr) {
    if (!fechaStr) {
        return "--/--/----";
    }
    const str = String(fechaStr).trim();
    const match = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
        const anio = match[1];
        const mes = match[2];
        const dia = match[3];
        return `${dia}/${mes}/${anio}`;
    }
    const matchInv = str.match(/^(\d{2})[\/-](\d{2})[\/-](\d{4})/);
    if (matchInv) {
        return `${matchInv[1]}/${matchInv[2]}/${matchInv[3]}`;
    }
    const d = new Date(fechaStr);
    if (!isNaN(d.getTime())) {
        const dia = String(d.getUTCDate()).padStart(2, "0");
        const mes = String(d.getUTCMonth() + 1).padStart(2, "0");
        const anio = d.getUTCFullYear();
        return `${dia}/${mes}/${anio}`;
    }
    return fechaStr;
};

const formatearHoraLegal = function (horaStr) {
    if (!horaStr || horaStr === "--") {
        return "--:--";
    }
    const str = String(horaStr).trim();
    const match = str.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?/);
    if (match) {
        let horas = parseInt(match[1], 10);
        const minutos = match[2];
        const segundos = match[3];
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12;
        horas = horas ? horas : 12;
        const horasPad = String(horas).padStart(2, "0");
        const segStr = segundos !== undefined ? `:${segundos}` : "";
        return `${horasPad}:${minutos}${segStr} ${ampm}`;
    }
    return horaStr;
};

const obtenerFechaHoy = function () {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, "0");
    const dia = String(hoy.getDate()).padStart(2, "0");
    return `${anio}-${mes}-${dia}`;
};

const validarFiltroFechas = function () {
    const hoy = obtenerFechaHoy();
    const $inicio = $("#filtroFechaInicio");
    const $fin = $("#filtroFechaFin");

    let fechaInicio = $inicio.val();
    let fechaFin = $fin.val();

    if (fechaInicio && fechaInicio > hoy) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Fecha no permitida",
                text: "La fecha inicial no puede ser posterior a la fecha actual."
            });
        }
        $inicio.val(hoy);
        fechaInicio = hoy;
    }

    if (fechaFin && fechaFin > hoy) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Fecha no permitida",
                text: "La fecha final no puede ser posterior a la fecha actual."
            });
        }
        $fin.val(hoy);
        fechaFin = hoy;
    }

    if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Rango inválido",
                text: "La fecha 'Desde' no puede ser mayor que la fecha 'Hasta'."
            });
        }
        $fin.val(fechaInicio);
        fechaFin = fechaInicio;
    }

    if (fechaInicio) {
        $fin.attr("min", fechaInicio);
    } else {
        $fin.removeAttr("min");
    }

    if (fechaFin) {
        $inicio.attr("max", fechaFin > hoy ? hoy : fechaFin);
    } else {
        $inicio.attr("max", hoy);
    }
    $fin.attr("max", hoy);

    return true;
};

$(document).ready(function () {
    const hoy = obtenerFechaHoy();
    $("#filtroFechaInicio").attr("max", hoy);
    $("#filtroFechaFin").attr("max", hoy);

    $("#filtroFechaInicio, #filtroFechaFin").on("change blur", function () {
        validarFiltroFechas();
    });

    inicializarTablaFacturas();
    cargarKpisFacturas();

    $("#formAnularFactura").on("submit", function (e) {
        e.preventDefault();
        const id = $("#anular_factura_id").val();
        if (!id) {
            return;
        }
        enviarFormulario({
            form: this,
            url: `${urlFacturasDetalle}/${id}/anular`,
            isEditar: false,
            modalSelector: "#modalAnularFactura",
            tablaSelector: "#datatable_facturas",
            btnSubmit: "#modalAnularFacturaBtnGuardar",
            textoGuardarOriginal: "Confirmar Anulación",
            onSuccess: function () {
                cargarKpisFacturas();
            }
        });
    });

    $("#formDevolverFactura").on("submit", function (e) {
        e.preventDefault();
        const id = $("#devolver_factura_id").val();
        if (!id) {
            return;
        }

        const motivo = $("#devolver_motivo").val().trim();
        if (!motivo) {
            if (window.notificacion) {
                window.notificacion.fire({ icon: "warning", title: "Motivo requerido", text: "Por favor ingresa el motivo de la devolución." });
            }
            return;
        }

        const items = [];
        $(".input-cant-devolver-row").each(function () {
            const cant = parseFloat($(this).val()) || 0;
            const detId = parseInt($(this).data("id"), 10) || 0;
            if (cant > 0 && detId > 0) {
                items.push({
                    venta_detalle_id: detId,
                    cantidad: cant
                });
            }
        });

        if (items.length === 0) {
            if (window.notificacion) {
                window.notificacion.fire({ icon: "warning", title: "Sin cantidades", text: "Debes ingresar al menos una cantidad mayor a 0 para devolver." });
            }
            return;
        }

        const turnoId = $("#devolver_caja_turno_id").val() || null;
        const payload = {
            venta_id: parseInt(id, 10),
            motivo: motivo,
            tipo_reembolso: "sin_reembolso",
            items: items
        };
        if (turnoId) {
            payload.caja_turno_id = parseInt(turnoId, 10);
        }

        const $btn = $("#modalDevolverFacturaBtnGuardar");
        $btn.prop("disabled", true);

        peticionAjax({
            url: `${urlFacturasDetalle}/${id}/devolver`,
            method: "POST",
            data: payload
        }).then(function (res) {
            $btn.prop("disabled", false);
            if (res && res.success) {
                const modalEl = document.getElementById("modalDevolverFactura");
                if (modalEl) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                }
                if (window.Swal) {
                    Swal.fire({
                        icon: "success",
                        title: "Devolución Procesada",
                        text: res.message
                    });
                }
                recargarTablaFacturas();
            }
        }).catch(function (xhr) {
            $btn.prop("disabled", false);
            const msg = xhr.responseJSON?.message || "Error al procesar la devolución.";
            if (window.notificacion) {
                window.notificacion.fire({ icon: "error", title: "Error", text: msg });
            }
        });
    });
});

const inicializarTablaFacturas = function () {
    datatableFacturas = crearDataTable({
        selector: "#datatable_facturas",
        url: urlFacturasLista,
        responsive: true,
        dataExtra: function (d) {
            d.fecha_inicio = $("#filtroFechaInicio").val();
            d.fecha_fin = $("#filtroFechaFin").val();
            d.condicion_pago = $("#filtroCondicion").val();
            d.estado = $("#filtroEstado").val();
        },
        columns: [
            {
                data: "codigo",
                name: "codigo",
                render: function (data, type, row) {
                    const controlHtml = row.numero_control
                        ? `<div class="d-inline-flex align-items-center gap-1 font-monospace text-secondary text-nowrap mt-0.5" style="font-size: 0.73rem;"><i class="fas fa-barcode text-muted"></i> <span>Ctrl: <strong class="text-dark">${row.numero_control}</strong></span></div>`
                        : "";
                    return `
                        <div class="d-inline-flex flex-column text-nowrap align-items-start" style="gap: 2px;">
                            <span class="badge rounded-pill bg-primary-subtle text-primary font-monospace fw-bold px-2.5 py-1" style="font-size: 0.84rem;">
                                <i class="fas fa-receipt me-1"></i>${data}
                            </span>
                            ${controlHtml}
                        </div>
                    `;
                }
            },
            {
                data: "fecha_emision",
                name: "fecha_emision",
                render: function (data, type, row) {
                    const fechaFormateada = formatearFechaLegal(data);
                    const horaFormateada = formatearHoraLegal(row.hora_emision);
                    return `
                        <div class="d-flex flex-column text-nowrap" style="gap: 2px;">
                            <div class="d-inline-flex align-items-center gap-1.5">
                                <i class="far fa-calendar-alt text-primary" style="font-size: 0.8rem;"></i>
                                <span class="text-dark font-monospace fw-bold" style="font-size: 0.84rem; letter-spacing: 0.2px;">${fechaFormateada}</span>
                            </div>
                            <div class="d-inline-flex align-items-center gap-1 text-muted" style="font-size: 0.73rem;">
                                <i class="far fa-clock text-secondary" style="font-size: 0.7rem;"></i>
                                <span class="font-monospace">${horaFormateada}</span>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: "cliente",
                name: "cliente.nombre",
                render: function (data, type, row) {
                    const cliNombre = data ? `${data.nombre} ${data.apellido || ''}`.trim() : "Consumidor Final";
                    const cliCedula = data ? data.cedula : "V-00000000";
                    return `
                        <div>
                            <strong class="text-dark font-monospace d-block" style="font-size: 0.88rem;">${cliNombre}</strong>
                            <span class="d-inline-flex align-items-center font-monospace text-secondary" style="font-size: 0.74rem;"><i class="far fa-id-card me-1 text-primary"></i>${cliCedula}</span>
                        </div>
                    `;
                }
            },
            {
                data: "vendedor",
                name: "vendedor.nombre",
                render: function (data, type, row) {
                    if (!data) {
                        return '<span class="text-muted small fst-italic"><i class="fas fa-minus me-1 opacity-50"></i>Sin asignar</span>';
                    }
                    const comisionStr = parseFloat(row.comision_monto_usd || 0) > 0 ? ` <small class="text-success fw-bold">($${parseFloat(row.comision_monto_usd).toFixed(2)})</small>` : '';
                    return `
                        <div>
                            <strong class="text-dark font-monospace d-block" style="font-size: 0.85rem;"><i class="fas fa-user-tie text-primary me-1"></i>${data.nombre}</strong>
                            <small class="text-muted font-monospace">${data.tipo_documento ? `${data.tipo_documento}-${data.documento || ''}` : (data.documento || '')}${comisionStr}</small>
                        </div>
                    `;
                }
            },
            {
                data: "almacen.nombre",
                name: "almacen.nombre",
                render: function (data, type, row) {
                    const nombreAlm = data || "General";
                    const cajaNom = row.caja ? row.caja.nombre : "Caja Principal";
                    return `
                        <div class="d-flex flex-column text-nowrap" style="gap: 2px;">
                            <div class="d-inline-flex align-items-center gap-1.5">
                                <i class="fas fa-warehouse text-primary" style="font-size: 0.8rem;"></i>
                                <span class="font-monospace fw-semibold text-dark" style="font-size: 0.83rem;">${nombreAlm}</span>
                            </div>
                            <div class="d-inline-flex align-items-center gap-1 text-muted" style="font-size: 0.74rem;">
                                <i class="fas fa-cash-register text-secondary" style="font-size: 0.72rem;"></i>
                                <span class="font-monospace">${cajaNom}</span>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: "condicion_pago",
                name: "condicion_pago",
                render: function (data, type, row) {
                    const esCredito = data === "credito";
                    const badgeCond = esCredito
                        ? '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis font-monospace px-2 py-0.5">Crédito (CXC)</span>'
                        : '<span class="badge rounded-pill bg-success-subtle text-success font-monospace px-2 py-0.5">Contado</span>';
                    const tipoVenta = row.tipo_venta === "mayor" ? "Mayor" : "Detal";
                    return `<div class="text-nowrap">${badgeCond}<small class="text-muted font-monospace d-block mt-0.5">${tipoVenta}</small></div>`;
                }
            },
            {
                data: "total_usd",
                name: "total_usd",
                className: "text-end font-monospace",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.total_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `<div><strong class="text-dark d-block" style="font-size: 0.92rem;">$ ${usd}</strong><small class="badge rounded-pill bg-success-subtle text-success px-2 py-0.5 fw-bold" style="font-size: 0.74rem;">Bs. ${bs}</small></div>`;
                }
            },
            {
                data: "monto_pagado_usd",
                name: "monto_pagado_usd",
                className: "text-end font-monospace",
                render: function (data, type, row) {
                    const pagadoUsd = parseFloat(data || 0).toFixed(2);
                    const saldoUsd = parseFloat(row.saldo_pendiente_usd || 0).toFixed(2);
                    if (parseFloat(saldoUsd) > 0) {
                        return `<div><span class="text-success small d-block">Pagado: $ ${pagadoUsd}</span><strong class="text-danger small d-block">Resta: $ ${saldoUsd}</strong></div>`;
                    }
                    return `<span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i>Saldado ($ ${pagadoUsd})</span>`;
                }
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center",
                render: function (data) {
                    switch (data) {
                        case "completada":
                            return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1 font-monospace"><i class="fas fa-check-circle me-1"></i>Completada</span>';
                        case "devuelta_parcial":
                            return '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 font-monospace"><i class="fas fa-undo-alt me-1"></i>Devuelta Parcial</span>';
                        case "devuelta_total":
                            return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 font-monospace"><i class="fas fa-ban me-1"></i>Devuelta Total</span>';
                        case "anulada":
                            return '<span class="badge rounded-pill bg-secondary-subtle text-secondary px-2.5 py-1 font-monospace"><i class="fas fa-times-circle me-1"></i>Anulada</span>';
                        default:
                            return `<span class="badge rounded-pill bg-primary-subtle text-primary px-2.5 py-1 font-monospace">${data}</span>`;
                    }
                }
            },
            {
                data: "id",
                name: "id",
                className: "text-end",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    const puedeAccionar = typeof puedeAnularFactura !== "undefined"
                        && puedeAnularFactura
                        && (row.estado === "completada" || row.estado === "devuelta_parcial");
                    const btnDevolverHtml = puedeAccionar
                        ? `<button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-2.5 py-1 shadow-xs" onclick="abrirModalDevolverFactura(${data})" title="Devolver Ítems"><i class="fas fa-undo"></i></button>`
                        : "";
                    const btnAnularHtml = puedeAccionar
                        ? `<button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 shadow-xs" onclick="abrirModalAnularFactura(${data}, '${row.codigo}', ${parseFloat(row.total_usd || 0)})" title="Anular Factura"><i class="fas fa-ban"></i></button>`
                        : "";
                    return `
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5 py-1 shadow-xs" onclick="verDetalleFactura(${data})" title="Ver Detalle 360°">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1 shadow-xs" onclick="imprimirCartaFactura(${data})" title="Factura Carta (Hoja Blanca)">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1 shadow-xs" onclick="imprimirTicketFactura(${data})" title="Ticket Térmico (Tiquera)">
                                <i class="fas fa-receipt"></i>
                            </button>
                            ${btnDevolverHtml}
                            ${btnAnularHtml}
                        </div>
                    `;
                }
            }
        ]
    });
};

const cargarKpisFacturas = async function () {
    try {
        const res = await peticionAjax({
            url: urlFacturasKpis,
            method: "GET"
        });

        if (res && res.success && res.data) {
            const d = res.data;
            $("#kpiTotalUsd").text(`$ ${parseFloat(d.total_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiTotalBs").text(`Bs. ${parseFloat(d.total_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiTotalHoyUsd").text(`$ ${parseFloat(d.total_hoy_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiTotalHoyBs").text(`Bs. ${parseFloat(d.total_hoy_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiTotalCreditoUsd").text(`$ ${parseFloat(d.total_credito_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiTotalCreditoBs").text(`Bs. ${parseFloat(d.total_credito_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiConteoTotal").text(d.conteo_total || 0);
            $("#kpiConteoHoy").text(d.conteo_hoy || 0);
            $("#kpiConteoCredito").text(d.conteo_credito || 0);
            $("#kpiConteoDevueltas").text(d.conteo_devueltas || 0);
            turnosAbiertosFacturas = Array.isArray(d.turnos_abiertos) ? d.turnos_abiertos : [];
            turnoPropioFacturaId = d.caja_turno_propio_id || null;
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los indicadores de facturación."
            });
        }
    }
};

const recargarTablaFacturas = function () {
    validarFiltroFechas();
    if (datatableFacturas) {
        datatableFacturas.ajax.reload(null, false);
    }
    cargarKpisFacturas();
};

const limpiarFiltrosFacturas = function () {
    const hoy = obtenerFechaHoy();
    $("#filtroFechaInicio").val("").attr("max", hoy).removeAttr("min");
    $("#filtroFechaFin").val("").attr("max", hoy).removeAttr("min");
    $("#filtroCondicion").val("");
    $("#filtroEstado").val("");
    recargarTablaFacturas();
};

const verDetalleFactura = async function (id) {
    try {
        const res = await consultarRegistro(urlFacturasDetalle, id);

        if (res && res.success && res.data) {
            const v = res.data;
            $("#modalDetalleFacturaSubtitulo").text(`Comprobante #${v.codigo}`);
            $("#modalDetalleFacturaTitulo").text(`Detalle de Factura #${v.codigo}`);
            $("#btnImprimirCartaModalDetalle").attr("onclick", `imprimirCartaFactura(${v.id})`);
            $("#btnReimprimirModalDetalle").attr("onclick", `imprimirTicketFactura(${v.id})`);
            $("#btnImprimirGarantiaModalDetalle").attr("onclick", `imprimirGarantiaFactura(${v.id})`);

            if ($("#btnDevolverModalDetalle").length) {
                if (typeof puedeAnularFactura !== "undefined" && puedeAnularFactura && (v.estado === "completada" || v.estado === "devuelta_parcial")) {
                    $("#btnDevolverModalDetalle")
                        .removeClass("d-none")
                        .attr("onclick", `abrirModalDevolverFactura(${v.id})`);
                } else {
                    $("#btnDevolverModalDetalle").addClass("d-none").removeAttr("onclick");
                }
            }

            if ($("#btnAnularModalDetalle").length) {
                if (typeof puedeAnularFactura !== "undefined" && puedeAnularFactura && (v.estado === "completada" || v.estado === "devuelta_parcial")) {
                    $("#btnAnularModalDetalle")
                        .removeClass("d-none")
                        .attr("onclick", `abrirModalAnularFactura(${v.id}, '${v.codigo}', ${parseFloat(v.total_usd || 0)})`);
                } else {
                    $("#btnAnularModalDetalle").addClass("d-none").removeAttr("onclick");
                }
            }

            const cliNombre = v.cliente ? `${v.cliente.nombre} ${v.cliente.apellido || ''}`.trim() : "Consumidor Final";
            const cliCedula = v.cliente ? v.cliente.cedula : "V-00000000";
            const cliTelefono = v.cliente ? (v.cliente.telefono || "Sin teléfono") : "Sin teléfono";
            const cliDireccion = v.cliente ? (v.cliente.direccion || "Sin dirección registrada") : "Sin dirección";

            const selloAnuladaHtml = v.estado === "anulada" ? `
                <div class="alert bg-danger-subtle border border-danger-subtle text-danger rounded-4 py-2.5 px-3 font-monospace small mb-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-ban fs-5"></i>
                        <div>
                            <strong>FACTURA ANULADA</strong>
                            <div class="small">Motivo: ${v.anulacion_motivo || 'Anulación de comprobante'}</div>
                        </div>
                    </div>
                    <div class="text-end small">
                        <span>Por: <strong>${v.anulada_por ? (v.anulada_por.name || v.anulada_por.nombre || 'Usuario') : 'Sistema'}</strong></span>
                        <div class="text-muted">${formatearFechaLegal(v.anulada_en || v.updated_at)}</div>
                    </div>
                </div>
            ` : "";

            let renglonesHtml = "";
            if (Array.isArray(v.detalles)) {
                v.detalles.forEach((det, idx) => {
                    const itemNombre = det.nombre_item || (det.producto ? det.producto.nombre : (det.moto ? `${det.moto.marca} ${det.moto.modelo}` : (det.servicio ? det.servicio.nombre : "Ítem")));
                    const serialBadge = det.serial_identificador ? `<span class="badge bg-primary-subtle text-primary font-monospace ms-1">#${det.serial_identificador}</span>` : "";

                    let tipoBadge = "";
                    if (det.tipo_item === "moto") {
                        tipoBadge = '<span class="badge rounded-pill bg-info-subtle text-info font-monospace px-2 py-0.5"><i class="fas fa-motorcycle me-1"></i>Moto</span>';
                    } else if (det.tipo_item === "servicio") {
                        tipoBadge = '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis font-monospace px-2 py-0.5"><i class="fas fa-wrench me-1"></i>Servicio</span>';
                    } else {
                        tipoBadge = '<span class="badge rounded-pill bg-primary-subtle text-primary font-monospace px-2 py-0.5"><i class="fas fa-box me-1"></i>Producto</span>';
                    }

                    renglonesHtml += `
                        <tr>
                            <td class="font-monospace text-center small text-secondary">${idx + 1}</td>
                            <td>
                                <strong class="text-dark font-monospace d-block">${itemNombre} ${serialBadge}</strong>
                                <small class="text-secondary font-monospace d-inline-flex align-items-center gap-1.5 mt-0.5">${tipoBadge} <span>•</span> <i class="fas fa-warehouse text-primary"></i> <span class="text-dark fw-semibold">${det.almacen?.nombre || 'General'}</span></small>
                            </td>
                            <td class="text-center font-monospace fw-bold text-dark">${parseFloat(det.cantidad).toLocaleString()}</td>
                            <td class="text-end font-monospace text-dark">$ ${parseFloat(det.precio_unitario_usd).toFixed(2)}</td>
                            <td class="text-center font-monospace small"><span class="badge rounded-pill ${det.aplica_iva ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success'} px-2 py-0.5">${det.aplica_iva ? `IVA ${parseFloat(det.iva_porcentaje)}%` : 'Exento'}</span></td>
                            <td class="text-end font-monospace fw-bold text-dark">$ ${parseFloat(det.subtotal_usd).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }

            let pagosHtml = "";
            if (Array.isArray(v.pagos) && v.pagos.length > 0) {
                v.pagos.forEach((p) => {
                    const metodoNombre = p.metodo_pago ? p.metodo_pago.nombre : "Pago";
                    pagosHtml += `
                        <div class="d-flex align-items-center justify-content-between p-2.5 border rounded-3 bg-white mb-2 font-monospace small shadow-xs">
                            <div>
                                <strong class="text-dark">${metodoNombre}</strong>
                                ${p.referencia ? `<small class="text-secondary ms-1.5">(Ref: ${p.referencia})</small>` : ''}
                            </div>
                            <div>
                                <span class="badge ${p.moneda === 'VES' ? 'bg-info-subtle text-info' : 'bg-success-subtle text-success'} rounded-pill me-1.5 px-2 py-0.5">${p.moneda}</span>
                                <strong class="text-success">$ ${parseFloat(p.monto_usd).toFixed(2)}</strong>
                                <small class="text-secondary ms-1.5 fw-semibold">(Bs. ${parseFloat(p.monto_bs).toFixed(2)})</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                pagosHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">Facturado 100% a Crédito (Sin cobro en caja).</div>`;
            }

            let devolucionesHtml = "";
            if (Array.isArray(v.devoluciones) && v.devoluciones.length > 0) {
                let devFilas = "";
                v.devoluciones.forEach(dev => {
                    let itemsStr = "";
                    if (Array.isArray(dev.detalles)) {
                        itemsStr = dev.detalles.map(dd => `${dd.nombre_item || (dd.producto ? dd.producto.nombre : 'Ítem')} (x${parseFloat(dd.cantidad)})`).join(", ");
                    }
                    devFilas += `
                        <tr>
                            <td><span class="badge rounded-pill bg-warning-subtle text-warning-emphasis fw-bold">${dev.codigo}</span></td>
                            <td>${formatearFechaLegal(dev.fecha_devolucion || dev.created_at)}</td>
                            <td><span class="text-truncate d-inline-block" style="max-width: 250px;" title="${itemsStr}">${itemsStr || 'Ítems devueltos'}</span></td>
                            <td class="text-end fw-bold text-danger">$ ${parseFloat(dev.total_usd || 0).toFixed(2)}</td>
                            <td>${dev.motivo || 'Devolución de cliente'}</td>
                        </tr>
                    `;
                });
                devolucionesHtml = `
                    <div class="table-responsive bg-white rounded-3 border">
                        <table class="table table-sm table-hover align-middle mb-0 font-monospace small">
                            <thead class="bg-warning bg-opacity-10 text-dark">
                                <tr>
                                    <th>Comprobante Dev.</th>
                                    <th>Fecha</th>
                                    <th>Ítems Devueltos</th>
                                    <th class="text-end">Monto Reintegrado</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>${devFilas}</tbody>
                        </table>
                    </div>
                `;
            } else if (v.estado === "anulada") {
                devolucionesHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">Factura anulada en su totalidad con reintegro de stock y reversión contable.</div>`;
            } else {
                devolucionesHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">No se registran devoluciones ni anulaciones para este comprobante.</div>`;
            }

            let cxcHtml = "";
            if (v.cuenta_por_cobrar) {
                const cxc = v.cuenta_por_cobrar;
                let filasAbonos = "";
                if (Array.isArray(cxc.abonos) && cxc.abonos.length > 0) {
                    cxc.abonos.forEach(ab => {
                        filasAbonos += `
                            <tr>
                                <td>${formatearFechaLegal(ab.fecha_abono || ab.created_at)}</td>
                                <td><span class="badge rounded-pill bg-primary-subtle text-primary">${ab.metodo_pago ? ab.metodo_pago.nombre : 'Abono'}</span></td>
                                <td>${ab.referencia || '--'}</td>
                                <td class="text-end fw-bold text-success">$ ${parseFloat(ab.monto_usd || 0).toFixed(2)}</td>
                                <td class="text-end fw-semibold text-secondary">Bs. ${parseFloat(ab.monto_bs || 0).toFixed(2)}</td>
                                <td class="text-center">
                                    <a href="/cxc/abonos/${ab.id}/ticket" target="_blank" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-0.5" title="Ticket de Abono"><i class="fas fa-print"></i></a>
                                </td>
                            </tr>
                        `;
                    });
                } else {
                    filasAbonos = `<tr><td colspan="6" class="text-center text-muted py-2">Sin abonos registrados aún en esta cuenta.</td></tr>`;
                }

                cxcHtml = `
                    <div class="card border rounded-4 p-3 bg-warning-subtle text-warning-emphasis shadow-xs mb-3 font-monospace">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom border-warning pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0"><i class="fas fa-hand-holding-usd me-1"></i> Crédito Registrado (CXC #${cxc.numero_factura || cxc.id})</h6>
                                <small>Vencimiento: ${formatearFechaLegal(cxc.fecha_vencimiento)}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold fs-6">Saldo Deudor: $ ${parseFloat(cxc.saldo_pendiente_usd).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="small mb-2">
                            <span>Total Crédito: <strong>$ ${parseFloat(cxc.monto_total_usd).toFixed(2)}</strong></span> • 
                            <span>Total Abonado: <strong>$ ${parseFloat(cxc.monto_pagado_usd).toFixed(2)}</strong></span> • 
                            <span>Estado: <strong>${cxc.estado}</strong></span>
                        </div>
                        <div class="table-responsive bg-white rounded-3 border">
                            <table class="table table-sm table-hover align-middle mb-0 font-monospace small">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Método</th>
                                        <th>Referencia</th>
                                        <th class="text-end">Monto USD</th>
                                        <th class="text-end">Monto Bs</th>
                                        <th class="text-center">Ticket</th>
                                    </tr>
                                </thead>
                                <tbody>${filasAbonos}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            } else {
                cxcHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">Venta pagada de contado. No generó cuenta por cobrar.</div>`;
            }

            let comisionHtml = "";
            if (v.vendedor || parseFloat(v.comision_monto_usd || 0) > 0) {
                comisionHtml = `
                    <div class="card border rounded-4 p-3 bg-white shadow-xs font-monospace small">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                            <span class="text-secondary fw-semibold">Asesor / Vendedor:</span>
                            <strong class="text-dark"><i class="fas fa-user-tie text-primary me-1"></i>${v.vendedor ? v.vendedor.nombre : 'Vendedor'}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Porcentaje Comisión:</span>
                            <strong class="text-dark">${parseFloat(v.comision_porcentaje || 0).toFixed(2)}%</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Base Imponible:</span>
                            <strong class="text-dark">$ ${parseFloat(v.subtotal_neto_usd || 0).toFixed(2)}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark">Comisión Ganada:</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold fs-6">$ ${parseFloat(v.comision_monto_usd || 0).toFixed(2)}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <span class="text-muted">Estado Liquidación:</span>
                            <span class="badge rounded-pill ${v.comision_estado === 'liquidada' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning-emphasis'}">${v.comision_estado || 'pendiente'}</span>
                        </div>
                    </div>
                `;
            } else {
                comisionHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">Venta directa sin comisión asignada a vendedores.</div>`;
            }

            $("#contenidoDetalleFactura").html(`
                ${selloAnuladaHtml}

                <div class="card border rounded-4 p-3 bg-white shadow-xs mb-3">
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-dark mb-2.5"><i class="fas fa-receipt text-primary me-1"></i> Información de Comprobante</h6>
                            <div class="font-monospace small">
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Número Factura:</span>
                                    <strong class="text-dark">${v.codigo}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">N° Control Fiscal:</span>
                                    <span class="badge rounded-pill bg-danger-subtle text-danger font-monospace fw-bold px-2.5 py-0.5">${v.numero_control || 'N/A'}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Fecha & Hora:</span>
                                    <strong class="text-dark">${formatearFechaLegal(v.fecha_emision)} ${formatearHoraLegal(v.hora_emision)}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Almacén Despacho:</span>
                                    <span class="d-inline-flex align-items-center gap-1.5 font-monospace fw-semibold text-dark"><i class="fas fa-warehouse text-primary"></i> ${v.almacen?.nombre || 'General'}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Caja Registradora:</span>
                                    <span class="d-inline-flex align-items-center gap-1.5 font-monospace fw-semibold text-dark"><i class="fas fa-cash-register text-secondary"></i> ${v.caja?.nombre || 'Caja Principal'}${v.caja_turno_id ? ` (Turno #${v.caja_turno_id})` : ''}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Condición de Pago:</span>
                                    <span class="badge rounded-pill ${v.condicion_pago === 'credito' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success'} font-monospace px-2.5 py-0.5">${v.condicion_pago === 'credito' ? 'Crédito' : 'Contado'} (${v.tipo_venta === 'mayor' ? 'Mayor' : 'Detal'})</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Asesor / Vendedor:</span>
                                    <span class="d-inline-flex align-items-center gap-1.5 font-monospace fw-semibold text-dark"><i class="fas fa-user-tie text-primary"></i> ${v.vendedor ? v.vendedor.nombre : 'Sin asignar'}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary fw-medium">Operador de Caja:</span>
                                    <span class="d-inline-flex align-items-center gap-1.5 font-monospace fw-semibold text-dark"><i class="fas fa-user-circle text-secondary"></i> ${v.usuario?.name || 'Sistema'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-2.5"><i class="fas fa-user-check text-success me-1"></i> Datos del Cliente</h6>
                            <div class="font-monospace small">
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Cliente:</span>
                                    <strong class="text-dark font-monospace">${cliNombre}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Cédula / RIF:</span>
                                    <span class="d-inline-flex align-items-center font-monospace text-secondary fw-semibold"><i class="far fa-id-card text-primary me-1"></i>${cliCedula}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1.5">
                                    <span class="text-secondary fw-medium">Teléfono:</span>
                                    <span class="font-monospace text-dark"><i class="fas fa-phone text-secondary me-1"></i>${cliTelefono}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-secondary fw-medium">Dirección:</span>
                                    <span class="text-dark text-truncate d-inline-block" style="max-width: 240px;" title="${cliDireccion}"><i class="fas fa-map-marker-alt text-danger me-1"></i>${cliDireccion}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills nav-fill bg-white p-1 rounded-pill border shadow-xs mb-3 gap-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold font-monospace small py-1.5" data-bs-toggle="pill" data-bs-target="#tab-detalle-items" type="button" role="tab">
                            <i class="fas fa-boxes-stacked me-1 text-primary"></i> Renglones (${v.detalles?.length || 0})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold font-monospace small py-1.5" data-bs-toggle="pill" data-bs-target="#tab-detalle-pagos" type="button" role="tab">
                            <i class="fas fa-wallet me-1 text-success"></i> Pagos (${v.pagos?.length || 0})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold font-monospace small py-1.5" data-bs-toggle="pill" data-bs-target="#tab-detalle-devoluciones" type="button" role="tab">
                            <i class="fas fa-undo me-1 text-warning"></i> Devoluciones (${v.devoluciones?.length || 0})
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold font-monospace small py-1.5" data-bs-toggle="pill" data-bs-target="#tab-detalle-cxc" type="button" role="tab">
                            <i class="fas fa-hand-holding-usd me-1 text-danger"></i> Cuenta por Cobrar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold font-monospace small py-1.5" data-bs-toggle="pill" data-bs-target="#tab-detalle-comision" type="button" role="tab">
                            <i class="fas fa-user-tie me-1 text-info"></i> Comisión
                        </button>
                    </li>
                </ul>

                <div class="tab-content mb-3">
                    <div class="tab-pane fade show active" id="tab-detalle-items" role="tabpanel">
                        <div class="card border rounded-4 bg-white shadow-xs overflow-hidden">
                            <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="bg-primary bg-opacity-10 text-primary font-monospace small">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">#</th>
                                            <th>Descripción</th>
                                            <th class="text-center" style="width: 90px;">Cantidad</th>
                                            <th class="text-end" style="width: 110px;">Precio Unit.</th>
                                            <th class="text-center" style="width: 90px;">IVA</th>
                                            <th class="text-end" style="width: 120px;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>${renglonesHtml}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-detalle-pagos" role="tabpanel">
                        <div class="card border rounded-4 p-3 bg-white shadow-xs">
                            <h6 class="fw-bold text-dark mb-2.5 font-monospace"><i class="fas fa-wallet text-success me-1"></i> Formas de Cobro Registradas</h6>
                            ${pagosHtml}
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-detalle-devoluciones" role="tabpanel">
                        ${devolucionesHtml}
                    </div>

                    <div class="tab-pane fade" id="tab-detalle-cxc" role="tabpanel">
                        ${cxcHtml}
                    </div>

                    <div class="tab-pane fade" id="tab-detalle-comision" role="tabpanel">
                        ${comisionHtml}
                    </div>
                </div>

                <div class="card border rounded-4 p-3 bg-white shadow-xs font-monospace">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6 border-end">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary fw-semibold">Sub-Total Neto:</span>
                                <strong class="text-dark">$ ${parseFloat(v.subtotal_neto_usd).toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-secondary fw-semibold">Total IVA:</span>
                                <strong class="text-dark">$ ${parseFloat(v.iva_monto_usd).toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">Monto Pagado:</span>
                                <strong class="text-success">$ ${parseFloat(v.monto_pagado_usd).toFixed(2)}</strong>
                            </div>
                        </div>
                        <div class="col-md-6 text-end ps-md-4">
                            <span class="fw-bold text-dark fs-6 d-block mb-1">TOTAL FACTURA</span>
                            <h3 class="fw-bold text-success mb-0 font-monospace">$ ${parseFloat(v.total_usd).toFixed(2)}</h3>
                            <span class="badge rounded-pill bg-success-subtle text-success font-monospace px-2.5 py-1 fw-bold mt-1">Bs. ${parseFloat(v.total_bs).toFixed(2)}</span>
                            ${parseFloat(v.saldo_pendiente_usd) > 0 ? `
                                <div class="small text-danger fw-bold mt-1">
                                    <span>Saldo Pendiente (CXC): $ ${parseFloat(v.saldo_pendiente_usd).toFixed(2)}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `);

            bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalleFactura")).show();
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error al cargar factura." });
        }
    }
};

const imprimirTicketFactura = function (id) {
    window.open(`${urlPosImprimirTicket}/${id}`, "_blank", "width=400,height=600");
};

const imprimirCartaFactura = function (id) {
    window.open(`${urlPosImprimirCarta}/${id}`, "_blank");
};

const imprimirGarantiaFactura = function (id) {
    window.open(`${urlPosImprimirGarantia}/${id}`, "_blank");
};

const configurarSelectorTurnoAnular = function () {
    const $contenedor = $("#contenedorTurnoAnularFactura");
    const $select = $("#anular_caja_turno_id");
    $select.empty();

    if (turnoPropioFacturaId) {
        $select.append(`<option value="${turnoPropioFacturaId}" selected>Turno propio #${turnoPropioFacturaId}</option>`);
        $select.prop("required", false);
        $contenedor.addClass("d-none");
        return;
    }

    if (turnosAbiertosFacturas.length === 1) {
        const unico = turnosAbiertosFacturas[0];
        $select.append(`<option value="${unico.id}" selected>${unico.caja_nombre} - ${unico.cajero_nombre}</option>`);
        $select.prop("required", false);
        $contenedor.addClass("d-none");
        return;
    }

    if (turnosAbiertosFacturas.length > 1) {
        $select.append('<option value="">Seleccione el turno de caja abierto...</option>');
        turnosAbiertosFacturas.forEach(function (t) {
            $select.append(`<option value="${t.id}">${t.caja_nombre} (${t.cajero_nombre})</option>`);
        });
        $select.val(String(turnosAbiertosFacturas[0].id));
        $select.prop("required", true);
        $contenedor.removeClass("d-none");
        return;
    }

    $select.append('<option value="">Sin turnos abiertos</option>');
    $select.prop("required", false);
    $contenedor.addClass("d-none");
};

const abrirModalAnularFactura = function (id, codigo, totalUsd) {
    const modalDetalleEl = document.getElementById("modalDetalleFactura");
    if (modalDetalleEl) {
        const instDetalle = bootstrap.Modal.getInstance(modalDetalleEl);
        if (instDetalle) {
            instDetalle.hide();
        }
    }

    const formEl = document.getElementById("formAnularFactura");
    if (formEl) {
        formEl.reset();
    }
    $("#anular_factura_id").val(id);
    $("#anular_factura_codigo").text(`#${codigo}`);
    $("#anular_factura_total").text(`$ ${parseFloat(totalUsd || 0).toFixed(2)}`);
    $("#anular_motivo").val("");
    configurarSelectorTurnoAnular();

    const modalEl = document.getElementById("modalAnularFactura");
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
};

let facturaDevolverActual = null;

const configurarSelectorTurnoDevolver = function () {
    const $contenedor = $("#contenedorTurnoDevolverFactura");
    const $select = $("#devolver_caja_turno_id");
    $select.empty();

    if (turnoPropioFacturaId) {
        $select.append(`<option value="${turnoPropioFacturaId}" selected>Turno propio #${turnoPropioFacturaId}</option>`);
        $select.prop("required", false);
        $contenedor.addClass("d-none");
        return;
    }

    if (turnosAbiertosFacturas.length === 1) {
        const unico = turnosAbiertosFacturas[0];
        $select.append(`<option value="${unico.id}" selected>${unico.caja_nombre} - ${unico.cajero_nombre}</option>`);
        $select.prop("required", false);
        $contenedor.addClass("d-none");
        return;
    }

    if (turnosAbiertosFacturas.length > 1) {
        $select.append('<option value="">Seleccione el turno de caja abierto...</option>');
        turnosAbiertosFacturas.forEach(function (t) {
            $select.append(`<option value="${t.id}">${t.caja_nombre} (${t.cajero_nombre})</option>`);
        });
        $select.val(String(turnosAbiertosFacturas[0].id));
        $select.prop("required", true);
        $contenedor.removeClass("d-none");
        return;
    }

    $select.append('<option value="">Sin turnos abiertos</option>');
    $select.prop("required", false);
    $contenedor.addClass("d-none");
};

const abrirModalDevolverFactura = async function (id) {
    const modalDetalleEl = document.getElementById("modalDetalleFactura");
    if (modalDetalleEl) {
        const instDetalle = bootstrap.Modal.getInstance(modalDetalleEl);
        if (instDetalle) {
            instDetalle.hide();
        }
    }

    try {
        const res = await peticionAjax({
            url: `${urlFacturasDetalle}/${id}/devolucion-datos`,
            method: "GET"
        });

        if (res && res.success && res.data) {
            const v = res.data;
            facturaDevolverActual = v;
            $("#devolver_factura_id").val(v.id);
            $("#devolver_factura_codigo").text(`#${v.codigo}`);
            $("#devolver_factura_cliente").text(`Cliente: ${v.cliente_nombre} (${v.cliente_cedula})`);
            $("#devolver_factura_total").text(`$ ${parseFloat(v.total_usd).toFixed(2)}`);
            $("#devolver_factura_fecha").text(`Fecha: ${formatearFechaLegal(v.fecha_emision)}`);
            $("#devolver_motivo").val("Devolución de cliente");
            configurarSelectorTurnoDevolver();

            const $tbody = $("#contenedorFilasDevolverFactura");
            $tbody.empty();

            (v.detalles || []).forEach(function (det) {
                const precio = parseFloat(det.precio_unitario_usd || 0).toFixed(2);
                const subtotal = (det.cantidad_disponible * parseFloat(det.precio_unitario_usd || 0)).toFixed(2);
                const devPrevInfo = det.cantidad_devuelta > 0 ? `<small class="text-danger d-block font-monospace">(-${det.cantidad_devuelta} devueltos)</small>` : "";

                $tbody.append(`
                    <tr>
                        <td>
                            <strong class="text-dark font-monospace d-block">${det.nombre}</strong>
                            <small class="text-secondary font-monospace">${det.tipo_item}</small>
                        </td>
                        <td class="text-center font-monospace">
                            ${det.cantidad_facturada}
                            ${devPrevInfo}
                        </td>
                        <td class="text-end font-monospace text-dark">$ ${precio}</td>
                        <td class="text-center font-monospace">
                            <input type="number" step="any" min="0" max="${det.cantidad_disponible}" class="form-control form-control-sm text-center font-monospace fw-bold input-cant-devolver-row" data-id="${det.id}" data-precio="${det.precio_unitario_usd}" value="${det.cantidad_disponible}" oninput="calcularTotalRenglonDevolver(this)">
                        </td>
                        <td class="text-end font-monospace fw-bold text-danger subtotal-dev-row">$ ${subtotal}</td>
                    </tr>
                `);
            });

            const modalEl = document.getElementById("modalDevolverFactura");
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }
    } catch (err) {
        const msg = err.responseJSON?.message || "No se pudieron obtener los datos para la devolución.";
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: msg });
        }
    }
};

const marcarTodoDevolver = function () {
    $(".input-cant-devolver-row").each(function () {
        const max = parseFloat($(this).attr("max")) || 0;
        $(this).val(max);
        calcularTotalRenglonDevolver(this);
    });
};

const desmarcarTodoDevolver = function () {
    $(".input-cant-devolver-row").each(function () {
        $(this).val(0);
        calcularTotalRenglonDevolver(this);
    });
};

const calcularTotalRenglonDevolver = function (input) {
    const $input = $(input);
    const cant = parseFloat($input.val()) || 0;
    const precio = parseFloat($input.data("precio")) || 0;
    const subtotal = Math.round((cant * precio + Number.EPSILON) * 100) / 100;
    $input.closest("tr").find(".subtotal-dev-row").text(`$ ${subtotal.toFixed(2)}`);
};
