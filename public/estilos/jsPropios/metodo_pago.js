const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

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
                    const iniciales = nombre.substring(0, 2).toUpperCase() || "MP";

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1 bg-light-primary text-primary">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${nombre}</span>
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
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${row.nombre}');" title="Eliminar método de pago" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
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

    $("#modalMetodoPago").modal("show");
    $("#modalMetodoPagoTituloTexto").text("Nuevo Método de Pago");
    $("#modalMetodoPagoSubtituloTexto").text(
        "Completa la información del método de pago",
    );
    $("#modalMetodoPagoIcono").attr(
        "class",
        "fas fa-credit-card me-2 text-warning fs-5",
    );

    $("#formularioMetodoPago")[0].reset();
    $("#formularioMetodoPago")
        .find("input, select, textarea")
        .prop("disabled", false);

    $("#modalMetodoPagoBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalMetodoPagoTextoGuardar").text("Guardar");
};

const ver = async function (id) {
    try {
        idMetodoPagoActual = id;
        const metodoPago = await consultarRegistro(urlDetalles, id);

        $("#modalMetodoPago").modal("show");
        $("#modalMetodoPagoTituloTexto").text("Detalles del Método de Pago");
        $("#modalMetodoPagoSubtituloTexto").text(
            "Consulta la información del método de pago",
        );
        $("#modalMetodoPagoIcono").attr("class", "fas fa-eye me-2 text-info fs-5");

        llenarFormularioMetodoPago(metodoPago);

        $("#formularioMetodoPago")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#modalMetodoPagoBtnGuardar").prop("hidden", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del método de pago.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idMetodoPagoActual = id;
        urlAccion = urlEditar + id;
        const metodoPago = await consultarRegistro(urlDetalles, id);

        $("#modalMetodoPago").modal("show");
        $("#modalMetodoPagoTituloTexto").text(
            `Editar Método de Pago: ${metodoPago.nombre}`,
        );
        $("#modalMetodoPagoSubtituloTexto").text(
            "Modifica los datos del método de pago",
        );
        $("#modalMetodoPagoIcono").attr(
            "class",
            "fas fa-edit me-2 text-warning fs-5",
        );

        $("#formularioMetodoPago")
            .find("input, select, textarea")
            .prop("disabled", false);
        llenarFormularioMetodoPago(metodoPago);

        $("#modalMetodoPagoBtnGuardar")
            .prop("hidden", false)
            .prop("disabled", false);
        $("#modalMetodoPagoTextoGuardar").text("Actualizar Cambios");
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del método de pago.",
        });
    }
};

const llenarFormularioMetodoPago = (data) => {
    $("#nombre").val(data.nombre || "");
    $("#descripcion").val(data.descripcion || "");
};

const eliminar = function (id, nombreMetodo) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreMetodo,
        tablaSelector: "#datatable_metodos_pago",
    });
};

$("#formularioMetodoPago").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();

    if (nombre.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "El nombre del método debe tener al menos 2 caracteres",
        });
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
