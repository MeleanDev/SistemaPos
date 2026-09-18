const urlBase = window.location.href.split("?")[0].replace(/\/$/, "");
const urlTasa = urlBase + "/tasa-actual";
const urlBuscarPaciente = urlBase + "/buscar-paciente";
const urlCrearPaciente = urlBase + "/crear-paciente";
const urlBuscarServicio = urlBase + "/buscar-servicio";
const urlServiciosSugeridos = urlBase + "/servicios-sugeridos";
const urlProcesar = urlBase + "/procesar";
const urlValidarReferencia = urlBase + "/validar-referencia";

let estadoApp = {
    tasaBcv: 0,
    pacienteActual: null,
    convenioId: null,
    carrito: [],
    pagos: [],
    subtotal: 0,
    totalUsd: 0,
    totalBs: 0,
    abonadoUsd: 0,
};

$(document).ready(function () {
    inicializar();
    configurarAtajosTeclado();
    configurarEventosDOM();
});

// Helper para formatear montos en formato de moneda (separador de miles y 2 decimales)
function formatearMonto(monto) {
    let num = parseFloat(monto);
    if (isNaN(num)) num = 0;
    return num.toLocaleString("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function inicializar() {
    // Obtener Tasa Actual
    $.get(urlTasa, function (res) {
        if (res.success && res.tasa > 0) {
            estadoApp.tasaBcv = parseFloat(res.tasa);
            $("#display-tasa-bcv").text(`Bs. ${formatearMonto(estadoApp.tasaBcv)}`);
            actualizarTotales(); // Recalculate if anything was there
        } else {
            notificacion.fire({
                icon: "warning",
                title: "Atención",
                text: "No hay Tasa BCV configurada.",
            });
        }
    });
}

function configurarAtajosTeclado() {
    $(document).on("keydown", function (e) {
        // F2 o Alt+P: Buscar Paciente
        if (e.key === "F2" || (e.altKey && e.key.toLowerCase() === "p")) {
            e.preventDefault();
            $("#buscar-cedula").focus();
        }
        // F4 o Alt+S: Buscar Servicio (solo si hay paciente)
        if (e.key === "F4" || (e.altKey && e.key.toLowerCase() === "s")) {
            e.preventDefault();
            if (!estadoApp.pacienteActual) {
                notificacion.fire({
                    icon: "info",
                    title: "Paso previo requerido",
                    text: "Primero seleccione un paciente (F2).",
                });
                $("#buscar-cedula").focus();
                return;
            }
            $("#buscar-servicio").focus();
        }
        // F8 o Alt+C: Cobrar
        if (e.key === "F8" || (e.altKey && e.key.toLowerCase() === "c")) {
            e.preventDefault();
            abrirModalPago();
        }
        // Esc: Cancelar/Cerrar modales o limpiar busquedas
        if (e.key === "Escape") {
            $("#lista-resultados-servicios").addClass("d-none");
        }
    });

    // Guardar paciente con Ctrl+Enter
    $("#modalCrearPaciente").on("keydown", function (e) {
        if (e.ctrlKey && e.key === "Enter") {
            guardarNuevoPaciente();
        }
    });

    // Toggle menor de edad en ingreso
    $("#nuevo-paciente-es-menor").on("change", function () {
        toggleIngresoMenor(this.checked);
    });
}

function configurarEventosDOM() {
    // ------------------ PACIENTE ------------------
    let timerBuscarPaciente;
    let indiceSeleccionPaciente = -1;

    $("#buscar-cedula").on("keyup", function (e) {
        // Ignorar teclas de navegacion
        if (
            e.key === "ArrowDown" ||
            e.key === "ArrowUp" ||
            e.key === "Enter" ||
            e.key === "Escape"
        )
            return;

        clearTimeout(timerBuscarPaciente);
        let q = $(this).val().trim();
        let $lista = $("#lista-resultados-pacientes");

        if (q.length >= 2) {
            timerBuscarPaciente = setTimeout(() => {
                $.get(urlBuscarPaciente, { q: q }, function (res) {
                    $lista.empty();
                    indiceSeleccionPaciente = -1;
                    if (res.length > 0) {
                        res.forEach((item) => {
                            let nombreComp = `${item.nombreUno} ${item.apellidoUno}`;
                            let html = `
                                <button type="button" class="list-group-item list-group-item-action item-paciente" onclick='seleccionarPaciente(${JSON.stringify(item).replace(/'/g, "&apos;")})'>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold"><i class="fas fa-user text-muted me-2"></i> ${nombreComp}</span>
                                        <span class="badge bg-light text-dark border">C.I: ${item.cedula || "N/A"}</span>
                                    </div>
                                </button>
                            `;
                            $lista.append(html);
                        });
                        $lista.append(`
                            <button type="button" class="list-group-item list-group-item-action text-primary text-center fw-bold bg-light mt-1" onclick='abrirCrearPaciente()'>
                                <i class="fas fa-plus-circle me-1"></i> Registrar Nuevo Paciente
                            </button>
                        `);
                        $lista.removeClass("d-none");
                    } else {
                        $lista.html(`
                            <div class="list-group-item text-muted small text-center">No se encontraron pacientes.</div>
                            <button type="button" class="list-group-item list-group-item-action text-primary text-center fw-bold bg-light" onclick='abrirCrearPaciente()'>
                                <i class="fas fa-plus-circle me-1"></i> Registrar Nuevo Paciente
                            </button>
                        `);
                        $lista.removeClass("d-none");
                    }
                });
            }, 300);
        } else {
            $lista.addClass("d-none");
            $("#panel-nuevo-paciente").addClass("d-none"); // Aunque ahora usamos modal, por si acaso
        }
    });

    // Navegacion teclado en paciente
    $("#buscar-cedula").on("keydown", function (e) {
        let $lista = $("#lista-resultados-pacientes");
        if ($lista.hasClass("d-none")) return;

        let $items = $lista.find("button");
        if (e.key === "ArrowDown") {
            e.preventDefault();
            indiceSeleccionPaciente++;
            if (indiceSeleccionPaciente >= $items.length)
                indiceSeleccionPaciente = 0;
            $items
                .removeClass("active text-white")
                .eq(indiceSeleccionPaciente)
                .addClass("active text-white");
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            indiceSeleccionPaciente--;
            if (indiceSeleccionPaciente < 0)
                indiceSeleccionPaciente = $items.length - 1;
            $items
                .removeClass("active text-white")
                .eq(indiceSeleccionPaciente)
                .addClass("active text-white");
        } else if (e.key === "Enter") {
            e.preventDefault();
            if (indiceSeleccionPaciente >= 0) {
                $items.eq(indiceSeleccionPaciente).click();
            } else {
                abrirCrearPaciente(); // Si presiona Enter sin seleccionar, va a crear paciente
            }
        } else if (e.key === "Escape") {
            $lista.addClass("d-none");
        }
    });

    // Botones de paciente
    $("#btn-guardar-paciente").on("click", guardarNuevoPaciente);
    $("#btn-abrir-modal-paciente").on("click", abrirCrearPaciente);

    // Cambiar paciente
    $("#panel-paciente-seleccionado button").on("click", function () {
        estadoApp.pacienteActual = null;
        estadoApp.convenioId = null;
        $("#panel-paciente-seleccionado").addClass("d-none");
        $("#buscar-cedula").val("").prop("disabled", false).focus();
        $("#bloque-catalogo").css({ opacity: "0.5", "pointer-events": "none" });
        $("#bloque-ticket").css({ opacity: "0.5", "pointer-events": "none" });
        actualizarAccesoRapido(null);
    });

    // ------------------ CATALOGO ------------------
    let timerBuscarServicio;
    let indiceSeleccionServicio = -1;

    $("#buscar-servicio").on("keyup", function (e) {
        if (
            e.key === "ArrowDown" ||
            e.key === "ArrowUp" ||
            e.key === "Enter" ||
            e.key === "Escape"
        )
            return;

        clearTimeout(timerBuscarServicio);
        let q = $(this).val().trim();
        let $lista = $("#lista-resultados-servicios");

        if (q.length >= 2) {
            timerBuscarServicio = setTimeout(() => {
                $.get(urlBuscarServicio, { q: q, convenio_id: estadoApp.convenioId }, function (res) {
                    $lista.empty();
                    indiceSeleccionServicio = -1;
                    if (res.length > 0) {
                        res.forEach((item) => {
                            let icon = "fa-vial";
                            let badgeStyle = "background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;";
                            let badgeTexto = "EXAMEN";

                            if (item.tipo === "perfil") {
                                icon = "fa-folder-open";
                                badgeStyle = "background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;";
                                badgeTexto = "PERFIL";
                            } else if (item.tipo === "servicio") {
                                icon = "fa-tag";
                                badgeStyle = "background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7;";
                                badgeTexto = "SERVICIO";
                            } else if (item.tipo === "producto") {
                                icon = "fa-box";
                                badgeStyle = "background-color: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff;";
                                badgeTexto = "PRODUCTO";
                            }

                            let html = `
                                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3" onclick='agregarAlCarrito(${JSON.stringify(item).replace(/'/g, "&apos;")})'>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge rounded-pill px-2.5 py-1 small fw-bold" style="${badgeStyle}">
                                            <i class="fas ${icon} me-1"></i>${badgeTexto}
                                        </span>
                                        <span class="fw-semibold text-dark">${item.nombre}</span>
                                    </div>
                                    <span class="fw-bold text-success font-monospace">$${formatearMonto(item.precio)}</span>
                                </button>
                            `;
                            $lista.append(html);
                        });
                        $lista.removeClass("d-none");
                    } else {
                        $lista.html(
                            `<div class="list-group-item text-muted small text-center">No se encontraron servicios.</div>`,
                        );
                        $lista.removeClass("d-none");
                    }
                });
            }, 300);
        } else {
            $lista.addClass("d-none");
        }
    });

    // Navegacion teclado en servicios
    $("#buscar-servicio").on("keydown", function (e) {
        let $lista = $("#lista-resultados-servicios");
        if ($lista.hasClass("d-none")) return;

        let $items = $lista.find("button");
        if (e.key === "ArrowDown") {
            e.preventDefault();
            indiceSeleccionServicio++;
            if (indiceSeleccionServicio >= $items.length)
                indiceSeleccionServicio = 0;
            $items
                .removeClass("active text-white")
                .eq(indiceSeleccionServicio)
                .addClass("active text-white");
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            indiceSeleccionServicio--;
            if (indiceSeleccionServicio < 0)
                indiceSeleccionServicio = $items.length - 1;
            $items
                .removeClass("active text-white")
                .eq(indiceSeleccionServicio)
                .addClass("active text-white");
        } else if (e.key === "Enter") {
            e.preventDefault();
            if (indiceSeleccionServicio >= 0) {
                $items.eq(indiceSeleccionServicio).click();
            } else if ($items.length > 0) {
                // Si presiona enter sin seleccionar, escoge el primero
                $items.eq(0).click();
            }
        } else if (e.key === "Escape") {
            $lista.addClass("d-none");
        }
    });

    // Ocultar lista al perder foco (con retardo para permitir el click)
    $("#buscar-servicio").on("blur", function () {
        setTimeout(
            () => $("#lista-resultados-servicios").addClass("d-none"),
            200,
        );
    });

    // ------------------ PAGOS ------------------
    $("#btn-abrir-pago").on("click", abrirModalPago);

    // Cambiar moneda base segun metodo
    $("#pago-metodo").on("change", function () {
        let moneda = $(this).find(":selected").data("moneda"); // usd o bs
        $("#pago-monto").val("");

        if (moneda === "bs") {
            $("#lbl-pago-monto").text("Monto (Bs)");
            $("#pago-monto-bs").text("Equiv: $0.00");
        } else {
            $("#lbl-pago-monto").text("Monto (USD)");
            $("#pago-monto-bs").text("Bs. 0.00");
        }
    });

    // Conversion en vivo
    $("#pago-monto").on("keyup change", function () {
        let moneda = $("#pago-metodo").find(":selected").data("moneda");
        let v = parseFloat($(this).val());
        if (isNaN(v) || v < 0) v = 0;

        if (moneda === "bs") {
            let eqUsd = v / estadoApp.tasaBcv;
            $("#pago-monto-bs").text(`Equiv: $${formatearMonto(eqUsd)}`);
        } else {
            let eqBs = v * estadoApp.tasaBcv;
            $("#pago-monto-bs").text(`Bs. ${formatearMonto(eqBs)}`);
        }
    });

    $("#btn-agregar-pago").on("click", function () {
        let $metodoSel = $("#pago-metodo").find(":selected");
        let metodo = $metodoSel.val();
        let moneda = $metodoSel.data("moneda");
        let montoInput = parseFloat($("#pago-monto").val());
        let ref = $("#pago-referencia").val().trim();

        if (isNaN(montoInput) || montoInput < 0.01) {
            notificacion.fire({
                icon: "warning",
                title: "Monto inválido",
                text: "El monto debe ser mayor a 0.",
            });
            return;
        }

        if (!ref && metodo !== "Efectivo (USD)" && metodo !== "Efectivo (Bs)") {
            notificacion.fire({
                icon: "warning",
                title: "Referencia requerida",
                text: "Por favor ingrese el número de referencia del pago.",
            });
            $("#pago-referencia").focus();
            return;
        }

        if (ref) {
            let existeLocal = estadoApp.pagos.find(p => p.referencia === ref);
            if (existeLocal) {
                notificacion.fire({ icon: "warning", title: "Referencia duplicada", text: "Esta referencia ya fue ingresada en los pagos actuales." });
                return;
            }
        }

        let btn = $(this);
        btn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin"></i>');

        let procesarMonto = function() {
            let restanteUsd = estadoApp.totalUsd - estadoApp.abonadoUsd;
            let montoUsd = 0;
            let montoBs = 0;

            if (moneda === "bs") {
                montoBs = montoInput;
                montoUsd = montoInput / estadoApp.tasaBcv;
            } else {
                montoUsd = montoInput;
                montoBs = montoInput * estadoApp.tasaBcv;
            }

            if (montoUsd > (restanteUsd + 0.005)) {
                montoUsd = restanteUsd;
                montoBs = montoUsd * estadoApp.tasaBcv;

                if (moneda === "bs") {
                    $("#pago-monto").val(montoBs.toFixed(2));
                } else {
                    $("#pago-monto").val(montoUsd.toFixed(2));
                }

                $("#pago-monto").trigger("change");
                notificacion.fire({
                    icon: "info",
                    title: "Monto ajustado",
                    text: "El pago no puede exceder la deuda restante.",
                });
                btn.prop("disabled", false).html('<i class="fas fa-plus"></i> Añadir');
                return;
            }

            estadoApp.pagos.push({
                metodo_pago: metodo,
                monto_usd: montoUsd,
                monto_bs: montoBs,
                referencia: ref,
            });

            $("#pago-monto").val("");
            $("#pago-referencia").val("");
            $("#pago-monto-bs").text(moneda === "bs" ? "Equiv: $0.00" : "Bs. 0.00");
            $("#pago-metodo").focus();
            actualizarModalPagos();
            btn.prop("disabled", false).html('<i class="fas fa-plus"></i> Añadir');
        };

        if (ref) {
            $.post({
                url: urlValidarReferencia,
                data: { referencia: ref, _token: $('meta[name="csrf-token"]').attr('content') },
                success: function(res) {
                    if (res.existe) {
                        notificacion.fire({ icon: "warning", title: "Referencia duplicada", text: "Esta referencia ya está registrada en otro pago anterior del sistema." });
                        btn.prop("disabled", false).html('<i class="fas fa-plus"></i> Añadir');
                    } else {
                        procesarMonto();
                    }
                },
                error: function() {
                    notificacion.fire({ icon: "error", title: "Error", text: "Hubo un error al validar la referencia." });
                    btn.prop("disabled", false).html('<i class="fas fa-plus"></i> Añadir');
                }
            });
        } else {
            procesarMonto();
        }
    });

    $("#check-credito").on("change", function () {
        validarEstadoFacturacion();
    });

    $("#btn-finalizar-factura").on("click", finalizarFactura);
}

// ================= FUNCIONES =================

function toggleIngresoMenor(activo) {
    if (activo) {
        $("#nuevo-paciente-tipo-cedula, #nuevo-paciente-cedula").prop("disabled", true).val("");
        $("#ingreso-bloque-representante").removeClass("d-none");
        $("#nuevo-paciente-nombre-rep, #nuevo-paciente-cedula-rep").prop("disabled", false);
        $("#ingreso-label-contacto").text("Contacto del Representante");
        $("#ingreso-label-correo").text("Correo del Representante");
        $("#ingreso-label-telefono").text("Teléfono del Representante");
    } else {
        $("#nuevo-paciente-tipo-cedula, #nuevo-paciente-cedula").prop("disabled", false);
        $("#ingreso-bloque-representante").addClass("d-none");
        $("#nuevo-paciente-nombre-rep, #nuevo-paciente-cedula-rep").prop("disabled", true).val("");
        $("#nuevo-paciente-tipo-cedula-rep").val("V-");
        $("#ingreso-label-contacto").text("Contacto y Ubicación");
        $("#ingreso-label-correo").text("Correo Electrónico");
        $("#ingreso-label-telefono").text("Teléfono");
    }
}

function abrirCrearPaciente() {
    $("#lista-resultados-pacientes").addClass("d-none");

    // Limpiar todos los inputs y selects del modal
    $("#modalCrearPaciente input").not("#nuevo-paciente-es-menor").val("");
    $("#modalCrearPaciente select").prop("selectedIndex", 0);

    // Resetear toggle menor
    $("#nuevo-paciente-es-menor").prop("checked", false);
    toggleIngresoMenor(false);

    // Si escribió puros números, asumir que es cédula
    let q = $("#buscar-cedula").val().trim();
    if (/^\d+$/.test(q)) {
        $("#nuevo-paciente-cedula").val(q);
    } else {
        $("#nuevo-paciente-nombreUno").val(q);
    }

    var modal = new bootstrap.Modal(
        document.getElementById("modalCrearPaciente"),
    );
    modal.show();
    setTimeout(() => {
        if ($("#nuevo-paciente-nombreUno").val() === "") {
            $("#nuevo-paciente-nombreUno").focus();
        } else {
            $("#nuevo-paciente-apellidoUno").focus();
        }
    }, 500);
}

function seleccionarPaciente(paciente) {
    estadoApp.pacienteActual = paciente;
    estadoApp.convenioId = paciente.convenio_id ? parseInt(paciente.convenio_id) : null;

    // UI
    $("#buscar-cedula").val("").prop("disabled", true);
    $("#panel-nuevo-paciente").addClass("d-none");
    $("#lista-resultados-pacientes").addClass("d-none");

    let nombreComp =
        `${paciente.nombreUno || ""} ${paciente.apellidoUno || ""}`.trim();
    $("#txt-paciente-nombre").text(nombreComp || "Sin Nombre");

    // Mostrar cédula o código de registro para menores
    let identificador = paciente.cedula
        ? paciente.cedula
        : (paciente.codigo_registro ? `👶 ${paciente.codigo_registro}` : "Sin Doc.");
    $("#txt-paciente-ci").text(identificador);
    $("#txt-paciente-tel").text(paciente.telefono || "N/A");
    $("#pos-convenio-select").val(paciente.convenio_id || "");
    $("#panel-paciente-seleccionado").removeClass("d-none");

    // Desbloquear Catalogo y Ticket
    $("#bloque-catalogo").css({ opacity: "1", "pointer-events": "auto" });
    $("#bloque-ticket").css({ opacity: "1", "pointer-events": "auto" });

    // Actualizar botones de Acceso Rápido con el tarifario del paciente
    actualizarAccesoRapido(estadoApp.convenioId);

    // Si ya había items en el carrito antes de seleccionar paciente, actualizar precios
    if (estadoApp.carrito.length > 0) {
        cambiarConvenioOrden(estadoApp.convenioId, false);
    }

    $("#buscar-servicio").focus();
}

window.cambiarConvenioOrden = async function (nuevoConvenioId, mostrarAviso = true) {
    estadoApp.convenioId = nuevoConvenioId ? parseInt(nuevoConvenioId) : null;

    // Actualizar botones de Acceso Rápido
    actualizarAccesoRapido(estadoApp.convenioId);

    if (estadoApp.carrito.length > 0) {
        for (let item of estadoApp.carrito) {
            if (item.tipo === "examen" || item.tipo === "perfil") {
                try {
                    const res = await $.get(urlBuscarServicio, {
                        q: item.nombre,
                        convenio_id: estadoApp.convenioId,
                    });
                    const match = res.find(
                        (s) => s.id === item.id && s.tipo === item.tipo,
                    );
                    if (match) {
                        item.precio = match.precio;
                        item.precio_base = match.precio_base;
                    }
                } catch (e) {
                    console.error("Error al actualizar precio de item:", e);
                }
            }
        }
        renderizarCarrito();

        if (mostrarAviso) {
            notificacion.fire({
                icon: "info",
                title: "Tarifario aplicado",
                text: "Los precios de la orden han sido recalculados.",
                timer: 1500,
                showConfirmButton: false,
            });
        }
    }
};

window.editarPrecioItem = function (index) {
    let item = estadoApp.carrito[index];
    if (!item) return;

    Swal.fire({
        title: `Modificar Precio`,
        html: `Ajustar precio para <strong class="text-primary">${item.nombre}</strong><br><small class="text-muted">Precio base estándar: $${formatearMonto(item.precio_base || item.precio)}</small>`,
        input: "number",
        inputValue: item.precio,
        inputAttributes: {
            step: "0.01",
            min: "0",
            style: "text-align: center; font-size: 1.25rem; font-weight: bold;",
        },
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check me-1"></i> Aplicar Precio',
        cancelButtonText: "Cancelar",
        customClass: {
            confirmButton: "rounded-pill px-4",
            cancelButton: "rounded-pill px-4",
        },
    }).then((res) => {
        if (res.isConfirmed && res.value !== "") {
            let nuevoPrecio = parseFloat(res.value);
            if (!isNaN(nuevoPrecio) && nuevoPrecio >= 0) {
                item.precio = nuevoPrecio;
                item.precio_personalizado = true;
                renderizarCarrito();
            }
        }
    });
};

function guardarNuevoPaciente() {
    const esMenor = $("#nuevo-paciente-es-menor").is(":checked");

    let datos = {
        nombreUno: $("#nuevo-paciente-nombreUno").val().trim(),
        nombreDos: $("#nuevo-paciente-nombreDos").val().trim(),
        apellidoUno: $("#nuevo-paciente-apellidoUno").val().trim(),
        apellidoDos: $("#nuevo-paciente-apellidoDos").val().trim(),
        fecha_nacimiento: $("#nuevo-paciente-fecha").val(),
        sexo: $("#nuevo-paciente-sexo").val(),
        correo: $("#nuevo-paciente-correo").val().trim(),
        cod_tel: $("#nuevo-paciente-cod-tel").val(),
        telefono_numero: $("#nuevo-paciente-telefono").val().trim(),
        cod_emerg: $("#nuevo-paciente-cod-emerg").val(),
        emergencia_numero: $("#nuevo-paciente-emergencia").val().trim(),
        direccion: $("#nuevo-paciente-direccion").val().trim(),
        convenio_id: $("#nuevo-paciente-convenio").val() || null,
    };

    // Campos condicionales por tipo de paciente
    if (esMenor) {
        datos.es_menor = 1;
        datos.nombre_representante = $("#nuevo-paciente-nombre-rep").val().trim();
        datos.cedula_representante =
            $("#nuevo-paciente-tipo-cedula-rep").val() +
            $("#nuevo-paciente-cedula-rep").val().trim();
    } else {
        datos.tipo_cedula = $("#nuevo-paciente-tipo-cedula").val();
        datos.cedula_numero = $("#nuevo-paciente-cedula").val().trim();
        datos.cedula = datos.tipo_cedula + datos.cedula_numero;
    }

    datos.telefono = datos.telefono_numero
        ? datos.cod_tel + datos.telefono_numero
        : "";
    datos.telefono_emergencia = datos.emergencia_numero
        ? datos.cod_emerg + datos.emergencia_numero
        : "";

    // Validaciones base
    if (!datos.nombreUno || !datos.apellidoUno || !datos.fecha_nacimiento ||
        !datos.sexo || !datos.telefono_numero || !datos.direccion) {
        notificacion.fire({
            icon: "warning",
            title: "Faltan datos requeridos",
            text: "Por favor completa todos los campos obligatorios (*).",
        });
        return;
    }

    // Validaciones específicas
    if (!esMenor && !datos.cedula_numero) {
        notificacion.fire({ icon: "warning", title: "Ingrese la cédula del paciente" });
        return;
    }
    if (esMenor && !datos.nombre_representante) {
        notificacion.fire({ icon: "warning", title: "Ingrese el nombre del representante" });
        return;
    }
    if (esMenor && $("#nuevo-paciente-tipo-cedula-rep").val() !== "X-" && $("#nuevo-paciente-cedula-rep").val().trim().length < 6) {
        notificacion.fire({ icon: "warning", title: "Ingrese la cédula del representante" });
        return;
    }

    $.post({
        url: urlCrearPaciente,
        data: datos,
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        success: function (res) {
            if (res.success) {
                let modalEl = document.getElementById("modalCrearPaciente");
                let modalObj = bootstrap.Modal.getInstance(modalEl);
                if (modalObj) modalObj.hide();
                seleccionarPaciente(res.paciente);
            } else {
                notificacion.fire({ icon: "error", title: "Error", text: res.message });
            }
        },
        error: function (xhr) {
            let msg = xhr.responseJSON?.message || "Error al registrar paciente";
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                msg = Object.values(xhr.responseJSON.errors).map(e => e.join("\n")).join("\n");
            }
            notificacion.fire({ icon: "error", title: "Error", text: msg });
        },
    });
}

