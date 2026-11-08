const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlCatalogos = `${urlBase}/catalogos`;
const urlDetalles = `${urlBase}/`;
const urlEliminar = `${urlBase}/`;
const urlGuardar = urlBase;
const urlEditar = `${urlBase}/actualizar/`;

let urlAccion = urlGuardar;
let isEditar = false;
let idVendedorActual = null;
let catalogoTurnos = [];
let catalogoMetodos = [];
let ventasComisionesActuales = [];

$(document).ready(function () {
    crearSelect2({
        selector: "#user_id",
        modalSelector: "#modalVendedor",
        placeholder: "-- Sin usuario asignado / Vendedor externo --",
        allowClear: true,
    });
    cargarCatalogos();
    crearDataTable({
        selector: "#datatable_vendedores",
        url: urlLista,
        searchPlaceholder: "Documento, nombre, teléfono...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const n = (row.nombre || "").trim();
                    const partes = n.split(" ");
                    const iniciales = (partes[0] ? partes[0].charAt(0) : "V") + (partes[1] ? partes[1].charAt(0) : "");
                    const doc = row.documento
                        ? `<span class="badge bg-white text-dark border"><i class="fas fa-id-badge text-muted me-1"></i>${row.tipo_documento || 'V'}-${row.documento}</span>`
                        : '<span class="text-muted small">Sin documento</span>';

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1">
                                ${iniciales.toUpperCase()}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${n}</span>
                                <div>${doc}</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "telefono",
                name: "telefono",
                className: "text-center align-middle",
                render: function (data) {
                    return data
                        ? `<a href="tel:${data}" class="contacto-item phone" title="Llamar o contactar"><i class="fas fa-phone-alt"></i><span>${data}</span></a>`
                        : '<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="fas fa-minus text-muted opacity-50 me-1"></i>Sin teléfono</span>';
                },
            },
            {
                data: "correo",
                name: "correo",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<a href="mailto:${data}" class="contacto-item email" title="${data}"><i class="fas fa-envelope"></i><span class="text-truncate" style="max-width: 180px; display: inline-block; vertical-align: middle;">${data}</span></a>`
                        : '<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="fas fa-minus text-muted opacity-50 me-1"></i>Sin correo</span>';
                },
            },
            {
                data: "comision_porcentaje",
                name: "comision_porcentaje",
                className: "text-center align-middle",
                render: function (data) {
                    const comision = parseFloat(data || 0).toFixed(2);
                    return `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold"><i class="fas fa-percentage me-1"></i>${comision}%</span>`;
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data) {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill"><i class="fas fa-check-circle me-1"></i>Activo</span>';
                    }
                    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';
                },
            },
            {
                data: null,
                width: "150px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").trim().replace(/'/g, "\\'");
                    let botones = '<div class="d-flex justify-content-center gap-1">';

                    botones += `
                        <button type="button" class="btn btn-outline-warning btn-sm rounded-circle shadow-sm" onclick="irAComisiones(${row.id});" title="Ver Comisiones Pendientes" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-hand-holding-usd"></i>
                        </button>
                    `;

                    if (typeof puedeVerVendedor !== 'undefined' && puedeVerVendedor) {
                        botones += `
                            <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-eye"></i>
                            </button>
                        `;
                    }

                    if (typeof puedeEditarVendedor !== 'undefined' && puedeEditarVendedor) {
                        botones += `
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-edit"></i>
                            </button>
                        `;
                    }

                    if (typeof puedeEliminarVendedor !== 'undefined' && puedeEliminarVendedor) {
                        botones += `
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Alternar estado" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        `;
                    }

                    botones += '</div>';
                    return botones;
                },
            },
        ],
    });
});

