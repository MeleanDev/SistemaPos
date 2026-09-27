const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlDetalles = `${urlBase}/`;
const urlEliminar = `${urlBase}/`;
const urlGuardar = urlBase;
const urlEditar = `${urlBase}/actualizar/`;

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
                    const nombreCompleto = `${row.nombre || ""} ${row.apellido || ""}`.trim();
                    const n = (row.nombre || "").trim();
                    const a = (row.apellido || "").trim();
                    const iniciales = (n.charAt(0) + (a ? a.charAt(0) : "")).toUpperCase() || "CL";
                    const cedula = row.cedula
                        ? `<span class="badge-documento"><i class="fas fa-id-card"></i> ${row.cedula}</span>`
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
                data: null,
                width: "120px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = `${row.nombre || ""} ${row.apellido || ""}`.trim().replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Eliminar cliente" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`;
                },
            },
        ],
    });

    aplicarRestriccionesInput();
});

const resetearFormularioCliente = function () {
    const $form = $("#formularioCliente");
    $form[0].reset();
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();
    $form.find("input, select, textarea").prop("disabled", false);
    $("#tipo_cedula").val("V-");
    $("#codigo_pais").val("+58");
    $("#tipo_cliente").val("detal");
};

const crear = function () {
    isEditar = false;
    idClienteActual = null;
    urlAccion = urlGuardar;

    resetearFormularioCliente();

    $("#modalClienteTitulo").text("Nuevo Cliente");
    $("#modalClienteSubtitulo").text("Completa la información del cliente");
    $("#modalClienteIcono").attr("class", "fas fa-user-plus text-warning fs-5");

    $("#modalClienteBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalClienteTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCliente"));
    modal.show();
};

const llenarFormularioCliente = function (data) {
    $("#nombre").val(data.nombre || "");
    $("#apellido").val(data.apellido || "");
    $("#correo").val(data.correo || "");
    $("#direccion").val(data.direccion || "");
    $("#tipo_cliente").val(data.tipo_cliente || "detal");

    const cedula = desglosarCedula(data.cedula);
    $("#tipo_cedula").val(cedula.tipo || "V-");
    $("#cedula_numero").val(cedula.numero || "");

    const telefono = desglosarTelefono(data.telefono);
    $("#codigo_pais").val(telefono.codigo || "+58");
    $("#telefono_numero").val(telefono.numero || "");
};

const ver = async function (id) {
    try {
        idClienteActual = id;
        const res = await consultarRegistro(urlDetalles, id);
        const cliente = res && res.data ? res.data : res;
        if (!cliente) return;

        resetearFormularioCliente();
        llenarFormularioCliente(cliente);

        $("#formularioCliente").find("input, select, textarea").prop("disabled", true);

        $("#modalClienteTitulo").text("Detalles del Cliente");
        $("#modalClienteSubtitulo").text("Consulta la información del cliente");
        $("#modalClienteIcono").attr("class", "fas fa-eye text-info fs-5");

        $("#modalClienteBtnGuardar").prop("hidden", true);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCliente"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del cliente.",
            });
        }
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idClienteActual = id;
        urlAccion = `${urlEditar}${id}`;
        const res = await consultarRegistro(urlDetalles, id);
        const cliente = res && res.data ? res.data : res;
        if (!cliente) return;

        resetearFormularioCliente();
        llenarFormularioCliente(cliente);

        $("#modalClienteTitulo").text(`Editar Cliente: ${cliente.nombre} ${cliente.apellido}`);
        $("#modalClienteSubtitulo").text("Modifica los datos del cliente");
        $("#modalClienteIcono").attr("class", "fas fa-user-edit text-warning fs-5");

        $("#modalClienteBtnGuardar").prop("hidden", false).prop("disabled", false);
        $("#modalClienteTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCliente"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del cliente.",
            });
        }
    }
};

const eliminar = function (id, nombreCliente) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreCliente,
        tablaSelector: "#datatable_clientes",
        titulo: "¿Desactivar Cliente?",
        mensaje: `Se modificará el estado de "${nombreCliente}". Podrás reactivarlo en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, desactivar',
    });
};

$("#formularioCliente").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalCliente",
        tablaSelector: "#datatable_clientes",
        btnSubmit: "#modalClienteBtnGuardar",
        textoGuardarOriginal: $("#modalClienteTextoGuardar").text(),
        antesDeEnviar: function (formData) {
            const cedulaNum = ($("#cedula_numero").val() || "").trim();
            if (cedulaNum) {
                formData.set("cedula", `${$("#tipo_cedula").val()}${cedulaNum}`);
            }

            const telNum = ($("#telefono_numero").val() || "").trim();
            if (telNum) {
                formData.set("telefono", `${$("#codigo_pais").val()}${telNum}`);
            } else {
                formData.delete("telefono");
            }
        },
    });
});
