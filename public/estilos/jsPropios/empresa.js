const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
let urlAccion = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";
const urlGuardar = urlCompleta;
let isEditar = false;

const soloLetras = (e) => {
    const key = e.keyCode || e.which;
    const teclado = String.fromCharCode(key).toLowerCase();
    const letras = " áéíóúabcdefghijklmnñopqrstuvwxyz";
    if (letras.indexOf(teclado) === -1 && key !== 8 && key !== 13) return false;
};

const soloNumeros = (e) => {
    const key = e.keyCode || e.which;
    const teclado = String.fromCharCode(key);
    const numeros = "0123456789";
    if (numeros.indexOf(teclado) === -1 && key !== 8 && key !== 13)
        return false;
};

$(document).ready(function () {
    $("#datatable_empresas").DataTable({
        ajax: urlLista,
        responsive: true,
        processing: true,
        serverSide: true,
        lengthMenu: [
            [10, 25, 50],
            [10, 25, 50],
        ],
        columns: [
            {
                data: "logo",
                name: "logo",
                orderable: false,
                className: "text-center",
                render: function (data, type, row) {
                    const foto = row.logo
                        ? row.logo
                        : "https://ui-avatars.com/api/?name=" +
                          encodeURIComponent(row.nombre || "Empresa");
                    return `
                        <div class="d-flex justify-content-center">
                            <img src="${foto}" alt="${row.nombre}"
                                 class="rounded-circle border shadow-xs"
                                 style="width: 44px; height: 44px; object-fit: cover;">
                        </div>`;
                },
            },
            {
                data: null,
                name: "empresa",
                orderable: true,
                className: "text-start",
                render: function (data, type, row) {
                    return `
                        <div class="d-flex flex-column text-start">
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">${row.nombre || ""}</span>
                            <span class="text-muted small font-monospace"><i class="fas fa-id-card text-muted me-1"></i>${row.rif || "S/R"}</span>
                        </div>`;
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center",
                render: function (data, type, row) {
                    let badgeEstado =
                        row.estado == 1
                            ? '<span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1 small fw-bold"><i class="fas fa-check-circle me-1"></i>Activo</span>'
                            : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-1 small fw-bold"><i class="fas fa-times-circle me-1"></i>Inactivo</span>';

                    if (row.id === 1 || row.estado !== 1) {
                        return badgeEstado;
                    }

                    if (row.fechaFinalSuscripcion) {
                        const hoy = new Date();
                        hoy.setHours(0, 0, 0, 0);
                        const fechaStr = row.fechaFinalSuscripcion.includes("T")
                            ? row.fechaFinalSuscripcion
                            : row.fechaFinalSuscripcion + "T00:00:00";
                        const vencimiento = new Date(fechaStr);
                        const diffTime = vencimiento - hoy;
                        const diffDays = Math.ceil(
                            diffTime / (1000 * 60 * 60 * 24),
                        );

                        if (diffDays < 0) {
                            badgeEstado +=
                                '<br><span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-0 mt-1 small" style="font-size: 0.7rem;"><i class="fas fa-exclamation-triangle me-1"></i>Expiró hace ' +
                                Math.abs(diffDays) +
                                " días</span>";
                        } else if (diffDays <= 5) {
                            badgeEstado +=
                                '<br><span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-2 py-0 mt-1 small" style="font-size: 0.7rem;"><i class="fas fa-clock me-1"></i>Quedan ' +
                                diffDays +
                                " días</span>";
                        } else {
                            badgeEstado +=
                                '<br><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-0 mt-1 small" style="font-size: 0.7rem;"><i class="far fa-calendar-check me-1"></i>' +
                                diffDays +
                                " días restantes</span>";
                        }
                    } else {
                        badgeEstado +=
                            '<br><span class="badge bg-light text-muted border rounded-pill px-2 py-0 mt-1 small" style="font-size: 0.7rem;">Sin límite</span>';
                    }

                    return badgeEstado;
                },
            },
            {
                data: "plan",
                name: "plan",
                className: "text-center",
                render: function (data, type, row) {
                    const plan = (row.plan || "basico").toLowerCase();
                    if (plan === 'premium') {
                        return '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3 py-1 small fw-bold"><i class="fas fa-star me-1"></i>Premium</span>';
                    }
                    return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-3 py-1 small fw-bold"><i class="fas fa-cube me-1"></i>Básico</span>';
                },
            },
            {
                data: "created_at",
                name: "created_at",
                className: "text-center",
                render: function (data, type, row) {
                    return row.created_at ? `<span class="text-muted small font-monospace"><i class="far fa-calendar-alt me-1"></i>${row.created_at}</span>` : '—';
                },
            },
            {
                data: null,
                width: "120px",
                className: "text-center",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-sm btn-outline-info rounded-circle shadow-xs" onclick="ver(${row.id});" title="Ver Detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle shadow-xs" onclick="editar(${row.id});" title="Editar Empresa" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${row.id !== 1 ? `
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-xs" onclick="eliminar(${row.id});" title="Eliminar Empresa" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>` : ''}
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 al 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros)",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior",
            },
            sProcessing: "Procesando...",
        },
    });

    $("#pais, #estadoPais, #ciudad").on("keypress", soloLetras);
    $("#rif, #telefonoUno, #telefonoDos").on("keypress", soloNumeros);

    $("#rif, #telefonoUno, #telefonoDos").on("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "");
    });
});

