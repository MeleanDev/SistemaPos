const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlListaMotos = `${urlBase}/lista`;
const urlCatalogosMotos = `${urlBase}/catalogos`;
const urlDetallesMoto = `${urlBase}/`;
const urlEditarMoto = `${urlBase}/actualizar/`;
const urlCambiarEstadoMoto = `${urlBase}/`;

const urlBaseModelos = `${window.location.origin}/modelos-motos`;
const urlListaModelos = `${urlBaseModelos}/lista`;
const urlCatalogosModelos = `${urlBaseModelos}/catalogos`;
const urlProximaReferenciaModelo = `${urlBaseModelos}/proxima-referencia`;
const urlGuardarModelo = urlBaseModelos;
const urlEditarModelo = `${urlBaseModelos}/actualizar/`;
const urlEliminarModelo = `${urlBaseModelos}/`;

let isEditarModelo = false;
let idModeloActual = null;
let idMotoActual = null;
let tasaBcvActual = 1.0000;
let tasaCompraActual = 1.0000;
let tasaVentaActual = 1.0000;
let monedaSimboloActual = "Bs.";
let catalogosSistema = {
    almacenes: [],
};

$(document).ready(function () {
    inicializarTablaModelos();
    inicializarTablaMotos();
    cargarCatalogos();
    inicializarEventos();
});

