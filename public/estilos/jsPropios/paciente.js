const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlCompleta;
let isEditar = false;
let filtroRapido = '';
let filtroEdadMin = '';
let filtroEdadMax = '';
let filtroDireccion = '';
let filtroTimer = null;

window.filtrarTablaPacientes = function(filtro, btn) {
    $('#filtros-pacientes .btn').removeClass('active');
    $(btn).addClass('active');
    filtroRapido = filtro;
    $('#datatable_pacientes').DataTable().ajax.reload();
};

window.limpiarFiltrosAvanzados = function() {
    $('#filtro_edad_min').val('');
    $('#filtro_edad_max').val('');
    $('#filtro_direccion').val('');
    filtroEdadMin = '';
    filtroEdadMax = '';
    filtroDireccion = '';
    filtroRapido = '';
    $('#filtros-pacientes .btn').removeClass('active');
    $('#filtros-pacientes .btn:first').addClass('active');
    $('#datatable_pacientes').DataTable().ajax.reload();
};

function dispararFiltrosConDebounce() {
    clearTimeout(filtroTimer);
    filtroTimer = setTimeout(function() {
        filtroEdadMin = $('#filtro_edad_min').val();
        filtroEdadMax = $('#filtro_edad_max').val();
        filtroDireccion = $('#filtro_direccion').val();
        $('#datatable_pacientes').DataTable().ajax.reload();
    }, 350);
}

$(document).on('keyup input change', '#filtro_edad_min, #filtro_edad_max, #filtro_direccion', function() {
    dispararFiltrosConDebounce();
});

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

const sinEspacios = (e) => {
    if (e.keyCode === 32) return false;
};

const esMinorActivo = () => $("#es_menor").is(":checked");

/**
 * Activa/desactiva la UI según el toggle de menor de edad.
 */
const toggleMenor = (activo) => {
    if (activo) {
        // Deshabilitar cédula del paciente
        $("#tipo_cedula, #cedula_numero").prop("disabled", true).val("");
        $("#bloque_representante").removeClass("d-none");
        $("#nombre_representante, #cedula_rep_numero").prop("disabled", false);

        // Cambiar labels de contacto
        $("#label_seccion_contacto").text("Contacto del Representante");
        $("#label_correo").text("Correo del Representante");
        $("#label_telefono").text("Teléfono del Representante");
    } else {
        // Habilitar cédula del paciente
        $("#tipo_cedula, #cedula_numero").prop("disabled", false);
        $("#bloque_representante").addClass("d-none");
        $("#nombre_representante, #cedula_rep_numero").prop("disabled", true).val("");
        $("#tipo_cedula_rep").val("V-");

        // Restaurar labels de contacto
        $("#label_seccion_contacto").text("Contacto y Ubicación");
        $("#label_correo").text("Correo Electrónico");
        $("#label_telefono").text("Teléfono");
    }
};

