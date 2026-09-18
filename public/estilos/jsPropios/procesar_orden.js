$(document).ready(function () {
    // Lógica para saltar al siguiente input al presionar Enter
    $(".input-resultado").on("keypress", function (e) {
        if (e.which === 13) {
            // 13 es Enter
            e.preventDefault();
            let inputs = $(".input-resultado");
            let index = inputs.index(this);
            if (index > -1 && index < inputs.length - 1) {
                inputs.eq(index + 1).focus(); // Pasar al siguiente
            } else {
                // Si es el último, quitar foco o enfocar botón guardar
                $(this).blur();
            }
        }
    });

    // Lógica visual para resaltar si es anómalo
    $(".switch-anomalo").on("change", function () {
        let inputResultado = $(this).closest("tr").find(".input-resultado");
        if ($(this).is(":checked")) {
            inputResultado.css({
                "border-color": "red",
                "background-color": "#fff5f5",
            });
        } else {
            inputResultado.css({ "border-color": "", "background-color": "" });
        }
    });
});

function guardar(estadoDeseado) {
    let id = $("#orden_id").val();
    let resultados = [];
    let inputsVacios = 0;

    $(".input-resultado").each(function () {
        let valor = $(this).val().trim();
        let anomalo = $(this)
            .closest("tr")
            .find(".switch-anomalo")
            .is(":checked")
            ? 1
            : 0;

        if (valor === "") {
            inputsVacios++;
        }

        resultados.push({
            orden_detalle_id: $(this).data("orden-detalle-id"),
            examen_id: $(this).data("examen-id"),
            parametro_id: $(this).data("parametro-id"),
            valor: valor,
            anomalo: anomalo,
        });
    });

    let datosBacteriologia = [];
    $(".bacteriologia-container").each(function () {
        let detalleId = $(this).data("detalle-id");
        let tipoCultivo = $(this).find(".radio-tipo-cultivo:checked").val() || "positivo";
        let aislamientos = [];
        let antibiograma = [];

        if (tipoCultivo === "positivo") {
            $(this)
                .find(".aislamiento-card")
                .each(function () {
                    aislamientos.push({
                        genero: ($(this).find(".input-aisl-genero").val() || "").trim(),
                        especie: ($(this).find(".input-aisl-especie").val() || "").trim(),
                        crecimiento: ($(this).find(".input-aisl-crecimiento").val() || "").trim(),
                        hemolisis: ($(this).find(".input-aisl-hemolisis").val() || "").trim(),
                        grupo_s: ($(this).find(".input-aisl-grupos").val() || "").trim(),
                        grupo: ($(this).find(".input-aisl-grupo").val() || "").trim(),
                    });
                });

            $(this)
                .find(".antibiograma-row")
                .each(function () {
                    let antibiotico = $(this)
                        .find(".input-antibiotico")
                        .val()
                        .trim();
                    if (antibiotico !== "") {
                        let resultadosAb = [];
                        $(this)
                            .find(".select-sensibilidad")
                            .each(function () {
                                resultadosAb.push($(this).val());
                            });
                        antibiograma.push({
                            antibiotico: antibiotico,
                            resultados: resultadosAb,
                        });
                    }
                });
        }

        datosBacteriologia.push({
            detalle_id: detalleId,
            tipo_cultivo: tipoCultivo,
            aislamientos: aislamientos,
            antibiograma: antibiograma,
        });
    });

    // Si el usuario quiere un estado que finaliza/valida ('Transcrita', 'Validada', etc), validamos que no falte ninguno
    if (estadoDeseado !== "En Proceso" && inputsVacios > 0) {
        Swal.fire({
            icon: "warning",
            title: "Faltan resultados",
            text:
                'Para guardar como "' +
                estadoDeseado +
                '", todos los parámetros deben tener un resultado. Si desea salir sin completar, use "Guardar Progreso" o devuélvalo.',
        });
        return;
    }

    // Validar Bacteriología (si hay un contenedor de bacteriología positivo y va a finalizar, debe tener antibiograma)
    let faltaAntibiograma = false;
    if (estadoDeseado !== "En Proceso" && datosBacteriologia.length > 0) {
        datosBacteriologia.forEach((bact) => {
            // Requerimos al menos 1 antibiótico solo si el cultivo es positivo
            if (bact.tipo_cultivo === "positivo" && bact.antibiograma.length === 0) {
                faltaAntibiograma = true;
            }
        });
    }

    if (faltaAntibiograma) {
        Swal.fire({
            icon: "warning",
            title: "Antibiograma incompleto",
            text: "Debe agregar al menos un antibiótico al antibiograma para cultivos positivos antes de finalizar la orden.",
        });
        return;
    }

    if (
        estadoDeseado !== "En Proceso" &&
        typeof consumosPropuestos !== "undefined" &&
        consumosPropuestos.length > 0
    ) {
        let htmlTable = `
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered text-start align-middle" id="table-consumos">
                    <thead class="table-light">
                        <tr>
                            <th>Insumo</th>
                            <th>Stock Actual</th>
                            <th width="120px" class="text-center">A Consumir</th>
                            <th width="40px"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-consumos">
        `;
        consumosPropuestos.forEach((c, index) => {
            htmlTable += `
                        <tr class="consumo-row">
                            <td class="text-dark fw-bold">
                                <span class="consumo-nombre">${c.nombre}</span>
                                <input type="hidden" class="consumo-id" value="${c.id}">
                            </td>
                            <td class="text-muted">${c.stock_actual} ${c.unidad}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm text-center consumo-cantidad" data-stock="${c.stock_actual}" value="${c.cantidad_requerida}" step="0.01" min="0">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>
            `;
        });
        htmlTable += `</tbody></table></div>`;

        let optionsInsumos = '<option value="">Seleccionar...</option>';
        if (typeof inventariosList !== "undefined") {
            inventariosList.forEach((inv) => {
                optionsInsumos += `<option value="${inv.id}" data-unidad="${inv.unidadMedida}" data-stock="${inv.cantidad}">${inv.nombre}</option>`;
            });
        }

        htmlTable += `
            <div class="d-flex gap-2 align-items-center bg-light p-2 rounded border">
                <select id="nuevo-insumo-select" class="form-select form-select-sm" style="flex:1;">${optionsInsumos}</select>
                <input type="number" id="nuevo-insumo-cant" class="form-control form-control-sm" placeholder="Cant." style="width: 80px;" step="0.01" min="0.01">
                <button type="button" class="btn btn-sm btn-primary" onclick="agregarFilaConsumoExtra()"><i class="fas fa-plus"></i></button>
            </div>
        `;

        window.agregarFilaConsumoExtra = function () {
            let select = $("#nuevo-insumo-select");
            let cant = $("#nuevo-insumo-cant").val();
            let id = select.val();

            if (!id || !cant || cant <= 0) return;

            let nombre = select.find("option:selected").text();
            let unidad = select.find("option:selected").data("unidad");
            let stock = select.find("option:selected").data("stock");

            // Check if already exists
            if ($('.consumo-id[value="' + id + '"]').length > 0) {
                notificacion.fire({
                    icon: "warning",
                    title: "Ya existe en la lista",
                });
                return;
            }

            $("#tbody-consumos").append(`
                <tr class="consumo-row">
                    <td class="text-dark fw-bold">
                        <span class="consumo-nombre">${nombre}</span>
                        <input type="hidden" class="consumo-id" value="${id}">
                    </td>
                    <td class="text-muted">${stock} ${unidad}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-center consumo-cantidad" data-stock="${stock}" value="${cant}" step="0.01" min="0">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="$(this).closest('tr').remove()"><i class="fas fa-times"></i></button>
                    </td>
                </tr>
            `);

            select.val("");
            $("#nuevo-insumo-cant").val("");
        };

        Swal.fire({
            title: '<i class="fas fa-boxes text-primary me-2"></i>Consumo de Inventario',
            html: htmlTable,
            showCancelButton: true,
            confirmButtonText:
                '<i class="fas fa-check-circle me-1"></i> Confirmar y Guardar',
            cancelButtonText: "Cancelar",
            customClass: {
                confirmButton: "btn btn-primary rounded-pill px-4",
                cancelButton: "btn btn-light rounded-pill px-4",
                popup: "rounded-4",
            },
            width: "600px",
            preConfirm: () => {
                let insumosConfirmados = [];
                let error = null;
                $(".consumo-row").each(function () {
                    let inputCant = $(this).find(".consumo-cantidad");
                    let id = $(this).find(".consumo-id").val();
                    let cant = parseFloat(inputCant.val());
                    let stock = parseFloat(inputCant.data("stock"));
                    let nombre = $(this).find(".consumo-nombre").text().trim();

                    if (cant > stock) {
                        error = `El inventario de ${nombre} es insuficiente (Disp: ${stock}, Req: ${cant}).`;
                        return false;
                    }

                    if (cant > 0) {
                        insumosConfirmados.push({
                            inventario_id: id,
                            cantidad: cant,
                        });
                    }
                });

                if (error) {
                    Swal.showValidationMessage(error);
                    return false;
                }

                return insumosConfirmados;
            },
        }).then((result) => {
            if (result.isConfirmed) {
                enviarAjaxGuardar(
                    id,
                    resultados,
                    estadoDeseado,
                    result.value,
                    datosBacteriologia,
                );
            }
        });
    } else {
        enviarAjaxGuardar(
            id,
            resultados,
            estadoDeseado,
            [],
            datosBacteriologia,
        );
    }
}