const inicializarTablaModelos = function () {
    crearDataTable({
        selector: "#datatable_modelos",
        url: urlListaModelos,
        searchPlaceholder: "Buscar por referencia, marca, modelo, color...",
        columns: [
            {
                data: "referencia",
                name: "referencia",
                className: "text-center align-middle",
                render: function (data) {
                    return `
                        <span class="badge rounded-pill font-monospace fw-bold px-3 py-1.5 shadow-xs"
                            style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.84rem;">
                            # ${data || "—"}
                        </span>
                    `;
                }
            },
            {
                data: "modelo",
                name: "modelo",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm rounded-3 shadow-xs"
                                style="width: 36px; height: 36px; min-width: 36px; display: flex; align-items: center; justify-content: center; background-color: #f1f5f9; color: #0f172a; font-size: 1rem;">
                                <i class="fas fa-layer-group text-primary"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.88rem;">${row.marca || ""} ${data || ""}</span>
                                <small class="text-muted" style="font-size: 0.74rem;">${row.descripcion ? row.descripcion.substring(0, 45) + '...' : 'Modelo de catálogo'}</small>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: "anio",
                name: "anio",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    return `
                        <div class="d-flex flex-column align-items-center py-1">
                            <span class="fw-bold text-dark font-monospace" style="font-size: 0.88rem;">${data || "—"}</span>
                            <span class="badge rounded-pill px-2.5 py-0.5 text-capitalize fw-semibold mt-0.5"
                                style="font-size: 0.72rem; background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;">
                                ${row.color || "Sin color"}
                            </span>
                        </div>
                    `;
                }
            },
            {
                data: "cilindrada",
                name: "cilindrada",
                className: "text-center align-middle",
                render: function (data) {
                    return `<span class="badge rounded-pill bg-white text-secondary font-monospace border px-2.5 py-1" style="font-size: 0.76rem;">${data || "N/A"}</span>`;
                }
            },
            {
                data: "stock_disponible",
                name: "stock_disponible",
                className: "text-center align-middle",
                render: function (data) {
                    const cant = parseInt(data || 0);
                    if (cant > 0) {
                        return `
                            <span class="badge rounded-pill px-3 py-1.5 fw-bold font-monospace shadow-xs"
                                style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 0.82rem;">
                                <i class="fas fa-check-circle me-1"></i> ${cant} Disponibles
                            </span>
                        `;
                    }
                    return `
                        <span class="badge rounded-pill px-3 py-1.5 fw-semibold font-monospace"
                            style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.80rem;">
                            Sin Stock
                        </span>
                    `;
                }
            },
            {
                data: "total_unidades",
                name: "total_unidades",
                className: "text-center align-middle",
                render: function (data) {
                    return `<span class="font-monospace fw-bold text-secondary" style="font-size: 0.84rem;">${data || 0} Unds</span>`;
                }
            },
            {
                data: null,
                width: "100px",
                className: "text-center align-middle",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editarModelo(${row.id});" title="Editar modelo" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminarModelo(${row.id});" title="Desactivar modelo" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>`;
                }
            }
        ]
    });
};

const inicializarTablaMotos = function () {
    crearDataTable({
        selector: "#datatable_motos",
        url: urlListaMotos,
        searchPlaceholder: "NIV, chasis, motor, marca, modelo, almacén...",
        columns: [
            {
                data: "modelo",
                name: "modelo",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const ref = row.referencia ? `Ref: #${row.referencia}` : "";
                    const cil = row.cilindrada ? ` • ${row.cilindrada}` : "";
                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm shadow-xs rounded-3" style="width: 38px; height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5; font-size: 1.05rem;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.88rem; letter-spacing: -0.01em;">${row.marca || ""} ${data || ""}</span>
                                <small class="text-primary font-monospace fw-semibold" style="font-size: 0.74rem;">${ref}${cil}</small>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: "anio",
                name: "anio",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    const col = row.color || "Sin color";
                    return `
                        <div class="d-flex flex-column align-items-center py-1">
                            <span class="fw-bold text-dark font-monospace" style="font-size: 0.88rem;">${data || "—"}</span>
                            <span class="text-muted text-capitalize" style="font-size: 0.74rem;">${col}</span>
                        </div>
                    `;
                }
            },
            {
                data: "numero_niv",
                name: "numero_niv",
                className: "text-start align-middle",
                render: function (data) {
                    return `
                        <div class="d-flex align-items-center font-monospace fw-bold text-primary" style="font-size: 0.84rem;">
                            <i class="fas fa-fingerprint me-1 text-primary" style="font-size: 0.78rem;"></i>
                            <span>${data || "—"}</span>
                        </div>
                    `;
                }
            },
            {
                data: "numero_chasis",
                name: "numero_chasis",
                className: "text-start align-middle",
                render: function (data) {
                    return `<span class="font-monospace text-dark fw-semibold" style="font-size: 0.82rem;">${data || "—"}</span>`;
                }
            },
            {
                data: "numero_motor",
                name: "numero_motor",
                className: "text-start align-middle",
                render: function (data) {
                    return `<span class="font-monospace text-dark fw-semibold" style="font-size: 0.82rem;">${data || "—"}</span>`;
                }
            },
            {
                data: "almacen.nombre",
                name: "almacen.nombre",
                className: "text-center align-middle",
                render: function (data) {
                    return `
                        <span class="badge rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.76rem; background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                            <i class="fas fa-warehouse me-1 text-primary" style="font-size: 0.70rem;"></i>${data || "General"}
                        </span>
                    `;
                }
            },
            {
                data: "precio_detal_con_iva_usd",
                name: "precio_detal_con_iva_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const valorUsd = parseFloat(data || row.precio_detal_usd || 0);
                    const valorBs = parseFloat(row.precio_detal_con_iva_bs || row.precio_detal_bs || (valorUsd * tasaVentaActual));
                    const usd = valorUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = valorBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <div class="d-flex flex-column text-end py-1">
                            <span class="fw-bold font-monospace text-primary" style="font-size: 0.88rem;">$ ${usd}</span>
                            <span class="font-monospace fw-semibold" style="font-size: 0.74rem; color: #64748b;">Bs. ${bs}</span>
                        </div>
                    `;
                }
            },
            {
                data: "precio_mayorista_con_iva_usd",
                name: "precio_mayorista_con_iva_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const valorUsd = parseFloat(data || row.precio_mayorista_usd || 0);
                    const valorBs = parseFloat(row.precio_mayorista_con_iva_bs || row.precio_mayorista_bs || (valorUsd * tasaVentaActual));
                    const usd = valorUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = valorBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <div class="d-flex flex-column text-end py-1">
                            <span class="fw-bold font-monospace" style="font-size: 0.88rem; color: #7e22ce;">$ ${usd}</span>
                            <span class="font-monospace fw-semibold" style="font-size: 0.74rem; color: #64748b;">Bs. ${bs}</span>
                        </div>
                    `;
                }
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    switch (data) {
                        case "disponible":
                            return '<span class="badge rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem; background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle me-1"></i>Disponible</span>';
                        case "reservada":
                            return '<span class="badge rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem; background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a;"><i class="fas fa-clock me-1"></i>Reservada</span>';
                        case "vendida":
                            return '<span class="badge rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem; background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;"><i class="fas fa-file-invoice me-1"></i>Vendida</span>';
                        case "en_mantenimiento":
                        case "mantenimiento":
                            return '<span class="badge rounded-pill px-3 py-1 fw-bold" style="font-size: 0.75rem; background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"><i class="fas fa-wrench me-1"></i>Taller</span>';
                        default:
                            return `<span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.75rem; background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0;">${data || "—"}</span>`;
                    }
                }
            },
            {
                data: null,
                width: "100px",
                className: "text-center align-middle",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="ver(${row.id});" title="Ver Ficha 360°" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar vehículo" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>`;
                }
            }
        ]
    });
};

