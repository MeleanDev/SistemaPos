/**
 * ============================================================================
 * SISTEMA POS ENTERPRISE - CONTROLADOR JAVASCRIPT DE PUNTO DE VENTA & FACTURACIÓN
 * ============================================================================
 */

let posCatalogos = {
    cliente_defecto: null,
    almacenes: [],
    metodos_pago: [],
    tasa_usd: 1.0000,
    productos: [],
    proximo_codigo: "VEN-00001",
};

let posClienteActual = null;
let posTipoVentaActual = "detal"; // 'detal' o 'mayor'
let posAlmacenActualId = 1;
let posTasaDia = 1.0000;
let posCarrito = [];
let posPagos = [];
let posIndiceRenglonSeleccionado = null;

$(document).ready(function () {
    cargarDatosInicialesPos();
    configurarAtajosTecladoPos();
    configurarBuscadorProductosPos();
    configurarBuscadorClientesPos();
});

/**
 * 1. Cargar Datos y Catálogos Iniciales del POS
 */
const cargarDatosInicialesPos = async function () {
    try {
        const res = await $.ajax({
            url: urlPosDatos,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            posCatalogos = res.data;
            posTasaDia = parseFloat(res.data.tasa_usd) || 1.0000;
            $("#posBadgeTasaDia").text(posTasaDia.toFixed(4));
            $("#cobroModalTasa").text(posTasaDia.toFixed(4));

            // Poblar selector de almacenes
            const $selAlm = $("#posSelectAlmacen");
            $selAlm.empty();
            if (Array.isArray(res.data.almacenes) && res.data.almacenes.length > 0) {
                res.data.almacenes.forEach((a, idx) => {
                    $selAlm.append(`<option value="${a.id}" ${idx === 0 ? "selected" : ""}>${a.nombre}</option>`);
                });
                posAlmacenActualId = parseInt(res.data.almacenes[0].id);
            }

            // Establecer cliente por defecto
            if (res.data.cliente_defecto) {
                establecerClienteActual(res.data.cliente_defecto);
            }

            // Poblar selector de métodos de pago en el modal de cobro
            poblarSelectMetodosPago();
        }
    } catch (e) {
        console.error("Error al cargar datos del POS:", e);
    }
};

/**
 * Poblar opciones de métodos de pago
 */
const poblarSelectMetodosPago = function () {
    const $selMetodo = $("#cobroSelectMetodo");
    $selMetodo.empty();

    if (Array.isArray(posCatalogos.metodos_pago)) {
        posCatalogos.metodos_pago.forEach((m) => {
            const esBs = (m.nombre || "").toLowerCase().includes("pago móvil") || (m.nombre || "").toLowerCase().includes("punto") || (m.nombre || "").toLowerCase().includes("bolívar") || (m.nombre || "").toLowerCase().includes("transferencia");
            const monedaDefault = esBs ? "VES" : "USD";
            $selMetodo.append(`<option value="${m.id}" data-moneda="${monedaDefault}">${m.nombre}</option>`);
        });
    }
    actualizarMonedaPagoSeleccionada();
};

/**
 * Actualizar símbolo según el método de pago seleccionado
 */
const actualizarMonedaPagoSeleccionada = function () {
    const $opt = $("#cobroSelectMetodo option:selected");
    const moneda = $opt.data("moneda") || "USD";
    $("#cobroSimboloMonedaPago").text(moneda === "VES" ? "Bs." : "$");
};
window.actualizarMonedaPagoSeleccionada = actualizarMonedaPagoSeleccionada;

/**
 * 2. Manejo de Clientes
 */
const establecerClienteActual = function (cliente) {
    posClienteActual = cliente;
    const nombreCompleto = `${cliente.nombre} ${cliente.apellido || ""}`.trim();
    $("#posClienteNombre").text(nombreCompleto);
    $("#posClienteCedula").text(cliente.cedula || "--");
    $("#posClienteTelefono").text(cliente.telefono || "Sin teléfono");

    const tipo = cliente.tipo_cliente || "detal";
    $("#posClienteTipoBadge").text(tipo === "mayorista" ? "Mayorista" : "Detal")
        .removeClass("bg-secondary-subtle text-secondary bg-purple-subtle text-purple-emphasis")
        .addClass(tipo === "mayorista" ? "bg-purple-subtle text-purple-emphasis" : "bg-secondary-subtle text-secondary");

    // Si el cliente es mayorista y estamos en detal, sugerir o auto-cambiar
    if (tipo === "mayorista" && posTipoVentaActual !== "mayor") {
        $("#tipoVentaMayor").prop("checked", true);
        cambiarTipoVenta("mayor");
    }
};

const resetearClienteDefecto = function () {
    if (posCatalogos.cliente_defecto) {
        establecerClienteActual(posCatalogos.cliente_defecto);
        $("#posInputCliente").val("");
    }
};
window.resetearClienteDefecto = resetearClienteDefecto;

const configurarBuscadorClientesPos = function () {
    const $input = $("#posInputCliente");
    const $dropdown = $("#dropdownClientesPos");
    let debounceTimer = null;

    $input.on("input", function () {
        clearTimeout(debounceTimer);
        const term = $(this).val().trim();

        if (term.length < 2) {
            $dropdown.hide();
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await $.ajax({
                    url: urlPosBuscarClientes,
                    type: "GET",
                    data: { termino: term },
                    dataType: "json",
                });

                $dropdown.empty();
                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    res.data.forEach((c) => {
                        const itemHtml = `
                            <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center cliente-item-pos" data-json='${JSON.stringify(c).replace(/'/g, "&apos;")}'>
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 0.85rem;">${c.nombre} ${c.apellido || ''}</strong>
                                    <small class="text-muted font-monospace">${c.cedula} • ${c.telefono || 'Sin tel.'}</small>
                                </div>
                                <span class="badge bg-light text-secondary border rounded-pill font-monospace">${c.tipo_cliente}</span>
                            </a>
                        `;
                        $dropdown.append(itemHtml);
                    });

                    $dropdown.find(".cliente-item-pos").on("click", function () {
                        const cli = $(this).data("json");
                        establecerClienteActual(cli);
                        $input.val("");
                        $dropdown.hide();
                    });

                    $dropdown.show();
                } else {
                    $dropdown.append(`
                        <div class="list-group-item p-3 text-center bg-white">
                            <span class="text-muted small d-block mb-2">No se encontró cliente con "${term}".</span>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" onclick="abrirModalNuevoCliente('${term}')">
                                <i class="fas fa-plus me-1"></i> Registrar "${term}"
                            </button>
                        </div>
                    `);
                    $dropdown.show();
                }
            } catch (e) {
                console.error("Error en búsqueda de clientes:", e);
            }
        }, 150);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#posInputCliente, #dropdownClientesPos").length) {
            $dropdown.hide();
        }
    });
};

const abrirModalNuevoCliente = function (cedulaInicial = "") {
    $("#formRapidoClientePos")[0].reset();
    $("#rapidoClienteId").val("");
    if (cedulaInicial) {
        $("#rapido_cli_cedula").val(cedulaInicial);
    }
    $("#modalRapidoClientePosLabel").html('<i class="fas fa-user-plus text-warning me-1"></i> Registrar Nuevo Cliente');
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoClientePos")).show();
};
window.abrirModalNuevoCliente = abrirModalNuevoCliente;

