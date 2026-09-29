let posCatalogos = {
    cliente_defecto: null,
    almacenes: [],
    metodos_pago: [],
    tasa_usd: 1.0000,
    productos: [],
    proximo_codigo: "VEN-00001",
};

let posClienteActual = null;
let posTipoVentaActual = "detal";
let posAlmacenActualId = 1;
let posTasaDia = 1.0000;
let posCarrito = [];
let posPagos = [];
let posIndiceRenglonSeleccionado = null;
let posVendedorFijoEspera = null;
let productoPendienteVariante = null;
let productoPendienteSerial = null;
let serialesDisponiblesModal = [];

$(document).ready(function () {
    cargarDatosInicialesPos();
    configurarAtajosTecladoPos();
    configurarBuscadorProductosPos();
    configurarBuscadorClientesPos();
    $("#modalInicioVentaCliente").on("shown.bs.modal", function () {
        const $inp = $("#posInputClienteModal");
        $inp.trigger("focus");
        $inp.select();
    });
});

function formatearMonto(monto, decimales = 2) {
    const num = parseFloat(monto) || 0;
    return num.toLocaleString("es-VE", {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
};

async function cargarDatosInicialesPos() {
    try {
        const res = await $.ajax({
            url: urlPosDatos,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            posCatalogos = res.data;
            posTasaDia = parseFloat(res.data.tasa_usd) || 1.0000;
            $("#posBadgeTasaDia").text(formatearMonto(posTasaDia, 2));
            $("#cobroModalTasa").text(formatearMonto(posTasaDia, 2));

            const $selAlm = $("#posSelectAlmacen");
            $selAlm.empty();
            if (Array.isArray(res.data.almacenes) && res.data.almacenes.length > 0) {
                res.data.almacenes.forEach((a, idx) => {
                    $selAlm.append(`<option value="${a.id}" ${idx === 0 ? "selected" : ""}>${a.nombre}</option>`);
                });
                posAlmacenActualId = parseInt(res.data.almacenes[0].id);
            }

            poblarSelectMetodosPago();

            const $selVendedor = $("#cobroSelectVendedor");
            if ($selVendedor.length) {
                $selVendedor.empty().append('<option value="">-- Sin vendedor asignado --</option>');
                if (Array.isArray(res.data.vendedores)) {
                    res.data.vendedores.forEach((v) => {
                        $selVendedor.append(`<option value="${v.id}">${v.nombre} (${parseFloat(v.comision_porcentaje).toFixed(2)}%)</option>`);
                    });
                }
            }

            if (res.data.es_vendedor) {
                $("#btnCobrarPos").addClass("d-none");
                $("#btnPreventaPos").removeClass("d-none").addClass("d-flex");
            } else {
                $("#btnPreventaPos").addClass("d-none").removeClass("d-flex");
                $("#btnCobrarPos").removeClass("d-none");
            }

            if (res.data.maneja_vendedores === false) {
                $("#contenedorVendedorCobro").hide();
            } else {
                $("#contenedorVendedorCobro").show();
            }

            renderizarCajaNavbar();

            if (res.data.es_cajero && !res.data.puede_operar) {
                const modalApertura = document.getElementById("modalAperturaTurnoPos");
                if (modalApertura) {
                    const inst = bootstrap.Modal.getInstance(modalApertura);
                    if (inst) inst.hide();
                }
                const modalBloqueo = document.getElementById("modalBloqueoCajaPos");
                if (modalBloqueo) {
                    bootstrap.Modal.getOrCreateInstance(modalBloqueo).show();
                }
            } else if (res.data.es_admin && !res.data.turno_activo) {
                abrirModalAperturaPos();
            } else if (!posClienteActual) {
                activarModoConsulta();
                abrirModalInicioCliente();
            }
        }
    } catch (e) {
        console.error("Error al cargar datos del POS:", e);
    }
};

function poblarSelectMetodosPago() {
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

function actualizarMonedaPagoSeleccionada() {
    const $opt = $("#cobroSelectMetodo option:selected");
    const moneda = $opt.data("moneda") || "USD";
    $("#cobroSimboloMonedaPago").text(moneda === "VES" ? "Bs." : "$");
};

function activarModoConsulta() {
    posClienteActual = null;
    $("#contenedorClienteActivo").addClass("d-none");
    $("#contenedorModoConsulta").removeClass("d-none");
    setTimeout(() => {
        $("#posInputBuscadorProducto").focus();
    }, 100);
};

function abrirModalInicioCliente() {
    const modalEl = document.getElementById("modalInicioVentaCliente");
    if (!modalEl) return;
    $("#posInputClienteModal").val("");
    $("#dropdownClientesModalPos").hide().empty();
    const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInst.show();
    setTimeout(() => {
        const $inp = $("#posInputClienteModal");
        $inp.trigger("focus");
        $inp.select();
    }, 150);
};

function asignarConsumidorFinalRapido() {
    let cli = posCatalogos.cliente_defecto;
    if (!cli) {
        cli = {
            id: null,
            nombre: "Consumidor",
            apellido: "Final",
            cedula: "V-00000000",
            telefono: "Sin teléfono",
            tipo_cliente: "detal"
        };
    }
    establecerClienteActual(cli);
};

function abrirModalNuevoClienteDesdeInicio() {
    const modalInicioEl = document.getElementById("modalInicioVentaCliente");
    if (modalInicioEl) {
        const modalInst = bootstrap.Modal.getInstance(modalInicioEl) || bootstrap.Modal.getOrCreateInstance(modalInicioEl);
        if (modalInst) modalInst.hide();
    }
    abrirModalNuevoCliente();
};

function establecerClienteActual(cliente) {
    posClienteActual = cliente;
    const nombreCompleto = `${cliente.nombre || ''} ${cliente.apellido || ''}`.trim() || "Consumidor Final";
    $("#posClienteNombre").text(nombreCompleto);
    $("#posClienteCedula").text(cliente.cedula || "--");
    $("#posClienteTelefono").text(cliente.telefono || "Sin teléfono");

    const tipo = cliente.tipo_cliente || "detal";
    $("#posClienteTipoBadge").text(tipo === "mayorista" ? "Mayor" : "Detal")
        .removeClass("bg-primary-subtle text-primary border-primary-subtle bg-purple-subtle text-purple-emphasis border-purple-subtle bg-secondary-subtle text-secondary")
        .addClass(tipo === "mayorista" ? "bg-purple-subtle text-purple-emphasis border border-purple-subtle" : "bg-primary-subtle text-primary border border-primary-subtle");

    if (tipo === "mayorista" && posTipoVentaActual !== "mayor") {
        cambiarTipoVenta("mayor");
    }

    $("#contenedorModoConsulta").addClass("d-none");
    $("#contenedorClienteActivo").removeClass("d-none");

    const modalEl = document.getElementById("modalInicioVentaCliente");
    if (modalEl) {
        const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalInst) modalInst.hide();
    }

    setTimeout(() => {
        $("#posInputBuscadorProducto").focus();
    }, 150);
};

function resetearClienteDefecto() {
    activarModoConsulta();
};

function configurarBuscadorClientesPos() {
    const $input = $("#posInputClienteModal");
    const $dropdown = $("#dropdownClientesModalPos");
    let debounceTimer = null;
    let indiceSeleccionadoCli = -1;

    $input.on("keydown", function (e) {
        const $items = $dropdown.find(".cliente-item-pos");

        if ($dropdown.is(":visible") && $items.length > 0) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                indiceSeleccionadoCli = (indiceSeleccionadoCli + 1) % $items.length;
                $items.removeClass("dropdown-item-hover-pos");
                const $itemActivo = $items.eq(indiceSeleccionadoCli).addClass("dropdown-item-hover-pos");
                $itemActivo[0]?.scrollIntoView({ block: "nearest" });
                return;
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                indiceSeleccionadoCli = (indiceSeleccionadoCli - 1 + $items.length) % $items.length;
                $items.removeClass("dropdown-item-hover-pos");
                const $itemActivo = $items.eq(indiceSeleccionadoCli).addClass("dropdown-item-hover-pos");
                $itemActivo[0]?.scrollIntoView({ block: "nearest" });
                return;
            } else if (e.key === "Enter") {
                e.preventDefault();
                if (indiceSeleccionadoCli >= 0 && indiceSeleccionadoCli < $items.length) {
                    $items.eq(indiceSeleccionadoCli).trigger("click");
                } else if ($items.length > 0) {
                    $items.first().trigger("click");
                }
                return;
            } else if (e.key === "Escape") {
                $dropdown.hide();
                return;
            }
        }

        if (e.key === "Enter") {
            e.preventDefault();
            const term = $input.val().trim();
            if (!term) {
                asignarConsumidorFinalRapido();
            } else {
                $dropdown.hide();
                abrirModalNuevoCliente(term);
            }
        }
    });

    $input.on("input", function () {
        clearTimeout(debounceTimer);
        const term = $(this).val().trim();
        indiceSeleccionadoCli = -1;

        if (term.length < 2) {
            $dropdown.hide().empty();
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
                indiceSeleccionadoCli = -1;

                if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                    res.data.forEach((c) => {
                        const itemHtml = `
                            <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2 d-flex justify-content-between align-items-center cliente-item-pos" data-json='${JSON.stringify(c).replace(/'/g, "&apos;")}'>
                                <div>
                                    <strong class="text-dark d-block" style="font-size: 0.85rem;">${c.nombre} ${c.apellido || ''}</strong>
                                    <small class="text-muted font-monospace">${c.cedula} • ${c.telefono || 'Sin tel.'}</small>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace">${c.tipo_cliente}</span>
                            </a>
                        `;
                        $dropdown.append(itemHtml);
                    });

                    const createExtraHtml = `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2.5 d-flex justify-content-between align-items-center cliente-item-pos cliente-item-create-pos border-top" data-accion="crear" data-termino="${term}" style="background: #fdfefe;">
                            <div class="d-flex align-items-center gap-2 text-start">
                                <i class="fas fa-plus-circle text-primary"></i>
                                <span class="text-primary font-monospace fw-bold small">¿No es ninguno? Registrar "${term}" como nuevo cliente</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" style="font-size: 0.70rem;">Nuevo [Enter]</span>
                        </a>
                    `;
                    $dropdown.append(createExtraHtml);

                    $dropdown.find(".cliente-item-pos").on("click", function () {
                        const accion = $(this).attr("data-accion") || $(this).data("accion");
                        if (accion === "crear") {
                            const termCrear = $(this).attr("data-termino") || $input.val().trim();
                            $input.val("");
                            $dropdown.hide();
                            abrirModalNuevoCliente(String(termCrear));
                            return;
                        }
                        const cli = $(this).data("json");
                        if (cli) {
                            establecerClienteActual(cli);
                            $input.val("");
                            $dropdown.hide();
                        }
                    });

                    $dropdown.show();
                } else {
                    const noResultHtml = `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center cliente-item-pos cliente-item-create-pos dropdown-item-hover-pos" data-accion="crear" data-termino="${term}">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="rounded-circle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: #eef2ff;">
                                    <i class="fas fa-user-plus text-primary"></i>
                                </div>
                                <div class="text-start">
                                    <strong class="text-dark d-block" style="font-size: 0.88rem;">Registrar nuevo cliente con "${term}"</strong>
                                    <small class="text-muted font-monospace">No existe en la base de datos • Pulsa [Enter] para registrar</small>
                                </div>
                            </div>
                            <span class="badge bg-primary text-white rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs">Registrar [Enter]</span>
                        </a>
                    `;
                    $dropdown.append(noResultHtml);
                    indiceSeleccionadoCli = 0;

                    $dropdown.find(".cliente-item-pos").on("click", function () {
                        const termCrear = $(this).attr("data-termino") || $input.val().trim();
                        $input.val("");
                        $dropdown.hide();
                        abrirModalNuevoCliente(String(termCrear));
                    });

                    $dropdown.show();
                }
            } catch (e) {
                console.error("Error en búsqueda de clientes:", e);
            }
        }, 150);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#posInputClienteModal, #dropdownClientesModalPos").length) {
            $dropdown.hide();
        }
    });
};

