const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlDatos = `${urlBase}/datos`;
const urlGuardarEmpresa = `${urlBase}/empresa`;
const urlGuardarMonedas = `${urlBase}/monedas`;

let logoOriginalUrl = null;

$(document).ready(function () {
    cargarConfiguracion();
    inicializarEventosTicketPreview();
    inicializarEventosLogo();
    inicializarEventosCalculadora();

    // Restricciones de entrada numéricas para RIF y teléfono
    $("#rif_numero").on("input", function () {
        this.value = this.value.replace(/[^0-9kK\-]/g, "");
        actualizarRifCompleto();
        actualizarTicketPreview();
    });

    $("#rif_prefijo").on("change", function () {
        actualizarRifCompleto();
        actualizarTicketPreview();
    });

    $("#telefono_numero").on("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "");
        actualizarTelefonoCompleto();
        actualizarTicketPreview();
    });

    $("#telefono_prefijo").on("change", function () {
        actualizarTelefonoCompleto();
        actualizarTicketPreview();
    });

    // Restricción a máximo 2 decimales en tiempo real en los inputs de tasa
    $(document).on("input", ".input-tasa-cambio", function () {
        const val = this.value;
        if (val.includes(".")) {
            const partes = val.split(".");
            if (partes[1] && partes[1].length > 2) {
                this.value = `${partes[0]}.${partes[1].substring(0, 2)}`;
            }
        }
    });

    // Eventos del formulario Empresa
    $("#formEmpresa").on("submit", function (e) {
        e.preventDefault();
        actualizarRifCompleto();
        actualizarTelefonoCompleto();

        enviarFormulario({
            form: this,
            url: urlGuardarEmpresa,
            btnSubmit: "#btnGuardarEmpresa",
            textoGuardarOriginal: $("#btnGuardarEmpresa").html(),
            onSuccess: function (res) {
                if (res && res.data) {
                    if (res.data.logo) {
                        logoOriginalUrl = `/storage/${res.data.logo}?v=${Date.now()}`;
                        $("#previewLogo").attr("src", logoOriginalUrl).removeClass("d-none");
                        $("#placeholderLogo").addClass("d-none");
                        $("#btnQuitarLogo").removeClass("d-none");
                        $("#ticketPreviewLogo").attr("src", logoOriginalUrl);
                        $("#ticketPreviewLogoContainer").removeClass("d-none");
                    }
                }
            },
        });
    });

    // Eventos del formulario de Tasas de Moneda
    $("#formTasasMonedas").on("submit", function (e) {
        e.preventDefault();

        enviarFormulario({
            form: this,
            url: urlGuardarMonedas,
            btnSubmit: "#btnGuardarTasas",
            textoGuardarOriginal: $("#btnGuardarTasas").html(),
            antesDeEnviar: function (formData) {
                $(".fila-moneda").each(function (index) {
                    const codigo = $(this).data("codigo");
                    const tasa = $(this).find(".input-tasa-cambio").val();
                    const estado = $(this).find(".switch-estado-moneda").is(":checked") ? 1 : 0;

                    formData.append(`monedas[${index}][codigo]`, codigo);
                    formData.append(`monedas[${index}][tasa_cambio]`, parseFloat(tasa) || 1);
                    formData.append(`monedas[${index}][estado]`, estado);
                });
            },
            onSuccess: function () {
                cargarConfiguracion();
            },
        });
    });
});

const actualizarRifCompleto = function () {
    const prefijo = $("#rif_prefijo").val() || "J-";
    const numero = $("#rif_numero").val() ? $("#rif_numero").val().trim() : "";
    $("#rif").val(numero ? `${prefijo}${numero}` : "");
};

const actualizarTelefonoCompleto = function () {
    const prefijo = $("#telefono_prefijo").val() || "+58";
    const numero = $("#telefono_numero").val() ? $("#telefono_numero").val().trim() : "";
    $("#telefono").val(numero ? `${prefijo}${numero}` : "");
};

const inicializarEventosLogo = function () {
    $("#logo").on("change", function () {
        const archivo = this.files[0];
        if (archivo) {
            const lector = new FileReader();
            lector.onload = function (e) {
                $("#previewLogo").attr("src", e.target.result).removeClass("d-none");
                $("#placeholderLogo").addClass("d-none");
                $("#btnQuitarLogo").removeClass("d-none");

                // Actualizar en el preview del ticket
                $("#ticketPreviewLogo").attr("src", e.target.result);
                $("#ticketPreviewLogoContainer").removeClass("d-none");
            };
            lector.readAsDataURL(archivo);
        }
    });

    $("#btnQuitarLogo").on("click", function () {
        $("#logo").val("");
        if (logoOriginalUrl) {
            $("#previewLogo").attr("src", logoOriginalUrl).removeClass("d-none");
            $("#placeholderLogo").addClass("d-none");
            $("#ticketPreviewLogo").attr("src", logoOriginalUrl);
            $("#ticketPreviewLogoContainer").removeClass("d-none");
        } else {
            $("#previewLogo").attr("src", "").addClass("d-none");
            $("#placeholderLogo").removeClass("d-none");
            $("#ticketPreviewLogoContainer").addClass("d-none");
        }
        $(this).addClass("d-none");
    });
};

