const urlCompleta = window.location.href;
const urlObtenerEmpresa = urlCompleta + "/empresa";
const urlGuardarEmpresa = urlCompleta + "/empresa";
const urlGuardarTasa = urlCompleta + "/tasa";
const urlListaTasa = urlCompleta + "/tasa-lista";

$(document).ready(function () {
    // Inicializar Datatable de Tasa
    const tablaTasa = $("#datatable_tasa").DataTable({
        ajax: urlListaTasa,
        responsive: true,
        processing: true,
        serverSide: true,
        lengthChange: false,
        searching: false,
        order: [[0, 'desc']],
        lengthMenu: [
            [10, 25, 50],
            [10, 25, 50],
        ],
        columns: [
            {
                data: "created_at",
                name: "created_at",
                render: function (data, type, row) {
                    return `<div class="d-flex align-items-center gap-2 ps-2">
                        <div class="bg-light text-primary p-2 rounded-circle small d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="fw-bold text-dark">${data}</span>
                    </div>`;
                }
            },
            {
                data: "tasa",
                name: "tasa",
                className: "text-end pe-3",
                render: function (data, type, row) {
                    return `<span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill font-monospace fs-6">Bs. ${parseFloat(data).toFixed(2)}</span>`;
                }
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
        drawCallback: function(settings) {
            var api = this.api();
            var datos = api.rows({page:'current'}).data();
            if (datos.length > 0) {
                // The first row is the latest one (since we ordered by desc in controller/query or here)
                $("#tasaActualSpan").text("Bs. " + parseFloat(datos[0].tasa).toFixed(2));
            } else {
                $("#tasaActualSpan").text("Bs. 0.00");
            }
        }
    });

    // Cargar datos de la empresa
    cargarDatosEmpresa();
});

function cargarDatosEmpresa() {
    $.ajax({
        url: urlObtenerEmpresa,
        type: "GET",
        dataType: "json",
        success: function (data) {
            // Read-only fields
            $("#nombre").val(data.nombre);
            $("#rif").val(data.rif);
            $("#direccion").val(data.direccion);
            $("#plan").val(data.plan ?? 'N/A');
            $("#fechaFinalSuscripcion").val(data.fechaFinalSuscripcion ?? 'N/A');

            // Editable fields
            $("#telefonoUno").val(data.telefonoUno);
            $("#telefonoDos").val(data.telefonoDos);
            $("#correo").val(data.correo);
            $("#correoSecundario").val(data.correoSecundario);
            $("#telegram_chat_id").val(data.telegram_chat_id);
            if (data.formato_factura) {
                $("#formato_factura").val(data.formato_factura);
            }
            if (data.reinicio_numero_control) {
                $("#reinicio_numero_control").val(data.reinicio_numero_control);
            }
            if (data.reinicio_numero_orden) {
                $("#reinicio_numero_orden").val(data.reinicio_numero_orden);
            }
            if (data.requiere_validacion) {
                $("#requiere_validacion").prop("checked", true);
            } else {
                $("#requiere_validacion").prop("checked", false);
            }
        },
        error: function () {
            notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos de la empresa.",
            });
        }
    });
}

// Guardar datos de la empresa
$("#formularioEmpresa").on("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    const btn = $("#btnGuardarEmpresa");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: urlGuardarEmpresa,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        headers: {
            Accept: "application/json",
        },
        success: function (response) {
            if (response.success) {
                notificacion.fire({
                    icon: "success",
                    title: "Éxito",
                    text: response.message,
                });
                // Update specific values if returned
                if(response.empresa) {
                    $("#telefonoUno").val(response.empresa.telefonoUno);
                    $("#telefonoDos").val(response.empresa.telefonoDos);
                    $("#correo").val(response.empresa.correo);
                    $("#correoSecundario").val(response.empresa.correoSecundario);
                    if (response.empresa.formato_factura) {
                        $("#formato_factura").val(response.empresa.formato_factura);
                    }
                    if (response.empresa.reinicio_numero_control) {
                        $("#reinicio_numero_control").val(response.empresa.reinicio_numero_control);
                    }
                    if (response.empresa.reinicio_numero_orden) {
                        $("#reinicio_numero_orden").val(response.empresa.reinicio_numero_orden);
                    }
                    if (response.empresa.requiere_validacion) {
                        $("#requiere_validacion").prop("checked", true);
                    } else {
                        $("#requiere_validacion").prop("checked", false);
                    }
                    if (response.empresa.telegram_chat_id !== undefined) {
                        $("#telegram_chat_id").val(response.empresa.telegram_chat_id);
                    }
                }
            } else {
                notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            let mensajeError = xhr.responseJSON?.message || "Error al procesar la solicitud";
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
            btn.prop("disabled", false).html('Guardar Cambios');
        },
    });
});

// Guardar Tasa BCV
$("#formularioTasa").on("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    const btn = $("#btnGuardarTasa");
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

    $.ajax({
        url: urlGuardarTasa,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        headers: {
            Accept: "application/json",
        },
        success: function (response) {
            if (response.success) {
                notificacion.fire({
                    icon: "success",
                    title: "Éxito",
                    text: response.message,
                });
                $("#formularioTasa").trigger("reset");
                $("#datatable_tasa").DataTable().ajax.reload(null, false);
            } else {
                notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            let mensajeError = xhr.responseJSON?.message || "Error al procesar la solicitud";
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
            btn.prop("disabled", false).html('Aplicar Tasa');
        },
    });
});