function abrirModalNuevoCliente(terminoInicial = "") {
    const modalInicioEl = document.getElementById("modalInicioVentaCliente");
    if (modalInicioEl) {
        const modalInst = bootstrap.Modal.getInstance(modalInicioEl) || bootstrap.Modal.getOrCreateInstance(modalInicioEl);
        if (modalInst) modalInst.hide();
    }

    $("#formRapidoClientePos")[0].reset();
    $("#rapidoClienteId").val("");

    const termStr = String(terminoInicial || "").trim();

    if (termStr) {
        const tieneLetras = /[a-zA-Z]/.test(termStr);
        const tieneNumeros = /[0-9]/.test(termStr);
        const esCedulaOIdentificacion = /^[VJEGPvjegp]-?[0-9]+$/i.test(termStr) || (!tieneLetras && tieneNumeros);

        if (esCedulaOIdentificacion) {
            let tipo = "V-";
            let numero = termStr.replace(/^[VJEGPvjegp]-?/i, "").replace(/\D/g, "");
            const matchPrefijo = termStr.match(/^([VJEGPvjegp])-?/i);
            if (matchPrefijo) {
                tipo = matchPrefijo[1].toUpperCase() + "-";
            }
            $("#rapido_tipo_cedula").val(tipo);
            $("#rapido_cedula_numero").val(numero);
        } else {
            $("#rapido_tipo_cedula").val("V-");
            $("#rapido_cedula_numero").val("");
            const partes = termStr.split(/\s+/);
            if (partes.length > 1) {
                $("#rapido_nombre").val(partes[0]);
                $("#rapido_apellido").val(partes.slice(1).join(" "));
            } else {
                $("#rapido_nombre").val(termStr);
            }
        }
    } else {
        $("#rapido_tipo_cedula").val("V-");
        $("#rapido_cedula_numero").val("");
    }

    $("#rapido_codigo_pais").val("+58");
    $("#rapido_telefono_numero").val("");
    $("#rapido_tipo_cliente").val("detal");

    $("#modalRapidoClientePosTitulo").text("Nuevo Cliente");
    $("#modalRapidoClientePosSubtitulo").text("Completa la información del cliente");
    $("#modalRapidoClientePosIcono").attr("class", "fas fa-user-plus text-white");
    $("#modalRapidoClientePosTextoGuardar").text("Guardar Cliente");

    const modalNuevoEl = document.getElementById("modalRapidoClientePos");
    if (modalNuevoEl) {
        bootstrap.Modal.getOrCreateInstance(modalNuevoEl).show();
    }

    setTimeout(() => {
        if ($("#rapido_cedula_numero").val()) {
            $("#rapido_nombre").focus();
        } else if ($("#rapido_nombre").val()) {
            if ($("#rapido_apellido").val()) {
                $("#rapido_cedula_numero").focus();
            } else {
                $("#rapido_apellido").focus();
            }
        } else {
            $("#rapido_cedula_numero").focus();
        }
    }, 250);
};

function abrirModalEditarCliente() {
    if (!posClienteActual || posClienteActual.id === posCatalogos.cliente_defecto?.id) {
        return abrirModalNuevoCliente();
    }
    $("#formRapidoClientePos")[0].reset();
    $("#rapidoClienteId").val(posClienteActual.id);

    $("#rapido_nombre").val(posClienteActual.nombre || "");
    $("#rapido_apellido").val(posClienteActual.apellido || "");

    const cedula = typeof desglosarCedula === "function" ? desglosarCedula(posClienteActual.cedula) : { tipo: "V-", numero: posClienteActual.cedula };
    $("#rapido_tipo_cedula").val(cedula.tipo || "V-");
    $("#rapido_cedula_numero").val(cedula.numero || "");

    const telefono = typeof desglosarTelefono === "function" ? desglosarTelefono(posClienteActual.telefono) : { codigo: "+58", numero: posClienteActual.telefono };
    $("#rapido_codigo_pais").val(telefono.codigo || "+58");
    $("#rapido_telefono_numero").val(telefono.numero || "");

    $("#rapido_correo").val(posClienteActual.correo || "");
    $("#rapido_direccion").val(posClienteActual.direccion || "");
    $("#rapido_tipo_cliente").val(posClienteActual.tipo_cliente || "detal");

    $("#modalRapidoClientePosTitulo").text(`Editar Cliente: ${posClienteActual.nombre} ${posClienteActual.apellido || ''}`);
    $("#modalRapidoClientePosSubtitulo").text("Modifica los datos fiscales del cliente");
    $("#modalRapidoClientePosIcono").attr("class", "fas fa-user-edit text-white");
    $("#modalRapidoClientePosTextoGuardar").text("Actualizar Cambios");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalRapidoClientePos")).show();
};

async function guardarClienteRapidoPos(e) {
    e.preventDefault();

    const nombre = $("#rapido_nombre").val().trim();
    const apellido = $("#rapido_apellido").val().trim();
    const cedulaNum = $("#rapido_cedula_numero").val().trim();

    if (nombre.length < 2 || apellido.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Nombre o Apellido demasiado corto",
                text: "El nombre y apellido deben tener al menos 2 caracteres.",
            });
        }
        return;
    }

    if (cedulaNum.length < 5) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Documento incompleto",
                text: "El número de cédula o RIF debe tener al menos 5 dígitos.",
            });
        }
        return;
    }

    const tipoCedula = $("#rapido_tipo_cedula").val() || "V-";
    const cedulaCompleta = tipoCedula + cedulaNum;

    const telNum = $("#rapido_telefono_numero").val().trim();
    const telCodigo = $("#rapido_codigo_pais").val() || "+58";
    const telefonoCompleto = telNum ? (telCodigo + telNum) : null;

    const payload = {
        cliente_id: $("#rapidoClienteId").val() || null,
        cedula: cedulaCompleta,
        nombre: nombre,
        apellido: apellido,
        telefono: telefonoCompleto,
        correo: $("#rapido_correo").val().trim() || null,
        direccion: $("#rapido_direccion").val().trim() || null,
        tipo_cliente: $("#rapido_tipo_cliente").val() || "detal",
    };

    try {
        const res = await $.ajax({
            url: urlPosGuardarCliente,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success && res.data) {
            establecerClienteActual(res.data);
            const modalEl = document.getElementById("modalRapidoClientePos");
            const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
            if (modalInst) modalInst.hide();

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

function cambiarTipoVenta(tipo) {
    posTipoVentaActual = tipo;

    if (tipo === "mayor") {
        $("#btnTarifaDetal").removeClass("active-detal");
        $("#btnTarifaMayor").addClass("active-mayor");
        $("#posTarifaStatusBadge")
            .text("Tarifa Mayor")
            .removeClass("bg-primary-subtle text-primary border-primary-subtle")
            .addClass("bg-purple-subtle text-purple-emphasis border border-purple-subtle");
    } else {
        $("#btnTarifaMayor").removeClass("active-mayor");
        $("#btnTarifaDetal").addClass("active-detal");
        $("#posTarifaStatusBadge")
            .text("Tarifa Detal")
            .removeClass("bg-purple-subtle text-purple-emphasis border-purple-subtle")
            .addClass("bg-primary-subtle text-primary border border-primary-subtle");
    }

    posCarrito.forEach((item) => {
        const precioUnitUsd = posTipoVentaActual === "mayor" ? item.precio_mayorista_usd : item.precio_detal_usd;
        item.precio_unitario_usd = precioUnitUsd;
        item.subtotal_usd = roundDecimals(item.cantidad * precioUnitUsd * (1 - item.descuento_porcentaje / 100), 2);
        item.subtotal_bs = roundDecimals(item.subtotal_usd * posTasaDia, 2);
    });

    renderizarCarritoPos();
    recalcularTotalesPos();
};

function cambiarAlmacenActivo() {
    posAlmacenActualId = parseInt($("#posSelectAlmacen").val()) || 1;
};

function configurarBuscadorProductosPos() {
    const $input = $("#posInputBuscadorProducto");
    const $dropdown = $("#dropdownProductosPos");
    let debounceTimer = null;
    let indiceSeleccionadoProd = -1;

    $input.on("keydown", function (e) {
        const $items = $dropdown.find(".prod-item-pos");

        if ($dropdown.is(":visible") && $items.length > 0) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                indiceSeleccionadoProd = (indiceSeleccionadoProd + 1) % $items.length;
                $items.removeClass("dropdown-item-hover-pos");
                const $itemActivo = $items.eq(indiceSeleccionadoProd).addClass("dropdown-item-hover-pos");
                $itemActivo[0]?.scrollIntoView({ block: "nearest" });
                return;
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                indiceSeleccionadoProd = (indiceSeleccionadoProd - 1 + $items.length) % $items.length;
                $items.removeClass("dropdown-item-hover-pos");
                const $itemActivo = $items.eq(indiceSeleccionadoProd).addClass("dropdown-item-hover-pos");
                $itemActivo[0]?.scrollIntoView({ block: "nearest" });
                return;
            } else if (e.key === "Enter") {
                if (indiceSeleccionadoProd >= 0 && indiceSeleccionadoProd < $items.length) {
                    e.preventDefault();
                    $items.eq(indiceSeleccionadoProd).trigger("click");
                    return;
                }
            } else if (e.key === "Escape") {
                $dropdown.hide();
                return;
            }
        }

        if (e.key === "Enter") {
            e.preventDefault();
            const rawVal = $(this).val().trim();
            if (!rawVal) return;

            const requiereModal = rawVal.startsWith("*");
            const query = (requiereModal ? rawVal.substring(1).trim() : rawVal).toLowerCase();
            if (!query) return;

            let serialExactoEncontrado = null;
            let prodConSerialExacto = null;
            for (const p of posCatalogos.productos) {
                if (Array.isArray(p.seriales_disponibles)) {
                    const matchSerial = p.seriales_disponibles.find((s) => (s.numero_serial || "").toLowerCase() === query);
                    if (matchSerial) {
                        serialExactoEncontrado = matchSerial;
                        prodConSerialExacto = p;
                        break;
                    }
                }
            }

            if (prodConSerialExacto && serialExactoEncontrado) {
                agregarProductoAlCarrito(
                    prodConSerialExacto,
                    1,
                    serialExactoEncontrado.almacen_id || posAlmacenActualId,
                    0,
                    serialExactoEncontrado.variante_color || null,
                    serialExactoEncontrado.id,
                    serialExactoEncontrado.numero_serial
                );
                $(this).val("").focus();
                $dropdown.hide();
                return;
            }

            const prodExacto = posCatalogos.productos.find((p) => {
                const skuMatch = (p.codigo_interno || "").toLowerCase() === query;
                const barcodeMatch = Array.isArray(p.codigos_barra) && p.codigos_barra.some((cb) => cb.toLowerCase() === query);
                return skuMatch || barcodeMatch;
            });

            if (prodExacto) {
                if (requiereModal) {
                    abrirModalSeleccionCantidadAlmacen(prodExacto);
                } else {
                    solicitarAgregarProductoPos(prodExacto);
                }
                $(this).val("").focus();
                $dropdown.hide();
                return;
            }

            const coincidencias = posCatalogos.productos.filter((p) =>
                p.nombre.toLowerCase().includes(query) || (p.codigo_interno || "").toLowerCase().includes(query)
            );

            if (coincidencias.length === 1) {
                if (requiereModal) {
                    abrirModalSeleccionCantidadAlmacen(coincidencias[0]);
                } else {
                    solicitarAgregarProductoPos(coincidencias[0]);
                }
                $(this).val("").focus();
                $dropdown.hide();
            } else if (coincidencias.length > 1) {
                renderizarDropdownProductos(coincidencias, requiereModal);
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
        const rawVal = $(this).val().trim();
        const requiereModal = rawVal.startsWith("*");
        const query = (requiereModal ? rawVal.substring(1).trim() : rawVal).toLowerCase();
        indiceSeleccionadoProd = -1;

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

            renderizarDropdownProductos(coincidencias, requiereModal);
        }, 150);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("#posInputBuscadorProducto, #dropdownProductosPos").length) {
            $dropdown.hide();
        }
    });
};

