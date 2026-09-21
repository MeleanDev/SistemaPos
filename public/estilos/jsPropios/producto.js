// URL limpia independiente de query parameters en la barra de navegación
const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlCatalogos = urlBase + "/catalogos";
const urlDetalles = urlBase + "/";
const urlEliminar = urlBase + "/";
const urlGuardar = urlBase;
const urlEditar = urlBase + "/actualizar/";
const urlGuardarProveedor = window.location.origin + "/proveedores";

let urlAccion = urlGuardar;
let isEditar = false;
let idProductoActual = null;
let tasaUsdActual = 1.0;
let catalogosSistema = {
    categorias: [],
    proveedores: [],
    almacenes: [],
    monedas: {}
};

let contadorFilasCodigos = 0;
let contadorFilasProveedores = 0;

/**
 * Auto-cálculo bidireccional en tiempo real de precios (USD <-> Bs.) según la tasa activa de la empresa
 */
/**
 * Auto-cálculo bidireccional en tiempo real de precios (USD <-> Bs.) y márgenes
 */
const alCambiarCostoUsd = function () {
    const costoUsd = parseFloat($("#precio_costo_usd").val()) || 0;
    const tasa = tasaUsdActual > 0 ? tasaUsdActual : 1.0;

    $("#precio_costo_bs").val(costoUsd > 0 ? (costoUsd * tasa).toFixed(2) : "");

    // Recalcular precios de venta según márgenes configurados
    calcularPrecioDetalDesdeMargenForm();
    calcularPrecioMayorDesdeMargenForm();
};

const calcularPrecioDetalDesdeMargenForm = function () {
    const costoUsd = parseFloat($("#precio_costo_usd").val()) || 0;
    const margen = parseFloat($("#ultimo_margen_detal").val()) || 0;
    const tasa = tasaUsdActual > 0 ? tasaUsdActual : 1.0;

    if (costoUsd > 0) {
        const precioUsd = costoUsd * (1 + margen / 100);
        $("#precio_detal_usd").val(precioUsd.toFixed(2));
        $("#precio_detal_bs").val((precioUsd * tasa).toFixed(2));
    } else {
        const pvpUsd = parseFloat($("#precio_detal_usd").val()) || 0;
        if (pvpUsd > 0) {
            $("#precio_detal_bs").val((pvpUsd * tasa).toFixed(2));
        }
    }
};

const calcularMargenDetalDesdePrecioForm = function () {
    const costoUsd = parseFloat($("#precio_costo_usd").val()) || 0;
    const pvpUsd = parseFloat($("#precio_detal_usd").val()) || 0;
    const tasa = tasaUsdActual > 0 ? tasaUsdActual : 1.0;

    $("#precio_detal_bs").val(pvpUsd > 0 ? (pvpUsd * tasa).toFixed(2) : "");

    if (costoUsd > 0 && pvpUsd > 0) {
        const margen = ((pvpUsd - costoUsd) / costoUsd) * 100;
        $("#ultimo_margen_detal").val(margen.toFixed(2));
    }
};

const calcularPrecioMayorDesdeMargenForm = function () {
    const costoUsd = parseFloat($("#precio_costo_usd").val()) || 0;
    const margen = parseFloat($("#ultimo_margen_mayorista").val()) || 0;
    const tasa = tasaUsdActual > 0 ? tasaUsdActual : 1.0;

    if (costoUsd > 0) {
        const precioUsd = costoUsd * (1 + margen / 100);
        $("#precio_mayorista_usd").val(precioUsd.toFixed(2));
        $("#precio_mayorista_bs").val((precioUsd * tasa).toFixed(2));
    } else {
        const pvpUsd = parseFloat($("#precio_mayorista_usd").val()) || 0;
        if (pvpUsd > 0) {
            $("#precio_mayorista_bs").val((pvpUsd * tasa).toFixed(2));
        }
    }
};

const calcularMargenMayorDesdePrecioForm = function () {
    const costoUsd = parseFloat($("#precio_costo_usd").val()) || 0;
    const pvpUsd = parseFloat($("#precio_mayorista_usd").val()) || 0;
    const tasa = tasaUsdActual > 0 ? tasaUsdActual : 1.0;

    $("#precio_mayorista_bs").val(pvpUsd > 0 ? (pvpUsd * tasa).toFixed(2) : "");

    if (costoUsd > 0 && pvpUsd > 0) {
        const margen = ((pvpUsd - costoUsd) / costoUsd) * 100;
        $("#ultimo_margen_mayorista").val(margen.toFixed(2));
    }
};

