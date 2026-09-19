// URL limpia independiente de query parameters en la barra de navegación
const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlDetalles = urlBase + "/";
const urlEliminar = urlBase + "/";
const urlGuardar = urlBase;
const urlEditar = urlBase + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idAlmacenActual = null;
let listaAlmacenes = [];

$(document).ready(function () {
    cargarAlmacenes();

    // Búsqueda en tiempo real
    $("#buscadorAlmacenes").on("input", function () {
        const busqueda = $(this).val().toLowerCase().trim();

        if (!busqueda) {
            renderizarAlmacenes(listaAlmacenes);
            return;
        }

        const filtrados = listaAlmacenes.filter((almacen) => {
            const codigo = (almacen.codigo || "").toLowerCase();
            const nombre = (almacen.nombre || "").toLowerCase();
            const direccion = (almacen.direccion || "").toLowerCase();

            return (
                codigo.includes(busqueda) ||
                nombre.includes(busqueda) ||
                direccion.includes(busqueda)
            );
        });

        renderizarAlmacenes(filtrados, true);
    });

    aplicarRestriccionesInput();
});

/**
 * Mostrar esqueletos de carga visual
 */
const mostrarSkeletonLoading = function () {
    const $contenedor = $("#contenedorAlmacenes");
    let skeletonHtml = "";

    for (let i = 0; i < 6; i++) {
        skeletonHtml += `
            <div class="col-md-6 col-xl-4 skeleton-card-wrapper">
                <div class="card card-executive h-100 border-0 shadow-sm p-4" style="border-radius: 16px; border-top: 4px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="skeleton" style="width: 48px; height: 48px; border-radius: 12px; background: #e2e8f0;"></div>
                        <div class="flex-grow-1">
                            <div class="skeleton mb-2" style="width: 70%; height: 16px; border-radius: 6px; background: #e2e8f0;"></div>
                            <div class="skeleton" style="width: 40%; height: 12px; border-radius: 4px; background: #e2e8f0;"></div>
                        </div>
                    </div>
                    <div class="skeleton mb-2 mt-3" style="width: 100%; height: 14px; border-radius: 4px; background: #e2e8f0;"></div>
                    <div class="skeleton mb-3" style="width: 80%; height: 14px; border-radius: 4px; background: #e2e8f0;"></div>
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top mt-auto">
                        <div class="skeleton" style="width: 70px; height: 30px; border-radius: 20px; background: #e2e8f0;"></div>
                        <div class="skeleton" style="width: 70px; height: 30px; border-radius: 20px; background: #e2e8f0;"></div>
                    </div>
                </div>
            </div>
        `;
    }

    $contenedor.html(skeletonHtml);
    $("#contadorAlmacenes").html(
        '<span class="spinner-border spinner-border-sm text-primary me-2"></span> Cargando almacenes...'
    );
};

/**
 * Cargar almacenes desde el servidor
 */
const cargarAlmacenes = async function () {
    mostrarSkeletonLoading();

    try {
        const respuesta = await $.ajax({
            url: urlLista,
            type: "GET",
            dataType: "json",
        });

        if (respuesta.success && Array.isArray(respuesta.data)) {
            listaAlmacenes = respuesta.data;
            renderizarAlmacenes(listaAlmacenes);
        } else {
            listaAlmacenes = [];
            renderizarAlmacenes([]);
        }
    } catch (error) {
        $("#contenedorAlmacenes").html(`
            <div class="col-12 text-center py-5">
                <div class="alert alert-danger d-inline-flex align-items-center rounded-4 shadow-sm px-4 py-3">
                    <i class="fas fa-exclamation-triangle fs-3 me-3 text-danger"></i>
                    <div class="text-start">
                        <h6 class="mb-0 fw-bold">Error al cargar los almacenes</h6>
                        <small>No se pudo conectar con el servidor. Intente nuevamente.</small>
                    </div>
                </div>
            </div>
        `);
        $("#contadorAlmacenes").html(
            '<i class="fas fa-times-circle me-1"></i> Error al cargar'
        );
    }
};

/**
 * Renderizar la lista de almacenes en Cards
 */