function renderizarDropdownProductos(productos, requiereModal = false) {
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

        let itemIcon = '<i class="fas fa-box"></i>';
        let itemBg = 'bg-primary bg-opacity-10 text-primary';
        let tipoBadge = '';

        if (p.tipo_item === 'moto') {
            itemIcon = '<i class="fas fa-motorcycle"></i>';
            itemBg = 'bg-warning bg-opacity-15 text-warning-emphasis';
            tipoBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" style="font-size: 0.70rem;">Moto / Serial</span>';
        } else if (p.tipo_item === 'servicio') {
            itemIcon = '<i class="fas fa-wrench"></i>';
            itemBg = 'bg-info bg-opacity-15 text-info-emphasis';
            tipoBadge = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace" style="font-size: 0.70rem;">Servicio</span>';
        } else if (p.maneja_seriales) {
            itemIcon = '<i class="fas fa-tv"></i>';
            itemBg = 'bg-info bg-opacity-15 text-info-emphasis';
            tipoBadge = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace" style="font-size: 0.70rem;">Serial Físico</span>';
        } else if (p.maneja_variantes) {
            itemIcon = '<i class="fas fa-couch"></i>';
            itemBg = 'bg-purple-subtle text-purple-emphasis';
            tipoBadge = '<span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle font-monospace" style="font-size: 0.70rem;">Variantes</span>';
        }

        const stockText = p.tipo_item === 'moto'
            ? `<strong class="text-success">Disponible (1 UND)</strong>`
            : (p.tipo_item === 'servicio' ? `<strong class="text-info">Servicio Activo</strong>` : `<strong class="${stockAlmacenActual <= 0 ? 'text-danger' : 'text-success'}">${stockAlmacenActual} ${p.unidad_medida || 'und'}</strong>`);

        const modoBadge = requiereModal ? '<span class="badge bg-warning text-dark font-monospace me-1"><i class="fas fa-asterisk"></i> Cantidad & Almacén</span>' : '';

        const itemHtml = `
            <a href="javascript:void(0)" class="list-group-item list-group-item-action p-2.5 d-flex align-items-center justify-content-between prod-item-pos" data-id="${p.id}" data-tipo="${p.tipo_item}">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-executive-sm rounded-3 ${itemBg} d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px; font-size: 1.1rem;">
                        ${itemIcon}
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            ${modoBadge}
                            <strong class="text-dark font-monospace" style="font-size: 0.90rem;">${p.nombre}</strong>
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.70rem;">#${p.codigo_interno}</span>
                            ${tipoBadge}
                            ${badgeIvaHtml}
                        </div>
                        <div class="small font-monospace text-muted mt-0.5">
                            Stock: ${stockText}
                            • Detal: <strong class="text-primary">$${precioDetalUsd.toFixed(2)}</strong> <small class="text-muted">(Bs. ${precioDetalBs})</small>
                            • Mayor: <strong style="color: #7e22ce;">$${precioMayorUsd.toFixed(2)}</strong> <small class="text-muted">(Bs. ${precioMayorBs})</small>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm ${requiereModal ? 'btn-warning text-dark' : 'btn-primary'} rounded-pill px-3 py-1 fw-bold shadow-xs">
                    <i class="fas ${requiereModal ? 'fa-sliders-h' : 'fa-plus'} me-1"></i> ${requiereModal ? 'Detalle' : 'Cargar'}
                </button>
            </a>
        `;
        $dropdown.append(itemHtml);
    });

    $dropdown.find(".prod-item-pos").on("click", function () {
        const id = parseInt($(this).data("id"));
        const tipo = $(this).data("tipo");
        const prod = posCatalogos.productos.find((p) => parseInt(p.id) === id && p.tipo_item === tipo);
        if (prod) {
            if (requiereModal) {
                abrirModalSeleccionCantidadAlmacen(prod);
            } else {
                solicitarAgregarProductoPos(prod);
            }
            $("#posInputBuscadorProducto").val("").focus();
            $dropdown.hide();
        }
    });

    $dropdown.show();
};

function solicitarAgregarProductoPos(prod, cantidad = 1, almacenId = null, descuento = 0) {
    if (!prod) return;
    const targetAlmId = almacenId || posAlmacenActualId;

    if (prod.tipo_item === 'producto') {
        if (prod.maneja_seriales) {
            abrirModalSeleccionarSerialPos(prod, targetAlmId, descuento);
            return;
        }
        if (prod.maneja_variantes) {
            const opciones = (prod.atributos_variantes && Array.isArray(prod.atributos_variantes.opciones)) ? prod.atributos_variantes.opciones : [];
            if (opciones.length > 0) {
                abrirModalSeleccionarVariantePos(prod, cantidad, targetAlmId, descuento);
                return;
            }
        }
    }

    agregarProductoAlCarrito(prod, cantidad, targetAlmId, descuento);
}

function abrirModalSeleccionarVariantePos(prod, cantidad = 1, almacenId = null, descuento = 0) {
    productoPendienteVariante = {
        prod: prod,
        cantidad: cantidad,
        almacenId: almacenId || posAlmacenActualId,
        descuento: descuento
    };

    $("#posVarianteModalProdNombre").text(prod.nombre);
    $("#posVarianteModalProdCodigo").text(`#${prod.codigo_interno || '0000'}`);
    const attrNombre = prod.atributos_variantes?.nombre || "Opciones";
    $("#posVarianteModalAttrNombre").text(`${attrNombre} Disponibles:`);

    const $container = $("#posVarianteOpcionesContainer");
    $container.empty();
    $("#posVarianteModalOpcionSeleccionada").val("");

    const opciones = (prod.atributos_variantes && Array.isArray(prod.atributos_variantes.opciones)) ? prod.atributos_variantes.opciones : [];

    if (opciones.length === 0) {
        agregarProductoAlCarrito(prod, cantidad, almacenId, descuento);
        return;
    }

    opciones.forEach((opc, idx) => {
        const chipHtml = `
            <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-1.5 font-monospace fw-bold btn-variante-pos-chip ${idx === 0 ? 'active btn-primary text-white' : ''}" data-opcion="${opc}" onclick="seleccionarChipVariantePos(this, '${opc}')">
                <i class="fas fa-check-circle me-1 ${idx === 0 ? '' : 'd-none'}"></i> ${opc}
            </button>
        `;
        $container.append(chipHtml);
    });

    $("#posVarianteModalOpcionSeleccionada").val(opciones[0]);

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalSeleccionarVariantePos")).show();
}

function seleccionarChipVariantePos(btn, opcion) {
    $(".btn-variante-pos-chip").removeClass("active btn-primary text-white").addClass("btn-outline-primary");
    $(".btn-variante-pos-chip i").addClass("d-none");
    $(btn).removeClass("btn-outline-primary").addClass("active btn-primary text-white");
    $(btn).find("i").removeClass("d-none");
    $("#posVarianteModalOpcionSeleccionada").val(opcion);
}

function confirmarSeleccionVariantePos() {
    if (!productoPendienteVariante) return;
    const opcion = $("#posVarianteModalOpcionSeleccionada").val() || "";
    const p = productoPendienteVariante;

    const modalEl = document.getElementById("modalSeleccionarVariantePos");
    if (modalEl) {
        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (inst) inst.hide();
    }

    agregarProductoAlCarrito(p.prod, p.cantidad, p.almacenId, p.descuento, opcion);
    productoPendienteVariante = null;
    $("#posInputBuscadorProducto").val("").focus();
}

function abrirModalSeleccionarSerialPos(prod, almacenId = null, descuento = 0) {
    const targetAlmId = almacenId || posAlmacenActualId;
    productoPendienteSerial = {
        prod: prod,
        almacenId: targetAlmId,
        descuento: descuento
    };

    $("#posSerialModalProdNombre").text(prod.nombre);
    $("#posSerialModalProdCodigo").text(`#${prod.codigo_interno || '0000'}`);

    const almObj = posCatalogos.almacenes.find((a) => a.id === targetAlmId);
    $("#posSerialModalAlmacenBadge").text(almObj ? almObj.nombre : "Almacén");

    const todosSeriales = Array.isArray(prod.seriales_disponibles) ? prod.seriales_disponibles : [];
    serialesDisponiblesModal = todosSeriales.filter((s) => !s.almacen_id || s.almacen_id === targetAlmId);

    if (serialesDisponiblesModal.length === 0 && todosSeriales.length > 0) {
        serialesDisponiblesModal = todosSeriales;
    }

    $("#posFiltroSerialesModal").val("");
    renderizarListaSerialesModalPos();

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalSeleccionarSerialPos")).show();
    setTimeout(() => {
        $("#posFiltroSerialesModal").focus();
    }, 250);
}

function filtrarSerialesModalPos() {
    renderizarListaSerialesModalPos();
}

