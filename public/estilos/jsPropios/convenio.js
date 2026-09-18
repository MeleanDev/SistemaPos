const urlCompleta = window.location.origin + "/convenios";
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let convenioActualId = null;
let matrizCatalogo = [];
let convenioActualData = null;

$(document).ready(function () {
    iniciarDatatable();
});

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_convenio")) {
        $("#datatable_convenio").DataTable().destroy();
    }

    $("#datatable_convenio").DataTable({
        ajax: urlLista,
        responsive: true,
        processing: true,
        serverSide: true,
        order: [[0, "asc"]],
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],
        columns: [
            {
                data: "nombre",
                name: "nombre",
                render: function (data, type, row) {
                    const desc = row.descripcion ? `<small class="text-secondary d-block text-truncate fw-medium mt-1" style="max-width: 320px;">${row.descripcion}</small>` : '';
                    return `
                        <div class="d-flex align-items-center ps-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex justify-content-center align-items-center me-3 shadow-xs" style="width: 38px; height: 38px; min-width: 38px;">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block h6 mb-0">${data}</span>
                                ${desc}
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "porcentaje_general",
                name: "porcentaje_general",
                className: "text-center",
                render: function (data) {
                    const pct = parseFloat(data) || 0;
                    if (pct > 0) {
                        return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.82rem;"><i class="fas fa-arrow-down me-1"></i>${pct}% Descuento</span>`;
                    }
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.82rem;"><i class="fas fa-tag me-1"></i>Tarifa Base (0%)</span>`;
                },
            },
            {
                data: "precios_count",
                name: "precios_count",
                className: "text-center",
                searchable: false,
                render: function (data) {
                    const cant = parseInt(data) || 0;
                    if (cant > 0) {
                        return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff; font-size: 0.82rem;"><i class="fas fa-tags me-1"></i>${cant} ${cant === 1 ? 'precio fijo' : 'precios fijos'}</span>`;
                    }
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1; font-size: 0.82rem;">Sin precios fijos</span>`;
                },
            },
            {
                data: "pacientes_count",
                name: "pacientes_count",
                className: "text-center",
                searchable: false,
                render: function (data) {
                    const cant = parseInt(data) || 0;
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.82rem;"><i class="fas fa-users me-1"></i>${cant} ${cant === 1 ? 'paciente' : 'pacientes'}</span>`;
                },
            },
            {
                data: "es_predeterminado",
                name: "es_predeterminado",
                className: "text-center",
                render: function (data) {
                    if (data == 1 || data === true) {
                        return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #fefce8; color: #a16207; border: 1px solid #fde047; font-size: 0.82rem;"><i class="fas fa-star me-1 text-warning"></i>Predeterminado</span>`;
                    }
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1; font-size: 0.82rem;">Opcional</span>`;
                },
            },
            {
                data: null,
                width: "180px",
                className: "text-end pe-3",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-xs" onclick="abrirMatrizPrecios(${row.id});" title="Configurar Precios por Examen" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-dollar-sign"></i>
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-xs" onclick="ver(${row.id});" title="Ver Detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-xs" onclick="editar(${row.id});" title="Editar Tarifario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminar(${row.id});" title="Eliminar Tarifario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Buscar tarifario, seguro, clínica...",
            zeroRecords: "No se encontraron convenios coincidentes",
            emptyTable: "No hay tarifarios registrados en el sistema",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 al 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros)",
            oPaginate: {
                sFirst: '<i class="fas fa-angle-double-left"></i>',
                sLast: '<i class="fas fa-angle-double-right"></i>',
                sNext: '<i class="fas fa-angle-right"></i>',
                sPrevious: '<i class="fas fa-angle-left"></i>',
            },
            sProcessing: "Cargando tarifarios...",
        },
    });
}

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

const crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;

    $("#modalConvenio").modal("show");
    $("#tituloModal").html('<i class="fas fa-handshake me-2"></i> Nuevo Tarifario / Convenio');
    $("#colorModal").attr(
        "class",
        "modal-header border-0 bg-primary text-white rounded-top-4 pb-3",
    );

    $("#formularioConvenio").trigger("reset");
    $("#formularioConvenio")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#guardarModal")
        .prop("hidden", false)
        .html('<i class="fas fa-save me-1"></i> Guardar Tarifario');
};