function enviarAjaxGuardar(
    id,
    resultados,
    estadoDeseado,
    consumos,
    datosBacteriologia,
) {
    Swal.fire({
        title: "Guardando...",
        text: "Por favor espere",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    $.ajax({
        url: `/ordenes-servicio/guardar/${id}`,
        type: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            resultados: resultados,
            estado: estadoDeseado,
            consumos: consumos,
            datosBacteriologia: datosBacteriologia,
            observacion: $("#observacion_general").val(),
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Éxito",
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    if (estadoDeseado !== "En Proceso") {
                        // Abrir el reporte en una nueva pestaña
                        window.open(
                            `/ordenes-servicio/${id}/imprimir-resultados`,
                            "_blank",
                        );
                        window.location.href = "/ordenes-servicio";
                    } else {
                        location.reload();
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            let mensajeError = "Ocurrió un problema al guardar los resultados.";
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                mensajeError = Object.values(xhr.responseJSON.errors)
                    .map((err) => err.join("\\n"))
                    .join("\\n");
            } else if (xhr.responseJSON?.message) {
                mensajeError = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: "error",
                title: "Error",
                html: mensajeError,
            });
        },
    });
}

function enviarCorreo(id) {
    let selCount = $(".check-imprimir:checked").length;
    let tieneVacios = false;
    $(".input-resultado").each(function () {
        if ($(this).val().trim() === "") {
            tieneVacios = true;
        }
    });

    if (selCount > 0) {
        Swal.fire({
            title: "Enviar Resultados por Correo",
            text: `Tienes ${selCount} examen(es) seleccionado(s). ¿Cómo deseas enviar el informe al paciente?`,
            icon: "question",
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-tasks me-1"></i> Solo Seleccionados',
            denyButtonText: '<i class="fas fa-check-circle me-1"></i> Solo Listos (Parcial)',
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#0891b2",
            denyButtonColor: "#10b981",
        }).then((result) => {
            if (result.isConfirmed) {
                let ids = $(".check-imprimir:checked")
                    .map(function () {
                        return $(this).val();
                    })
                    .get();
                ejecutarEnvioCorreo(id, { solo_detalle: ids.join(",") });
            } else if (result.isDenied) {
                ejecutarEnvioCorreo(id, { solo_listos: 1 });
            }
        });
    } else if (tieneVacios) {
        Swal.fire({
            title: "Enviar Resultados por Correo",
            html: '<div class="text-start small text-muted mb-2">Se detectaron exámenes pendientes sin resultados. Puedes enviar únicamente los estudios completados hasta la fecha.</div>',
            icon: "info",
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Solo Listos (Parcial)',
            denyButtonText: '<i class="fas fa-file-alt me-1"></i> Informe Completo',
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#10b981",
            denyButtonColor: "#6b7280",
        }).then((result) => {
            if (result.isConfirmed) {
                ejecutarEnvioCorreo(id, { solo_listos: 1 });
            } else if (result.isDenied) {
                ejecutarEnvioCorreo(id, { solo_listos: 0 });
            }
        });
    } else {
        ejecutarEnvioCorreo(id, { solo_listos: 0 });
    }
}