const cargarCatalogos = async function () {
    try {
        const res = await peticionAjax({ url: urlCatalogos });
        if (res && res.success) {
            if (Array.isArray(res.usuarios)) {
                const $select = $("#user_id");
                $select.empty().append('<option value="">-- Sin usuario asignado / Vendedor externo --</option>');
                res.usuarios.forEach((usr) => {
                    const nombreCompleto = (usr.nombre || '') + (usr.apellido ? ' ' + usr.apellido : '');
                    const label = nombreCompleto.trim() || usr.email || usr.name;
                    $select.append(`<option value="${usr.id}">${usr.name} (${label})</option>`);
                });
                $select.trigger("change.select2");
            }

            if (Array.isArray(res.vendedores)) {
                const $selectVendedores = $("#filtro_vendedor_comision");
                $selectVendedores.empty().append('<option value="">-- Seleccionar Vendedor --</option>');
                res.vendedores.forEach((v) => {
                    const doc = v.documento ? ` (${v.tipo_documento || 'V'}-${v.documento})` : '';
                    $selectVendedores.append(`<option value="${v.id}">${v.nombre}${doc}</option>`);
                });
            }

            if (Array.isArray(res.turnos_abiertos)) {
                catalogoTurnos = res.turnos_abiertos;
            }

            if (Array.isArray(res.metodos_pago)) {
                catalogoMetodos = res.metodos_pago;
            }
        }
    } catch (error) {}
};

const resetearFormularioVendedor = function () {
    const $form = $("#formularioVendedor");
    $form[0].reset();
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();
    $form.find("input, select, textarea").prop("disabled", false);
    $("#tipo_documento").val("V");
    limpiarSelect2("#user_id");
    $("#comision_porcentaje").val("0.00");
};

const crear = function () {
    isEditar = false;
    idVendedorActual = null;
    urlAccion = urlGuardar;

    resetearFormularioVendedor();

    $("#modalVendedorTitulo").text("Nuevo Vendedor");
    $("#modalVendedorSubtitulo").text("Completa la información del asesor de ventas");
    $("#modalVendedorIcono").attr("class", "fas fa-user-tie text-primary fs-5");

    $("#modalVendedorBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalVendedorTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
    modal.show();
};

const llenarFormularioVendedor = function (data) {
    $("#nombre").val(data.nombre || "");
    const tipoDoc = String(data.tipo_documento || "V").replace("-", "").toUpperCase();
    $("#tipo_documento").val(tipoDoc || "V");
    $("#documento").val(data.documento || "");
    establecerValorSelect2("#user_id", data.user_id || "");
    $("#telefono").val(data.telefono || "");
    $("#correo").val(data.correo || "");
    $("#comision_porcentaje").val(parseFloat(data.comision_porcentaje || 0).toFixed(2));
};

const ver = async function (id) {
    try {
        idVendedorActual = id;
        const res = await consultarRegistro(urlDetalles, id);
        const vendedor = res && res.data ? res.data : res;
        if (!vendedor) return;

        resetearFormularioVendedor();
        llenarFormularioVendedor(vendedor);

        $("#formularioVendedor").find("input, select, textarea").prop("disabled", true);

        $("#modalVendedorTitulo").text("Detalles del Vendedor");
        $("#modalVendedorSubtitulo").text("Consulta la información del asesor de ventas");
        $("#modalVendedorIcono").attr("class", "fas fa-eye text-info fs-5");

        $("#modalVendedorBtnGuardar").prop("hidden", true);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
        modal.show();
    } catch (error) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del vendedor.",
            });
        }
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idVendedorActual = id;
        urlAccion = `${urlEditar}${id}`;
        const res = await consultarRegistro(urlDetalles, id);
        const vendedor = res && res.data ? res.data : res;
        if (!vendedor) return;

        resetearFormularioVendedor();
        llenarFormularioVendedor(vendedor);

        $("#modalVendedorTitulo").text(`Editar Vendedor: ${vendedor.nombre}`);
        $("#modalVendedorSubtitulo").text("Modifica los datos del asesor de ventas");
        $("#modalVendedorIcono").attr("class", "fas fa-user-edit text-primary fs-5");

        $("#modalVendedorBtnGuardar").prop("hidden", false).prop("disabled", false);
        $("#modalVendedorTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
        modal.show();
    } catch (error) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del vendedor.",
            });
        }
    }
};