function renderizarListaSerialesModalPos() {
    const query = ($("#posFiltroSerialesModal").val() || "").trim().toLowerCase();
    const $container = $("#posSerialesOpcionesContainer");
    $container.empty();

    const filtrados = serialesDisponiblesModal.filter((s) => {
        if (!query) return true;
        const numMatch = (s.numero_serial || "").toLowerCase().includes(query);
        const colMatch = (s.variante_color || "").toLowerCase().includes(query);
        return numMatch || colMatch;
    });

    $("#posSerialesModalContador").text(`${filtrados.length} Disponible${filtrados.length === 1 ? '' : 's'}`);

    if (filtrados.length === 0) {
        $container.append(`
            <div class="text-center py-4 text-muted small font-monospace">
                <i class="fas fa-barcode fs-4 d-block mb-1 opacity-50"></i>
                No hay unidades físicas disponibles con seriales en este almacén.
            </div>
        `);
        return;
    }

    filtrados.forEach((s) => {
        const yaEnCarrito = posCarrito.some((it) => it.producto_serial_id === s.id);
        const badgeColor = s.variante_color
            ? `<span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle font-monospace ms-2"><i class="fas fa-palette me-1"></i>${s.variante_color}</span>`
            : '';

        const itemHtml = `
            <div class="p-2.5 bg-white border rounded-3 d-flex align-items-center justify-content-between shadow-xs ${yaEnCarrito ? 'opacity-50' : ''}">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle ${yaEnCarrito ? 'bg-secondary' : 'bg-success'} text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                        <i class="fas ${yaEnCarrito ? 'fa-ban' : 'fa-check'}"></i>
                    </div>
                    <div>
                        <strong class="text-dark font-monospace" style="font-size: 0.88rem;">${s.numero_serial}</strong>
                        ${badgeColor}
                        <small class="text-muted font-monospace d-block" style="font-size: 0.70rem;">Estado: Disponible para venta</small>
                    </div>
                </div>
                <div>
                    ${yaEnCarrito
                        ? '<span class="badge bg-secondary rounded-pill font-monospace">Cargado</span>'
                        : `<button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs" onclick="seleccionarSerialUnicoPos(${s.id}, '${s.numero_serial}', '${s.variante_color || ''}', ${s.almacen_id || posAlmacenActualId})"><i class="fas fa-cart-plus me-1"></i> Seleccionar</button>`
                    }
                </div>
            </div>
        `;
        $container.append(itemHtml);
    });
}

function seleccionarSerialUnicoPos(serialId, serialNumero, varianteColor, almacenId) {
    if (!productoPendienteSerial) return;
    const p = productoPendienteSerial;

    const modalEl = document.getElementById("modalSeleccionarSerialPos");
    if (modalEl) {
        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (inst) inst.hide();
    }

    agregarProductoAlCarrito(
        p.prod,
        1,
        almacenId || p.almacenId,
        p.descuento,
        varianteColor || null,
        serialId,
        serialNumero
    );

    productoPendienteSerial = null;
    $("#posInputBuscadorProducto").val("").focus();
}

let productoSeleccionadoModalDetalle = null;

function abrirModalSeleccionCantidadAlmacen(prod) {
    if (!prod) return;
    productoSeleccionadoModalDetalle = prod;

    $("#modalDetalleProdId").val(prod.id);
    $("#modalDetalleProdTipo").val(prod.tipo_item || "producto");
    $("#modalDetalleProdNombre").text(prod.nombre);
    $("#modalDetalleProdCodigo").text(`#${prod.codigo_interno || '0000'}`);
    $("#modalDetalleProdCategoria").text(prod.categoria_nombre || 'General');

    const detalUsd = parseFloat(prod.precio_detal_usd || 0);
    const mayorUsd = parseFloat(prod.precio_mayorista_usd || 0);
    $("#modalDetallePrecioDetal").text(`Detal: $${detalUsd.toFixed(2)}`);
    $("#modalDetallePrecioMayor").text(`Mayor: $${mayorUsd.toFixed(2)}`);

    $("#modalDetalleBadgeIva").text(prod.aplica_iva ? `IVA ${prod.iva_porcentaje}%` : "Exento")
        .removeClass("bg-primary-subtle text-primary bg-light text-secondary")
        .addClass(prod.aplica_iva ? "bg-primary-subtle text-primary" : "bg-light text-secondary");

    let iconHtml = '<i class="fas fa-box"></i>';
    let iconBg = 'bg-primary bg-opacity-10 text-primary';
    if (prod.tipo_item === 'moto') {
        iconHtml = '<i class="fas fa-motorcycle"></i>';
        iconBg = 'bg-warning bg-opacity-15 text-warning-emphasis';
        $("#modalDetalleContenedorMoto").show();
        $("#modalDetalleNiv").text(prod.numero_niv || '--');
        $("#modalDetalleMotor").text(prod.numero_motor || '--');
        $("#modalDetalleChasis").text(prod.numero_chasis || '--');
        $("#modalDetalleInputCantidad").val(1).prop("readonly", true);
        $("#modalDetallePresets").hide();
    } else {
        if (prod.tipo_item === 'servicio') {
            iconHtml = '<i class="fas fa-wrench"></i>';
            iconBg = 'bg-info bg-opacity-15 text-info-emphasis';
        } else if (prod.maneja_seriales) {
            iconHtml = '<i class="fas fa-tv"></i>';
            iconBg = 'bg-info bg-opacity-15 text-info-emphasis';
        } else if (prod.maneja_variantes) {
            iconHtml = '<i class="fas fa-couch"></i>';
            iconBg = 'bg-purple-subtle text-purple-emphasis';
        }
        $("#modalDetalleContenedorMoto").hide();
        $("#modalDetalleInputCantidad").val(1).prop("readonly", false);
        $("#modalDetallePresets").show();
    }
    $("#modalDetalleIcono").html(iconHtml).attr("class", `avatar-executive-sm rounded-3 ${iconBg} d-flex align-items-center justify-content-center`);

    if (prod.maneja_variantes) {
        $("#modalDetalleContenedorVariante").show();
        const $selVar = $("#modalDetalleSelectVariante");
        $selVar.empty().append('<option value="">-- Sin variante seleccionada --</option>');
        const opciones = (prod.atributos_variantes && Array.isArray(prod.atributos_variantes.opciones)) ? prod.atributos_variantes.opciones : [];
        opciones.forEach((opc) => {
            $selVar.append(`<option value="${opc}">${opc}</option>`);
        });
    } else {
        $("#modalDetalleContenedorVariante").hide();
    }

    if (prod.maneja_seriales) {
        $("#modalDetalleContenedorSerial").show();
        const $selSer = $("#modalDetalleSelectSerial");
        $selSer.empty().append('<option value="">-- Seleccionar serial disponible --</option>');
        const seriales = Array.isArray(prod.seriales_disponibles) ? prod.seriales_disponibles : [];
        seriales.forEach((s) => {
            const extraColor = s.variante_color ? ` [${s.variante_color}]` : '';
            $selSer.append(`<option value="${s.id}" data-serial="${s.numero_serial}" data-color="${s.variante_color || ''}">${s.numero_serial}${extraColor}</option>`);
        });
        $("#modalDetalleInputCantidad").val(1).prop("readonly", true);
        $("#modalDetallePresets").hide();
    } else if (prod.tipo_item !== 'moto') {
        $("#modalDetalleContenedorSerial").hide();
        $("#modalDetalleInputCantidad").prop("readonly", false);
        $("#modalDetallePresets").show();
    }

    const $selAlm = $("#modalDetalleSelectAlmacen");
    $selAlm.empty();

    if (Array.isArray(posCatalogos.almacenes)) {
        posCatalogos.almacenes.forEach((alm) => {
            let stockEnAlm = 0;
            if (Array.isArray(prod.stock_almacenes)) {
                const s = prod.stock_almacenes.find((stk) => stk.almacen_id === alm.id);
                if (s) stockEnAlm = s.cantidad_actual;
            }
            const esSeleccionado = alm.id === posAlmacenActualId;
            $selAlm.append(`<option value="${alm.id}" data-stock="${stockEnAlm}" ${esSeleccionado ? "selected" : ""}>${alm.nombre} (Disp: ${stockEnAlm} ${prod.unidad_medida || 'und'})</option>`);
        });
    }

    $("#modalDetalleInputDescuento").val(0);
    $("#modalDetalleTasa").text(formatearMonto(posTasaDia, 2));

    actualizarStockAlmacenModalDetalle();
    actualizarSubtotalModalDetalle();

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDetalleVentaProducto")).show();
    setTimeout(() => {
        $("#modalDetalleInputCantidad").focus().select();
    }, 300);
};

function actualizarStockAlmacenModalDetalle() {
    const $opt = $("#modalDetalleSelectAlmacen option:selected");
    const stock = parseFloat($opt.data("stock")) || 0;
    const unidad = productoSeleccionadoModalDetalle ? (productoSeleccionadoModalDetalle.unidad_medida || 'UND') : 'UND';

    $("#modalDetalleStockBadge").text(`${stock} ${unidad}`)
        .removeClass("bg-success bg-danger")
        .addClass(stock <= 0 && productoSeleccionadoModalDetalle?.tipo_item === 'producto' ? "bg-danger" : "bg-success");
};

function actualizarSubtotalModalDetalle() {
    if (!productoSeleccionadoModalDetalle) return;
    const prod = productoSeleccionadoModalDetalle;
    const cant = parseFloat($("#modalDetalleInputCantidad").val()) || 0;
    const desc = parseFloat($("#modalDetalleInputDescuento").val()) || 0;
    const precioUnit = posTipoVentaActual === "mayor" ? parseFloat(prod.precio_mayorista_usd || 0) : parseFloat(prod.precio_detal_usd || 0);

    const subtotalUsd = roundDecimals(cant * precioUnit * (1 - desc / 100), 2);
    const subtotalBs = roundDecimals(subtotalUsd * posTasaDia, 2);

    $("#modalDetalleSubtotalUsd").text(`$ ${subtotalUsd.toFixed(2)}`);
    $("#modalDetalleSubtotalBs").text(`Bs. ${subtotalBs.toFixed(2)}`);
};

function alterarCantidadModalDetalle(delta) {
    if (productoSeleccionadoModalDetalle?.tipo_item === 'moto' || productoSeleccionadoModalDetalle?.maneja_seriales) return;
    const actual = parseFloat($("#modalDetalleInputCantidad").val()) || 1;
    const nueva = Math.max(0.001, actual + delta);
    $("#modalDetalleInputCantidad").val(nueva);
    actualizarSubtotalModalDetalle();
};

function sumarPresetModalDetalle(cant) {
    if (productoSeleccionadoModalDetalle?.tipo_item === 'moto' || productoSeleccionadoModalDetalle?.maneja_seriales) return;
    const actual = parseFloat($("#modalDetalleInputCantidad").val()) || 0;
    $("#modalDetalleInputCantidad").val(actual + cant);
    actualizarSubtotalModalDetalle();
};

function confirmarAgregarConDetalle(e) {
    if (e) e.preventDefault();
    if (!productoSeleccionadoModalDetalle) return;

    const prod = productoSeleccionadoModalDetalle;
    const cant = parseFloat($("#modalDetalleInputCantidad").val()) || 1;
    const desc = parseFloat($("#modalDetalleInputDescuento").val()) || 0;
    const almId = parseInt($("#modalDetalleSelectAlmacen").val()) || posAlmacenActualId;

    if (cant <= 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Cantidad inválida", text: "La cantidad debe ser mayor a 0." });
        }
        return;
    }

    let varianteTexto = null;
    let productoSerialId = null;
    let serialNumero = null;

    if (prod.maneja_variantes) {
        varianteTexto = $("#modalDetalleSelectVariante").val() || null;
    }

    if (prod.maneja_seriales) {
        const $optSer = $("#modalDetalleSelectSerial option:selected");
        productoSerialId = parseInt($("#modalDetalleSelectSerial").val()) || null;
        serialNumero = $optSer.data("serial") || null;
        if (!varianteTexto && $optSer.data("color")) {
            varianteTexto = $optSer.data("color");
        }
        if (!productoSerialId) {
            if (window.notificacion) {
                window.notificacion.fire({ icon: "warning", title: "Serial Requerido", text: "Debes seleccionar el serial físico de la unidad a despachar." });
            }
            return;
        }
    }

    agregarProductoAlCarrito(prod, cant, almId, desc, varianteTexto, productoSerialId, serialNumero);

    const modalEl = document.getElementById("modalDetalleVentaProducto");
    const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
    if (modalInst) modalInst.hide();

    $("#posInputBuscadorProducto").val("").focus();
};

function agregarProductoAlCarrito(prod, cantidad = 1, almacenId = null, descuentoPorcentaje = 0, varianteTexto = null, productoSerialId = null, serialNumero = null) {
    const precioUnitUsd = posTipoVentaActual === "mayor" ? parseFloat(prod.precio_mayorista_usd || 0) : parseFloat(prod.precio_detal_usd || 0);
    const targetAlmId = almacenId || posAlmacenActualId;

    if (productoSerialId) {
        const yaExisteSerial = posCarrito.some((it) => it.producto_serial_id === productoSerialId);
        if (yaExisteSerial) {
            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "warning",
                    title: "Serial ya en Carrito",
                    text: `La unidad con serial "${serialNumero}" ya está cargada en el carrito.`,
                });
            }
            return;
        }
    }

    const idxExistente = (!productoSerialId && prod.tipo_item !== 'moto')
        ? posCarrito.findIndex((item) => item.producto_id === prod.id && item.tipo_item === prod.tipo_item && (item.variante_texto || null) === (varianteTexto || null) && item.almacen_id === targetAlmId && !item.producto_serial_id)
        : -1;

    if (idxExistente !== -1) {
        posCarrito[idxExistente].cantidad += cantidad;
        if (descuentoPorcentaje > 0) {
            posCarrito[idxExistente].descuento_porcentaje = descuentoPorcentaje;
        }
        if (almacenId) {
            posCarrito[idxExistente].almacen_id = targetAlmId;
        }
        posCarrito[idxExistente].subtotal_usd = roundDecimals(
            posCarrito[idxExistente].cantidad * posCarrito[idxExistente].precio_unitario_usd * (1 - posCarrito[idxExistente].descuento_porcentaje / 100),
            2
        );
        posCarrito[idxExistente].subtotal_bs = roundDecimals(posCarrito[idxExistente].subtotal_usd * posTasaDia, 2);
        posIndiceRenglonSeleccionado = idxExistente;
    } else {
        if (prod.tipo_item === 'moto') {
            const yaExisteMoto = posCarrito.some((it) => it.producto_id === prod.id && it.tipo_item === 'moto');
            if (yaExisteMoto) {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "warning",
                        title: "Moto ya en Carrito",
                        text: `La unidad con serial NIV "${prod.numero_niv || prod.codigo_interno}" ya está cargada en el carrito.`,
                    });
                }
                return;
            }
        }

        const subtotalUsd = roundDecimals(cantidad * precioUnitUsd * (1 - descuentoPorcentaje / 100), 2);
        const subtotalBs = roundDecimals(subtotalUsd * posTasaDia, 2);

        let stockAlm = 0;
        if (Array.isArray(prod.stock_almacenes)) {
            const stk = prod.stock_almacenes.find((s) => s.almacen_id === targetAlmId);
            if (stk) stockAlm = stk.cantidad_actual;
        }

        posCarrito.push({
            producto_id: prod.id,
            tipo_item: prod.tipo_item || "producto",
            almacen_id: targetAlmId,
            codigo: prod.codigo_interno || "--",
            nombre: prod.nombre,
            unidad: prod.unidad_medida || "UND",
            variante_texto: varianteTexto || null,
            producto_serial_id: productoSerialId || null,
            serial_numero: serialNumero || null,
            numero_niv: prod.numero_niv || null,
            numero_motor: prod.numero_motor || null,
            numero_chasis: prod.numero_chasis || null,
            cantidad: cantidad,
            precio_detal_usd: parseFloat(prod.precio_detal_usd || 0),
            precio_mayorista_usd: parseFloat(prod.precio_mayorista_usd || 0),
            precio_unitario_usd: precioUnitUsd,
            aplica_iva: !!prod.aplica_iva,
            iva_porcentaje: parseFloat(prod.iva_porcentaje || 16),
            descuento_porcentaje: descuentoPorcentaje,
            subtotal_usd: subtotalUsd,
            subtotal_bs: subtotalBs,
            stock_disponible: stockAlm,
            maneja_seriales: !!prod.maneja_seriales,
            maneja_variantes: !!prod.maneja_variantes,
        });

        posIndiceRenglonSeleccionado = posCarrito.length - 1;
    }

    renderizarCarritoPos();
    recalcularTotalesPos();
};

