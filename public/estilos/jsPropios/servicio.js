// URL limpia independiente de query parameters en la barra de navegación
const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlCatalogos = urlBase + "/catalogos";
const urlDetalles = urlBase + "/";
const urlEliminar = urlBase + "/";
const urlGuardar = urlBase;
const urlEditar = urlBase + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idServicioActual = null;
let tasaUsdActual = 1.0000;
let catalogosSistema = {
    categorias: [],
};

$(document).ready(function () {
    cargarCatalogos();

    // Inicializar DataTable
    crearDataTable({
        selector: "#datatable_servicios",
        url: urlLista,
        searchPlaceholder: "Buscar código, nombre del servicio o categoría...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombre = (row.nombre || "").trim();
                    const codigo = row.codigo || "S/C";

                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm shadow-xs rounded-3" style="width: 40px; height: 40px; min-width: 40px; display: flex; align-items: center; justify-content: center; background-color: #f3e8ff; color: #7e22ce; font-size: 1.1rem;">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize mb-1" style="font-size: 0.90rem; letter-spacing: -0.01em;">${nombre}</span>
                                <div>
                                    <span class="badge rounded-pill font-monospace px-2 py-1" style="font-size: 0.74rem; font-weight: 600; background-color: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">
                                        <i class="fas fa-hashtag text-primary me-1" style="font-size: 0.68rem;"></i>${codigo}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "categoria",
                name: "categoria.nombre",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<span class="badge rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"><i class="fas fa-tag text-primary me-1"></i>${data.nombre}</span>`
                        : '<span class="badge rounded-pill px-2 py-1" style="font-size: 0.72rem; background-color: #f8fafc; color: #94a3b8; border: 1px dashed #cbd5e1;"><i class="fas fa-minus me-1"></i>Sin categoría</span>';
                },
            },
            {
                data: null,
                name: "precio_venta_usd",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const ventaUsd = parseFloat(row.precio_venta_usd || 0);
                    const ventaBs = (ventaUsd * tasaUsdActual).toFixed(2);

                    return `
                        <div class="d-flex flex-column py-1">
                            <span class="fw-bold font-monospace" style="font-size: 0.88rem; color: #0f172a;"><i class="fas fa-dollar-sign text-success me-1" style="font-size: 0.75rem;"></i>${ventaUsd.toFixed(2)}</span>
                            <span class="font-monospace fw-semibold" style="font-size: 0.75rem; color: #64748b;">Bs. ${ventaBs}</span>
                        </div>
                    `;
                },
            },
            {
                data: "aplica_iva",
                name: "aplica_iva",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    const aplicaIva = !!data;
                    const porc = parseFloat(row.iva_porcentaje || 16.00).toFixed(0);

                    return aplicaIva
                        ? `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1" style="font-size: 0.75rem;"><i class="fas fa-percentage me-1"></i>IVA ${porc}%</span>`
                        : `<span class="badge rounded-pill bg-light text-secondary border font-monospace px-2.5 py-1" style="font-size: 0.75rem;"><i class="fas fa-ban me-1"></i>Exento</span>`;
                },
            },
            {
                data: null,
                width: "100px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar servicio" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Desactivar servicio" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`;
                },
            },
        ],
    });

    aplicarRestriccionesInput();
});

/**
 * Cargar catálogos de categorías y tasa activa
 */