window.agregarAlCarrito = async function (item) {
    let duplicado = estadoApp.carrito.find(
        (c) => c.id === item.id && c.tipo === item.tipo,
    );
    if (duplicado) {
        notificacion.fire({
            icon: "warning",
            title: "Servicio duplicado",
            text: "Este servicio ya está en la orden.",
        });
        return;
    }

    let precioBase = item.precio_base !== undefined ? parseFloat(item.precio_base) : parseFloat(item.precio);
    let precioFinal = parseFloat(item.precio);

    // Si hay un tarifario / convenio activo en la orden, asegurar que el precio corresponda al convenio
    if (estadoApp.convenioId && (item.tipo === "examen" || item.tipo === "perfil")) {
        try {
            const res = await $.get(urlBuscarServicio, {
                q: item.nombre,
                convenio_id: estadoApp.convenioId,
            });
            const match = res.find(
                (s) => s.id === item.id && s.tipo === item.tipo,
            );
            if (match) {
                precioFinal = parseFloat(match.precio);
                precioBase = parseFloat(match.precio_base);
            }
        } catch (e) {
            console.error("Error al resolver precio de convenio para acceso rápido:", e);
        }
    }

    estadoApp.carrito.push({
        ...item,
        precio_base: precioBase,
        precio: precioFinal,
    });
    $("#buscar-servicio").val("").focus();
    $("#lista-resultados-servicios").addClass("d-none");
    renderizarCarrito();
};