const abrirModalEditarCliente = function () {
    if (!posClienteActual || posClienteActual.id === posCatalogos.cliente_defecto?.id) {
        return abrirModalNuevoCliente();
    }
    $("#formRapidoClientePos")[0].reset();
    $("#rapidoClienteId").val(posClienteActual.id);
    $("#rapido_cli_cedula").val(posClienteActual.cedula);
    $("#rapido_cli_nombre").val(posClienteActual.nombre);
    $("#rapido_cli_apellido").val(posClienteActual.apellido);
    $("#rapido_cli_telefono").val(posClienteActual.telefono);
    $("#rapido_cli_correo").val(posClienteActual.correo);
    $("#rapido_cli_direccion").val(posClienteActual.direccion);
    $("#rapido_cli_tipo").val(posClienteActual.tipo_cliente || "detal");

    $("#modalRapidoClientePosLabel").html('<i class="fas fa-user-edit text-primary me-1"></i> Modificar Datos del Cliente');
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoClientePos")).show();
};
window.abrirModalEditarCliente = abrirModalEditarCliente;

const guardarClienteRapidoPos = async function (e) {
    e.preventDefault();
    const $form = $("#formRapidoClientePos");

    try {
        const res = await $.ajax({
            url: urlPosGuardarCliente,
            type: "POST",
            data: $form.serialize(),
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success && res.data) {
            establecerClienteActual(res.data);
            bootstrap.Modal.getInstance(document.getElementById("modalRapidoClientePos")).hide();
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Cliente Guardado",
                    text: res.message,
                });
            }
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se pudo guardar el cliente.";
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: msg });
        }
    }
};
window.guardarClienteRapidoPos = guardarClienteRapidoPos;

/**
 * 3. Cambio de Almacén y Tipo de Venta (Detal vs Mayor)
 */
const cambiarTipoVenta = function (tipo) {
    posTipoVentaActual = tipo;

    // Recalcular precios de todos los productos en el carrito
    posCarrito.forEach((item) => {
        const precioUnitUsd = posTipoVentaActual === "mayor" ? item.precio_mayorista_usd : item.precio_detal_usd;
        item.precio_unitario_usd = precioUnitUsd;
        item.subtotal_usd = roundDecimals(item.cantidad * precioUnitUsd * (1 - item.descuento_porcentaje / 100), 2);
        item.subtotal_bs = roundDecimals(item.subtotal_usd * posTasaDia, 2);
    });

    renderizarCarritoPos();
    recalcularTotalesPos();
};
window.cambiarTipoVenta = cambiarTipoVenta;

const cambiarAlmacenActivo = function () {
    posAlmacenActualId = parseInt($("#posSelectAlmacen").val()) || 1;
};
window.cambiarAlmacenActivo = cambiarAlmacenActivo;

/**
 * 4. Buscador / Escáner de Productos
 */
const configurarBuscadorProductosPos = function () {
    const $input = $("#posInputBuscadorProducto");
    const $dropdown = $("#dropdownProductosPos");
    let debounceTimer = null;

    $input.on("keypress", function (e) {
        if (e.which === 13) {
            e.preventDefault();
            const query = $(this).val().trim().toLowerCase();
            if (!query) return;

            // 1. Buscar coincidencia exacta por código de barra o SKU
            const prodExacto = posCatalogos.productos.find((p) => {
                const skuMatch = (p.codigo_interno || "").toLowerCase() === query;
                const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase() === query);
                return skuMatch || barcodeMatch;
            });

            if (prodExacto) {
                agregarProductoAlCarrito(prodExacto);
                $(this).val("");
                $dropdown.hide();
                return;
            }

            // 2. Coincidencias por nombre
            const coincidencias = posCatalogos.productos.filter((p) =>
                p.nombre.toLowerCase().includes(query) || (p.codigo_interno || "").toLowerCase().includes(query)
            );

            if (coincidencias.length === 1) {
                agregarProductoAlCarrito(coincidencias[0]);
                $(this).val("");
                $dropdown.hide();
            } else if (coincidencias.length > 1) {
                renderizarDropdownProductos(coincidencias);
            } else {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "warning",
                        title: "No encontrado",
                        text: `No existe ningún producto con el código o nombre "${query}".`,
                    });
                }
            }
        }
    });

    $input.on("input", function () {
        clearTimeout(debounceTimer);
        const query = $(this).val().trim().toLowerCase();

        if (query.length < 2) {
            $dropdown.hide();
            return;
        }

        debounceTimer = setTimeout(() => {
            const coincidencias = posCatalogos.productos.filter((p) => {
                const nombreMatch = p.nombre.toLowerCase().includes(query);
                const skuMatch = (p.codigo_interno || "").toLowerCase().includes(query);
                const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase() === query);
                return nombreMatch || skuMatch || barcodeMatch;
            });

            renderizarDropdownProductos(coincidencias);
        }, 150);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#posInputBuscadorProducto, #dropdownProductosPos").length) {
            $dropdown.hide();
        }
    });
};

const renderizarDropdownProductos = function (productos) {
    const $dropdown = $("#dropdownProductosPos");
    $dropdown.empty();

    if (!productos || productos.length === 0) {
        $dropdown.hide();
        return;
    }

    productos.slice(0, 8).forEach((p) => {
        const precioDetalUsd = parseFloat(p.precio_detal_usd) || 0;
        const precioMayorUsd = parseFloat(p.precio_mayorista_usd) || 0;
        const precioDetalBs = (precioDetalUsd * posTasaDia).toFixed(2);
        const precioMayorBs = (precioMayorUsd * posTasaDia).toFixed(2);

        let stockAlmacenActual = 0;
        if (Array.isArray(p.stock_almacenes)) {
            const stk = p.stock_almacenes.find((s) => s.almacen_id === posAlmacenActualId);
            if (stk) stockAlmacenActual = stk.cantidad_actual;
        }

        const badgeIvaHtml = p.aplica_iva
            ? `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">IVA ${p.iva_porcentaje}%</span>`
            : `<span class="badge rounded-pill bg-light text-secondary border font-monospace px-1.5 py-0.5" style="font-size: 0.68rem;">Exento</span>`;

        const itemHtml = `
            <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2.5 d-flex align-items-center justify-content-between prod-item-pos" data-id="${p.id}" data-tipo="${p.tipo_item}">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px; font-size: 1.1rem;">
                        <i class="${p.tipo_item === 'servicio' ? 'fas fa-wrench' : 'fas fa-box'}"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            <strong class="text-dark font-monospace" style="font-size: 0.90rem;">${p.nombre}</strong>
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.70rem;">#${p.codigo_interno}</span>
                            ${badgeIvaHtml}
                        </div>
                        <div class="small font-monospace text-muted mt-0.5">
                            Stock: <strong class="${stockAlmacenActual <= 0 && p.tipo_item === 'producto' ? 'text-danger' : 'text-success'}">${stockAlmacenActual} ${p.unidad_medida || 'und'}</strong>
                            • Detal: <strong class="text-primary">$${precioDetalUsd.toFixed(2)}</strong> <small class="text-muted">(Bs. ${precioDetalBs})</small>
                            • Mayor: <strong style="color: #7e22ce;">$${precioMayorUsd.toFixed(2)}</strong> <small class="text-muted">(Bs. ${precioMayorBs})</small>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-bold shadow-xs">
                    <i class="fas fa-plus me-1"></i> Cargar
                </button>
            </a>
        `;
        $dropdown.append(itemHtml);
    });

    $dropdown.find(".prod-item-pos").on("click", function () {
        const id = $(this).data("id");
        const prod = posCatalogos.productos.find((p) => p.id === id);
        if (prod) {
            agregarProductoAlCarrito(prod);
            $("#posInputBuscadorProducto").val("").focus();
            $dropdown.hide();
        }
    });

    $dropdown.show();
};

