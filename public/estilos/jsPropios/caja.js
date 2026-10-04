const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlListaCajas = `${urlBase}/lista`;
const urlListaTurnos = `${urlBase}/turnos/lista`;
const urlCajasDisponibles = `${urlBase}/disponibles`;
const urlGuardarCaja = urlBase;
const urlEditarCaja = `${urlBase}/actualizar/`;
const urlEliminarCaja = `${urlBase}/`;
const urlAperturarTurno = `${urlBase}/turnos/aperturar`;

let isEditarCaja = false;
let idCajaActual = null;
let turnoActivoCierre = null;
let esperadoUsdActual = 0;
let esperadoBsActual = 0;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_cajas",
        url: urlListaCajas,
        searchPlaceholder: "Nombre, código, almacén...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const codigo = row.codigo ? `<span class="badge bg-white text-secondary border px-2 py-0.5 rounded-pill shadow-xs me-1 fw-bold">${row.codigo}</span>` : '';
                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1 bg-primary-subtle text-primary border border-primary-subtle">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark" style="font-size: 0.93rem;">${row.nombre}</span>
                                <div>${codigo}<span class="text-muted small">${row.descripcion || 'Sin descripción'}</span></div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "almacen.nombre",
                name: "almacen.nombre",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (row.almacen) {
                        return `<span class="badge bg-white text-dark border px-2.5 py-1 rounded-pill shadow-xs"><i class="fas fa-warehouse text-primary me-1"></i>${row.almacen.nombre}</span>`;
                    }
                    return '<span class="text-muted small fst-italic">Global / Ninguno</span>';
                },
            },
            {
                data: "turno_activo",
                name: "turno_activo",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (row.turno_activo) {
                        const cajero = row.turno_activo.usuario ? (row.turno_activo.usuario.name || row.turno_activo.usuario.nombre || 'Cajero') : 'Cajero';
                        return `
                            <div class="d-flex flex-column align-items-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-bold mb-1">
                                    <i class="fas fa-circle fa-beat me-1"></i>En curso (#${String(row.turno_activo.id).padStart(5, '0')})
                                </span>
                                <span class="text-muted small" style="font-size: 0.76rem;">Por: <strong>${cajero}</strong></span>
                            </div>
                        `;
                    }
                    return '<span class="badge bg-white text-secondary border px-2.5 py-1 rounded-pill shadow-xs"><i class="fas fa-lock-open me-1 text-success"></i>Disponible / Libre</span>';
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data) {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill"><i class="fas fa-check-circle me-1"></i>Activa</span>';
                    }
                    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill"><i class="fas fa-times-circle me-1"></i>Inactiva</span>';
                },
            },
            {
                data: null,
                width: "140px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").trim().replace(/'/g, "\\'");
                    let btnsTurno = '';

                    if (row.turno_activo) {
                        btnsTurno = `
                            <button type="button" class="btn btn-outline-warning btn-sm rounded-circle shadow-sm" onclick="verCorteX(${row.turno_activo.id});" title="Ver Corte X (Auditoría)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="abrirModalCierre(${row.turno_activo.id});" title="Cerrar Turno (Z)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-lock"></i>
                            </button>
                        `;
                    } else if (row.estado) {
                        btnsTurno = `
                            <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="abrirModalApertura(${row.id});" title="Aperturar esta caja" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-key"></i>
                            </button>
                        `;
                    }

                    return `
                    <div class="d-flex justify-content-center gap-1">
                        ${btnsTurno}
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editarCaja(${row.id});" title="Editar caja" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminarCaja(${row.id}, '${nombreEscapado}');" title="Alternar estado" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>`;
                },
            },
        ],
    });

    crearDataTable({
        selector: "#datatable_turnos",
        url: urlListaTurnos,
        searchPlaceholder: "Caja, cajero, fecha, estado...",
        columns: [
            {
                data: "id",
                name: "id",
                className: "text-center align-middle",
                render: function (data) {
                    return `<span class="fw-bold text-dark">#${String(data).padStart(5, '0')}</span>`;
                },
            },
            {
                data: "caja.nombre",
                name: "caja.nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const cajaNom = row.caja ? row.caja.nombre : 'Caja';
                    const cajaCod = row.caja && row.caja.codigo ? `<span class="badge bg-white text-secondary border px-2 py-0.5 rounded-pill shadow-xs me-1 fw-bold">${row.caja.codigo}</span>` : '';
                    return `
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-cash-register text-primary"></i>
                            <div>
                                <span class="fw-bold text-dark">${cajaNom}</span>
                                <div>${cajaCod}</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "usuario.name",
                name: "usuario.name",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const userNom = row.usuario ? (row.usuario.name || row.usuario.nombre || 'Cajero') : 'Cajero';
                    return `
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-user-circle text-muted"></i>
                            <span class="fw-semibold text-dark">${userNom}</span>
                        </div>
                    `;
                },
            },
            {
                data: "fecha_apertura",
                name: "fecha_apertura",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    return `
                        <div class="d-flex flex-column">
                            <span class="fw-bold text-dark">${data || ''}</span>
                            <span class="text-muted small">${row.hora_apertura || ''}</span>
                        </div>
                    `;
                },
            },
            {
                data: "fecha_cierre",
                name: "fecha_cierre",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (!data) {
                        return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill"><i class="fas fa-clock me-1"></i>En curso</span>';
                    }
                    return `
                        <div class="d-flex flex-column">
                            <span class="fw-semibold text-dark">${data}</span>
                            <span class="text-muted small">${row.hora_cierre || ''}</span>
                        </div>
                    `;
                },
            },
            {
                data: "monto_apertura_usd",
                name: "monto_apertura_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    return `
                        <div class="d-flex flex-column text-end">
                            <span class="fw-bold text-dark">$${parseFloat(data || 0).toFixed(2)}</span>
                            <span class="text-muted small">Bs. ${parseFloat(row.monto_apertura_bs || 0).toFixed(2)}</span>
                        </div>
                    `;
                },
            },
            {
                data: "monto_cierre_usd",
                name: "monto_cierre_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    if (row.estado === 'abierta') {
                        return '<span class="text-muted fst-italic small">Sin cierre</span>';
                    }
                    return `
                        <div class="d-flex flex-column text-end">
                            <span class="fw-bold text-dark">$${parseFloat(data || 0).toFixed(2)}</span>
                            <span class="text-muted small">Bs. ${parseFloat(row.monto_cierre_bs || 0).toFixed(2)}</span>
                        </div>
                    `;
                },
            },
            {
                data: "diferencia_usd",
                name: "diferencia_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    if (row.estado === 'abierta') {
                        return '<span class="text-muted small">--</span>';
                    }
                    const difUsd = parseFloat(data || 0);
                    const difBs = parseFloat(row.diferencia_bs || 0);

                    if (Math.abs(difUsd) < 0.001 && Math.abs(difBs) < 0.001) {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i>Cuadrado</span>';
                    }

                    let clase = difUsd >= 0 ? 'text-success' : 'text-danger';
                    let signo = difUsd > 0 ? '+' : '';

                    return `
                        <div class="d-flex flex-column text-end">
                            <span class="fw-bold ${clase}">${signo}$${difUsd.toFixed(2)}</span>
                            <span class="${clase} small">${signo}Bs. ${difBs.toFixed(2)}</span>
                        </div>
                    `;
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data === 'abierta') {
                        return '<span class="badge bg-success rounded-pill px-2.5 py-1 fw-bold"><i class="fas fa-circle fa-beat me-1"></i>Abierta</span>';
                    }
                    return '<span class="badge bg-secondary rounded-pill px-2.5 py-1">Cerrada</span>';
                },
            },
            {
                data: null,
                width: "100px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    let btnCierre = '';
                    if (row.estado === 'abierta') {
                        btnCierre = `
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="abrirModalCierre(${row.id});" title="Cerrar Turno (Z)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-lock"></i>
                            </button>
                        `;
                    } else {
                        btnCierre = `
                            <a href="${urlBase}/turnos/${row.id}/imprimir-z" target="_blank" class="btn btn-outline-dark btn-sm rounded-circle shadow-sm" title="Imprimir Ticket Z" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-print"></i>
                            </a>
                        `;
                    }

                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="verCorteX(${row.id});" title="Ver Corte X" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </button>
                        ${btnCierre}
                    </div>`;
                },
            },
        ],
    });

    $("#monto_cierre_usd, #monto_cierre_bs").on("input", function () {
        calcularDiferenciasCierre();
    });

    $('button[data-bs-toggle="pill"]').on("shown.bs.tab", function (e) {
        const targetId = $(e.target).attr("data-bs-target");
        if (targetId === "#pills-turnos") {
            if ($("#datatable_turnos").length && $.fn.DataTable.isDataTable("#datatable_turnos")) {
                $("#datatable_turnos").DataTable().columns.adjust().responsive.recalc();
            }
        } else if (targetId === "#pills-cajas") {
            if ($("#datatable_cajas").length && $.fn.DataTable.isDataTable("#datatable_cajas")) {
                $("#datatable_cajas").DataTable().columns.adjust().responsive.recalc();
            }
        }
    });
});

