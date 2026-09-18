const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlCompleta;
let isEditar = false;
let filtroStockActual = "";
let filtroTipoActual = "";

$(document).ready(function () {
    iniciarDatatable();
});

function formatearMonto(monto) {
    let num = parseFloat(monto);
    if (isNaN(num)) num = 0;
    return num.toLocaleString("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_inventario")) {
        $("#datatable_inventario").DataTable().destroy();
    }

    $("#datatable_inventario").DataTable({
        ajax: {
            url: urlLista,
            data: function (d) {
                d.filtro_stock = filtroStockActual;
                d.filtro_tipo = filtroTipoActual;
            },
        },
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
                    const esServicio = row.tipo === "servicio";
                    const icon = esServicio ? "fa-tag" : "fa-box";
                    const colorClass = esServicio ? "bg-info bg-opacity-10 text-info" : "bg-primary bg-opacity-10 text-primary";
                    return `
                        <div class="d-flex align-items-center ps-2">
                            <div class="${colorClass} rounded-3 d-inline-flex justify-content-center align-items-center me-3 shadow-xs" style="width: 38px; height: 38px; min-width: 38px;">
                                <i class="fas ${icon}"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block h6 mb-0">${data}</span>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "tipo",
                name: "tipo",
                className: "text-center",
                render: function (data) {
                    if (data === "servicio") {
                        return `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-tag me-1"></i>Servicio</span>`;
                    }
                    return `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-box me-1"></i>Producto</span>`;
                },
            },
            {
                data: "precio_venta",
                name: "precio_venta",
                className: "text-center",
                render: function (data, type, row) {
                    const esServicio = row.tipo === "servicio";
                    const seVende = row.se_vende == 1 || row.se_vende === true || esServicio;
                    if (seVende && data !== null && data !== undefined) {
                        return `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold fs-6">$${formatearMonto(data)}</span>`;
                    }
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 0.78rem;"><i class="fas fa-lock me-1 text-secondary"></i>Uso Interno</span>`;
                },
            },
            {
                data: "cantidad",
                name: "cantidad",
                className: "text-center",
                render: function (data, type, row) {
                    if (row.tipo === "servicio") {
                        return `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i>Disponible Siempre</span>`;
                    }
                    const cant = parseFloat(data) || 0;
                    const unidad = row.unidadMedida || row.unidad_medida || "Unidades";
                    
                    if (cant <= 0) {
                        return `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-times-circle me-1"></i>0 ${unidad} (Agotado)</span>`;
                    } else if (cant <= 5) {
                        return `<span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>${cant} ${unidad} (Stock Bajo)</span>`;
                    } else {
                        return `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i>${cant} ${unidad}</span>`;
                    }
                },
            },
            {
                data: "descripcion",
                name: "descripcion",
                render: function (data) {
                    if (data && data.trim().length > 0) {
                        return `<span class="text-secondary small">${data}</span>`;
                    }
                    return `<span class="text-muted small fst-italic">Sin descripción</span>`;
                },
            },
            {
                data: null,
                width: "150px",
                className: "text-end pe-3",
                orderable: false,
                render: function (data, type, row) {
                    const esServicio = row.tipo === "servicio";
                    const btnKardex = esServicio
                        ? `<button type="button" class="btn btn-outline-secondary btn-sm rounded-circle shadow-xs opacity-50" disabled title="Los servicios no generan Kardex" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-history"></i>
                           </button>`
                        : `<button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-xs" onclick="kardex(${row.id});" title="Historial (Kardex)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-history"></i>
                           </button>`;

                    return `
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-xs" onclick="ver(${row.id});" title="Ver Detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${btnKardex}
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-xs" onclick="editar(${row.id});" title="Editar Registro" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminar(${row.id});" title="Eliminar Registro" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Buscar producto, reactivo, servicio...",
            zeroRecords: "No se encontraron registros coincidentes",
            emptyTable: "No hay productos o servicios registrados",
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
            sProcessing: "Cargando registros...",
        },
    });
}

function filtrarRapidoTipo(tipo, btn) {
    filtroTipoActual = tipo;
    $("#filtros-tipo-rapido button").removeClass("active");
    $(btn).addClass("active");

    if (tipo === "servicio") {
        $("#seccion-filtro-stock").hide();
    } else {
        $("#seccion-filtro-stock").show();
    }

    $("#datatable_inventario").DataTable().ajax.reload();
}

function filtrarRapidoStock(estado, btn) {
    filtroStockActual = estado;
    $("#filtros-inventario-rapido button").removeClass("active");
    $(btn).addClass("active");
    $("#datatable_inventario").DataTable().ajax.reload();
}

function cambiarTipoRegistro(tipo) {
    if (tipo === "servicio") {
        $(".campo-producto").hide();
        $(".campo-servicio").show();
        $("#icon-nombre-item").html('<i class="fas fa-tag"></i>');
        $("#nombre").attr("placeholder", "Ej. Toma de muestra a domicilio, Curación simple, Honorarios médicos...");
        $("#seccion_titulo_info").html('<i class="fas fa-tag me-1"></i> Información del Servicio Facturable');
        $("#cantidad").prop("required", false);
        $("#unidad_medida").prop("required", false);
        $("#bloque-precio-venta").show();
        $("#precio_venta").prop("required", true);
    } else {
        $(".campo-producto").show();
        $(".campo-servicio").hide();
        $("#icon-nombre-item").html('<i class="fas fa-box"></i>');
        $("#nombre").attr("placeholder", "Ej. Tubos de ensayo con EDTA, Guantes de látex, Reactivo Glucosa...");
        $("#seccion_titulo_info").html('<i class="fas fa-box me-1"></i> Información del Producto Físico');
        $("#cantidad").prop("required", true);
        $("#unidad_medida").prop("required", true);
        
        let seVende = $("#se_vende").is(":checked");
        togglePrecioVentaProducto(seVende);
    }
}

function togglePrecioVentaProducto(checked) {
    if (checked) {
        $("#bloque-precio-venta").show();
        $("#precio_venta").prop("required", true);
    } else {
        $("#bloque-precio-venta").hide();
        $("#precio_venta").prop("required", false).val("");
    }
}

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

const crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;

    $("#modalInventario").modal("show");
    $("#tituloModal").html('<i class="fas fa-box-open me-2"></i> Nuevo Registro');
    $("#colorModal").attr(
        "class",
        "modal-header border-0 bg-primary text-white rounded-top-4 pb-3",
    );

    $("#formularioInventario").trigger("reset");
    $("#formularioInventario")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#selector-tipo-registro input").prop("disabled", false);

    // Default to producto
    $("#tipo_producto").prop("checked", true);
    cambiarTipoRegistro("producto");

    $("#guardarModal")
        .prop("hidden", false)
        .html('<i class="fas fa-save me-1"></i> Guardar Registro');
};

const ver = async function (id) {
    try {
        const data = await consultar(id);
        const esServicio = data.tipo === "servicio";

        $("#modalInventario").modal("show");
        $("#tituloModal").html(`<i class="fas fa-info-circle me-2"></i> Detalles del ${esServicio ? 'Servicio' : 'Producto'}: ${data.nombre}`);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-secondary text-white rounded-top-4 pb-3",
        );

        $("#formularioInventario")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#guardarModal").prop("hidden", true);

        llenarDatosBasicos(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del registro.",
        });
    }
};

const kardex = async function (id) {
    try {
        const prod = await consultar(id);
        if (prod.tipo === "servicio") {
            notificacion.fire({
                icon: "info",
                title: "Servicio sin Kardex",
                text: "Los servicios facturables no manejan stock ni movimientos de Kardex.",
            });
            return;
        }

        const unidad = prod.unidadMedida || prod.unidad_medida || "";
        $('#kardexProductoNombre').text(`${prod.nombre} (${prod.cantidad} ${unidad})`);
        
        $.ajax({
            url: urlCompleta + '/kardex/' + id,
            type: 'GET',
            success: function(res) {
                let html = '';
                if(!res || res.length === 0) {
                    html = `
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <div class="opacity-50 mb-2"><i class="fas fa-inbox fa-3x"></i></div>
                                <span class="fw-semibold">No se han registrado movimientos de kardex para este ítem.</span>
                            </td>
                        </tr>
                    `;
                } else {
                    res.forEach(mov => {
                        const esEntrada = mov.tipo === 'Entrada';
                        const badgeClass = esEntrada 
                            ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' 
                            : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                        const iconTipo = esEntrada ? '<i class="fas fa-arrow-down me-1"></i>' : '<i class="fas fa-arrow-up me-1"></i>';
                        
                        let fechaStr = '-';
                        if (mov.created_at) {
                            let date = new Date(mov.created_at);
                            fechaStr = `<span class="fw-semibold text-dark">${date.toLocaleDateString()}</span> <span class="text-muted small">${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>`;
                        }

                        const userName = mov.user ? mov.user.name : 'Sistema';
                        
                        html += `
                            <tr>
                                <td class="ps-3">${fechaStr}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle text-muted d-inline-flex align-items-center justify-content-center me-2" style="width: 26px; height: 26px;">
                                            <i class="fas fa-user small"></i>
                                        </div>
                                        <span class="fw-medium text-dark">${userName}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge ${badgeClass} rounded-pill px-3 py-1 fw-bold">
                                        ${iconTipo}${mov.tipo}
                                    </span>
                                </td>
                                <td class="text-center fw-bold ${esEntrada ? 'text-success' : 'text-danger'}">
                                    ${esEntrada ? '+' : '-'}${parseFloat(mov.cantidad) || 0} ${unidad}
                                </td>
                                <td><span class="text-secondary">${mov.motivo || '-'}</span></td>
                                <td class="text-end pe-3">
                                    <span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">
                                        ${mov.referencia || 'N/A'}
                                    </span>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#tablaKardex tbody').html(html);
                $('#modalKardex').modal('show');
            }
        });
    } catch (error) {
        notificacion.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el historial de kardex.' });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        urlAccion = urlEditar + id;
        const data = await consultar(id);
        const esServicio = data.tipo === "servicio";

        $("#modalInventario").modal("show");
        $("#tituloModal").html(`<i class="fas fa-edit me-2"></i> Editar ${esServicio ? 'Servicio' : 'Producto'}: ${data.nombre}`);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-dark text-white rounded-top-4 pb-3",
        );

        $("#formularioInventario")
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
            text: "No se pudo cargar la información del registro.",
        });
    }
};