/**
 * 5. Agregar / Modificar / Eliminar Productos en el Carrito
 */
const agregarProductoAlCarrito = function (prod, cantidad = 1) {
    const precioUnitUsd = posTipoVentaActual === "mayor" ? parseFloat(prod.precio_mayorista_usd || 0) : parseFloat(prod.precio_detal_usd || 0);

    // Verificar si ya existe en el carrito
    const idxExistente = posCarrito.findIndex((item) => item.producto_id === prod.id && item.tipo_item === prod.tipo_item);

    if (idxExistente !== -1) {
        posCarrito[idxExistente].cantidad += cantidad;
        posCarrito[idxExistente].subtotal_usd = roundDecimals(
            posCarrito[idxExistente].cantidad * posCarrito[idxExistente].precio_unitario_usd * (1 - posCarrito[idxExistente].descuento_porcentaje / 100),
            2
        );
        posCarrito[idxExistente].subtotal_bs = roundDecimals(posCarrito[idxExistente].subtotal_usd * posTasaDia, 2);
        posIndiceRenglonSeleccionado = idxExistente;
    } else {
        const subtotalUsd = roundDecimals(cantidad * precioUnitUsd, 2);
        const subtotalBs = roundDecimals(subtotalUsd * posTasaDia, 2);

        let stockAlm = 0;
        if (Array.isArray(prod.stock_almacenes)) {
            const stk = prod.stock_almacenes.find((s) => s.almacen_id === posAlmacenActualId);
            if (stk) stockAlm = stk.cantidad_actual;
        }

        posCarrito.push({
            producto_id: prod.id,
            tipo_item: prod.tipo_item || "producto",
            codigo: prod.codigo_interno || "--",
            nombre: prod.nombre,
            unidad: prod.unidad_medida || "UND",
            cantidad: cantidad,
            precio_detal_usd: parseFloat(prod.precio_detal_usd || 0),
            precio_mayorista_usd: parseFloat(prod.precio_mayorista_usd || 0),
            precio_unitario_usd: precioUnitUsd,
            aplica_iva: !!prod.aplica_iva,
            iva_porcentaje: parseFloat(prod.iva_porcentaje || 16),
            descuento_porcentaje: 0,
            subtotal_usd: subtotalUsd,
            subtotal_bs: subtotalBs,
            stock_disponible: stockAlm,
        });

        posIndiceRenglonSeleccionado = posCarrito.length - 1;
    }

    renderizarCarritoPos();
    recalcularTotalesPos();
};

const actualizarCantidadItem = function (idx, nuevaCantidad) {
    if (idx < 0 || idx >= posCarrito.length) return;
    const cant = parseFloat(nuevaCantidad) || 1;

    if (cant <= 0) {
        eliminarItemCarrito(idx);
        return;
    }

    posCarrito[idx].cantidad = cant;
    posCarrito[idx].subtotal_usd = roundDecimals(
        cant * posCarrito[idx].precio_unitario_usd * (1 - posCarrito[idx].descuento_porcentaje / 100),
        2
    );
    posCarrito[idx].subtotal_bs = roundDecimals(posCarrito[idx].subtotal_usd * posTasaDia, 2);

    renderizarCarritoPos();
    recalcularTotalesPos();
};
window.actualizarCantidadItem = actualizarCantidadItem;

const alterarCantidadItem = function (idx, delta) {
    if (idx < 0 || idx >= posCarrito.length) return;
    const actual = posCarrito[idx].cantidad;
    actualizarCantidadItem(idx, actual + delta);
};
window.alterarCantidadItem = alterarCantidadItem;

const eliminarItemCarrito = function (idx) {
    if (idx < 0 || idx >= posCarrito.length) return;
    posCarrito.splice(idx, 1);
    if (posIndiceRenglonSeleccionado === idx) {
        posIndiceRenglonSeleccionado = posCarrito.length > 0 ? posCarrito.length - 1 : null;
    }
    renderizarCarritoPos();
    recalcularTotalesPos();
};
window.eliminarItemCarrito = eliminarItemCarrito;

const limpiarPantallaPos = function () {
    if (posCarrito.length === 0) return;

    if (window.Swal) {
        Swal.fire({
            title: "¿Limpiar Carrito de Venta?",
            text: "Se descartarán todos los productos ingresados en la venta activa.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Sí, Limpiar',
            cancelButtonText: "Cancelar",
        }).then((res) => {
            if (res.isConfirmed) {
                posCarrito = [];
                posIndiceRenglonSeleccionado = null;
                renderizarCarritoPos();
                recalcularTotalesPos();
                $("#posInputBuscadorProducto").val("").focus();
            }
        });
    } else {
        posCarrito = [];
        posIndiceRenglonSeleccionado = null;
        renderizarCarritoPos();
        recalcularTotalesPos();
    }
};
window.limpiarPantallaPos = limpiarPantallaPos;

/**
 * 6. Renderizar Carrito y Totales
 */
