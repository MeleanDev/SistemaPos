const urlBaseConsultor = window.location.origin + (window.location.pathname.includes("/consultor-precios") ? window.location.pathname.replace(/\/$/, "") : "/consultor-precios");
const urlDatosConsultor = urlBaseConsultor + "/datos";
const urlBuscarConsultor = urlBaseConsultor + "/buscar";

let tasaCambioActiva = 1.0;
let debounceTimerConsultor = null;
let temporizadorReset = null;
let intervaloProgresoReset = null;
let audioContextConsultor = null;
let coincidenciasActuales = [];

$(document).ready(function () {
    inicializarConsultor();

    $("#inputBuscarProductoConsultor").on("input", function () {
        const val = $(this).val().trim();
        if (debounceTimerConsultor) {
            clearTimeout(debounceTimerConsultor);
            debounceTimerConsultor = null;
        }
        if (val.length === 0) {
            cancelarTemporizadorReset();
            mostrarEstadoInicial();
            return;
        }
        debounceTimerConsultor = setTimeout(function () {
            ejecutarBusqueda(val, false);
        }, 220);
    });

    $("#inputBuscarProductoConsultor").on("keydown", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            const val = $(this).val().trim();
            if (debounceTimerConsultor) {
                clearTimeout(debounceTimerConsultor);
                debounceTimerConsultor = null;
            }
            ejecutarBusqueda(val, true);
        }
    });

    $("#formularioConsultorPrecios").on("submit", function (e) {
        e.preventDefault();
        const val = $("#inputBuscarProductoConsultor").val().trim();
        if (debounceTimerConsultor) {
            clearTimeout(debounceTimerConsultor);
            debounceTimerConsultor = null;
        }
        ejecutarBusqueda(val, true);
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest("button, a, input, select, textarea, .kiosk-interactive").length) {
            asegurarFocoInput();
        }
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
            limpiarBusqueda();
        } else if (e.key === "F2" || e.key === "F3" || e.key === "F4") {
            e.preventDefault();
            asegurarFocoInput();
        }
    });
});

function inicializarConsultor() {
    asegurarFocoInput();
    cargarDatosIniciales();
}

function asegurarFocoInput() {
    const $input = $("#inputBuscarProductoConsultor");
    if ($input.length && !$input.is(":focus")) {
        $input.focus();
    }
}

async function cargarDatosIniciales() {
    try {
        const res = await $.ajax({
            url: urlDatosConsultor,
            type: "GET",
            dataType: "json",
        });

        if (res.success && res.data) {
            tasaCambioActiva = parseFloat(res.data.tasa_usd) || 1.0;
            $("#badgeTasaConsultor").text(tasaCambioActiva.toFixed(2));
        }
    } catch (e) {
        tasaCambioActiva = 1.0;
    }
}

async function ejecutarBusqueda(termino, esEnter = false) {
    const term = (termino || "").trim();

    if (!term) {
        cancelarTemporizadorReset();
        mostrarEstadoInicial();
        return;
    }

    $("#spinnerCargandoConsultor").show();
    $("#btnLimpiarBusqueda").show();

    try {
        const res = await $.ajax({
            url: urlBuscarConsultor,
            type: "GET",
            dataType: "json",
            data: { termino: term },
        });

        $("#spinnerCargandoConsultor").hide();

        if (res.success && Array.isArray(res.data)) {
            if (res.data.length === 0) {
                reproducirSonidoError();
                mostrarSinResultados(term);
                iniciarAutoReset(5);
            } else if (res.data.length === 1 || esEnter) {
                reproducirSonidoScan();
                renderizarHeroProducto(res.data[0]);
                iniciarAutoReset(10);
            } else {
                reproducirSonidoScan();
                renderizarListaCoincidencias(res.data);
                iniciarAutoReset(15);
            }
        } else {
            reproducirSonidoError();
            mostrarSinResultados(term);
            iniciarAutoReset(5);
        }
    } catch (err) {
        $("#spinnerCargandoConsultor").hide();
        reproducirSonidoError();
        mostrarSinResultados(term);
        iniciarAutoReset(5);
    }
}

