// URL limpia independiente de query parameters en la barra de navegación
const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlDatos = urlBase + "/datos";
const urlGuardarEmpresa = urlBase + "/empresa";
const urlGuardarMonedas = urlBase + "/monedas";

$(document).ready(function () {
    cargarConfiguracion();

    // Preview interactivo del logo
    $("#logo").on("change", function () {
        const archivo = this.files[0];
        if (archivo) {
            const lector = new FileReader();
            lector.onload = function (e) {
                $("#previewLogo").attr("src", e.target.result).removeClass("d-none");
                $("#placeholderLogo").addClass("d-none");
            };
            lector.readAsDataURL(archivo);
        }
    });

    // Guardar Perfil de la Empresa
    $("#formEmpresa").on("submit", function (e) {
        e.preventDefault();

        const $btn = $("#btnGuardarEmpresa");
        const textoOriginal = $btn.html();
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Guardando...');

        $("#formEmpresa .is-invalid").removeClass("is-invalid");
        $("#formEmpresa .invalid-feedback").remove();

        const formData = new FormData(this);

        $.ajax({
            url: urlGuardarEmpresa,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).html(textoOriginal);
                if (res.success) {
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: "success",
                            title: res.message || "Datos actualizados exitosamente",
                        });
                    }
                    if (res.data && res.data.logo) {
                        $("#previewLogo").attr("src", `/storage/${res.data.logo}?v=${Date.now()}`).removeClass("d-none");
                        $("#placeholderLogo").addClass("d-none");
                    }
                }
            },
            error: function (xhr) {
                $btn.prop("disabled", false).html(textoOriginal);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function (field, messages) {
                        const $input = $(`#${field}`);
                        if ($input.length) {
                            $input.addClass("is-invalid");
                            $input.after(`<div class="invalid-feedback fw-semibold">${messages[0]}</div>`);
                        }
                    });

                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: "error",
                            title: "Corrige los errores en el formulario",
                        });
                    }
                } else {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Error inesperado al guardar.";
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: "error",
                            title: "Error",
                            text: msg,
                        });
                    }
                }
            },
        });
    });

    // Guardar Tasas de Cambio
    $("#formTasasMonedas").on("submit", function (e) {
        e.preventDefault();

        const $btn = $("#btnGuardarTasas");
        const textoOriginal = $btn.html();
        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Actualizando...');

        const monedasData = [];
        $(".fila-moneda").each(function () {
            const codigo = $(this).data("codigo");
            const tasa = $(this).find(".input-tasa-cambio").val();
            const estado = $(this).find(".switch-estado-moneda").is(":checked") ? 1 : 0;

            monedasData.push({
                codigo: codigo,
                tasa_cambio: parseFloat(tasa) || 1,
                estado: estado,
            });
        });

        $.ajax({
            url: urlGuardarMonedas,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content") || $('input[name="_token"]').val(),
                monedas: monedasData,
            },
            dataType: "json",
            success: function (res) {
                $btn.prop("disabled", false).html(textoOriginal);
                if (res.success) {
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: "success",
                            title: res.message || "Tasas actualizadas exitosamente",
                        });
                    }
                    cargarConfiguracion();
                }
            },
            error: function (xhr) {
                $btn.prop("disabled", false).html(textoOriginal);
                const msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : "Error al actualizar las tasas de cambio.";
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "error",
                        title: "Error",
                        text: msg,
                    });
                }
            },
        });
    });
});

/**
 * Cargar configuración inicial de la empresa y monedas
 */
const cargarConfiguracion = async function () {
    try {
        const res = await $.ajax({
            url: urlDatos,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            const emp = res.data.empresa;
            const monedas = res.data.monedas || [];

            // Llenar datos de la empresa
            if (emp) {
                $("#rif").val(emp.rif || "");
                $("#nombre").val(emp.nombre || "");
                $("#razon_social").val(emp.razon_social || "");
                $("#telefono").val(emp.telefono || "");
                $("#correo").val(emp.correo || "");
                $("#direccion").val(emp.direccion || "");

                if (emp.logo) {
                    $("#previewLogo").attr("src", `/storage/${emp.logo}?v=${Date.now()}`).removeClass("d-none");
                    $("#placeholderLogo").addClass("d-none");
                } else {
                    $("#previewLogo").attr("src", "").addClass("d-none");
                    $("#placeholderLogo").removeClass("d-none");
                }
            }

            // Renderizar monedas secundarias
            renderizarMonedas(monedas);
        }
    } catch (e) {
        console.error("Error al cargar configuración:", e);
        $("#contenedorMonedas").html('<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-1"></i> No se pudo cargar las monedas.</div>');
    }
};

/**
 * Renderizar tarjetas de monedas y tasas de cambio
 */
const renderizarMonedas = function (monedas) {
    const $cont = $("#contenedorMonedas");
    $cont.empty();

    const secundarias = monedas.filter((m) => !m.es_principal);

    if (secundarias.length === 0) {
        $cont.html('<div class="text-center py-4 text-muted">No hay monedas secundarias configuradas.</div>');
        return;
    }

    secundarias.forEach((m) => {
        const fechaActualizacion = m.ultima_actualizacion_tasa
            ? new Date(m.ultima_actualizacion_tasa).toLocaleString("es-VE", { dateStyle: "short", timeStyle: "short" })
            : "No registrada";

        const checkedAttr = m.estado ? "checked" : "";
        const tasaFormateada = parseFloat(m.tasa_cambio || 1).toFixed(4);

        const cardHtml = `
            <div class="card border rounded-4 p-3 bg-light-subtle fila-moneda" data-codigo="${m.codigo}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-pill font-monospace px-3 py-1 fw-bold fs-6">${m.codigo} (${m.simbolo})</span>
                        <span class="fw-bold text-dark">${m.nombre}</span>
                    </div>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input switch-estado-moneda" type="checkbox" role="switch" id="switch_${m.codigo}" ${checkedAttr}>
                        <label class="form-check-label small fw-semibold text-muted" for="switch_${m.codigo}">Habilitada</label>
                    </div>
                </div>

                <div class="row g-2 align-items-center mt-1">
                    <div class="col-7">
                        <label class="form-label text-muted small mb-1 fw-semibold">Tasa de Cambio Oficial (1 ${m.simbolo} =)</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.0001" min="0.0001" class="form-control form-control-executive text-end fw-bold font-monospace input-tasa-cambio" value="${tasaFormateada}" placeholder="0.0000" required>
                            <span class="input-group-text fw-bold bg-white">Bs.</span>
                        </div>
                    </div>
                    <div class="col-5 text-end pt-3">
                        <small class="text-muted d-block" style="font-size: 0.70rem;">Última actualización:</small>
                        <span class="badge bg-light text-secondary border font-monospace py-0" style="font-size: 0.70rem;">${fechaActualizacion}</span>
                    </div>
                </div>
            </div>
        `;

        $cont.append(cardHtml);
    });
};