const renderizarCarritoPos = function () {
    const $tbody = $("#contenedorFilasPos");
    $tbody.empty();

    if (posCarrito.length === 0) {
        $tbody.append(`
            <tr id="filaPosVacia">
                <td colspan="7" class="text-center py-5 text-muted">
                    <i class="fas fa-cash-register fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                    <h6 class="fw-bold text-dark mb-1">Carrito de Venta Vacío</h6>
                    <span class="small">Escanea un código de barras o busca un producto en el campo inferior para comenzar la venta.</span>
                </td>
            </tr>
        `);
        $("#posContadorItems").html('<i class="fas fa-shopping-basket text-primary me-1"></i> 0 Ítems (0 Unid.)');
        return;
    }

    let totalUnidades = 0;

    posCarrito.forEach((item, idx) => {
        totalUnidades += parseFloat(item.cantidad);
        const precioUnitBs = (item.precio_unitario_usd * posTasaDia).toFixed(2);
        const esSeleccionado = posIndiceRenglonSeleccionado === idx;

        const filaHtml = `
            <tr class="fila-pos-item ${esSeleccionado ? 'table-active border-primary' : ''}" data-idx="${idx}" onclick="seleccionarFilaPos(${idx})" style="cursor: pointer;">
                <!-- Código -->
                <td class="font-monospace">
                    <span class="badge bg-light text-secondary border px-2 py-1">${item.codigo}</span>
                </td>

                <!-- Producto / Descripción -->
                <td>
                    <strong class="text-dark d-block font-monospace" style="font-size: 0.90rem;">${item.nombre}</strong>
                    <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                        <span>${item.unidad}</span>
                        ${item.stock_disponible !== undefined ? ` • <span class="${item.stock_disponible <= 0 && item.tipo_item === 'producto' ? 'text-danger' : 'text-success'}">Disp: ${item.stock_disponible}</span>` : ''}
                    </div>
                </td>

                <!-- Cantidad -->
                <td class="text-center font-monospace">
                    <div class="input-group input-group-sm justify-content-center" style="max-width: 110px; margin: 0 auto;">
                        <button class="btn btn-outline-secondary px-2" type="button" onclick="event.stopPropagation(); alterarCantidadItem(${idx}, -1);">-</button>
                        <input type="number" step="any" min="0.001" class="form-control text-center font-monospace fw-bold px-1" value="${item.cantidad}" onchange="event.stopPropagation(); actualizarCantidadItem(${idx}, this.value);" onclick="event.stopPropagation(); this.select();">
                        <button class="btn btn-outline-secondary px-2" type="button" onclick="event.stopPropagation(); alterarCantidadItem(${idx}, 1);">+</button>
                    </div>
                </td>

                <!-- Precio Unitario -->
                <td class="text-end font-monospace">
                    <strong class="text-primary d-block" style="font-size: 0.92rem;">$ ${item.precio_unitario_usd.toFixed(2)}</strong>
                    <small class="badge rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                        Bs. ${precioUnitBs}
                    </small>
                </td>

                <!-- IVA -->
                <td class="text-center font-monospace small">
                    <span class="badge rounded-pill ${item.aplica_iva ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border'} px-2 py-1">
                        ${item.aplica_iva ? `IVA ${item.iva_porcentaje}%` : 'Exento'}
                    </span>
                </td>

                <!-- Total Renglón -->
                <td class="text-end font-monospace">
                    <strong class="text-dark d-block" style="font-size: 0.96rem;">$ ${item.subtotal_usd.toFixed(2)}</strong>
                    <small class="badge rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                        Bs. ${item.subtotal_bs.toFixed(2)}
                    </small>
                </td>

                <!-- Acciones -->
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-xs" onclick="event.stopPropagation(); eliminarItemCarrito(${idx});" title="Eliminar Producto" style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                    </button>
                </td>
            </tr>
        `;
        $tbody.append(filaHtml);
    });

    $("#posContadorItems").html(`<i class="fas fa-shopping-basket text-primary me-1"></i> ${posCarrito.length} Ítem${posCarrito.length === 1 ? '' : 's'} (${totalUnidades.toLocaleString()} Unid.)`);
};

const seleccionarFilaPos = function (idx) {
    posIndiceRenglonSeleccionado = idx;
    $(".fila-pos-item").removeClass("table-active border-primary");
    $(`.fila-pos-item[data-idx="${idx}"]`).addClass("table-active border-primary");
};
window.seleccionarFilaPos = seleccionarFilaPos;

const recalcularTotalesPos = function () {
    let subtotalNetoUsd = 0;
    let totalIvaUsd = 0;

    posCarrito.forEach((item) => {
        subtotalNetoUsd += item.subtotal_usd;
        if (item.aplica_iva && item.iva_porcentaje > 0) {
            totalIvaUsd += item.subtotal_usd * (item.iva_porcentaje / 100);
        }
    });

    subtotalNetoUsd = roundDecimals(subtotalNetoUsd, 2);
    totalIvaUsd = roundDecimals(totalIvaUsd, 2);
    const totalGeneralUsd = roundDecimals(subtotalNetoUsd + totalIvaUsd, 2);

    const subtotalNetoBs = roundDecimals(subtotalNetoUsd * posTasaDia, 2);
    const totalIvaBs = roundDecimals(totalIvaUsd * posTasaDia, 2);
    const totalGeneralBs = roundDecimals(totalGeneralUsd * posTasaDia, 2);

    $("#posTotalNetoUsd").text(`$ ${subtotalNetoUsd.toFixed(2)}`);
    $("#posTotalNetoBs").text(`Bs. ${subtotalNetoBs.toFixed(2)}`);

    $("#posTotalIvaUsd").text(`$ ${totalIvaUsd.toFixed(2)}`);
    $("#posTotalIvaBs").text(`Bs. ${totalIvaBs.toFixed(2)}`);

    $("#posTotalVentaUsd").text(`$ ${totalGeneralUsd.toFixed(2)}`);
    $("#posTotalVentaBs").text(`Bs. ${totalGeneralBs.toFixed(2)}`);
};

/**
 * 7. Modal de Cobro & Facturación Multimoneda
 */
const abrirModalCobro = function () {
    if (posCarrito.length === 0) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Carrito Vacío",
                text: "Debes agregar al menos un producto al carrito para facturar.",
            });
        }
        return;
    }

    let subtotalNetoUsd = 0;
    let totalIvaUsd = 0;

    posCarrito.forEach((item) => {
        subtotalNetoUsd += item.subtotal_usd;
        if (item.aplica_iva && item.iva_porcentaje > 0) {
            totalIvaUsd += item.subtotal_usd * (item.iva_porcentaje / 100);
        }
    });

    const totalGeneralUsd = roundDecimals(subtotalNetoUsd + totalIvaUsd, 2);
    const totalGeneralBs = roundDecimals(totalGeneralUsd * posTasaDia, 2);

    $("#cobroModalTotalUsd").text(`$ ${totalGeneralUsd.toFixed(2)}`);
    $("#cobroModalTotalBs").text(`Bs. ${totalGeneralBs.toFixed(2)}`);

    $("#cobroCondicionPago").val("contado");
    toggleCondicionPagoCobro();

    // Iniciar con la lista de pagos limpia o con pago sugerido exacto
    posPagos = [];
    renderizarListaPagosCobro();

    // Sugerir monto total en el input de pago
    const $primerMetodo = $("#cobroSelectMetodo option:first");
    const esBs = $primerMetodo.data("moneda") === "VES";
    $("#cobroInputMonto").val(esBs ? totalGeneralBs.toFixed(2) : totalGeneralUsd.toFixed(2));
    $("#cobroInputReferencia").val("");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCobroVenta")).show();

    setTimeout(() => {
        $("#cobroInputMonto").focus().select();
    }, 300);
};
window.abrirModalCobro = abrirModalCobro;

const toggleCondicionPagoCobro = function () {
    const cond = $("#cobroCondicionPago").val();
    if (cond === "credito") {
        $("#contenedorDiasCreditoPos").slideDown(150);
        calcularVencimientoCobro();
    } else {
        $("#contenedorDiasCreditoPos").slideUp(150);
    }
    actualizarBalancesCobro();
};
window.toggleCondicionPagoCobro = toggleCondicionPagoCobro;

const calcularVencimientoCobro = function () {
    const dias = parseInt($("#cobroDiasCredito").val()) || 15;
    const fecha = new Date();
    fecha.setDate(fecha.getDate() + dias);
    const fechaStr = fecha.toISOString().split("T")[0];
    $("#cobroFechaVenceBadge").text(`Vence: ${fechaStr}`);
};
window.calcularVencimientoCobro = calcularVencimientoCobro;

