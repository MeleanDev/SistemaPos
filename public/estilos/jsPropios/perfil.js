const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlGuardar = urlCompleta;
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlCompleta;
let isEditar = false;
let isVer = false;
let examenIndex = 0;
let faseActual = 1;

$(document).ready(function () {
    iniciarDatatable();
});

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_perfiles")) {
        $("#datatable_perfiles").DataTable().destroy();
    }

    $("#datatable_perfiles").DataTable({
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
                                <i class="fas fa-cubes"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark h6 mb-0">${data}</span>
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
                    return `<span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">0 exámenes</span>`;
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
                data: "precio",
                name: "precio",
                className: "text-center",
                render: (data) => `<span class="fw-bold text-dark fs-6">$${parseFloat(data).toFixed(2)}</span>`,
            },
            {
                data: null,
                width: "130px",
                className: "text-end pe-3",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-xs" onclick="ver(${row.id})" title="Ver Detalles" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-xs" onclick="editar(${row.id})" title="Editar Perfil" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminar(${row.id})" title="Eliminar Perfil" style="width:32px;height:32px;padding:0;display:inline-flex;align-items:center;justify-content:center;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Buscar perfil o combo...",
            zeroRecords: "No se encontraron perfiles coincidentes",
            emptyTable: "No hay perfiles registrados en el sistema",
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
            sProcessing: "Cargando perfiles...",
        },
    });
}

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

/* ==========================================================================
   WIZARD NAVEGACIÓN
   ========================================================================== */

const navegarFase = function (direccion) {
    let siguienteFase = faseActual + direccion;

    // Validación Fase 1 -> 2
    if (direccion === 1 && faseActual === 1) {
        let isValid = true;
        if ($("#nombre").val().trim() === "") {
            $("#nombre").addClass("is-invalid");
            isValid = false;
        } else {
            $("#nombre").removeClass("is-invalid");
        }
        if ($("#precio").val() === "" || parseFloat($("#precio").val()) < 0) {
            $("#precio").addClass("is-invalid");
            isValid = false;
        } else {
            $("#precio").removeClass("is-invalid");
        }

        if (!isValid) {
            notificacion.fire({
                icon: "warning",
                title: "Datos incompletos",
                text: "El nombre del perfil y un precio válido son obligatorios.",
            });
            return;
        }
    }

    // Validación Fase 2 -> 3
    if (direccion === 1 && faseActual === 2) {
        if ($(".examen-asociado-item").length === 0) {
            $("#alerta-examenes").removeClass("d-none");
            notificacion.fire({
                icon: "warning",
                title: "Sin exámenes asignados",
                text: "Debes vincular al menos un examen al perfil.",
            });
            return;
        }
        generarVistaPrevia();
    }

    $(`#fase-${faseActual}`).addClass("d-none");
    $(`#fase-${siguienteFase}`).removeClass("d-none");
    faseActual = siguienteFase;
    actualizarInterfazProgreso();
};

const actualizarInterfazProgreso = function () {
    let porcentaje = ((faseActual - 1) / 2) * 100;
    $("#progreso-wizard").css("width", porcentaje + "%");

    for (let i = 1; i <= 3; i++) {
        let pillo = $(`#pillo-paso-${i}`);
        if (i < faseActual) {
            pillo
                .removeClass("btn-secondary btn-primary")
                .addClass("btn-success")
                .html('<i class="fas fa-check text-white"></i>');
        } else if (i === faseActual) {
            pillo
                .removeClass("btn-secondary btn-success")
                .addClass("btn-primary")
                .text(i);
        } else {
            pillo
                .removeClass("btn-primary btn-success")
                .addClass("btn-secondary")
                .text(i);
        }
    }

    if (isVer) {
        $("#btn-wizard-cancelar")
            .removeClass("d-none")
            .html('<i class="fas fa-times me-1"></i> Cerrar');
        $(
            "#btn-wizard-anterior, #btn-wizard-siguiente, #guardarModal",
        ).addClass("d-none");
        return;
    }

    $("#btn-wizard-cancelar").html(
        '<i class="fas fa-times me-1"></i> Cancelar',
    );
    $("#btn-wizard-anterior").toggleClass("d-none", faseActual === 1);
    $("#btn-wizard-siguiente").toggleClass("d-none", faseActual === 3);
    $("#guardarModal").toggleClass("d-none", faseActual !== 3);
};

/* ==========================================================================
   GESTIÓN DE EXÁMENES (FASE 2)
   ========================================================================== */

const agregarExamenALista = function () {
    let $select = $("#select-examen-asociar");
    let id = $select.val();
    let textoSelect = $select.find("option:selected").text().trim();
    let nombre = textoSelect.split(" ($")[0];
    let categoria = $select.find("option:selected").data("categoria");

    if (!id) {
        return notificacion.fire({
            icon: "warning",
            title: "Seleccione un examen",
            text: "Debe seleccionar un examen de la lista para vincularlo.",
        });
    }

    if ($(`.examen-vinculado-id[value="${id}"]`).length > 0) {
        return notificacion.fire({
            icon: "info",
            title: "Ya incluido",
            text: "Este examen ya está vinculado al perfil.",
        });
    }

    inyectarFilaExamen(id, nombre, categoria);

    $select.val("").trigger("change");
};

const inyectarFilaExamen = (id, nombre, categoria) => {
    let template = $("#template-examen-fila").html();
    let html = template
        .replace(/__INDEX__/g, examenIndex++)
        .replace(/__ID__/g, id)
        .replace(/__NOMBRE__/g, nombre)
        .replace(/__CATEGORIA__/g, categoria || "Sin área");

    $("#contenedor-examenes-asociados").append(html);
    $("#alerta-examenes").addClass("d-none");
};

