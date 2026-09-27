const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlDetalles = `${urlBase}/`;
const urlEliminar = `${urlBase}/`;
const urlGuardar = urlBase;
const urlEditar = `${urlBase}/actualizar/`;

let urlAccion = urlGuardar;
let isEditar = false;
let idVendedorActual = null;

$(document).ready(function () {
    crearDataTable({
        selector: "#datatable_vendedores",
        url: urlLista,
        searchPlaceholder: "Documento, nombre, teléfono...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const n = (row.nombre || "").trim();
                    const partes = n.split(" ");
                    const iniciales = (partes[0] ? partes[0].charAt(0) : "V") + (partes[1] ? partes[1].charAt(0) : "");
                    const doc = row.documento
                        ? `<span class="badge bg-light text-dark border"><i class="fas fa-id-badge text-muted me-1"></i>${row.tipo_documento || 'V'}-${row.documento}</span>`
                        : '<span class="text-muted small">Sin documento</span>';

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm me-1">
                                ${iniciales.toUpperCase()}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${n}</span>
                                <div>${doc}</div>
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
                data: "comision_porcentaje",
                name: "comision_porcentaje",
                className: "text-center align-middle",
                render: function (data) {
                    const comision = parseFloat(data || 0).toFixed(2);
                    return `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-bold"><i class="fas fa-percentage me-1"></i>${comision}%</span>`;
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data) {
                        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill"><i class="fas fa-check-circle me-1"></i>Activo</span>';
                    }
                    return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';
                },
            },
            {
                data: null,
                width: "120px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").trim().replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Alternar estado" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>`;
                },
            },
        ],
    });
});

const resetearFormularioVendedor = function () {
    const $form = $("#formularioVendedor");
    $form[0].reset();
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();
    $form.find("input, select, textarea").prop("disabled", false);
    $("#tipo_documento").val("V");
    $("#user_id").val("");
    $("#comision_porcentaje").val("0.00");
};

const crear = function () {
    isEditar = false;
    idVendedorActual = null;
    urlAccion = urlGuardar;

    resetearFormularioVendedor();

    $("#modalVendedorTitulo").text("Nuevo Vendedor");
    $("#modalVendedorSubtitulo").text("Completa la información del asesor de ventas");
    $("#modalVendedorIcono").attr("class", "fas fa-user-tie text-primary fs-5");

    $("#modalVendedorBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalVendedorTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
    modal.show();
};

const llenarFormularioVendedor = function (data) {
    $("#nombre").val(data.nombre || "");
    $("#tipo_documento").val(data.tipo_documento || "V");
    $("#documento").val(data.documento || "");
    $("#user_id").val(data.user_id || "");
    $("#telefono").val(data.telefono || "");
    $("#correo").val(data.correo || "");
    $("#comision_porcentaje").val(parseFloat(data.comision_porcentaje || 0).toFixed(2));
};

const ver = async function (id) {
    try {
        idVendedorActual = id;
        const res = await consultarRegistro(urlDetalles, id);
        const vendedor = res && res.data ? res.data : res;
        if (!vendedor) return;

        resetearFormularioVendedor();
        llenarFormularioVendedor(vendedor);

        $("#formularioVendedor").find("input, select, textarea").prop("disabled", true);

        $("#modalVendedorTitulo").text("Detalles del Vendedor");
        $("#modalVendedorSubtitulo").text("Consulta la información del asesor de ventas");
        $("#modalVendedorIcono").attr("class", "fas fa-eye text-info fs-5");

        $("#modalVendedorBtnGuardar").prop("hidden", true);

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del vendedor.",
            });
        }
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idVendedorActual = id;
        urlAccion = `${urlEditar}${id}`;
        const res = await consultarRegistro(urlDetalles, id);
        const vendedor = res && res.data ? res.data : res;
        if (!vendedor) return;

        resetearFormularioVendedor();
        llenarFormularioVendedor(vendedor);

        $("#modalVendedorTitulo").text(`Editar Vendedor: ${vendedor.nombre}`);
        $("#modalVendedorSubtitulo").text("Modifica los datos del asesor de ventas");
        $("#modalVendedorIcono").attr("class", "fas fa-user-edit text-primary fs-5");

        $("#modalVendedorBtnGuardar").prop("hidden", false).prop("disabled", false);
        $("#modalVendedorTextoGuardar").text("Actualizar Cambios");

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalVendedor"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del vendedor.",
            });
        }
    }
};

const eliminar = function (id, nombreVendedor) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreVendedor,
        tablaSelector: "#datatable_vendedores",
        titulo: "¿Cambiar Estado del Vendedor?",
        mensaje: `Se alternará el estado de "${nombreVendedor}". Podrás reactivarlo cuando lo requieras.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, cambiar estado',
    });
};

$("#formularioVendedor").on("submit", function (e) {
    e.preventDefault();

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalVendedor",
        tablaSelector: "#datatable_vendedores",
        btnSubmit: "#modalVendedorBtnGuardar",
        textoGuardarOriginal: $("#modalVendedorTextoGuardar").text(),
    });
});