const agregarPagoALista = function () {
    const metodoId = parseInt($("#cobroSelectMetodo").val()) || 0;
    const metodoNombre = $("#cobroSelectMetodo option:selected").text();
    const moneda = $("#cobroSelectMetodo option:selected").data("moneda") || "USD";
    const monto = parseFloat($("#cobroInputMonto").val()) || 0;
    const referencia = $("#cobroInputReferencia").val().trim();

    if (monto <= 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Monto inválido", text: "Por favor ingresa un monto mayor a 0." });
        }
        $("#cobroInputMonto").focus().select();
        return;
    }

    const montoUsd = moneda === "VES" ? roundDecimals(monto / posTasaDia, 2) : monto;
    const montoBs = moneda === "VES" ? monto : roundDecimals(monto * posTasaDia, 2);

    posPagos.push({
        metodo_pago_id: metodoId,
        metodo_nombre: metodoNombre,
        moneda: moneda,
        tasa_cambio: posTasaDia,
        monto_origen: monto,
        monto_usd: montoUsd,
        monto_bs: montoBs,
        referencia: referencia,
    });

    renderizarListaPagosCobro();

    // Limpiar input y recalcular faltante sugerido
    const faltante = obtenerMontoFaltanteUsd();
    if (faltante > 0) {
        const esBs = $("#cobroSelectMetodo option:selected").data("moneda") === "VES";
        $("#cobroInputMonto").val(esBs ? (faltante * posTasaDia).toFixed(2) : faltante.toFixed(2)).focus().select();
    } else {
        $("#cobroInputMonto").val("");
    }
    $("#cobroInputReferencia").val("");
};
window.agregarPagoALista = agregarPagoALista;

const eliminarPagoDeLista = function (idx) {
    if (idx < 0 || idx >= posPagos.length) return;
    posPagos.splice(idx, 1);
    renderizarListaPagosCobro();
};
window.eliminarPagoDeLista = eliminarPagoDeLista;

const renderizarListaPagosCobro = function () {
    const $tbody = $("#contenedorFilasCobroPagos");
    $tbody.empty();

    if (posPagos.length === 0) {
        $tbody.append('<tr><td colspan="6" class="text-center text-muted py-3 small">No has ingresado ningún pago aún.</td></tr>');
        $("#cobroContadorPagos").text("0 Pagos");
        actualizarBalancesCobro();
        return;
    }

    $("#cobroContadorPagos").text(`${posPagos.length} Pago${posPagos.length === 1 ? '' : 's'}`);

    posPagos.forEach((p, idx) => {
        const filaHtml = `
            <tr>
                <td class="font-monospace fw-bold text-dark">${p.metodo_nombre}</td>
                <td class="font-monospace"><span class="badge ${p.moneda === 'VES' ? 'bg-info-subtle text-info-emphasis' : 'bg-success-subtle text-success'} rounded-pill">${p.moneda}</span></td>
                <td class="text-end font-monospace fw-semibold">${p.moneda === 'VES' ? 'Bs. ' : '$ '}${p.monto_origen.toFixed(2)}</td>
                <td class="text-end font-monospace text-success fw-bold">$ ${p.monto_usd.toFixed(2)}</td>
                <td class="font-monospace text-muted small">${p.referencia || '--'}</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-0" onclick="eliminarPagoDeLista(${idx})" style="width: 24px; height: 24px;">
                        <i class="fas fa-times" style="font-size: 0.70rem;"></i>
                    </button>
                </td>
            </tr>
        `;
        $tbody.append(filaHtml);
    });

    actualizarBalancesCobro();
};

const obtenerTotalVentaUsd = function () {
    let subtotalNetoUsd = 0;
    let totalIvaUsd = 0;
    posCarrito.forEach((item) => {
        subtotalNetoUsd += item.subtotal_usd;
        if (item.aplica_iva && item.iva_porcentaje > 0) {
            totalIvaUsd += item.subtotal_usd * (item.iva_porcentaje / 100);
        }
    });
    return roundDecimals(subtotalNetoUsd + totalIvaUsd, 2);
};

const obtenerMontoFaltanteUsd = function () {
    const total = obtenerTotalVentaUsd();
    const pagado = posPagos.reduce((acc, p) => acc + p.monto_usd, 0);
    return Math.max(0, roundDecimals(total - pagado, 2));
};

const actualizarBalancesCobro = function () {
    const total = obtenerTotalVentaUsd();
    const pagado = posPagos.reduce((acc, p) => acc + p.monto_usd, 0);
    const cond = $("#cobroCondicionPago").val();

    $("#cobroBalancePagadoUsd").text(`$ ${pagado.toFixed(2)} (Bs. ${(pagado * posTasaDia).toFixed(2)})`);

    if (pagado >= total) {
        const vueltoUsd = roundDecimals(pagado - total, 2);
        const vueltoBs = roundDecimals(vueltoUsd * posTasaDia, 2);
        $("#cobroBalanceFaltanteUsd").text("$ 0.00").removeClass("text-danger").addClass("text-muted");
        $("#cobroBalanceVueltoUsd").text(`$ ${vueltoUsd.toFixed(2)} (Bs. ${vueltoBs.toFixed(2)})`).removeClass("text-muted").addClass("text-success");
    } else {
        const faltanteUsd = roundDecimals(total - pagado, 2);
        const faltanteBs = roundDecimals(faltanteUsd * posTasaDia, 2);
        $("#cobroBalanceFaltanteUsd").text(`$ ${faltanteUsd.toFixed(2)} (Bs. ${faltanteBs.toFixed(2)})`).removeClass("text-muted").addClass("text-danger");
        $("#cobroBalanceVueltoUsd").text("$ 0.00").removeClass("text-success").addClass("text-muted");

        if (cond === "credito") {
            $("#cobroLabelFaltante").text("Saldo a Crédito (CXC):");
        } else {
            $("#cobroLabelFaltante").text("Resta por Pagar:");
        }
    }
};

/**
 * 8. Procesar Venta Final
 */