const inicializarEventos = function () {
    $("#edit_costo_base_usd, #edit_flete_usd, #edit_margen_detal, #edit_margen_mayorista").on("input", function () {
        recalcularPreciosEditarMoto();
    });

    $("#edit_iva_porcentaje").on("change", function () {
        recalcularPreciosEditarMoto();
    });

    $("#edit_precio_detal_con_iva_usd").on("input", function () {
        calcularMargenDetalEditarMoto();
    });

    $("#edit_precio_mayorista_con_iva_usd").on("input", function () {
        calcularMargenMayoristaEditarMoto();
    });

    $("#formularioEditarMoto").on("submit", function (e) {
        e.preventDefault();
        guardar();
    });

    $("#formularioModeloMoto").on("submit", function (e) {
        e.preventDefault();
        guardarModelo();
    });

    $("#btnEditarDesdeFicha").on("click", function () {
        const id = $(this).data("moto-id");
        if (id) {
            const modalFichaEl = document.getElementById("modalFichaMoto");
            const modalFicha = bootstrap.Modal.getOrCreateInstance(modalFichaEl);
            modalFicha.hide();
            editar(id);
        }
    });

    $('button[data-bs-toggle="pill"]').on("shown.bs.tab", function (e) {
        const targetId = $(e.target).attr("data-bs-target");
        if (targetId === "#pills-modelos") {
            recargarDataTable("#datatable_modelos");
        } else if (targetId === "#pills-unidades") {
            recargarDataTable("#datatable_motos");
        }
    });
};