function actualizarCantidadItem(idx, nuevaCantidad) {
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

function alterarCantidadItem(idx, delta) {
    if (idx < 0 || idx >= posCarrito.length) return;
    const actual = posCarrito[idx].cantidad;
    actualizarCantidadItem(idx, actual + delta);
};

function eliminarItemCarrito(idx) {
    if (idx < 0 || idx >= posCarrito.length) return;
    posCarrito.splice(idx, 1);
    if (posIndiceRenglonSeleccionado === idx) {
        posIndiceRenglonSeleccionado = posCarrito.length > 0 ? posCarrito.length - 1 : null;
    }
    renderizarCarritoPos();
    recalcularTotalesPos();
};

function limpiarPantallaPos() {
    posVendedorFijoEspera = null;
    $("#cobroSelectVendedor").prop("disabled", false).val("");

    if (posCarrito.length === 0) {
        resetearClienteDefecto();
        abrirModalInicioCliente();
        return;
    }

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
                resetearClienteDefecto();
                renderizarCarritoPos();
                recalcularTotalesPos();
                abrirModalInicioCliente();
            }
        });
    } else {
        posCarrito = [];
        posIndiceRenglonSeleccionado = null;
        resetearClienteDefecto();
        renderizarCarritoPos();
        recalcularTotalesPos();
        abrirModalInicioCliente();
    }
};

function renderizarCarritoPos() {
    const $tbody = $("#contenedorFilasPos");
    $tbody.empty();

    if (posCarrito.length === 0) {
        $tbody.append(`
            <tr id="filaPosVacia">
                <td colspan="7" class="text-center py-5">
                    <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 1.8rem;">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">Carrito de Venta Vacío</h6>
                    <p class="mb-0 font-monospace" style="color: #475569; font-size: 0.86rem;">
                        Escanea un código de barras o escribe <strong class="text-primary fw-bold">*código</strong> para especificar cantidad y almacén.
                    </p>
                </td>
            </tr>
        `);
        $("#posContadorItems").html('<i class="fas fa-shopping-basket text-primary me-1"></i> 0 Ítems (0 Unid.)');
        return;
    }

    let totalUnidades = 0;

    posCarrito.forEach((item, idx) => {
        const cant = parseFloat(item.cantidad) || 0;
        const precioUnitUsd = parseFloat(item.precio_unitario_usd) || 0;
        const precioUnitBs = (precioUnitUsd * posTasaDia).toFixed(2);
        const subtotalUsd = parseFloat(item.subtotal_usd) || 0;
        const subtotalBs = parseFloat(item.subtotal_bs) || (subtotalUsd * posTasaDia);
        const aplicaIva = item.aplica_iva === true || item.aplica_iva === 'true' || item.aplica_iva === 1 || item.aplica_iva === '1';
        const ivaPorcentaje = parseFloat(item.iva_porcentaje) || 0;

        totalUnidades += cant;
        const esSeleccionado = posIndiceRenglonSeleccionado === idx;

        let itemIcon = '<i class="fas fa-box text-primary me-1"></i>';
        let badgeTipo = '';

        if (item.tipo_item === 'moto') {
            itemIcon = '<i class="fas fa-motorcycle text-warning me-1"></i>';
            badgeTipo = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace ms-1" style="font-size: 0.68rem;">Moto</span>';
        } else if (item.tipo_item === 'servicio') {
            itemIcon = '<i class="fas fa-wrench text-info me-1"></i>';
            badgeTipo = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace ms-1" style="font-size: 0.68rem;">Servicio</span>';
        }

        const badgeVariante = item.variante_texto
            ? `<span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle font-monospace ms-1" style="font-size: 0.68rem;"><i class="fas fa-palette me-1"></i>${item.variante_texto}</span>`
            : '';

        const badgeSerial = item.serial_numero
            ? `<span class="badge bg-dark text-white font-monospace ms-1" style="font-size: 0.68rem;"><i class="fas fa-barcode me-1 text-warning"></i>SN: ${item.serial_numero}</span>`
            : '';

        const cantidadHtml = (item.tipo_item === 'moto' || item.producto_serial_id)
            ? `<span class="badge ${item.tipo_item === 'moto' ? 'bg-warning-subtle text-warning-emphasis border-warning-subtle' : 'bg-info-subtle text-info-emphasis border-info-subtle'} border font-monospace px-2.5 py-1.5 fw-bold" style="font-size: 0.80rem;"><i class="fas fa-tag me-1"></i> 1 UND</span>`
            : `
                <div class="pos-qty-control">
                    <button class="pos-qty-btn" type="button" onclick="event.stopPropagation(); alterarCantidadItem(${idx}, -1);" title="Disminuir"><i class="fas fa-minus" style="font-size: 0.65rem;"></i></button>
                    <input type="number" step="any" min="0.001" class="pos-qty-input" value="${cant}" onchange="event.stopPropagation(); actualizarCantidadItem(${idx}, this.value);" onclick="event.stopPropagation(); this.select();">
                    <button class="pos-qty-btn" type="button" onclick="event.stopPropagation(); alterarCantidadItem(${idx}, 1);" title="Aumentar"><i class="fas fa-plus" style="font-size: 0.65rem;"></i></button>
                </div>
            `;

        const filaHtml = `
            <tr class="fila-pos-item ${esSeleccionado ? 'fila-activa' : ''}" data-idx="${idx}" onclick="seleccionarFilaPos(${idx})" style="cursor: pointer;">
                <!-- Código -->
                <td class="font-monospace">
                    <span class="badge bg-light text-secondary border px-2 py-1">${item.codigo}</span>
                </td>

                <!-- Producto / Descripción -->
                <td>
                    <strong class="text-dark d-block font-monospace" style="font-size: 0.90rem;">${itemIcon} ${item.nombre} ${badgeTipo} ${badgeVariante} ${badgeSerial}</strong>
                    <div class="small text-muted font-monospace" style="font-size: 0.72rem;">
                        <span>${item.unidad}</span>
                        ${item.stock_disponible !== undefined && item.tipo_item === 'producto' ? ` • <span class="${item.stock_disponible <= 0 ? 'text-danger' : 'text-success'}">Disp: ${item.stock_disponible}</span>` : ''}
                    </div>
                </td>

                <!-- Cantidad -->
                <td class="text-center font-monospace">
                    ${cantidadHtml}
                </td>

                <!-- Precio Unitario -->
                <td class="text-end font-monospace">
                    <strong class="text-primary d-block" style="font-size: 0.92rem;">$ ${precioUnitUsd.toFixed(2)}</strong>
                    <small class="badge rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                        Bs. ${precioUnitBs}
                    </small>
                </td>

                <!-- IVA -->
                <td class="text-center font-monospace small">
                    <span class="badge rounded-pill ${aplicaIva ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-secondary border'} px-2 py-1">
                        ${aplicaIva ? `IVA ${ivaPorcentaje}%` : 'Exento'}
                    </span>
                </td>

                <!-- Total Renglón -->
                <td class="text-end font-monospace">
                    <strong class="text-dark d-block" style="font-size: 0.96rem;">$ ${subtotalUsd.toFixed(2)}</strong>
                    <small class="badge rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                        Bs. ${subtotalBs.toFixed(2)}
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

function seleccionarFilaPos(idx) {
    posIndiceRenglonSeleccionado = idx;
    $(".fila-pos-item").removeClass("fila-activa table-active border-primary");
    $(`.fila-pos-item[data-idx="${idx}"]`).addClass("fila-activa");
};

function recalcularTotalesPos() {
    let subtotalNetoUsd = 0;
    let totalIvaUsd = 0;
    let totalGeneralUsd = 0;

    posCarrito.forEach((item) => {
        const subUsd = parseFloat(item.subtotal_usd) || 0;
        const ivaPct = parseFloat(item.iva_porcentaje) || 0;
        const aplicaIva = item.aplica_iva === true || item.aplica_iva === 'true' || item.aplica_iva === 1 || item.aplica_iva === '1';

        totalGeneralUsd += subUsd;
        if (aplicaIva && ivaPct > 0) {
            const factor = 1 + (ivaPct / 100);
            const baseRenglon = roundDecimals(subUsd / factor, 2);
            const ivaRenglon = roundDecimals(subUsd - baseRenglon, 2);
            subtotalNetoUsd += baseRenglon;
            totalIvaUsd += ivaRenglon;
        } else {
            subtotalNetoUsd += subUsd;
        }
    });

    subtotalNetoUsd = roundDecimals(subtotalNetoUsd, 2);
    totalIvaUsd = roundDecimals(totalIvaUsd, 2);
    totalGeneralUsd = roundDecimals(totalGeneralUsd, 2);

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

function abrirModalCobro() {
    if (posCatalogos.es_vendedor) {
        guardarPreventaDirecta();
        return;
    }

    if (!posCatalogos.turno_activo) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Estación sin Aperturar",
                text: "Debes aperturar tu caja de trabajo antes de iniciar el cobro.",
            });
        }
        abrirModalAperturaPos();
        return;
    }

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

    if (posVendedorFijoEspera) {
        $("#cobroSelectVendedor").val(posVendedorFijoEspera).prop("disabled", true);
    } else {
        $("#cobroSelectVendedor").prop("disabled", false);
    }

    const totalGeneralUsd = obtenerTotalVentaUsd();
    const totalGeneralBs = roundDecimals(totalGeneralUsd * posTasaDia, 2);

    $("#cobroModalTotalUsd").text(`$ ${totalGeneralUsd.toFixed(2)}`);
    $("#cobroModalTotalBs").text(`Bs. ${totalGeneralBs.toFixed(2)}`);

    $("#cobroCondicionPago").val("contado");
    toggleCondicionPagoCobro();

    posPagos = [];
    renderizarListaPagosCobro();

    const $primerMetodo = $("#cobroSelectMetodo option:first");
    const esBs = $primerMetodo.data("moneda") === "VES";
    $("#cobroInputMonto").val(esBs ? totalGeneralBs.toFixed(2) : totalGeneralUsd.toFixed(2));
    $("#cobroInputReferencia").val("");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCobroVenta")).show();

    setTimeout(() => {
        $("#cobroInputMonto").focus().select();
    }, 300);
};

function toggleCondicionPagoCobro() {
    const cond = $("#cobroCondicionPago").val();
    if (cond === "credito") {
        $("#contenedorDiasCreditoPos").slideDown(150);
        calcularVencimientoCobro();
    } else {
        $("#contenedorDiasCreditoPos").slideUp(150);
    }
    actualizarBalancesCobro();
};

function calcularVencimientoCobro() {
    const dias = parseInt($("#cobroDiasCredito").val()) || 15;
    const fecha = new Date();
    fecha.setDate(fecha.getDate() + dias);
    const fechaStr = fecha.toISOString().split("T")[0];
    $("#cobroFechaVenceBadge").text(`Vence: ${fechaStr}`);
};

function agregarPagoALista() {
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

    const faltante = obtenerMontoFaltanteUsd();
    if (faltante > 0) {
        const esBs = $("#cobroSelectMetodo option:selected").data("moneda") === "VES";
        $("#cobroInputMonto").val(esBs ? (faltante * posTasaDia).toFixed(2) : faltante.toFixed(2)).focus().select();
    } else {
        $("#cobroInputMonto").val("");
    }
    $("#cobroInputReferencia").val("");
};

function eliminarPagoDeLista(idx) {
    if (idx < 0 || idx >= posPagos.length) return;
    posPagos.splice(idx, 1);
    renderizarListaPagosCobro();
};

function renderizarListaPagosCobro() {
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

function obtenerTotalVentaUsd() {
    let totalGeneralUsd = 0;
    posCarrito.forEach((item) => {
        totalGeneralUsd += parseFloat(item.subtotal_usd) || 0;
    });
    return roundDecimals(totalGeneralUsd, 2);
};

function obtenerMontoFaltanteUsd() {
    const total = obtenerTotalVentaUsd();
    const pagado = posPagos.reduce((acc, p) => acc + p.monto_usd, 0);
    return Math.max(0, roundDecimals(total - pagado, 2));
};

function actualizarBalancesCobro() {
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

async function procesarVentaFinal() {
    if (!posClienteActual && posCatalogos.cliente_defecto) {
        posClienteActual = posCatalogos.cliente_defecto;
    }

    if (!posClienteActual) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Cliente requerido",
                text: "Por favor selecciona un cliente antes de procesar la venta.",
            });
        }
        return;
    }

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
        return;
    }

    if (cond === "credito" && (posClienteActual.cedula === "V-00000000" || posClienteActual.cedula === "J-00000000")) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "Cliente no válido para crédito",
                text: "No se puede otorgar crédito al cliente 'Consumidor Final'. Por favor registra o selecciona un cliente identificado.",
            });
        }
        return;
    }

    if (!posCatalogos.turno_activo) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "warning",
                title: "Turno de caja no aperturado",
                text: "Debes aperturar un turno de caja antes de procesar ventas.",
            });
        }
        abrirModalAperturaPos();
        return;
    }

    const $btn = $("#btnConfirmarVentaFinal");
    const textoOriginal = $btn.html();
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Facturando...');

    const payload = {
        cliente_id: posClienteActual.id,
        almacen_id: posAlmacenActualId,
        caja_turno_id: posCatalogos.turno_activo ? posCatalogos.turno_activo.id : null,
        vendedor_id: $("#cobroSelectVendedor").val() || null,
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
            almacen_id: it.almacen_id || posAlmacenActualId,
            variante_texto: it.variante_texto || null,
            producto_serial_id: it.producto_serial_id || null,
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
            const modalEl = document.getElementById("modalCobroVenta");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            const ventaId = res.data.id;
            const ventaCodigo = res.data.codigo;

            if (window.Swal) {
                Swal.fire({
                    icon: "success",
                    title: "¡Venta Completada!",
                    html: `
                        <div class="text-center">
                            <p class="mb-3">Comprobante <strong>#${ventaCodigo}</strong> emitido con éxito.${cond === 'credito' ? ' <br><span class="badge bg-warning text-dark">Registrado en Cuentas por Cobrar</span>' : ''}</p>
                            <p class="small text-muted mb-3">Selecciona el formato de impresión que deseas utilizar:</p>
                            <div class="d-flex flex-column gap-2">
                                <button type="button" class="btn btn-primary rounded-pill py-2 fw-bold" onclick="window.open('${urlPosImprimirCarta}/${ventaId}', '_blank'); Swal.close();">
                                    <i class="fas fa-file-invoice me-1"></i> Imprimir Factura Carta (Hoja Blanca)
                                </button>
                                <button type="button" class="btn btn-success rounded-pill py-2 fw-bold" onclick="window.open('${urlPosImprimirTicket}/${ventaId}', '_blank', 'width=400,height=600'); Swal.close();">
                                    <i class="fas fa-receipt me-1"></i> Imprimir Ticket Térmico (Tiquera)
                                </button>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: true,
                    timer: 15000,
                });
            } else {
                window.open(`${urlPosImprimirCarta}/${ventaId}`, "_blank");
            }

            posVendedorFijoEspera = null;
            $("#cobroSelectVendedor").prop("disabled", false).val("");
            posCarrito = [];
            posPagos = [];
            posIndiceRenglonSeleccionado = null;
            resetearClienteDefecto();
            renderizarCarritoPos();
            recalcularTotalesPos();
            cargarDatosInicialesPos();
            abrirModalInicioCliente();
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