const inicializarEventosTicketPreview = function () {
    $("#nombre, #razon_social, #direccion, #correo").on("input", function () {
        actualizarTicketPreview();
    });
};

const actualizarTicketPreview = function () {
    const nombre = $("#nombre").val().trim() || "NOMBRE DE LA EMPRESA";
    const razonSocial = $("#razon_social").val().trim() || "Razón Social Legal";
    const rifCompleto = $("#rif").val().trim() || "RIF: J-00000000-0";
    const direccion = $("#direccion").val().trim() || "Dirección fiscal de la empresa";
    const telefono = $("#telefono").val().trim() || "No especificado";
    const correo = $("#correo").val().trim() || "No especificado";

    $("#ticketPreviewNombre").text(nombre.toUpperCase());
    $("#ticketPreviewRazonSocial").text(razonSocial);
    $("#ticketPreviewRif").text(`RIF: ${rifCompleto}`);
    $("#ticketPreviewDireccion").text(direccion);
    $("#ticketPreviewContacto").text(`Telf: ${telefono} | Email: ${correo}`);
};

const inicializarEventosCalculadora = function () {
    $("#calcMonto, #calcMoneda").on("input change", function () {
        ejecutarCalculoPrueba();
    });

    $(document).on("input change", ".input-tasa-cambio", function () {
        ejecutarCalculoPrueba();
    });
};

