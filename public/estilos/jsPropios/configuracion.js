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

    $(document).on("input", ".input-tasa-cambio", function () {
        const val = this.value;
        if (val.includes(".")) {
            const partes = val.split(".");
            if (partes[1] && partes[1].length > 4) {
                this.value = `${partes[0]}.${partes[1].substring(0, 4)}`;
            }
        }
    });

    $("#formEmpresa").on("submit", function (e) {
        e.preventDefault();
        actualizarRifCompleto();
        actualizarTelefonoCompleto();

        enviarFormulario({
            form: this,
            url: urlGuardarEmpresa,
            btnSubmit: "#btnGuardarEmpresa",
            textoGuardarOriginal: $("#btnGuardarEmpresa").html(),
            antesDeEnviar: function (formData) {
                if ($("#usar_formula_costo_tc_menor").length && !$("#usar_formula_costo_tc_menor").prop("disabled")) {
                    formData.set("usar_formula_costo_tc_menor", $("#usar_formula_costo_tc_menor").is(":checked") ? 1 : 0);
                }
                formData.set("maneja_motos", $("#maneja_motos").is(":checked") ? 1 : 0);
                formData.set("maneja_vendedores", $("#maneja_vendedores").is(":checked") ? 1 : 0);
            },
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
                    const estado = $(this).find(".switch-estado-moneda").length
                        ? ($(this).find(".switch-estado-moneda").is(":checked") ? 1 : 0)
                        : 1;

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
    } else {
        const $opt = $(`#calcMoneda option[value="${codigoMoneda}"]`);
        if ($opt.length) {
            tasa = parseFloat($opt.data("tasa")) || 1;
            simbolo = $opt.data("simbolo") || "$";
        }
    }

    const resultadoBs = monto * tasa;
    const formateadoBs = new Intl.NumberFormat("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(resultadoBs);

    const tasaTexto = tasa.toLocaleString("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 4,
    });

    $("#calcResultado").text(`${formateadoBs} Bs.`);
    $("#calcDetalleTasa").text(
        `Calculado: ${monto.toFixed(2)} ${simbolo} × ${tasaTexto} Bs. = ${formateadoBs} Bs.`
    );
};

const cargarConfiguracion = async function () {
    try {
        const res = await peticionAjax({ url: urlDatos });

        if (res && res.success && res.data) {
            const emp = res.data.empresa;
            const monedas = res.data.monedas || [];

            if (emp) {
                if (typeof desglosarCedula === "function" && emp.rif) {
                    const r = desglosarCedula(emp.rif);
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

                if (typeof desglosarTelefono === "function" && emp.telefono) {
                    const tel = desglosarTelefono(emp.telefono);
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
                $("#usar_formula_costo_tc_menor").prop("checked", Boolean(emp.usar_formula_costo_tc_menor));

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

    const secundarias = (monedas || []).filter((m) => m.codigo !== "VES");

    if (secundarias.length === 0) {
        $cont.html('<div class="text-center py-4 text-muted">No hay monedas secundarias configuradas.</div>');
        poblarSelectMonedas([]);
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
        const tasaNum = parseFloat(m.tasa_cambio || 1);
        const tasaFormateada = (tasaNum % 1 === 0) ? tasaNum.toFixed(2) : (tasaNum.toString().split(".")[1]?.length > 2 ? tasaNum.toString() : tasaNum.toFixed(2));

        const badgeActualizacion = esDesactualizada
            ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill font-monospace px-2.5 py-1 text-wrap text-start text-sm-end" title="Tasa con más de 24 horas sin actualizar" style="font-size: 0.70rem; line-height: 1.3;">
                <i class="fas fa-clock-rotate-left me-1"></i> Desactualizada <span class="d-block d-sm-inline">(${fechaActualizacion})</span>
               </span>`
            : `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2.5 py-1 text-wrap text-start text-sm-end" style="font-size: 0.70rem; line-height: 1.3;">
                <i class="fas fa-check-double me-1"></i> Al día <span class="d-block d-sm-inline">(${fechaActualizacion})</span>
               </span>`;

        const switchHtml = m.codigo === "USD"
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2.5 py-1">
                <i class="fas fa-check me-1"></i> Referencia Activa
               </span>`
            : `<div class="form-check form-switch mb-0 ms-auto">
                <input class="form-check-input switch-estado-moneda cursor-pointer" type="checkbox" role="switch" id="switch_${m.codigo}" ${checkedAttr}>
                <label class="form-check-label small fw-semibold text-muted cursor-pointer ms-1" for="switch_${m.codigo}">Operativa</label>
               </div>`;

        const cardHtml = `
            <div class="card border rounded-4 p-3 bg-white shadow-xs fila-moneda" data-codigo="${m.codigo}" data-simbolo="${m.simbolo}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-2.5 py-1 fw-bold fs-6">
                            ${m.codigo} (${m.simbolo})
                        </span>
                        <span class="fw-bold text-dark text-break" style="font-size: 0.92rem;">${m.nombre}</span>
                    </div>
                    ${switchHtml}
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-7 col-lg-12 col-xxl-7">
                        <label class="form-label text-muted small mb-1 fw-semibold d-block" for="input_tasa_${m.codigo}">
                            Tasa de Cambio Oficial (1 ${m.simbolo} =)
                        </label>
                        <div class="input-group input-group-executive">
                            <input type="number" step="any" min="0.0001" id="input_tasa_${m.codigo}" class="form-control form-control-executive text-end fw-bold font-monospace input-tasa-cambio" value="${tasaFormateada}" placeholder="0.00" required>
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

    poblarSelectMonedas(secundarias);
};

const poblarSelectMonedas = function (monedas) {
    const $sel = $("#calcMoneda");
    const valorActual = $sel.val();
    $sel.empty();

    const disponibles = (monedas || []).filter((m) => m.codigo !== "VES" && m.estado);

    if (disponibles.length === 0) {
        $sel.append('<option value="" disabled selected>Sin monedas habilitadas</option>');
        return;
    }

    disponibles.forEach((m) => {
        $sel.append(
            `<option value="${m.codigo}" data-simbolo="${m.simbolo}" data-tasa="${m.tasa_cambio}">${m.codigo} (${m.simbolo})</option>`
        );
    });

    if (valorActual && disponibles.some((m) => m.codigo === valorActual)) {
        $sel.val(valorActual);
    } else {
        $sel.val(disponibles[0].codigo);
    }
};
