let datatableFacturas = null;

$(document).ready(function () {
    inicializarTablaFacturas();
    cargarKpisFacturas();
});

const inicializarTablaFacturas = function () {
    datatableFacturas = crearDataTable({
        selector: "#datatable_facturas",
        url: urlFacturasLista,
        data: function (d) {
            d.fecha_inicio = $("#filtroFechaInicio").val();
            d.fecha_fin = $("#filtroFechaFin").val();
            d.condicion_pago = $("#filtroCondicion").val();
            d.estado = $("#filtroEstado").val();
        },
        columns: [
            {
                data: "codigo",
                name: "codigo",
                render: function (data) {
                    return `<span class="badge rounded-pill bg-light text-dark border font-monospace fw-bold px-2.5 py-1.5"><i class="fas fa-receipt text-primary me-1"></i>${data}</span>`;
                }
            },
            {
                data: "fecha_emision",
                name: "fecha_emision",
                render: function (data, type, row) {
                    return `
                        <div>
                            <strong class="text-dark font-monospace d-block" style="font-size: 0.85rem;">${data}</strong>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;"><i class="fas fa-clock me-1"></i>${row.hora_emision || '--'}</small>
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
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">${cliCedula}</small>
                        </div>
                    `;
                }
            },
            {
                data: "almacen.nombre",
                name: "almacen.nombre",
                render: function (data) {
                    return `<span class="badge rounded-pill bg-light text-secondary border px-2 py-1 font-monospace"><i class="fas fa-warehouse me-1"></i>${data || 'General'}</span>`;
                }
            },
            {
                data: "condicion_pago",
                name: "condicion_pago",
                render: function (data, type, row) {
                    const esCredito = data === "credito";
                    const badgeCond = esCredito
                        ? '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace px-2 py-0.5">Crédito (CXC)</span>'
                        : '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle font-monospace px-2 py-0.5">Contado</span>';
                    const tipoVenta = row.tipo_venta === "mayor" ? "Mayor" : "Detal";
                    return `<div>${badgeCond}<small class="text-muted font-monospace d-block mt-0.5">${tipoVenta}</small></div>`;
                }
            },
            {
                data: "total_usd",
                name: "total_usd",
                className: "text-end font-monospace",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.total_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `<div><strong class="text-dark d-block" style="font-size: 0.92rem;">$ ${usd}</strong><small class="badge rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">Bs. ${bs}</small></div>`;
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
    if (datatableFacturas) {
        datatableFacturas.ajax.reload();
    }
    cargarKpisFacturas();
};
window.recargarTablaFacturas = recargarTablaFacturas;

const limpiarFiltrosFacturas = function () {
    $("#filtroFechaInicio").val("");
    $("#filtroFechaFin").val("");
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
            $("#modalFacturaCodigoHeader").text(`Comprobante #${v.codigo}`);
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
                    const serialBadge = det.serial_identificador ? `<span class="badge bg-light text-dark border font-monospace ms-1">#${det.serial_identificador}</span>` : "";
                    const tipoBadge = `<span class="badge bg-secondary-subtle text-secondary font-monospace rounded-pill">${det.tipo_item}</span>`;

                    renglonesHtml += `
                        <tr>
                            <td class="font-monospace text-center small">${idx + 1}</td>
                            <td>
                                <strong class="text-dark font-monospace d-block">${itemNombre} ${serialBadge}</strong>
                                <small class="text-muted font-monospace">${tipoBadge} • Almacén: ${det.almacen?.nombre || 'General'}</small>
                            </td>
                            <td class="text-center font-monospace fw-bold">${parseFloat(det.cantidad).toLocaleString()}</td>
                            <td class="text-end font-monospace">$ ${parseFloat(det.precio_unitario_usd).toFixed(2)}</td>
                            <td class="text-center font-monospace small">${det.aplica_iva ? `IVA ${det.iva_porcentaje}%` : 'Exento'}</td>
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
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded-3 bg-light mb-1 font-monospace small">
                            <div>
                                <strong class="text-dark">${metodoNombre}</strong>
                                ${p.referencia ? `<span class="text-muted ms-1">(Ref: ${p.referencia})</span>` : ''}
                            </div>
                            <div>
                                <span class="badge ${p.moneda === 'VES' ? 'bg-info-subtle text-info-emphasis' : 'bg-success-subtle text-success'} rounded-pill me-1">${p.moneda}</span>
                                <strong class="text-success">$ ${parseFloat(p.monto_usd).toFixed(2)}</strong>
                                <small class="text-muted ms-1">(Bs. ${parseFloat(p.monto_bs).toFixed(2)})</small>
                            </div>
                        </div>
                    `;
                });
            } else {
                pagosHtml = `<div class="p-2 border rounded-3 bg-light text-muted font-monospace small text-center">Facturado 100% a Crédito (Sin abono inicial en caja).</div>`;
            }

            let cxcHtml = "";
            if (v.cuenta_por_cobrar) {
                const cxc = v.cuenta_por_cobrar;
                cxcHtml = `
                    <div class="card border rounded-4 p-3 bg-warning-subtle text-warning-emphasis shadow-xs mb-3 font-monospace">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom border-warning pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0"><i class="fas fa-hand-holding-usd me-1"></i> Cuenta por Cobrar Registrada</h6>
                                <small>Vencimiento: ${cxc.fecha_vencimiento}</small>
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
                <!-- CABECERA DE FACTURA Y CLIENTE -->
                <div class="card border rounded-4 p-3 bg-white shadow-xs mb-3">
                    <div class="row g-3">
                        <div class="col-md-6 border-end">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-receipt text-primary me-1"></i> Información de Comprobante</h6>
                            <div class="font-monospace small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Número Factura:</span>
                                    <strong class="text-dark">${v.codigo}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Fecha & Hora:</span>
                                    <span>${v.fecha_emision} ${v.hora_emision || ''}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Almacén Despacho:</span>
                                    <strong class="text-primary">${v.almacen?.nombre || 'General'}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Condición de Pago:</span>
                                    <span class="badge ${v.condicion_pago === 'credito' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success'} rounded-pill">${v.condicion_pago === 'credito' ? 'Crédito' : 'Contado'} (${v.tipo_venta})</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Operador de Caja:</span>
                                    <span>${v.usuario?.name || 'Sistema'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-user-check text-success me-1"></i> Datos del Cliente</h6>
                            <div class="font-monospace small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Cliente:</span>
                                    <strong class="text-dark">${cliNombre}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Cédula / RIF:</span>
                                    <span>${cliCedula}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Teléfono:</span>
                                    <span>${cliTelefono}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Dirección:</span>
                                    <span class="text-truncate" style="max-width: 220px;">${cliDireccion}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                ${cxcHtml}

                <!-- TABLA DE RENGLONES -->
                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden mb-3">
                    <div class="p-2.5 bg-light border-bottom">
                        <strong class="text-dark small"><i class="fas fa-boxes-stacked text-primary me-1"></i> Productos & Servicios Facturados</strong>
                    </div>
                    <div class="table-responsive" style="max-height: 240px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light font-monospace small">
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

                <!-- FORMAS DE PAGO Y TOTALES -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-wallet text-secondary me-1"></i> Formas de Cobro Aplicadas</h6>
                            ${pagosHtml}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border rounded-4 p-3 bg-white shadow-xs font-monospace">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Sub-Total Neto:</span>
                                <strong class="text-dark">$ ${parseFloat(v.subtotal_neto_usd).toFixed(2)}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total IVA:</span>
                                <strong class="text-dark">$ ${parseFloat(v.iva_monto_usd).toFixed(2)}</strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark fs-6">TOTAL FACTURA:</span>
                                <div class="text-end">
                                    <h4 class="fw-bold text-success mb-0">$ ${parseFloat(v.total_usd).toFixed(2)}</h4>
                                    <small class="badge rounded-pill bg-light text-secondary border">Bs. ${parseFloat(v.total_bs).toFixed(2)}</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 pt-2 border-top small">
                                <span class="text-muted">Monto Pagado:</span>
                                <strong class="text-success">$ ${parseFloat(v.monto_pagado_usd).toFixed(2)}</strong>
                            </div>
                            ${parseFloat(v.vuelto_usd) > 0 ? `
                                <div class="d-flex justify-content-between small text-info">
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