const llenarDatosBasicos = (data) => {
    const tipo = data.tipo || "producto";
    if (tipo === "servicio") {
        $("#tipo_servicio").prop("checked", true);
    } else {
        $("#tipo_producto").prop("checked", true);
    }
    cambiarTipoRegistro(tipo);

    $("#nombre").val(data.nombre);
    $("#cantidad").val(data.cantidad);
    const unidad = data.unidadMedida || data.unidad_medida;
    $("#unidad_medida").val(unidad).trigger("change");
    
    const seVende = data.se_vende == 1 || data.se_vende === true;
    $("#se_vende").prop("checked", seVende);
    
    if (tipo === "producto") {
        togglePrecioVentaProducto(seVende);
    }

    if (data.precio_venta !== null && data.precio_venta !== undefined) {
        $("#precio_venta").val(parseFloat(data.precio_venta) || 0);
    } else {
        $("#precio_venta").val("");
    }

    $("#descripcion").val(data.descripcion);
};

const eliminar = async function (id) {
    try {
        const data = await consultar(id);
        const tipoLabel = data.tipo === "servicio" ? "servicio" : "producto";
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará el ${tipoLabel} <strong class='text-danger'>${data.nombre}</strong> del catálogo activo.`,
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
                    success: function (res) {
                        $("#datatable_inventario")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Registro Eliminado",
                            text: res.message,
                        });
                    },
                    error: function(err) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: err.responseJSON?.message || "No se pudo eliminar el registro",
                        });
                    }
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: "error", title: "Error" });
    }
};

$("#formularioInventario").on("submit", function (e) {
    e.preventDefault();

    const tipo = $("input[name='tipo']:checked").val() || "producto";

    if ($("#nombre").val().trim().length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "Nombre muy corto",
            text: "El nombre del ítem debe tener al menos 2 caracteres.",
        });
    }

    if (tipo === "producto") {
        if ($("#cantidad").val() === "" || parseFloat($("#cantidad").val()) < 0) {
            return notificacion.fire({
                icon: "warning",
                title: "Cantidad inválida",
                text: "Por favor especifique una cantidad de stock válida.",
            });
        }

        if (!$("#unidad_medida").val()) {
            return notificacion.fire({
                icon: "warning",
                title: "Seleccione una unidad de medida",
            });
        }

        if ($("#se_vende").is(":checked")) {
            const pVenta = parseFloat($("#precio_venta").val());
            if (isNaN(pVenta) || pVenta < 0) {
                return notificacion.fire({
                    icon: "warning",
                    title: "Precio de venta requerido",
                    text: "Por favor indique el precio de venta en USD para este producto.",
                });
            }
        }
    } else {
        const pVenta = parseFloat($("#precio_venta").val());
        if (isNaN(pVenta) || pVenta < 0) {
            return notificacion.fire({
                icon: "warning",
                title: "Precio de venta requerido",
                text: "Por favor indique el precio de venta en USD para este servicio.",
            });
        }
    }

    let formData = new FormData(this);

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
        },
        success: function (response) {
            if (response.success) {
                $("#modalInventario").modal("hide");
                $("#datatable_inventario").DataTable().ajax.reload(null, false);
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
            const txt = isEditar ? "Actualizar Cambios" : "Guardar Registro";
            btn.prop("disabled", false).html(
                `<i class="fas fa-save me-1"></i> ${txt}`,
            );
        },
    });
});

