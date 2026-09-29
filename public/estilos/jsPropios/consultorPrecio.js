const urlBaseConsultor = window.location.origin + (window.location.pathname.includes("/consultor-precios") ? window.location.pathname.replace(/\/$/, "") : "/consultor-precios");
const urlDatosConsultor = urlBaseConsultor + "/datos";
const urlBuscarConsultor = urlBaseConsultor + "/buscar";

let tasaCambioActiva = 1.0;
let debounceTimerConsultor = null;
let audioContextConsultor = null;

$(document).ready(function () {
    inicializarConsultor();

    $("#inputBuscarProductoConsultor").on("input keyup change paste", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) return;
        const val = $(this).val().trim();
        if (debounceTimerConsultor) {
            clearTimeout(debounceTimerConsultor);
        }
        if (val.length === 0) {
            cargarDatosIniciales();
            return;
        }
        debounceTimerConsultor = setTimeout(function () {
            ejecutarBusqueda(val, false);
        }, 200);
    });

    $("#inputBuscarProductoConsultor").on("keypress", function (e) {
        if (e.key === "Enter" || e.keyCode === 13) {
            e.preventDefault();
            const val = $(this).val().trim();
            if (debounceTimerConsultor) {
                clearTimeout(debounceTimerConsultor);
            }
            ejecutarBusqueda(val, true);
        }
    });

    $("#formularioConsultorPrecios").on("submit", function (e) {
        e.preventDefault();
        const val = $("#inputBuscarProductoConsultor").val().trim();
        ejecutarBusqueda(val, true);
    });

    $(document).on("keydown", function (e) {
        if (e.key === "Escape") {
            limpiarBusqueda();
        } else if (e.key === "F2" || e.key === "F3") {
            e.preventDefault();
            $("#inputBuscarProductoConsultor").focus().select();
        }
    });
});

function inicializarConsultor() {
    $("#inputBuscarProductoConsultor").focus();
    cargarDatosIniciales();
}

async function cargarDatosIniciales() {
    try {
        $("#spinnerCargandoConsultor").show();
        const res = await $.ajax({
            url: urlDatosConsultor,
            type: "GET",
            dataType: "json",
        });
        $("#spinnerCargandoConsultor").hide();

        if (res.success && res.data) {
            tasaCambioActiva = parseFloat(res.data.tasa_usd) || 1.0;
            $("#badgeTasaConsultor").text(tasaCambioActiva.toFixed(4));
            if (res.data.fecha_consulta) {
                $("#badgeFechaConsultor").text(res.data.fecha_consulta);
            }

            if (Array.isArray(res.data.productos_iniciales) && res.data.productos_iniciales.length > 0) {
                renderizarListaCoincidencias(res.data.productos_iniciales, true);
            } else {
                mostrarEstadoInicial();
            }
        }
    } catch (e) {
        $("#spinnerCargandoConsultor").hide();
        tasaCambioActiva = 1.0;
        mostrarEstadoInicial();
    }
}

async function ejecutarBusqueda(termino, esEnter = false) {
    const term = (termino || "").trim();

    if (!term) {
        cargarDatosIniciales();
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
                mostrarSinResultados(term);
            } else if (res.data.length === 1 || esEnter) {
                reproducirSonidoScan();
                renderizarHeroProducto(res.data[0]);
            } else {
                renderizarListaCoincidencias(res.data, false);
            }
        } else {
            mostrarSinResultados(term);
        }
    } catch (err) {
        $("#spinnerCargandoConsultor").hide();
        mostrarSinResultados(term);
    }
}

