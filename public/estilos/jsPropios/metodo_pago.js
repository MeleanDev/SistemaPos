const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlDetalles = urlBase + "/";
const urlEliminar = urlBase + "/";
const urlGuardar = urlBase;
const urlEditar = urlBase + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idMetodoPagoActual = null;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_metodos_pago",
        url: urlLista,
        searchPlaceholder: "Nombre, descripción...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombre = (row.nombre || "").trim();

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1 bg-light-primary text-primary shadow-xs" style="width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5;">
                                <i class="fas fa-credit-card" style="font-size: 0.90rem;"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.92rem; letter-spacing: -0.01em;">${nombre}</span>
                            </div>
                        </div>
                    `;
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
                width: "120px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Eliminar método de pago" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
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
    idMetodoPagoActual = null;
    urlAccion = urlGuardar;

    $("#formularioMetodoPago")[0].reset();
    $("#formularioMetodoPago .is-invalid").removeClass("is-invalid");
    $("#formularioMetodoPago .invalid-feedback").remove();

    $("#formularioMetodoPago")
        .find("input, select, textarea")
        .prop("disabled", false);

    $("#modalMetodoPagoTitulo").text("Nuevo Método de Pago");
    $("#modalMetodoPagoSubtitulo").text("Completa la información del método de pago");
    $("#modalMetodoPagoIcono").attr("class", "fas fa-credit-card text-warning fs-5");

    $("#modalMetodoPagoBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalMetodoPagoTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalMetodoPago"));
    modal.show();
};

const ver = async function (id) {
    try {
        idMetodoPagoActual = id;
        const metodoPago = await consultarRegistro(urlDetalles, id);
        if (!metodoPago) return;

        $("#formularioMetodoPago")[0].reset();
        $("#formularioMetodoPago .is-invalid").removeClass("is-invalid");
        $("#formularioMetodoPago .invalid-feedback").remove();

        $("#modalMetodoPagoTitulo").text("Detalles del Método de Pago");
        $("#modalMetodoPagoSubtitulo").text("Consulta la información del método de pago");
        $("#modalMetodoPagoIcono").attr("class", "fas fa-eye text-info fs-5");

        llenarFormularioMetodoPago(metodoPago);

        $("#formularioMetodoPago")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#modalMetodoPagoBtnGuardar").prop("hidden", true);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalMetodoPago"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del método de pago.",
            });
        }
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idMetodoPagoActual = id;
        urlAccion = urlEditar + id;
        const metodoPago = await consultarRegistro(urlDetalles, id);
        if (!metodoPago) return;

        $("#formularioMetodoPago")[0].reset();
        $("#formularioMetodoPago .is-invalid").removeClass("is-invalid");
        $("#formularioMetodoPago .invalid-feedback").remove();

        $("#modalMetodoPagoTitulo").text(`Editar Método de Pago: ${metodoPago.nombre}`);
        $("#modalMetodoPagoSubtitulo").text("Modifica los datos del método de pago");
        $("#modalMetodoPagoIcono").attr("class", "fas fa-edit text-warning fs-5");

        $("#formularioMetodoPago")
            .find("input, select, textarea")
            .prop("disabled", false);
        llenarFormularioMetodoPago(metodoPago);

        $("#modalMetodoPagoBtnGuardar")
            .prop("hidden", false)
            .prop("disabled", false);
        $("#modalMetodoPagoTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalMetodoPago"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del método de pago.",
            });
        }
    }
};

const llenarFormularioMetodoPago = function (data) {
    $("#nombre").val(data.nombre || "");
    $("#descripcion").val(data.descripcion || "");
};

const eliminar = function (id, nombreMetodo) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreMetodo,
        tablaSelector: "#datatable_metodos_pago",
        titulo: "¿Desactivar Método de Pago?",
        mensaje: `Se modificará el estado del método "${nombreMetodo}". Podrás reactivarlo en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, desactivar',
    });
};

$("#formularioMetodoPago").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();

    if (nombre.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El nombre del método debe tener al menos 2 caracteres",
            });
        }
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalMetodoPago",
        tablaSelector: "#datatable_metodos_pago",
        btnSubmit: "#modalMetodoPagoBtnGuardar",
        textoGuardarOriginal: $("#modalMetodoPagoTextoGuardar").text(),
    });
});