function actualizarAccesoRapido(convenioId) {
    $.get(urlServiciosSugeridos, { convenio_id: convenioId }, function (res) {
        if (!res || res.length === 0) return;

        let $contenedor = $("#contenedor-acceso-rapido");
        if ($contenedor.length === 0) return;

        $contenedor.empty();
        res.forEach((s) => {
            let iconClass = 'fa-vial text-primary';
            if (s.tipo === 'perfil') iconClass = 'fa-folder-open text-warning';
            else if (s.tipo === 'servicio') iconClass = 'fa-tag text-info';
            else if (s.tipo === 'producto') iconClass = 'fa-box text-primary';

            let jsonStr = JSON.stringify(s).replace(/'/g, "&apos;");
            let html = `
                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 shadow-xs fw-semibold text-secondary d-flex align-items-center gap-1"
                    onclick='agregarAlCarrito(${jsonStr})'>
                    <i class="fas ${iconClass} small"></i>
                    <span>${s.nombre}</span>
                    <span class="badge rounded-pill ms-1 font-monospace fw-bold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">$${formatearMonto(s.precio)}</span>
                </button>
            `;
            $contenedor.append(html);
        });
    });
}

window.eliminarDelCarrito = function (index) {
    estadoApp.carrito.splice(index, 1);
    renderizarCarrito();
};

function renderizarCarrito() {
    let $tbody = $("#carrito-body");
    $tbody.empty();

    if (estadoApp.carrito.length === 0) {
        $tbody.html(`
            <tr>
                <td class="text-center text-muted py-5">
                    <i class="fas fa-shopping-cart fa-2x mb-2 opacity-25"></i>
                    <p class="small mb-0">Sin servicios agregados</p>
                </td>
            </tr>
        `);
        actualizarTotales();
        return;
    }

    estadoApp.carrito.forEach((item, index) => {
        let bs = parseFloat(item.precio) * estadoApp.tasaBcv;
        let icon = "fa-vial";
        let badgeStyle = "background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;";
        let badgeTexto = "EXAMEN";

        if (item.tipo === "perfil") {
            icon = "fa-folder-open";
            badgeStyle = "background-color: #fef3c7; color: #92400e; border: 1px solid #fcd34d;";
            badgeTexto = "PERFIL";
        } else if (item.tipo === "servicio") {
            icon = "fa-tag";
            badgeStyle = "background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7;";
            badgeTexto = "SERVICIO";
        } else if (item.tipo === "producto") {
            icon = "fa-box";
            badgeStyle = "background-color: #faf5ff; color: #7e22ce; border: 1px solid #e9d5ff;";
            badgeTexto = "PRODUCTO";
        }

        const tieneDescuento = item.precio_base && item.precio < item.precio_base;
        const precioBaseHtml = tieneDescuento
            ? `<small class="text-decoration-line-through text-muted me-1 font-monospace" style="font-size: 0.75rem;">$${formatearMonto(item.precio_base)}</small>`
            : '';

        let html = `
            <tr class="item-row">
                <td class="ps-3 py-3">
                    <span class="d-block fw-bold text-dark mb-1">${item.nombre}</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill px-2.5 py-1 fw-bold" style="${badgeStyle} font-size: 0.68rem; letter-spacing: 0.4px;">
                            <i class="fas ${icon} me-1"></i>${badgeTexto}
                        </span>
                        ${tieneDescuento ? '<span class="badge rounded-pill px-2 py-0.5 small fw-bold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"><i class="fas fa-tag me-1"></i>Tarifario</span>' : ''}
                    </div>
                </td>
                <td class="text-end py-3">
                    <div class="d-flex align-items-center justify-content-end gap-1">
                        ${precioBaseHtml}
                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 fw-bold text-success font-monospace fs-6" 
                            onclick="editarPrecioItem(${index})" title="Clic para editar precio de este examen">
                            $${formatearMonto(item.precio)} <i class="fas fa-pencil-alt text-muted small ms-1" style="font-size: 0.7rem;"></i>
                        </button>
                    </div>
                    <span class="d-block small text-muted font-monospace">Bs. ${formatearMonto(bs)}</span>
                </td>
                <td class="text-end pe-3 py-3" style="width: 40px;">
                    <button class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="eliminarDelCarrito(${index})" title="Quitar de la orden">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `;
        $tbody.append(html);
    });

    actualizarTotales();
}

function actualizarTotales() {
    estadoApp.subtotal = estadoApp.carrito.reduce(
        (sum, item) => sum + parseFloat(item.precio),
        0,
    );
    // Assuming everything is exempt for now based on context
    estadoApp.totalUsd = estadoApp.subtotal;
    estadoApp.totalBs = estadoApp.totalUsd * estadoApp.tasaBcv;

    $("#resumen-subtotal").text(`$${formatearMonto(estadoApp.subtotal)}`);
    $("#resumen-total-usd").text(`$${formatearMonto(estadoApp.totalUsd)}`);
    $("#resumen-total-bs").text(`Bs. ${formatearMonto(estadoApp.totalBs)}`);

    if (estadoApp.carrito.length > 0) {
        $("#btn-abrir-pago").prop("disabled", false);
    } else {
        $("#btn-abrir-pago").prop("disabled", true);
    }
}

function abrirModalPago() {
    if (estadoApp.carrito.length === 0 || !estadoApp.pacienteActual) return;

    let totalPagarBs = estadoApp.totalUsd * estadoApp.tasaBcv;
    $("#modal-total-pagar").text(`$${formatearMonto(estadoApp.totalUsd)}`);
    $("#modal-total-pagar-bs").text(`Bs. ${formatearMonto(totalPagarBs)}`);
    actualizarModalPagos();

    var myModal = new bootstrap.Modal(document.getElementById("modalPagos"));
    myModal.show();
    setTimeout(() => $("#pago-monto").focus(), 500);
}

function actualizarModalPagos() {
    let $lista = $("#lista-pagos-mixtos");
    $lista.empty();

    estadoApp.abonadoUsd = estadoApp.pagos.reduce(
        (sum, p) => sum + p.monto_usd,
        0,
    );
    let abonadoBs = estadoApp.abonadoUsd * estadoApp.tasaBcv;
    let restanteUsd = estadoApp.totalUsd - estadoApp.abonadoUsd;
    if (restanteUsd < 0) restanteUsd = 0;
    let restanteBs = restanteUsd * estadoApp.tasaBcv;

    $("#modal-total-pagar").text(`$${formatearMonto(estadoApp.totalUsd)}`);
    $("#modal-total-pagar-bs").text(`Bs. ${formatearMonto(estadoApp.totalUsd * estadoApp.tasaBcv)}`);

    $("#modal-total-abonado").text(`$${formatearMonto(estadoApp.abonadoUsd)}`);
    $("#modal-total-abonado-bs").text(`Bs. ${formatearMonto(abonadoBs)}`);

    $("#modal-total-restante").text(`$${formatearMonto(restanteUsd)}`);
    $("#modal-total-restante-bs").text(`Bs. ${formatearMonto(restanteBs)}`);

    if (estadoApp.pagos.length === 0) {
        $lista.html(
            `<tr><td colspan="5" class="text-center text-muted small py-3">No hay pagos agregados aún</td></tr>`,
        );
    } else {
        estadoApp.pagos.forEach((p, idx) => {
            let montoBs = p.monto_bs ? parseFloat(p.monto_bs) : p.monto_usd * estadoApp.tasaBcv;
            $lista.append(`
                <tr>
                    <td class="ps-3 fw-bold text-dark">
                        <i class="fas fa-credit-card text-primary me-2"></i>${p.metodo_pago}
                    </td>
                    <td class="small text-muted font-monospace">${p.referencia || "N/A"}</td>
                    <td class="small fw-bold text-success text-end font-monospace">$${formatearMonto(p.monto_usd)}</td>
                    <td class="small fw-bold text-primary text-end font-monospace">Bs. ${formatearMonto(montoBs)}</td>
                    <td class="text-end pe-3">
                        <button class="btn btn-sm btn-outline-danger border-0 rounded-circle" onclick="eliminarPago(${idx})" title="Eliminar pago">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    validarEstadoFacturacion();
}

window.eliminarPago = function (index) {
    estadoApp.pagos.splice(index, 1);
    actualizarModalPagos();
};

function validarEstadoFacturacion() {
    let restante = estadoApp.totalUsd - estadoApp.abonadoUsd;
    let permiteDeuda = $("#check-credito").is(":checked");

    if (restante <= 0.001 || permiteDeuda) {
        $("#btn-finalizar-factura").prop("disabled", false);
    } else {
        $("#btn-finalizar-factura").prop("disabled", true);
    }
}

function finalizarFactura() {
    let btn = $("#btn-finalizar-factura");
    btn.prop("disabled", true).html(
        '<i class="fas fa-spinner fa-spin"></i> Procesando...',
    );

    let restante = estadoApp.totalUsd - estadoApp.abonadoUsd;
    let estadoFactura = restante > 0.005 ? "Pendiente" : "Pagada";

    let payload = {
        paciente_id: estadoApp.pacienteActual.id,
        convenio_id: estadoApp.convenioId || null,
        subtotal_exento_usd: estadoApp.subtotal,
        total_usd: estadoApp.totalUsd,
        total_bs: estadoApp.totalBs,
        estado_factura: estadoFactura,
        carrito: estadoApp.carrito,
        pagos: estadoApp.pagos,
        _token: $('meta[name="csrf-token"]').attr("content"),
    };

    $.post({
        url: urlProcesar,
        data: JSON.stringify(payload),
        contentType: "application/json",
        success: function (res) {
            if (res.success) {
                notificacion.fire({
                    icon: "success",
                    title: "Completado",
                    text: res.message,
                });
                bootstrap.Modal.getInstance(
                    document.getElementById("modalPagos"),
                ).hide();
                
                // Abrir impresión en una nueva pestaña
                if (res.data && res.data.factura && res.data.factura.id) {
                    window.open(`/factura/${res.data.factura.id}/imprimir`, '_blank');
                }
                
                resetearPOS();
            }
        },
        error: function () {
            notificacion.fire({
                icon: "error",
                title: "Error al procesar la factura",
            });
            btn.prop("disabled", false).html("Confirmar y Facturar");
        },
    });
}

function resetearPOS() {
    // Restaurar botones
    $("#btn-finalizar-factura")
        .prop("disabled", false)
        .html("Confirmar y Facturar");

    // Reset estado
    estadoApp.pacienteActual = null;
    estadoApp.convenioId = null;
    estadoApp.carrito = [];
    estadoApp.pagos = [];

    // Reset UI
    $("#pos-convenio-select").val("");
    $("#panel-paciente-seleccionado").addClass("d-none");
    $("#buscar-cedula").val("").prop("disabled", false).focus();
    $("#bloque-catalogo").css({ opacity: "0.5", "pointer-events": "none" });
    $("#bloque-ticket").css({ opacity: "0.5", "pointer-events": "none" });
    $("#check-credito").prop("checked", false);

    renderizarCarrito();
}

/**
 * Genera datos anonimos para registrar pacientes que no quieren dar sus datos
 * Si es menor: llena cedula del representante con X-RANDOM y nombre anonimo
 * Si es adulto: llena cedula del paciente con X-RANDOM y nombre anonimo
 */
function generarDatosAnonimosIngreso() {
    var rand = Math.floor(Math.random() * 90000000) + 10000000;
    var esMenor = $("#nuevo-paciente-es-menor").is(":checked");

    if (esMenor) {
        $("#nuevo-paciente-tipo-cedula-rep").val("X-");
        $("#nuevo-paciente-cedula-rep").val(rand);
        if (!$("#nuevo-paciente-nombre-rep").val().trim()) {
            $("#nuevo-paciente-nombre-rep").val("Representante Anonimo");
        }
        if (!$("#nuevo-paciente-nombreUno").val().trim()) {
            $("#nuevo-paciente-nombreUno").val("Menor");
            $("#nuevo-paciente-apellidoUno").val("Anonimo");
        }
    } else {
        $("#nuevo-paciente-tipo-cedula").val("X-");
        $("#nuevo-paciente-cedula").val(rand);
        if (!$("#nuevo-paciente-nombreUno").val().trim()) {
            $("#nuevo-paciente-nombreUno").val("Paciente");
            $("#nuevo-paciente-apellidoUno").val("Anonimo");
        }
    }
}
