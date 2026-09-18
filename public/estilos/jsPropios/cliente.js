const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idClienteActual = null;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_clientes",
        url: urlLista,
        searchPlaceholder: "Cédula, nombre, teléfono...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombreCompleto =
                        `${row.nombre || ""} ${row.apellido || ""}`.trim();
                    const n = (row.nombre || "").trim();
                    const a = (row.apellido || "").trim();
                    const iniciales =
                        (n.charAt(0) + (a ? a.charAt(0) : "")).toUpperCase() ||
                        "CL";
                    const cedula = row.cedula
                        ? `<span class="badge-documento"><i class="fas fa-id-card"></i>${row.cedula}</span>`
                        : '<span class="text-muted small">Sin documento</span>';

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1">
                                ${iniciales}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${nombreCompleto}</span>
                                <div>${cedula}</div>
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
                data: "tipo_cliente",
                name: "tipo_cliente",
                className: "text-center align-middle",
                render: function (data) {
                    if (data === "mayorista") {
                        return '<span class="badge badge-mayorista"><i class="fas fa-crown"></i>Mayorista</span>';
                    }
                    return '<span class="badge badge-detal"><i class="fas fa-user"></i>Detal</span>';
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data === true || data === 1 || data === "1") {
                        return '<span class="badge badge-activo"><i class="fas fa-check-circle"></i>Activo</span>';
                    }
                    return '<span class="badge badge-inactivo"><i class="fas fa-ban"></i>Inactivo</span>';
                },
            },
            {
                data: null,
                width: "120px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const iconEstado = row.estado
                        ? "fa-toggle-on text-success"
                        : "fa-toggle-off text-secondary";
                    const titleEstado = row.estado
                        ? "Desactivar cliente"
                        : "Activar cliente";

                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${row.nombre} ${row.apellido}');" title="${titleEstado}" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas ${iconEstado}"></i>
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
    idClienteActual = null;
    urlAccion = urlGuardar;

    $("#modalCliente").modal("show");
    $("#modalClienteTituloTexto").text("Nuevo Cliente");
    $("#modalClienteSubtituloTexto").text(
        "Completa la información del cliente",
    );
    $("#modalClienteIcono").attr(
        "class",
        "fas fa-user-plus me-2 text-warning fs-5",
    );

    $("#formularioCliente")[0].reset();
    $("#formularioCliente")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#tipo_cedula").val("V-");
    $("#codigo_pais").val("+58");
    $("#tipo_cliente").val("detal");

    $("#modalClienteBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalClienteTextoGuardar").text("Guardar");
};

const ver = async function (id) {
    try {
        idClienteActual = id;
        const cliente = await consultarRegistro(urlDetalles, id);

        $("#modalCliente").modal("show");
        $("#modalClienteTituloTexto").text("Detalles del Cliente");
        $("#modalClienteSubtituloTexto").text(
            "Consulta la información del cliente",
        );
        $("#modalClienteIcono").attr("class", "fas fa-eye me-2 text-info fs-5");

        llenarFormularioCliente(cliente);

        $("#formularioCliente")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#modalClienteBtnGuardar").prop("hidden", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del cliente.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idClienteActual = id;
        urlAccion = urlEditar + id;
        const cliente = await consultarRegistro(urlDetalles, id);

        $("#modalCliente").modal("show");
        $("#modalClienteTituloTexto").text(
            `Editar Cliente: ${cliente.nombre} ${cliente.apellido}`,
        );
        $("#modalClienteSubtituloTexto").text("Modifica los datos del cliente");
        $("#modalClienteIcono").attr(
            "class",
            "fas fa-user-edit me-2 text-warning fs-5",
        );

        $("#formularioCliente")
            .find("input, select, textarea")
            .prop("disabled", false);
        llenarFormularioCliente(cliente);

        $("#modalClienteBtnGuardar")
            .prop("hidden", false)
            .prop("disabled", false);
        $("#modalClienteTextoGuardar").text("Actualizar Cambios");
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del cliente.",
        });
    }
};

const llenarFormularioCliente = (data) => {
    $("#nombre").val(data.nombre || "");
    $("#apellido").val(data.apellido || "");
    $("#correo").val(data.correo || "");
    $("#direccion").val(data.direccion || "");
    $("#tipo_cliente").val(data.tipo_cliente || "detal");

    const cedula = desglosarCedula(data.cedula);
    $("#tipo_cedula").val(cedula.tipo);
    $("#cedula_numero").val(cedula.numero);

    const telefono = desglosarTelefono(data.telefono);
    $("#codigo_pais").val(telefono.codigo);
    $("#telefono_numero").val(telefono.numero);
};

const eliminar = function (id, nombreCliente) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreCliente,
        tablaSelector: "#datatable_clientes",
    });
};

$("#formularioCliente").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();
    const apellido = $("#apellido").val().trim();
    const cedulaNum = $("#cedula_numero").val().trim();

    if (nombre.length < 2 || apellido.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "Nombre o Apellido demasiado corto",
        });
    }

    if (cedulaNum.length < 5) {
        return notificacion.fire({
            icon: "warning",
            title: "Número de Cédula/RIF incompleto",
        });
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalCliente",
        tablaSelector: "#datatable_clientes",
        btnSubmit: "#modalClienteBtnGuardar",
        textoGuardarOriginal: $("#modalClienteTextoGuardar").text(),
        antesDeEnviar: function (formData) {
            const cedulaCompleta = $("#tipo_cedula").val() + cedulaNum;
            formData.set("cedula", cedulaCompleta);

            const telNum = $("#telefono_numero").val().trim();
            if (telNum) {
                formData.set("telefono", $("#codigo_pais").val() + telNum);
            } else {
                formData.delete("telefono");
            }
        },
    });
});
