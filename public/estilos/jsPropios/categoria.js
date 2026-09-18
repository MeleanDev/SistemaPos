const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlCompleta;
let isEditar = false;

$(document).ready(function () {
    iniciarDatatable();
});

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_categoria")) {
        $("#datatable_categoria").DataTable().destroy();
    }

    $("#datatable_categoria").DataTable({
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
                render: function (data) {
                    return `
                        <div class="d-flex align-items-center ps-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex justify-content-center align-items-center me-3 shadow-xs" style="width: 38px; height: 38px; min-width: 38px;">
                                <i class="fas fa-tags"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block h6 mb-0">${data}</span>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "examenes_count",
                name: "examenes_count",
                className: "text-center",
                searchable: false,
                render: function (data) {
                    const cant = parseInt(data) || 0;
                    if (cant > 0) {
                        return `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-flask me-1"></i>${cant} ${cant === 1 ? 'examen' : 'exámenes'}</span>`;
                    }
                    return `<span class="badge bg-light text-muted border rounded-pill px-3 py-1 small"><i class="fas fa-flask me-1 text-muted"></i>0 exámenes</span>`;
                },
            },
            {
                data: "created_at",
                name: "created_at",
                className: "text-center",
                render: function (data) {
                    if (!data) return '<span class="text-muted">-</span>';
                    const date = new Date(data);
                    return `
                        <span class="fw-semibold text-dark d-block">${date.toLocaleDateString()}</span>
                        <span class="text-muted small">${date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    `;
                },
            },
            {
                data: null,
                width: "160px",
                className: "text-end pe-3",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/categorias/${row.id}/imprimir-examenes" target="_blank" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" title="Descargar Exámenes en PDF" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-pdf"></i>
                        </a>
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-xs" onclick="ver(${row.id});" title="Ver Detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-xs" onclick="editar(${row.id});" title="Editar Área" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminar(${row.id});" title="Eliminar Área" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Buscar área, especialidad...",
            zeroRecords: "No se encontraron áreas coincidentes",
            emptyTable: "No hay áreas registradas en el sistema",
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
            sProcessing: "Cargando áreas...",
        },
    });
}

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

const crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;

    $("#modalCategoria").modal("show");
    $("#tituloModal").html('<i class="fas fa-tags me-2"></i> Nueva Área / Especialidad');
    $("#colorModal").attr(
        "class",
        "modal-header border-0 bg-primary text-white rounded-top-4 pb-3",
    );

    $("#formularioCategoria").trigger("reset");
    $("#formularioCategoria")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#guardarModal")
        .prop("hidden", false)
        .html('<i class="fas fa-save me-1"></i> Guardar Área');
};

const ver = async function (id) {
    try {
        const data = await consultar(id);

        $("#modalCategoria").modal("show");
        $("#tituloModal").html('<i class="fas fa-info-circle me-2"></i> Detalles del Área: ' + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-secondary text-white rounded-top-4 pb-3",
        );

        $("#formularioCategoria")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#guardarModal").prop("hidden", true);

        llenarDatosBasicos(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del área.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        urlAccion = urlEditar + id;
        const data = await consultar(id);

        $("#modalCategoria").modal("show");
        $("#tituloModal").html('<i class="fas fa-edit me-2"></i> Editar Área: ' + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-dark text-white rounded-top-4 pb-3",
        );

        $("#formularioCategoria")
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
            text: "No se pudo cargar la información del área.",
        });
    }
};

const llenarDatosBasicos = (data) => {
    $("#nombre").val(data.nombre);
};

const eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará el área <strong class='text-danger'>${data.nombre}</strong>.`,
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
                        $("#datatable_categoria")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Área Eliminada",
                            text: res.message,
                        });
                    },
                    error: function(err) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: err.responseJSON?.message || "No se pudo eliminar el área",
                        });
                    }
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: "error", title: "Error" });
    }
};

$("#formularioCategoria").on("submit", function (e) {
    e.preventDefault();

    if ($("#nombre").val().trim().length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "El nombre del área es muy corto",
        });
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
                $("#modalCategoria").modal("hide");
                $("#datatable_categoria").DataTable().ajax.reload(null, false);
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
            const txt = isEditar ? "Actualizar Cambios" : "Guardar Área";
            btn.prop("disabled", false).html(
                `<i class="fas fa-save me-1"></i> ${txt}`,
            );
        },
    });
});

