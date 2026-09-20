// URL base para el módulo de Recepciones
const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlCatalogos = urlBase + "/catalogos";
const urlDetalles = urlBase + "/";
const urlGuardar = urlBase;
const urlGuardarProveedor = window.location.origin + "/proveedores";
const urlGuardarProducto = window.location.origin + "/productos";

let tasaUsdActual = 1.0000;
let monedaDocumentoActual = "USD"; // 'USD' o 'VES'

let catalogosSistema = {
    proveedores: [],
    almacenes: [],
    productos: [],
    categorias: [],
    tasa_usd: 1.0000,
    proximo_codigo: "REC-00001",
};

let contadorFilas = 0;

// Helper para formatear fechas de manera elegante y consistente (DD/MM/YYYY)
const formatearFecha = function (fechaStr) {
    if (!fechaStr) return "";
    try {
        const str = String(fechaStr).trim();
        const match = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (match) {
            const [, anio, mes, dia] = match;
            return `${dia}/${mes}/${anio}`;
        }
        const d = new Date(fechaStr);
        if (!isNaN(d.getTime())) {
            const dia = String(d.getUTCDate()).padStart(2, "0");
            const mes = String(d.getUTCMonth() + 1).padStart(2, "0");
            const anio = d.getUTCFullYear();
            return `${dia}/${mes}/${anio}`;
        }
        return str;
    } catch (e) {
        return String(fechaStr);
    }
};
window.formatearFecha = formatearFecha;