$(document).ready(function () {
    $("#datatable_pacientes").DataTable({
        ajax: {
            url: urlLista,
            data: function (d) {
                d.filtro_rapido = filtroRapido;
                d.edad_min = filtroEdadMin;
                d.edad_max = filtroEdadMax;
                d.direccion = filtroDireccion;
            }
        },
        responsive: true,
        processing: true,
        serverSide: true,
        lengthMenu: [
            [10, 25, 50],
            [10, 25, 50],
        ],
        columns: [
            {
                data: null,
                name: "nombreUno",
                render: function (data, type, row) {
                    return `${row.nombreUno} ${row.nombreDos ? row.nombreDos : ""}`.trim();
                },
            },
            {
                data: null,
                name: "apellidoUno",
                render: function (data, type, row) {
                    return `${row.apellidoUno} ${row.apellidoDos ? row.apellidoDos : ""}`.trim();
                },
            },
            {
                data: null,
                name: "cedula",
                render: function (data, type, row) {
                    if (row.es_menor) {
                        return `
                            <span class="badge bg-warning rounded-pill text-dark me-1" title="Menor de edad / Sin cédula">
                                <i class="fas fa-child me-1"></i>MENOR
                            </span>
                            <small class="text-muted">${row.codigo_registro || ""}</small>
                        `;
                    }
                    return row.cedula || "-";
                },
            },
            { data: "telefono", name: "telefono" },
            {
                data: null,
                name: "convenio_id",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (row.convenio && row.convenio.nombre) {
                        return `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.82rem;"><i class="fas fa-handshake me-1"></i>${row.convenio.nombre}</span>`;
                    }
                    return `<span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1; font-size: 0.82rem;"><i class="fas fa-user me-1 text-primary"></i>Particular</span>`;
                },
            },
            {
                data: "fechaNacimiento",
                name: "fechaNacimiento",
                className: "text-center align-middle",
                render: function (data) {
                    if (!data) return '<span class="text-muted">-</span>';

                    const hoy = new Date();
                    const b = new Date(data);

                    let years = hoy.getFullYear() - b.getFullYear();
                    let months = hoy.getMonth() - b.getMonth();
                    let days = hoy.getDate() - b.getDate();

                    if (days < 0) {
                        months--;
                        days += new Date(
                            hoy.getFullYear(),
                            hoy.getMonth(),
                            0,
                        ).getDate();
                    }
                    if (months < 0) {
                        years--;
                        months += 12;
                    }

                    return `
                        <div class="d-flex justify-content-center gap-2" style="font-family: 'Segoe UI', sans-serif;">
                            <div class="text-center" style="min-width: 45px;">
                                <div class="fw-bold text-primary" style="font-size: 0.95rem;">${years}</div>
                                <div class="text-uppercase" style="font-size: 0.55rem; color: #6c757d;">Años</div>
                            </div>
                            <div class="text-center" style="min-width: 45px;">
                                <div class="fw-bold text-primary" style="font-size: 0.95rem;">${months}</div>
                                <div class="text-uppercase" style="font-size: 0.55rem; color: #6c757d;">Meses</div>
                            </div>
                            <div class="text-center" style="min-width: 45px;">
                                <div class="fw-bold text-primary" style="font-size: 0.95rem;">${days}</div>
                                <div class="text-uppercase" style="font-size: 0.55rem; color: #6c757d;">Días</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: null,
                width: "120px",
                className: "text-center",
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-primary text-white btn-sm rounded-circle shadow" onclick="abrirModalHistorial(${row.id}, '${row.nombreUno}', '${row.apellidoUno}')" title="Historial Clínico" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa fa-file-medical"></i>
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id});" title="Eliminar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>`;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Nombre, apellido, cédula, teléfono...",
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

    $("#nombreUno, #nombreDos, #apellidoUno, #apellidoDos").on(
        "keypress",
        soloLetras,
    );
    $("#cedula_numero, #telefono_numero, #tel_emergencia_numero").on(
        "keypress",
        soloNumeros,
    );
    $("#correo").on("keypress", sinEspacios);

    // Toggle menor de edad
    $("#es_menor").on("change", function () {
        toggleMenor(this.checked);
    });
});

const consultar = (id) => {
    return $.ajax({ url: urlDetalles + id, type: "GET", dataType: "json" });
};

const cargarConveniosSelect = async (selectedId = null) => {
    try {
        const res = await $.ajax({
            url: window.location.origin + "/convenios/activos",
            type: "GET",
            dataType: "json"
        });

        let html = '<option value="">Particular / Precio Estándar</option>';
        if (res.success && res.data) {
            res.data.forEach(conv => {
                const esPredeterminado = conv.es_predeterminado ? ' (Predeterminado)' : '';
                const isSelected = (selectedId && selectedId == conv.id) || (!selectedId && conv.es_predeterminado && !isEditar);
                html += `<option value="${conv.id}" ${isSelected ? 'selected' : ''}>${conv.nombre}${esPredeterminado}</option>`;
            });
        }
        $("#convenio_id").html(html);
    } catch (e) {
        console.error("Error al cargar convenios:", e);
    }
};

const crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;

    $("#modalPaciente").modal("show");
    $("#tituloModal").html('<i class="fas fa-user-plus me-2"></i> Nuevo Paciente <button type="button" class="btn btn-sm btn-light text-primary ms-3 fw-bold rounded-pill shadow-sm" onclick="generarDatosAnonimosPaciente()"><i class="fas fa-user-secret me-1"></i> Generar Anonimo</button>');
    $("#colorModal").attr(
        "class",
        "modal-header modal-colored-header bg-primary",
    );

    $("#formularioPaciente").trigger("reset");
    $("#formularioPaciente").find("input, select").prop("disabled", false);
    $("#guardarModal")
        .prop("hidden", false)
        .html('<i class="fa fa-save"></i> Guardar');

    // Resetear estado menor
    $("#es_menor").prop("checked", false);
    toggleMenor(false);
    cargarConveniosSelect();
};

const ver = async function (id) {
    try {
        const data = await consultar(id);

        $("#modalPaciente").modal("show");
        $("#tituloModal").html('<i class="fas fa-eye me-2"></i> Detalles del Paciente');
        $("#colorModal").attr(
            "class",
            "modal-header modal-colored-header bg-secondary",
        );

        await cargarConveniosSelect(data.convenio_id);
        llenarDatosBasicos(data);

        $("#formularioPaciente").find("input, select").prop("disabled", true);
        $("#guardarModal").prop("hidden", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        urlAccion = urlEditar + id;
        const data = await consultar(id);

        $("#modalPaciente").modal("show");
        $("#tituloModal").html('<i class="fas fa-user-edit me-2"></i> Editar Paciente: ' + data.nombreUno + ' ' + data.apellidoUno);
        $("#colorModal").attr(
            "class",
            "modal-header modal-colored-header bg-dark",
        );

        await cargarConveniosSelect(data.convenio_id);
        llenarDatosBasicos(data);

        $("#formularioPaciente").find("input, select").prop("disabled", false);

        // Si es menor, re-deshabilitar cedula del paciente
        if (data.es_menor) {
            $("#tipo_cedula, #cedula_numero").prop("disabled", true);
        }

        $("#guardarModal")
            .prop("hidden", false)
            .html('<i class="fa fa-save"></i> Actualizar');
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información.",
        });
    }
};

const extraerPrefijoTelefono = (telefono, idSelect, idInput) => {
    if (telefono) {
        const opcionesCodigos = [
            ...document.getElementById(idSelect).options,
        ].map((opt) => opt.value);
        const codigoEncontrado = opcionesCodigos
            .filter((code) => telefono.startsWith(code))
            .sort((a, b) => b.length - a.length)[0];

        if (codigoEncontrado) {
            $(`#${idSelect}`).val(codigoEncontrado);
            $(`#${idInput}`).val(telefono.replace(codigoEncontrado, ""));
        } else {
            $(`#${idSelect}`).val("+58");
            $(`#${idInput}`).val(telefono);
        }
    }
};

const llenarDatosBasicos = (data) => {
    $("#nombreUno").val(data.nombreUno);
    $("#nombreDos").val(data.nombreDos);
    $("#convenio_id").val(data.convenio_id || "");
    $("#apellidoUno").val(data.apellidoUno);
    $("#apellidoDos").val(data.apellidoDos);
    $("#fechaNacimiento").val(data.fechaNacimiento);
    $("#sexo").val(data.sexo);
    $("#correo").val(data.correo);
    $("#direccion").val(data.direccion);

    // Estado menor
    const esMenor = !!data.es_menor;
    $("#es_menor").prop("checked", esMenor);
    toggleMenor(esMenor);

    if (esMenor) {
        // Cargar datos del representante
        $("#nombre_representante").val(data.nombre_representante || "");

        if (data.cedula_representante) {
            const prefijosRep = ["V-", "E-", "P-", "X-"];
            const prefijoRep = prefijosRep.find((p) =>
                data.cedula_representante.startsWith(p),
            );
            if (prefijoRep) {
                $("#tipo_cedula_rep").val(prefijoRep);
                $("#cedula_rep_numero").val(
                    data.cedula_representante.replace(prefijoRep, ""),
                );
            } else {
                $("#tipo_cedula_rep").val("V-");
                $("#cedula_rep_numero").val(data.cedula_representante);
            }
        }
    } else {
        if (data.cedula) {
            const prefijos = ["V-", "E-", "P-", "J-", "X-"];
            const prefijoEncontrado = prefijos.find((p) =>
                data.cedula.startsWith(p),
            );
            if (prefijoEncontrado) {
                $("#tipo_cedula").val(prefijoEncontrado);
                $("#cedula_numero").val(data.cedula.replace(prefijoEncontrado, ""));
            } else {
                $("#tipo_cedula").val("V-");
                $("#cedula_numero").val(data.cedula);
            }
        }
    }

    extraerPrefijoTelefono(data.telefono, "codigo_pais", "telefono_numero");
    extraerPrefijoTelefono(
        data.telefonoEmergencia,
        "codigo_pais_emergencia",
        "tel_emergencia_numero",
    );
};

const eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: "¿Estás seguro?",
            html: `Se eliminará al paciente <span class='text-danger'>${data.nombreUno} ${data.apellidoUno}</span>`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Sí, eliminar",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlEliminar + id,
                    type: "DELETE",
                    success: function (res) {
                        $("#datatable_pacientes")
                            .DataTable()
                            .ajax.reload(null, false);
                        notificacion.fire({
                            icon: "success",
                            title: "Eliminado",
                            text: res.message,
                        });
                    },
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: "error", title: "Error" });
    }
};