const ver = async function (id) {
    try {
        const res = await consultar(id);
        const data = res.data;

        $("#modalConvenio").modal("show");
        $("#tituloModal").html('<i class="fas fa-info-circle me-2"></i> Detalles: ' + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-secondary text-white rounded-top-4 pb-3",
        );

        $("#formularioConvenio")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#guardarModal").prop("hidden", true);

        llenarDatosBasicos(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del tarifario.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        urlAccion = urlEditar + id;
        const res = await consultar(id);
        const data = res.data;

        $("#modalConvenio").modal("show");
        $("#tituloModal").html('<i class="fas fa-edit me-2"></i> Editar: ' + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-dark text-white rounded-top-4 pb-3",
        );

        $("#formularioConvenio")
            .find("input, select, textarea")
            .prop("disabled", false);
        $("#guardarModal")
            .prop("hidden", false)
            .html('<i class="fas fa-save me-1"></i> Actualizar Cambios');

        llenarDatosBasicos(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del tarifario.",
        });
    }
};

const llenarDatosBasicos = (data) => {
    $("#nombre").val(data.nombre);
    $("#descripcion").val(data.descripcion || "");
    $("#porcentaje_general").val(data.porcentaje_general || 0);
    $("#es_predeterminado").prop("checked", data.es_predeterminado == 1 || data.es_predeterminado === true);
};

const eliminar = async function (id) {
    try {
        const res = await consultar(id);
        const data = res.data;
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se inhabilitará el tarifario / convenio <strong class='text-danger'>${data.nombre}</strong>. Los pacientes vinculados mantendrán sus registros históricos.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="fas fa-trash me-1"></i> Sí, eliminar',
            cancelButtonText: "Cancelar",
            customClass: {
                confirmButton: 'rounded-pill px-4',
                cancelButton: 'rounded-pill px-4'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlEliminar + id,
                    type: "DELETE",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        $("#datatable_convenio").DataTable().ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Tarifario Eliminado",
                            text: response.message,
                        });
                    },
                    error: function(err) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: err.responseJSON?.message || "No se pudo eliminar el tarifario",
                        });
                    }
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: "error", title: "Error" });
    }
};

$("#formularioConvenio").on("submit", function (e) {
    e.preventDefault();

    if ($("#nombre").val().trim().length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "El nombre del tarifario es obligatorio",
        });
    }

    let formData = new FormData(this);
    if (!$("#es_predeterminado").is(":checked")) {
        formData.set("es_predeterminado", "0");
    }

    const btn = $("#guardarModal");
    btn.prop("disabled", true).html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...',
    );

    $.ajax({
        url: urlAccion,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        headers: {
            Accept: "application/json",
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                $("#modalConvenio").modal("hide");
                $("#datatable_convenio").DataTable().ajax.reload(null, false);
                notificacion.fire({
                    icon: "success",
                    title: "Operación Exitosa",
                    text: response.message,
                });
            } else {
                notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            let mensajeError =
                xhr.responseJSON?.message || "Error al procesar la solicitud";
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                mensajeError = Object.values(xhr.responseJSON.errors)
                    .map((err) => err.join("<br>"))
                    .join("<br>");
            }
            notificacion.fire({
                icon: "error",
                title: "Error de Validación",
                html: mensajeError,
            });
        },
        complete: function () {
            const txt = isEditar ? "Actualizar Cambios" : "Guardar Tarifario";
            btn.prop("disabled", false).html(
                `<i class="fas fa-save me-1"></i> ${txt}`,
            );
        },
    });
});

// ==========================================
// MATRIZ DE PRECIOS ESPECIALES
// ==========================================