$(document).ready(function () {
    cargarCatalogos();

    // Inicializar DataTable de Recepciones
    crearDataTable({
        selector: "#datatable_recepciones",
        url: urlLista,
        searchPlaceholder: "Buscar N° recepción, factura, control, proveedor o almacén...",
        columns: [
            {
                data: "codigo",
                name: "codigo",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const fecha = formatearFecha(row.fecha_recepcion);
                    return `
                        <div class="d-flex align-items-center gap-2 py-1" style="white-space: nowrap;">
                            <div class="avatar-executive-sm shadow-xs rounded-3" style="width: 38px; height: 38px; min-width: 38px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #2563eb; font-size: 1rem; border: 1px solid #bfdbfe;">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark font-monospace" style="font-size: 0.90rem; letter-spacing: 0.2px;">${row.codigo}</span>
                                <span class="text-secondary small d-inline-flex align-items-center gap-1" style="font-size: 0.76rem; font-weight: 500;"><i class="fas fa-calendar-alt text-primary opacity-75"></i>${fecha || "--"}</span>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "numero_documento",
                name: "numero_documento",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const tipo = (row.tipo_documento || "factura").replace("_", " ").toUpperCase();
                    return `
                        <div class="d-flex flex-column gap-1" style="white-space: nowrap;">
                            <span class="badge rounded-pill font-monospace px-2.5 py-1 text-dark" style="font-size: 0.78rem; font-weight: 700; background-color: #ffffff; border: 1.5px solid #cbd5e1; width: fit-content; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                                <i class="fas fa-file-invoice text-primary me-1"></i>${data}
                            </span>
                            <span class="badge rounded-pill px-2 py-0.5" style="font-size: 0.68rem; width: fit-content; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 600;">${tipo}</span>
                        </div>
                    `;
                },
            },
            {
                data: "numero_control",
                name: "numero_control",
                className: "text-center align-middle",
                render: function (data) {
                    if (!data) return '<span class="text-muted small fst-italic" style="font-size: 0.76rem;">--</span>';
                    return `
                        <span class="badge rounded-pill font-monospace px-2.5 py-1 text-dark" style="font-size: 0.74rem; background-color: #ffffff; border: 1px solid #cbd5e1; white-space: nowrap;">
                            <i class="fas fa-barcode text-primary me-1"></i>${data}
                        </span>
                    `;
                },
            },
            {
                data: "proveedor",
                name: "proveedor.nombre",
                className: "text-start align-middle",
                render: function (data) {
                    if (!data) return '<span class="text-muted small fst-italic">Sin proveedor</span>';
                    return `
                        <div class="d-flex flex-column" style="white-space: nowrap;">
                            <span class="fw-bold text-dark" style="font-size: 0.88rem;">${data.nombre}</span>
                            <span class="text-secondary font-monospace" style="font-size: 0.75rem;"><i class="fas fa-id-card text-primary opacity-75 me-1"></i>${data.rif}</span>
                        </div>
                    `;
                },
            },
            {
                data: "almacen",
                name: "almacen.nombre",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<span class="badge rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.76rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; white-space: nowrap;"><i class="fas fa-warehouse text-primary me-1"></i>${data.nombre}</span>`
                        : '<span class="text-muted small">--</span>';
                },
            },
            {
                data: "detalles",
                name: "detalles",
                className: "text-center align-middle",
                orderable: false,
                render: function (data) {
                    const cantRenglones = Array.isArray(data) ? data.length : 0;
                    const totalUnidades = Array.isArray(data)
                        ? data.reduce((acc, d) => acc + parseFloat(d.cantidad || 0), 0)
                        : 0;
                    return `
                        <div class="d-flex flex-column align-items-center gap-1" style="white-space: nowrap;">
                            <span class="fw-bold text-dark font-monospace" style="font-size: 0.88rem;">${totalUnidades.toLocaleString()} unds</span>
                            <span class="badge rounded-pill px-2 py-0.5" style="font-size: 0.68rem; background-color: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-weight: 600;">${cantRenglones} ${cantRenglones === 1 ? 'ítem' : 'ítems'}</span>
                        </div>
                    `;
                },
            },
            {
                data: null,
                name: "total_usd",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const totalUsd = parseFloat(row.total_usd || 0);
                    const totalBs = parseFloat(row.total_bs || 0);
                    return `
                        <div class="d-flex flex-column py-1" style="white-space: nowrap; min-width: 125px;">
                            <div class="d-inline-flex align-items-center gap-1">
                                <span class="badge rounded-pill px-1.5 py-0.5 bg-success text-white fw-bold" style="font-size: 0.70rem; line-height: 1;">$</span>
                                <span class="fw-bold font-monospace text-dark" style="font-size: 0.95rem; letter-spacing: -0.2px;">${totalUsd.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </div>
                            <div class="d-inline-flex align-items-center gap-1 mt-0.5">
                                <span class="badge rounded-pill px-1.5 py-0.5 bg-info-subtle text-info-emphasis fw-bold" style="font-size: 0.65rem; border: 1px solid #bae6fd; line-height: 1;">Bs.</span>
                                <span class="font-monospace fw-semibold" style="font-size: 0.78rem; color: #0284c7;">${totalBs.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "condicion_pago",
                name: "condicion_pago",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (data === "credito") {
                        const venceStr = row.fecha_vencimiento ? formatearFecha(row.fecha_vencimiento) : "";
                        const vence = venceStr ? `Vence: ${venceStr}` : "Crédito";
                        return `<span class="badge rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.74rem; background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a; white-space: nowrap;"><i class="fas fa-clock me-1"></i>${vence}</span>`;
                    }
                    return '<span class="badge rounded-pill px-2.5 py-1 fw-semibold" style="font-size: 0.74rem; background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; white-space: nowrap;"><i class="fas fa-check-circle me-1"></i>Contado</span>';
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center align-middle",
                render: function (data) {
                    if (data === "anulada") {
                        return '<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.74rem; background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; white-space: nowrap;"><i class="fas fa-times-circle me-1"></i>Anulada</span>';
                    }
                    return '<span class="badge rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.74rem; background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; white-space: nowrap;"><i class="fas fa-check-circle me-1"></i>Procesada</span>';
                },
            },
            {
                data: null,
                width: "135px",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    const esAnulada = row.estado === "anulada";
                    const btnAnular = esAnulada
                        ? ""
                        : `
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-xs" onclick="anularRecepcion(${row.id}, '${row.codigo}');" title="Anular recepción y revertir inventario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease;">
                            <i class="fas fa-ban"></i>
                        </button>
                    `;

                    return `
                        <div class="d-flex justify-content-center gap-1" style="white-space: nowrap;">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle shadow-xs" onclick="imprimirRecepcion(${row.id});" title="Imprimir Comprobante Oficial" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; background-color: #ffffff; color: #475569;">
                                <i class="fas fa-print"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-circle shadow-xs" onclick="verFicha(${row.id});" title="Comprobante 360°" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; background-color: #ffffff;">
                                <i class="fas fa-eye"></i>
                            </button>
                            ${btnAnular}
                        </div>
                    `;
                },
            },
        ],
    });

    // Eventos de escaneo, búsqueda y navegación fluida por teclado
    configurarBuscadorProductos();
    configurarNavegacionTeclado();
    aplicarRestriccionesInput();

    $("#formularioRecepcion").on("submit", function (e) {
        e.preventDefault();
    });
});

/**
 * Cargar catálogos iniciales
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

            $("#tasa_cambio").val(tasaUsdActual.toFixed(4));
            $("#badgeTasaCambio").text(tasaUsdActual.toFixed(4));
            $("#badgeCodigoRecepcion").html(`<i class="fas fa-hashtag me-1"></i>${res.data.proximo_codigo}`);

            poblarSelectProveedores();
            poblarSelectAlmacenes();
            poblarSelectCategoriasRapido();
        }
    } catch (e) {
        console.error("Error al cargar catálogos de recepción:", e);
    }
};

const poblarSelectProveedores = function () {
    const $select = $("#proveedor_id");
    $select.empty().append('<option value="">Seleccione un proveedor...</option>');

    if (Array.isArray(catalogosSistema.proveedores)) {
        catalogosSistema.proveedores.forEach((p) => {
            $select.append(`<option value="${p.id}">[${p.rif}] ${p.nombre}</option>`);
        });
    }
};

const poblarSelectAlmacenes = function () {
    const $select = $("#almacen_id");
    $select.empty().append('<option value="">Seleccione almacén...</option>');

    if (Array.isArray(catalogosSistema.almacenes)) {
        catalogosSistema.almacenes.forEach((a) => {
            $select.append(`<option value="${a.id}">[${a.codigo}] ${a.nombre}</option>`);
        });
    }
};

const poblarSelectCategoriasRapido = function () {
    const $select = $("#rapido_prod_categoria_id");
    $select.empty().append('<option value="">Seleccione categoría...</option>');

    if (Array.isArray(catalogosSistema.categorias)) {
        catalogosSistema.categorias.forEach((c) => {
            $select.append(`<option value="${c.id}">${c.nombre}</option>`);
        });
    }
};

const cambiarAlmacenPredeterminado = function () {
    const almacenGlobalId = $("#almacen_id").val();
    if (almacenGlobalId) {
        $(".select-almacen-fila").each(function () {
            if (!$(this).data("custom-selected")) {
                $(this).val(almacenGlobalId).trigger("change");
            }
        });
    }
};
window.cambiarAlmacenPredeterminado = cambiarAlmacenPredeterminado;

/**
 * Selección Obligatoria de Moneda de la Factura (Fase 1)
 */
const seleccionarMonedaDocumento = function (moneda) {
    const totalFilas = $("#contenedorFilasRecepcion tr.fila-producto-recepcion").length;
    if (totalFilas > 0 && moneda !== monedaDocumentoActual) {
        if (window.Swal) {
            Swal.fire({
                title: "¿Deseas cambiar la moneda de la factura?",
                text: "Ya tienes productos cargados en la recepción. Para cambiar la moneda de facturación debes limpiar la lista de artículos.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#64748b",
                confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Sí, limpiar y cambiar',
                cancelButtonText: "Mantener actual",
            }).then((res) => {
                if (res.isConfirmed) {
                    limpiarTablaProductos();
                    aplicarCambioMoneda(moneda);
                } else {
                    if (monedaDocumentoActual === "USD") {
                        $("#radio_usd").prop("checked", true);
                    } else {
                        $("#radio_ves").prop("checked", true);
                    }
                }
            });
            return;
        }
    }

    aplicarCambioMoneda(moneda);
};
window.seleccionarMonedaDocumento = seleccionarMonedaDocumento;

const aplicarCambioMoneda = function (moneda) {
    monedaDocumentoActual = moneda;
    $("#moneda_documento").val(moneda);

    if (moneda === "VES") {
        $("#cardMonedaVes").addClass("active-moneda");
        $("#cardMonedaUsd").removeClass("active-moneda");
        $("#radio_ves").prop("checked", true);
        $(".label-simbolo-moneda-fac").text("Bs.");
    } else {
        $("#cardMonedaUsd").addClass("active-moneda");
        $("#cardMonedaVes").removeClass("active-moneda");
        $("#radio_usd").prop("checked", true);
        $(".label-simbolo-moneda-fac").text("$");
    }

    recalcularTotalesGenerales();
};

/**
 * Control de Fases: Fase 1 (Datos Principales) <-> Fase 2 (Productos & Liquidación)
 */
const avanzarAFase2 = function () {
    let errores = [];

    const numDoc = ($("#numero_documento").val() || "").trim();
    const provId = $("#proveedor_id").val();
    const almId = $("#almacen_id").val();
    const fechaEmis = $("#fecha_emision").val();
    const fechaRecep = $("#fecha_recepcion").val();
    const montoBruto = parseFloat($("#monto_bruto_input").val()) || 0;

    $("#seccionFase1 .form-control, #seccionFase1 .form-select").removeClass("is-invalid");

    if (!numDoc) {
        $("#numero_documento").addClass("is-invalid");
        errores.push("N° Factura / Documento");
    }
    if (!provId) {
        $("#proveedor_id").addClass("is-invalid");
        errores.push("Proveedor");
    }
    if (!almId) {
        $("#almacen_id").addClass("is-invalid");
        errores.push("Almacén Predeterminado");
    }
    if (!fechaEmis) {
        $("#fecha_emision").addClass("is-invalid");
        errores.push("Fecha de Emisión");
    }
    if (!fechaRecep) {
        $("#fecha_recepcion").addClass("is-invalid");
        errores.push("Fecha de Recepción");
    }
    if (montoBruto <= 0) {
        $("#monto_bruto_input").addClass("is-invalid");
        errores.push("Monto Bruto Factura");
    }

    if (errores.length > 0) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Campos requeridos en Fase 1",
                text: `Por favor completa los siguientes datos obligatorios: ${errores.join(", ")}.`,
            });
        }
        return false;
    }

    // Actualizar barra de resumen superior en Fase 2
    const provText = $("#proveedor_id option:selected").text();
    const almText = $("#almacen_id option:selected").text();
    const tipoDocText = $("#tipo_documento option:selected").text();

    if (monedaDocumentoActual === "VES") {
        $("#badgeMonedaFase2").html('<i class="fas fa-money-bill-wave me-1"></i> Factura en Bs. VES').css({
            "background-color": "#eff6ff",
            color: "#1d4ed8",
            border: "1px solid #bfdbfe",
        });
    } else {
        $("#badgeMonedaFase2").html('<i class="fas fa-dollar-sign me-1"></i> Factura en $ USD').css({
            "background-color": "#dcfce7",
            color: "#15803d",
            border: "1px solid #bbf7d0",
        });
    }

    $("#badgeDocFase2").html(`<i class="fas fa-file-invoice text-primary me-1"></i> ${numDoc} (${tipoDocText})`);
    $("#badgeProvFase2").html(`<i class="fas fa-truck text-primary me-1"></i> ${provText}`);
    $("#badgeAlmFase2").html(`<i class="fas fa-warehouse text-secondary me-1"></i> ${almText}`);

    // Transición suave entre fases
    $("#seccionFase1").hide();
    $("#seccionFase2").fadeIn(200);

    // Stepper header
    $("#btnPaso1Stepper").removeClass("active").addClass("text-white-50");
    $("#btnPaso2Stepper").addClass("active").removeClass("text-white-50");

    // Botones de footer
    $("#btnAvanzarFase2Footer").hide();
    $("#btnVolverFase1Footer").show();
    $("#btnGuardarRecepcion").show();

    setTimeout(() => {
        $("#inputEscaneoProducto").focus();
    }, 200);

    return true;
};
window.avanzarAFase2 = avanzarAFase2;

const volverAFase1 = function () {
    $("#seccionFase2").hide();
    $("#seccionFase1").fadeIn(200);

    // Stepper header
    $("#btnPaso2Stepper").removeClass("active").addClass("text-white-50");
    $("#btnPaso1Stepper").addClass("active").removeClass("text-white-50");

    // Botones de footer
    $("#btnAvanzarFase2Footer").show();
    $("#btnVolverFase1Footer").hide();
    $("#btnGuardarRecepcion").hide();
};
window.volverAFase1 = volverAFase1;

/**
 * Condición de pago: Contado vs Crédito
 */
const toggleCondicionPago = function () {
    const condicion = $("#condicion_pago").val();
    if (condicion === "credito") {
        $("#contenedorDiasCredito").slideDown(150);
        calcularFechaVencimiento();
    } else {
        $("#contenedorDiasCredito").slideUp(150);
    }
};
window.toggleCondicionPago = toggleCondicionPago;

const calcularFechaVencimiento = function () {
    const fechaEmision = $("#fecha_emision").val();
    const dias = parseInt($("#dias_credito").val()) || 0;

    if (fechaEmision && dias > 0) {
        const fecha = new Date(fechaEmision);
        fecha.setDate(fecha.getDate() + dias);
        const fechaStr = fecha.toISOString().split("T")[0];
        $("#labelFechaVencimiento").text(`Vence: ${fechaStr}`);
    } else {
        $("#labelFechaVencimiento").text("Vence: --");
    }
};
window.calcularFechaVencimiento = calcularFechaVencimiento;

let listaProductosCargados = [];
let productoSeleccionadoActual = null;
let indiceEdicionActual = null;

/**
 * Configuración del Buscador / Escáner de Productos
 */
const configurarBuscadorProductos = function () {
    const $input = $("#inputEscaneoProducto");
    const $resultados = $("#resultadosBusqueda");

    // Enter / Lector de código de barras
    $input.on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim().toLowerCase();
            if (!query) return;

            // Buscar coincidencia exacta por código de barra o SKU
            const productoEncontrado = catalogosSistema.productos.find((p) => {
                const skuMatch = (p.codigo_interno || "").toLowerCase() === query;
                const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase() === query);
                return skuMatch || barcodeMatch;
            });

            if (productoEncontrado) {
                seleccionarProductoParaCarga(productoEncontrado);
                $input.val("");
                $resultados.hide();
            } else {
                const coincidencias = catalogosSistema.productos.filter((p) =>
                    p.nombre.toLowerCase().includes(query) || (p.codigo_interno || "").toLowerCase().includes(query)
                );

                if (coincidencias.length === 1) {
                    seleccionarProductoParaCarga(coincidencias[0]);
                    $input.val("");
                    $resultados.hide();
                } else if (coincidencias.length > 1) {
                    renderizarResultadosBusqueda(coincidencias, query);
                } else {
                    if (window.Swal) {
                        Swal.fire({
                            title: "Producto no encontrado",
                            text: `No existe ningún artículo con "${query}". ¿Deseas crearlo ahora mismo sin perder tus datos?`,
                            icon: "question",
                            showCancelButton: true,
                            confirmButtonColor: "#2563eb",
                            cancelButtonColor: "#64748b",
                            confirmButtonText: '<i class="fas fa-plus me-1"></i> Sí, crear producto',
                            cancelButtonText: "Cancelar",
                        }).then((res) => {
                            if (res.isConfirmed) {
                                abrirModalRapidoProducto(query);
                            }
                        });
                    } else {
                        abrirModalRapidoProducto(query);
                    }
                }
            }
        }
    });

    // Búsqueda interactiva
    let timeoutBusqueda = null;
    $input.on("input", function () {
        clearTimeout(timeoutBusqueda);
        const query = $(this).val().trim().toLowerCase();

        if (query.length < 2) {
            $resultados.hide();
            return;
        }

        timeoutBusqueda = setTimeout(() => {
            const coincidencias = catalogosSistema.productos.filter((p) => {
                const nombreMatch = p.nombre.toLowerCase().includes(query);
                const skuMatch = (p.codigo_interno || "").toLowerCase().includes(query);
                const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase() === query);
                return nombreMatch || skuMatch || barcodeMatch;
            });

            renderizarResultadosBusqueda(coincidencias, query);
        }, 150);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#inputEscaneoProducto, #resultadosBusqueda").length) {
            $resultados.hide();
        }
    });
};

const renderizarResultadosBusqueda = function (productos, query = "") {
    const $resultados = $("#resultadosBusqueda");
    $resultados.empty();

    if (!productos || productos.length === 0) {
        const querySegura = (query || "").replace(/"/g, "&quot;");
        $resultados.append(`
            <div class="list-group-item p-4 text-center bg-white border-0">
                <div class="avatar-executive-sm mx-auto mb-2 rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-search"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Sin resultados encontrados</h6>
                <p class="text-muted small mb-3">No existe ningún artículo registrado con <strong>"${querySegura}"</strong>.</p>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="abrirModalRapidoProducto('${querySegura.replace(/'/g, "\\'")}')">
                    <i class="fas fa-plus me-1"></i> Registrar "${querySegura}" Ahora
                </button>
            </div>
        `);
        $resultados.show();
        return;
    }

    productos.slice(0, 8).forEach((p) => {
        const costoViejoUsd = parseFloat(p.precio_costo_usd || 0).toFixed(2);
        const costoViejoBs = (parseFloat(p.precio_costo_usd || 0) * tasaUsdActual).toFixed(2);
        const detalViejoUsd = parseFloat(p.precio_detal_usd || 0).toFixed(2);

        const itemHtml = `
            <div class="list-group-item list-group-item-action p-3 d-flex align-items-center justify-content-between item-busqueda-producto bg-white" data-id="${p.id}" style="background-color: #ffffff !important; cursor: pointer; border-bottom: 1px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 40px; height: 40px; min-width: 40px; font-size: 1.1rem;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark" style="font-size: 0.92rem;">${p.nombre}</span>
                            <span class="badge rounded-pill bg-light text-secondary border font-monospace px-2 py-0" style="font-size: 0.72rem;">#${p.codigo_interno || ''}</span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 small font-monospace">
                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="background-color: #f8fafc; color: #334155; border: 1px solid #e2e8f0;">
                                <i class="fas fa-tag text-secondary me-1"></i>Último Costo: <strong class="text-dark">$ ${costoViejoUsd}</strong> <span class="text-muted">(Bs. ${costoViejoBs})</span>
                            </span>
                            <span class="badge rounded-pill px-2 py-1 fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                                <i class="fas fa-store text-primary me-1"></i>Detal: $ ${detalViejoUsd}
                            </span>
                        </div>
                    </div>
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 py-1 shadow-xs fw-bold d-flex align-items-center gap-1">
                        <i class="fas fa-plus"></i>
                        <span>Cargar</span>
                    </button>
                </div>
            </div>
        `;
        $resultados.append(itemHtml);
    });

    $resultados.find(".item-busqueda-producto").on("click", function () {
        const id = $(this).data("id");
        const prod = catalogosSistema.productos.find((p) => p.id === id);
        if (prod) {
            seleccionarProductoParaCarga(prod);
            $("#inputEscaneoProducto").val("");
            $resultados.hide();
        }
    });

    $resultados.show();
};

/**
 * Seleccionar Producto para el Formulario Superior Inteligente
 */
const seleccionarProductoParaCarga = function (prod, datosPrecargados = null) {
    productoSeleccionadoActual = prod;
    if (datosPrecargados === null) {
        indiceEdicionActual = null;
        $("#badgeModoEdicion").hide();
        $("#labelTituloFormRenglon").text("2. Datos de Entrada del Renglón");
        $("#btnGuardarRenglonTexto").text("Añadir Artículo");
    }

    const costoAnteriorUsd = parseFloat(prod.precio_costo_usd || 0);
    const costoAnteriorBs = (costoAnteriorUsd * tasaUsdActual).toFixed(2);
    const detalAnteriorUsd = parseFloat(prod.precio_detal_usd || 0);
    const detalAnteriorBs = (detalAnteriorUsd * tasaUsdActual).toFixed(2);
    const mayoristaAnteriorUsd = parseFloat(prod.precio_mayorista_usd || 0);
    const mayoristaAnteriorBs = (mayoristaAnteriorUsd * tasaUsdActual).toFixed(2);

    let stockTotal = 0;
    if (Array.isArray(prod.stock_almacenes)) {
        stockTotal = prod.stock_almacenes.reduce((acc, s) => acc + parseFloat(s.cantidad_actual || 0), 0);
    }

    // 1. Llenar Ficha Visual (Read-only)
    $("#infoProdNombre").text(prod.nombre);
    $("#infoProdCodigo").text(prod.codigo_interno || "--");
    $("#infoProdUnidad").text(prod.unidad_medida || "UND");
    $("#infoProdIvaBadge").text(prod.aplica_iva ? `IVA ${parseFloat(prod.iva_porcentaje || 16)}%` : "Exento (0%)");
    $("#infoProdStockBadge").text(`Stock Total: ${stockTotal} ${prod.unidad_medida || 'und'}`);

    $("#infoProdCostoActual").text(`$ ${costoAnteriorUsd.toFixed(2)} / Bs. ${costoAnteriorBs}`);
    $("#infoProdDetalActual").text(`$ ${detalAnteriorUsd.toFixed(2)} / Bs. ${detalAnteriorBs}`);
    $("#infoProdMayoristaActual").text(`$ ${mayoristaAnteriorUsd.toFixed(2)} / Bs. ${mayoristaAnteriorBs}`);

    $("#panelInfoProductoSeleccionado").slideDown(150);

    // 2. Poblar opciones de Almacenes en el formulario de renglón
    const $selectAlm = $("#form_renglon_almacen_id");
    $selectAlm.empty();
    const almDefectoId = $("#almacen_id").val();

    if (Array.isArray(catalogosSistema.almacenes)) {
        catalogosSistema.almacenes.forEach((a) => {
            const sel = String(a.id) === String(datosPrecargados?.almacen_id || almDefectoId) ? "selected" : "";
            $selectAlm.append(`<option value="${a.id}" ${sel}>${a.nombre}</option>`);
        });
    }

    // 3. Llenar valores iniciales en los campos modificables
    const esVes = monedaDocumentoActual === "VES";
    const margenDetalSugerido = parseFloat(datosPrecargados?.margen_detal_porcentaje || prod.ultimo_margen_detal || 30.00);
    const margenMayoristaSugerido = parseFloat(datosPrecargados?.margen_mayorista_porcentaje || prod.ultimo_margen_mayorista || 15.00);

    let costoInicial = 0;
    if (datosPrecargados) {
        costoInicial = esVes ? parseFloat(datosPrecargados.costo_unitario_bs || 0) : parseFloat(datosPrecargados.costo_unitario_usd || 0);
    } else {
        costoInicial = esVes ? parseFloat(costoAnteriorBs) : costoAnteriorUsd;
    }

    const bultosInicial = datosPrecargados?.bultos || 1;
    const unidPorBultoInicial = datosPrecargados?.unidades_por_bulto || 1;
    let costoBultoInicial = "";

    if (datosPrecargados) {
        costoBultoInicial = esVes ? datosPrecargados.costo_bulto_bs : datosPrecargados.costo_bulto_usd;
    } else if (costoInicial > 0) {
        costoBultoInicial = (costoInicial * unidPorBultoInicial).toFixed(2);
    }

    $("#form_renglon_bultos").val(bultosInicial);
    $("#form_renglon_unid_bulto").val(unidPorBultoInicial);
    $("#form_renglon_cantidad").val(bultosInicial * unidPorBultoInicial);
    $("#form_renglon_costo_bulto").val(costoBultoInicial);
    $("#form_renglon_costo_unitario").val(costoInicial > 0 ? costoInicial.toFixed(4) : "0.0000");
    $("#form_renglon_descuento").val(datosPrecargados?.descuento_porcentaje || 0);

    const ivaDefault = datosPrecargados ? datosPrecargados.iva_porcentaje : (prod.aplica_iva ? (parseFloat(prod.iva_porcentaje) || 16) : 0);
    $("#form_renglon_iva").val(ivaDefault);

    $("#form_renglon_margen_detal").val(margenDetalSugerido.toFixed(0));
    $("#form_renglon_margen_mayorista").val(margenMayoristaSugerido.toFixed(0));

    actualizarStockAlmacenFormulario();
    calcularCantidadDesdeBultos();

    $("#panelFormularioRenglon").slideDown(150);

    setTimeout(() => {
        $("#form_renglon_bultos").focus().select();
    }, 200);
};

const actualizarStockAlmacenFormulario = function () {
    if (!productoSeleccionadoActual) return;
    const almSelId = parseInt($("#form_renglon_almacen_id").val()) || 0;

    let stockEncontrado = 0;
    if (Array.isArray(productoSeleccionadoActual.stock_almacenes)) {
        const itemStock = productoSeleccionadoActual.stock_almacenes.find((s) => s.almacen_id === almSelId);
        if (itemStock) {
            stockEncontrado = parseFloat(itemStock.cantidad_actual || 0);
        }
    }

    $("#form_renglon_stock_actual").text(`${stockEncontrado} ${productoSeleccionadoActual.unidad_medida || 'und'}`);
};
window.actualizarStockAlmacenFormulario = actualizarStockAlmacenFormulario;

/**
 * Cálculos del Formulario Superior Inteligente
 */
const calcularCantidadDesdeBultos = function () {
    const bultos = parseFloat($("#form_renglon_bultos").val()) || 0;
    const unidPorBulto = parseFloat($("#form_renglon_unid_bulto").val()) || 1;
    const cantidadTotal = bultos * unidPorBulto;
    $("#form_renglon_cantidad").val(cantidadTotal > 0 ? cantidadTotal : 0);

    const costoBulto = parseFloat($("#form_renglon_costo_bulto").val()) || 0;
    if (costoBulto > 0 && unidPorBulto > 0) {
        $("#form_renglon_costo_unitario").val((costoBulto / unidPorBulto).toFixed(4));
    }

    calcularPrecioDetalDesdeMargen();
    calcularPrecioMayoristaDesdeMargen();
    recalcularFormularioRenglon();
};
window.calcularCantidadDesdeBultos = calcularCantidadDesdeBultos;

const calcularCostoDesdeBulto = function () {
    const costoBulto = parseFloat($("#form_renglon_costo_bulto").val()) || 0;
    const unidPorBulto = parseFloat($("#form_renglon_unid_bulto").val()) || 1;

    if (costoBulto > 0 && unidPorBulto > 0) {
        $("#form_renglon_costo_unitario").val((costoBulto / unidPorBulto).toFixed(4));
    } else {
        $("#form_renglon_costo_unitario").val("0.0000");
    }

    calcularPrecioDetalDesdeMargen();
    calcularPrecioMayoristaDesdeMargen();
    recalcularFormularioRenglon();
};
window.calcularCostoDesdeBulto = calcularCostoDesdeBulto;

const actualizarCostoUnitarioManual = function () {
    calcularPrecioDetalDesdeMargen();
    calcularPrecioMayoristaDesdeMargen();
    recalcularFormularioRenglon();
};
window.actualizarCostoUnitarioManual = actualizarCostoUnitarioManual;

const calcularPrecioDetalDesdeMargen = function () {
    const esVes = monedaDocumentoActual === "VES";
    const costoInput = parseFloat($("#form_renglon_costo_unitario").val()) || 0;
    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;

    const margenDetal = parseFloat($("#form_renglon_margen_detal").val()) || 0;
    if (costoUsd > 0) {
        const nuevoDetalUsd = costoUsd * (1 + margenDetal / 100);
        $("#form_renglon_precio_detal").val(nuevoDetalUsd.toFixed(2));
        $("#form_renglon_detal_bs").text(`Bs. ${(nuevoDetalUsd * tasaUsdActual).toFixed(2)}`);
    } else {
        $("#form_renglon_precio_detal").val("");
        $("#form_renglon_detal_bs").text("Bs. 0.00");
    }
};
window.calcularPrecioDetalDesdeMargen = calcularPrecioDetalDesdeMargen;

const calcularMargenDetalDesdePrecio = function () {
    const esVes = monedaDocumentoActual === "VES";
    const costoInput = parseFloat($("#form_renglon_costo_unitario").val()) || 0;
    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;

    const precioDetalUsd = parseFloat($("#form_renglon_precio_detal").val()) || 0;

    if (costoUsd > 0 && precioDetalUsd > 0) {
        const nuevoMargen = ((precioDetalUsd - costoUsd) / costoUsd) * 100;
        $("#form_renglon_margen_detal").val(nuevoMargen.toFixed(0));
        $("#form_renglon_detal_bs").text(`Bs. ${(precioDetalUsd * tasaUsdActual).toFixed(2)}`);
    }
    recalcularFormularioRenglon();
};
window.calcularMargenDetalDesdePrecio = calcularMargenDetalDesdePrecio;

const calcularPrecioMayoristaDesdeMargen = function () {
    const esVes = monedaDocumentoActual === "VES";
    const costoInput = parseFloat($("#form_renglon_costo_unitario").val()) || 0;
    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;

    const margenMayor = parseFloat($("#form_renglon_margen_mayorista").val()) || 0;
    if (costoUsd > 0) {
        const nuevoMayorUsd = costoUsd * (1 + margenMayor / 100);
        $("#form_renglon_precio_mayorista").val(nuevoMayorUsd.toFixed(2));
        $("#form_renglon_mayorista_bs").text(`Bs. ${(nuevoMayorUsd * tasaUsdActual).toFixed(2)}`);
    } else {
        $("#form_renglon_precio_mayorista").val("");
        $("#form_renglon_mayorista_bs").text("Bs. 0.00");
    }
};
window.calcularPrecioMayoristaDesdeMargen = calcularPrecioMayoristaDesdeMargen;

const calcularMargenMayoristaDesdePrecio = function () {
    const esVes = monedaDocumentoActual === "VES";
    const costoInput = parseFloat($("#form_renglon_costo_unitario").val()) || 0;
    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;

    const precioMayorUsd = parseFloat($("#form_renglon_precio_mayorista").val()) || 0;

    if (costoUsd > 0 && precioMayorUsd > 0) {
        const nuevoMargen = ((precioMayorUsd - costoUsd) / costoUsd) * 100;
        $("#form_renglon_margen_mayorista").val(nuevoMargen.toFixed(0));
        $("#form_renglon_mayorista_bs").text(`Bs. ${(precioMayorUsd * tasaUsdActual).toFixed(2)}`);
    }
    recalcularFormularioRenglon();
};
window.calcularMargenMayoristaDesdePrecio = calcularMargenMayoristaDesdePrecio;

const recalcularFormularioRenglon = function () {
    const esVes = monedaDocumentoActual === "VES";
    const cantidad = parseFloat($("#form_renglon_cantidad").val()) || 0;
    const costoInput = parseFloat($("#form_renglon_costo_unitario").val()) || 0;
    const descPorc = parseFloat($("#form_renglon_descuento").val()) || 0;

    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;
    let costoBs = (costoUsd * tasaUsdActual).toFixed(2);

    if (esVes) {
        $("#form_renglon_costo_equivalente").text(`Equiv: $ ${costoUsd.toFixed(2)}`);
    } else {
        $("#form_renglon_costo_equivalente").text(`Equiv: Bs. ${costoBs}`);
    }

    const subtotalBrutoUsd = cantidad * costoUsd;
    const subtotalNetoUsd = subtotalBrutoUsd * (1 - descPorc / 100);
    const subtotalNetoBs = subtotalNetoUsd * tasaUsdActual;

    $("#form_renglon_subtotal_usd").text(`$ ${subtotalNetoUsd.toFixed(2)}`);
    $("#form_renglon_subtotal_bs").text(`Bs. ${subtotalNetoBs.toFixed(2)}`);
};
window.recalcularFormularioRenglon = recalcularFormularioRenglon;

/**
 * Agregar o Actualizar Renglón en la lista
 */
const agregarOActualizarRenglon = function () {
    if (!productoSeleccionadoActual) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Selecciona un producto",
                text: "Por favor busca o escanea un producto primero.",
            });
        }
        return;
    }

    const bultos = parseFloat($("#form_renglon_bultos").val()) || 0;
    const unidPorBulto = parseFloat($("#form_renglon_unid_bulto").val()) || 1;
    const costoBultoInput = parseFloat($("#form_renglon_costo_bulto").val()) || 0;
    const almId = parseInt($("#form_renglon_almacen_id").val()) || 0;
    const almNombre = $("#form_renglon_almacen_id option:selected").text();

    if (bultos <= 0) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Bultos / Cajas requerido",
                text: "Por favor ingresa la cantidad de bultos o cajas recibidas (mayor a 0).",
            });
        }
        $("#form_renglon_bultos").focus().select();
        return;
    }

    if (unidPorBulto <= 0) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Unidades por bulto inválido",
                text: "Las unidades por bulto deben ser mínimo 1.",
            });
        }
        $("#form_renglon_unid_bulto").focus().select();
        return;
    }

    if (costoBultoInput <= 0) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Costo por Bulto requerido",
                text: "El costo por bulto es obligatorio y debe ser mayor a 0.",
            });
        }
        $("#form_renglon_costo_bulto").focus().select();
        return;
    }

    const cantidad = bultos * unidPorBulto;
    $("#form_renglon_cantidad").val(cantidad);

    const costoInput = costoBultoInput / unidPorBulto;
    $("#form_renglon_costo_unitario").val(costoInput.toFixed(4));

    const esVes = monedaDocumentoActual === "VES";
    let costoUsd = esVes ? (tasaUsdActual > 0 ? costoInput / tasaUsdActual : 0) : costoInput;
    let costoBs = costoUsd * tasaUsdActual;

    const costoBultoUsd = esVes ? (tasaUsdActual > 0 ? costoBultoInput / tasaUsdActual : 0) : costoBultoInput;
    const costoBultoBs = costoBultoUsd * tasaUsdActual;

    const descPorc = parseFloat($("#form_renglon_descuento").val()) || 0;
    const ivaPorc = parseFloat($("#form_renglon_iva").val()) || 0;

    const precioDetalUsd = parseFloat($("#form_renglon_precio_detal").val()) || (costoUsd * 1.3);
    const precioDetalBs = precioDetalUsd * tasaUsdActual;
    const margenDetal = parseFloat($("#form_renglon_margen_detal").val()) || 30;

    const precioMayorUsd = parseFloat($("#form_renglon_precio_mayorista").val()) || (costoUsd * 1.15);
    const precioMayorBs = precioMayorUsd * tasaUsdActual;
    const margenMayor = parseFloat($("#form_renglon_margen_mayorista").val()) || 15;

    const subtotalBrutoUsd = cantidad * costoUsd;
    const descuentoUsd = subtotalBrutoUsd * (descPorc / 100);
    const subtotalNetoUsd = subtotalBrutoUsd - descuentoUsd;
    const subtotalNetoBs = subtotalNetoUsd * tasaUsdActual;
    const ivaMontoUsd = ivaPorc > 0 ? subtotalNetoUsd * (ivaPorc / 100) : 0;

    const itemRenglon = {
        producto_id: productoSeleccionadoActual.id,
        producto: productoSeleccionadoActual,
        almacen_id: almId,
        almacen_nombre: almNombre,
        bultos: bultos,
        unidades_por_bulto: unidPorBulto,
        cantidad: cantidad,
        costo_bulto_usd: costoBultoUsd,
        costo_bulto_bs: costoBultoBs,
        costo_unitario_usd: costoUsd,
        costo_unitario_bs: costoBs,
        descuento_porcentaje: descPorc,
        descuento_usd: descuentoUsd,
        descuento_bs: descuentoUsd * tasaUsdActual,
        aplica_iva: ivaPorc > 0,
        iva_porcentaje: ivaPorc,
        iva_monto_usd: ivaMontoUsd,
        iva_monto_bs: ivaMontoUsd * tasaUsdActual,
        margen_detal_porcentaje: margenDetal,
        precio_detal_usd: precioDetalUsd,
        precio_detal_bs: precioDetalBs,
        margen_mayorista_porcentaje: margenMayor,
        precio_mayorista_usd: precioMayorUsd,
        precio_mayorista_bs: precioMayorBs,
        subtotal_usd: subtotalNetoUsd,
        subtotal_bs: subtotalNetoBs,
        costo_anterior_usd: parseFloat(productoSeleccionadoActual.precio_costo_usd || 0),
        costo_anterior_bs: parseFloat(productoSeleccionadoActual.precio_costo_usd || 0) * tasaUsdActual,
        precio_detal_anterior_usd: parseFloat(productoSeleccionadoActual.precio_detal_usd || 0),
        precio_detal_anterior_bs: parseFloat(productoSeleccionadoActual.precio_detal_usd || 0) * tasaUsdActual,
        precio_mayorista_anterior_usd: parseFloat(productoSeleccionadoActual.precio_mayorista_usd || 0),
        precio_mayorista_anterior_bs: parseFloat(productoSeleccionadoActual.precio_mayorista_usd || 0) * tasaUsdActual,
    };

    if (indiceEdicionActual !== null && indiceEdicionActual >= 0 && indiceEdicionActual < listaProductosCargados.length) {
        listaProductosCargados[indiceEdicionActual] = itemRenglon;
    } else {
        // Verificar si ya existe con el mismo producto y mismo almacén
        const indiceExistente = listaProductosCargados.findIndex(
            (it) => it.producto_id === itemRenglon.producto_id && it.almacen_id === itemRenglon.almacen_id
        );

        if (indiceExistente !== -1) {
            listaProductosCargados[indiceExistente].cantidad += itemRenglon.cantidad;
            listaProductosCargados[indiceExistente].costo_unitario_usd = itemRenglon.costo_unitario_usd;
            listaProductosCargados[indiceExistente].costo_unitario_bs = itemRenglon.costo_unitario_bs;
            listaProductosCargados[indiceExistente].precio_detal_usd = itemRenglon.precio_detal_usd;
            listaProductosCargados[indiceExistente].precio_mayorista_usd = itemRenglon.precio_mayorista_usd;
            listaProductosCargados[indiceExistente].subtotal_usd = listaProductosCargados[indiceExistente].cantidad * itemRenglon.costo_unitario_usd * (1 - itemRenglon.descuento_porcentaje / 100);
            listaProductosCargados[indiceExistente].subtotal_bs = listaProductosCargados[indiceExistente].subtotal_usd * tasaUsdActual;
        } else {
            listaProductosCargados.push(itemRenglon);
        }
    }

    cancelarEdicionRenglon();
    renderizarTablaDetalles();
    recalcularTotalesGenerales();

    $("#inputEscaneoProducto").focus();
};
window.agregarOActualizarRenglon = agregarOActualizarRenglon;

/**
 * Editar Renglón Cargado
 */
const editarRenglon = function (indice) {
    if (indice < 0 || indice >= listaProductosCargados.length) return;
    const item = listaProductosCargados[indice];

    indiceEdicionActual = indice;
    $("#badgeModoEdicion").show();
    $("#labelTituloFormRenglon").text(`2. Modificar Renglón #${indice + 1}`);
    $("#btnGuardarRenglonTexto").text("Guardar Cambios");

    seleccionarProductoParaCarga(item.producto, item);

    $("html, body, .modal-body").animate(
        {
            scrollTop: $("#cardCargaProducto").offset().top - 80,
        },
        200
    );
};
window.editarRenglon = editarRenglon;

/**
 * Eliminar Renglón Cargado
 */
const eliminarRenglon = function (indice) {
    if (indice < 0 || indice >= listaProductosCargados.length) return;
    listaProductosCargados.splice(indice, 1);

    if (indiceEdicionActual === indice) {
        cancelarEdicionRenglon();
    }

    renderizarTablaDetalles();
    recalcularTotalesGenerales();
};
window.eliminarRenglon = eliminarRenglon;

/**
 * Cancelar selección / edición de renglón
 */
const cancelarEdicionRenglon = function () {
    productoSeleccionadoActual = null;
    indiceEdicionActual = null;
    $("#panelInfoProductoSeleccionado").slideUp(100);
    $("#panelFormularioRenglon").slideUp(100);
    $("#badgeModoEdicion").hide();
    $("#inputEscaneoProducto").val("").focus();
};
window.cancelarEdicionRenglon = cancelarEdicionRenglon;

/**
 * Limpiar toda la tabla de productos
 */
const limpiarTablaProductos = function () {
    listaProductosCargados = [];
    cancelarEdicionRenglon();
    renderizarTablaDetalles();
    recalcularTotalesGenerales();
};
window.limpiarTablaProductos = limpiarTablaProductos;

/**
 * Renderizar Tabla Limpia y Renglones Ocultos de Formulario
 */
const renderizarTablaDetalles = function () {
    const $tbody = $("#contenedorFilasRecepcion");
    const $hiddenContainer = $("#contenedorInputsHiddenDetalles");
    $tbody.empty();
    $hiddenContainer.empty();

    if (listaProductosCargados.length === 0) {
        $tbody.append(`
            <tr id="filaVaciaMensaje">
                <td colspan="11" class="text-center py-5 text-muted">
                    <i class="fas fa-dolly fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                    <span class="fw-semibold">No hay productos agregados a la recepción.</span>
                    <br>
                    <small class="text-muted">Busca o escanea un producto en la parte superior para agregarlo al comprobante.</small>
                </td>
            </tr>
        `);
        $("#contadorRenglones").text("0 Productos");
        return;
    }

    $("#contadorRenglones").text(`${listaProductosCargados.length} Producto${listaProductosCargados.length === 1 ? "" : "s"}`);

    listaProductosCargados.forEach((item, idx) => {
        const prod = item.producto;
        const bultosTexto = item.bultos > 0 ? `<small class="text-muted d-block font-monospace">(${item.bultos} blt x ${item.unidades_por_bulto})</small>` : "";

        const filaHtml = `
            <tr class="fila-producto-cargado align-middle">
                <!-- # -->
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>

                <!-- Producto / SKU -->
                <td>
                    <strong class="text-dark d-block text-capitalize" style="font-size: 0.88rem;">${prod.nombre}</strong>
                    <div class="d-flex align-items-center gap-1 small text-muted font-monospace" style="font-size: 0.72rem;">
                        <span class="badge rounded-pill bg-light text-secondary border px-1 py-0">#${prod.codigo_interno}</span>
                        <span>•</span>
                        <span>${prod.unidad_medida || 'UND'}</span>
                    </div>
                </td>

                <!-- Almacén Destino -->
                <td>
                    <span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.74rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                        <i class="fas fa-warehouse me-1"></i>${item.almacen_nombre}
                    </span>
                </td>

                <!-- Cantidad -->
                <td class="text-center font-monospace">
                    <strong class="text-dark" style="font-size: 0.90rem;">${parseFloat(item.cantidad).toLocaleString()}</strong>
                    ${bultosTexto}
                </td>

                <!-- Costo Unitario ($ y Bs) -->
                <td class="text-end font-monospace">
                    <strong class="text-success d-block">$ ${parseFloat(item.costo_unitario_usd).toFixed(2)}</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Bs. ${parseFloat(item.costo_unitario_bs).toFixed(2)}</small>
                </td>

                <!-- Descuento -->
                <td class="text-center font-monospace small">
                    ${item.descuento_porcentaje > 0 ? `<span class="badge rounded-pill bg-danger-subtle text-danger px-2 py-1">-${parseFloat(item.descuento_porcentaje)}%</span>` : '<span class="text-muted">0%</span>'}
                </td>

                <!-- IVA -->
                <td class="text-center font-monospace small">
                    <span class="badge rounded-pill bg-light text-secondary border px-2 py-1">
                        ${item.iva_porcentaje > 0 ? `IVA ${parseFloat(item.iva_porcentaje)}%` : 'Exento'}
                    </span>
                </td>

                <!-- Precio Detal -->
                <td class="text-end font-monospace">
                    <strong class="text-primary d-block">$ ${parseFloat(item.precio_detal_usd).toFixed(2)}</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Bs. ${parseFloat(item.precio_detal_bs).toFixed(2)} <span class="badge bg-light text-secondary">(${parseFloat(item.margen_detal_porcentaje)}%)</span></small>
                </td>

                <!-- Precio Mayorista -->
                <td class="text-end font-monospace">
                    <strong style="color: #7e22ce;" class="d-block">$ ${parseFloat(item.precio_mayorista_usd).toFixed(2)}</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Bs. ${parseFloat(item.precio_mayorista_bs).toFixed(2)} <span class="badge bg-light text-secondary">(${parseFloat(item.margen_mayorista_porcentaje)}%)</span></small>
                </td>

                <!-- Subtotal -->
                <td class="text-end font-monospace">
                    <strong class="text-dark d-block" style="font-size: 0.92rem;">$ ${parseFloat(item.subtotal_usd).toFixed(2)}</strong>
                    <small class="text-muted" style="font-size: 0.72rem;">Bs. ${parseFloat(item.subtotal_bs).toFixed(2)}</small>
                </td>

                <!-- Acciones -->
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-xs" onclick="editarRenglon(${idx})" title="Editar Renglón" style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-pencil-alt" style="font-size: 0.75rem;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="eliminarRenglon(${idx})" title="Eliminar Renglón" style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        $tbody.append(filaHtml);

        // Generar Inputs Hidden para enviar al controlador
        const hiddenHtml = `
            <input type="hidden" name="detalles[${idx}][producto_id]" value="${item.producto_id}">
            <input type="hidden" name="detalles[${idx}][almacen_id]" value="${item.almacen_id}">
            <input type="hidden" name="detalles[${idx}][bultos]" value="${item.bultos}">
            <input type="hidden" name="detalles[${idx}][unidades_por_bulto]" value="${item.unidades_por_bulto}">
            <input type="hidden" name="detalles[${idx}][cantidad]" value="${item.cantidad}">
            <input type="hidden" name="detalles[${idx}][costo_bulto_usd]" value="${item.costo_bulto_usd}">
            <input type="hidden" name="detalles[${idx}][costo_bulto_bs]" value="${item.costo_bulto_bs}">
            <input type="hidden" name="detalles[${idx}][costo_unitario_usd]" value="${item.costo_unitario_usd}">
            <input type="hidden" name="detalles[${idx}][costo_unitario_bs]" value="${item.costo_unitario_bs}">
            <input type="hidden" name="detalles[${idx}][descuento_porcentaje]" value="${item.descuento_porcentaje}">
            <input type="hidden" name="detalles[${idx}][iva_porcentaje]" value="${item.iva_porcentaje}">
            <input type="hidden" name="detalles[${idx}][margen_detal_porcentaje]" value="${item.margen_detal_porcentaje}">
            <input type="hidden" name="detalles[${idx}][precio_detal_usd]" value="${item.precio_detal_usd}">
            <input type="hidden" name="detalles[${idx}][margen_mayorista_porcentaje]" value="${item.margen_mayorista_porcentaje}">
            <input type="hidden" name="detalles[${idx}][precio_mayorista_usd]" value="${item.precio_mayorista_usd}">
        `;
        $hiddenContainer.append(hiddenHtml);
    });
};

/**
 * Recalcular Resumen Global de Liquidación
 */
const recalcularTotalesGenerales = function () {
    let totalUnidades = 0;
    let montoBrutoUsd = 0;
    let totalDescuentosItemsUsd = 0;
    let totalIvaUsd = 0;

    listaProductosCargados.forEach((item) => {
        const cant = parseFloat(item.cantidad) || 0;
        const cUsd = parseFloat(item.costo_unitario_usd) || 0;
        const dUsd = parseFloat(item.descuento_usd) || 0;
        const ivaUsd = parseFloat(item.iva_monto_usd) || 0;

        totalUnidades += cant;
        montoBrutoUsd += cant * cUsd;
        totalDescuentosItemsUsd += dUsd;
        totalIvaUsd += ivaUsd;
    });

    // Descuento global
    const descGlobalPorc = parseFloat($("#descuento_global_porcentaje").val()) || 0;
    const subtotalAntesDescGlobal = montoBrutoUsd - totalDescuentosItemsUsd;
    const descGlobalMontoUsd = subtotalAntesDescGlobal * (descGlobalPorc / 100);

    const subtotalNetoUsd = subtotalAntesDescGlobal - descGlobalMontoUsd;
    const totalDescuentosTotalUsd = totalDescuentosItemsUsd + descGlobalMontoUsd;

    const totalGeneralUsd = subtotalNetoUsd + totalIvaUsd;
    const totalGeneralBs = totalGeneralUsd * tasaUsdActual;

    $("#resumenTotalUnidades").text(totalUnidades.toLocaleString());
    $("#resumenMontoBrutoUsd").text(`$ ${montoBrutoUsd.toFixed(2)}`);
    $("#resumenDescuentosUsd").text(`-$ ${totalDescuentosTotalUsd.toFixed(2)}`);
    $("#resumenSubtotalUsd").text(`$ ${subtotalNetoUsd.toFixed(2)}`);
    $("#resumenIvaUsd").text(`$ ${totalIvaUsd.toFixed(2)}`);
    $("#resumenTotalGeneralUsd").text(`$ ${totalGeneralUsd.toFixed(2)}`);
    $("#resumenTotalGeneralBs").text(`Bs. ${totalGeneralBs.toFixed(2)}`);
};
window.recalcularTotalesGenerales = recalcularTotalesGenerales;

/**
 * Abrir modal de nueva recepción
 */
const crear = function () {
    $("#formularioRecepcion")[0].reset();
    $("#formularioRecepcion .is-invalid").removeClass("is-invalid");
    $("#formularioRecepcion .invalid-feedback").remove();

    const hoy = new Date().toISOString().split("T")[0];
    $("#fecha_emision").val(hoy);
    $("#fecha_recepcion").val(hoy);
    $("#condicion_pago").val("contado");
    toggleCondicionPago();

    seleccionarMonedaDocumento("USD");
    limpiarTablaProductos();
    volverAFase1();

    $("#badgeTasaCambio").text(tasaUsdActual.toFixed(4));
    $("#tasa_cambio").val(tasaUsdActual.toFixed(4));
    $("#badgeCodigoRecepcion").html(`<i class="fas fa-hashtag me-1"></i>${catalogosSistema.proximo_codigo || "REC-00001"}`);

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRecepcion"));
    modal.show();

    setTimeout(() => {
        $("#numero_documento").focus();
    }, 400);
};
window.crear = crear;

/**
 * Guardar y procesar la recepción
 */
const guardarRecepcion = async function () {
    const $form = $("#formularioRecepcion");
    const $btn = $("#btnGuardarRecepcion");
    const textoOriginal = $btn.html();

    if (listaProductosCargados.length === 0) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Recepción vacía",
                text: "Debes agregar al menos un producto al comprobante.",
            });
        }
    }

    const numDoc = ($("#numero_documento").val() || "").trim();
    if (!numDoc) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Falta número de factura",
                text: "Por favor ingresa el número de factura o comprobante del proveedor.",
            });
        }
    }

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Procesando...');
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();

    try {
        const res = await $.ajax({
            url: urlGuardar,
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
        });

        if (res.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById("modalRecepcion"));
            if (modal) modal.hide();

            cargarCatalogos();
            if (window.LaravelDataTables && window.LaravelDataTables["datatable_recepciones"]) {
                window.LaravelDataTables["datatable_recepciones"].ajax.reload(null, false);
            } else if ($.fn.DataTable.isDataTable("#datatable_recepciones")) {
                $("#datatable_recepciones").DataTable().ajax.reload(null, false);
            }

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Recepción Procesada",
                    text: res.message || "La recepción ha sido registrada exitosamente.",
                });
            }
        }
    } catch (xhr) {
        if (xhr.status === 422 && xhr.responseJSON) {
            const errors = xhr.responseJSON.errors;
            if (errors) {
                Object.keys(errors).forEach((campo) => {
                    const input = $(`#${campo}`);
                    if (input.length) {
                        input.addClass("is-invalid");
                        input.after(`<div class="invalid-feedback d-block">${errors[campo][0]}</div>`);
                    }
                });
            }
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error de Validación",
                    text: xhr.responseJSON.message || "Por favor verifica los campos obligatorios.",
                });
            }
        } else {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON?.message || "No se pudo procesar la recepción.",
                });
            }
        }
    } finally {
        $btn.prop("disabled", false).html(textoOriginal);
    }
};
window.guardarRecepcion = guardarRecepcion;