function ejecutarEnvioCorreo(id, params) {
    let btn = $("#btnEnviarCorreo");
    let originalHtml = btn.html();
    btn.prop("disabled", true).html(
        '<i class="fas fa-spinner fa-spin me-1"></i> Enviando...',
    );

    $.ajax({
        url: `/ordenes-servicio/${id}/enviar-correo`,
        type: "POST",
        data: params,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    icon: "success",
                    title: "Correo Encolado",
                    text: response.message,
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: response.message,
                });
            }
        },
        error: function (xhr) {
            let mensajeError = "Ocurrió un problema al encolar el correo.";
            if (xhr.responseJSON?.message) {
                mensajeError = xhr.responseJSON.message;
            }
            Swal.fire({
                icon: "error",
                title: "Error",
                text: mensajeError,
            });
        },
        complete: function () {
            btn.prop("disabled", false).html(originalHtml);
        },
    });
}

$(document).ready(function () {
    $(document).on("click", ".btn-agregar-antibiotico", function () {
        let tbody = $(this)
            .closest(".bacteriologia-container")
            .find(".antibiograma-body");
        let row = `
            <tr class="antibiograma-row">
                <td class="ps-4 py-2">
                    <input type="text" class="form-control form-control-sm rounded-pill px-3 input-antibiotico fw-bold text-dark border-light-subtle shadow-none"
                           placeholder="Nombre del antibiótico">
                </td>
                <td class="text-center py-2">
                    <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="0">
                        <option value="">-</option>
                        <option value="S" class="text-success fw-bold">S (Sensible)</option>
                        <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                        <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                    </select>
                </td>
                <td class="text-center py-2">
                    <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="1">
                        <option value="">-</option>
                        <option value="S" class="text-success fw-bold">S (Sensible)</option>
                        <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                        <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                    </select>
                </td>
                <td class="text-center py-2">
                    <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="2">
                        <option value="">-</option>
                        <option value="S" class="text-success fw-bold">S (Sensible)</option>
                        <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                        <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                    </select>
                </td>
                <td class="text-center pe-3 py-2">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remover-antibiotico" title="Quitar antibiótico" style="width: 32px; height: 32px;">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    $(document).on("click", ".btn-remover-antibiotico", function () {
        $(this).closest("tr").remove();
    });

    // Select2 para campos de Microorganismos (Bacteriología)
    if ($(".select2-aisl-genero").length > 0) {
        $(".select2-aisl-genero").select2({
            placeholder: "Seleccionar o escribir género...",
            allowClear: true,
            tags: true,
            width: "100%",
        });
    }

    if ($(".select2-aisl-especie").length > 0) {
        $(".select2-aisl-especie").select2({
            placeholder: "Seleccionar o escribir especie...",
            allowClear: true,
            tags: true,
            width: "100%",
        });
    }

    if ($(".select2-aisl-crecimiento").length > 0) {
        $(".select2-aisl-crecimiento").select2({
            placeholder: "Seleccionar o escribir recuento...",
            allowClear: true,
            tags: true,
            width: "100%",
        });
    }

    if ($(".select2-aisl-hemolisis").length > 0) {
        $(".select2-aisl-hemolisis").select2({
            placeholder: "Seleccionar o escribir hemólisis...",
            allowClear: true,
            tags: true,
            width: "100%",
        });
    }

    // Select2 para antibióticos
    if ($(".select2-buscar-antibiotico").length > 0) {
        $(".select2-buscar-antibiotico").select2({
            placeholder: "Buscar o escribir antibiótico...",
            allowClear: true,
            tags: true, // Permite escribir uno nuevo
            width: "100%",
        });

        // Cuando se selecciona o escribe un antibiótico
        $(".select2-buscar-antibiotico").on("select2:select", function (e) {
            let valor = e.params.data.id;
            if (valor) {
                let container = $(this).closest(".bacteriologia-container");
                let tbody = container.find(".antibiograma-body");

                // Agregar fila
                let row = `
                    <tr class="antibiograma-row">
                        <td class="ps-4 py-2">
                            <input type="text" class="form-control form-control-sm rounded-pill px-3 input-antibiotico fw-bold text-dark border-light-subtle shadow-none"
                                   value="${valor}" placeholder="Nombre del antibiótico">
                        </td>
                        <td class="text-center py-2">
                            <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="0">
                                <option value="">-</option>
                                <option value="S" class="text-success fw-bold">S (Sensible)</option>
                                <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                                <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                            </select>
                        </td>
                        <td class="text-center py-2">
                            <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="1">
                                <option value="">-</option>
                                <option value="S" class="text-success fw-bold">S (Sensible)</option>
                                <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                                <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                            </select>
                        </td>
                        <td class="text-center py-2">
                            <select class="form-select form-select-sm rounded-pill select-sensibilidad text-center fw-bold shadow-none border-light-subtle" data-col="2">
                                <option value="">-</option>
                                <option value="S" class="text-success fw-bold">S (Sensible)</option>
                                <option value="I" class="text-warning fw-bold">I (Intermedio)</option>
                                <option value="R" class="text-danger fw-bold">R (Resistente)</option>
                            </select>
                        </td>
                        <td class="text-center pe-3 py-2">
                            <button type="button" class="btn btn-sm btn-outline-danger border-0 rounded-circle btn-remover-antibiotico" title="Quitar antibiótico" style="width: 32px; height: 32px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);

                // Limpiar el select para permitir buscar otro
                $(this).val(null).trigger("change");
            }
        });
    }
});