const resetearFormularioCaja = function () {
    const $form = $("#formularioCaja");
    $form[0].reset();
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();
};

const crearCaja = function () {
    isEditarCaja = false;
    idCajaActual = null;
    resetearFormularioCaja();

    $("#modalCajaTitulo").text("Nueva Caja Registradora");
    $("#modalCajaSubtitulo").text("Configura los detalles de la caja");
    $("#modalCajaIcono").attr("class", "fas fa-cash-register text-primary fs-5");

    $("#modalCajaBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalCajaTextoGuardar").text("Guardar Caja");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCaja"));
    modal.show();
};

const editarCaja = async function (id) {
    try {
        isEditarCaja = true;
        idCajaActual = id;
        const res = await consultarRegistro(`${urlBase}/`, id);
        const caja = res && res.data ? res.data : res;
        if (!caja) return;

        resetearFormularioCaja();
        $("#caja_nombre").val(caja.nombre || "");
        $("#caja_codigo").val(caja.codigo || "");
        $("#caja_almacen_id").val(caja.almacen_id || "");
        $("#caja_descripcion").val(caja.descripcion || "");

        $("#modalCajaTitulo").text(`Editar Caja: ${caja.nombre}`);
        $("#modalCajaSubtitulo").text("Modifica la configuración de la caja");
        $("#modalCajaIcono").attr("class", "fas fa-edit text-primary fs-5");

        $("#modalCajaBtnGuardar").prop("hidden", false).prop("disabled", false);
        $("#modalCajaTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCaja"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información de la caja.",
            });
        }
    }
};

