const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlCatalogos = `${urlBase}/catalogos`;
const urlDetalles = `${urlBase}/`;
const urlEliminar = `${urlBase}/`;
const urlGuardar = urlBase;
const urlEditar = `${urlBase}/actualizar/`;
const urlCambiarEstado = `${urlBase}/`;

let urlAccion = urlGuardar;
let isEditar = false;
let idMotoActual = null;
let tasaBcvActual = 1.0000;
let monedaSimboloActual = "Bs.";
let catalogosSistema = {
    almacenes: [],
};

$(document).ready(function () {
    inicializarTabla();
    cargarCatalogos();
    inicializarEventos();
});

const inicializarTabla = function () {
    crearDataTable({
        selector: "#datatable_motos",
        url: urlLista,
        searchPlaceholder: "NIV, chasis, motor, marca, modelo, almacén...",
        columns: [
            {
                data: "modelo",
                name: "modelo",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const ref = row.referencia ? `Ref: ${row.referencia}` : "";
                    const cil = row.cilindrada ? ` • ${row.cilindrada}` : "";
                    return `
                        <div class="d-flex align-items-center gap-2 py-1">
                            <div class="avatar-executive-sm shadow-xs rounded-3" style="width: 38px; height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; background-color: #eef2ff; color: #4f46e5; font-size: 1.05rem;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.88rem; letter-spacing: -0.01em;">${row.marca || ""} ${data || ""}</span>
                                <small class="text-muted font-monospace" style="font-size: 0.74rem;">${ref}${cil}</small>
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
                data: "precio_detal_usd",
                name: "precio_detal_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.precio_detal_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <div class="d-flex flex-column text-end py-1">
                            <span class="fw-bold font-monospace text-primary" style="font-size: 0.88rem;">$ ${usd}</span>
                            <span class="font-monospace fw-semibold" style="font-size: 0.74rem; color: #64748b;">Bs. ${bs}</span>
                        </div>
                    `;
                }
            },
            {
                data: "precio_mayorista_usd",
                name: "precio_mayorista_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.precio_mayorista_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
    $("#edit_precio_costo_usd, #edit_precio_detal_usd, #edit_precio_mayorista_usd").on("input", function () {
        actualizarPreciosBsPreview();
    });

    $("#formularioEditarMoto").on("submit", function (e) {
        e.preventDefault();
        guardar();
    });

    $("#btnEditarDesdeFicha").on("click", function () {
        const id = $(this).data("moto-id");
        if (id) {
            $("#modalFichaMoto").modal("hide");
            editar(id);
        }
    });
};

const actualizarPreciosBsPreview = function () {
    const costoUsd = parseFloat($("#edit_precio_costo_usd").val()) || 0;
    const detalUsd = parseFloat($("#edit_precio_detal_usd").val()) || 0;
    const mayorUsd = parseFloat($("#edit_precio_mayorista_usd").val()) || 0;

    const costoBs = (costoUsd * tasaBcvActual).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const detalBs = (detalUsd * tasaBcvActual).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const mayorBs = (mayorUsd * tasaBcvActual).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    $("#edit_costo_bs_preview").text(`≈ ${monedaSimboloActual} ${costoBs}`);
    $("#edit_detal_bs_preview").text(`≈ ${monedaSimboloActual} ${detalBs}`);
    $("#edit_mayorista_bs_preview").text(`≈ ${monedaSimboloActual} ${mayorBs}`);
};

const cargarCatalogos = async function () {
    try {
        const respuesta = await peticionAjax({
            url: urlCatalogos,
            method: "GET"
        });

        if (respuesta.success && respuesta.data) {
            catalogosSistema.almacenes = respuesta.data.almacenes || [];
            tasaBcvActual = parseFloat(respuesta.data.tasa_bcv || 1);
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
        const respuesta = await consultarRegistro(urlDetalles, id);

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

            const costoUsd = parseFloat(m.precio_costo_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const costoBs = parseFloat(m.precio_costo_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const detalUsd = parseFloat(m.precio_detal_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const detalBs = parseFloat(m.precio_detal_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const mayorUsd = parseFloat(m.precio_mayorista_usd || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const mayorBs = parseFloat(m.precio_mayorista_bs || 0).toLocaleString("es-VE", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const proveedorNombre = m.proveedor ? `${m.proveedor.nombre} (${m.proveedor.rif || ""})` : "N/A";
            const recepcionCodigo = m.recepcion?.codigo || "N/A";

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
                                ${m.referencia ? `<span class="badge rounded-pill px-2.5 py-1 text-white fw-semibold" style="background: rgba(255,255,255,0.12);">Ref: <strong class="text-white">${m.referencia}</strong></span>` : ""}
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

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="rounded-4 p-3.5 text-center shadow-xs" style="background-color: #f8fafc;">
                            <small class="text-muted text-uppercase fw-semibold d-block mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;"><i class="fas fa-tag text-secondary me-1"></i> Costo de Compra</small>
                            <h4 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 1.25rem;">$ ${costoUsd}</h4>
                            <small class="text-muted font-monospace d-block mt-0.5">${monedaSimboloActual} ${costoBs}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-4 p-3.5 text-center shadow-xs" style="background-color: #f0fdf4;">
                            <small class="text-success text-uppercase fw-bold d-block mb-1" style="font-size: 0.70rem; letter-spacing: 0.05em;"><i class="fas fa-store me-1"></i> Precio Detal</small>
                            <h4 class="fw-bold text-success mb-0 font-monospace" style="font-size: 1.25rem;">$ ${detalUsd}</h4>
                            <small class="text-success font-monospace fw-semibold d-block mt-0.5">${monedaSimboloActual} ${detalBs}</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rounded-4 p-3.5 text-center shadow-xs" style="background-color: #faf5ff;">
                            <small class="text-uppercase fw-bold d-block mb-1" style="color: #7e22ce; font-size: 0.70rem; letter-spacing: 0.05em;"><i class="fas fa-truck-moving me-1"></i> Precio Mayorista</small>
                            <h4 class="fw-bold mb-0 font-monospace" style="color: #7e22ce; font-size: 1.25rem;">$ ${mayorUsd}</h4>
                            <small class="font-monospace fw-semibold d-block mt-0.5" style="color: #9333ea;">${monedaSimboloActual} ${mayorBs}</small>
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
            $("#modalFichaMoto").modal("show");
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
        const respuesta = await consultarRegistro(urlDetalles, id);

        if (respuesta.success && respuesta.data) {
            const m = respuesta.data;
            idMotoActual = m.id;
            isEditar = true;
            urlAccion = `${urlEditar}${idMotoActual}`;

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

            $("#edit_precio_costo_usd").val(parseFloat(m.precio_costo_usd || 0));
            $("#edit_precio_detal_usd").val(parseFloat(m.precio_detal_usd || 0));
            $("#edit_precio_mayorista_usd").val(parseFloat(m.precio_mayorista_usd || 0));
            $("#edit_observaciones").val(m.observaciones || "");

            actualizarPreciosBsPreview();
            $("#modalEditarMoto").modal("show");
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
        url: `${urlEditar}${idMotoActual}`,
        isEditar: true,
        modalSelector: "#modalEditarMoto",
        tablaSelector: "#datatable_motos",
        btnSubmit: "#modalEditarMotoBtnGuardar",
        onSuccess: function () {
            actualizarPreciosBsPreview();
        }
    });
};

const cambiarEstadoMoto = async function (id, nuevoEstado) {
    try {
        const respuesta = await peticionAjax({
            url: `${urlCambiarEstado}${id}/cambiar-estado`,
            method: "POST",
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