function renderizarHeroProducto(p) {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    cancelarTemporizadorReset();

    const usd = parseFloat(p.precio_usd_con_iva || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const bs = parseFloat(p.precio_bs_con_iva || 0).toLocaleString("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    let fontSizeBs = "clamp(1.5rem, 2.6vw, 2.8rem)";
    let fontSizeBsSimbolo = "clamp(1rem, 1.6vw, 1.6rem)";
    if (bs.length > 12) {
        fontSizeBs = "clamp(1.15rem, 1.8vw, 1.9rem)";
        fontSizeBsSimbolo = "clamp(0.85rem, 1.2vw, 1.2rem)";
    } else if (bs.length > 8) {
        fontSizeBs = "clamp(1.35rem, 2.1vw, 2.3rem)";
        fontSizeBsSimbolo = "clamp(0.95rem, 1.4vw, 1.4rem)";
    }

    let fontSizeUsd = "clamp(1.6rem, 3vw, 3.1rem)";
    let fontSizeUsdSimbolo = "clamp(1.2rem, 2vw, 2rem)";
    if (usd.length > 10) {
        fontSizeUsd = "clamp(1.35rem, 2.1vw, 2.3rem)";
        fontSizeUsdSimbolo = "clamp(0.95rem, 1.4vw, 1.4rem)";
    }

    const sku = p.codigo_interno ? `#${p.codigo_interno}` : "N/A";
    const categoria = p.categoria || "General";
    let barcodesHtml = "";
    if (p.codigos_barra && p.codigos_barra.length > 0) {
        barcodesHtml = p.codigos_barra
            .map(
                (c) =>
                    `<span class="badge rounded-pill px-2.5 px-sm-3 py-1 font-mono-num" style="background: var(--kiosk-toggle-bg); color: var(--kiosk-text-main); font-size: clamp(0.72rem, 1vw, 0.85rem); border: 1px solid var(--kiosk-card-border);"><i class="fas fa-barcode text-primary me-1"></i>${c}</span>`
            )
            .join(" ");
    }

    const html = `
        <div class="kiosk-card p-3 p-sm-4 p-md-5 position-relative overflow-hidden animate__animated animate__zoomIn w-100" style="max-width: 1040px; margin: 0 auto;">
            <div class="kiosk-countdown-bar-wrapper position-absolute top-0 start-0 end-0">
                <div id="barraProgresoKiosco" class="kiosk-countdown-bar-fill"></div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 mt-1" style="border-bottom: 1px solid var(--kiosk-card-border);">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge rounded-pill px-2.5 px-sm-3 py-1.5 font-mono-num fw-bold" style="background: #2563eb; color: #ffffff; font-size: clamp(0.75rem, 1vw, 0.9rem); border: none;">
                        <i class="fas fa-hashtag me-1" style="color: #93c5fd;"></i>${sku}
                    </span>
                    <span class="badge rounded-pill px-2.5 px-sm-3 py-1.5 font-mono-num fw-semibold" style="background: var(--kiosk-toggle-bg); color: var(--kiosk-text-main); font-size: clamp(0.75rem, 1vw, 0.88rem); border: 1px solid var(--kiosk-card-border);">
                        <i class="fas fa-layer-group me-1" style="color: #60a5fa;"></i>${categoria}
                    </span>
                </div>
                ${barcodesHtml ? `<div class="d-flex flex-wrap gap-1">${barcodesHtml}</div>` : ""}
            </div>

            <div class="mb-3 mb-md-4 text-center text-md-start">
                <h1 class="fw-bold kiosk-text-heading mb-1" style="font-size: clamp(1.4rem, 2.8vw, 2.7rem); letter-spacing: -0.02em; line-height: 1.2; word-break: break-word;">
                    ${p.nombre}
                </h1>
            </div>

            <div class="row g-3 g-md-4 mb-3 mb-md-4">
                <div class="col-12 col-md-6">
                    <div class="p-3 p-sm-4 h-100 position-relative overflow-hidden d-flex flex-column justify-content-between text-white"
                        style="border-radius: clamp(1.4rem, 2vw, 2.2rem); background: linear-gradient(135deg, #059669 0%, #10b981 100%); box-shadow: 0 15px 35px -5px rgba(5, 150, 105, 0.4); border: none;">
                        <div class="d-flex justify-content-between align-items-start mb-3 mb-md-4">
                            <span class="badge rounded-pill font-mono-num px-2.5 px-sm-3 py-1 text-uppercase fw-bold" style="background: rgba(0, 0, 0, 0.28); color: #ffffff; letter-spacing: 0.05em; font-size: clamp(0.72rem, 0.9vw, 0.84rem); border: none;">
                                <i class="fas fa-check-circle text-emerald-300 me-1"></i> PRECIO CON IVA
                            </span>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: clamp(38px, 4vw, 46px); height: clamp(38px, 4vw, 46px); background: rgba(255, 255, 255, 0.25); color: #ffffff; font-size: clamp(1.1rem, 1.3vw, 1.35rem);">
                                <i class="fas fa-dollar-sign text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small font-mono-num mb-1 text-uppercase fw-semibold" style="color: rgba(255, 255, 255, 0.88); letter-spacing: 0.05em; font-size: clamp(0.75rem, 0.95vw, 0.85rem);">Dólares Americanos ($)</div>
                            <div class="d-flex align-items-baseline gap-1.5 text-white font-mono-num" style="white-space: nowrap;">
                                <span style="font-size: ${fontSizeUsdSimbolo}; font-weight: 700; opacity: 0.9;">$</span>
                                <span class="fw-extrabold" style="font-size: ${fontSizeUsd}; line-height: 1; letter-spacing: -0.03em;">${usd}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="p-3 p-sm-4 h-100 position-relative overflow-hidden d-flex flex-column justify-content-between text-white"
                        style="border-radius: clamp(1.4rem, 2vw, 2.2rem); background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%); box-shadow: 0 15px 35px -5px rgba(37, 99, 235, 0.4); border: none;">
                        <div class="d-flex justify-content-between align-items-start mb-3 mb-md-4">
                            <span class="badge rounded-pill font-mono-num px-2.5 px-sm-3 py-1 text-uppercase fw-bold" style="background: rgba(0, 0, 0, 0.28); color: #ffffff; letter-spacing: 0.05em; font-size: clamp(0.72rem, 0.9vw, 0.84rem); border: none;">
                                <i class="fas fa-check-circle text-blue-300 me-1"></i> PRECIO CON IVA
                            </span>
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: clamp(38px, 4vw, 46px); height: clamp(38px, 4vw, 46px); background: rgba(255, 255, 255, 0.25); color: #ffffff; font-size: clamp(1.1rem, 1.3vw, 1.35rem);">
                                <i class="fas fa-money-bill-wave text-white"></i>
                            </div>
                        </div>
                        <div>
                            <div class="small font-mono-num mb-1 text-uppercase fw-semibold" style="color: rgba(255, 255, 255, 0.88); letter-spacing: 0.05em; font-size: clamp(0.75rem, 0.95vw, 0.85rem);">Bolívares Digitales (Bs.)</div>
                            <div class="d-flex align-items-baseline gap-1.5 text-white font-mono-num" style="white-space: nowrap;">
                                <span style="font-size: ${fontSizeBsSimbolo}; font-weight: 700; opacity: 0.9;">Bs.</span>
                                <span class="fw-extrabold" style="font-size: ${fontSizeBs}; line-height: 1; letter-spacing: -0.03em;">${bs}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pt-3 font-mono-num kiosk-text-sub small" style="border-top: 1px solid var(--kiosk-card-border);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-history text-warning"></i>
                    <span>Retornando automáticamente en <strong class="kiosk-text-heading" id="contadorSegundosKiosco">10</strong>s</span>
                </div>
                <div>
                    <button type="button" class="kiosk-btn kiosk-interactive rounded-pill" onclick="limpiarBusqueda()">
                        <i class="fas fa-barcode"></i>
                        <span>Escanear / Consultar Otro</span>
                    </button>
                </div>
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
    $("#inputBuscarProductoConsultor").val("");
    $("#btnLimpiarBusqueda").hide();
    asegurarFocoInput();
}

function renderizarListaCoincidencias(lista) {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    cancelarTemporizadorReset();
    coincidenciasActuales = lista;

    let itemsHtml = "";
    lista.forEach((p, index) => {
        const usd = parseFloat(p.precio_usd_con_iva || 0).toLocaleString("en-US", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const bs = parseFloat(p.precio_bs_con_iva || 0).toLocaleString("es-VE", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const sku = p.codigo_interno ? `#${p.codigo_interno}` : "N/A";

        itemsHtml += `
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="kiosk-item-card p-3 p-sm-4 h-100 cursor-pointer kiosk-interactive d-flex flex-column justify-content-between"
                    onclick="seleccionarProductoPorIndice(${index})"
                    style="cursor: pointer;">
                    <div>
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <span class="badge rounded-pill font-mono-num px-2.5 px-sm-3 py-1 fw-bold" style="background: #2563eb; color: #ffffff; font-size: clamp(0.72rem, 0.9vw, 0.82rem); border: none;">
                                <i class="fas fa-hashtag me-1" style="color: #93c5fd;"></i>${sku}
                            </span>
                            <span class="badge rounded-pill px-2.5 px-sm-3 py-1 fw-semibold small text-truncate" style="background: var(--kiosk-toggle-bg); color: var(--kiosk-text-main); max-width: 140px; font-size: clamp(0.72rem, 0.9vw, 0.82rem); border: 1px solid var(--kiosk-card-border);">
                                <i class="fas fa-layer-group me-1" style="color: #60a5fa;"></i>${p.categoria || "General"}
                            </span>
                        </div>
                        <h5 class="fw-bold kiosk-text-heading mb-3" style="line-height: 1.35; font-size: clamp(0.98rem, 1.2vw, 1.15rem); min-height: 2.6rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            ${p.nombre}
                        </h5>
                    </div>
                    <div>
                        <div class="d-flex flex-column gap-2 pt-3 font-mono-num" style="border-top: 1px solid var(--kiosk-card-border);">
                            <div class="d-flex align-items-center justify-content-between rounded-pill" style="background: rgba(16, 185, 129, 0.16); border: 1px solid rgba(16, 185, 129, 0.35); padding: 0.45rem 1.25rem;">
                                <span class="fw-bold text-uppercase" style="color: #10b981; font-size: clamp(0.68rem, 0.85vw, 0.74rem); letter-spacing: 0.03em;">PVP ($)</span>
                                <strong class="kiosk-text-heading" style="font-size: clamp(0.95rem, 1.15vw, 1.05rem); font-weight: 800; white-space: nowrap;">$ ${usd}</strong>
                            </div>
                            <div class="d-flex align-items-center justify-content-between rounded-pill" style="background: rgba(59, 130, 246, 0.16); border: 1px solid rgba(59, 130, 246, 0.35); padding: 0.45rem 1.25rem;">
                                <span class="fw-bold text-uppercase" style="color: #3b82f6; font-size: clamp(0.68rem, 0.85vw, 0.74rem); letter-spacing: 0.03em;">PVP (Bs.)</span>
                                <strong class="kiosk-text-heading" style="font-size: clamp(0.95rem, 1.15vw, 1.05rem); font-weight: 800; white-space: nowrap;">Bs. ${bs}</strong>
                            </div>
                        </div>
                        <div class="text-center pt-2 text-primary small font-mono-num fw-semibold" style="font-size: 0.75rem;">
                            <i class="fas fa-hand-pointer me-1"></i> Tocar para ver detalle
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    const html = `
        <div class="kiosk-card kiosk-card-coincidencias p-3 p-sm-4 p-md-5 w-100" style="max-width: 1200px; margin: 0 auto;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3" style="border-bottom: 1px solid var(--kiosk-card-border);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: clamp(38px, 4vw, 44px); height: clamp(38px, 4vw, 44px); background: #2563eb; color: #ffffff; font-size: clamp(1.05rem, 1.25vw, 1.25rem);">
                        <i class="fas fa-search" style="color: #ffffff;"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold kiosk-text-heading mb-0" style="font-size: clamp(1.1rem, 1.5vw, 1.4rem);">Coincidencias Encontradas (${lista.length})</h4>
                        <span class="kiosk-text-sub small font-mono-num" style="font-size: clamp(0.75rem, 0.95vw, 0.85rem);">Seleccione un producto para ver el precio en pantalla completa</span>
                    </div>
                </div>
                <div>
                    <button type="button" class="kiosk-btn kiosk-interactive rounded-pill" onclick="limpiarBusqueda()">
                        <i class="fas fa-times me-1"></i>
                        <span>Cerrar</span>
                    </button>
                </div>
            </div>
            <div class="row g-3 g-md-4">
                ${itemsHtml}
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
    asegurarFocoInput();
}

function seleccionarProductoPorIndice(index) {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    if (coincidenciasActuales && coincidenciasActuales[index]) {
        const p = coincidenciasActuales[index];
        reproducirSonidoScan();
        renderizarHeroProducto(p);
        iniciarAutoReset(10);
    }
}

function seleccionarProductoCoincidencia(p) {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    reproducirSonidoScan();
    renderizarHeroProducto(p);
    iniciarAutoReset(10);
}

function mostrarSinResultados(termino) {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    cancelarTemporizadorReset();

    const html = `
        <div class="kiosk-card p-3 p-sm-4 p-md-5 text-center w-100 position-relative overflow-hidden" style="max-width: 760px; margin: 0 auto;">
            <div class="kiosk-countdown-bar-wrapper position-absolute top-0 start-0 end-0">
                <div id="barraProgresoKiosco" class="kiosk-countdown-bar-fill" style="background: #ef4444;"></div>
            </div>

            <div class="rounded-circle bg-danger bg-opacity-20 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center"
                style="width: clamp(56px, 7vw, 74px); height: clamp(56px, 7vw, 74px); font-size: clamp(1.6rem, 2.2vw, 2.2rem);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="fw-bold kiosk-text-heading mb-2" style="font-size: clamp(1.3rem, 2.2vw, 2rem);">Producto No Encontrado</h2>
            <p class="kiosk-text-sub mb-4 font-mono-num" style="font-size: clamp(0.9rem, 1.2vw, 1.15rem);">
                No existe ningún producto registrado con el código: <br>
                <strong class="text-danger bg-danger bg-opacity-10 px-3.5 py-1.5 rounded-pill mt-2 d-inline-block font-mono-num">"${termino}"</strong>
            </p>
            <div class="d-flex justify-content-center">
                <button type="button" class="kiosk-btn kiosk-interactive px-4 py-2 font-mono-num rounded-pill" onclick="limpiarBusqueda()">
                    <i class="fas fa-barcode me-2"></i> Intentar Nuevamente
                </button>
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
    $("#inputBuscarProductoConsultor").val("");
    $("#btnLimpiarBusqueda").hide();
    asegurarFocoInput();
}

function mostrarEstadoInicial() {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    cancelarTemporizadorReset();
    $("#contenedorResultadoConsultor").empty().hide();
    $("#contenedorEstadoInicial").show();
    $("#btnLimpiarBusqueda").hide();
    asegurarFocoInput();
}

function limpiarBusqueda() {
    if (debounceTimerConsultor) {
        clearTimeout(debounceTimerConsultor);
        debounceTimerConsultor = null;
    }
    cancelarTemporizadorReset();
    $("#inputBuscarProductoConsultor").val("");
    mostrarEstadoInicial();
}

function iniciarAutoReset(segundos) {
    cancelarTemporizadorReset();

    let restante = segundos;
    const duracionMs = segundos * 1000;
    const pasoMs = 100;
    let transcurridoMs = 0;

    $("#contadorSegundosKiosco").text(restante);
    $("#barraProgresoKiosco").css("width", "100%");

    intervaloProgresoReset = setInterval(function () {
        transcurridoMs += pasoMs;
        const porcentaje = Math.max(0, 100 - (transcurridoMs / duracionMs) * 100);
        $("#barraProgresoKiosco").css("width", porcentaje + "%");

        const segundosRestantes = Math.ceil((duracionMs - transcurridoMs) / 1000);
        $("#contadorSegundosKiosco").text(segundosRestantes);

        if (transcurridoMs >= duracionMs) {
            cancelarTemporizadorReset();
            limpiarBusqueda();
        }
    }, pasoMs);
}

function cancelarTemporizadorReset() {
    if (intervaloProgresoReset) {
        clearInterval(intervaloProgresoReset);
        intervaloProgresoReset = null;
    }
    if (temporizadorReset) {
        clearTimeout(temporizadorReset);
        temporizadorReset = null;
    }
}

function reproducirSonidoScan() {
    try {
        if (!audioContextConsultor) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioContextConsultor = new AudioContextClass();
            }
        }
        if (audioContextConsultor && audioContextConsultor.state === "suspended") {
            audioContextConsultor.resume();
        }
        if (audioContextConsultor) {
            const osc = audioContextConsultor.createOscillator();
            const gain = audioContextConsultor.createGain();
            osc.type = "sine";
            osc.frequency.setValueAtTime(1450, audioContextConsultor.currentTime);
            gain.gain.setValueAtTime(0.08, audioContextConsultor.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioContextConsultor.currentTime + 0.12);
            osc.connect(gain);
            gain.connect(audioContextConsultor.destination);
            osc.start();
            osc.stop(audioContextConsultor.currentTime + 0.12);
        }
    } catch (e) {}
}

function reproducirSonidoError() {
    try {
        if (!audioContextConsultor) {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if (AudioContextClass) {
                audioContextConsultor = new AudioContextClass();
            }
        }
        if (audioContextConsultor && audioContextConsultor.state === "suspended") {
            audioContextConsultor.resume();
        }
        if (audioContextConsultor) {
            const osc = audioContextConsultor.createOscillator();
            const gain = audioContextConsultor.createGain();
            osc.type = "sawtooth";
            osc.frequency.setValueAtTime(220, audioContextConsultor.currentTime);
            gain.gain.setValueAtTime(0.1, audioContextConsultor.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioContextConsultor.currentTime + 0.3);
            osc.connect(gain);
            gain.connect(audioContextConsultor.destination);
            osc.start();
            osc.stop(audioContextConsultor.currentTime + 0.3);
        }
    } catch (e) {}
}

window.limpiarBusqueda = limpiarBusqueda;
window.seleccionarProductoPorIndice = seleccionarProductoPorIndice;
window.seleccionarProductoCoincidencia = seleccionarProductoCoincidencia;
window.ejecutarBusqueda = ejecutarBusqueda;
window.cargarDatosIniciales = cargarDatosIniciales;