const recalcularPreciosEditarMoto = function () {
    const costo = parseFloat($("#edit_costo_base_usd").val()) || 0;
    const flete = parseFloat($("#edit_flete_usd").val()) || 0;
    const ivaPorcentaje = parseFloat($("#edit_iva_porcentaje").val()) || 0;
    const aplicaIva = ivaPorcentaje > 0;
    const margenDetal = $("#edit_margen_detal").val();
    const margenMayorista = $("#edit_margen_mayorista").val();

    const res = window.CalculosCompra.calcularPreciosDesdeMargen({
        costo: costo,
        flete: flete,
        ivaPorcentaje: ivaPorcentaje,
        aplicaIva: aplicaIva,
        margenDetal: margenDetal,
        margenMayorista: margenMayorista,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: "USD",
    });

    $("#edit_costo_base_bs_preview").text(`Base: Bs. ${res.costoBaseBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`);
    $("#edit_flete_bs_preview").text(`Flete: Bs. ${res.fleteBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`);

    $("#edit_costo_total_usd").text(`$ ${res.costoTotalUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`);
    $("#edit_costo_total_bs").text(`Bs. ${res.costoTotalBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 4 })}`);
    $("#edit_precio_costo_usd").val(res.costoTotalUsd.toFixed(4));
    $("#edit_precio_costo_bs").val(res.costoTotalBs.toFixed(4));

    if (res.costoTotalUsd > 0) {
        $("#edit_precio_detal_con_iva_usd").val(res.precioDetalConIvaUsd.toFixed(2));
        $("#edit_precio_mayorista_con_iva_usd").val(res.precioMayoristaConIvaUsd.toFixed(2));
    }

    $("#edit_detal_con_iva_badge").text(`$ ${res.precioDetalConIvaUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioDetalConIvaBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $("#edit_detal_sin_iva_badge").text(`$ ${res.precioDetalUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioDetalBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $("#edit_precio_detal_usd").val(res.precioDetalUsd.toFixed(4));
    $("#edit_precio_detal_bs").val(res.precioDetalBs.toFixed(4));
    $("#edit_precio_detal_con_iva_bs").val(res.precioDetalConIvaBs.toFixed(4));

    $("#edit_mayorista_con_iva_badge").text(`$ ${res.precioMayoristaConIvaUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioMayoristaConIvaBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $("#edit_mayorista_sin_iva_badge").text(`$ ${res.precioMayoristaUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioMayoristaBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $("#edit_precio_mayorista_usd").val(res.precioMayoristaUsd.toFixed(4));
    $("#edit_precio_mayorista_bs").val(res.precioMayoristaBs.toFixed(4));
    $("#edit_precio_mayorista_con_iva_bs").val(res.precioMayoristaConIvaBs.toFixed(4));
};

const calcularMargenDetalEditarMoto = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $("#edit_costo_base_usd").val(),
        flete: $("#edit_flete_usd").val(),
        precio: $("#edit_precio_detal_con_iva_usd").val(),
        tipoPrecio: "con_iva",
        ivaPorcentaje: $("#edit_iva_porcentaje").val(),
        aplicaIva: parseFloat($("#edit_iva_porcentaje").val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: "USD",
    });

    if (res.precioUsd > 0 || res.precioConIvaUsd > 0) {
        $("#edit_margen_detal").val(res.margen.toFixed(0));
        $("#edit_detal_con_iva_badge").text(`$ ${res.precioConIvaUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioConIvaBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $("#edit_detal_sin_iva_badge").text(`$ ${res.precioUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $("#edit_precio_detal_usd").val(res.precioUsd.toFixed(4));
        $("#edit_precio_detal_bs").val(res.precioBs.toFixed(4));
        $("#edit_precio_detal_con_iva_bs").val(res.precioConIvaBs.toFixed(4));
    }
};

const calcularMargenMayoristaEditarMoto = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $("#edit_costo_base_usd").val(),
        flete: $("#edit_flete_usd").val(),
        precio: $("#edit_precio_mayorista_con_iva_usd").val(),
        tipoPrecio: "con_iva",
        ivaPorcentaje: $("#edit_iva_porcentaje").val(),
        aplicaIva: parseFloat($("#edit_iva_porcentaje").val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: "USD",
    });

    if (res.precioUsd > 0 || res.precioConIvaUsd > 0) {
        $("#edit_margen_mayorista").val(res.margen.toFixed(0));
        $("#edit_mayorista_con_iva_badge").text(`$ ${res.precioConIvaUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioConIvaBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $("#edit_mayorista_sin_iva_badge").text(`$ ${res.precioUsd.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${res.precioBs.toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
        $("#edit_precio_mayorista_usd").val(res.precioUsd.toFixed(4));
        $("#edit_precio_mayorista_bs").val(res.precioBs.toFixed(4));
        $("#edit_precio_mayorista_con_iva_bs").val(res.precioConIvaBs.toFixed(4));
    }
};

const abrirModalCrearModelo = async function () {
    isEditarModelo = false;
    idModeloActual = null;
    $("#formularioModeloMoto")[0].reset();
    $("#modelo_moto_id").val("");
    $("#modelo_anio").val(new Date().getFullYear());
    $("#modelo_cilindrada").val("150cc");

    $("#modalModeloMotoTitulo").text("Registrar Modelo de Moto");
    $("#modalModeloMotoSubtitulo").text("Defina las características del modelo y su referencia única para el catálogo y el POS");
    $("#modalModeloMotoBtnGuardar").find("#modalModeloMotoTextoGuardar").text("Guardar Modelo");

    try {
        const res = await peticionAjax({
            url: urlProximaReferenciaModelo,
            type: "GET",
        });
        if (res && res.success && res.data) {
            $("#modelo_referencia").val(res.data.referencia || "1");
        }
    } catch (e) {
        $("#modelo_referencia").val("1");
    }

    const modalEl = document.getElementById("modalModeloMoto");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
};

const editarModelo = async function (id) {
    try {
        const res = await consultarRegistro(urlBaseModelos, id);
        if (res && res.success && res.data) {
            const m = res.data;
            isEditarModelo = true;
            idModeloActual = m.id;

            $("#modelo_moto_id").val(m.id);
            $("#modelo_referencia").val(m.referencia || "");
            $("#modelo_marca").val(m.marca || "");
            $("#modelo_modelo").val(m.modelo || "");
            $("#modelo_anio").val(m.anio || new Date().getFullYear());
            $("#modelo_color").val(m.color || "");
            $("#modelo_cilindrada").val(m.cilindrada || "150cc");
            $("#modelo_descripcion").val(m.descripcion || "");

            $("#modalModeloMotoTitulo").text("Editar Modelo de Moto");
            $("#modalModeloMotoSubtitulo").text(`Modificando modelo Ref #${m.referencia} — ${m.marca} ${m.modelo}`);
            $("#modalModeloMotoBtnGuardar").find("#modalModeloMotoTextoGuardar").text("Guardar Cambios");

            const modalEl = document.getElementById("modalModeloMoto");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del modelo.",
            });
        }
    }
};

const guardarModelo = function () {
    const url = isEditarModelo ? `${urlEditarModelo}${idModeloActual}` : urlGuardarModelo;

    enviarFormulario({
        form: "#formularioModeloMoto",
        url: url,
        isEditar: isEditarModelo,
        modalSelector: "#modalModeloMoto",
        tablaSelector: "#datatable_modelos",
        btnSubmit: "#modalModeloMotoBtnGuardar",
        textoGuardarOriginal: isEditarModelo ? "Guardar Cambios" : "Guardar Modelo",
        onSuccess: function () {
            recargarDataTable("#datatable_modelos");
            recargarDataTable("#datatable_motos");
        }
    });
};

const eliminarModelo = function (id) {
    cambiarEstadoRegistro({
        url: urlEliminarModelo,
        id: id,
        tablaSelector: "#datatable_modelos",
        nombre: "el modelo de moto seleccionado",
        onSuccess: function () {
            recargarDataTable("#datatable_modelos");
        }
    });
};

const cargarCatalogos = async function () {
    try {
        const respuesta = await peticionAjax({
            url: urlCatalogosMotos,
            type: "GET"
        });

        if (respuesta.success && respuesta.data) {
            catalogosSistema.almacenes = respuesta.data.almacenes || [];
            tasaBcvActual = parseFloat(respuesta.data.tasa_bcv || 1);
            tasaCompraActual = parseFloat(respuesta.data.tasa_compra || respuesta.data.tasa_bcv || 1);
            tasaVentaActual = parseFloat(respuesta.data.tasa_venta || respuesta.data.tasa_bcv || 1);
            monedaSimboloActual = respuesta.data.moneda_simbolo || "Bs.";

            const $select = $("#edit_almacen_id").empty();
            catalogosSistema.almacenes.forEach(function (a) {
                $select.append(`<option value="${a.id}">${a.nombre} (${a.codigo || "ALM"})</option>`);
            });

            crearSelect2({
                selector: "#edit_almacen_id",
                modalSelector: "#modalEditarMoto",
                placeholder: "Seleccione un almacén...",
            });

            crearSelect2({
                selector: "#edit_estado",
                modalSelector: "#modalEditarMoto",
                placeholder: "Seleccione el estado...",
            });

            crearSelect2({
                selector: "#edit_iva_porcentaje",
                modalSelector: "#modalEditarMoto",
                placeholder: "Seleccione IVA...",
            });
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error de carga",
                text: "No se pudieron cargar los catálogos del sistema",
            });
        }
    }
};

const ver = async function (id) {
    try {
        const respuesta = await consultarRegistro(urlDetallesMoto, id);

        if (respuesta.success && respuesta.data) {
            const m = respuesta.data;
            $("#modalFichaMotoSubtitulo").text(`NIV: ${m.numero_niv || "--"}`);

            let estadoBadge = "";
            if (m.estado === "disponible") {
                estadoBadge = '<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.80rem; background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0;"><i class="fas fa-check-circle me-1"></i>Disponible para Venta</span>';
            } else if (m.estado === "reservada") {
                estadoBadge = '<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.80rem; background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a;"><i class="fas fa-clock me-1"></i>Reservada</span>';
            } else if (m.estado === "vendida") {
                estadoBadge = '<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.80rem; background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe;"><i class="fas fa-file-invoice me-1"></i>Vendida</span>';
            } else {
                estadoBadge = `<span class="badge rounded-pill px-3 py-1.5 fw-bold" style="font-size: 0.80rem; background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">${m.estado}</span>`;
            }

            const costoBaseUsd = parseFloat(m.costo_base_usd || m.precio_costo_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const costoBaseBs = parseFloat(m.costo_base_bs || (parseFloat(m.costo_base_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fleteUsd = parseFloat(m.flete_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fleteBs = parseFloat(m.flete_bs || (parseFloat(m.flete_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const costoTotalUsd = parseFloat(m.precio_costo_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const costoTotalBs = parseFloat(m.precio_costo_bs || (parseFloat(m.precio_costo_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const detalConIvaUsd = parseFloat(m.precio_detal_con_iva_usd || m.precio_detal_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const detalConIvaBs = parseFloat(m.precio_detal_con_iva_bs || (parseFloat(m.precio_detal_con_iva_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const detalSinIvaUsd = parseFloat(m.precio_detal_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const detalSinIvaBs = parseFloat(m.precio_detal_bs || (parseFloat(m.precio_detal_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const mayorConIvaUsd = parseFloat(m.precio_mayorista_con_iva_usd || m.precio_mayorista_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const mayorConIvaBs = parseFloat(m.precio_mayorista_con_iva_bs || (parseFloat(m.precio_mayorista_con_iva_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const mayorSinIvaUsd = parseFloat(m.precio_mayorista_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const mayorSinIvaBs = parseFloat(m.precio_mayorista_bs || (parseFloat(m.precio_mayorista_usd || 0) * tasaVentaActual)).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const proveedorNombre = m.proveedor ? `${m.proveedor.nombre} (${m.proveedor.rif || ""})` : "N/A";
            const recepcionCodigo = m.recepcion?.codigo || "N/A";
            const ivaPorc = m.iva_porcentaje !== null && m.iva_porcentaje !== undefined ? parseFloat(m.iva_porcentaje) : 16;

            $("#contenidoFichaMoto").html(`
                <div class="card border-0 rounded-4 p-4 mb-3 text-white shadow-sm" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h3 class="fw-bold text-white mb-0" style="letter-spacing: -0.02em;">${m.marca || ""} ${m.modelo || ""}</h3>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 text-white-50 small mt-1">
                                <span class="badge rounded-pill px-2.5 py-1 text-white fw-semibold" style="background: rgba(255,255,255,0.12);">Año: <strong class="text-white">${m.anio || "—"}</strong></span>
                                <span class="badge rounded-pill px-2.5 py-1 text-white fw-semibold" style="background: rgba(255,255,255,0.12);">Color: <strong class="text-white text-capitalize">${m.color || "Sin color"}</strong></span>
                                <span class="badge rounded-pill px-2.5 py-1 text-white fw-semibold" style="background: rgba(255,255,255,0.12);">Cilindrada: <strong class="text-white">${m.cilindrada || "N/A"}</strong></span>
                                ${m.referencia ? `<span class="badge rounded-pill px-2.5 py-1 text-white fw-semibold" style="background: rgba(255,255,255,0.12);">Ref: <strong class="text-white">#${m.referencia}</strong></span>` : ""}
                            </div>
                        </div>
                        <div>
                            ${estadoBadge}
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-4 p-3.5 mb-3 bg-white shadow-xs">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-fingerprint text-primary me-2 fs-6"></i>
                        <h6 class="fw-bold text-dark mb-0">Identificadores Legales & Trazabilidad</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-barcode text-primary me-1"></i> N.I.V. (VIN)</small>
                                <span class="font-monospace fw-bold text-dark d-block" style="font-size: 0.92rem; word-break: break-all;">${m.numero_niv || "—"}</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-shield-alt text-secondary me-1"></i> N° Chasis / Bastidor</small>
                                <span class="font-monospace fw-bold text-dark d-block" style="font-size: 0.92rem; word-break: break-all;">${m.numero_chasis || "—"}</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-cogs text-secondary me-1"></i> N° de Motor</small>
                                <span class="font-monospace fw-bold text-dark d-block" style="font-size: 0.92rem; word-break: break-all;">${m.numero_motor || "—"}</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-certificate text-warning me-1"></i> Certificado de Origen</small>
                                <span class="font-monospace fw-bold text-dark d-block" style="font-size: 0.92rem; word-break: break-all;">${m.certificado_origen || "N/A"}</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-id-card text-info me-1"></i> Placa / Matrícula</small>
                                <span class="font-monospace fw-bold text-dark d-block" style="font-size: 0.92rem;">${m.placa || "Sin placa"}</span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="rounded-3 p-3 h-100" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem; letter-spacing: 0.05em;"><i class="fas fa-warehouse text-primary me-1"></i> Ubicación</small>
                                <span class="fw-bold text-primary d-block" style="font-size: 0.92rem;">${m.almacen?.nombre || "General"}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 rounded-4 p-3.5 mb-3 bg-white shadow-xs">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-coins text-warning me-2 fs-6"></i>
                        <h6 class="fw-bold text-dark mb-0">Estructura Detallada de Costos y Precios</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <div class="rounded-3 p-3 text-center" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem;"><i class="fas fa-tag me-1"></i> Costo Base</small>
                                <h5 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 1.05rem;">$ ${costoBaseUsd}</h5>
                                <small class="text-muted font-monospace d-block mt-0.5" style="font-size: 0.72rem;">Bs. ${costoBaseBs}</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="rounded-3 p-3 text-center" style="background-color: #fffbeb;">
                                <small class="text-warning text-uppercase fw-bold d-block mb-1" style="font-size: 0.68rem;"><i class="fas fa-truck-ramp-box me-1"></i> Flete Pagado</small>
                                <h5 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 1.05rem;">$ ${fleteUsd}</h5>
                                <small class="text-muted font-monospace d-block mt-0.5" style="font-size: 0.72rem;">Bs. ${fleteBs}</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="rounded-3 p-3 text-center" style="background-color: #f8fafc;">
                                <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.68rem;"><i class="fas fa-receipt me-1"></i> IVA Gravado</small>
                                <h5 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 1.05rem;">${ivaPorc}%</h5>
                                <small class="text-muted font-monospace d-block mt-0.5" style="font-size: 0.72rem;">${ivaPorc > 0 ? 'Con IVA' : 'Exento'}</small>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <div class="rounded-3 p-3 text-center" style="background-color: #eff6ff; border: 1px solid #bfdbfe;">
                                <small class="text-primary text-uppercase fw-bold d-block mb-1" style="font-size: 0.68rem;"><i class="fas fa-calculator me-1"></i> Costo Total</small>
                                <h5 class="fw-bold text-primary mb-0 font-monospace" style="font-size: 1.05rem;">$ ${costoTotalUsd}</h5>
                                <small class="text-primary font-monospace fw-semibold d-block mt-0.5" style="font-size: 0.72rem;">Bs. ${costoTotalBs}</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-3 p-3" style="background-color: #f0fdf4; border: 1px solid #bbf7d0;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-success text-uppercase fw-bold" style="font-size: 0.75rem;"><i class="fas fa-store me-1"></i> Venta al Detal (PVP)</span>
                                    <span class="badge rounded-pill bg-success text-white font-monospace px-2 py-0.5" style="font-size: 0.72rem;">Margen: ${m.margen_detal || 25}%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted fw-semibold">PVP (Con IVA):</small>
                                    <strong class="font-monospace text-success" style="font-size: 1rem;">$ ${detalConIvaUsd} <span class="text-muted fw-normal" style="font-size: 0.78rem;">| Bs. ${detalConIvaBs}</span></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Precio Sin IVA:</small>
                                    <span class="font-monospace text-secondary fw-semibold" style="font-size: 0.85rem;">$ ${detalSinIvaUsd} <span class="text-muted fw-normal" style="font-size: 0.75rem;">| Bs. ${detalSinIvaBs}</span></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="rounded-3 p-3" style="background-color: #faf5ff; border: 1px solid #e9d5ff;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-uppercase fw-bold" style="color: #7e22ce; font-size: 0.75rem;"><i class="fas fa-truck-moving me-1"></i> Venta Mayorista</span>
                                    <span class="badge rounded-pill text-white font-monospace px-2 py-0.5" style="background-color: #7e22ce; font-size: 0.72rem;">Margen: ${m.margen_mayorista || 15}%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <small class="text-muted fw-semibold">Mayor (Con IVA):</small>
                                    <strong class="font-monospace" style="color: #7e22ce; font-size: 1rem;">$ ${mayorConIvaUsd} <span class="text-muted fw-normal" style="font-size: 0.78rem;">| Bs. ${mayorConIvaBs}</span></strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Mayor Sin IVA:</small>
                                    <span class="font-monospace text-secondary fw-semibold" style="font-size: 0.85rem;">$ ${mayorSinIvaUsd} <span class="text-muted fw-normal" style="font-size: 0.75rem;">| Bs. ${mayorSinIvaBs}</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="rounded-4 p-3.5 h-100 bg-white shadow-xs" style="background-color: #ffffff;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-truck text-primary me-2"></i>
                                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.70rem; letter-spacing: 0.05em;">Procedencia</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Proveedor:</span>
                                <strong class="text-dark" style="font-size: 0.90rem;">${proveedorNombre}</strong>
                            </div>
                            <div>
                                <span class="text-muted small d-block mb-1">Recepción de Entrada:</span>
                                <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1 fw-bold">${recepcionCodigo}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rounded-4 p-3.5 h-100 bg-white shadow-xs" style="background-color: #ffffff;">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-comment-dots text-info me-2"></i>
                                <span class="text-muted small text-uppercase fw-semibold" style="font-size: 0.70rem; letter-spacing: 0.05em;">Observaciones</span>
                            </div>
                            <p class="text-secondary small mb-0" style="line-height: 1.5;">
                                ${m.observaciones ? m.observaciones : '<span class="text-muted fst-italic">Sin observaciones registradas para este vehículo.</span>'}
                            </p>
                        </div>
                    </div>
                </div>
            `);

            $("#btnEditarDesdeFicha").data("moto-id", m.id);
            const modalEl = document.getElementById("modalFichaMoto");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la ficha técnica del vehículo",
            });
        }
    }
};

const editar = async function (id) {
    try {
        const respuesta = await consultarRegistro(urlDetallesMoto, id);

        if (respuesta.success && respuesta.data) {
            const m = respuesta.data;
            idMotoActual = m.id;

            $("#edit_moto_id").val(m.id);
            $("#modalEditarMotoSubtitulo").text(`${m.marca || ""} ${m.modelo || ""} — NIV: ${m.numero_niv || ""}`);
            $("#edit_marca").val(m.marca || "");
            $("#edit_modelo").val(m.modelo || "");
            $("#edit_referencia").val(m.referencia || "");
            $("#edit_anio").val(m.anio || new Date().getFullYear());
            $("#edit_cilindrada").val(m.cilindrada || "");
            $("#edit_numero_niv").val(m.numero_niv || "");
            $("#edit_numero_chasis").val(m.numero_chasis || "");
            $("#edit_numero_motor").val(m.numero_motor || "");
            $("#edit_certificado_origen").val(m.certificado_origen || "");
            $("#edit_color").val(m.color || "");
            $("#edit_placa").val(m.placa || "");

            establecerValorSelect2("#edit_almacen_id", m.almacen_id);
            establecerValorSelect2("#edit_estado", m.estado);

            const costoBase = parseFloat(m.costo_base_usd || m.precio_costo_usd || 0);
            const flete = parseFloat(m.flete_usd || 0);
            const ivaPorc = m.iva_porcentaje !== null && m.iva_porcentaje !== undefined ? parseInt(m.iva_porcentaje) : 16;
            const margenDetal = m.margen_detal !== null && m.margen_detal !== undefined ? parseFloat(m.margen_detal) : 25;
            const detalConIva = parseFloat(m.precio_detal_con_iva_usd || m.precio_detal_usd || 0);
            const margenMayor = m.margen_mayorista !== null && m.margen_mayorista !== undefined ? parseFloat(m.margen_mayorista) : 15;
            const mayorConIva = parseFloat(m.precio_mayorista_con_iva_usd || m.precio_mayorista_usd || 0);

            $("#edit_costo_base_usd").val(costoBase > 0 ? costoBase : "");
            $("#edit_flete_usd").val(flete);
            establecerValorSelect2("#edit_iva_porcentaje", String(ivaPorc));
            $("#edit_margen_detal").val(margenDetal);
            $("#edit_precio_detal_con_iva_usd").val(detalConIva > 0 ? detalConIva : "");
            $("#edit_margen_mayorista").val(margenMayor);
            $("#edit_precio_mayorista_con_iva_usd").val(mayorConIva > 0 ? mayorConIva : "");
            $("#edit_observaciones").val(m.observaciones || "");

            recalcularPreciosEditarMoto();

            const modalEl = document.getElementById("modalEditarMoto");
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudieron cargar los datos del vehículo para edición",
            });
        }
    }
};

const guardar = function () {
    if (!idMotoActual) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Atención",
                text: "Identificador de vehículo inválido",
            });
        }
        return;
    }

    enviarFormulario({
        form: "#formularioEditarMoto",
        url: `${urlEditarMoto}${idMotoActual}`,
        isEditar: true,
        modalSelector: "#modalEditarMoto",
        tablaSelector: "#datatable_motos",
        btnSubmit: "#modalEditarMotoBtnGuardar",
        onSuccess: function () {
            recargarDataTable("#datatable_motos");
        }
    });
};

const cambiarEstadoMoto = async function (id, nuevoEstado) {
    try {
        const respuesta = await peticionAjax({
            url: `${urlCambiarEstadoMoto}${id}/cambiar-estado`,
            type: "POST",
            data: {
                estado: nuevoEstado,
            },
        });

        if (respuesta.success) {
            recargarDataTable("#datatable_motos");
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: respuesta.message || "Estado actualizado exitosamente",
                });
            }
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error al cambiar estado",
                text: e.responseJSON?.message || "Ocurrió un error al actualizar el estado.",
            });
        }
    }
};