async function abrirMatrizPrecios(id) {
    convenioActualId = id;
    $("#modalPreciosMatriz").modal("show");
    $("#cuerpoMatrizPrecios").html(`
        <tr>
            <td colspan="6" class="text-center py-5 text-muted">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="fw-semibold">Cargando catálogo de precios...</div>
            </td>
        </tr>
    `);
    $("#filtroPreciosMatriz").val("");
    $("#filtroTipoMatriz").val("todos");

    try {
        const response = await $.ajax({
            url: `${urlCompleta}/${id}/catalogo-precios`,
            type: "GET",
            dataType: "json"
        });

        if (response.success) {
            convenioActualData = response.data.convenio;
            $("#subtituloModalPrecios").html(`
                Convenio: <strong class="text-white">${convenioActualData.nombre}</strong> | Descuento Base: <span class="badge bg-light text-dark">${parseFloat(convenioActualData.porcentaje_general || 0)}%</span>
            `);

            // Unir exámenes y perfiles
            matrizCatalogo = [
                ...response.data.examenes.map(item => ({ ...item, modificado: item.precio_especifico !== null })),
                ...response.data.perfiles.map(item => ({ ...item, modificado: item.precio_especifico !== null }))
            ];

            renderizarTablaPrecios();
        }
    } catch (err) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar el catálogo de precios del convenio.",
        });
    }
}

function renderizarTablaPrecios() {
    const busqueda = $("#filtroPreciosMatriz").val().toLowerCase().trim();
    const filtroTipo = $("#filtroTipoMatriz").val();

    let filtrados = matrizCatalogo.filter(item => {
        const coincideNombre = item.nombre.toLowerCase().includes(busqueda) || item.categoria.toLowerCase().includes(busqueda);
        if (!coincideNombre) return false;

        if (filtroTipo === "examen" && item.tipo !== "examen") return false;
        if (filtroTipo === "perfil" && item.tipo !== "perfil") return false;
        if (filtroTipo === "modificados" && (item.precio_especifico === null || item.precio_especifico === '')) return false;

        return true;
    });

    $("#contadorMostrados").text(filtrados.length);

    if (filtrados.length === 0) {
        $("#cuerpoMatrizPrecios").html(`
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-search me-1"></i> No se encontraron servicios con el filtro aplicado.
                </td>
            </tr>
        `);
        return;
    }

    let html = "";
    filtrados.forEach((item) => {
        const indexReal = matrizCatalogo.findIndex(x => x.tipo === item.tipo && x.id === item.id);
        const valorInput = item.precio_especifico !== null && item.precio_especifico !== undefined ? item.precio_especifico : "";
        const esEspecial = valorInput !== "";

        let badgeTipo = '';
        if (item.tipo === 'perfil') {
            badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d; font-size: 0.78rem;"><i class="fas fa-cubes me-1"></i>PERFIL</span>`;
        } else {
            const cat = (item.categoria || '').toUpperCase();
            if (cat.includes('HEMATO')) {
                badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; font-size: 0.78rem;"><i class="fas fa-tint me-1"></i>${item.categoria}</span>`;
            } else if (cat.includes('BIOQUIM') || cat.includes('QUIM')) {
                badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.78rem;"><i class="fas fa-flask me-1"></i>${item.categoria}</span>`;
            } else if (cat.includes('INMUNO') || cat.includes('SERO')) {
                badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #f5f3ff; color: #6d28d9; border: 1px solid #c4b5fd; font-size: 0.78rem;"><i class="fas fa-shield-alt me-1"></i>${item.categoria}</span>`;
            } else if (cat.includes('COPRO') || cat.includes('ORINA') || cat.includes('URO')) {
                badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #fff7ed; color: #c2410c; border: 1px solid #fdba74; font-size: 0.78rem;"><i class="fas fa-microscope me-1"></i>${item.categoria}</span>`;
            } else {
                badgeTipo = `<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 0.78rem;"><i class="fas fa-vial me-1"></i>${item.categoria}</span>`;
            }
        }

        const badgeFinalHtml = esEspecial
            ? `<span class="badge rounded-pill px-3 py-1.5 fw-bold font-monospace shadow-xs" style="background-color: #10b981; color: #ffffff; border: 1px solid #059669; font-size: 0.92rem;" id="badge_final_${indexReal}">$${parseFloat(item.precio_final).toFixed(2)}</span>`
            : `<span class="badge rounded-pill px-3 py-1.5 fw-bold font-monospace" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #93c5fd; font-size: 0.92rem;" id="badge_final_${indexReal}">$${parseFloat(item.precio_final).toFixed(2)}</span>`;

        const accionHtml = esEspecial
            ? `<button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" 
                    title="Restablecer a precio general" onclick="restablecerPrecioItem(${indexReal})" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="fas fa-undo"></i>
                </button>`
            : `<span class="badge rounded-pill px-2.5 py-1 small fw-semibold" style="background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0;">Estándar</span>`;

        html += `
            <tr style="${esEspecial ? 'background-color: #f0fdf4 !important;' : ''}">
                <td class="ps-3 fw-bold text-dark">
                    ${item.nombre}
                </td>
                <td>
                    ${badgeTipo}
                </td>
                <td class="text-center fw-bold text-dark font-monospace fs-6">
                    $${parseFloat(item.precio_base).toFixed(2)}
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm mx-auto shadow-xs" style="max-width: 145px;">
                        <span class="input-group-text bg-white ${esEspecial ? 'border-success text-success fw-bold' : 'border-secondary border-opacity-25 text-dark fw-bold'}">$</span>
                        <input type="number" step="0.01" min="0" 
                            class="form-control text-center fw-bold font-monospace bg-white ${esEspecial ? 'border-success text-success' : 'border-secondary border-opacity-25 text-dark'}" 
                            value="${valorInput}" 
                            placeholder="Base (${parseFloat(item.precio_base).toFixed(2)})"
                            onchange="actualizarPrecioItem(${indexReal}, this.value)"
                            onkeyup="actualizarPrecioItem(${indexReal}, this.value)">
                    </div>
                </td>
                <td class="text-center">
                    ${badgeFinalHtml}
                </td>
                <td class="text-end pe-3">
                    ${accionHtml}
                </td>
            </tr>
        `;
    });

    $("#cuerpoMatrizPrecios").html(html);
}

