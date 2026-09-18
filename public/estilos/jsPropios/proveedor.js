const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idProveedorActual = null;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_proveedores",
        url: urlLista,
        searchPlaceholder: "RIF, nombre comercial, contacto...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombre = (row.nombre || "").trim();
                    const iniciales = nombre.substring(0, 2).toUpperCase() || "PR";
                    const rif = row.rif
                        ? `<span class="badge-documento"><i class="fas fa-id-card"></i>${row.rif}</span>`
                        : '<span class="text-muted small">Sin RIF</span>';

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1">
                                ${iniciales}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${nombre}</span>
                                <div>${rif}</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "razon_social",
                name: "razon_social",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<span class="small fw-semibold text-secondary"><i class="fas fa-building text-muted me-1 small"></i>${data}</span>`
                        : '<span class="text-muted small fst-italic">Sin razón social</span>';
                },
            },
            {
                data: "nombre_contacto",
                name: "nombre_contacto",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<span class="small text-dark"><i class="fas fa-user-tie text-info me-1 small"></i>${data}</span>`
                        : '<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="fas fa-minus text-muted opacity-50 me-1"></i>Sin contacto</span>';
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
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${row.nombre}');" title="Eliminar proveedor" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
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
    idProveedorActual = null;
    urlAccion = urlGuardar;

    $("#modalProveedor").modal("show");
    $("#modalProveedorTituloTexto").text("Nuevo Proveedor");
    $("#modalProveedorSubtituloTexto").text(
        "Completa la información del proveedor",
    );
    $("#modalProveedorIcono").attr(
        "class",
        "fas fa-truck-loading me-2 text-warning fs-5",
    );

    $("#formularioProveedor")[0].reset();
    $("#formularioProveedor")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#tipo_cedula").val("J-");
    $("#codigo_pais").val("+58");

    $("#modalProveedorBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalProveedorTextoGuardar").text("Guardar");
};

const ver = async function (id) {
    try {
        idProveedorActual = id;
        const proveedor = await consultarRegistro(urlDetalles, id);

        $("#modalProveedor").modal("show");
        $("#modalProveedorTituloTexto").text("Detalles del Proveedor");
        $("#modalProveedorSubtituloTexto").text(
            "Consulta la información del proveedor",
        );
        $("#modalProveedorIcono").attr("class", "fas fa-eye me-2 text-info fs-5");

        llenarFormularioProveedor(proveedor);

        $("#formularioProveedor")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#modalProveedorBtnGuardar").prop("hidden", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del proveedor.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idProveedorActual = id;
        urlAccion = urlEditar + id;
        const proveedor = await consultarRegistro(urlDetalles, id);

        $("#modalProveedor").modal("show");
        $("#modalProveedorTituloTexto").text(
            `Editar Proveedor: ${proveedor.nombre}`,
        );
        $("#modalProveedorSubtituloTexto").text("Modifica los datos del proveedor");
        $("#modalProveedorIcono").attr(
            "class",
            "fas fa-edit me-2 text-warning fs-5",
        );

        $("#formularioProveedor")
            .find("input, select, textarea")
            .prop("disabled", false);
        llenarFormularioProveedor(proveedor);

        $("#modalProveedorBtnGuardar")
            .prop("hidden", false)
            .prop("disabled", false);
        $("#modalProveedorTextoGuardar").text("Actualizar Cambios");
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del proveedor.",
        });
    }
};

const llenarFormularioProveedor = (data) => {
    $("#nombre").val(data.nombre || "");
    $("#razon_social").val(data.razon_social || "");
    $("#nombre_contacto").val(data.nombre_contacto || "");
    $("#correo").val(data.correo || "");
    $("#direccion").val(data.direccion || "");

    const rif = desglosarCedula(data.rif);
    $("#tipo_cedula").val(rif.tipo || "J-");
    $("#cedula_numero").val(rif.numero || "");

    const telefono = desglosarTelefono(data.telefono);
    $("#codigo_pais").val(telefono.codigo || "+58");
    $("#telefono_numero").val(telefono.numero || "");
};

const eliminar = function (id, nombreProveedor) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreProveedor,
        tablaSelector: "#datatable_proveedores",
    });
};

$("#formularioProveedor").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();
    const razonSocial = $("#razon_social").val().trim();
    const rifNum = $("#cedula_numero").val().trim();

    if (nombre.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "El nombre comercial debe tener al menos 2 caracteres",
        });
    }

    if (razonSocial.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "La razón social debe tener al menos 2 caracteres",
        });
    }

    if (rifNum.length < 5) {
        return notificacion.fire({
            icon: "warning",
            title: "Número de RIF / Identificación incompleto",
        });
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalProveedor",
        tablaSelector: "#datatable_proveedores",
        btnSubmit: "#modalProveedorBtnGuardar",
        textoGuardarOriginal: $("#modalProveedorTextoGuardar").text(),
        antesDeEnviar: function (formData) {
            const rifCompleto = $("#tipo_cedula").val() + rifNum;
            formData.set("rif", rifCompleto);

            const telNum = $("#telefono_numero").val().trim();
            if (telNum) {
                formData.set("telefono", $("#codigo_pais").val() + telNum);
            } else {
                formData.delete("telefono");
            }
        },
    });
});