async function abrirModalCuentasEspera() {
    $("#inputNotaEspera").val(posClienteActual ? `Cliente ${posClienteActual.nombre}` : "");
    await cargarListaCuentasEspera();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalCuentasEspera")).show();
};

async function guardarCarritoEnEspera() {
    if (posCarrito.length === 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Carrito Vacío", text: "No hay productos para colocar en espera." });
        }
        return;
    }

    const nota = $("#inputNotaEspera").val().trim() || (posClienteActual ? `Cliente ${posClienteActual.nombre}` : "Cuenta en espera");
    const totalUsd = obtenerTotalVentaUsd();
    const totalBs = roundDecimals(totalUsd * posTasaDia, 2);

    const payload = {
        cliente_id: posClienteActual ? posClienteActual.id : null,
        cliente: posClienteActual,
        tipo_venta: posTipoVentaActual,
        nota_referencia: nota,
        carrito: posCarrito,
        items: posCarrito,
        total_usd: totalUsd,
        total_bs: totalBs,
    };

    try {
        const res = await $.ajax({
            url: urlPosEnEsperaGuardar,
            type: "POST",
            data: JSON.stringify(payload),
            contentType: "application/json",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success) {
            posCarrito = [];
            posPagos = [];
            posIndiceRenglonSeleccionado = null;
            resetearClienteDefecto();
            renderizarCarritoPos();
            recalcularTotalesPos();
            await cargarListaCuentasEspera();
            const modalEl = document.getElementById("modalCuentasEspera");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            if (window.notificacion) {
                window.notificacion.fire({ icon: "success", title: "Venta en Espera", text: res.message });
            }
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se pudo poner en espera.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Error", text: msg });
    }
};

async function cargarListaCuentasEspera() {
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

async function recuperarCuentaEspera(id) {
    try {
        const res = await $.ajax({
            url: `${urlPosEnEsperaRecuperar}/${id}/recuperar`,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            const data = res.data;
            posVendedorFijoEspera = data.vendedor_id || null;

            if (data.cliente && typeof data.cliente === "object") {
                establecerClienteActual(data.cliente);
            } else if (data.cliente_id) {
                const cli = posCatalogos.clientes?.find((c) => c.id == data.cliente_id);
                if (cli) {
                    establecerClienteActual(cli);
                }
            }

            if (data.tipo_venta) {
                cambiarTipoVenta(data.tipo_venta);
            }

            const rawCarrito = Array.isArray(data.carrito) ? data.carrito : (Array.isArray(data.items) ? data.items : []);
            posCarrito = rawCarrito.map((item) => {
                const cant = parseFloat(item.cantidad) || 1;
                const pUnit = parseFloat(item.precio_unitario_usd) || 0;
                const pDetal = parseFloat(item.precio_detal_usd || item.precio_unitario_usd) || 0;
                const pMayor = parseFloat(item.precio_mayorista_usd || item.precio_unitario_usd) || 0;
                const desc = parseFloat(item.descuento_porcentaje) || 0;
                const subUsd = parseFloat(item.subtotal_usd) || roundDecimals(cant * pUnit * (1 - desc / 100), 2);
                const subBs = parseFloat(item.subtotal_bs) || roundDecimals(subUsd * posTasaDia, 2);
                const aplicaIva = item.aplica_iva === true || item.aplica_iva === "true" || item.aplica_iva === 1 || item.aplica_iva === "1";
                const ivaPct = parseFloat(item.iva_porcentaje) || (aplicaIva ? 16 : 0);

                return {
                    producto_id: parseInt(item.producto_id || item.id),
                    tipo_item: item.tipo_item || "producto",
                    almacen_id: parseInt(item.almacen_id) || posAlmacenActualId,
                    codigo: item.codigo || "--",
                    nombre: item.nombre || "Artículo",
                    unidad: item.unidad || "UND",
                    numero_niv: item.numero_niv || null,
                    numero_motor: item.numero_motor || null,
                    numero_chasis: item.numero_chasis || null,
                    cantidad: cant,
                    precio_detal_usd: pDetal,
                    precio_mayorista_usd: pMayor,
                    precio_unitario_usd: pUnit > 0 ? pUnit : (posTipoVentaActual === "mayor" ? pMayor : pDetal),
                    aplica_iva: aplicaIva,
                    iva_porcentaje: ivaPct,
                    descuento_porcentaje: desc,
                    subtotal_usd: subUsd,
                    subtotal_bs: subBs,
                    stock_disponible: parseFloat(item.stock_disponible) || 0,
                };
            });

            posIndiceRenglonSeleccionado = posCarrito.length > 0 ? 0 : null;

            renderizarCarritoPos();
            recalcularTotalesPos();

            const modalEl = document.getElementById("modalCuentasEspera");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "Cuenta Recuperada",
                    text: `Se recuperó el pedido de "${data.nota_referencia || 'Cliente'}" con ${posCarrito.length} ítems.`,
                });
            }
        }
    } catch (e) {
        console.error("Error al recuperar cuenta en espera:", e);
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: "No se pudo recuperar la cuenta en espera." });
        }
    }
};