const procesarVentaFinal = async function () {
    const total = obtenerTotalVentaUsd();
    const pagado = posPagos.reduce((acc, p) => acc + p.monto_usd, 0);
    const cond = $("#cobroCondicionPago").val();

    if (cond === "contado" && pagado < total) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Pago incompleto",
                text: `El monto pagado ($${pagado.toFixed(2)}) es menor al total ($${total.toFixed(2)}). Para registrar saldo pendiente, selecciona condición 'Crédito'.`,
            });
        }
    }

    if (cond === "credito" && (posClienteActual.cedula === "V-00000000" || posClienteActual.cedula === "J-00000000")) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Cliente no válido para crédito",
                text: "No se puede otorgar crédito al cliente 'Consumidor Final'. Por favor registra o selecciona un cliente identificado.",
            });
        }
    }

    const $btn = $("#btnConfirmarVentaFinal");
    const textoOriginal = $btn.html();
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Facturando...');

    const payload = {
        cliente_id: posClienteActual.id,
        almacen_id: posAlmacenActualId,
        tipo_venta: posTipoVentaActual,
        tasa_cambio: posTasaDia,
        condicion_pago: cond,
        dias_credito: parseInt($("#cobroDiasCredito").val()) || 15,
        items: posCarrito.map((it) => ({
            producto_id: it.producto_id,
            tipo_item: it.tipo_item,
            cantidad: it.cantidad,
            precio_unitario_usd: it.precio_unitario_usd,
            descuento_porcentaje: it.descuento_porcentaje,
            almacen_id: posAlmacenActualId,
        })),
        pagos: posPagos.map((p) => ({
            metodo_pago_id: p.metodo_pago_id,
            moneda: p.moneda,
            tasa_cambio: p.tasa_cambio,
            monto: p.monto_origen,
            referencia: p.referencia,
        })),
    };

    try {
        const res = await $.ajax({
            url: urlPosGuardarVenta,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success && res.data) {
            bootstrap.Modal.getInstance(document.getElementById("modalCobroVenta")).hide();

            // Abrir ventana de impresión térmica
            const winPrint = window.open(`${urlPosImprimir}/${res.data.id}`, "_blank", "width=400,height=600");

            if (window.Swal) {
                Swal.fire({
                    icon: "success",
                    title: "¡Venta Completada!",
                    text: `Comprobante #${res.data.codigo} generado exitosamente.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
            }

            // Resetear POS para la siguiente venta
            posCarrito = [];
            posPagos = [];
            posIndiceRenglonSeleccionado = null;
            resetearClienteDefecto();
            renderizarCarritoPos();
            recalcularTotalesPos();
            cargarDatosInicialesPos(); // Recargar stock en memoria
            $("#posInputBuscadorProducto").val("").focus();
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "Ocurrió un error al procesar la venta.";
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error al Facturar", text: msg });
        }
    } finally {
        $btn.prop("disabled", false).html(textoOriginal);
    }
};
window.procesarVentaFinal = procesarVentaFinal;

/**
 * 9. Cuentas en Espera (Pausar / Recuperar Pedidos)
 */
const abrirModalCuentasEspera = async function () {
    $("#inputNotaEspera").val(posClienteActual ? `Cliente ${posClienteActual.nombre}` : "");
    await cargarListaCuentasEspera();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCuentasEspera")).show();
};
window.abrirModalCuentasEspera = abrirModalCuentasEspera;

const guardarCarritoEnEspera = async function () {
    if (posCarrito.length === 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Carrito Vacío", text: "No hay productos para colocar en espera." });
        }
        return;
    }

    const nota = $("#inputNotaEspera").val().trim() || "Cuenta en espera";
    const totalUsd = obtenerTotalVentaUsd();
    const totalBs = roundDecimals(totalUsd * posTasaDia, 2);

    const payload = {
        cliente_id: posClienteActual ? posClienteActual.id : null,
        cliente: posClienteActual,
        tipo_venta: posTipoVentaActual,
        nota_referencia: nota,
        carrito: posCarrito,
        total_usd: totalUsd,
        total_bs: totalBs,
    };

    try {
        const res = await $.ajax({
            url: urlPosEnEsperaGuardar,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success) {
            posCarrito = [];
            posPagos = [];
            resetearClienteDefecto();
            renderizarCarritoPos();
            recalcularTotalesPos();
            await cargarListaCuentasEspera();
            bootstrap.Modal.getInstance(document.getElementById("modalCuentasEspera")).hide();

            if (window.notificacion) {
                window.notificacion.fire({ icon: "success", title: "Venta en Espera", text: res.message });
            }
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se pudo poner en espera.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Error", text: msg });
    }
};
window.guardarCarritoEnEspera = guardarCarritoEnEspera;

const cargarListaCuentasEspera = async function () {
    const $tbody = $("#contenedorFilasCuentasEspera");
    $tbody.empty();

    try {
        const res = await $.ajax({
            url: urlPosEnEsperaLista,
            type: "GET",
            dataType: "json",
        });

        if (res.success && Array.isArray(res.data) && res.data.length > 0) {
            res.data.forEach((e) => {
                const cliNombre = e.cliente ? `${e.cliente.nombre} ${e.cliente.apellido || ''}` : "Consumidor Final";
                const horaStr = new Date(e.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const filaHtml = `
                    <tr>
                        <td><strong class="text-dark font-monospace">${e.nota_referencia}</strong></td>
                        <td class="font-monospace small">${cliNombre}</td>
                        <td class="font-monospace text-muted small">${horaStr}</td>
                        <td class="text-end font-monospace fw-bold text-success">$ ${parseFloat(e.total_usd).toFixed(2)}</td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-0.5 fw-bold" onclick="recuperarCuentaEspera(${e.id})">
                                    <i class="fas fa-play me-1"></i> Retomar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-0" onclick="descartarCuentaEspera(${e.id})" style="width: 26px; height: 26px;">
                                    <i class="fas fa-trash-alt" style="font-size: 0.70rem;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                $tbody.append(filaHtml);
            });
        } else {
            $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3 small">No hay ninguna venta en espera guardada.</td></tr>');
        }
    } catch (e) {
        console.error("Error al listar ventas en espera:", e);
    }
};

const recuperarCuentaEspera = async function (id) {
    try {
        const res = await $.ajax({
            url: `${urlPosEnEsperaRecuperar}/${id}/recuperar`,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            const data = res.data;
            if (data.cliente) {
                establecerClienteActual(data.cliente);
            }
            if (data.tipo_venta) {
                posTipoVentaActual = data.tipo_venta;
                $(`#tipoVenta${data.tipo_venta === 'mayor' ? 'Mayor' : 'Detal'}`).prop("checked", true);
            }
            posCarrito = data.carrito || [];
            renderizarCarritoPos();
            recalcularTotalesPos();
            bootstrap.Modal.getInstance(document.getElementById("modalCuentasEspera")).hide();

            if (window.notificacion) {
                window.notificacion.fire({ icon: "success", title: "Cuenta Recuperada", text: "El pedido ha sido restaurado en pantalla." });
            }
        }
    } catch (e) {
        console.error("Error al recuperar cuenta en espera:", e);
    }
};
window.recuperarCuentaEspera = recuperarCuentaEspera;

const descartarCuentaEspera = async function (id) {
    try {
        await $.ajax({
            url: `${urlPosEnEsperaEliminar}/${id}`,
            type: "DELETE",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });
        cargarListaCuentasEspera();
    } catch (e) {
        console.error("Error al descartar cuenta en espera:", e);
    }
};
window.descartarCuentaEspera = descartarCuentaEspera;

/**
 * 10. Devolución de Factura
 */
let facturaDevolucionActual = null;

const abrirModalDevolucion = function () {
    $("#devInputBusquedaFactura").val("");
    $("#contenedorDetallesFacturaDevolucion").hide();
    $("#btnConfirmarDevolucion").hide();
    facturaDevolucionActual = null;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDevolucion")).show();
    setTimeout(() => {
        $("#devInputBusquedaFactura").focus();
    }, 300);
};
window.abrirModalDevolucion = abrirModalDevolucion;

const buscarFacturaParaDevolucion = async function () {
    const busqueda = $("#devInputBusquedaFactura").val().trim();
    if (!busqueda) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Ingresa un código", text: "Por favor escribe el número o código de comprobante (Ej. VEN-00001)." });
        }
        return;
    }

    try {
        const res = await $.ajax({
            url: urlPosDevolucionBuscar,
            type: "GET",
            data: { busqueda: busqueda },
            dataType: "json",
        });

        if (res.success && res.data) {
            facturaDevolucionActual = res.data;
            $("#devFacturaCodigo").text(`Factura #${res.data.codigo}`);
            $("#devFacturaCliente").text(`Cliente: ${res.data.cliente ? res.data.cliente.nombre + ' ' + (res.data.cliente.apellido || '') : 'Consumidor Final'}`);
            $("#devFacturaTotal").text(`Total: $ ${parseFloat(res.data.total_usd).toFixed(2)}`);
            $("#devFacturaFecha").text(`Fecha: ${res.data.fecha_emision} ${res.data.hora_emision}`);

            const $tbody = $("#contenedorFilasItemsDevolucion");
            $tbody.empty();

            if (Array.isArray(res.data.detalles)) {
                res.data.detalles.forEach((det) => {
                    const prodNombre = det.producto ? det.producto.nombre : "Artículo";
                    const precioUnit = parseFloat(det.precio_unitario_usd).toFixed(2);

                    // Calcular previamente devueltos si existen
                    let cantDevueltaPrevia = 0;
                    if (Array.isArray(res.data.devoluciones)) {
                        res.data.devoluciones.forEach((dev) => {
                            if (Array.isArray(dev.detalles)) {
                                dev.detalles.forEach((dd) => {
                                    if (dd.venta_detalle_id === det.id) {
                                        cantDevueltaPrevia += parseFloat(dd.cantidad || 0);
                                    }
                                });
                            }
                        });
                    }

                    const cantDisponible = Math.max(0, parseFloat(det.cantidad) - cantDevueltaPrevia);

                    const filaHtml = `
                        <tr>
                            <td>
                                <strong class="text-dark font-monospace">${prodNombre}</strong>
                                <small class="text-muted d-block">${det.tipo_item}</small>
                            </td>
                            <td class="text-center font-monospace">${parseFloat(det.cantidad)} ${cantDevueltaPrevia > 0 ? `<small class="text-danger">(-${cantDevueltaPrevia} dev)</small>` : ''}</td>
                            <td class="text-end font-monospace">$ ${precioUnit}</td>
                            <td class="text-center font-monospace">
                                <input type="number" step="any" min="0" max="${cantDisponible}" class="form-control form-control-sm text-center font-monospace fw-bold input-cant-dev" data-id="${det.id}" data-precio="${det.precio_unitario_usd}" value="0" oninput="calcularTotalRenglonDevolucion(this)">
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger subtotal-dev-row">$ 0.00</td>
                        </tr>
                    `;
                    $tbody.append(filaHtml);
                });
            }

            $("#contenedorDetallesFacturaDevolucion").slideDown(150);
            $("#btnConfirmarDevolucion").show();
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se encontró la factura.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Búsqueda Fallida", text: msg });
    }
};
window.buscarFacturaParaDevolucion = buscarFacturaParaDevolucion;

const calcularTotalRenglonDevolucion = function (input) {
    const $input = $(input);
    const cant = parseFloat($input.val()) || 0;
    const precio = parseFloat($input.data("precio")) || 0;
    const subtotal = roundDecimals(cant * precio, 2);
    $input.closest("tr").find(".subtotal-dev-row").text(`$ ${subtotal.toFixed(2)}`);
};
window.calcularTotalRenglonDevolucion = calcularTotalRenglonDevolucion;

const ejecutarDevolucion = async function () {
    if (!facturaDevolucionActual) return;

    const motivo = $("#devInputMotivo").val().trim();
    if (!motivo) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Motivo requerido", text: "Por favor ingresa el motivo de la devolución." });
        }
        $("#devInputMotivo").focus();
        return;
    }

    const items = [];
    $(".input-cant-dev").each(function () {
        const cant = parseFloat($(this).val()) || 0;
        const detId = parseInt($(this).data("id")) || 0;
        if (cant > 0 && detId > 0) {
            items.push({
                venta_detalle_id: detId,
                cantidad: cant,
            });
        }
    });

    if (items.length === 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Sin cantidades", text: "Debes ingresar al menos una cantidad mayor a 0 para devolver." });
        }
        return;
    }

    const payload = {
        venta_id: facturaDevolucionActual.id,
        motivo: motivo,
        tipo_reembolso: "sin_reembolso",
        items: items,
    };

    try {
        const res = await $.ajax({
            url: urlPosDevolucionProcesar,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById("modalDevolucion")).hide();
            cargarDatosInicialesPos(); // Recargar stock en memoria
            if (window.Swal) {
                Swal.fire({
                    icon: "success",
                    title: "Devolución Procesada",
                    text: res.message,
                });
            }
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "Error al procesar la devolución.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Error", text: msg });
    }
};
window.ejecutarDevolucion = ejecutarDevolucion;

