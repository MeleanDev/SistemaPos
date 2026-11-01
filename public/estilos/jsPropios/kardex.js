const urlBaseKardex = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlListaKardex = `${urlBaseKardex}/lista`;
const urlKpisKardex = `${urlBaseKardex}/kpis`;
const urlCatalogosKardex = `${urlBaseKardex}/catalogos`;
const urlStockProductoKardex = `${urlBaseKardex}/stock-producto/`;
const urlAjusteKardex = `${urlBaseKardex}/ajuste`;
const urlTrasladoKardex = `${urlBaseKardex}/traslado`;

let datatableKardex = null;
let catalogoAlmacenes = [];
let catalogoProductos = [];
let catalogoMotivos = {
    entrada: [],
    salida: [],
    traslado: []
};
let tasaUsdActual = 1.0000;

let itemsTraslado = [];
let itemsAjuste = [];
let tipoAjusteActual = "entrada";

const formatearMonto = function (monto, decimales = 2) {
    const num = parseFloat(monto) || 0;
    return num.toLocaleString("es-VE", {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
};

const normalizarNumero = function (val) {
    if (typeof val === "number") return val;
    if (!val) return 0;
    const str = String(val).trim().replace(/\s/g, "");
    if (str.includes(",") && str.includes(".")) {
        return parseFloat(str.replace(/\./g, "").replace(",", ".")) || 0;
    }
    if (str.includes(",")) {
        return parseFloat(str.replace(",", ".")) || 0;
    }
    return parseFloat(str) || 0;
};

const formatearFechaHora = function (fechaStr) {
    if (!fechaStr) return "--";
    try {
        const d = new Date(fechaStr);
        if (isNaN(d.getTime())) return fechaStr;
        const dia = String(d.getDate()).padStart(2, "0");
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const anio = d.getFullYear();
        let horas = d.getHours();
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12;
        horas = horas ? horas : 12;
        const minutos = String(d.getMinutes()).padStart(2, "0");
        return `${dia}/${mes}/${anio} ${horas}:${minutos} ${ampm}`;
    } catch (e) {
        return fechaStr;
    }
};

const resetearFormularioTraslado = function () {
    itemsTraslado = [];
    const form = document.getElementById("formularioTraslado");
    if (form) {
        form.reset();
    }
    const hoy = new Date().toISOString().split("T")[0];
    $("#traslado_fecha").val(hoy);
    $("#traslado_motivo").val("");
    $("#traslado_observaciones").val("");
    $("#traslado_item_cantidad").val("");
    establecerValorSelect2("#traslado_producto_select", "");
    $("#badgeStockOrigenPreview").text("Stock Disponible en Origen: --");
    renderizarTablaTraslado();
};

const resetearFormularioAjuste = function () {
    itemsAjuste = [];
    const form = document.getElementById("formularioAjuste");
    if (form) {
        form.reset();
    }
    const hoy = new Date().toISOString().split("T")[0];
    $("#ajuste_fecha").val(hoy);
    $("#ajuste_tipo_ajuste").val(tipoAjusteActual);
    $("#ajuste_motivo").val("");
    $("#ajuste_observaciones").val("");
    $("#ajuste_item_cantidad").val("");
    $("#ajuste_item_costo").val("");
    establecerValorSelect2("#ajuste_producto_select", "");
    $("#badgeStockAjustePreview").text("Stock Actual en Almacén: --");
    renderizarTablaAjuste();
};

const inicializarTablaKardex = function () {
    datatableKardex = crearDataTable({
        selector: "#datatable_kardex",
        url: urlListaKardex,
        dataExtra: function (d) {
            d.almacen_id = $("#filtro_almacen_id").val();
            d.tipo_movimiento = $("#filtro_tipo_movimiento").val();
            d.fecha_desde = $("#filtro_fecha_desde").val();
            d.fecha_hasta = $("#filtro_fecha_hasta").val();
        },
        columns: [
            {
                data: "created_at",
                name: "created_at",
                className: "font-monospace small align-middle text-nowrap",
                render: function (data) {
                    return formatearFechaHora(data);
                }
            },
            {
                data: "producto",
                name: "producto.nombre",
                className: "align-middle",
                render: function (data, type, row) {
                    if (!data) return '<span class="text-muted">--</span>';
                    const sku = data.codigo_interno ? `<span class="badge rounded-pill bg-white text-secondary border px-1.5 py-0 me-1">#${data.codigo_interno}</span>` : "";
                    const und = data.unidad_medida ? `<span class="text-muted small">(${data.unidad_medida})</span>` : "";
                    const prodNombreEscapado = data.nombre ? data.nombre.replace(/'/g, "\\'") : "";
                    return `
                        <div>
                            <strong class="text-dark d-block text-truncate" style="max-width: 240px;" title="${data.nombre}">${data.nombre}</strong>
                            <div class="d-flex align-items-center gap-1 font-monospace" style="font-size: 0.72rem;">
                                ${sku} ${und}
                                <a href="javascript:void(0)" class="text-primary ms-1" onclick="verStockProductoAlmacenes(${row.producto_id}, '${prodNombreEscapado}')" title="Ver stock en todos los almacenes">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    `;
                }
            },
            {
                data: "almacen",
                name: "almacen.nombre",
                className: "align-middle",
                render: function (data) {
                    if (!data) return '<span class="text-muted">--</span>';
                    return `
                        <span class="badge rounded-pill bg-white text-dark border px-2.5 py-1 fw-semibold font-monospace" style="font-size: 0.75rem;">
                            <i class="fas fa-warehouse text-secondary me-1"></i>${data.nombre}
                        </span>
                    `;
                }
            },
            {
                data: "tipo_movimiento",
                name: "tipo_movimiento",
                className: "text-center align-middle text-nowrap",
                render: function (data) {
                    switch (data) {
                        case "ajuste_positivo":
                            return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-plus-circle me-1"></i>Ajuste (+)</span>';
                        case "ajuste_negativo":
                            return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-minus-circle me-1"></i>Ajuste (-)</span>';
                        case "traslado_entrada":
                            return '<span class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-arrow-down-long me-1"></i>Traslado (Entrada)</span>';
                        case "traslado_salida":
                            return '<span class="badge rounded-pill px-2.5 py-1 fw-bold font-monospace" style="background-color: #faf5ff; color: #7e22ce; border: 1px solid #d8b4fe;"><i class="fas fa-arrow-up-long me-1"></i>Traslado (Salida)</span>';
                        case "entrada_recepcion":
                            return '<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-truck-loading me-1"></i>Recepción Compra</span>';
                        case "salida_venta":
                            return '<span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-cash-register me-1"></i>Venta POS</span>';
                        case "anulacion_recepcion":
                            return '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-ban me-1"></i>Anul. Recepción</span>';
                        case "anulacion_venta":
                            return '<span class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1 fw-bold font-monospace"><i class="fas fa-undo me-1"></i>Anul. Venta</span>';
                        default:
                            return `<span class="badge rounded-pill bg-secondary-subtle text-secondary border px-2.5 py-1 font-monospace">${data}</span>`;
                    }
                }
            },
            {
                data: "cantidad",
                name: "cantidad",
                className: "text-center font-monospace align-middle fw-bold",
                render: function (data, type, row) {
                    const cant = parseFloat(data) || 0;
                    const esEntrada = ["entrada_recepcion", "ajuste_positivo", "traslado_entrada", "anulacion_venta"].includes(row.tipo_movimiento);
                    const colorClase = esEntrada ? "text-success" : "text-danger";
                    const signo = esEntrada ? "+" : "-";
                    return `<span class="${colorClase}" style="font-size: 0.90rem;">${signo}${formatearMonto(cant, 2)}</span>`;
                }
            },
            {
                data: null,
                name: "stock_nuevo",
                className: "text-center font-monospace align-middle",
                render: function (data, type, row) {
                    const ant = parseFloat(row.stock_anterior) || 0;
                    const nue = parseFloat(row.stock_nuevo) || 0;
                    return `
                        <div class="d-flex align-items-center justify-content-center gap-1.5" style="font-size: 0.78rem;">
                            <span class="text-muted">${formatearMonto(ant, 2)}</span>
                            <i class="fas fa-arrow-right text-secondary small opacity-50"></i>
                            <strong class="text-dark px-1.5 py-0.5 rounded bg-white border">${formatearMonto(nue, 2)}</strong>
                        </div>
                    `;
                }
            },
            {
                data: "costo_unitario_usd",
                name: "costo_unitario_usd",
                className: "text-end font-monospace align-middle",
                render: function (data, type, row) {
                    const costoUsd = parseFloat(data) || 0;
                    const costoBs = parseFloat(row.costo_unitario_bs) || (costoUsd * tasaUsdActual);
                    return `
                        <div>
                            <strong class="text-dark d-block">$ ${formatearMonto(costoUsd, 2)}</strong>
                            <small class="text-muted d-block" style="font-size: 0.70rem;">Bs. ${formatearMonto(costoBs, 2)}</small>
                        </div>
                    `;
                }
            },
            {
                data: "motivo",
                name: "motivo",
                className: "align-middle small",
                render: function (data) {
                    return `<span class="text-secondary text-truncate d-block" style="max-width: 220px;" title="${data || ""}">${data || '<span class="text-muted fst-italic">Sin motivo especificado</span>'}</span>`;
                }
            },
            {
                data: "usuario",
                name: "usuario.name",
                className: "align-middle small font-monospace",
                render: function (data) {
                    return data ? `<span class="text-muted"><i class="fas fa-user-circle me-1"></i>${data.name}</span>` : '<span class="text-muted">Sistema</span>';
                }
            },
            {
                data: null,
                name: "acciones",
                orderable: false,
                searchable: false,
                className: "text-center align-middle",
                render: function (data, type, row) {
                    if (row.documento_tipo === "movimiento_inventario" && row.documento_id) {
                        return `
                            <a href="/kardex/comprobante/${row.documento_id}" target="_blank" class="btn btn-outline-primary btn-sm rounded-circle p-1 shadow-xs" title="Ver / Imprimir Comprobante" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-print" style="font-size: 0.75rem;"></i>
                            </a>
                        `;
                    }
                    return '<span class="text-muted opacity-50">--</span>';
                }
            }
        ]
    });
};

const aplicarFiltrosKardex = function () {
    recargarDataTable("#datatable_kardex", false);
};

const limpiarFiltrosKardex = function () {
    $("#filtro_almacen_id").val("");
    $("#filtro_tipo_movimiento").val("");
    $("#filtro_fecha_desde").val("");
    $("#filtro_fecha_hasta").val("");
    aplicarFiltrosKardex();
};

const recargarTablaKardex = function () {
    recargarDataTable("#datatable_kardex", false);
    cargarKpisKardex();
};

const cargarKpisKardex = async function () {
    try {
        const res = await peticionAjax({
            url: urlKpisKardex,
            method: "GET"
        });
        if (res && res.success && res.data) {
            $("#kpiTotalMovimientos").text(res.data.total_movimientos_hoy);
            $("#kpiEntradasHoy").text(formatearMonto(res.data.entradas_hoy_unidades, 2));
            $("#kpiSalidasHoy").text(formatearMonto(res.data.salidas_hoy_unidades, 2));
            $("#kpiTrasladosHoy").text(res.data.traslados_hoy_count);
        }
    } catch (e) {
        console.error(e);
    }
};

const poblarFiltrosAlmacenes = function () {
    const $select = $("#filtro_almacen_id");
    $select.empty().append('<option value="">Todos los Almacenes</option>');
    catalogoAlmacenes.forEach(a => {
        $select.append(`<option value="${a.id}">${a.nombre} (${a.codigo})</option>`);
    });
};

const poblarSelectsAlmacenesTraslado = function () {
    const $orig = $("#traslado_almacen_origen_id");
    const $dest = $("#traslado_almacen_destino_id");

    $orig.empty().append('<option value="">Seleccione almacén origen...</option>');
    $dest.empty().append('<option value="">Seleccione almacén destino...</option>');

    catalogoAlmacenes.forEach(a => {
        $orig.append(`<option value="${a.id}">${a.nombre} [${a.codigo}]</option>`);
        $dest.append(`<option value="${a.id}">${a.nombre} [${a.codigo}]</option>`);
    });

    if (catalogoAlmacenes.length > 0) {
        $orig.val(catalogoAlmacenes[0].id);
        if (catalogoAlmacenes.length > 1) {
            $dest.val(catalogoAlmacenes[1].id);
        }
    }
};

const poblarSelectAlmacenAjuste = function () {
    const $select = $("#ajuste_almacen_id");
    $select.empty().append('<option value="">Seleccione almacén...</option>');
    catalogoAlmacenes.forEach(a => {
        $select.append(`<option value="${a.id}">${a.nombre} [${a.codigo}]</option>`);
    });
    if (catalogoAlmacenes.length > 0) {
        $select.val(catalogoAlmacenes[0].id);
    }
};

const poblarSelectProductosTraslado = function () {
    const $select = $("#traslado_producto_select");
    $select.empty().append('<option value="">Buscar o seleccionar producto...</option>');
    catalogoProductos.forEach(p => {
        const sku = p.codigo_interno ? `[#${p.codigo_interno}] ` : "";
        $select.append(`<option value="${p.id}">${sku}${p.nombre} (${p.unidad_medida})</option>`);
    });
    crearSelect2({
        selector: "#traslado_producto_select",
        modalSelector: "#modalTraslado",
        placeholder: "Buscar producto por nombre o SKU...",
    });
};

const poblarSelectProductosAjuste = function () {
    const $select = $("#ajuste_producto_select");
    $select.empty().append('<option value="">Buscar o seleccionar producto...</option>');
    catalogoProductos.forEach(p => {
        const sku = p.codigo_interno ? `[#${p.codigo_interno}] ` : "";
        $select.append(`<option value="${p.id}">${sku}${p.nombre} (${p.unidad_medida})</option>`);
    });
    crearSelect2({
        selector: "#ajuste_producto_select",
        modalSelector: "#modalAjuste",
        placeholder: "Buscar producto por nombre o SKU...",
    });
};

const cargarCatalogosKardex = async function () {
    try {
        const res = await peticionAjax({
            url: urlCatalogosKardex,
            method: "GET"
        });

        if (res && res.success && res.data) {
            catalogoAlmacenes = res.data.almacenes || [];
            catalogoProductos = res.data.productos || [];
            tasaUsdActual = parseFloat(res.data.tasa_usd) || 1.0000;
            catalogoMotivos.entrada = res.data.motivos_ajuste_entrada || [];
            catalogoMotivos.salida = res.data.motivos_ajuste_salida || [];
            catalogoMotivos.traslado = res.data.motivos_traslado || [];

            poblarFiltrosAlmacenes();
            poblarSelectsAlmacenesTraslado();
            poblarSelectAlmacenAjuste();
            poblarSelectProductosTraslado();
            poblarSelectProductosAjuste();
        }
    } catch (e) {
        console.error(e);
    }
};

const actualizarStockAlmacenOrigenTraslado = function () {
    const almId = parseInt($("#traslado_almacen_origen_id").val()) || 0;
    const prodId = parseInt($("#traslado_producto_select").val()) || 0;

    if (!almId || !prodId) {
        $("#badgeStockOrigenPreview").text("Stock Disponible en Origen: --");
        return;
    }

    const prod = catalogoProductos.find(p => p.id === prodId);
    if (!prod) return;

    const stock = prod.stock_por_almacen && prod.stock_por_almacen[almId] !== undefined
        ? parseFloat(prod.stock_por_almacen[almId])
        : 0;

    $("#badgeStockOrigenPreview").text(`Stock en Origen: ${formatearMonto(stock, 2)} ${prod.unidad_medida}`);
};

const seleccionarProductoTraslado = function () {
    actualizarStockAlmacenOrigenTraslado();
    $("#traslado_item_cantidad").focus().select();
};

const seleccionarMotivoTraslado = function (val) {
    if (val) {
        $("#traslado_motivo").val(val);
    }
};

const renderizarTablaTraslado = function () {
    const $tbody = $("#tbodyDetallesTraslado");
    $tbody.empty();

    if (itemsTraslado.length === 0) {
        $tbody.append(`
            <tr id="filaSinItemsTraslado">
                <td colspan="6" class="text-center py-4 text-muted small">
                    <i class="fas fa-dolly fa-2x mb-2 d-block opacity-50"></i>
                    Aún no has agregado ningún producto para trasladar.
                </td>
            </tr>
        `);
        $("#contadorItemsTraslado").text("0 Productos");
        return;
    }

    $("#contadorItemsTraslado").text(`${itemsTraslado.length} Producto${itemsTraslado.length === 1 ? "" : "s"}`);

    itemsTraslado.forEach((item, idx) => {
        const resultante = Math.max(0, item.stock_origen - item.cantidad);
        $tbody.append(`
            <tr>
                <td class="text-center font-monospace fw-bold">${idx + 1}</td>
                <td>
                    <strong class="text-dark">${item.nombre}</strong>
                    <small class="text-muted d-block font-monospace">SKU: ${item.codigo_interno || "--"} | ${item.unidad_medida}</small>
                </td>
                <td class="text-center font-monospace text-muted">${formatearMonto(item.stock_origen, 2)}</td>
                <td class="text-center font-monospace text-primary fw-bold">${formatearMonto(item.cantidad, 2)}</td>
                <td class="text-center font-monospace fw-bold text-dark">${formatearMonto(resultante, 2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 shadow-xs" onclick="eliminarRenglonTraslado(${idx})" title="Eliminar ítem" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                    </button>
                </td>
            </tr>
        `);
    });
};

const abrirModalTraslado = function () {
    resetearFormularioTraslado();
    poblarSelectsAlmacenesTraslado();

    const $motivoSelect = $("#traslado_motivo_select");
    $motivoSelect.empty().append('<option value="">Seleccione motivo frecuente...</option>');
    catalogoMotivos.traslado.forEach(m => {
        $motivoSelect.append(`<option value="${m}">${m}</option>`);
    });

    actualizarStockAlmacenOrigenTraslado();
    const modalEl = document.getElementById("modalTraslado");
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
};

const agregarRenglonTraslado = function () {
    const almOrigId = parseInt($("#traslado_almacen_origen_id").val()) || 0;
    const almDestId = parseInt($("#traslado_almacen_destino_id").val()) || 0;
    const prodId = parseInt($("#traslado_producto_select").val()) || 0;
    const cantidad = normalizarNumero($("#traslado_item_cantidad").val());

    if (!almOrigId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén origen." });
    if (!almDestId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén destino." });
    if (almOrigId === almDestId) return notificacion.fire({ icon: "warning", title: "El almacén de destino debe ser diferente al de origen." });
    if (!prodId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar un producto." });
    if (cantidad <= 0) return notificacion.fire({ icon: "warning", title: "La cantidad a trasladar debe ser mayor a cero." });

    const prod = catalogoProductos.find(p => p.id === prodId);
    if (!prod) return;

    const stockOrigen = prod.stock_por_almacen && prod.stock_por_almacen[almOrigId] !== undefined
        ? parseFloat(prod.stock_por_almacen[almOrigId])
        : 0;

    if (cantidad > stockOrigen) {
        return notificacion.fire({
            icon: "warning",
            title: "Stock insuficiente",
            text: `Solo hay ${formatearMonto(stockOrigen, 2)} ${prod.unidad_medida} disponibles en el almacén de origen.`
        });
    }

    const indexExistente = itemsTraslado.findIndex(i => i.producto_id === prodId);
    if (indexExistente !== -1) {
        itemsTraslado[indexExistente].cantidad = cantidad;
    } else {
        itemsTraslado.push({
            producto_id: prodId,
            nombre: prod.nombre,
            codigo_interno: prod.codigo_interno,
            unidad_medida: prod.unidad_medida,
            stock_origen: stockOrigen,
            cantidad: cantidad,
        });
    }

    establecerValorSelect2("#traslado_producto_select", "");
    $("#traslado_item_cantidad").val("");
    $("#badgeStockOrigenPreview").text("Stock Disponible en Origen: --");

    renderizarTablaTraslado();
};

const eliminarRenglonTraslado = function (index) {
    itemsTraslado.splice(index, 1);
    renderizarTablaTraslado();
};

const procesarTraslado = async function () {
    const almOrigId = parseInt($("#traslado_almacen_origen_id").val()) || 0;
    const almDestId = parseInt($("#traslado_almacen_destino_id").val()) || 0;
    const motivo = $("#traslado_motivo").val().trim();
    const fecha = $("#traslado_fecha").val();
    const observaciones = $("#traslado_observaciones").val().trim();

    if (!almOrigId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén de origen." });
    if (!almDestId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén de destino." });
    if (almOrigId === almDestId) return notificacion.fire({ icon: "warning", title: "El almacén de destino debe ser diferente al de origen." });
    if (!motivo) return notificacion.fire({ icon: "warning", title: "El motivo del traslado es obligatorio." });
    if (itemsTraslado.length === 0) return notificacion.fire({ icon: "warning", title: "Debes agregar al menos un producto al traslado." });

    const payload = {
        almacen_origen_id: almOrigId,
        almacen_destino_id: almDestId,
        motivo: motivo,
        fecha: fecha,
        observaciones: observaciones,
        detalles: itemsTraslado.map(i => ({
            producto_id: i.producto_id,
            cantidad: i.cantidad
        }))
    };

    try {
        const res = await peticionAjax({
            url: urlTrasladoKardex,
            method: "POST",
            data: payload
        });

        if (res && res.success) {
            const modalEl = document.getElementById("modalTraslado");
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            notificacion.fire({
                icon: "success",
                title: "Traslado completado",
                text: res.message || "El traslado se procesó correctamente.",
                timer: 2000,
                showConfirmButton: false
            });
            recargarTablaKardex();
            cargarCatalogosKardex();
        }
    } catch (e) {
        console.error(e);
    }
};

const actualizarStockAlmacenAjuste = function () {
    const almId = parseInt($("#ajuste_almacen_id").val()) || 0;
    const prodId = parseInt($("#ajuste_producto_select").val()) || 0;

    if (!almId || !prodId) {
        $("#badgeStockAjustePreview").text("Stock Actual en Almacén: --");
        return;
    }

    const prod = catalogoProductos.find(p => p.id === prodId);
    if (!prod) return;

    const stock = prod.stock_por_almacen && prod.stock_por_almacen[almId] !== undefined
        ? parseFloat(prod.stock_por_almacen[almId])
        : 0;

    $("#badgeStockAjustePreview").text(`Stock en Almacén: ${formatearMonto(stock, 2)} ${prod.unidad_medida}`);
};

const seleccionarProductoAjuste = function () {
    actualizarStockAlmacenAjuste();
    const prodId = parseInt($("#ajuste_producto_select").val()) || 0;
    const prod = catalogoProductos.find(p => p.id === prodId);
    if (prod) {
        $("#ajuste_item_costo").val(prod.precio_costo_usd.toFixed(4));
    }
    $("#ajuste_item_cantidad").focus().select();
};

const seleccionarMotivoAjuste = function (val) {
    if (val) {
        $("#ajuste_motivo").val(val);
    }
};

const renderizarTablaAjuste = function () {
    const $tbody = $("#tbodyDetallesAjuste");
    $tbody.empty();

    if (itemsAjuste.length === 0) {
        $tbody.append(`
            <tr id="filaSinItemsAjuste">
                <td colspan="7" class="text-center py-4 text-muted small">
                    <i class="fas fa-dolly fa-2x mb-2 d-block opacity-50"></i>
                    Aún no has agregado ningún producto al ajuste.
                </td>
            </tr>
        `);
        $("#contadorItemsAjuste").text("0 Productos");
        return;
    }

    $("#contadorItemsAjuste").text(`${itemsAjuste.length} Producto${itemsAjuste.length === 1 ? "" : "s"}`);

    itemsAjuste.forEach((item, idx) => {
        const esEntrada = tipoAjusteActual === "entrada";
        const proyectado = esEntrada ? (item.stock_actual + item.cantidad) : Math.max(0, item.stock_actual - item.cantidad);
        const signo = esEntrada ? "+" : "-";
        const claseColor = esEntrada ? "text-success" : "text-danger";

        $tbody.append(`
            <tr>
                <td class="text-center font-monospace fw-bold">${idx + 1}</td>
                <td>
                    <strong class="text-dark">${item.nombre}</strong>
                    <small class="text-muted d-block font-monospace">SKU: ${item.codigo_interno || "--"} | ${item.unidad_medida}</small>
                </td>
                <td class="text-center font-monospace text-muted">${formatearMonto(item.stock_actual, 2)}</td>
                <td class="text-center font-monospace fw-bold ${claseColor}">${signo}${formatearMonto(item.cantidad, 2)}</td>
                <td class="text-center font-monospace fw-bold text-dark">${formatearMonto(proyectado, 2)}</td>
                <td class="text-end font-monospace">$ ${formatearMonto(item.costo_unitario_usd, 2)}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1 shadow-xs" onclick="eliminarRenglonAjuste(${idx})" title="Eliminar ítem" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                    </button>
                </td>
            </tr>
        `);
    });
};

const abrirModalAjuste = function (tipo = "entrada") {
    tipoAjusteActual = tipo;
    resetearFormularioAjuste();
    poblarSelectAlmacenAjuste();

    if (tipo === "entrada") {
        $("#modalAjusteLabel").text("Ajuste de Inventario (Entrada / Positivo)");
        $("#subtituloModalAjuste").text("Incrementa existencias por conteo sobrante, regularización o inicialización");
        $("#headerModalAjuste").css("background-color", "#065f46");
        $("#iconoHeaderAjuste").html('<i class="fas fa-plus-circle text-success fs-4"></i>');
        $("#btnProcesarAjusteTexto").text("Guardar y Aplicar Entrada (+)");
        $("#contenedorCostoAjuste").show();

        const $motivoSelect = $("#ajuste_motivo_select");
        $motivoSelect.empty().append('<option value="">Seleccione motivo predeterminado...</option>');
        catalogoMotivos.entrada.forEach(m => {
            $motivoSelect.append(`<option value="${m}">${m}</option>`);
        });
    } else {
        $("#modalAjusteLabel").text("Ajuste de Inventario (Salida / Negativo)");
        $("#subtituloModalAjuste").text("Disminuye existencias por faltante, merma, rotura o vencimiento");
        $("#headerModalAjuste").css("background-color", "#991b1b");
        $("#iconoHeaderAjuste").html('<i class="fas fa-minus-circle text-danger fs-4"></i>');
        $("#btnProcesarAjusteTexto").text("Guardar y Aplicar Salida (-)");
        $("#contenedorCostoAjuste").hide();

        const $motivoSelect = $("#ajuste_motivo_select");
        $motivoSelect.empty().append('<option value="">Seleccione motivo predeterminado...</option>');
        catalogoMotivos.salida.forEach(m => {
            $motivoSelect.append(`<option value="${m}">${m}</option>`);
        });
    }

    actualizarStockAlmacenAjuste();
    const modalEl = document.getElementById("modalAjuste");
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
};

const agregarRenglonAjuste = function () {
    const almId = parseInt($("#ajuste_almacen_id").val()) || 0;
    const prodId = parseInt($("#ajuste_producto_select").val()) || 0;
    const cantidad = normalizarNumero($("#ajuste_item_cantidad").val());
    const costoUsd = normalizarNumero($("#ajuste_item_costo").val());

    if (!almId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén." });
    if (!prodId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar un producto." });
    if (cantidad <= 0) return notificacion.fire({ icon: "warning", title: "La cantidad a ajustar debe ser mayor a cero." });

    const prod = catalogoProductos.find(p => p.id === prodId);
    if (!prod) return;

    const stockActual = prod.stock_por_almacen && prod.stock_por_almacen[almId] !== undefined
        ? parseFloat(prod.stock_por_almacen[almId])
        : 0;

    if (tipoAjusteActual === "salida" && cantidad > stockActual) {
        return notificacion.fire({
            icon: "warning",
            title: "Stock insuficiente",
            text: `Solo hay ${formatearMonto(stockActual, 2)} ${prod.unidad_medida} disponibles en este almacén.`
        });
    }

    const indexExistente = itemsAjuste.findIndex(i => i.producto_id === prodId);
    if (indexExistente !== -1) {
        itemsAjuste[indexExistente].cantidad = cantidad;
        itemsAjuste[indexExistente].costo_unitario_usd = costoUsd;
    } else {
        itemsAjuste.push({
            producto_id: prodId,
            nombre: prod.nombre,
            codigo_interno: prod.codigo_interno,
            unidad_medida: prod.unidad_medida,
            stock_actual: stockActual,
            cantidad: cantidad,
            costo_unitario_usd: costoUsd,
        });
    }

    establecerValorSelect2("#ajuste_producto_select", "");
    $("#ajuste_item_cantidad").val("");
    $("#ajuste_item_costo").val("");
    $("#badgeStockAjustePreview").text("Stock Actual en Almacén: --");

    renderizarTablaAjuste();
};

const eliminarRenglonAjuste = function (index) {
    itemsAjuste.splice(index, 1);
    renderizarTablaAjuste();
};

const procesarAjuste = async function () {
    const almId = parseInt($("#ajuste_almacen_id").val()) || 0;
    const motivo = $("#ajuste_motivo").val().trim();
    const fecha = $("#ajuste_fecha").val();
    const observaciones = $("#ajuste_observaciones").val().trim();

    if (!almId) return notificacion.fire({ icon: "warning", title: "Debes seleccionar el almacén." });
    if (!motivo) return notificacion.fire({ icon: "warning", title: "El motivo o justificación del ajuste es obligatorio." });
    if (itemsAjuste.length === 0) return notificacion.fire({ icon: "warning", title: "Debes agregar al menos un producto al ajuste." });

    const payload = {
        almacen_id: almId,
        tipo_ajuste: tipoAjusteActual,
        motivo: motivo,
        fecha: fecha,
        observaciones: observaciones,
        detalles: itemsAjuste.map(i => ({
            producto_id: i.producto_id,
            cantidad: i.cantidad,
            costo_unitario_usd: i.costo_unitario_usd
        }))
    };

    try {
        const res = await peticionAjax({
            url: urlAjusteKardex,
            method: "POST",
            data: payload
        });

        if (res && res.success) {
            const modalEl = document.getElementById("modalAjuste");
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            notificacion.fire({
                icon: "success",
                title: "Ajuste procesado",
                text: res.message || "El ajuste de inventario se aplicó correctamente.",
                timer: 2000,
                showConfirmButton: false
            });
            recargarTablaKardex();
            cargarCatalogosKardex();
        }
    } catch (e) {
        console.error(e);
    }
};

const verStockProductoAlmacenes = async function (productoId, productoNombre) {
    $("#consultaStockProdNombre").text(productoNombre);
    $("#cuerpoModalConsultaStock").html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted small mt-2">Consultando existencias multialmacén...</p>
        </div>
    `);
    const modalEl = document.getElementById("modalConsultaStock");
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    try {
        const res = await peticionAjax({
            url: urlStockProductoKardex + productoId,
            method: "GET"
        });

        if (res && res.success && res.data) {
            const data = res.data;
            let rowsHtml = "";
            (data.almacenes || []).forEach((a, idx) => {
                const stockVal = parseFloat(a.cantidad_actual) || 0;
                rowsHtml += `
                    <tr>
                        <td class="text-center font-monospace">${idx + 1}</td>
                        <td>
                            <strong class="text-dark">${a.almacen_nombre}</strong>
                            <small class="text-muted d-block font-monospace">Código: ${a.almacen_codigo}</small>
                        </td>
                        <td class="text-end font-monospace fw-bold">
                            <span class="badge rounded-pill px-3 py-1 ${stockVal > 0 ? "bg-success text-white" : "bg-secondary-subtle text-muted border"}" style="font-size: 0.85rem;">
                                ${formatearMonto(stockVal, 2)} ${data.unidad_medida || "UND"}
                            </span>
                        </td>
                    </tr>
                `;
            });

            $("#cuerpoModalConsultaStock").html(`
                <div class="p-3 bg-white rounded-4 border shadow-xs mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">Existencia Total en la Empresa:</span>
                        <h4 class="fw-bold mb-0 text-dark font-monospace">${formatearMonto(data.stock_total, 2)} ${data.unidad_medida || "UND"}</h4>
                    </div>
                    <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold bg-primary text-white">
                        <i class="fas fa-boxes-stacked me-1"></i>${(data.almacenes || []).length} Almacenes
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="border-bottom font-monospace small bg-white">
                            <tr>
                                <th style="width: 35px;" class="text-center">#</th>
                                <th>Almacén</th>
                                <th class="text-end">Existencia Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            `);
        }
    } catch (e) {
        $("#cuerpoModalConsultaStock").html(`
            <div class="alert alert-danger mb-0 small">
                <i class="fas fa-exclamation-triangle me-1"></i> No se pudo cargar la información de stock.
            </div>
        `);
    }
};

$(document).ready(function () {
    crearSelect2({
        selector: "#traslado_producto_select",
        modalSelector: "#modalTraslado",
        placeholder: "Buscar producto por nombre o SKU...",
    });

    crearSelect2({
        selector: "#ajuste_producto_select",
        modalSelector: "#modalAjuste",
        placeholder: "Buscar producto por nombre o SKU...",
    });

    $("#traslado_producto_select").on("change", function () {
        seleccionarProductoTraslado();
    });

    $("#ajuste_producto_select").on("change", function () {
        seleccionarProductoAjuste();
    });

    $("#modalTraslado").on("hidden.bs.modal", function () {
        resetearFormularioTraslado();
    });

    $("#modalAjuste").on("hidden.bs.modal", function () {
        resetearFormularioAjuste();
    });

    inicializarTablaKardex();
    cargarCatalogosKardex();
    cargarKpisKardex();

    const hoy = new Date().toISOString().split("T")[0];
    $("#traslado_fecha").val(hoy);
    $("#ajuste_fecha").val(hoy);
});