const eliminarCaja = function (id, nombreCaja) {
    cambiarEstadoRegistro({
        url: urlEliminarCaja,
        id: id,
        nombre: nombreCaja,
        tablaSelector: "#datatable_cajas",
        titulo: "¿Cambiar Estado de la Caja?",
        mensaje: `Se alternará el estado de "${nombreCaja}".`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, cambiar estado',
    });
};

$("#formularioCaja").on("submit", function (e) {
    e.preventDefault();
    const url = isEditarCaja ? `${urlEditarCaja}${idCajaActual}` : urlGuardarCaja;

    enviarFormulario({
        form: this,
        url: url,
        isEditar: isEditarCaja,
        modalSelector: "#modalCaja",
        tablaSelector: "#datatable_cajas",
        btnSubmit: "#modalCajaBtnGuardar",
        textoGuardarOriginal: $("#modalCajaTextoGuardar").text(),
    });
});

const abrirModalApertura = async function (cajaIdPreseleccionada = null) {
    try {
        const $form = $("#formularioApertura");
        $form[0].reset();
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").remove();
        $("#monto_apertura_usd").val("0.00");
        $("#monto_apertura_bs").val("0.00");

        const res = await $.get(urlCajasDisponibles);
        const cajas = res && res.data ? res.data : [];

        const $select = $("#apertura_caja_id");
        $select.empty();

        if (cajas.length === 0) {
            $select.append('<option value="">-- No hay cajas disponibles para aperturar --</option>');
            $("#modalAperturaTurnoBtnGuardar").prop("disabled", true);
        } else {
            $select.append('<option value="">-- Selecciona una caja disponible --</option>');
            cajas.forEach(c => {
                const selected = cajaIdPreseleccionada && parseInt(cajaIdPreseleccionada) === parseInt(c.id) ? 'selected' : '';
                $select.append(`<option value="${c.id}" ${selected}>${c.nombre} ${c.codigo ? `(${c.codigo})` : ''}</option>`);
            });
            $("#modalAperturaTurnoBtnGuardar").prop("disabled", false);
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAperturaTurno"));
        modal.show();
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron obtener las cajas disponibles.",
            });
        }
    }
};