window.alCambiarCostoUsd = alCambiarCostoUsd;
window.calcularPrecioDetalDesdeMargenForm = calcularPrecioDetalDesdeMargenForm;
window.calcularMargenDetalDesdePrecioForm = calcularMargenDetalDesdePrecioForm;
window.calcularPrecioMayorDesdeMargenForm = calcularPrecioMayorDesdeMargenForm;
window.calcularMargenMayorDesdePrecioForm = calcularMargenMayorDesdePrecioForm;

$(document).ready(function () {
    cargarCatalogos();

    // Restricción a solo números para código interno
    $("#codigo_interno").on("input", function () {
        this.value = this.value.replace(/[^0-9]/g, "");
    });

    // Inicializar DataTable
    crearDataTable({
        selector: "#datatable_productos",
        url: urlLista,
        searchPlaceholder: "Escanear código de barra, SKU, nombre...",
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const nombre = (row.nombre || "").trim();
                    const sku = (row.codigo_interno || "").trim();
                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm shadow-xs rounded-3" style="width: 40px; height: 40px; min-width: 40px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5; font-size: 1.1rem;">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="fw-bold text-dark text-capitalize" style="font-size: 0.90rem; letter-spacing: -0.01em;">${nombre}</span>
                                </div>
                                <div>
                                    <span class="badge rounded-pill font-monospace px-2 py-1" style="font-size: 0.74rem; font-weight: 600; background-color: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1;">
                                        <i class="fas fa-hashtag text-primary me-1" style="font-size: 0.68rem;"></i>${sku}
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
                data: "codigos_barra",
                name: "codigosBarra.codigo_barra",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    if (!data || data.length === 0) {
                        return '<span class="badge rounded-pill px-2 py-1" style="font-size: 0.72rem; background-color: #f8fafc; color: #64748b; border: 1px dashed #cbd5e1;"><i class="fas fa-minus me-1"></i>Sin código</span>';
                    }

                    const principal = data[0].codigo_barra;
                    const extras = data.length > 1 ? `<span class="badge bg-primary text-white rounded-pill ms-1 px-2 py-0" style="font-size: 0.68rem; font-weight: 700;">+${data.length - 1}</span>` : '';

                    return `<span class="badge-documento font-monospace py-1 shadow-xs" style="background-color: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; font-weight: 700;"><i class="fas fa-barcode text-primary me-1"></i>${principal}</span>${extras}`;
                },
            },
            {
                data: "stock_almacenes",
                name: "stock_almacenes",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    if (row.tipo === "servicio") {
                        return '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.74rem; background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff;"><i class="fas fa-wrench me-1"></i>Servicio</span>';
                    }

                    let stockTotal = 0;
                    if (Array.isArray(data)) {
                        stockTotal = data.reduce((total, item) => total + parseFloat(item.cantidad_actual || 0), 0);
                    }

                    const unidad = row.unidad_medida || "und";
                    const stockMinimo = parseFloat(row.stock_minimo || 0);

                    if (stockTotal <= 0) {
                        return `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-bold" style="background-color: #fee2e2; color: #dc2626;"><i class="fas fa-times-circle me-1"></i>0 ${unidad}</span>`;
                    } else if (stockMinimo > 0 && stockTotal <= stockMinimo) {
                        return `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold" style="background-color: #fef3c7; color: #d97706;"><i class="fas fa-exclamation-triangle me-1"></i>${stockTotal} ${unidad}</span>`;
                    } else {
                        return `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold" style="background-color: #dcfce7; color: #16a34a;"><i class="fas fa-check-circle me-1"></i>${stockTotal} ${unidad}</span>`;
                    }
                },
            },
            {
                data: null,
                name: "precio_detal_usd",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const detalUsd = parseFloat(row.precio_detal_usd || 0);
                    const mayoristaUsd = parseFloat(row.precio_mayorista_usd || 0);

                    if (detalUsd <= 0 && mayoristaUsd <= 0) {
                        return '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.74rem; background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;"><i class="fas fa-truck-loading me-1"></i>Por Recepción</span>';
                    }

                    const detalBs = (detalUsd * tasaUsdActual).toFixed(2);
                    return `
                        <div class="d-flex flex-column py-1">
                            <span class="fw-bold font-monospace" style="font-size: 0.88rem; color: #0f172a;"><i class="fas fa-dollar-sign text-success me-1" style="font-size: 0.75rem;"></i>${detalUsd.toFixed(2)}</span>
                            <span class="font-monospace fw-semibold" style="font-size: 0.75rem; color: #64748b;">Bs. ${detalBs}</span>
                        </div>
                    `;
                },
            },
            {
                data: null,
                name: "aplica_iva",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const ivaBadge = row.aplica_iva
                        ? `<span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.72rem; background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;"><i class="fas fa-receipt me-1"></i>IVA ${parseFloat(row.iva_porcentaje || 0)}%</span>`
                        : '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.72rem; background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><i class="fas fa-shield-alt me-1 text-secondary"></i>Exento</span>';

                    const igtfBadge = row.aplica_igtf
                        ? `<span class="badge rounded-pill px-2 py-1 fw-semibold ms-1" style="font-size: 0.72rem; background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff;">IGTF</span>`
                        : '';

                    return `<div class="d-flex justify-content-center align-items-center">${ivaBadge}${igtfBadge}</div>`;
                },
            },
            {
                data: null,
                width: "130px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const nombreEscapado = (row.nombre || "").replace(/'/g, "\\'");
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="verFicha(${row.id});" title="Ficha Técnica 360°" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar producto" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Desactivar producto" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
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
 * Cargar catálogos (Categorías, Proveedores, Almacenes)
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
            if (res.data.tasa_usd) {
                tasaUsdActual = parseFloat(res.data.tasa_usd) || 1.0;
                $("#badgeTasaUsd").text(tasaUsdActual.toFixed(4));
                $("#badgeTasaUsdModal").text(tasaUsdActual.toFixed(4));
            }
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

const toggleIvaInput = function () {
    const checked = $("#aplica_iva").is(":checked");
    $("#iva_porcentaje").prop("disabled", !checked);
    if (!checked) {
        $("#iva_porcentaje").val("0.00");
    } else if (parseFloat($("#iva_porcentaje").val()) === 0) {
        $("#iva_porcentaje").val("16.00");
    }
};

const toggleIgtfInput = function () {
    const checked = $("#aplica_igtf").is(":checked");
    $("#igtf_porcentaje").prop("disabled", !checked);
    if (!checked) {
        $("#igtf_porcentaje").val("0.00");
    } else if (parseFloat($("#igtf_porcentaje").val()) === 0) {
        $("#igtf_porcentaje").val("3.00");
    }
};

/**
 * Filas dinámicas: Códigos de Barra / QR
 */
const agregarFilaCodigoBarra = function (codigo = "", descripcion = "") {
    contadorFilasCodigos++;
    const idFila = `fila_cb_${contadorFilasCodigos}`;

    const filaHtml = `
        <tr id="${idFila}">
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-qrcode text-primary"></i></span>
                    <input type="text" class="form-control" name="codigos_barra[${contadorFilasCodigos}][codigo]" value="${codigo}" placeholder="Ej. 759123456789 o QR-PROD-001" maxlength="100">
                </div>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="codigos_barra[${contadorFilasCodigos}][descripcion]" value="${descripcion}" placeholder="Ej. Empaque individual, QR etiqueta, Caja x12" maxlength="100">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" onclick="$('#${idFila}').remove()" style="width: 28px; height: 28px; padding: 0;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    $("#contenedorFilasCodigos").append(filaHtml);
};

/**
 * Filas dinámicas: Proveedores
 */
const agregarFilaProveedor = function (proveedorId = "", codigoProveedor = "") {
    contadorFilasProveedores++;
    const idFila = `fila_prov_${contadorFilasProveedores}`;

    let opcionesProveedores = '<option value="">Seleccione proveedor...</option>';
    if (Array.isArray(catalogosSistema.proveedores)) {
        catalogosSistema.proveedores.forEach((p) => {
            const selected = String(p.id) === String(proveedorId) ? "selected" : "";
            opcionesProveedores += `<option value="${p.id}" ${selected}>${p.nombre} (${p.rif})</option>`;
        });
    }

    const filaHtml = `
        <tr id="${idFila}">
            <td>
                <select class="form-select form-select-sm select-proveedor-fila" name="proveedores[${contadorFilasProveedores}][proveedor_id]">
                    ${opcionesProveedores}
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="proveedores[${contadorFilasProveedores}][codigo_proveedor]" value="${codigoProveedor}" placeholder="Código / SKU del proveedor" maxlength="100">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm rounded-circle" onclick="$('#${idFila}').remove()" style="width: 28px; height: 28px; padding: 0;">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    $("#contenedorFilasProveedores").append(filaHtml);
};

/**
 * Modal rápido para crear proveedor sin salir del modal de productos
 */
const abrirModalRapidoProveedor = function () {
    $("#formularioRapidoProveedor")[0].reset();
    $("#formularioRapidoProveedor .is-invalid").removeClass("is-invalid");
    $("#formularioRapidoProveedor .invalid-feedback").remove();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoProveedor"));
    modal.show();
};
window.abrirModalRapidoProveedor = abrirModalRapidoProveedor;

/**
 * Guardar proveedor rápido vía AJAX y agregarlo automáticamente
 */
$("#formularioRapidoProveedor").on("submit", async function (e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $("#modalRapidoProveedorBtnGuardar");
    const textoOriginal = $btn.html();

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();

    try {
        const res = await $.ajax({
            url: urlGuardarProveedor,
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
        });

        if (res.success && res.data) {
            const nuevoProv = res.data;

            // Actualizar catálogo local
            if (!Array.isArray(catalogosSistema.proveedores)) {
                catalogosSistema.proveedores = [];
            }
            catalogosSistema.proveedores.push(nuevoProv);

            // Actualizar opciones en selects existentes de proveedores
            $(".select-proveedor-fila").each(function () {
                const valorActual = $(this).val();
                let opciones = '<option value="">Seleccione proveedor...</option>';
                catalogosSistema.proveedores.forEach((p) => {
                    const sel = String(p.id) === String(valorActual) ? "selected" : "";
                    opciones += `<option value="${p.id}" ${sel}>${p.nombre} (${p.rif})</option>`;
                });
                $(this).html(opciones);
            });

            // Agregar una nueva fila vinculando automáticamente este proveedor
            agregarFilaProveedor(nuevoProv.id, "");

            // Cerrar modal de proveedor rápido
            const modal = bootstrap.Modal.getInstance(document.getElementById("modalRapidoProveedor"));
            if (modal) {
                modal.hide();
            }

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Proveedor Registrado",
                    text: `"${nuevoProv.nombre}" fue registrado y vinculado exitosamente.`,
                });
            }
        }
    } catch (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            Object.keys(errors).forEach((campo) => {
                const input = $(`#rapido_prov_${campo}`);
                if (input.length) {
                    input.addClass("is-invalid");
                    input.after(`<div class="invalid-feedback d-block">${errors[campo][0]}</div>`);
                }
            });
        } else {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON?.message || "No se pudo registrar el proveedor.",
                });
            }
        }
    } finally {
        $btn.prop("disabled", false).html(textoOriginal);
    }
});