const cargarCatalogos = async function () {
    try {
        const res = await $.ajax({
            url: urlCatalogos,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            catalogosSistema = res.data;
            tasaUsdActual = parseFloat(res.data.tasa_usd) || 1.0000;
            $("#badgeTasaUsd").text(tasaUsdActual.toFixed(4));
            poblarSelectCategorias();
        }
    } catch (e) {
        console.error("Error al cargar catálogos:", e);
    }
};

const poblarSelectCategorias = function () {
    const $select = $("#categoria_id");
    $select.empty().append('<option value="">Seleccione una categoría...</option>');

    if (Array.isArray(catalogosSistema.categorias)) {
        catalogosSistema.categorias.forEach((cat) => {
            $select.append(`<option value="${cat.id}">[${cat.codigo}] ${cat.nombre}</option>`);
        });
    }
};

/**
 * Alternar visibilidad del porcentaje de IVA según el switch
 */
const toggleIvaInput = function () {
    const aplicaIva = $("#aplica_iva").is(":checked");
    if (aplicaIva) {
        $("#contenedorIvaPorcentaje").show();
        if (!$("#iva_porcentaje").val() || parseFloat($("#iva_porcentaje").val()) === 0) {
            $("#iva_porcentaje").val("16.00");
        }
    } else {
        $("#contenedorIvaPorcentaje").hide();
    }
};
window.toggleIvaInput = toggleIvaInput;

/**
 * Cálculo bidireccional en tiempo real de precios (USD <-> Bs.) según la tasa activa
 */
const calcularPreciosBsDesdeUsd = function () {
    if (!tasaUsdActual || tasaUsdActual <= 0) return;
    const ventaUsd = parseFloat($("#precio_venta_usd").val()) || 0;
    $("#precio_venta_bs").val(ventaUsd > 0 ? (ventaUsd * tasaUsdActual).toFixed(4) : '');
};

const calcularPreciosUsdDesdeBs = function () {
    if (!tasaUsdActual || tasaUsdActual <= 0) return;
    const ventaBs = parseFloat($("#precio_venta_bs").val()) || 0;
    $("#precio_venta_usd").val(ventaBs > 0 ? (ventaBs / tasaUsdActual).toFixed(4) : '');
};

window.calcularPreciosBs = calcularPreciosBsDesdeUsd;
window.calcularPreciosBsDesdeUsd = calcularPreciosBsDesdeUsd;
window.calcularPreciosUsdDesdeBs = calcularPreciosUsdDesdeBs;

/**
 * Abrir modal para crear nuevo servicio
 */
const crear = function () {
    isEditar = false;
    idServicioActual = null;
    urlAccion = urlGuardar;

    $("#formularioServicio")[0].reset();
    $("#formularioServicio .is-invalid").removeClass("is-invalid");
    $("#formularioServicio .invalid-feedback").remove();

    // Default IVA
    $("#aplica_iva").prop("checked", false);
    $("#iva_porcentaje").val("16.00");
    toggleIvaInput();

    $("#badgeTasaUsd").text(tasaUsdActual.toFixed(4));
    $("#modalServicioTitulo").text("Nuevo Servicio");
    $("#modalServicioSubtitulo").text("Completa la información del servicio profesional");
    $("#modalServicioIcono").attr("class", "fas fa-wrench text-warning fs-5");
    $("#modalServicioTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalServicio"));
    modal.show();
};

/**
 * Abrir modal para editar servicio
 */
const editar = async function (id) {
    try {
        isEditar = true;
        idServicioActual = id;
        urlAccion = urlEditar + id;

        const res = await consultarRegistro(urlDetalles, id);
        if (!res || !res.data) return;
        const srv = res.data;

        $("#formularioServicio")[0].reset();
        $("#formularioServicio .is-invalid").removeClass("is-invalid");
        $("#formularioServicio .invalid-feedback").remove();

        $("#badgeTasaUsd").text(tasaUsdActual.toFixed(4));
        $("#modalServicioTitulo").text(`Editar: ${srv.nombre}`);
        $("#modalServicioSubtitulo").text("Modifica los datos del servicio");
        $("#modalServicioIcono").attr("class", "fas fa-edit text-warning fs-5");
        $("#modalServicioTextoGuardar").text("Actualizar Cambios");

        // Llenar campos
        $("#categoria_id").val(srv.categoria_id || "");
        $("#codigo").val(srv.codigo || "");
        $("#nombre").val(srv.nombre || "");
        $("#descripcion").val(srv.descripcion || "");

        // Precios en USD y auto-cálculo en Bs.
        $("#precio_venta_usd").val(srv.precio_venta_usd || "");
        calcularPreciosBs();

        // Régimen Fiscal (IVA)
        const aplicaIva = !!srv.aplica_iva;
        $("#aplica_iva").prop("checked", aplicaIva);
        $("#iva_porcentaje").val(srv.iva_porcentaje !== null && srv.iva_porcentaje !== undefined ? parseFloat(srv.iva_porcentaje).toFixed(2) : "16.00");
        toggleIvaInput();

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalServicio"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del servicio.",
            });
        }
    }
};

/**
 * Guardar o Actualizar Servicio
 */
$("#formularioServicio").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();
    const codigo = $("#codigo").val().trim();
    const categoriaId = $("#categoria_id").val();

    if (codigo.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El código debe tener al menos 2 caracteres",
            });
        }
    }

    if (!categoriaId) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Debe seleccionar una categoría",
            });
        }
    }

    if (nombre.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El nombre debe tener al menos 2 caracteres",
            });
        }
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalServicio",
        tablaSelector: "#datatable_servicios",
        btnSubmit: "#modalServicioBtnGuardar",
        textoGuardarOriginal: $("#modalServicioTextoGuardar").text(),
    });
});

/**
 * Desactivar Servicio (Borrado Lógico)
 */
const eliminar = function (id, nombreServicio) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreServicio,
        tablaSelector: "#datatable_servicios",
        titulo: "¿Desactivar Servicio?",
        mensaje: `Se modificará el estado de "${nombreServicio}". Podrás reactivarlo en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, desactivar',
    });
};