/**
 * Ficha Técnica 360° / Comprobante de Recepción
 */
const verFicha = async function (id) {
    try {
        const res = await consultarRegistro(urlDetalles, id);
        if (!res || !res.data) return;
        const recep = res.data;

        let filasProductosHtml = "";
        if (Array.isArray(recep.detalles) && recep.detalles.length > 0) {
            recep.detalles.forEach((d) => {
                const prod = d.producto || {};
                const alm = d.almacen || recep.almacen || {};
                const subtotalUsd = parseFloat(d.subtotal_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const subtotalBs = parseFloat(d.subtotal_bs || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const costoUsd = parseFloat(d.costo_unitario_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const costoBs = parseFloat(d.costo_unitario_bs || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const detalUsd = parseFloat(d.precio_detal_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const detalBs = parseFloat(d.precio_detal_bs || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const mayoristaUsd = parseFloat(d.precio_mayorista_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const mayoristaBs = parseFloat(d.precio_mayorista_bs || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                filasProductosHtml += `
                    <tr>
                        <td>
                            <strong class="text-dark d-block">${prod.nombre || 'N/A'}</strong>
                            <span class="badge rounded-pill bg-white text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem; border-color: #cbd5e1 !important;">#${prod.codigo_interno || ''}</span>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.72rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; white-space: nowrap;">
                                <i class="fas fa-warehouse me-1"></i>${alm.nombre || 'N/A'}
                            </span>
                        </td>
                        <td class="text-center font-monospace fw-bold" style="white-space: nowrap;">${parseFloat(d.cantidad || 0)} ${prod.unidad_medida || 'und'}</td>
                        <td class="text-end font-monospace" style="white-space: nowrap;">
                            <span class="d-block fw-bold text-dark">$ ${costoUsd}</span>
                            <small class="d-block font-monospace fw-semibold" style="font-size: 0.72rem; color: #0284c7;">Bs. ${costoBs}</small>
                        </td>
                        <td class="text-center font-monospace">${parseFloat(d.descuento_porcentaje || 0)}%</td>
                        <td class="text-center font-monospace" style="white-space: nowrap;">${d.aplica_iva ? `IVA ${parseFloat(d.iva_porcentaje || 0)}%` : 'Exento'}</td>
                        <td class="text-end font-monospace" style="white-space: nowrap;">
                            <span class="d-block fw-bold text-dark">$ ${detalUsd}</span>
                            <small class="d-block font-monospace fw-semibold" style="font-size: 0.72rem; color: #0284c7;">Bs. ${detalBs}</small>
                        </td>
                        <td class="text-end font-monospace" style="white-space: nowrap;">
                            <span class="d-block fw-bold text-dark">$ ${mayoristaUsd}</span>
                            <small class="d-block font-monospace fw-semibold" style="font-size: 0.72rem; color: #0284c7;">Bs. ${mayoristaBs}</small>
                        </td>
                        <td class="text-end font-monospace" style="white-space: nowrap;">
                            <span class="d-block fw-bold text-dark" style="font-size: 0.90rem;">$ ${subtotalUsd}</span>
                            <small class="d-block font-monospace fw-semibold" style="font-size: 0.74rem; color: #0284c7;">Bs. ${subtotalBs}</small>
                        </td>
                    </tr>
                `;
            });
        }

        const fichaHtml = `
            <div class="row g-3">
                <!-- ENCABEZADO 360 -->
                <div class="col-12">
                    <div class="card card-executive border-0 shadow-sm p-4" style="border-radius: 16px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff;">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-executive shadow-sm rounded-3" style="width: 56px; height: 56px; min-width: 56px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.15); color: #ffffff; font-size: 1.5rem;">
                                    <i class="fas fa-truck-loading"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold mb-0 text-white font-monospace">${recep.codigo}</h4>
                                    <div class="d-flex flex-wrap gap-2 small text-white-50 mt-1">
                                        <span><i class="fas fa-file-invoice me-1"></i>Doc: <strong>${recep.numero_documento} (${(recep.tipo_documento || '').toUpperCase()})</strong></span>
                                        ${recep.numero_control ? `<span>•</span><span><i class="fas fa-barcode me-1"></i>Control: <strong>${recep.numero_control}</strong></span>` : ''}
                                        <span>•</span>
                                        <span><i class="fas fa-calendar-alt me-1"></i>Fecha: <strong>${formatearFecha(recep.fecha_recepcion)}</strong></span>
                                        ${recep.fecha_emision ? `<span>•</span><span><i class="fas fa-calendar-day me-1"></i>Emisión: <strong>${formatearFecha(recep.fecha_emision)}</strong></span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="d-block small text-white-50">Total General</span>
                                <h3 class="fw-bold mb-0 text-warning font-monospace">$ ${parseFloat(recep.total_usd || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</h3>
                                <small class="text-white-50 font-monospace">Bs. ${parseFloat(recep.total_bs || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PROVEEDOR Y CONDICIONES -->
                <div class="col-md-6">
                    <div class="card border rounded-4 p-3 h-100 shadow-sm bg-white">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-truck text-primary me-2"></i> Información del Proveedor</h6>
                        <div class="small">
                            <div><strong>Nombre:</strong> ${recep.proveedor ? recep.proveedor.nombre : 'N/A'}</div>
                            <div><strong>RIF:</strong> <span class="font-monospace text-secondary">${recep.proveedor ? recep.proveedor.rif : 'N/A'}</span></div>
                            <div><strong>Contacto:</strong> ${recep.proveedor?.telefono || 'N/A'} | ${recep.proveedor?.correo || 'N/A'}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border rounded-4 p-3 h-100 shadow-sm bg-white">
                        <h6 class="fw-bold text-dark mb-2"><i class="fas fa-warehouse text-primary me-2"></i> Liquidación & Almacén</h6>
                        <div class="small">
                            <div><strong>Almacén General:</strong> ${recep.almacen ? recep.almacen.nombre : 'N/A'}</div>
                            <div><strong>Condición:</strong> <span class="badge ${recep.condicion_pago === 'credito' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success'} rounded-pill">${recep.condicion_pago.toUpperCase()}</span></div>
                            ${recep.condicion_pago === 'credito' && recep.fecha_vencimiento ? `<div><strong>Vencimiento:</strong> <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill"><i class="fas fa-clock me-1"></i>${formatearFecha(recep.fecha_vencimiento)} (${recep.dias_credito || 0} días)</span></div>` : ''}
                            <div><strong>Tasa Aplicada:</strong> <span class="font-monospace fw-bold text-primary">${parseFloat(recep.tasa_cambio || 1).toFixed(4)} Bs/$</span></div>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTOS -->
                <div class="col-12">
                    <div class="card border rounded-4 p-3 shadow-sm bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-boxes-stacked text-primary me-2"></i> Artículos Recibidos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light font-monospace small">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Almacén Destino</th>
                                        <th class="text-center">Cant.</th>
                                        <th class="text-end">Costo ($ / Bs.)</th>
                                        <th class="text-center">Desc</th>
                                        <th class="text-center">IVA</th>
                                        <th class="text-end">Detal ($ / Bs.)</th>
                                        <th class="text-end">Mayorista ($ / Bs.)</th>
                                        <th class="text-end">Subtotal ($ / Bs.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${filasProductosHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $("#contenidoFichaRecepcion").html(fichaHtml);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalFichaRecepcion"));
        
        // Configurar botón de impresión del modal para abrir la hoja física de impresión
        $("#modalFichaRecepcion").find(".btn-outline-primary").attr("onclick", `imprimirRecepcion(${id})`);
        
        modal.show();
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo cargar la información de la recepción.",
            });
        }
    }
};
window.verFicha = verFicha;

/**
 * Abrir comprobante físico / hoja de impresión en ventana dedicada
 */
const imprimirRecepcion = function (id) {
    if (!id) return;
    const printUrl = `${urlBase}/${id}/imprimir`;
    window.open(printUrl, "_blank");
};
window.imprimirRecepcion = imprimirRecepcion;

/**
 * Anular Recepción
 */
const anularRecepcion = function (id, codigo) {
    if (!window.Swal) return;

    Swal.fire({
        title: `¿Anular Recepción ${codigo}?`,
        text: "Se revertirán las cantidades del inventario y se generará el movimiento de anulación en Kardex.",
        icon: "warning",
        input: "text",
        inputPlaceholder: "Motivo de la anulación (opcional)",
        showCancelButton: true,
        confirmButtonColor: "#dc2626",
        cancelButtonColor: "#64748b",
        confirmButtonText: '<i class="fas fa-ban me-1"></i> Sí, anular',
        cancelButtonText: "Cancelar",
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await $.ajax({
                    url: `${urlBase}/${id}/anular`,
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr("content"),
                        motivo: result.value || "Anulación administrativa",
                    },
                    dataType: "json",
                });

                if (res.success) {
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: "success",
                            title: "Recepción Anulada",
                            text: res.message || "La recepción ha sido anulada exitosamente.",
                        });
                    }

                    if (window.LaravelDataTables && window.LaravelDataTables["datatable_recepciones"]) {
                        window.LaravelDataTables["datatable_recepciones"].ajax.reload(null, false);
                    } else if ($.fn.DataTable.isDataTable("#datatable_recepciones")) {
                        $("#datatable_recepciones").DataTable().ajax.reload(null, false);
                    }
                }
            } catch (xhr) {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "error",
                        title: "Error",
                        text: xhr.responseJSON?.message || "No se pudo anular la recepción.",
                    });
                }
            }
        }
    });
};
window.anularRecepcion = anularRecepcion;

/**
 * Modal rápido para crear proveedor sin salir del modal de recepción
 */
const abrirModalRapidoProveedor = function () {
    $("#formularioRapidoProveedor")[0].reset();
    $("#formularioRapidoProveedor .is-invalid").removeClass("is-invalid");
    $("#formularioRapidoProveedor .invalid-feedback").remove();

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoProveedor"));
    modal.show();
};
window.abrirModalRapidoProveedor = abrirModalRapidoProveedor;

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

            if (!Array.isArray(catalogosSistema.proveedores)) {
                catalogosSistema.proveedores = [];
            }
            catalogosSistema.proveedores.push(nuevoProv);
            poblarSelectProveedores();
            $("#proveedor_id").val(nuevoProv.id);

            const modal = bootstrap.Modal.getInstance(document.getElementById("modalRapidoProveedor"));
            if (modal) modal.hide();

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Proveedor Registrado",
                    text: `"${nuevoProv.nombre}" fue registrado y seleccionado exitosamente.`,
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
 * Toggle Impuesto IVA en Modal Rápido de Producto
 */
const toggleIvaRapidoProducto = function () {
    const aplicaIva = $("#rapido_prod_aplica_iva").is(":checked");
    if (aplicaIva) {
        $("#contenedorIvaPorcentajeRapido").slideDown(150);
    } else {
        $("#contenedorIvaPorcentajeRapido").slideUp(150);
    }
};
window.toggleIvaRapidoProducto = toggleIvaRapidoProducto;

/**
 * Modal rápido para crear producto sin salir de la recepción
 */
const abrirModalRapidoProducto = function (query = "") {
    $("#formularioRapidoProducto")[0].reset();
    $("#formularioRapidoProducto .is-invalid").removeClass("is-invalid");
    $("#formularioRapidoProducto .invalid-feedback").remove();

    // Asegurar que las categorías estén pobladas
    poblarSelectCategoriasRapido();

    // Reset de switch IVA
    $("#rapido_prod_aplica_iva").prop("checked", true);
    $("#rapido_prod_iva_porcentaje").val("16.00");
    $("#contenedorIvaPorcentajeRapido").show();

    // Pre-llenar si vino del buscador
    query = (query || "").trim();
    if (query) {
        const esCodigoBarra = /^\d{6,}$/.test(query);
        if (esCodigoBarra) {
            $("#rapido_prod_barcode").val(query);
            $("#rapido_prod_codigo").val("PROD-" + Math.floor(1000 + Math.random() * 9000));
            $("#rapido_prod_nombre").val("");
            setTimeout(() => $("#rapido_prod_nombre").focus(), 300);
        } else {
            $("#rapido_prod_nombre").val(query);
            const prefijo = query.replace(/[^a-zA-Z0-9]/g, "").substring(0, 4).toUpperCase();
            $("#rapido_prod_codigo").val((prefijo ? prefijo : "PROD") + "-" + Math.floor(1000 + Math.random() * 9000));
            setTimeout(() => $("#rapido_prod_categoria_id").focus(), 300);
        }
    } else {
        $("#rapido_prod_codigo").val("PROD-" + Math.floor(1000 + Math.random() * 9000));
        setTimeout(() => $("#rapido_prod_nombre").focus(), 300);
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoProducto"));
    modal.show();
};
window.abrirModalRapidoProducto = abrirModalRapidoProducto;

$("#formularioRapidoProducto").on("submit", async function (e) {
    e.preventDefault();

    const $form = $(this);
    const $btn = $("#modalRapidoProductoBtnGuardar");
    const textoOriginal = $btn.html();

    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Creando producto...');
    $form.find(".is-invalid").removeClass("is-invalid");
    $form.find(".invalid-feedback").remove();

    const aplicaIva = $("#rapido_prod_aplica_iva").is(":checked");
    const ivaPorcentaje = aplicaIva ? parseFloat($("#rapido_prod_iva_porcentaje").val()) || 16.00 : 0;
    const barcodeVal = ($("#rapido_prod_barcode").val() || "").trim();

    const payload = {
        _token: $('meta[name="csrf-token"]').attr("content"),
        tipo: "producto",
        codigo_interno: ($("#rapido_prod_codigo").val() || "").trim(),
        nombre: ($("#rapido_prod_nombre").val() || "").trim(),
        categoria_id: $("#rapido_prod_categoria_id").val(),
        unidad_medida: $("#rapido_prod_unidad").val(),
        aplica_iva: aplicaIva ? 1 : 0,
        iva_porcentaje: ivaPorcentaje,
        aplica_igtf: 0,
        igtf_porcentaje: 0,
        stock_minimo: 0,
        stock_maximo: null,
    };

    if (barcodeVal) {
        payload.codigos_barra = [
            {
                codigo: barcodeVal,
                descripcion: "Código Principal",
            },
        ];
    }

    const proveedorActualId = $("#proveedor_id").val();
    if (proveedorActualId) {
        payload.proveedores = [
            {
                proveedor_id: proveedorActualId,
                codigo_proveedor: ($("#rapido_prod_codigo").val() || "").trim(),
            },
        ];
    }

    try {
        const res = await $.ajax({
            url: urlGuardarProducto,
            type: "POST",
            data: payload,
            dataType: "json",
        });

        if (res.success && res.data) {
            const prodData = res.data;

            const nuevoProd = {
                id: prodData.id,
                codigo_interno: prodData.codigo_interno,
                nombre: prodData.nombre,
                unidad_medida: prodData.unidad_medida || "UND",
                categoria_nombre: prodData.categoria?.nombre || "General",
                precio_costo_usd: parseFloat(prodData.precio_costo_usd || 0),
                precio_costo_bs: parseFloat(prodData.precio_costo_bs || 0),
                precio_detal_usd: parseFloat(prodData.precio_detal_usd || 0),
                precio_detal_bs: parseFloat(prodData.precio_detal_bs || 0),
                precio_mayorista_usd: parseFloat(prodData.precio_mayorista_usd || 0),
                precio_mayorista_bs: parseFloat(prodData.precio_mayorista_bs || 0),
                aplica_iva: prodData.aplica_iva,
                iva_porcentaje: parseFloat(prodData.iva_porcentaje || 16),
                codigos_barra: Array.isArray(prodData.codigos_barra)
                    ? prodData.codigos_barra.map((cb) => cb.codigo_barra || cb.codigo || cb)
                    : (barcodeVal ? [barcodeVal] : []),
                stock_almacenes: prodData.stock_almacenes || [],
            };

            if (!Array.isArray(catalogosSistema.productos)) {
                catalogosSistema.productos = [];
            }
            catalogosSistema.productos.push(nuevoProd);

            const modal = bootstrap.Modal.getInstance(document.getElementById("modalRapidoProducto"));
            if (modal) modal.hide();

            // Limpiar buscador y resultados
            $("#inputEscaneoProducto").val("");
            $("#resultadosBusqueda").hide();

            // Auto-seleccionar para carga directa
            seleccionarProductoParaCarga(nuevoProd);

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Producto Creado Exitosamente",
                    text: `"${nuevoProd.nombre}" ha sido registrado y cargado al formulario de recepción.`,
                });
            }
        }
    } catch (xhr) {
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            const errors = xhr.responseJSON.errors;
            Object.keys(errors).forEach((campo) => {
                let input = $(`#rapido_prod_${campo}`);
                if (campo === "codigo_barra_principal" || campo.startsWith("codigos_barra")) {
                    input = $("#rapido_prod_barcode");
                }
                if (input.length) {
                    input.addClass("is-invalid");
                    input.after(`<div class="invalid-feedback d-block">${errors[campo][0]}</div>`);
                }
            });
        } else {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "error",
                    title: "Error al Registrar Producto",
                    text: xhr.responseJSON?.message || "No se pudo registrar el producto en el sistema.",
                });
            }
        }
    } finally {
        $btn.prop("disabled", false).html(textoOriginal);
    }
});