const eliminar = function (id, nombreVendedor) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreVendedor,
        tablaSelector: "#datatable_vendedores",
        titulo: "¿Cambiar Estado del Vendedor?",
        mensaje: `Se alternará el estado de "${nombreVendedor}". Podrás reactivarlo cuando lo requieras.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, cambiar estado',
    });
};

$("#formularioVendedor").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalVendedor",
        tablaSelector: "#datatable_vendedores",
        btnSubmit: "#modalVendedorBtnGuardar",
        textoGuardarOriginal: $("#modalVendedorTextoGuardar").text(),
    });
});

const irAComisiones = function (vendedorId) {
    const tabTrigger = document.getElementById("tab-comisiones-btn");
    if (tabTrigger) {
        const tab = bootstrap.Tab.getOrCreateInstance(tabTrigger);
        tab.show();
    }
    $("#filtro_vendedor_comision").val(vendedorId);
    consultarComisionesPendientes(vendedorId);
};

const limpiarFiltrosComisiones = function () {
    $("#filtro_vendedor_comision").val("");
    $("#filtro_fecha_inicio_comision").val("");
    $("#filtro_fecha_fin_comision").val("");
    ventasComisionesActuales = [];
    $("#kpi_cantidad_ventas").text("0");
    $("#kpi_total_usd").text("$0.00");
    $("#kpi_total_bs").text("0.00 Bs");
    $("#contador_seleccionadas").text("0");
    $("#btnAbrirLiquidarComisiones").prop("disabled", true);
    $("#check_todos_comision").prop("checked", false);
    $("#tbody_comisiones_pendientes").html(`
        <tr>
            <td colspan="8" class="text-center py-4 text-muted">
                <i class="fas fa-search me-1"></i> Selecciona un vendedor para consultar comisiones pendientes.
            </td>
        </tr>
    `);
};

const consultarComisionesPendientes = async function (vendedorIdParam) {
    const vendedorId = vendedorIdParam || $("#filtro_vendedor_comision").val();
    if (!vendedorId) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "Selecciona un vendedor",
                text: "Por favor elige un vendedor para consultar sus comisiones pendientes.",
            });
        }
        return;
    }

    const fechaInicio = $("#filtro_fecha_inicio_comision").val();
    const fechaFin = $("#filtro_fecha_fin_comision").val();

    let queryParams = [];
    if (fechaInicio) queryParams.push(`fecha_inicio=${encodeURIComponent(fechaInicio)}`);
    if (fechaFin) queryParams.push(`fecha_fin=${encodeURIComponent(fechaFin)}`);
    const qs = queryParams.length ? `?${queryParams.join("&")}` : "";

    try {
        const res = await peticionAjax({
            url: `${urlBase}/${vendedorId}/comisiones-pendientes${qs}`,
            method: "GET",
        });

        if (!res || !res.success || !res.data) {
            throw new Error(res?.message || "No se pudo obtener la información de comisiones");
        }

        const data = res.data;
        ventasComisionesActuales = data.ventas || [];

        $("#kpi_cantidad_ventas").text(data.cantidad_ventas || 0);
        $("#kpi_total_usd").text(`$${parseFloat(data.total_comision_usd || 0).toFixed(2)}`);
        $("#kpi_total_bs").text(`${parseFloat(data.total_comision_bs || 0).toFixed(2)} Bs`);

        renderizarTablaComisiones(ventasComisionesActuales);
    } catch (error) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Error al consultar",
                text: error.message || "Ocurrió un error al cargar las comisiones.",
            });
        }
    }
};

const renderizarTablaComisiones = function (ventas) {
    const $tbody = $("#tbody_comisiones_pendientes");
    $tbody.empty();
    $("#check_todos_comision").prop("checked", false);

    if (!ventas || ventas.length === 0) {
        $tbody.html(`
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle text-success me-1"></i> No hay facturas con comisiones pendientes para este vendedor en el rango seleccionado.
                </td>
            </tr>
        `);
        actualizarSeleccionComisiones();
        return;
    }

    ventas.forEach((v) => {
        const clienteNombre = v.cliente ? `${v.cliente.nombre || ''} ${v.cliente.apellido || ''}`.trim() : 'Cliente General';
        const comisionPorc = parseFloat(v.comision_porcentaje || 0).toFixed(2);
        const totalUsd = parseFloat(v.total_usd || 0).toFixed(2);
        const comisionUsd = parseFloat(v.comision_monto_usd || 0).toFixed(2);
        const comisionBs = parseFloat(v.comision_monto_bs || 0).toFixed(2);
        const fecha = v.fecha_emision ? String(v.fecha_emision).substring(0, 10) : '-';

        $tbody.append(`
            <tr>
                <td class="text-center">
                    <input type="checkbox" class="form-check-input check-comision-item" value="${v.id}" data-usd="${v.comision_monto_usd}" data-bs="${v.comision_monto_bs}" onchange="actualizarSeleccionComisiones()">
                </td>
                <td>
                    <span class="fw-bold text-dark font-monospace">${v.codigo || ('#' + v.id)}</span>
                    ${v.numero_control ? `<small class="d-block text-muted">Ctrl: ${v.numero_control}</small>` : ''}
                </td>
                <td><small class="text-muted">${fecha}</small></td>
                <td><span class="fw-semibold text-dark">${clienteNombre}</span></td>
                <td class="text-end fw-semibold text-dark">$${totalUsd}</td>
                <td class="text-center"><span class="badge bg-white text-dark border">${comisionPorc}%</span></td>
                <td class="text-end fw-bold text-success">$${comisionUsd}</td>
                <td class="text-end fw-bold text-primary">${comisionBs} Bs</td>
            </tr>
        `);
    });

    actualizarSeleccionComisiones();
};

const toggleTodosComision = function (element) {
    const isChecked = $(element).is(":checked");
    $(".check-comision-item").prop("checked", isChecked);
    actualizarSeleccionComisiones();
};

const actualizarSeleccionComisiones = function () {
    let seleccionados = 0;
    let sumUsd = 0;
    let sumBs = 0;

    $(".check-comision-item:checked").each(function () {
        seleccionados++;
        sumUsd += parseFloat($(this).data("usd") || 0);
        sumBs += parseFloat($(this).data("bs") || 0);
    });

    $("#contador_seleccionadas").text(seleccionados);
    $("#resumen_seleccion_texto").text(
        seleccionados > 0
            ? `Seleccionadas: ${seleccionados} facturas | Total: $${sumUsd.toFixed(2)} USD / ${sumBs.toFixed(2)} Bs`
            : "Selecciona las facturas a incluir en la liquidación."
    );

    const $btnLiquidar = $("#btnAbrirLiquidarComisiones");
    if ($btnLiquidar.length) {
        $btnLiquidar.prop("disabled", seleccionados === 0);
    }
};

const toggleEgresoCaja = function (element) {
    const check = $(element).is(":checked");
    const $seccion = $("#seccion_egreso_caja");
    if (check) {
        $seccion.removeClass("d-none");
    } else {
        $seccion.addClass("d-none");
    }
};

const abrirModalLiquidar = function () {
    const seleccionados = $(".check-comision-item:checked");
    if (seleccionados.length === 0) {
        return;
    }

    const vendedorNombre = $("#filtro_vendedor_comision option:selected").text();
    let sumUsd = 0;
    let sumBs = 0;

    seleccionados.each(function () {
        sumUsd += parseFloat($(this).data("usd") || 0);
        sumBs += parseFloat($(this).data("bs") || 0);
    });

    $("#modal_liquidar_vendedor").text(vendedorNombre || "Vendedor seleccionado");
    $("#modal_liquidar_facturas").text(seleccionados.length);
    $("#modal_liquidar_usd").text(`$${sumUsd.toFixed(2)}`);
    $("#modal_liquidar_bs").text(`${sumBs.toFixed(2)} Bs`);

    $("#check_egreso_caja").prop("checked", false);
    $("#seccion_egreso_caja").addClass("d-none");

    const $selectTurnos = $("#liquidar_caja_turno_id");
    $selectTurnos.empty().append('<option value="">-- Seleccionar Turno Abierto --</option>');
    if (Array.isArray(catalogoTurnos) && catalogoTurnos.length > 0) {
        catalogoTurnos.forEach((t) => {
            const cajaNombre = t.caja ? t.caja.nombre : `Caja #${t.caja_id}`;
            $selectTurnos.append(`<option value="${t.id}">Turno #${t.id} - ${cajaNombre} (${t.fecha_apertura} ${t.hora_apertura})</option>`);
        });
    }

    const $selectMetodos = $("#liquidar_metodo_pago_id");
    $selectMetodos.empty().append('<option value="">-- Método --</option>');
    if (Array.isArray(catalogoMetodos) && catalogoMetodos.length > 0) {
        catalogoMetodos.forEach((m) => {
            $selectMetodos.append(`<option value="${m.id}">${m.nombre}</option>`);
        });
    }

    $("#liquidar_moneda").val("USD");
    $("#liquidar_observaciones").val("");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalLiquidarComisiones"));
    modal.show();
};