/**
 * Abrir modal para crear nuevo producto
 */
const crear = function () {
    isEditar = false;
    idProductoActual = null;
    urlAccion = urlGuardar;

    $("#formularioProducto")[0].reset();
    $("#formularioProducto .is-invalid").removeClass("is-invalid");
    $("#formularioProducto .invalid-feedback").remove();

    $("#contenedorFilasCodigos").empty();
    $("#contenedorFilasProveedores").empty();

    // Resetear a pestaña inicial
    $("#tab-basicos-btn").tab("show");

    $("#modalProductoTitulo").text("Nuevo Producto");
    $("#modalProductoSubtitulo").text("Completa la información del producto físico");
    $("#modalProductoIcono").attr("class", "fas fa-boxes-stacked text-warning fs-5");
    $("#modalProductoTextoGuardar").text("Guardar");

    // Código interno automático
    $("#codigo_interno").val("").attr("placeholder", "[Generado automáticamente]");

    // Valores por defecto
    $("#tipo").val("producto");
    $("#unidad_medida").val("unidad");
    $("#precio_costo_usd").val("");
    $("#precio_costo_bs").val("");
    $("#ultimo_margen_detal").val("30.00");
    $("#precio_detal_usd").val("");
    $("#precio_detal_bs").val("");
    $("#ultimo_margen_mayorista").val("15.00");
    $("#precio_mayorista_usd").val("");
    $("#precio_mayorista_bs").val("");
    $("#stock_minimo").val("0");
    $("#stock_maximo").val("");
    $("#badgeTasaUsdModal").text(tasaUsdActual.toFixed(4));

    $("#aplica_iva").prop("checked", true);
    $("#iva_porcentaje").val("16.00").prop("disabled", false);
    $("#aplica_igtf").prop("checked", true);
    $("#igtf_porcentaje").val("3.00").prop("disabled", false);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalProducto"));
    modal.show();
};