/**
 * =========================================================================
 * NAVEGACIÓN FLUIDA POR TECLADO (ENTER PARA AVANZAR, ESC PARA CANCELAR)
 * =========================================================================
 */
const configurarNavegacionTeclado = function () {
    const enfocarCampo = function (selector) {
        const $el = $(selector);
        if ($el.length && $el.is(":visible")) {
            $el.focus();
            if ($el.is("input:not([type=checkbox]):not([type=radio]), textarea")) {
                $el.select();
            }
            return true;
        }
        return false;
    };

    // ==========================================
    // 1. FASE 1: DATOS PRINCIPALES DEL DOCUMENTO
    // ==========================================
    $("#numero_documento").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#numero_control");
        }
    });

    $("#numero_control").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#tipo_documento");
        }
    });

    $("#tipo_documento").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#proveedor_id");
        }
    });

    $("#proveedor_id").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#almacen_id");
        }
    });

    $("#almacen_id").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#fecha_emision");
        }
    });

    $("#fecha_emision").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#fecha_recepcion");
        }
    });

    $("#fecha_recepcion").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#condicion_pago");
        }
    });

    $("#condicion_pago").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            if ($(this).val() === "credito") {
                enfocarCampo("#dias_credito");
            } else {
                enfocarCampo("#monto_bruto_input");
            }
        }
    });

    $("#dias_credito").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#monto_bruto_input");
        }
    });

    $("#monto_bruto_input").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#descuento_global_porcentaje");
        }
    });

    $("#descuento_global_porcentaje").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            avanzarAFase2();
        }
    });

    // ==========================================
    // 2. FASE 2: FORMULARIO DE CARGA DE RENGLÓN
    // ==========================================
    $("#form_renglon_almacen_id").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_bultos");
        }
    });

    $("#form_renglon_bultos").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_unid_bulto");
        }
    });

    $("#form_renglon_unid_bulto").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_costo_bulto");
        }
    });

    $("#form_renglon_costo_bulto").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_descuento");
        }
    });

    $("#form_renglon_descuento").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_iva");
        }
    });

    $("#form_renglon_iva").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_margen_detal");
        }
    });

    $("#form_renglon_margen_detal").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_precio_detal");
        }
    });

    $("#form_renglon_precio_detal").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_margen_mayorista");
        }
    });

    $("#form_renglon_margen_mayorista").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#form_renglon_precio_mayorista");
        }
    });

    // En el último campo del renglón, al presionar Enter se agrega el producto y regresa el foco al buscador
    $("#form_renglon_precio_mayorista").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            agregarOActualizarRenglon();
        }
    });

    // Atajo rápido: Ctrl+Enter o Alt+A en cualquier campo del panel de renglón agrega inmediatamente
    $("#panelFormularioRenglon").on("keydown", "input, select", function (e) {
        if ((e.ctrlKey && e.which === 13) || (e.altKey && e.which === 65)) {
            e.preventDefault();
            agregarOActualizarRenglon();
        }
    });

    // Tecla Escape para cancelar edición rápida de renglón
    $(document).on("keydown", function (e) {
        if (e.which === 27) { // ESC
            if ($("#panelFormularioRenglon").is(":visible") && !$(".modal.show").length) {
                cancelarEdicionRenglon();
                $("#inputEscaneoProducto").focus();
            }
        }
    });

    // ==========================================
    // 3. MODAL RÁPIDO DE PROVEEDOR
    // ==========================================
    $("#rapido_prov_rif").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_nombre");
        }
    });

    $("#rapido_prov_nombre").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_razon_social");
        }
    });

    $("#rapido_prov_razon_social").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_contacto");
        }
    });

    $("#rapido_prov_contacto").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_telefono");
        }
    });

    $("#rapido_prov_telefono").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_correo");
        }
    });

    $("#rapido_prov_correo").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prov_direccion");
        }
    });

    $("#rapido_prov_direccion").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $("#formularioRapidoProveedor").submit();
        }
    });

    // ==========================================
    // 4. MODAL RÁPIDO DE PRODUCTO
    // ==========================================
    $("#rapido_prod_codigo").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prod_barcode");
        }
    });

    $("#rapido_prod_barcode").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prod_nombre");
        }
    });

    $("#rapido_prod_nombre").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prod_categoria_id");
        }
    });

    $("#rapido_prod_categoria_id").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            enfocarCampo("#rapido_prod_unidad");
        }
    });

    $("#rapido_prod_unidad").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            if ($("#rapido_prod_aplica_iva").is(":checked")) {
                enfocarCampo("#rapido_prod_iva_porcentaje");
            } else {
                $("#formularioRapidoProducto").submit();
            }
        }
    });

    $("#rapido_prod_iva_porcentaje").on("keydown", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $("#formularioRapidoProducto").submit();
        }
    });
};

/**
 * Aplicar restricciones y selecciones automáticas en campos
 */
const aplicarRestriccionesInput = function () {
    $(document).on("focus", "#panelFormularioRenglon input, #seccionFase1 input, #modalRapidoProducto input, #modalRapidoProveedor input", function () {
        $(this).select();
    });
};