async function descartarCuentaEspera(id) {
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

let facturaDevolucionActual = null;

function abrirModalDevolucion() {
    $("#devInputBusquedaFactura").val("");
    $("#contenedorDetallesFacturaDevolucion").hide();
    $("#btnConfirmarDevolucion").hide();
    facturaDevolucionActual = null;
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalDevolucion")).show();
    setTimeout(() => {
        $("#devInputBusquedaFactura").focus();
    }, 300);
};

async function buscarFacturaParaDevolucion() {
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
                    const prodNombre = det.nombre_item || (det.producto ? det.producto.nombre : (det.moto ? (det.moto.marca + ' ' + det.moto.modelo) : (det.servicio ? det.servicio.nombre : "Artículo")));
                    const precioUnit = parseFloat(det.precio_unitario_usd).toFixed(2);

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
                                <input type="number" step="any" min="0" max="${cantDisponible}" class="form-control form-control-sm text-center font-monospace fw-bold input-cant-dev" data-id="${det.id}" data-precio="${det.precio_unitario_usd}" value="${cantDisponible}" oninput="calcularTotalRenglonDevolucion(this)">
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger subtotal-dev-row">$ ${(cantDisponible * parseFloat(det.precio_unitario_usd)).toFixed(2)}</td>
                        </tr>
                    `;
                    $tbody.append(filaHtml);
                });
            }

            if (!$("#devInputMotivo").val()) {
                $("#devInputMotivo").val("Devolución de cliente en mostrador");
            }

            $("#contenedorDetallesFacturaDevolucion").slideDown(150);
            $("#btnConfirmarDevolucion").show();
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se encontró la factura.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Búsqueda Fallida", text: msg });
    }
};

function marcarTodoDevolucion() {
    $(".input-cant-dev").each(function () {
        const max = parseFloat($(this).attr("max")) || 0;
        $(this).val(max);
        calcularTotalRenglonDevolucion(this);
    });
};

function desmarcarTodoDevolucion() {
    $(".input-cant-dev").each(function () {
        $(this).val(0);
        calcularTotalRenglonDevolucion(this);
    });
};

function calcularTotalRenglonDevolucion(input) {
    const $input = $(input);
    const cant = parseFloat($input.val()) || 0;
    const precio = parseFloat($input.data("precio")) || 0;
    const subtotal = roundDecimals(cant * precio, 2);
    $input.closest("tr").find(".subtotal-dev-row").text(`$ ${subtotal.toFixed(2)}`);
};

async function ejecutarDevolucion() {
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
            const modalEl = document.getElementById("modalDevolucion");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            cargarDatosInicialesPos();
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

function abrirModalConsultaProducto() {
    $("#inputConsultaProdFiltro").val("");
    filtrarConsultaProductos();
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalConsultaProducto")).show();
    setTimeout(() => {
        $("#inputConsultaProdFiltro").focus();
    }, 300);
};

function filtrarConsultaProductos() {
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

        let stockText = `${stockTotal} ${p.unidad_medida || 'und'}`;
        let stockBadgeClass = stockTotal <= 0 && p.tipo_item === 'producto' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success';
        let tipoBadge = '';

        if (p.tipo_item === 'moto') {
            stockText = '1 UND Disp.';
            stockBadgeClass = 'bg-warning-subtle text-warning-emphasis';
            tipoBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace ms-1">Moto</span>';
        } else if (p.tipo_item === 'servicio') {
            stockText = 'Servicio';
            stockBadgeClass = 'bg-info-subtle text-info-emphasis';
            tipoBadge = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace ms-1">Servicio</span>';
        }

        const filaHtml = `
            <tr class="fila-consulta-prod" onclick="cargarProductoDesdeConsulta(${p.id}, '${p.tipo_item}')" style="cursor: pointer;">
                <td class="font-monospace"><span class="badge bg-light text-secondary border px-2 py-1">#${p.codigo_interno}</span></td>
                <td>
                    <strong class="text-dark font-monospace d-block" style="font-size: 0.88rem;">${p.nombre} ${tipoBadge}</strong>
                    <small class="text-muted font-monospace">${p.categoria_nombre || 'General'}</small>
                </td>
                <td class="text-center font-monospace">
                    <span class="badge ${stockBadgeClass} rounded-pill px-2.5 py-1">
                        ${stockText}
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
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 shadow-xs fw-bold" onclick="event.stopPropagation(); cargarProductoDesdeConsulta(${p.id}, '${p.tipo_item}')" title="Añadir a la Venta Activa">
                        <i class="fas fa-plus me-1"></i> Cargar
                    </button>
                </td>
            </tr>
        `;
        $tbody.append(filaHtml);
    });
};

function cargarProductoDesdeConsulta(id, tipoItem = 'producto') {
    const prod = posCatalogos.productos.find((p) => parseInt(p.id) === parseInt(id) && p.tipo_item === tipoItem);
    if (prod) {
        const modalEl = document.getElementById("modalConsultaProducto");
        const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (modalInst) {
            modalInst.hide();
        }
        solicitarAgregarProductoPos(prod);
        $("#posInputBuscadorProducto").val("").focus();
    }
};

function abrirModalReimprimir() {
    $("#inputCodigoReimprimir").val(posCatalogos.proximo_codigo ? `VEN-${String(Math.max(1, parseInt(posCatalogos.proximo_codigo.replace(/\D/g, '')) - 1)).padStart(5, '0')}` : "");
    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalReimprimirTicket")).show();
    setTimeout(() => {
        $("#inputCodigoReimprimir").focus().select();
    }, 300);
};

function ejecutarReimpresionTicket() {
    const cod = $("#inputCodigoReimprimir").val().trim();
    if (!cod) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Ingresa el código", text: "Por favor escribe el número o código de comprobante (Ej. VEN-00001)." });
        }
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById("modalReimprimirTicket")).hide();
    window.open(`${urlPosImprimirTicket}/${cod}`, "_blank", "width=400,height=600");
};

function ejecutarReimpresionCarta() {
    const cod = $("#inputCodigoReimprimir").val().trim();
    if (!cod) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Ingresa el código", text: "Por favor escribe el número o código de comprobante (Ej. VEN-00001)." });
        }
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById("modalReimprimirTicket")).hide();
    window.open(`${urlPosImprimirCarta}/${cod}`, "_blank");
};

function modificarRenglonSeleccionado() {
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

function configurarAtajosTecladoPos() {
    window.addEventListener("keydown", function (e) {
        const key = e.key;

        const teclasInterceptar = ["F1", "F2", "F3", "F4", "F5", "F6", "F7", "F8", "F9", "F10", "F11", "F12", "Escape", "Esc"];

        if (teclasInterceptar.includes(key)) {
            if (key !== "F5" && key !== "F12") {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            }
        }

        const modalInicioClienteAbierto = $("#modalInicioVentaCliente").hasClass("show");
        const modalCobroAbierto = $("#modalCobroVenta").hasClass("show");
        const modalDevolucionAbierto = $("#modalDevolucion").hasClass("show");
        const modalEsperaAbierto = $("#modalCuentasEspera").hasClass("show");
        const modalConsultaAbierto = $("#modalConsultaProducto").hasClass("show");
        const modalReimprimirAbierto = $("#modalReimprimirTicket").hasClass("show");
        const modalClienteAbierto = $("#modalRapidoClientePos").hasClass("show");
        const modalDetalleAbierto = $("#modalDetalleVentaProducto").hasClass("show");

        const hayModalAbierto = modalInicioClienteAbierto || modalCobroAbierto || modalDevolucionAbierto || modalEsperaAbierto || modalConsultaAbierto || modalReimprimirAbierto || modalClienteAbierto || modalDetalleAbierto || $(".modal.show").length > 0;

        switch (key) {
            case "F1":
                $("#posInputBuscadorProducto").val("").focus();
                break;

            case "F2":
                abrirModalInicioCliente();
                break;

            case "F3":
                abrirModalConsultaProducto();
                break;

            case "F4":
                if (posCatalogos.es_vendedor) {
                    guardarPreventaDirecta();
                } else if (modalCobroAbierto) {
                    procesarVentaFinal();
                } else {
                    abrirModalCobro();
                }
                break;

            case "F6":
                abrirModalCuentasEspera();
                break;

            case "F7":
                abrirModalDevolucion();
                break;

            case "F8":
                abrirModalReimprimir();
                break;

            case "F9":
                modificarRenglonSeleccionado();
                break;

            case "F10":
                limpiarPantallaPos();
                break;

            case "F11":
                if (typeof alternarPantallaCompleta === "function") {
                    alternarPantallaCompleta();
                }
                break;

            case "Escape":
            case "Esc":
                if (hayModalAbierto) {
                    $(".modal.show").each(function () {
                        const m = bootstrap.Modal.getInstance(this);
                        if (m) m.hide();
                    });
                    if (!posClienteActual) {
                        activarModoConsulta();
                    } else {
                        $("#posInputBuscadorProducto").focus();
                    }
                } else if (posClienteActual) {
                    activarModoConsulta();
                } else {
                    if (typeof urlDashboard !== "undefined") {
                        window.location.href = urlDashboard;
                    }
                }
                break;
        }
    }, { capture: true, passive: false });
};

function roundDecimals(num, decimals = 2) {
    const factor = Math.pow(10, decimals);
    return Math.round((Number(num) + Number.EPSILON) * factor) / factor;
};

let posEsperadoCierreUsd = 0;
let posEsperadoCierreBs = 0;

function renderizarCajaNavbar() {
    const $container = $("#posContainerCajaNavbar");
    if (!$container.length) return;

    $container.empty();

    if (posCatalogos.es_vendedor) {
        const asesorNombre = posCatalogos.vendedor_asociado ? posCatalogos.vendedor_asociado.nombre : 'Vendedor';
        $container.html(`
            <div class="d-flex align-items-center gap-1.5">
                <span class="badge rounded-pill bg-purple-subtle text-purple-emphasis border border-purple-subtle px-3 py-1 font-monospace fw-bold" style="font-size: 0.78rem;">
                    <i class="fas fa-clipboard-list me-1 text-purple"></i> PREVENTA - Asesor: <strong class="text-white">${asesorNombre}</strong>
                </span>
            </div>
        `);
        return;
    }

    if (posCatalogos.es_cajero) {
        if (posCatalogos.turno_activo && posCatalogos.turno_activo.caja) {
            const cajaNombre = posCatalogos.turno_activo.caja.nombre;
            const turnoId = String(posCatalogos.turno_activo.id).padStart(5, '0');
            $container.html(`
                <div class="d-flex align-items-center gap-1.5">
                    <span class="badge rounded-pill bg-dark text-white border border-secondary px-3 py-1 font-monospace fw-bold" style="font-size: 0.78rem;">
                        <i class="fas fa-cash-register text-success me-1"></i> Caja: <strong class="text-warning">${cajaNombre}</strong> <span class="opacity-75">(#${turnoId})</span>
                    </span>
                </div>
            `);
        } else {
            $container.html(`
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 font-monospace">
                    <i class="fas fa-lock me-1"></i> Sin Caja Asignada
                </span>
            `);
        }
        return;
    }

    const turnos = Array.isArray(posCatalogos.turnos_activos) ? posCatalogos.turnos_activos : [];

    if (posCatalogos.turno_activo && posCatalogos.turno_activo.caja) {
        const cajaNombre = posCatalogos.turno_activo.caja.nombre;
        const turnoId = String(posCatalogos.turno_activo.id).padStart(5, '0');

        let opcionesDropdown = '';
        if (turnos.length > 0) {
            turnos.forEach(t => {
                const cNom = t.caja ? t.caja.nombre : 'Caja';
                const tId = String(t.id).padStart(5, '0');
                const esActivo = t.id === posCatalogos.turno_activo.id;
                const iconoCheck = esActivo ? '<i class="fas fa-check text-success me-2"></i>' : '<i class="fas fa-circle text-muted me-2" style="font-size: 0.5rem;"></i>';
                opcionesDropdown += `
                    <li>
                        <a class="dropdown-item d-flex align-items-center ${esActivo ? 'fw-bold active bg-light text-dark' : ''}" href="#" onclick="seleccionarTurnoPos(${t.id}); return false;">
                            ${iconoCheck}
                            <span>${cNom} (#${tId})</span>
                        </a>
                    </li>
                `;
            });
        }

        $container.html(`
            <div class="dropdown">
                <button class="btn btn-sm btn-dark rounded-pill border border-secondary px-3 py-1 font-monospace fw-bold dropdown-toggle d-flex align-items-center gap-1.5 shadow-sm text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.78rem;">
                    <i class="fas fa-cash-register text-success"></i>
                    <span>Caja: <strong class="text-warning">${cajaNombre}</strong> <span class="opacity-75">(#${turnoId})</span></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark shadow rounded-4 py-2 font-monospace" style="font-size: 0.80rem; min-width: 220px;">
                    <li class="dropdown-header text-uppercase small text-white-50">Cajas con Turno Abierto</li>
                    ${opcionesDropdown}
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li>
                        <a class="dropdown-item text-warning" href="#" onclick="verCorteXPos(); return false;">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Ver Corte X en Vivo
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" onclick="abrirModalCierrePos(); return false;">
                            <i class="fas fa-lock me-2"></i> Cerrar Turno (Z)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider border-secondary opacity-25"></li>
                    <li>
                        <a class="dropdown-item text-success" href="#" onclick="abrirModalAperturaPos(); return false;">
                            <i class="fas fa-plus-circle me-2"></i> Aperturar otra caja
                        </a>
                    </li>
                </ul>
            </div>
        `);
    } else {
        $container.html(`
            <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs" onclick="abrirModalAperturaPos()" style="font-size: 0.76rem;">
                <i class="fas fa-key me-1"></i> Aperturar Caja
            </button>
        `);
    }
};

async function guardarPreventaDirecta() {
    if (posCarrito.length === 0) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Carrito Vacío", text: "Agrega productos antes de guardar la preventa." });
        }
        return;
    }

    if (!posClienteActual && posCatalogos.cliente_defecto) {
        posClienteActual = posCatalogos.cliente_defecto;
    }

    const asesorNombre = posCatalogos.vendedor_asociado ? posCatalogos.vendedor_asociado.nombre : "Vendedor";
    const clienteNombre = posClienteActual ? `${posClienteActual.nombre || ''} ${posClienteActual.apellido || ''}`.trim() : "Consumidor Final";
    const nota = `Preventa: ${asesorNombre} - ${clienteNombre}`;
    const totalUsd = obtenerTotalVentaUsd();
    const totalBs = roundDecimals(totalUsd * posTasaDia, 2);

    const payload = {
        cliente_id: posClienteActual ? posClienteActual.id : null,
        cliente: posClienteActual,
        tipo_venta: posTipoVentaActual,
        nota_referencia: nota,
        vendedor_id: posCatalogos.vendedor_asociado ? posCatalogos.vendedor_asociado.id : null,
        carrito: posCarrito,
        items: posCarrito,
        total_usd: totalUsd,
        total_bs: totalBs,
    };

    const $btn = $("#btnPreventaPos");
    const orig = $btn.html();
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

    try {
        const res = await $.ajax({
            url: urlPosEnEsperaGuardar,
            type: "POST",
            data: JSON.stringify(payload),
            contentType: "application/json",
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success) {
            posCarrito = [];
            posPagos = [];
            posIndiceRenglonSeleccionado = null;
            resetearClienteDefecto();
            renderizarCarritoPos();
            recalcularTotalesPos();
            if (window.notificacion) {
                window.notificacion.fire({ icon: "success", title: "Preventa Registrada", text: "La preventa fue guardada en espera para que Caja procese el cobro." });
            }
            abrirModalInicioCliente();
        }
    } catch (xhr) {
        const msg = xhr.responseJSON?.message || "No se pudo guardar la preventa.";
        if (window.notificacion) window.notificacion.fire({ icon: "error", title: "Error", text: msg });
    } finally {
        $btn.prop("disabled", false).html(orig);
    }
};

function seleccionarTurnoPos(turnoId) {
    const turnos = Array.isArray(posCatalogos.turnos_activos) ? posCatalogos.turnos_activos : [];
    const seleccionado = turnos.find(t => t.id === turnoId);
    if (seleccionado) {
        posCatalogos.turno_activo = seleccionado;
        renderizarCajaNavbar();
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "info",
                title: "Caja Activa Cambiada",
                text: `Ahora estás operando en '${seleccionado.caja ? seleccionado.caja.nombre : 'Caja'}' (Turno #${String(seleccionado.id).padStart(5, '0')}).`,
            });
        }
    }
};

async function abrirModalAperturaPos() {
    const modalEl = document.getElementById("modalAperturaTurnoPos");
    if (!modalEl) return;

    const $select = $("#posAperturaSelectCaja");
    $select.empty();

    try {
        const res = await $.get(urlCajasDisponibles);
        const cajas = res && res.data ? res.data : [];

        if (cajas.length === 0) {
            $select.append('<option value="">-- No hay cajas disponibles para aperturar --</option>');
            $("#btnConfirmarAperturaPos").prop("disabled", true);
        } else {
            $select.append('<option value="">-- Selecciona una caja disponible --</option>');
            cajas.forEach(c => {
                $select.append(`<option value="${c.id}">${c.nombre} ${c.codigo ? `(${c.codigo})` : ''}</option>`);
            });
            $("#btnConfirmarAperturaPos").prop("disabled", false);
        }
    } catch (e) {
        $select.append('<option value="">-- Error al cargar cajas --</option>');
    }

    $("#posAperturaMontoUsd").val("0.00");
    $("#posAperturaMontoBs").val("0.00");

    const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInst.show();
};

$("#formAperturaPos").on("submit", async function (e) {
    e.preventDefault();

    const cajaId = $("#posAperturaSelectCaja").val();
    if (!cajaId) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Caja requerida", text: "Debes seleccionar una caja registradora." });
        }
        return;
    }

    const payload = {
        caja_id: cajaId,
        monto_apertura_usd: parseFloat($("#posAperturaMontoUsd").val()) || 0,
        monto_apertura_bs: parseFloat($("#posAperturaMontoBs").val()) || 0,
    };

    const $btn = $("#btnConfirmarAperturaPos");
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Aperturando...');

    try {
        const res = await $.ajax({
            url: urlCajasAperturar,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success && res.data) {
            posCatalogos.turno_activo = res.data;
            if (!Array.isArray(posCatalogos.turnos_activos)) {
                posCatalogos.turnos_activos = [];
            }
            posCatalogos.turnos_activos.push(res.data);
            renderizarCajaNavbar();

            const modalEl = document.getElementById("modalAperturaTurnoPos");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            if (window.notificacion) {
                window.notificacion.fire({
                    icon: "success",
                    title: "¡Turno Aperturado!",
                    text: `La sesión de caja '${res.data.caja ? res.data.caja.nombre : ''}' ha iniciado correctamente.`,
                });
            }

            if (!posClienteActual) {
                abrirModalInicioCliente();
            }
        }
    } catch (err) {
        const msg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : "No se pudo aperturar el turno.";
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: msg });
        }
    } finally {
        $btn.prop("disabled", false).html('<i class="fas fa-door-open me-1"></i> Iniciar Turno');
    }
});