/**
 * Abrir modal para editar producto
 */
const editar = async function (id) {
    try {
        isEditar = true;
        idProductoActual = id;
        urlAccion = urlEditar + id;

        const res = await consultarRegistro(urlDetalles, id);
        if (!res || !res.data) return;
        const prod = res.data;

        $("#formularioProducto")[0].reset();
        $("#formularioProducto .is-invalid").removeClass("is-invalid");
        $("#formularioProducto .invalid-feedback").remove();

        $("#contenedorFilasCodigos").empty();
        $("#contenedorFilasProveedores").empty();

        $("#tab-basicos-btn").tab("show");

        $("#modalProductoTitulo").text(`Editar: ${prod.nombre}`);
        $("#modalProductoSubtitulo").text("Modifica los datos del producto físico");
        $("#modalProductoIcono").attr("class", "fas fa-edit text-warning fs-5");
        $("#modalProductoTextoGuardar").text("Actualizar Cambios");

        // Llenar campos básicos
        $("#tipo").val("producto");
        $("#categoria_id").val(prod.categoria_id || "");
        $("#codigo_interno").val(prod.codigo_interno || "");
        $("#nombre").val(prod.nombre || "");
        $("#descripcion").val(prod.descripcion || "");
        $("#unidad_medida").val(prod.unidad_medida || "unidad");

        // Precios, Costos y Márgenes
        const costoUsd = parseFloat(prod.precio_costo_usd || 0);
        const costoBs = parseFloat(prod.precio_costo_bs || 0);
        const margenDetal = prod.ultimo_margen_detal !== null && prod.ultimo_margen_detal !== undefined ? parseFloat(prod.ultimo_margen_detal) : 30.00;
        const detalUsd = parseFloat(prod.precio_detal_usd || 0);
        const detalBs = parseFloat(prod.precio_detal_bs || 0);
        const margenMayor = prod.ultimo_margen_mayorista !== null && prod.ultimo_margen_mayorista !== undefined ? parseFloat(prod.ultimo_margen_mayorista) : 15.00;
        const mayorUsd = parseFloat(prod.precio_mayorista_usd || 0);
        const mayorBs = parseFloat(prod.precio_mayorista_bs || 0);

        $("#precio_costo_usd").val(costoUsd > 0 ? costoUsd.toFixed(2) : "");
        $("#precio_costo_bs").val(costoBs > 0 ? costoBs.toFixed(2) : (costoUsd > 0 ? (costoUsd * tasaUsdActual).toFixed(2) : ""));
        $("#ultimo_margen_detal").val(margenDetal.toFixed(2));
        $("#precio_detal_usd").val(detalUsd > 0 ? detalUsd.toFixed(2) : "");
        $("#precio_detal_bs").val(detalBs > 0 ? detalBs.toFixed(2) : (detalUsd > 0 ? (detalUsd * tasaUsdActual).toFixed(2) : ""));
        $("#ultimo_margen_mayorista").val(margenMayor.toFixed(2));
        $("#precio_mayorista_usd").val(mayorUsd > 0 ? mayorUsd.toFixed(2) : "");
        $("#precio_mayorista_bs").val(mayorBs > 0 ? mayorBs.toFixed(2) : (mayorUsd > 0 ? (mayorUsd * tasaUsdActual).toFixed(2) : ""));

        $("#stock_minimo").val(prod.stock_minimo || 0);
        $("#stock_maximo").val(prod.stock_maximo || "");
        $("#badgeTasaUsdModal").text(tasaUsdActual.toFixed(4));

        // Impuestos
        $("#aplica_iva").prop("checked", prod.aplica_iva);
        $("#iva_porcentaje").val(prod.iva_porcentaje || "16.00").prop("disabled", !prod.aplica_iva);
        $("#aplica_igtf").prop("checked", prod.aplica_igtf);
        $("#igtf_porcentaje").val(prod.igtf_porcentaje || "3.00").prop("disabled", !prod.aplica_igtf);

        // Códigos de barra
        if (Array.isArray(prod.codigos_barra)) {
            prod.codigos_barra.forEach((cb) => {
                agregarFilaCodigoBarra(cb.codigo_barra, cb.descripcion);
            });
        }

        // Proveedores
        if (Array.isArray(prod.producto_proveedores)) {
            prod.producto_proveedores.forEach((pp) => {
                agregarFilaProveedor(pp.proveedor_id, pp.codigo_proveedor);
            });
        }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalProducto"));
        modal.show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información del producto.",
            });
        }
    }
};