function filtrarTablaPrecios() {
    renderizarTablaPrecios();
}

function actualizarPrecioItem(index, valor) {
    const item = matrizCatalogo[index];
    if (!item) return;

    if (valor === "" || valor === null || isNaN(parseFloat(valor))) {
        item.precio_especifico = null;
        // Calcular según descuento general
        const pct = parseFloat(convenioActualData.porcentaje_general || 0);
        item.precio_final = roundToTwo(item.precio_base * (1 - (pct / 100)));
    } else {
        const valNum = Math.max(0, parseFloat(valor));
        item.precio_especifico = valNum;
        item.precio_final = valNum;
    }

    $(`#badge_final_${index}`).text(`$${item.precio_final.toFixed(2)}`);
}

function restablecerPrecioItem(index) {
    actualizarPrecioItem(index, "");
    renderizarTablaPrecios();
}

function roundToTwo(num) {
    return +(Math.round(num + "e+2") + "e-2");
}

function guardarMatrizPrecios() {
    if (!convenioActualId) return;

    const btn = $("#btnGuardarMatriz");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando Precios...');

    const payload = {
        items: matrizCatalogo.map(item => ({
            id: item.id,
            tipo: item.tipo,
            precio: item.precio_especifico !== null && item.precio_especifico !== "" ? item.precio_especifico : null
        }))
    };

    $.ajax({
        url: `${urlCompleta}/${convenioActualId}/guardar-precios`,
        type: "POST",
        data: JSON.stringify(payload),
        contentType: "application/json",
        dataType: "json",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            Accept: "application/json"
        },
        success: function (response) {
            $("#modalPreciosMatriz").modal("hide");
            $("#datatable_convenio").DataTable().ajax.reload(null, false);
            notificacion.fire({
                icon: "success",
                title: "Precios Guardados",
                text: response.message,
            });
        },
        error: function (xhr) {
            notificacion.fire({
                icon: "error",
                title: "Error",
                text: xhr.responseJSON?.message || "No se pudieron guardar los precios especiales.",
            });
        },
        complete: function () {
            btn.prop("disabled", false).html('<i class="fas fa-save me-1"></i> Guardar Todos los Precios');
        }
    });
}