const renderizarAlmacenes = function (almacenes, esFiltrado = false) {
    const $contenedor = $("#contenedorAlmacenes");
    $contenedor.empty();
    const total = almacenes.length;
    const totalGeneral = listaAlmacenes.length;

    if (esFiltrado) {
        $("#contadorAlmacenes").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.72rem;">
                <i class="fas fa-filter"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} de ${totalGeneral} almacenes
            </span>`
        );
    } else {
        $("#contadorAlmacenes").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.72rem;">
                <i class="fas fa-warehouse"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} ${total === 1 ? "Almacén registrado" : "Almacenes registrados"}
            </span>`
        );
    }

    if (total === 0) {
        if (esFiltrado) {
            $contenedor.html(`
                <div class="col-12 text-center py-5">
                    <div class="card card-executive border-0 shadow-sm p-5 mx-auto" style="max-width: 500px;">
                        <i class="fas fa-search text-muted opacity-50 mb-3" style="font-size: 3.5rem;"></i>
                        <h5 class="fw-bold text-dark mb-1">Sin resultados</h5>
                        <p class="text-muted small mb-0">No se encontraron almacenes que coincidan con tu búsqueda.</p>
                    </div>
                </div>
            `);
        } else {
            $contenedor.html(`
                <div class="col-12 text-center py-5">
                    <div class="card card-executive border-0 shadow-sm p-5 mx-auto" style="max-width: 520px;">
                        <div class="avatar-executive mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.8rem; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca;">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Comienza agregando tu primer Almacén</h4>
                        <p class="text-muted small mb-4">Registra una bodega, depósito o sucursal física para gestionar existencias de inventario y transferencias.</p>
                        <button type="button" class="btn btn-primary btn-gradient-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center mx-auto" onclick="crear()">
                            <i class="fas fa-plus me-2"></i> Nuevo Almacén
                        </button>
                    </div>
                </div>
            `);
        }
        return;
    }

    almacenes.forEach((almacen) => {
        const nombre = (almacen.nombre || "").trim();
        const codigo = (almacen.codigo || "").trim();
        const direccion = (almacen.direccion || "").trim();

        const cardHtml = `
            <div class="col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm hover-lift transition-all" style="border-radius: 16px; overflow: hidden; border-top: 4px solid #4f46e5 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <!-- ENCABEZADO DE LA CARD -->
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-executive shadow-sm" style="width: 48px; height: 48px; font-size: 1.25rem; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); color: #ffffff; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-warehouse"></i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <h5 class="fw-bold text-dark text-capitalize mb-1" style="letter-spacing: -0.01em; line-height: 1.2;">${nombre}</h5>
                                        <div>
                                            <span class="badge-documento"><i class="fas fa-barcode"></i> ${codigo}</span>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge-activo" style="font-size: 0.75rem;"><i class="fas fa-check-circle me-1"></i>Activo</span>
                            </div>

                            <hr class="my-3 opacity-10">

                            <!-- DETALLES DE UBICACIÓN -->
                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-map-marker-alt text-danger me-2 mt-1 opacity-75" style="width: 16px;"></i>
                                    <span class="small text-dark text-truncate-2" style="font-size: 0.83rem; line-height: 1.35;" title="${direccion || ''}">
                                        ${direccion || '<span class="text-muted fst-italic">Sin dirección registrada</span>'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER Y BOTONES DE ACCIÓN -->
                        <div class="pt-3 border-top mt-3 d-flex justify-content-end align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                onclick="editar(${almacen.id})" style="font-size: 0.8rem;">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-semibold d-inline-flex align-items-center gap-1"
                                onclick="eliminar(${almacen.id}, '${nombre.replace(/'/g, "\\'")}')" style="font-size: 0.8rem;">
                                <i class="fas fa-trash-alt"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $contenedor.append(cardHtml);
    });
};

/**
 * Abrir modal para crear nuevo almacén
 */
const crear = function () {
    isEditar = false;
    idAlmacenActual = null;
    urlAccion = urlGuardar;

    $("#formularioAlmacen")[0].reset();
    $("#modalAlmacen .is-invalid").removeClass("is-invalid");
    $("#modalAlmacen .invalid-feedback").remove();

    $("#modalAlmacenTitulo").text("Nuevo Almacén");
    $("#modalAlmacenSubtitulo").text("Completa la información del almacén o bodega");
    $("#modalAlmacenIcono").attr("class", "fas fa-warehouse text-warning fs-5");
    $("#modalAlmacenTextoGuardar").text("Guardar");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAlmacen"));
    modal.show();
};

/**
 * Abrir modal para editar almacén
 */
const editar = async function (id) {
    isEditar = true;
    idAlmacenActual = id;
    urlAccion = urlEditar + id;

    $("#formularioAlmacen")[0].reset();
    $("#modalAlmacen .is-invalid").removeClass("is-invalid");
    $("#modalAlmacen .invalid-feedback").remove();

    $("#modalAlmacenTitulo").text("Editar Almacén");
    $("#modalAlmacenSubtitulo").text("Modifica los datos del almacén seleccionado");
    $("#modalAlmacenIcono").attr("class", "fas fa-edit text-warning fs-5");
    $("#modalAlmacenTextoGuardar").text("Actualizar");

    const datos = await consultarRegistro(urlDetalles, id);
    if (!datos) return;

    $("#codigo").val(datos.codigo || "");
    $("#nombre").val(datos.nombre || "");
    $("#direccion").val(datos.direccion || "");

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalAlmacen"));
    modal.show();
};

/**
 * Procesar envío del formulario (Crear / Actualizar)
 */
$("#formularioAlmacen").on("submit", function (e) {
    e.preventDefault();

    const codigo = $("#codigo").val().trim();
    const nombre = $("#nombre").val().trim();

    if (codigo.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El código debe tener al menos 2 caracteres",
            });
        }
    }

    if (nombre.length < 2) {
        if (window.notificacion) {
            return window.notificacion.fire({
                icon: "warning",
                title: "El nombre del almacén debe tener al menos 2 caracteres",
            });
        }
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalAlmacen",
        btnSubmit: "#modalAlmacenBtnGuardar",
        textoGuardarOriginal: $("#modalAlmacenTextoGuardar").text(),
        onSuccess: function () {
            cargarAlmacenes();
        },
    });
});

/**
 * Eliminar Almacén (Borrado Lógico)
 */
const eliminar = function (id, nombre) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombre,
        titulo: "¿Eliminar Almacén?",
        mensaje: `Se desactivará el almacén "${nombre}". Podrás reactivarlo en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Sí, eliminar',
        onSuccess: function () {
            cargarAlmacenes();
        },
    });
};
