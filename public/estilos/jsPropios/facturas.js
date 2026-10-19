let datatableFacturas = null;

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
                data: "almacen.nombre",
                name: "almacen.nombre",
                render: function (data) {
                    const nombre = data || "General";
                    return `
                        <div class="d-inline-flex align-items-center gap-1.5 text-nowrap">
                            <i class="fas fa-warehouse text-primary" style="font-size: 0.82rem;"></i>
                            <span class="font-monospace fw-semibold text-dark" style="font-size: 0.84rem;">${nombre}</span>
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
                            return '<span class="badge rounded-pill bg-dark-subtle text-dark border px-2.5 py-1 font-monospace"><i class="fas fa-times-circle me-1"></i>Anulada</span>';
                        default:
                            return `<span class="badge rounded-pill bg-light text-dark border px-2.5 py-1 font-monospace">${data}</span>`;
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
                    return `
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2.5 py-1" onclick="verDetalleFactura(${data})" title="Ver Detalle 360°">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2.5 py-1" onclick="imprimirCartaFactura(${data})" title="Factura Carta (Hoja Blanca)">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                            <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2.5 py-1" onclick="imprimirTicketFactura(${data})" title="Ticket Térmico (Tiquera)">
                                <i class="fas fa-receipt"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
};

const cargarKpisFacturas = async function () {
    try {
        const res = await $.ajax({
            url: urlFacturasKpis,
            type: "GET",
            dataType: "json"
        });

        if (res.success && res.data) {
            const d = res.data;
            $("#kpiTotalUsd").text(`$ ${d.total_usd.toLocaleString('en-US', { minimumFractionDigits: 2 })}`);
            $("#kpiTotalBs").text(`Bs. ${d.total_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiTotalHoyUsd").text(`$ ${d.total_hoy_usd.toLocaleString('en-US', { minimumFractionDigits: 2 })}`);
            $("#kpiTotalHoyBs").text(`Bs. ${d.total_hoy_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiTotalCreditoUsd").text(`$ ${d.total_credito_usd.toLocaleString('en-US', { minimumFractionDigits: 2 })}`);
            $("#kpiTotalCreditoBs").text(`Bs. ${d.total_credito_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiConteoTotal").text(d.conteo_total);
            $("#kpiConteoHoy").text(d.conteo_hoy);
            $("#kpiConteoCredito").text(d.conteo_credito);
            $("#kpiConteoDevueltas").text(d.conteo_devueltas);
        }
    } catch (e) {
        console.error("Error al cargar KPIs de facturas:", e);
    }
};

const recargarTablaFacturas = function () {
    validarFiltroFechas();
    if (datatableFacturas) {
        datatableFacturas.ajax.reload();
    }
    cargarKpisFacturas();
};
window.recargarTablaFacturas = recargarTablaFacturas;

const limpiarFiltrosFacturas = function () {
    const hoy = obtenerFechaHoy();
    $("#filtroFechaInicio").val("").attr("max", hoy).removeAttr("min");
    $("#filtroFechaFin").val("").attr("max", hoy).removeAttr("min");
    $("#filtroCondicion").val("");
    $("#filtroEstado").val("");
    recargarTablaFacturas();
};
window.limpiarFiltrosFacturas = limpiarFiltrosFacturas;

const verDetalleFactura = async function (id) {
    try {
        const res = await $.ajax({
            url: `${urlFacturasDetalle}/${id}`,
            type: "GET",
            dataType: "json"
        });

        if (res.success && res.data) {
            const v = res.data;
            $("#modalDetalleFacturaSubtitulo").text(`Comprobante #${v.codigo}`);
            $("#modalDetalleFacturaTitulo").text(`Detalle de Factura #${v.codigo}`);
            $("#btnImprimirCartaModalDetalle").attr("onclick", `imprimirCartaFactura(${v.id})`);
            $("#btnReimprimirModalDetalle").attr("onclick", `imprimirTicketFactura(${v.id})`);

            const cliNombre = v.cliente ? `${v.cliente.nombre} ${v.cliente.apellido || ''}`.trim() : "Consumidor Final";
            const cliCedula = v.cliente ? v.cliente.cedula : "V-00000000";
            const cliTelefono = v.cliente ? (v.cliente.telefono || "Sin teléfono") : "Sin teléfono";
            const cliDireccion = v.cliente ? (v.cliente.direccion || "Sin dirección registrada") : "Sin dirección";

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
                pagosHtml = `<div class="p-3 border rounded-3 bg-white text-muted font-monospace small text-center shadow-xs">Facturado 100% a Crédito (Sin abono inicial en caja).</div>`;
            }

            let cxcHtml = "";
            if (v.cuenta_por_cobrar) {
                const cxc = v.cuenta_por_cobrar;
                cxcHtml = `
                    <div class="card border rounded-4 p-3 bg-warning-subtle text-warning-emphasis shadow-xs mb-3 font-monospace">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom border-warning pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0"><i class="fas fa-hand-holding-usd me-1"></i> Cuenta por Cobrar Registrada</h6>
                                <small>Vencimiento: ${formatearFechaLegal(cxc.fecha_vencimiento)}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold fs-6">Saldo: $ ${parseFloat(cxc.saldo_pendiente_usd).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="small">
                            <span>Monto Total: <strong>$ ${parseFloat(cxc.monto_total_usd).toFixed(2)}</strong></span> • 
                            <span>Total Abonado: <strong>$ ${parseFloat(cxc.monto_pagado_usd).toFixed(2)}</strong></span> • 
                            <span>Estado CXC: <strong>${cxc.estado}</strong></span>
                        </div>
                    </div>
                `;
            }

            $("#contenidoDetalleFactura").html(`
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
                                    <span class="text-secondary fw-medium">Condición de Pago:</span>
                                    <span class="badge rounded-pill ${v.condicion_pago === 'credito' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success'} font-monospace px-2.5 py-0.5">${v.condicion_pago === 'credito' ? 'Crédito' : 'Contado'} (${v.tipo_venta === 'mayor' ? 'Mayor' : 'Detal'})</span>
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

                ${cxcHtml}

                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden mb-3">
                    <div class="p-2.5 bg-white border-bottom">
                        <strong class="text-dark small"><i class="fas fa-boxes-stacked text-primary me-1"></i> Productos & Servicios Facturados</strong>
                    </div>
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
                            <tbody>
                                ${renglonesHtml}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                            <h6 class="fw-bold text-dark mb-2.5"><i class="fas fa-wallet text-primary me-1"></i> Formas de Cobro Aplicadas</h6>
                            ${pagosHtml}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border rounded-4 p-3 bg-white shadow-xs font-monospace">
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-secondary fw-semibold">Sub-Total Neto:</span>
                                <strong class="text-dark">$ ${parseFloat(v.subtotal_neto_usd).toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1.5">
                                <span class="text-secondary fw-semibold">Total IVA:</span>
                                <strong class="text-dark">$ ${parseFloat(v.iva_monto_usd).toFixed(2)}</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark fs-6">TOTAL FACTURA:</span>
                                <div class="text-end">
                                    <h3 class="fw-bold text-success mb-0 font-monospace">$ ${parseFloat(v.total_usd).toFixed(2)}</h3>
                                    <span class="badge rounded-pill bg-success-subtle text-success font-monospace px-2.5 py-1 fw-bold mt-1">Bs. ${parseFloat(v.total_bs).toFixed(2)}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top small">
                                <span class="text-secondary fw-semibold">Monto Pagado:</span>
                                <strong class="text-success">$ ${parseFloat(v.monto_pagado_usd).toFixed(2)}</strong>
                            </div>
                            ${parseFloat(v.vuelto_usd) > 0 ? `
                                <div class="d-flex justify-content-between small text-info fw-semibold">
                                    <span>Vuelto / Cambio:</span>
                                    <strong>$ ${parseFloat(v.vuelto_usd).toFixed(2)}</strong>
                                </div>
                            ` : ''}
                            ${parseFloat(v.saldo_pendiente_usd) > 0 ? `
                                <div class="d-flex justify-content-between small text-danger fw-bold">
                                    <span>Saldo Pendiente (CXC):</span>
                                    <strong>$ ${parseFloat(v.saldo_pendiente_usd).toFixed(2)}</strong>
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
window.verDetalleFactura = verDetalleFactura;

const imprimirTicketFactura = function (id) {
    window.open(`${urlPosImprimirTicket}/${id}`, "_blank", "width=400,height=600");
};
window.imprimirTicketFactura = imprimirTicketFactura;

const imprimirCartaFactura = function (id) {
    window.open(`${urlPosImprimirCarta}/${id}`, "_blank");
};
window.imprimirCartaFactura = imprimirCartaFactura;