const consultar = (id) => {
    return $.ajax({
        url: urlDetalles + id,
        type: "GET",
        dataType: "json",
    });
};

crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;
    $("#modalEmpresa").modal("show");
    $("#tituloModal").text("Nueva Empresa");
    $("#iconoModal").attr("class", "fa fa-plus me-2");
    $("#colorModal").attr(
        "class",
        "modal-header modal-colored-header bg-primary",
    );
    $("#formulario").trigger("reset");
    $("#imgPreview").hide().attr("src", "");
    $("#imgPlaceholder").show();
    $("#formulario").find("input, select, textarea").prop("disabled", false);
    $("#guardarModal").prop("hidden", false);
    $("#tipo_rif").val("J-");
    $("#plan").val("basico");
    $("#estado").val("1");
    $("#fechaFinalSuscripcion").val("");
    $("#container-input-img").show();
};

const cargarDatosFormulario = (data) => {
    $("#nombre").val(data.nombre || "");

    let rifCompleto = data.rif || "";
    let prefijo = "J-";
    let numero = "";

    const patrones = ["J-", "V-", "E-", "G-", "J", "V", "E", "G"];
    let encontrado = false;
    for (let p of patrones) {
        if (rifCompleto.toUpperCase().startsWith(p.toUpperCase())) {
            prefijo = p.includes("-") ? p : p + "-";
            numero = rifCompleto.substring(p.length).replace(/-/g, "");
            encontrado = true;
            break;
        }
    }
    if (!encontrado) {
        numero = rifCompleto.replace(/-/g, "");
        prefijo = "J-";
    }
    numero = numero.replace(/[^0-9]/g, "");

    $("#tipo_rif").val(prefijo);
    $("#rif").val(numero);

    $("#plan").val(data.plan || "basico");
    $("#correo").val(data.correo || "");
    $("#correoSecundario").val(data.correoSecundario || "");
    $("#telefonoUno").val(data.telefonoUno || "");
    $("#telefonoDos").val(data.telefonoDos || "");
    $("#pais").val(data.pais || "");
    $("#estadoPais").val(data.estadoPais || "");
    $("#ciudad").val(data.ciudad || "");
    $("#direccion").val(data.direccion || "");

    $("#estado").val(data.estado !== null ? data.estado : "1");

    if (data.fechaFinalSuscripcion) {
        const fecha = data.fechaFinalSuscripcion.includes("T")
            ? data.fechaFinalSuscripcion.split("T")[0]
            : data.fechaFinalSuscripcion;
        $("#fechaFinalSuscripcion").val(fecha);
    } else {
        $("#fechaFinalSuscripcion").val("");
    }

    if (data.logo) {
        $("#imgPreview").attr("src", data.logo).show();
        $("#imgPlaceholder").hide();
    } else {
        $("#imgPreview").hide();
        $("#imgPlaceholder").show();
    }
};

ver = async function (id) {
    try {
        const data = await consultar(id);
        $("#modalEmpresa").modal("show");
        $("#iconoModal").attr("class", "fa fa-eye me-2");
        $("#tituloModal").text("Ver Empresa: " + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header modal-colored-header bg-secondary",
        );
        $("#container-input-img").hide();
        $("#formulario").find("input, select, textarea").prop("disabled", true);
        $("#guardarModal").prop("hidden", true);
        cargarDatosFormulario(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: error.responseJSON?.message || "Error al cargar los datos",
        });
    }
};