/**
 * Ficha Técnica 360° (Ver Detalles con Kardex)
 */
const verFicha = async function (id) {
    try {
        const res = await consultarRegistro(urlDetalles, id);
        if (!res || !res.data) return;
        const prod = res.data;

        let totalStock = 0;
        let tablaAlmacenesHtml = "";

        if (Array.isArray(prod.stock_almacenes) && prod.stock_almacenes.length > 0) {
            prod.stock_almacenes.forEach((stk) => {
                const cantidad = parseFloat(stk.cantidad_actual || 0);
                totalStock += cantidad;
                const nombreAlmacen = stk.almacen ? stk.almacen.nombre : "Almacén";
                const codigoAlmacen = stk.almacen ? stk.almacen.codigo : "";
                const pasillo = stk.ubicacion_pasillo || '<span class="text-muted fst-italic">No asignado</span>';

                tablaAlmacenesHtml += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary rounded-circle p-2"><i class="fas fa-warehouse"></i></span>
                                <div>
                                    <span class="fw-bold text-dark d-block">${nombreAlmacen}</span>
                                    <small class="text-muted font-monospace">${codigoAlmacen}</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-center font-monospace">${pasillo}</td>
                        <td class="text-end fw-bold font-monospace ${cantidad > 0 ? 'text-success' : 'text-danger'}">
                            ${cantidad} ${prod.unidad_medida}
                        </td>
                    </tr>
                `;
            });
        } else {
            tablaAlmacenesHtml = '<tr><td colspan="3" class="text-center text-muted py-3">No hay registros de almacén disponibles.</td></tr>';
        }

        let codigosHtml = "";
        if (Array.isArray(prod.codigos_barra) && prod.codigos_barra.length > 0) {
            prod.codigos_barra.forEach((cb) => {
                codigosHtml += `
                    <div class="col-md-6">
                        <div class="border rounded-3 p-2 d-flex align-items-center justify-content-between bg-light">
                            <span class="badge-documento font-monospace"><i class="fas fa-barcode"></i> ${cb.codigo_barra}</span>
                            <small class="text-secondary">${cb.descripcion || 'General'}</small>
                        </div>
                    </div>
                `;
            });
        } else {
            codigosHtml = '<div class="col-12 text-muted fst-italic py-2"><i class="fas fa-info-circle me-1"></i> Este producto no posee códigos de barra registrados. Se identifica por SKU/Nombre.</div>';
        }

        let proveedoresHtml = "";
        if (Array.isArray(prod.producto_proveedores) && prod.producto_proveedores.length > 0) {
            prod.producto_proveedores.forEach((pp) => {
                const prov = pp.proveedor || {};
                const costoUsd = pp.ultimo_costo_usd ? `$ ${parseFloat(pp.ultimo_costo_usd).toFixed(2)}` : '--';
                const costoBs = pp.ultimo_costo_bs ? `Bs. ${parseFloat(pp.ultimo_costo_bs).toFixed(2)}` : '--';
                proveedoresHtml += `
                    <div class="col-md-6">
                        <div class="border rounded-3 p-2.5 d-flex align-items-center justify-content-between bg-white shadow-xs">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                    <i class="fas fa-truck text-primary" style="font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 0.88rem;">${prov.nombre || 'Proveedor'}</strong>
                                    <small class="text-muted font-monospace"><i class="fas fa-id-card me-1"></i>${prov.rif || 'N/A'}</small>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace fw-bold" style="font-size: 0.74rem;">${costoUsd}</span>
                                <small class="text-muted d-block font-monospace" style="font-size: 0.70rem;">${costoBs}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            proveedoresHtml = '<div class="col-12 text-muted fst-italic py-2"><i class="fas fa-info-circle me-1"></i> No posee proveedores registrados. Se vinculan automáticamente al procesar recepciones.</div>';
        }

        // Histórico de Kardex
        let kardexHtml = "";
        if (Array.isArray(prod.movimientos_kardex) && prod.movimientos_kardex.length > 0) {
            prod.movimientos_kardex.forEach((mov) => {
                const esEntrada = ['entrada_recepcion', 'entrada_ajuste', 'entrada_traslado', 'entrada_anulacion'].includes(mov.tipo_movimiento);
                const badgeTipo = esEntrada
                    ? `<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 font-monospace"><i class="fas fa-arrow-down me-1"></i>${mov.tipo_movimiento.replace(/_/g, ' ')}</span>`
                    : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1 font-monospace"><i class="fas fa-arrow-up me-1"></i>${mov.tipo_movimiento.replace(/_/g, ' ')}</span>`;

                const cantFormateada = esEntrada ? `+${mov.cantidad}` : `-${mov.cantidad}`;
                const cantClass = esEntrada ? 'text-success' : 'text-danger';

                kardexHtml += `
                    <tr>
                        <td class="small text-muted font-monospace">${mov.fecha}</td>
                        <td class="fw-semibold small">${mov.almacen_nombre}</td>
                        <td class="text-capitalize small">${badgeTipo}</td>
                        <td class="small text-muted font-monospace">${mov.motivo || mov.documento_tipo || '--'}</td>
                        <td class="text-end font-monospace fw-bold ${cantClass}">${cantFormateada}</td>
                        <td class="text-end font-monospace small text-muted">${mov.stock_anterior} ➔ <span class="fw-bold text-dark">${mov.stock_nuevo}</span></td>
                        <td class="text-end font-monospace small">$ ${mov.costo_unitario_usd.toFixed(2)}</td>
                        <td class="small text-muted">${mov.usuario_nombre}</td>
                    </tr>
                `;
            });
        } else {
            kardexHtml = '<tr><td colspan="8" class="text-center text-muted py-3"><i class="fas fa-history me-1"></i> No se registran movimientos de Kardex para este producto aún.</td></tr>';
        }

        const costoBaseUsd = parseFloat(prod.precio_costo_usd || 0);
        const costoBaseBs = parseFloat(prod.precio_costo_bs || 0);
        const detalUsd = parseFloat(prod.precio_detal_usd || 0);
        const detalBs = parseFloat(prod.precio_detal_bs || 0);
        const mayoristaUsd = parseFloat(prod.precio_mayorista_usd || 0);
        const mayoristaBs = parseFloat(prod.precio_mayorista_bs || 0);
        const margenDetal = prod.ultimo_margen_detal !== null && prod.ultimo_margen_detal !== undefined ? parseFloat(prod.ultimo_margen_detal) : 30.00;
        const margenMayor = prod.ultimo_margen_mayorista !== null && prod.ultimo_margen_mayorista !== undefined ? parseFloat(prod.ultimo_margen_mayorista) : 15.00;

        const fichaHtml = `
            <div class="row g-4">
                <!-- ENCABEZADO 360 -->
                <div class="col-12">
                    <div class="card card-executive border-0 shadow-sm p-4" style="border-radius: 16px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-executive shadow-sm rounded-3" style="width: 56px; height: 56px; min-width: 56px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.15); color: #ffffff; font-size: 1.5rem;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h4 class="fw-bold mb-0 text-white">${prod.nombre}</h4>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 small text-white-50">
                                        <span><i class="fas fa-hashtag me-1"></i>SKU: <strong>${prod.codigo_interno}</strong></span>
                                        <span>•</span>
                                        <span><i class="fas fa-tag me-1"></i>Categoría: <strong>${prod.categoria ? prod.categoria.nombre : 'N/A'}</strong></span>
                                        <span>•</span>
                                        <span><i class="fas fa-balance-scale me-1"></i>Unidad: <strong>${prod.unidad_medida}</strong></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="d-block small text-white-50">Stock Total Consolidado</span>
                                <h3 class="fw-bold mb-0 text-warning font-monospace">${totalStock} ${prod.unidad_medida}</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PRECIOS Y FISCAL -->
                <div class="col-md-5">
                    <div class="card border rounded-4 p-3 h-100 shadow-sm">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-coins text-warning me-2"></i> Estructura de Precios & Fiscal</h6>
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Costo Base de Compra:</span>
                                ${costoBaseUsd > 0 ? `<strong class="text-dark font-monospace">$ ${costoBaseUsd.toFixed(2)} / Bs. ${costoBaseBs.toFixed(2)}</strong>` : '<span class="text-muted fst-italic">No establecido</span>'}
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Precio Detal (Margen ${margenDetal.toFixed(1)}%):</span>
                                ${detalUsd > 0 ? `<strong class="text-primary font-monospace fw-bold">$ ${detalUsd.toFixed(2)} / Bs. ${detalBs.toFixed(2)}</strong>` : '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;"><i class="fas fa-truck-loading me-1"></i>Por Recepción</span>'}
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Precio Mayorista (Margen ${margenMayor.toFixed(1)}%):</span>
                                ${mayoristaUsd > 0 ? `<strong class="font-monospace fw-bold" style="color: #7e22ce;">$ ${mayoristaUsd.toFixed(2)} / Bs. ${mayoristaBs.toFixed(2)}</strong>` : '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a;"><i class="fas fa-truck-loading me-1"></i>Por Recepción</span>'}
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">Stock Mínimo / Máximo:</span>
                                <strong class="font-monospace text-dark">${prod.stock_minimo || 0} / ${prod.stock_maximo || 'Sin límite'}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">IVA:</span>
                                <strong>${prod.aplica_iva ? `<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;"><i class="fas fa-receipt me-1"></i>Gravado (${prod.iva_porcentaje}%)</span>` : '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><i class="fas fa-shield-alt me-1"></i>Exento</span>'}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">IGTF (Divisas):</span>
                                <strong>${prod.aplica_igtf ? `<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff;">Aplica (${prod.igtf_porcentaje}%)</span>` : '<span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #f1f5f9; color: #94a3b8; border: 1px dashed #cbd5e1;">No Aplica</span>'}</strong>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- DISTRIBUCION EN ALMACENES -->
                <div class="col-md-7">
                    <div class="card border rounded-4 p-3 h-100 shadow-sm">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-warehouse text-primary me-2"></i> Existencias por Almacén</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Almacén</th>
                                        <th class="text-center">Ubicación</th>
                                        <th class="text-end">Stock Disponible</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tablaAlmacenesHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- PROVEEDORES VINCULADOS -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 shadow-sm">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-truck text-primary me-2"></i> Proveedores Suministradores</h6>
                        <div class="row g-2">
                            ${proveedoresHtml}
                        </div>
                    </div>
                </div>

                <!-- CODIGOS DE BARRA -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 shadow-sm">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-barcode text-primary me-2"></i> Códigos de Barra Registrados</h6>
                        <div class="row g-2">
                            ${codigosHtml}
                        </div>
                    </div>
                </div>

                <!-- HISTÓRICO DE MOVIMIENTOS KARDEX -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-history text-primary me-2"></i> Histórico Reciente de Movimientos (Kardex)</h6>
                            <span class="badge rounded-pill bg-light text-dark border font-monospace px-3 py-1">Últimos 30 movimientos</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light font-monospace small">
                                    <tr>
                                        <th>Fecha & Hora</th>
                                        <th>Almacén</th>
                                        <th>Tipo Movimiento</th>
                                        <th>Motivo / Doc</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Balance Stock</th>
                                        <th class="text-end">Costo Unitario ($)</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${kardexHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("#contenidoFichaProducto").html(fichaHtml);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalFichaProducto"));
        modal.show();
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la Ficha Técnica del producto.",
            });
        }
    }
};

/**
 * Guardar o Actualizar Producto
 */
$("#formularioProducto").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();
    const categoriaId = $("#categoria_id").val();

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
        modalSelector: "#modalProducto",
        tablaSelector: "#datatable_productos",
        btnSubmit: "#modalProductoBtnGuardar",
        textoGuardarOriginal: $("#modalProductoTextoGuardar").text(),
    });
});

/**
 * Desactivar Producto (Borrado Lógico)
 */
const eliminar = function (id, nombreProducto) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreProducto,
        tablaSelector: "#datatable_productos",
        titulo: "¿Desactivar Ítem?",
        mensaje: `Se modificará el estado de "${nombreProducto}". Podrás reactivarlo en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-sync-alt me-1"></i> Sí, desactivar',
    });
};