// ===== IMPRESION SELECCIONADA (CHECKLIST) =====
$(document).on("change", ".check-imprimir", function () {
    var count = $(".check-imprimir:checked").length;
    var $contador = $("#contador-sel");
    var $btn = $("#btn-imprimir-sel");
    if (count > 0) {
        $contador.text(count).show();
        $btn.removeClass("btn-outline-secondary").addClass("btn-primary");
    } else {
        $contador.hide();
        $btn.removeClass("btn-primary").addClass("btn-outline-secondary");
    }
});

function imprimirSeleccionados(ordenId) {
    var ids = $(".check-imprimir:checked")
        .map(function () {
            return $(this).val();
        })
        .get();

    if (ids.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin selección",
            text: "Debes seleccionar al menos un examen o perfil para imprimir.",
            confirmButtonText: "Entendido",
        });
        return;
    }

    var url =
        "/ordenes-servicio/" +
        ordenId +
        "/imprimir-resultados?solo_detalle=" +
        ids.join(",");
    window.open(url, "_blank");
}

// ===== MANEJO DE CULTIVOS POSITIVO / NEGATIVO =====
window.toggleTipoCultivo = function (detalleId, tipo) {
    var container = $('.bacteriologia-container[data-detalle-id="' + detalleId + '"]');
    if (tipo === "negativo") {
        container.find("#seccion_desarrollo_" + detalleId).slideUp(200);
        container.find("#aviso_negativo_" + detalleId).slideDown(200);
    } else {
        container.find("#aviso_negativo_" + detalleId).slideUp(200);
        container.find("#seccion_desarrollo_" + detalleId).slideDown(200);
    }
};

window.pegarObservacion = function (texto) {
    var $obs = $("#observacion_general");
    var actual = $obs.val().trim();
    if (actual === "") {
        $obs.val(texto);
    } else if (!actual.includes(texto)) {
        $obs.val(actual + "\n" + texto);
    }
    $obs.focus();

    if (typeof Swal !== "undefined" && Swal.mixin) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
        });
        Toast.fire({
            icon: "success",
            title: "Texto añadido a la observación",
        });
    }
};