$("#formularioPaciente").on("submit", function (e) {
    e.preventDefault();

    const esMenor = esMinorActivo();

    if (
        $("#nombreUno").val().trim().length < 2 ||
        $("#apellidoUno").val().trim().length < 2
    ) {
        return notificacion.fire({
            icon: "warning",
            title: "Nombre o Apellido principal demasiado corto",
        });
    }

    // Validar cédula solo si NO es menor
    if (!esMenor && $("#tipo_cedula").val() !== "X-" && $("#cedula_numero").val().length < 6) {
        return notificacion.fire({ icon: "warning", title: "Cédula inválida" });
    }

    // Validar representante si es menor
    if (esMenor) {
        if ($("#nombre_representante").val().trim().length < 2) {
            return notificacion.fire({
                icon: "warning",
                title: "Ingrese el nombre del representante",
            });
        }
        if ($("#tipo_cedula_rep").val() !== "X-" && $("#cedula_rep_numero").val().length < 6) {
            return notificacion.fire({
                icon: "warning",
                title: "Ingrese la cédula del representante",
            });
        }
    }

    if ($("#telefono_numero").val().length < 7) {
        return notificacion.fire({
            icon: "warning",
            title: "Teléfono incompleto",
        });
    }

    const email = $("#correo").val().trim();
    if (email !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return notificacion.fire({ icon: "warning", title: "Correo inválido" });
    }

    let formData = new FormData(this);

    if (!esMenor) {
        formData.append(
            "cedula",
            $("#tipo_cedula").val() + $("#cedula_numero").val(),
        );
    }

    if (esMenor) {
        formData.append("es_menor", "1");
        formData.append(
            "cedula_representante",
            $("#tipo_cedula_rep").val() + $("#cedula_rep_numero").val(),
        );
    }

    formData.append(
        "telefono",
        $("#codigo_pais").val() + $("#telefono_numero").val(),
    );

    if ($("#tel_emergencia_numero").val().length > 0) {
        formData.append(
            "telefonoEmergencia",
            $("#codigo_pais_emergencia").val() +
                $("#tel_emergencia_numero").val(),
        );
    }

    const btn = $("#guardarModal");
    btn.prop("disabled", true).html(
        '<span class="spinner-border spinner-border-sm"></span>',
    );

    $.ajax({
        url: urlAccion,
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
                $("#modalPaciente").modal("hide");
                $("#datatable_pacientes").DataTable().ajax.reload(null, false);
                notificacion.fire({
                    icon: "success",
                    title: "Éxito",
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
                xhr.responseJSON?.message || "Error al procesar la solicitud";
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
            const txt = isEditar ? "Actualizar" : "Guardar";
            btn.prop("disabled", false).html(
                `<i class="fa fa-save"></i> ${txt}`,
            );
        },
    });
});

// --- LÓGICA DEL HISTORIAL ---

window.abrirModalHistorial = function(pacienteId, nombreUno, apellidoUno) {
    $('#historialPacienteId').val(pacienteId);
    $('#historialNombrePaciente').text(`${nombreUno} ${apellidoUno}`);
    $('#listaHistorialOrdenes').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>');
    $('#modalSeleccionHistorial').modal('show');
    $('#checkAllHistorial').prop('checked', false);

    $.ajax({
        url: `/pacientes/${pacienteId}/ordenes-completadas`,
        type: 'GET',
        success: function(response) {
            let html = '';
            if (response.ordenes.length === 0) {
                html = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay reportes de laboratorio completados para este paciente.</td></tr>';
            } else {
                response.ordenes.forEach(orden => {
                    html += `
                        <tr>
                            <td class="text-center align-middle">
                                <input class="form-check-input shadow-sm check-orden" type="checkbox" value="${orden.id}" style="width: 1.5em; height: 1.5em; border: 2px solid #adb5bd; cursor: pointer;">
                            </td>
                            <td class="align-middle">${orden.fecha}</td>
                            <td class="align-middle"><span class="badge bg-light text-dark border">${orden.codigo}</span></td>
                            <td class="align-middle"><small>${orden.examenes}</small></td>
                            <td class="align-middle"><span class="badge bg-success">${orden.estado}</span></td>
                        </tr>
                    `;
                });
            }
            $('#listaHistorialOrdenes').html(html);

            // Permitir siempre el envío (si no tiene correo, se envía por Telegram de respaldo)
            if (response.paciente_correo) {
                $('#btnEnviarCorreoHistorial').prop('disabled', false).attr('title', `Enviar a: ${response.paciente_correo} (Y Respaldo Telegram)`);
            } else {
                $('#btnEnviarCorreoHistorial').prop('disabled', false).attr('title', 'Enviar a Respaldo (Telegram) - Sin Correo');
            }
        },
        error: function() {
            $('#listaHistorialOrdenes').html('<tr><td colspan="5" class="text-center py-4 text-danger">Error al cargar el historial.</td></tr>');
        }
    });
};

$('#checkAllHistorial').on('change', function() {
    $('.check-orden').prop('checked', $(this).is(':checked'));
});

window.procesarHistorial = function(accion) {
    const pacienteId = $('#historialPacienteId').val();
    const ordenesSeleccionadas = [];
    $('.check-orden:checked').each(function() {
        ordenesSeleccionadas.push($(this).val());
    });

    if (ordenesSeleccionadas.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Debe seleccionar al menos un reporte del historial.'
        });
        return;
    }

    if (accion === 'imprimir') {
        const url = `/pacientes/${pacienteId}/imprimir-historial?ordenes=${ordenesSeleccionadas.join(',')}`;
        window.open(url, '_blank');
        $('#modalSeleccionHistorial').modal('hide');
    } else if (accion === 'correo') {
        $('#btnEnviarCorreoHistorial').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');
        
        $.ajax({
            url: `/pacientes/${pacienteId}/enviar-correo-historial`,
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val(),
                ordenes: ordenesSeleccionadas
            },
            success: function(response) {
                $('#modalSeleccionHistorial').modal('hide');
                Swal.fire({
                    icon: response.success ? 'success' : 'error',
                    title: response.success ? 'Enviado' : 'Error',
                    text: response.message
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Error al enviar el correo'
                });
            },
            complete: function() {
                $('#btnEnviarCorreoHistorial').prop('disabled', false).html('<i class="fas fa-envelope me-1"></i> Enviar Correo');
            }
        });
    }
};

/**
 * Genera datos anonimos para registrar pacientes que no quieren dar sus datos
 * Si es menor: llena cedula del representante con X-RANDOM y nombre anonimo
 * Si es adulto: llena cedula del paciente con X-RANDOM y nombre anonimo
 */
window.generarDatosAnonimosPaciente = function () {
    var rand = Math.floor(Math.random() * 90000000) + 10000000;
    var esMenor = $("#es_menor").is(":checked");

    if (esMenor) {
        $("#tipo_cedula_rep").val("X-");
        $("#cedula_rep_numero").val(rand);
        if (!$("#nombre_representante").val().trim()) {
            $("#nombre_representante").val("Representante Anonimo");
        }
        if (!$("#nombreUno").val().trim()) {
            $("#nombreUno").val("Menor");
            $("#apellidoUno").val("Anonimo");
        }
    } else {
        $("#tipo_cedula").val("X-");
        $("#cedula_numero").val(rand);
        if (!$("#nombreUno").val().trim()) {
            $("#nombreUno").val("Paciente");
            $("#apellidoUno").val("Anonimo");
        }
    }
};