function renderizarHeroProducto(p) {
    const usd = parseFloat(p.precio_usd_con_iva || 0).toLocaleString("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    const bs = parseFloat(p.precio_bs_con_iva || 0).toLocaleString("es-VE", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const sku = p.codigo_interno ? `#${p.codigo_interno}` : "N/A";
    const categoria = p.categoria || "General";
    let barcodesHtml = "";
    if (p.codigos_barra && p.codigos_barra.length > 0) {
        barcodesHtml = p.codigos_barra
            .map(
                (c) =>
                    `<span class="badge rounded-pill bg-white text-dark border px-2.5 py-1 font-monospace shadow-xs"><i class="fas fa-barcode text-primary me-1"></i>${c}</span>`
            )
            .join(" ");
    }

    const html = `
        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden animate__animated animate__fadeIn">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 font-monospace fw-bold fs-6 shadow-xs">
                            <i class="fas fa-hashtag me-1"></i>${sku}
                        </span>
                        <span class="badge rounded-pill bg-white text-secondary border px-3 py-1.5 font-monospace shadow-xs">
                            <i class="fas fa-layer-group text-primary me-1"></i>${categoria}
                        </span>
                    </div>
                    ${barcodesHtml ? `<div class="d-flex flex-wrap gap-1">${barcodesHtml}</div>` : ""}
                </div>

                <div class="mb-4">
                    <h2 class="fw-bold text-dark mb-1" style="font-size: clamp(1.4rem, 2.5vw, 2.2rem); letter-spacing: -0.02em;">
                        ${p.nombre}
                    </h2>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <div class="card border-0 rounded-4 p-4 shadow-sm h-100 position-relative overflow-hidden"
                            style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-pill bg-white bg-opacity-20 text-white font-monospace px-3 py-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 0.78rem;">
                                    Precio Con IVA
                                </span>
                                <div class="avatar-executive-sm rounded-circle bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                            </div>
                            <div class="mt-auto">
                                <div class="text-white-50 small font-monospace">Dólares Americanos ($)</div>
                                <div class="display-5 fw-bold text-white font-monospace tracking-tight" style="font-size: clamp(2rem, 3.5vw, 3.2rem); line-height: 1.1;">
                                    $ ${usd}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="card border-0 rounded-4 p-4 shadow-sm h-100 position-relative overflow-hidden"
                            style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-pill bg-white bg-opacity-20 text-white font-monospace px-3 py-1 text-uppercase" style="letter-spacing: 0.05em; font-size: 0.78rem;">
                                    Precio Con IVA
                                </span>
                                <div class="avatar-executive-sm rounded-circle bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                            </div>
                            <div class="mt-auto">
                                <div class="text-white-50 small font-monospace">Bolívares Digitales (Bs.)</div>
                                <div class="display-5 fw-bold text-white font-monospace tracking-tight" style="font-size: clamp(2rem, 3.5vw, 3.2rem); line-height: 1.1;">
                                    Bs. ${bs}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 text-center border-top">
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 py-2 font-monospace shadow-xs" onclick="limpiarBusqueda()">
                        <i class="fas fa-barcode me-2"></i>Escanear o Consultar Otro Producto
                    </button>
                </div>
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
}

function renderizarListaCoincidencias(lista, esInicial = false) {
    let itemsHtml = "";
    lista.forEach((p, idx) => {
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
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card border rounded-4 p-3.5 bg-white shadow-xs h-100 cursor-pointer transition-all hover-scale"
                    onclick='seleccionarProductoCoincidencia(${JSON.stringify(p)})'
                    style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2.5 py-1">
                            ${sku}
                        </span>
                        <span class="badge rounded-pill bg-white text-muted border px-2 py-0.5 small">
                            ${p.categoria || "General"}
                        </span>
                    </div>
                    <h6 class="fw-bold text-dark mb-3 text-truncate" title="${p.nombre}">
                        ${p.nombre}
                    </h6>
                    <div class="d-flex justify-content-between align-items-end pt-2 border-top font-monospace">
                        <div>
                            <span class="text-muted d-block small">PVP ($):</span>
                            <strong class="text-success fs-5">$ ${usd}</strong>
                        </div>
                        <div class="text-end">
                            <span class="text-muted d-block small">PVP (Bs.):</span>
                            <strong class="text-primary fs-5">Bs. ${bs}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    const titulo = esInicial
        ? '<i class="fas fa-boxes-stacked text-primary me-2"></i>Catálogo de Productos'
        : `<i class="fas fa-search text-primary me-2"></i>Resultados encontrados (${lista.length})`;

    const subtitulo = esInicial
        ? "Selecciona cualquier producto o escanea un código para ver en grande"
        : "Haz clic en un producto para ver el precio en grande";

    const html = `
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                <h6 class="fw-bold text-dark mb-0">
                    ${titulo}
                </h6>
                <small class="text-muted font-monospace">${subtitulo}</small>
            </div>
            <div class="row g-3">
                ${itemsHtml}
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
}

function seleccionarProductoCoincidencia(p) {
    reproducirSonidoScan();
    renderizarHeroProducto(p);
}

function mostrarSinResultados(termino) {
    const html = `
        <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center">
            <div class="avatar-executive-lg rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center"
                style="width: 64px; height: 64px; font-size: 1.75rem;">
                <i class="fas fa-search"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Producto No Encontrado</h5>
            <p class="text-muted mb-4 font-monospace">No se encontraron productos registrados con el código o nombre: <strong class="text-dark">"${termino}"</strong></p>
            <div>
                <button type="button" class="btn btn-outline-primary rounded-pill px-4 py-2 font-monospace shadow-xs" onclick="limpiarBusqueda()">
                    <i class="fas fa-arrow-left me-1"></i> Intentar con otro código o nombre
                </button>
            </div>
        </div>
    `;

    $("#contenedorResultadoConsultor").html(html).show();
    $("#contenedorEstadoInicial").hide();
}

function mostrarEstadoInicial() {
    $("#contenedorResultadoConsultor").empty().hide();
    $("#contenedorEstadoInicial").show();
    $("#btnLimpiarBusqueda").hide();
}

function limpiarBusqueda() {
    $("#inputBuscarProductoConsultor").val("").focus();
    cargarDatosIniciales();
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
            osc.frequency.setValueAtTime(1400, audioContextConsultor.currentTime);
            gain.gain.setValueAtTime(0.08, audioContextConsultor.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioContextConsultor.currentTime + 0.12);
            osc.connect(gain);
            gain.connect(audioContextConsultor.destination);
            osc.start();
            osc.stop(audioContextConsultor.currentTime + 0.12);
        }
    } catch (e) {}
}

window.limpiarBusqueda = limpiarBusqueda;
window.seleccionarProductoCoincidencia = seleccionarProductoCoincidencia;
window.ejecutarBusqueda = ejecutarBusqueda;
window.cargarDatosIniciales = cargarDatosIniciales;