editar = async function (id) {
    urlAccion = urlEditar + id;
    try {
        isEditar = true;
        const data = await consultar(id);
        $("#modalEmpresa").modal("show");
        $("#iconoModal").attr("class", "fa fa-edit me-2");
        $("#tituloModal").text("Editar Empresa: " + data.nombre);
        $("#colorModal").attr(
            "class",
            "modal-header modal-colored-header bg-dark",
        );
        $("#container-input-img").show();
        $("#formulario")
            .find("input, select, textarea")
            .prop("disabled", false);
        $("#guardarModal").prop("hidden", false);
        cargarDatosFormulario(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: error.responseJSON?.message || "Error al cargar los datos",
        });
    }
};

eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará la empresa <span class='text-danger'>${data.nombre}</span>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlEliminar + id,
                    type: "DELETE",
                    success: function () {
                        $("#datatable_empresas")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "¡Eliminado!",
                            text: "La empresa ha sido eliminada.",
                        });
                    },
                    error: function (xhr) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text:
                                xhr.responseJSON?.message ||
                                "Error al eliminar",
                        });
                    },
                });
            }
        });
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: error.responseJSON?.message || "Error al consultar",
        });
    }
};

function previsualizar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#imgPreview").attr("src", e.target.result).show();
            $("#imgPlaceholder").hide();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

$("#formulario").on("submit", function (e) {
    e.preventDefault();

    if ($("#nombre").val().trim().length < 3) {
        return notificacion.fire({
            icon: "warning",
            title: "Nombre de empresa demasiado corto",
            text: "El nombre debe tener al menos 3 caracteres.",
        });
    }

    if ($("#rif").val().trim().length < 6) {
        return notificacion.fire({
            icon: "warning",
            title: "RIF incompleto",
            text: "El RIF debe tener al menos 6 dígitos.",
        });
    }

    if ($("#telefonoUno").val().trim().length < 7) {
        return notificacion.fire({
            icon: "warning",
            title: "Teléfono 1 incompleto",
            text: "El teléfono debe tener al menos 7 dígitos.",
        });
    }

    if ($("#correo").val().trim() && !validarEmail($("#correo").val())) {
        return notificacion.fire({
            icon: "warning",
            title: "Correo principal inválido",
            text: "Ingrese un correo electrónico válido.",
        });
    }

    if (
        $("#correoSecundario").val().trim() &&
        !validarEmail($("#correoSecundario").val())
    ) {
        return notificacion.fire({
            icon: "warning",
            title: "Correo secundario inválido",
            text: "Ingrese un correo electrónico válido.",
        });
    }

    if (!isEditar && document.getElementById("logo").files.length === 0) {
        return Swal.fire(
            "Error",
            "El logo es obligatorio para crear una empresa.",
            "error",
        );
    }

    let formData = new FormData(this);
    const tipoRif = $("#tipo_rif").val();
    const numeroRif = $("#rif").val().trim();
    formData.delete("rif_input");
    formData.append("rif", tipoRif + numeroRif);

    const btn = $("#guardarModal");
    btn.prop("disabled", true).html(
        '<span class="spinner-border spinner-border-sm"></span> Guardando...',
    );

    $.ajax({
        url: urlAccion,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            if (response.success) {
                $("#modalEmpresa").modal("hide");
                $("#datatable_empresas").DataTable().ajax.reload(null, false);
                notificacion.fire({
                    icon: "success",
                    title: isEditar ? "Editado" : "Guardado",
                    text: response.message || "Operación exitosa.",
                });
            } else {
                notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message || "Ocurrió un error.",
                });
            }
        },
        error: function (xhr) {
            let mensaje = "Error al procesar la solicitud.";
            if (xhr.responseJSON && xhr.responseJSON.message) {
                mensaje = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                mensaje = Object.values(xhr.responseJSON.errors)
                    .flat()
                    .join("\n");
            }
            notificacion.fire({
                icon: "error",
                title: "Error",
                text: mensaje,
            });
        },
        complete: function () {
            btn.prop("disabled", false).html(
                '<i data-feather="save" class="svg-icon me-1"></i> ' +
                    (isEditar ? "Actualizar" : "Guardar"),
            );
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        },
    });
});

function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