async function verCorteXPos() {
    if (!posCatalogos.turno_activo) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Sin Turno Activo", text: "No tienes ninguna sesión de caja abierta actualmente." });
        }
        abrirModalAperturaPos();
        return;
    }

    const turnoId = posCatalogos.turno_activo.id;
    $("#posBtnImprimirCorteX").attr("href", `${urlCajasBase}/turnos/${turnoId}/imprimir-x`);

    const modalEl = document.getElementById("modalCorteXPos");
    const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
    modalInst.show();

    $("#posContenidoCorteX").html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="text-muted mt-2">Consultando corte de turno...</p>
        </div>
    `);

    try {
        const res = await $.get(`${urlCajasBase}/turnos/${turnoId}/reporte-x`);
        const rep = res && res.data ? res.data : null;
        if (!rep) {
            $("#posContenidoCorteX").html('<div class="alert alert-danger">No se pudo cargar el reporte del turno.</div>');
            return;
        }

        let filasMetodos = '';
        if (rep.pagos_por_metodo && rep.pagos_por_metodo.length > 0) {
            rep.pagos_por_metodo.forEach(pm => {
                const simbolo = pm.moneda === 'USD' ? '$' : 'Bs.';
                filasMetodos += `
                    <tr>
                        <td><strong>${pm.metodo}</strong></td>
                        <td class="text-center"><span class="badge bg-light text-dark border">${pm.moneda}</span></td>
                        <td class="text-center">${pm.conteo}</td>
                        <td class="text-end fw-bold">${simbolo} ${parseFloat(pm.total_origen).toFixed(2)}</td>
                    </tr>
                `;
            });
        } else {
            filasMetodos = '<tr><td colspan="4" class="text-center text-muted py-3">Sin pagos registrados en este turno</td></tr>';
        }

        const html = `
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Caja: ${rep.caja.nombre}</h5>
                    <span class="text-muted small">Cajero: <strong>${rep.usuario.name || rep.usuario.nombre_completo}</strong> | Turno #${String(rep.turno.id).padStart(5, '0')}</span>
                </div>
                <span class="badge bg-success rounded-pill px-3 py-2 text-uppercase">EN CURSO</span>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card bg-light border-0 rounded-4 p-3 text-center">
                        <span class="text-muted small">Fondo Apertura</span>
                        <h6 class="fw-bold text-dark mb-0 mt-1">$${parseFloat(rep.monto_apertura_usd).toFixed(2)}</h6>
                        <small class="text-muted">Bs. ${parseFloat(rep.monto_apertura_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light border-0 rounded-4 p-3 text-center">
                        <span class="text-muted small">Ventas (${rep.cantidad_ventas})</span>
                        <h6 class="fw-bold text-success mb-0 mt-1">$${parseFloat(rep.total_ventas_usd).toFixed(2)}</h6>
                        <small class="text-muted">Bs. ${parseFloat(rep.total_ventas_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light border-0 rounded-4 p-3 text-center">
                        <span class="text-muted small">Devoluciones (${rep.cantidad_devoluciones})</span>
                        <h6 class="fw-bold text-danger mb-0 mt-1">-$${parseFloat(rep.total_devoluciones_usd).toFixed(2)}</h6>
                        <small class="text-muted">-Bs. ${parseFloat(rep.total_devoluciones_bs).toFixed(2)}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-primary-subtle border border-primary-subtle rounded-4 p-3 text-center">
                        <span class="text-primary small fw-semibold">Efectivo Teórico</span>
                        <h6 class="fw-bold text-primary mb-0 mt-1">$${parseFloat(rep.efectivo_esperado_usd).toFixed(2)}</h6>
                        <small class="text-primary">Bs. ${parseFloat(rep.efectivo_esperado_bs).toFixed(2)}</small>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-dark mb-2"><i class="fas fa-wallet text-muted me-1"></i> Desglose por Método de Pago</h6>
            <div class="table-responsive rounded-3 border mb-3">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Método</th>
                            <th class="text-center">Moneda</th>
                            <th class="text-center">Transacciones</th>
                            <th class="text-end">Total Recaudado</th>
                        </tr>
                    </thead>
                    <tbody>${filasMetodos}</tbody>
                </table>
            </div>
        `;

        $("#posContenidoCorteX").html(html);
    } catch (e) {
        $("#posContenidoCorteX").html('<div class="alert alert-danger">Error al consultar datos del turno.</div>');
    }
};

async function abrirModalCierrePos() {
    if (!posCatalogos.turno_activo) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "warning", title: "Sin Turno Activo", text: "No tienes ninguna sesión de caja abierta actualmente." });
        }
        abrirModalAperturaPos();
        return;
    }

    const turnoId = posCatalogos.turno_activo.id;

    try {
        const res = await $.get(`${urlCajasBase}/turnos/${turnoId}/reporte-x`);
        const rep = res && res.data ? res.data : null;
        if (!rep) return;

        posEsperadoCierreUsd = parseFloat(rep.efectivo_esperado_usd || 0);
        posEsperadoCierreBs = parseFloat(rep.efectivo_esperado_bs || 0);

        $("#posCierreResumenApertura").text(`$${parseFloat(rep.monto_apertura_usd).toFixed(2)}`);
        $("#posCierreResumenVentas").text(`$${parseFloat(rep.total_ventas_usd).toFixed(2)}`);
        $("#posCierreResumenEsperadoUsd").text(`$${posEsperadoCierreUsd.toFixed(2)}`);
        $("#posCierreResumenEsperadoBs").text(`Bs. ${posEsperadoCierreBs.toFixed(2)}`);

        $("#posCierreMontoUsd").val(posEsperadoCierreUsd.toFixed(2));
        $("#posCierreMontoBs").val(posEsperadoCierreBs.toFixed(2));
        $("#posCierreObservaciones").val("");

        calcularDiferenciasCierrePos();

        const modalEl = document.getElementById("modalCierreZPos");
        const modalInst = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInst.show();
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: "No se pudieron calcular los totales para el cierre." });
        }
    }
};

function calcularDiferenciasCierrePos() {
    const contadoUsd = parseFloat($("#posCierreMontoUsd").val() || 0);
    const contadoBs = parseFloat($("#posCierreMontoBs").val() || 0);

    const difUsd = contadoUsd - posEsperadoCierreUsd;
    const difBs = contadoBs - posEsperadoCierreBs;

    const $alertaUsd = $("#posCierreAlertaUsd");
    const $alertaBs = $("#posCierreAlertaBs");

    if (Math.abs(difUsd) < 0.001) {
        $alertaUsd.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Cuadre Exacto en USD ($0.00)</span>');
    } else if (difUsd > 0) {
        $alertaUsd.html(`<span class="text-success fw-bold"><i class="fas fa-plus-circle me-1"></i>Sobrante: +$${difUsd.toFixed(2)}</span>`);
    } else {
        $alertaUsd.html(`<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Faltante: -$${Math.abs(difUsd).toFixed(2)}</span>`);
    }

    if (Math.abs(difBs) < 0.001) {
        $alertaBs.html('<span class="text-success"><i class="fas fa-check-circle me-1"></i>Cuadre Exacto en Bs. (Bs. 0.00)</span>');
    } else if (difBs > 0) {
        $alertaBs.html(`<span class="text-success fw-bold"><i class="fas fa-plus-circle me-1"></i>Sobrante: +Bs. ${difBs.toFixed(2)}</span>`);
    } else {
        $alertaBs.html(`<span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Faltante: -Bs. ${Math.abs(difBs).toFixed(2)}</span>`);
    }
};

$("#posCierreMontoUsd, #posCierreMontoBs").on("input", function () {
    calcularDiferenciasCierrePos();
});

$("#formCierreZPos").on("submit", async function (e) {
    e.preventDefault();
    if (!posCatalogos.turno_activo) return;

    const turnoId = posCatalogos.turno_activo.id;
    const payload = {
        monto_cierre_usd: parseFloat($("#posCierreMontoUsd").val()) || 0,
        monto_cierre_bs: parseFloat($("#posCierreMontoBs").val()) || 0,
        observaciones: $("#posCierreObservaciones").val() || null,
    };

    const $btn = $("#btnConfirmarCierrePos");
    $btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cerrando Turno...');

    try {
        const res = await $.ajax({
            url: `${urlCajasBase}/turnos/${turnoId}/cerrar`,
            type: "POST",
            data: payload,
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });

        if (res.success) {
            const modalEl = document.getElementById("modalCierreZPos");
            if (modalEl) {
                const modalInst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (modalInst) modalInst.hide();
            }

            posCatalogos.turno_activo = null;
            renderizarCajaNavbar();

            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Cierre Z Completado!',
                    text: 'El arqueo físico y cierre de turno se registraron exitosamente.',
                    showCancelButton: true,
                    confirmButtonText: '🖨️ Imprimir Ticket Z',
                    cancelButtonText: 'Finalizar',
                    confirmButtonColor: '#0f172a',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.open(`${urlCajasBase}/turnos/${turnoId}/imprimir-z`, '_blank');
                    }
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        }
    } catch (err) {
        const msg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : "No se pudo cerrar el turno.";
        if (window.notificacion) {
            window.notificacion.fire({ icon: "error", title: "Error", text: msg });
        }
    } finally {
        $btn.prop("disabled", false).html('<i class="fas fa-lock me-1"></i> Confirmar y Cerrar Turno');
    }
});