const removerExamenDeLista = (btn) => {
    $(btn).closest(".examen-asociado-item").remove();
    if ($(".examen-asociado-item").length === 0) {
        $("#alerta-examenes").removeClass("d-none");
    }
};

/* ==========================================================================
   VISTA PREVIA (FASE 3)
   ========================================================================== */

const generarVistaPrevia = function () {
    $("#preview-nombre").text($("#nombre").val());
    $("#preview-precio").text(`$${parseFloat($("#precio").val()).toFixed(2)}`);
    $("#preview-descripcion").text(
        $("#descripcion").val() || "Sin descripción adicional.",
    );

    let htmlPreview = "";
    $(".examen-asociado-item").each(function () {
        htmlPreview += `
            <div class="row g-0 py-2 border-bottom align-items-center text-center small">
                <div class="col-7 text-start ps-3 fw-medium text-dark"><i class="fas fa-flask text-primary me-2"></i>${$(this).find(".examen-texto-nombre").text().trim()}</div>
                <div class="col-5 text-muted">${$(this).find(".examen-texto-categoria").html()}</div>
            </div>`;
    });
    $("#preview-contenedor-examenes").html(htmlPreview);
};

/* ==========================================================================
   DISPARADORES CRUD (CREAR, VER, EDITAR, ELIMINAR)
   ========================================================================== */

const resetearModal = () => {
    $(".fase-wizard").addClass("d-none");
    $("#fase-1").removeClass("d-none");
    faseActual = 1;
    examenIndex = 0;

    $("#formularioPerfil").trigger("reset");
    $("#select-examen-asociar").val("").trigger("change");
    $("#contenedor-examenes-asociados").empty();
    $("#preview-contenedor-examenes").empty();
    $("#alerta-examenes").addClass("d-none");
    $(".form-control, .form-select").removeClass("is-invalid");

    actualizarInterfazProgreso();
};

const crear = () => {
    isEditar = false;
    isVer = false;
    urlAccion = urlGuardar;
    resetearModal();

    $("#modalPerfil").modal("show");
    $("#tituloModal").html('<i class="fas fa-cubes me-2"></i> Nuevo Perfil de Exámenes');
    $("#colorModal").attr(
        "class",
        "modal-header border-0 bg-primary text-white rounded-top-4 pb-3",
    );

    $("#formularioPerfil")
        .find("input, select, textarea, button")
        .prop("disabled", false);
};

const llenarDatosFormulario = (data) => {
    $("#nombre").val(data.nombre);
    $("#precio").val(data.precio);
    $("#descripcion").val(data.descripcion);

    if (data.examenes && data.examenes.length > 0) {
        data.examenes.forEach((examen) => {
            inyectarFilaExamen(
                examen.id,
                examen.nombre,
                examen.categoria ? examen.categoria.nombre : "Sin área",
            );
        });
    } else {
        $("#alerta-examenes").removeClass("d-none");
    }
};

const ver = async function (id) {
    try {
        isVer = true;
        const data = await consultar(id);
        resetearModal();

        faseActual = 3;
        $(".fase-wizard").addClass("d-none");
        $("#fase-3").removeClass("d-none");

        $("#modalPerfil").modal("show");
        $("#tituloModal").html(
            '<i class="fas fa-eye me-2"></i> Detalles del Perfil: ' + data.nombre,
        );
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-secondary text-white rounded-top-4 pb-3",
        );

        llenarDatosFormulario(data);
        generarVistaPrevia();
        actualizarInterfazProgreso();

        $("#formularioPerfil")
            .find("input, select, textarea, button")
            .prop("disabled", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del perfil.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        isVer = false;
        urlAccion = urlEditar + id;
        resetearModal();
        const data = await consultar(id);

        $("#modalPerfil").modal("show");
        $("#tituloModal").html(
            '<i class="fas fa-edit me-2"></i> Editar Perfil: ' + data.nombre,
        );
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-dark text-white rounded-top-4 pb-3",
        );

        $("#formularioPerfil")
            .find("input, select, textarea, button")
            .prop("disabled", false);
        llenarDatosFormulario(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del perfil.",
        });
    }
};

const eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará el perfil <strong class='text-danger'>${data.nombre}</strong> del catálogo de combos.`,
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
                        $("#datatable_perfiles")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Perfil Eliminado",
                            text: res.message,
                        });
                    },
                    error: function(err) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: err.responseJSON?.message || "No se pudo eliminar el perfil",
                        });
                    }
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: "error", title: "Error" });
    }
};

/* ==========================================================================
   ENVÍO AL BACKEND
   ========================================================================== */

$("#formularioPerfil").on("submit", function (e) {
    e.preventDefault();
    let formData = new FormData(this);

    if (isEditar) formData.append("_method", "PUT");

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
        headers: { Accept: "application/json" },
        success: (res) => {
            if (res.success) {
                $("#modalPerfil").modal("hide");
                $("#datatable_perfiles").DataTable().ajax.reload(null, false);
                notificacion.fire({
                    icon: "success",
                    title: "¡Operación Exitosa!",
                    text: res.message,
                });
            } else {
                notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: res.message,
                });
            }
        },
        error: function (xhr) {
            let mensajeError =
                xhr.responseJSON?.message ||
                "Ocurrió un error inesperado al procesar la transacción.";
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
            btn.prop("disabled", false).html(
                '<i class="fas fa-check-circle me-1"></i> Confirmar y Crear Combo',
            );
        },
    });
});