$("#formLiquidarComisiones").on("submit", async function (e) {
    e.preventDefault();

    const vendedorId = $("#filtro_vendedor_comision").val();
    if (!vendedorId) return;

    const ventaIds = [];
    $(".check-comision-item:checked").each(function () {
        ventaIds.push(parseInt($(this).val(), 10));
    });

    if (ventaIds.length === 0) {
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "Sin selección",
                text: "Debes seleccionar al menos una factura para liquidar.",
            });
        }
        return;
    }

    const registrarEgreso = $("#check_egreso_caja").is(":checked");
    const payload = {
        _token: $('meta[name="csrf-token"]').attr("content"),
        venta_ids: ventaIds,
        registrar_egreso_caja: registrarEgreso ? 1 : 0,
        caja_turno_id: registrarEgreso ? $("#liquidar_caja_turno_id").val() : null,
        metodo_pago_id: registrarEgreso ? $("#liquidar_metodo_pago_id").val() : null,
        moneda: registrarEgreso ? $("#liquidar_moneda").val() : null,
        observaciones: $("#liquidar_observaciones").val() || null,
    };

    const $btn = $("#modalLiquidarComisiones").find('button[type="submit"]');
    const originalText = $btn.text();
    $btn.prop("disabled", true).text("Liquidando...");

    try {
        const res = await peticionAjax({
            url: `${urlBase}/${vendedorId}/liquidar`,
            method: "POST",
            data: payload,
        });

        $btn.prop("disabled", false).text(originalText);

        if (res && res.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById("modalLiquidarComisiones"));
            if (modal) modal.hide();

            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "success",
                    title: "Liquidación Exitosa",
                    text: res.message || "Comisiones liquidadas satisfactoriamente.",
                });
            }

            consultarComisionesPendientes(vendedorId);
            if ($.fn.DataTable.isDataTable("#datatable_vendedores")) {
                $("#datatable_vendedores").DataTable().ajax.reload(null, false);
            }
        } else {
            throw new Error(res?.message || "No se pudo completar la liquidación.");
        }
    } catch (error) {
        $btn.prop("disabled", false).text(originalText);
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "error",
                title: "Error al liquidar",
                text: error.message || "Ocurrió un error al procesar la liquidación.",
            });
        }
    }
});