/**
 * 11. Consulta / Verificador de Productos
 */
const abrirModalConsultaProducto = function () {
    $("#inputConsultaProdFiltro").val("");
    filtrarConsultaProductos();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalConsultaProducto")).show();
    setTimeout(() => {
        $("#inputConsultaProdFiltro").focus();
    }, 300);
};
window.abrirModalConsultaProducto = abrirModalConsultaProducto;

const filtrarConsultaProductos = function () {
    const query = ($("#inputConsultaProdFiltro").val() || "").trim().toLowerCase();
    const $tbody = $("#contenedorFilasConsultaProductos");
    $tbody.empty();

    const prods = posCatalogos.productos.filter((p) => {
        if (!query) return true;
        const nombreMatch = p.nombre.toLowerCase().includes(query);
        const skuMatch = (p.codigo_interno || "").toLowerCase().includes(query);
        const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase().includes(query));
        return nombreMatch || skuMatch || barcodeMatch;
    });

    if (prods.length === 0) {
        $tbody.append('<tr><td colspan="7" class="text-center text-muted py-4 small">No se encontraron productos coincidentes.</td></tr>');
        return;
    }

    prods.slice(0, 25).forEach((p) => {
        let stockTotal = 0;
        if (Array.isArray(p.stock_almacenes)) {
            stockTotal = p.stock_almacenes.reduce((acc, s) => acc + parseFloat(s.cantidad_actual || 0), 0);
        }

        const detalUsd = parseFloat(p.precio_detal_usd || 0);
        const mayorUsd = parseFloat(p.precio_mayorista_usd || 0);
        const detalBs = (detalUsd * posTasaDia).toFixed(2);
        const mayorBs = (mayorUsd * posTasaDia).toFixed(2);

        const badgeIva = p.aplica_iva
            ? `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-0.5">IVA ${p.iva_porcentaje}%</span>`
            : `<span class="badge rounded-pill bg-light text-secondary border font-monospace px-2 py-0.5">Exento</span>`;

        const filaHtml = `
            <tr>
                <td class="font-monospace"><span class="badge bg-light text-secondary border px-2 py-1">#${p.codigo_interno}</span></td>
                <td>
                    <strong class="text-dark font-monospace d-block" style="font-size: 0.88rem;">${p.nombre}</strong>
                    <small class="text-muted font-monospace">${p.categoria_nombre || 'General'}</small>
                </td>
                <td class="text-center font-monospace">
                    <span class="badge ${stockTotal <= 0 && p.tipo_item === 'producto' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success'} rounded-pill px-2.5 py-1">
                        ${stockTotal} ${p.unidad_medida || 'und'}
                    </span>
                </td>
                <td class="text-end font-monospace">
                    <strong class="text-primary d-block">$ ${detalUsd.toFixed(2)}</strong>
                    <small class="text-muted">Bs. ${detalBs}</small>
                </td>
                <td class="text-end font-monospace">
                    <strong style="color: #7e22ce;" class="d-block">$ ${mayorUsd.toFixed(2)}</strong>
                    <small class="text-muted">Bs. ${mayorBs}</small>
                </td>
                <td class="text-center font-monospace small">
                    ${badgeIva}
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-2.5 py-1 shadow-xs fw-bold" onclick="cargarProductoDesdeConsulta(${p.id})" title="Añadir a la Venta Activa">
                        <i class="fas fa-plus me-1"></i> Cargar
                    </button>
                </td>
            </tr>
        `;
        $tbody.append(filaHtml);
    });
};
window.filtrarConsultaProductos = filtrarConsultaProductos;

