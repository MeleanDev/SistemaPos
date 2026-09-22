const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlDatos = urlBase + "/datos";
const urlGuardarEmpresa = urlBase + "/empresa";
const urlGuardarMonedas = urlBase + "/monedas";

$(document).ready(function () {
    cargarConfiguracion();

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

    $("#formEmpresa").on("submit", function (e) {
        e.preventDefault();

        enviarFormulario({
            form: this,
            url: urlGuardarEmpresa,
            btnSubmit: "#btnGuardarEmpresa",
            onSuccess: function (res) {
                if (res.data && res.data.logo) {
                    $("#previewLogo").attr("src", `/storage/${res.data.logo}?v=${Date.now()}`).removeClass("d-none");
                    $("#placeholderLogo").addClass("d-none");
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

const cargarConfiguracion = async function () {
    try {
        const res = await peticionAjax({ url: urlDatos });

        if (res.success && res.data) {
            const emp = res.data.empresa;
            const monedas = res.data.monedas || [];

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

            renderizarMonedas(monedas);
        }
    } catch (e) {
        $("#contenedorMonedas").html('<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle me-1"></i> No se pudo cargar las monedas.</div>');
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

    secundarias.forEach((m) => {
        const fechaActualizacion = m.ultima_actualizacion_tasa
            ? new Date(m.ultima_actualizacion_tasa).toLocaleString("es-VE", { dateStyle: "short", timeStyle: "short" })
            : "No registrada";

        const checkedAttr = m.estado ? "checked" : "";
        const tasaFormateada = parseFloat(m.tasa_cambio || 1).toFixed(4);

        const cardHtml = `
            <div class="card border rounded-4 p-3 bg-white shadow-xs fila-moneda" data-codigo="${m.codigo}">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace px-3 py-1 fw-bold fs-6">${m.codigo} (${m.simbolo})</span>
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
                        <div class="input-group input-group-executive">
                            <input type="number" step="0.0001" min="0.0001" class="form-control form-control-executive text-end fw-bold font-monospace input-tasa-cambio" value="${tasaFormateada}" placeholder="0.0000" required>
                            <span class="input-group-text fw-bold">Bs.</span>
                        </div>
                    </div>
                    <div class="col-5 text-end pt-3">
                        <small class="text-muted d-block" style="font-size: 0.70rem;">Última actualización:</small>
                        <span class="badge bg-white text-secondary border rounded-pill shadow-xs font-monospace py-1 px-2.5" style="font-size: 0.72rem;">${fechaActualizacion}</span>
                    </div>
                </div>
            </div>
        `;

        $cont.append(cardHtml);
    });
};
