const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlCompleta;
let isEditar = false;
let isVer = false;
let parametroIndex = 0;
let insumoIndex = 0;
let faseActual = 1;
let filtroVentaActual = "";

$(document).ready(function () {
    iniciarDatatable();

    $("#filtro_categoria").on("change", function () {
        $("#datatable_examenes").DataTable().ajax.reload();
    });
});

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_examenes")) {
        $("#datatable_examenes").DataTable().destroy();
    }

    $("#datatable_examenes").DataTable({
        ajax: {
            url: urlLista,
            data: function (d) {
                d.categoria_id = $("#filtro_categoria").val();
                d.filtro_venta = filtroVentaActual;
            },
        },
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
                render: function (data, type, row) {
                    let antibioBadge = row.requiere_antibiograma
                        ? '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2 py-0 ms-2" style="font-size: 0.7rem;"><i class="fas fa-dna me-1"></i>Antibiograma</span>'
                        : '';
                    return `
                        <div class="d-flex align-items-center ps-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-inline-flex justify-content-center align-items-center me-3 shadow-xs" style="width: 38px; height: 38px; min-width: 38px;">
                                <i class="fas fa-flask"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold text-dark h6 mb-0">${data}</span>
                                    ${antibioBadge}
                                </div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "categoria.nombre",
                name: "categoria.nombre",
                defaultContent: '<span class="text-muted small">Sin área</span>',
                render: function (data) {
                    if (data) {
                        return `<span class="badge bg-light text-dark border rounded-pill px-3 py-1 fw-semibold"><i class="fas fa-tags me-1 text-primary"></i>${data}</span>`;
                    }
                    return '<span class="badge bg-light text-muted border rounded-pill px-2 py-1 small">General</span>';
                },
            },
            {
                data: "precio",
                name: "precio",
                className: "text-center",
                render: function (data) {
                    return `<span class="fw-bold text-dark fs-6">$${parseFloat(data).toFixed(2)}</span>`;
                },
            },
            {
                data: "venta_individual",
                name: "venta_individual",
                className: "text-center",
                render: function (data) {
                    return data
                        ? '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-1 rounded-pill fw-bold"><i class="fas fa-check-circle me-1"></i>Individual</span>'
                        : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-1 rounded-pill fw-bold"><i class="fas fa-cubes me-1"></i>Solo Perfil</span>';
                },
            },
            {
                data: null,
                width: "130px",
                className: "text-end pe-3",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-xs" onclick="ver(${row.id});" title="Ver Detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-xs" onclick="editar(${row.id});" title="Editar Examen" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminar(${row.id});" title="Eliminar Examen" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Buscar examen por nombre...",
            zeroRecords: "No se encontraron exámenes coincidentes",
            emptyTable: "No hay exámenes registrados en el catálogo",
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
            sProcessing: "Cargando catálogo de exámenes...",
        },
    });
}

function filtrarRapidoVenta(modalidad, btn) {
    filtroVentaActual = modalidad;
    $("#filtros-examenes-rapido button").removeClass("active");
    $(btn).addClass("active");
    $("#datatable_examenes").DataTable().ajax.reload();
}

function limpiarFiltrosExamenes() {
    filtroVentaActual = "";
    $("#filtros-examenes-rapido button").removeClass("active");
    $("#filtros-examenes-rapido button:first").addClass("active");
    $("#filtro_categoria").val("");
    $("#datatable_examenes").DataTable().ajax.reload();
}

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

/* ==========================================================================
   CONTRÓL DE FASES Y NAVEGACIÓN (WIZARD)
   ========================================================================== */

const navegarFase = function (direccion) {
    let siguienteFase = faseActual + direccion;

    if (direccion === 1 && faseActual === 1) {
        if (!validarCamposFase1()) return;
    }

    if (direccion === 1 && faseActual === 2) {
        if (
            $("#contenedor-parametros").find(".parametro-item").length === 0
        ) {
            $("#alerta-parametros").removeClass("d-none");
            return notificacion.fire({
                icon: "warning",
                title: "Configuración requerida",
                text: "Debes agregar por lo menos un parámetro antes de continuar.",
            });
        }

        let parametrosValidos = true;
        $(".parametro-nombre").each(function () {
            if ($(this).val().trim() === "") {
                parametrosValidos = false;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
            }
        });

        if (!parametrosValidos) {
            return notificacion.fire({
                icon: "warning",
                title: "Campos incompletos",
                text: "Todos los parámetros deben tener asignado un nombre.",
            });
        }
    }

    if (direccion === 1 && faseActual === 3) {
        let insumosValidos = true;
        $(".insumo-select, .insumo-cantidad").each(function () {
            if ($(this).val() === "") {
                insumosValidos = false;
                $(this).addClass("is-invalid");
            } else {
                $(this).removeClass("is-invalid");
            }
        });

        if (!insumosValidos) {
            return notificacion.fire({
                icon: "warning",
                title: "Campos incompletos",
                text: "Todos los insumos agregados deben tener producto y cantidad especificada.",
            });
        }
        generarVistaPrevia();
    }

    $(`#fase-${faseActual}`).addClass("d-none");
    $(`#fase-${siguienteFase}`).removeClass("d-none");

    faseActual = siguienteFase;
    actualizarInterfazProgreso();
};

const validarCamposFase1 = function () {
    let inputsValidos = true;

    if ($("#nombre").val().trim().length < 2) {
        $("#nombre").addClass("is-invalid");
        inputsValidos = false;
    } else {
        $("#nombre").removeClass("is-invalid");
    }
    if ($("#categoria_id").val() === "") {
        $("#categoria_id").addClass("is-invalid");
        inputsValidos = false;
    } else {
        $("#categoria_id").removeClass("is-invalid");
    }
    if ($("#precio").val() === "" || parseFloat($("#precio").val()) < 0) {
        $("#precio").addClass("is-invalid");
        inputsValidos = false;
    } else {
        $("#precio").removeClass("is-invalid");
    }

    if (!inputsValidos) {
        notificacion.fire({
            icon: "warning",
            title: "Campos obligatorios",
            text: "Complete todos los campos con (*) correctamente.",
        });
    }
    return inputsValidos;
};

const actualizarInterfazProgreso = function () {
    let porcentaje = ((faseActual - 1) / 3) * 100;
    $("#progreso-wizard")
        .css("width", porcentaje + "%")
        .attr("aria-valuenow", porcentaje);

    for (let i = 1; i <= 4; i++) {
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
    if (faseActual === 1) {
        $("#btn-wizard-anterior").addClass("d-none");
        $("#btn-wizard-siguiente").removeClass("d-none");
        $("#guardarModal").addClass("d-none");
    } else if (faseActual === 4) {
        $("#btn-wizard-anterior").removeClass("d-none");
        $("#btn-wizard-siguiente").addClass("d-none");
        if (!isVer) {
            $("#guardarModal").removeClass("d-none");
        }
    } else {
        $("#btn-wizard-anterior").removeClass("d-none");
        $("#btn-wizard-siguiente").removeClass("d-none");
        $("#guardarModal").addClass("d-none");
    }
};

/* ==========================================================================
   MANIPULACIÓN DINÁMICA DE PARÁMETROS (FASE 2)
   ========================================================================== */

const agregarSeccion = function () {
    let template = $("#template-seccion").html();
    $("#contenedor-parametros").append(template);
    $("#alerta-parametros").addClass("d-none");
};

const agregarParametroSuelto = function () {
    let template = $("#template-parametro").html();
    let html = template.replace(/__INDEX__/g, parametroIndex);
    $("#contenedor-parametros").append(html);
    parametroIndex++;
    $("#alerta-parametros").addClass("d-none");
};

const agregarParametroASeccion = function (btn) {
    let template = $("#template-parametro").html();
    let html = template.replace(/__INDEX__/g, parametroIndex);
    let $html = $(html);
    
    let seccionContenedor = $(btn).closest('.seccion-item');
    let seccionNombre = seccionContenedor.find('.seccion-nombre').val();
    $html.find('.parametro-seccion').val(seccionNombre);
    
    seccionContenedor.find(".contenedor-parametros-seccion").append($html);
    parametroIndex++;
};

/* ==========================================================================
   INSUMOS (FASE 3)
   ========================================================================== */

const agregarInsumo = function () {
    let template = $("#template-insumo").html();
    template = template.replace(/__INDEX__/g, insumoIndex);
    $("#contenedor-insumos").append(template);
    insumoIndex++;
};

const agregarInsumoConDatos = function (data) {
    let template = $("#template-insumo").html();
    template = template.replace(/__INDEX__/g, insumoIndex);

    let $elemento = $(template);
    $elemento.find(".insumo-select").val(data.id);
    $elemento.find(".insumo-cantidad").val(data.pivot.cantidad);

    $("#contenedor-insumos").append($elemento);
    insumoIndex++;
};

const actualizarSeccionParametros = function (input) {
    let seccionContenedor = $(input).closest('.seccion-item');
    let nuevoNombre = $(input).val();
    seccionContenedor.find('.parametro-seccion').val(nuevoNombre);
};

const eliminarSeccion = function (btn) {
    $(btn).closest('.seccion-item').remove();
    if ($("#contenedor-parametros").find('.parametro-item').length === 0) {
        $("#alerta-parametros").removeClass("d-none");
    }
};

const agregarParametroConDatos = function (parametro) {
    let template = $("#template-parametro").html();
    let html = template.replace(/__INDEX__/g, parametroIndex);
    let $html = $(html);

    $html.find(".parametro-id").val(parametro.id);
    $html.find(".parametro-nombre").val(parametro.nombre);
    $html.find(".parametro-unidad").val(parametro.unidad_medida);
    $html.find(".parametro-rango").val(parametro.rango_referencia);
    $html.find(".parametro-tipo").val(parametro.tipo_input);
    $html.find(".parametro-seccion").val(parametro.seccion || '');

    if (parametro.tipo_input === 'opciones' && parametro.opciones) {
        let opcs = parametro.opciones;
        if (typeof opcs === 'string') { try { opcs = JSON.parse(opcs); } catch (e) {} }
        $html.find(".parametro-opciones").val(Array.isArray(opcs) ? opcs.join(', ') : (opcs || ''));
        $html.find(".contenedor-opciones").removeClass('d-none');
    }

    if (parametro.seccion) {
        let seccionExistente = null;
        $(".seccion-item").each(function() {
            if ($(this).find('.seccion-nombre').val() === parametro.seccion) {
                seccionExistente = $(this);
            }
        });

        if (!seccionExistente) {
            let tplSeccion = $("#template-seccion").html();
            let $seccion = $(tplSeccion);
            $seccion.find('.seccion-nombre').val(parametro.seccion);
            $("#contenedor-parametros").append($seccion);
            seccionExistente = $seccion;
        }
        seccionExistente.find(".contenedor-parametros-seccion").append($html);
    } else {
        $("#contenedor-parametros").append($html);
    }
    
    parametroIndex++;
    $("#alerta-parametros").addClass("d-none");
};

const eliminarParametro = function (btn) {
    $(btn).closest(".parametro-item").remove();
    if ($("#contenedor-parametros").find('.parametro-item').length === 0) {
        $("#alerta-parametros").removeClass("d-none");
    }
};

/* ==========================================================================
   CONSTRUCCIÓN DE LA VISTA PREVIA (FASE 4)
   ========================================================================== */

const generarVistaPrevia = function () {
    $("#preview-nombre").text($("#nombre").val());
    $("#preview-categoria").text($("#categoria_id option:selected").text());
    $("#preview-precio").text(`$${parseFloat($("#precio").val()).toFixed(2)}`);

    let disponibleVenta = $("#venta_individual").is(":checked")
        ? "Disponible para facturación individual y en perfiles"
        : "Oculto en facturas individuales (Uso exclusivo dentro de perfiles)";
    $("#preview-venta").text(disponibleVenta);

    let notas =
        $("#descripcion").val().trim() !== ""
            ? $("#descripcion").val()
            : "Sin especificaciones internas anotadas.";
    $("#preview-descripcion").text(notas);

    let contenedorPreview = $("#preview-contenedor-parametros");
    contenedorPreview.empty();

    let agrupados = {};
    let sueltos = [];

    $(".parametro-item").each(function () {
        let nombre = $(this).find(".parametro-nombre").val();
        let tipo = $(this).find(".parametro-tipo option:selected").text();
        let unidad = $(this).find(".parametro-unidad").val() || '<span class="text-muted small">N/A</span>';
        let rango = $(this).find(".parametro-rango").val() || '<span class="text-muted small">No aplica</span>';
        let seccion = $(this).find(".parametro-seccion").val();

        let filaHtml = `
            <div class="row g-0 py-2 border-bottom align-items-center text-center text-dark small">
                <div class="col-md-4 text-start ps-3 fw-bold">${nombre}</div>
                <div class="col-md-3 text-muted">${tipo}</div>
                <div class="col-md-2">${unidad}</div>
                <div class="col-md-3 fst-italic text-secondary">${rango}</div>
            </div>
        `;

        if (seccion && seccion.trim() !== '') {
            if (!agrupados[seccion]) agrupados[seccion] = [];
            agrupados[seccion].push(filaHtml);
        } else {
            sueltos.push(filaHtml);
        }
    });

    for (let seccion in agrupados) {
        contenedorPreview.append(`<div class="bg-light text-primary fw-bold p-2 text-start ps-3 border-bottom"><i class="fas fa-folder-open me-2"></i>${seccion}</div>`);
        agrupados[seccion].forEach(html => contenedorPreview.append(html));
    }

    if (sueltos.length > 0) {
        if (Object.keys(agrupados).length > 0) {
            contenedorPreview.append(`<div class="bg-light text-secondary fw-bold p-2 text-start ps-3 border-bottom"><i class="fas fa-folder-minus me-2"></i>Otros Parámetros</div>`);
        }
        sueltos.forEach(html => contenedorPreview.append(html));
    }
};

/* ==========================================================================
   DISPARADORES CRUD (CREAR, VER, EDITAR, ELIMINAR)
   ========================================================================== */

const resetearModalACero = function () {
    $(".fase-wizard").addClass("d-none");
    $("#fase-1").removeClass("d-none");
    faseActual = 1;
    parametroIndex = 0;
    insumoIndex = 0;

    $("#formularioExamen").trigger("reset");
    $("#categoria_id").val('').trigger('change');
    $("#contenedor-parametros").empty();
    $("#contenedor-insumos").empty();
    $("#preview-contenedor-parametros").empty();
    $("#alerta-parametros").addClass("d-none");
    $(".form-control, .form-select").removeClass("is-invalid");

    actualizarInterfazProgreso();
};

const crear = function () {
    isEditar = false;
    isVer = false;
    urlAccion = urlGuardar;
    resetearModalACero();

    $("#modalExamen").modal("show");
    $("#tituloModal").html(
        '<i class="fas fa-flask me-2"></i> Nuevo Examen Clínico',
    );
    $("#colorModal").attr(
        "class",
        "modal-header border-0 bg-primary text-white rounded-top-4 pb-3",
    );

    $("#formularioExamen")
        .find("input, select, textarea, button")
        .prop("disabled", false);
    $(".btn-eliminar-param").prop("disabled", false);

    agregarParametroSuelto();
};

const ver = async function (id) {
    try {
        isVer = true;
        const data = await consultar(id);
        resetearModalACero();

        faseActual = 4;
        $(".fase-wizard").addClass("d-none");
        $("#fase-4").removeClass("d-none");

        $("#modalExamen").modal("show");
        $("#tituloModal").html(
            '<i class="fas fa-eye me-2"></i> Detalles del Examen: ' + data.nombre,
        );
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-secondary text-white rounded-top-4 pb-3",
        );

        llenarDatosFormulario(data);
        generarVistaPrevia();
        actualizarInterfazProgreso();

        $("#formularioExamen")
            .find("input, select, textarea, button")
            .prop("disabled", true);
        $(".btn-eliminar-param").prop("disabled", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos del examen.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        isVer = false;
        urlAccion = urlEditar + id;
        resetearModalACero();
        const data = await consultar(id);

        $("#modalExamen").modal("show");
        $("#tituloModal").html(
            '<i class="fas fa-edit me-2"></i> Editar Examen: ' + data.nombre,
        );
        $("#colorModal").attr(
            "class",
            "modal-header border-0 bg-dark text-white rounded-top-4 pb-3",
        );

        $("#formularioExamen")
            .find("input, select, textarea, button")
            .prop("disabled", false);
        $(".btn-eliminar-param").prop("disabled", false);

        llenarDatosFormulario(data);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información del examen.",
        });
    }
};

const llenarDatosFormulario = (data) => {
    $("#nombre").val(data.nombre);
    $("#categoria_id").val(data.categoria_id).trigger('change');
    $("#precio").val(data.precio);
    $("#descripcion").val(data.descripcion);
    $("#venta_individual").prop("checked", data.venta_individual == 1);
    $("#requiere_antibiograma").prop("checked", data.requiere_antibiograma == 1);

    if (data.parametros && data.parametros.length > 0) {
        data.parametros.forEach((param) => {
            agregarParametroConDatos(param);
        });
    } else {
        $("#alerta-parametros").removeClass("d-none");
    }

    if (data.inventarios && data.inventarios.length > 0) {
        data.inventarios.forEach((inv) => {
            agregarInsumoConDatos(inv);
        });
    }
};

const eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará el examen <strong class='text-danger'>${data.nombre}</strong> y su configuración de parámetros asociados.`,
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
                        $("#datatable_examenes")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Examen Eliminado",
                            text: res.message,
                        });
                    },
                    error: function(err) {
                        notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: err.responseJSON?.message || "No se pudo eliminar el examen",
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
   ENVÍO DEL FORMULARIO FINAL (PROCESAMIENTO BACKEND)
   ========================================================================== */

$("#formularioExamen").on("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);

    if (!$("#venta_individual").is(":checked")) {
        formData.set("venta_individual", "0");
    }

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
        success: function (response) {
            if (response.success) {
                $("#modalExamen").modal("hide");
                $("#datatable_examenes").DataTable().ajax.reload(null, false);
                notificacion.fire({
                    icon: "success",
                    title: "¡Operación Exitosa!",
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
                '<i class="fas fa-check-circle me-1"></i> Confirmar y Guardar',
            );
        },
    });
});

window.toggleOpcionesInput = function(selectElement) {
    const container = $(selectElement).closest('.parametro-item').find('.contenedor-opciones');
    const input = container.find('.parametro-opciones');
    if ($(selectElement).val() === 'opciones') {
        container.removeClass('d-none');
        input.prop('required', true);
    } else {
        container.addClass('d-none');
        input.prop('required', false).val('');
    }
}