const cargarProductoDesdeConsulta = function (id) {
    const prod = posCatalogos.productos.find((p) => p.id === id);
    if (prod) {
        agregarProductoAlCarrito(prod);
        bootstrap.Modal.getInstance(document.getElementById("modalConsultaProducto")).hide();
        $("#posInputBuscadorProducto").val("").focus();
    }
};
window.cargarProductoDesdeConsulta = cargarProductoDesdeConsulta;

/**
 * 12. Reimpresión de Ticket
 */
const abrirModalReimprimir = function () {
    $("#inputCodigoReimprimir").val(posCatalogos.proximo_codigo ? `VEN-${String(Math.max(1, parseInt(posCatalogos.proximo_codigo.replace(/\D/g, '')) - 1)).padStart(5, '0')}` : "");
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalReimprimirTicket")).show();
    setTimeout(() => {
        $("#inputCodigoReimprimir").focus().select();
    }, 300);
};
window.abrirModalReimprimir = abrirModalReimprimir;

const ejecutarReimpresionTicket = function () {
    const cod = $("#inputCodigoReimprimir").val().trim();
    if (!cod) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Ingresa el código", text: "Por favor escribe el número o código de comprobante (Ej. VEN-00001)." });
        }
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById("modalReimprimirTicket")).hide();
    window.open(`${urlPosImprimir}/${cod}`, "_blank", "width=400,height=600");
};
window.ejecutarReimpresionTicket = ejecutarReimpresionTicket;

/**
 * 13. Modificar Renglón Seleccionado
 */
const modificarRenglonSeleccionado = function () {
    if (posIndiceRenglonSeleccionado === null || posIndiceRenglonSeleccionado < 0 || posIndiceRenglonSeleccionado >= posCarrito.length) {
        if (posCarrito.length > 0) {
            posIndiceRenglonSeleccionado = 0;
        } else {
            if (window.notificacion) {
                window.notificacion.fire({ icon: "warning", title: "Sin selección", text: "Selecciona un producto del carrito para modificarlo." });
            }
            return;
        }
    }

    const item = posCarrito[posIndiceRenglonSeleccionado];
    if (window.Swal) {
        Swal.fire({
            title: `Modificar: ${item.nombre}`,
            html: `
                <div class="text-start">
                    <label class="form-label small fw-bold">Cantidad:</label>
                    <input type="number" step="any" min="0.001" id="swalInputCant" class="form-control mb-2 font-monospace" value="${item.cantidad}">
                    <label class="form-label small fw-bold">Descuento (%):</label>
                    <input type="number" step="any" min="0" max="100" id="swalInputDesc" class="form-control font-monospace" value="${item.descuento_porcentaje}">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: "Guardar Cambios",
            cancelButtonText: "Cancelar",
            preConfirm: () => {
                const cant = parseFloat(document.getElementById("swalInputCant").value) || 1;
                const desc = parseFloat(document.getElementById("swalInputDesc").value) || 0;
                return { cant, desc };
            },
        }).then((res) => {
            if (res.isConfirmed && res.value) {
                item.cantidad = res.value.cant;
                item.descuento_porcentaje = res.value.desc;
                item.subtotal_usd = roundDecimals(item.cantidad * item.precio_unitario_usd * (1 - item.descuento_porcentaje / 100), 2);
                item.subtotal_bs = roundDecimals(item.subtotal_usd * posTasaDia, 2);
                renderizarCarritoPos();
                recalcularTotalesPos();
            }
        });
    }
};
window.modificarRenglonSeleccionado = modificarRenglonSeleccionado;

/**
 * 14. Atajos Globales de Teclado (Interceptación Estricta sin Comportamiento Predeterminado de Navegador)
 */
const configurarAtajosTecladoPos = function () {
    window.addEventListener("keydown", function (e) {
        const key = e.key;

        // Lista de teclas de atajo del POS que deben prevenir totalmente la acción por defecto del navegador
        const teclasInterceptar = ["F1", "F2", "F3", "F4", "F5", "F6", "F7", "F8", "F9", "F10", "F11", "F12", "Escape", "Esc"];

        if (teclasInterceptar.includes(key)) {
            // Cancelar de inmediato cualquier comportamiento nativo del navegador (como F6 foco a url, F7 caret browsing, F10 barra menú, etc.)
            if (key !== "F5" && key !== "F12") {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        }

        const modalCobroAbierto = $("#modalCobroVenta").hasClass("show");
        const modalDevolucionAbierto = $("#modalDevolucion").hasClass("show");
        const modalEsperaAbierto = $("#modalCuentasEspera").hasClass("show");
        const modalConsultaAbierto = $("#modalConsultaProducto").hasClass("show");
        const modalReimprimirAbierto = $("#modalReimprimirTicket").hasClass("show");
        const modalClienteAbierto = $("#modalRapidoClientePos").hasClass("show");

        const hayModalAbierto = modalCobroAbierto || modalDevolucionAbierto || modalEsperaAbierto || modalConsultaAbierto || modalReimprimirAbierto || modalClienteAbierto || $(".modal.show").length > 0;

        switch (key) {
            case "F2":
                abrirModalReimprimir();
                break;

            case "F4":
                if (modalCobroAbierto) {
                    procesarVentaFinal();
                } else {
                    abrirModalCobro();
                }
                break;

            case "F6":
                abrirModalDevolucion();
                break;

            case "F7":
                abrirModalCuentasEspera();
                break;

            case "F8":
                abrirModalConsultaProducto();
                break;

            case "F9":
                modificarRenglonSeleccionado();
                break;

            case "F10":
                limpiarPantallaPos();
                break;

            case "Escape":
            case "Esc":
                if (hayModalAbierto) {
                    $(".modal.show").each(function () {
                        const m = bootstrap.Modal.getInstance(this);
                        if (m) m.hide();
                    });
                } else {
                    if (typeof urlDashboard !== "undefined") {
                        window.location.href = urlDashboard;
                    }
                }
                break;
        }
    }, { capture: true, passive: false });
};

/**
 * Helper para redondeo decimal exacto
 */
const roundDecimals = function (num, decimals = 2) {
    const factor = Math.pow(10, decimals);
    return Math.round((Number(num) + Number.EPSILON) * factor) / factor;
};