$("#formularioApertura").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAperturarTurno,
        isEditar: false,
        modalSelector: "#modalAperturaTurno",
        tablaSelector: "#datatable_cajas",
        btnSubmit: "#modalAperturaTurnoBtnGuardar",
        textoGuardarOriginal: "Aperturar Turno",
        despuesDeGuardar: function () {
            if ($("#datatable_turnos").length) {
                $("#datatable_turnos").DataTable().ajax.reload(null, false);
            }
            setTimeout(() => {
                window.location.reload();
            }, 800);
        },
    });
});

const verCorteX = async function (turnoId) {
    if (!turnoId) return;

    $("#btnImprimirCorteX").attr("href", `${urlBase}/turnos/${turnoId}/imprimir-x`);
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCorteX"));
    modal.show();

    $("#contenidoCorteX").html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Cargando desglose del turno...</p>
        </div>
    `);

    try {
        const res = await $.get(`${urlBase}/turnos/${turnoId}/reporte-x`);
        const rep = res && res.data ? res.data : null;
        if (!rep) {
            $("#contenidoCorteX").html('<div class="alert alert-danger">No se pudo cargar el reporte del turno.</div>');
            return;
        }

        let filasMetodos = '';
        if (rep.pagos_por_metodo && rep.pagos_por_metodo.length > 0) {
            rep.pagos_por_metodo.forEach(pm => {
                const simbolo = pm.moneda === 'USD' ? '$' : 'Bs.';
                filasMetodos += `
                    <tr>
                        <td><strong>${pm.metodo}</strong></td>
                        <td class="text-center"><span class="badge bg-white text-dark border px-2 py-0.5 rounded-pill shadow-xs fw-bold">${pm.moneda}</span></td>
                        <td class="text-center">${pm.conteo}</td>
                        <td class="text-end fw-bold">${simbolo} ${parseFloat(pm.total_origen).toFixed(2)}</td>
                    </tr>
                `;
            });
        } else {
            filasMetodos = '<tr><td colspan="4" class="text-center text-muted py-3">Sin pagos registrados en este turno</td></tr>';
        }

        const html = `
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Caja: ${rep.caja.nombre}</h5>
                    <span class="text-muted small">Cajero: <strong>${rep.usuario.name || rep.usuario.nombre_completo}</strong> | Turno #${String(rep.turno.id).padStart(5, '0')}</span>
                </div>
                <span class="badge ${rep.turno.estado === 'abierta' ? 'bg-success' : 'bg-secondary'} rounded-pill px-3 py-2 text-uppercase">
                    ${rep.turno.estado}
                </span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card bg-white border rounded-4 p-3 text-center shadow-xs">
                        <span class="text-muted small">Fondo Apertura</span>
                        <h6 class="fw-bold text-dark mb-0 mt-1">$${parseFloat(rep.monto_apertura_usd).toFixed(2)}</h6>
                        <small class="text-muted">Bs. ${parseFloat(rep.monto_apertura_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-white border rounded-4 p-3 text-center shadow-xs">
                        <span class="text-muted small">Ventas (${rep.cantidad_ventas})</span>
                        <h6 class="fw-bold text-success mb-0 mt-1">$${parseFloat(rep.total_ventas_usd).toFixed(2)}</h6>
                        <small class="text-muted">Bs. ${parseFloat(rep.total_ventas_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-white border rounded-4 p-3 text-center shadow-xs">
                        <span class="text-muted small">Devoluciones (${rep.cantidad_devoluciones})</span>
                        <h6 class="fw-bold text-danger mb-0 mt-1">-$${parseFloat(rep.total_devoluciones_usd).toFixed(2)}</h6>
                        <small class="text-muted">-Bs. ${parseFloat(rep.total_devoluciones_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-primary-subtle border border-primary-subtle rounded-4 p-3 text-center">
                        <span class="text-primary small fw-semibold">Efectivo Teórico</span>
                        <h6 class="fw-bold text-primary mb-0 mt-1">$${parseFloat(rep.efectivo_esperado_usd).toFixed(2)}</h6>
                        <small class="text-primary">Bs. ${parseFloat(rep.efectivo_esperado_bs).toFixed(2)}</small>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-wallet text-muted me-1"></i> Desglose por Método de Pago</h6>
            <div class="table-responsive rounded-3 border mb-3">
                <table class="table table-sm table-hover mb-0">
                    <thead class="bg-light-subtle text-dark border-bottom">
                        <tr>
                            <th>Método</th>
                            <th class="text-center">Moneda</th>
                            <th class="text-center">Transacciones</th>
                            <th class="text-end">Total Recaudado</th>
                        </tr>
                    </thead>
                    <tbody>${filasMetodos}</tbody>
                </table>
            </div>
        `;

        $("#contenidoCorteX").html(html);
    } catch (e) {
        $("#contenidoCorteX").html('<div class="alert alert-danger">Error al consultar datos del turno.</div>');
    }
};

const abrirModalCierre = async function (turnoId) {
    if (!turnoId) return;

    try {
        turnoActivoCierre = turnoId;
        $("#cierre_turno_id").val(turnoId);
        $("#monto_cierre_usd").val("0.00");
        $("#monto_cierre_bs").val("0.00");
        $("#cierre_observaciones").val("");

        const res = await $.get(`${urlBase}/turnos/${turnoId}/reporte-x`);
        const rep = res && res.data ? res.data : null;
        if (!rep) return;

        esperadoUsdActual = parseFloat(rep.efectivo_esperado_usd || 0);
        esperadoBsActual = parseFloat(rep.efectivo_esperado_bs || 0);

        $("#resumenAperturaUsd").text(`$${parseFloat(rep.monto_apertura_usd).toFixed(2)}`);
        $("#resumenVentasUsd").text(`$${parseFloat(rep.total_ventas_usd).toFixed(2)}`);
        $("#resumenEsperadoUsd").text(`$${esperadoUsdActual.toFixed(2)}`);
        $("#resumenEsperadoBs").text(`Bs. ${esperadoBsActual.toFixed(2)}`);

        $("#monto_cierre_usd").val(esperadoUsdActual.toFixed(2));
        $("#monto_cierre_bs").val(esperadoBsActual.toFixed(2));

        calcularDiferenciasCierre();

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCierreZ"));
        modal.show();
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar los totales para el arqueo de cierre.",
            });
        }
    }
};

const calcularDiferenciasCierre = function () {
    const contadoUsd = parseFloat($("#monto_cierre_usd").val() || 0);
    const contadoBs = parseFloat($("#monto_cierre_bs").val() || 0);

    const difUsd = contadoUsd - esperadoUsdActual;
    const difBs = contadoBs - esperadoBsActual;

    const $alertaUsd = $("#alertaDiferenciaUsd");
    const $alertaBs = $("#alertaDiferenciaBs");

    if (Math.abs(difUsd) < 0.001) {
        $alertaUsd.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Cuadre Exacto en USD ($0.00)</span>');
    } else if (difUsd > 0) {
        $alertaUsd.html(`<span class="text-success fw-bold"><i class="fas fa-plus-circle me-1"></i>Sobrante: +$${difUsd.toFixed(2)}</span>`);
    } else {
        $alertaUsd.html(`<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Faltante: -$${Math.abs(difUsd).toFixed(2)}</span>`);
    }

    if (Math.abs(difBs) < 0.001) {
        $alertaBs.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Cuadre Exacto en Bs. (Bs. 0.00)</span>');
    } else if (difBs > 0) {
        $alertaBs.html(`<span class="text-success fw-bold"><i class="fas fa-plus-circle me-1"></i>Sobrante: +Bs. ${difBs.toFixed(2)}</span>`);
    } else {
        $alertaBs.html(`<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Faltante: -Bs. ${Math.abs(difBs).toFixed(2)}</span>`);
    }
};

$("#formularioCierreZ").on("submit", function (e) {
    e.preventDefault();
    if (!turnoActivoCierre) return;

    const url = `${urlBase}/turnos/${turnoActivoCierre}/cerrar`;

    enviarFormulario({
        form: this,
        url: url,
        isEditar: true,
        modalSelector: "#modalCierreZ",
        tablaSelector: "#datatable_cajas",
        btnSubmit: "#modalCierreZBtnGuardar",
        textoGuardarOriginal: "Confirmar y Cerrar Turno",
        despuesDeGuardar: function () {
            if ($("#datatable_turnos").length) {
                $("#datatable_turnos").DataTable().ajax.reload(null, false);
            }
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Turno Cerrado!',
                    text: 'El cierre Z y arqueo físico se completaron correctamente.',
                    showCancelButton: true,
                    confirmButtonText: '🖨️ Imprimir Ticket Z',
                    cancelButtonText: 'Continuar',
                    confirmButtonColor: '#0f172a',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(`${urlBase}/turnos/${turnoActivoCierre}/imprimir-z`, '_blank');
                    }
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        },
    });
});