const ejecutarCalculoPrueba = function () {
    const monto = parseFloat($("#calcMonto").val()) || 0;
    const codigoMoneda = $("#calcMoneda").val();

    let tasa = 1;
    let simbolo = "$";

    const $fila = $(`.fila-moneda[data-codigo="${codigoMoneda}"]`);
    if ($fila.length) {
        const valInput = parseFloat($fila.find(".input-tasa-cambio").val());
        tasa = !isNaN(valInput) && valInput > 0 ? valInput : 1;
        simbolo = $fila.data("simbolo") || "$";
    }

    const resultadoBs = monto * tasa;
    const formateadoBs = new Intl.NumberFormat("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(resultadoBs);

    $("#calcResultado").text(`${formateadoBs} Bs.`);
    $("#calcDetalleTasa").text(
        `Calculado: ${monto.toFixed(2)} ${simbolo} × ${tasa.toFixed(2)} Bs. = ${formateadoBs} Bs.`
    );
};

const cargarConfiguracion = async function () {
    try {
        const res = await peticionAjax({ url: urlDatos });

        if (res && res.success && res.data) {
            const emp = res.data.empresa;
            const monedas = res.data.monedas || [];

            if (emp) {
                // Desglosar RIF si existe helper
                if (typeof window.desglosarCedula === "function" && emp.rif) {
                    const r = window.desglosarCedula(emp.rif);
                    $("#rif_prefijo").val(r.tipo || r.prefijo || "J-");
                    $("#rif_numero").val(r.numero || "");
                } else if (emp.rif) {
                    const match = emp.rif.match(/^([JVGEPjvgep]-?)(.+)$/);
                    if (match) {
                        const pref = (match[1].replace("-", "") + "-").toUpperCase();
                        $("#rif_prefijo").val(pref);
                        $("#rif_numero").val(match[2]);
                    } else {
                        $("#rif_numero").val(emp.rif);
                    }
                }
                actualizarRifCompleto();

                // Desglosar Teléfono si existe helper
                if (typeof window.desglosarTelefono === "function" && emp.telefono) {
                    const tel = window.desglosarTelefono(emp.telefono);
                    $("#telefono_prefijo").val(tel.codigo || tel.prefijo || "+58");
                    $("#telefono_numero").val(tel.numero || "");
                } else if (emp.telefono) {
                    $("#telefono_numero").val(emp.telefono);
                }
                actualizarTelefonoCompleto();

                $("#nombre").val(emp.nombre || "");
                $("#razon_social").val(emp.razon_social || "");
                $("#correo").val(emp.correo || "");
                $("#direccion").val(emp.direccion || "");
                $("#maneja_motos").prop("checked", Boolean(emp.maneja_motos));
                $("#maneja_vendedores").prop("checked", Boolean(emp.maneja_vendedores));

                if (emp.logo) {
                    logoOriginalUrl = `/storage/${emp.logo}?v=${Date.now()}`;
                    $("#previewLogo").attr("src", logoOriginalUrl).removeClass("d-none");
                    $("#placeholderLogo").addClass("d-none");
                    $("#btnQuitarLogo").removeClass("d-none");

                    $("#ticketPreviewLogo").attr("src", logoOriginalUrl);
                    $("#ticketPreviewLogoContainer").removeClass("d-none");
                } else {
                    logoOriginalUrl = null;
                    $("#previewLogo").attr("src", "").addClass("d-none");
                    $("#placeholderLogo").removeClass("d-none");
                    $("#btnQuitarLogo").addClass("d-none");
                    $("#ticketPreviewLogoContainer").addClass("d-none");
                }

                actualizarTicketPreview();
            }

            renderizarMonedas(monedas);
            ejecutarCalculoPrueba();
        }
    } catch (e) {
        $("#contenedorMonedas").html(
            '<div class="text-center py-4 text-danger"><i class="fas fa-triangle-exclamation me-1"></i> No se pudo cargar las monedas.</div>'
        );
    }
};

const renderizarMonedas = function (monedas) {
    const $cont = $("#contenedorMonedas");
    $cont.empty();

    const secundarias = monedas.filter((m) => !m.es_principal);

    if (secundarias.length === 0) {
        $cont.html('<div class="text-center py-4 text-muted">No hay monedas secundarias configuradas.</div>');
        return;
    }

    const ahora = new Date().getTime();
    const veinticuatroHorasMs = 24 * 60 * 60 * 1000;

    secundarias.forEach((m) => {
        let fechaActualizacion = "Sin registros previos";
        let esDesactualizada = false;

        if (m.ultima_actualizacion_tasa) {
            const fechaTasa = new Date(m.ultima_actualizacion_tasa);
            fechaActualizacion = fechaTasa.toLocaleString("es-VE", {
                dateStyle: "short",
                timeStyle: "short",
            });

            if (ahora - fechaTasa.getTime() > veinticuatroHorasMs) {
                esDesactualizada = true;
            }
        } else {
            esDesactualizada = true;
        }

        const checkedAttr = m.estado ? "checked" : "";
        const tasaFormateada = parseFloat(m.tasa_cambio || 1).toFixed(2);

        const badgeActualizacion = esDesactualizada
            ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill font-monospace px-2.5 py-1 text-wrap text-start text-sm-end" title="Tasa con más de 24 horas sin actualizar" style="font-size: 0.70rem; line-height: 1.3;">
                <i class="fas fa-clock-rotate-left me-1"></i> Desactualizada <span class="d-block d-sm-inline">(${fechaActualizacion})</span>
               </span>`
            : `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2.5 py-1 text-wrap text-start text-sm-end" style="font-size: 0.70rem; line-height: 1.3;">
                <i class="fas fa-check-double me-1"></i> Al día <span class="d-block d-sm-inline">(${fechaActualizacion})</span>
               </span>`;

        const cardHtml = `
            <div class="card border rounded-4 p-3 bg-white shadow-xs fila-moneda" data-codigo="${m.codigo}" data-simbolo="${m.simbolo}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-2.5 py-1 fw-bold fs-6">
                            ${m.codigo} (${m.simbolo})
                        </span>
                        <span class="fw-bold text-dark text-break" style="font-size: 0.92rem;">${m.nombre}</span>
                    </div>
                    <div class="form-check form-switch mb-0 ms-auto">
                        <input class="form-check-input switch-estado-moneda cursor-pointer" type="checkbox" role="switch" id="switch_${m.codigo}" ${checkedAttr}>
                        <label class="form-check-label small fw-semibold text-muted cursor-pointer ms-1" for="switch_${m.codigo}">Operativa</label>
                    </div>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-7 col-lg-12 col-xxl-7">
                        <label class="form-label text-muted small mb-1 fw-semibold d-block" for="input_tasa_${m.codigo}">
                            Tasa de Cambio Oficial (1 ${m.simbolo} =)
                        </label>
                        <div class="input-group input-group-executive">
                            <input type="number" step="0.01" min="0.01" id="input_tasa_${m.codigo}" class="form-control form-control-executive text-end fw-bold font-monospace input-tasa-cambio" value="${tasaFormateada}" placeholder="0.00" required>
                            <span class="input-group-text fw-bold bg-white text-dark font-monospace">Bs.</span>
                        </div>
                    </div>
                    <div class="col-12 col-md-5 col-lg-12 col-xxl-5 text-start text-md-end text-lg-start text-xxl-end">
                        <small class="text-muted d-block mb-1" style="font-size: 0.70rem;">Estatus de cotización:</small>
                        ${badgeActualizacion}
                    </div>
                </div>
            </div>
        `;

        $cont.append(cardHtml);
    });
};
