const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlDetalles = urlBase + "/";
const urlEliminar = urlBase + "/";
const urlGuardar = urlBase;
const urlEditar = urlBase + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idCategoriaActual = null;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_categorias",
        url: urlLista,
        searchPlaceholder: "Código, nombre o descripción...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombre = (row.nombre || "").trim();

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1 bg-light-primary text-primary shadow-xs" style="width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5;">
                                <i class="fas fa-tags" style="font-size: 0.88rem;"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.92rem; letter-spacing: -0.01em;">${nombre}</span>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "codigo",
                name: "codigo",
                className: "text-center align-middle",
                render: function (data) {
                    return data
                        ? `<span class="badge-documento"><i class="fas fa-barcode"></i> ${data}</span>`
                        : '<span class="text-muted small">-</span>';
                },
            },
            {
                data: "descripcion",
                name: "descripcion",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<span class="small text-secondary"><i class="fas fa-align-left text-muted me-1 small"></i>${data}</span>`
                        : '<span class="text-muted small fst-italic">Sin descripción</span>';
                },
            },
            {
                data: "created_at",
                name: "created_at",
                className: "text-center align-middle",
                render: function (data) {
                    if (!data) return '<span class="text-muted small">-</span>';
                    const fecha = new Date(data);
                    return `<span class="small text-muted"><i class="far fa-calendar-alt me-1"></i>${fecha.toLocaleDateString()}</span>`;
                },
            },
            {
                data: null,
                width: "130px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar categoría" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Desactivar categoría" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`;
                },
            },
        ],
    });

    aplicarRestriccionesInput();
});

const crear = function () {
    isEditar = false;
    idCategoriaActual = null;
    urlAccion = urlGuardar;

    $("#formularioCategoria")[0].reset();
    $("#formularioCategoria .is-invalid").removeClass("is-invalid");
    $("#formularioCategoria .invalid-feedback").remove();

    $("#formularioCategoria").find("input, select, textarea").prop("disabled", false);

    $("#modalCategoriaTitulo").text("Nueva Categoría");
    $("#modalCategoriaSubtitulo").text("Completa la información de la categoría de productos");
    $("#modalCategoriaIcono").attr("class", "fas fa-tags text-warning fs-5");

    $("#modalCategoriaBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalCategoriaTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCategoria"));
    modal.show();
};

const ver = async function (id) {
    try {
        idCategoriaActual = id;
        const categoria = await consultarRegistro(urlDetalles, id);
        if (!categoria) return;

        $("#formularioCategoria")[0].reset();
        $("#formularioCategoria .is-invalid").removeClass("is-invalid");
        $("#formularioCategoria .invalid-feedback").remove();

        $("#codigo").val(categoria.codigo || "");
        $("#nombre").val(categoria.nombre || "");
        $("#descripcion").val(categoria.descripcion || "");

        $("#formularioCategoria").find("input, select, textarea").prop("disabled", true);

        $("#modalCategoriaTitulo").text("Detalles de la Categoría");
        $("#modalCategoriaSubtitulo").text("Consulta la información de la categoría seleccionada");
        $("#modalCategoriaIcono").attr("class", "fas fa-eye text-info fs-5");

        $("#modalCategoriaBtnGuardar").prop("hidden", true);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCategoria"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos de la categoría.",
            });
        }
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idCategoriaActual = id;
        urlAccion = urlEditar + id;

        const categoria = await consultarRegistro(urlDetalles, id);
        if (!categoria) return;

        $("#formularioCategoria")[0].reset();
        $("#formularioCategoria .is-invalid").removeClass("is-invalid");
        $("#formularioCategoria .invalid-feedback").remove();

        $("#codigo").val(categoria.codigo || "");
        $("#nombre").val(categoria.nombre || "");
        $("#descripcion").val(categoria.descripcion || "");

        $("#formularioCategoria").find("input, select, textarea").prop("disabled", false);

        $("#modalCategoriaTitulo").text(`Editar Categoría: ${categoria.nombre}`);
        $("#modalCategoriaSubtitulo").text("Modifica los datos de la categoría seleccionada");
        $("#modalCategoriaIcono").attr("class", "fas fa-edit text-warning fs-5");

        $("#modalCategoriaBtnGuardar").prop("hidden", false).prop("disabled", false);
        $("#modalCategoriaTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCategoria"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información de la categoría.",
            });
        }
    }
};

$("#formularioCategoria").on("submit", function (e) {
    e.preventDefault();

    const codigo = $("#codigo").val().trim();
    const nombre = $("#nombre").val().trim();

    if (codigo.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El código debe tener al menos 2 caracteres",
            });
        }
    }

    if (nombre.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El nombre de la categoría debe tener al menos 2 caracteres",
            });
        }
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalCategoria",
        tablaSelector: "#datatable_categorias",
        btnSubmit: "#modalCategoriaBtnGuardar",
        textoGuardarOriginal: $("#modalCategoriaTextoGuardar").text(),
    });
});

const eliminar = function (id, nombreCategoria) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreCategoria,
        tablaSelector: "#datatable_categorias",
        titulo: "¿Desactivar Categoría?",
        mensaje: `Se modificará el estado de la categoría "${nombreCategoria}". Podrás reactivarla en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, desactivar',
    });
};
