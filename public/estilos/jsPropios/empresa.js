const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlDetalles = urlCompleta + "/";
const urlEliminar = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlEditar = urlCompleta + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idEmpresaActual = null;
let listaEmpresas = [];

$(document).ready(function () {
    cargarEmpresas();

    $("#buscadorEmpresas").on("input", function () {
        const busqueda = $(this).val().toLowerCase().trim();

        if (!busqueda) {
            renderizarEmpresas(listaEmpresas);
            return;
        }

        const filtradas = listaEmpresas.filter((empresa) => {
            const nombre = (empresa.nombre || "").toLowerCase();
            const razon = (empresa.razon_social || "").toLowerCase();
            const rif = (empresa.rif || "").toLowerCase();
            const correo = (empresa.correo || "").toLowerCase();
            const direccion = (empresa.direccion || "").toLowerCase();

            return (
                nombre.includes(busqueda) ||
                razon.includes(busqueda) ||
                rif.includes(busqueda) ||
                correo.includes(busqueda) ||
                direccion.includes(busqueda)
            );
        });

        renderizarEmpresas(filtradas, true);
    });

    // Preview en vivo al seleccionar logo
    $("#logo").on("change", function () {
        const archivo = this.files[0];
        if (archivo) {
            const lector = new FileReader();
            lector.onload = function (e) {
                $("#previewLogo").attr("src", e.target.result).removeClass("d-none");
                $("#iconoPlaceholderLogo").addClass("d-none");
            };
            lector.readAsDataURL(archivo);
        } else {
            resetPreviewLogo();
        }
    });

    aplicarRestriccionesInput();
});

const resetPreviewLogo = function () {
    $("#previewLogo").attr("src", "").addClass("d-none");
    $("#iconoPlaceholderLogo").removeClass("d-none");
    $("#logo").val("");
};

const cargarEmpresas = async function () {
    mostrarSkeletonLoading();

    try {
        const respuesta = await $.ajax({
            url: urlLista,
            type: "GET",
            dataType: "json",
        });

        if (respuesta.success && Array.isArray(respuesta.data)) {
            listaEmpresas = respuesta.data;
            renderizarEmpresas(listaEmpresas);
        } else {
            listaEmpresas = [];
            renderizarEmpresas([]);
        }
    } catch (error) {
        $("#contenedorEmpresas").html(`
            <div class="col-12 text-center py-5">
                <div class="alert alert-danger d-inline-flex align-items-center rounded-4 shadow-sm px-4 py-3">
                    <i class="fas fa-exclamation-triangle fs-3 me-3 text-danger"></i>
                    <div class="text-start">
                        <h6 class="mb-0 fw-bold">Error al cargar las empresas</h6>
                        <small>No se pudo conectar con el servidor. Intente nuevamente.</small>
                    </div>
                </div>
            </div>
        `);
        $("#contadorEmpresas").html(
            '<i class="fas fa-times-circle me-1"></i> Error al cargar',
        );
    }
};

const renderizarEmpresas = function (empresas, esFiltrado = false) {
    const $contenedor = $("#contenedorEmpresas");
    $contenedor.empty();
    const total = empresas.length;
    const totalGeneral = listaEmpresas.length;

    if (esFiltrado) {
        $("#contadorEmpresas").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.72rem;">
                <i class="fas fa-filter"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} de ${totalGeneral} empresas
            </span>`
        );
    } else {
        $("#contadorEmpresas").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.72rem;">
                <i class="fas fa-building"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} ${total === 1 ? "Empresa activa" : "Empresas activas"}
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
                        <p class="text-muted small mb-0">No se encontraron empresas que coincidan con tu búsqueda.</p>
                    </div>
                </div>
            `);
        } else {
            $contenedor.html(`
                <div class="col-12 text-center py-5">
                    <div class="card card-executive border-0 shadow-sm p-5 mx-auto" style="max-width: 520px;">
                        <div class="avatar-executive mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.8rem; background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #4338ca;">
                            <i class="fas fa-building"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Comienza agregando tu primera Empresa</h4>
                        <p class="text-muted small mb-4">Crea una razón social o sede para comenzar a gestionar almacenes, inventarios, cajas y facturas.</p>
                        <button type="button" class="btn btn-primary btn-gradient-primary rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center mx-auto" onclick="crear()">
                            <i class="fas fa-plus me-2"></i> Nueva Empresa
                        </button>
                    </div>
                </div>
            `);
        }
        return;
    }

    empresas.forEach((empresa) => {
        const nombre = (empresa.nombre || "").trim();
        const iniciales = nombre.substring(0, 2).toUpperCase() || "EM";
        const rif = empresa.rif
            ? `<span class="badge-documento"><i class="fas fa-id-card"></i>${empresa.rif}</span>`
            : '<span class="text-muted small">Sin RIF</span>';

        const logoHtml = empresa.logo
            ? `<div class="avatar-executive shadow-sm border bg-white p-1" style="width: 48px; height: 48px; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <img src="/storage/${empresa.logo}" alt="${nombre}" style="max-width: 100%; max-height: 100%; object-fit: contain;">
               </div>`
            : `<div class="avatar-executive shadow-sm" style="width: 48px; height: 48px; font-size: 1.1rem; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); color: #ffffff; border-radius: 12px;">
                    ${iniciales}
               </div>`;

        const telefonoHtml = empresa.telefono
            ? `<a href="tel:${empresa.telefono}" class="contacto-item phone mb-1" title="Llamar"><i class="fas fa-phone-alt"></i><span>${empresa.telefono}</span></a>`
            : '<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="fas fa-minus text-muted opacity-50 me-1"></i>Sin teléfono</span>';

        const correoHtml = empresa.correo
            ? `<a href="mailto:${empresa.correo}" class="contacto-item email mb-1 text-truncate" style="max-width: 100%;" title="${empresa.correo}"><i class="fas fa-envelope"></i><span class="text-truncate">${empresa.correo}</span></a>`
            : '<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="fas fa-minus text-muted opacity-50 me-1"></i>Sin correo</span>';

        const motoBadgeHtml = empresa.maneja_motos
            ? `<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold" style="font-size: 0.72rem;"><i class="fas fa-motorcycle me-1"></i>Motos & Seriales</span>`
            : `<span class="badge rounded-pill bg-light text-muted border px-2 py-1" style="font-size: 0.72rem;"><i class="fas fa-boxes-stacked me-1"></i>Retail Estándar</span>`;

        const cardHtml = `
            <div class="col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm hover-lift transition-all" style="border-radius: 16px; overflow: hidden; border-top: 4px solid #4f46e5 !important;">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <!-- ENCABEZADO DE LA CARD -->
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    ${logoHtml}
                                    <div class="d-flex flex-column">
                                        <h5 class="fw-bold text-dark text-capitalize mb-1" style="letter-spacing: -0.01em; line-height: 1.2;">${nombre}</h5>
                                        <div>${rif}</div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <span class="badge-activo" style="font-size: 0.75rem;"><i class="fas fa-check-circle me-1"></i>Activa</span>
                                    ${motoBadgeHtml}
                                </div>
                            </div>

                            <hr class="my-3 opacity-10">

                            <!-- DETALLES DE LA EMPRESA -->
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-landmark text-primary me-2 opacity-75" style="width: 16px;"></i>
                                    <span class="small fw-semibold text-secondary text-truncate" title="${empresa.razon_social || ''}">${empresa.razon_social || '<span class="text-muted fst-italic">Sin razón social</span>'}</span>
                                </div>
                                <div class="d-flex align-items-start mb-2">
                                    <i class="fas fa-map-marker-alt text-danger me-2 mt-1 opacity-75" style="width: 16px;"></i>
                                    <span class="small text-dark text-truncate-2" style="font-size: 0.83rem; line-height: 1.35;" title="${empresa.direccion || ''}">${empresa.direccion || '<span class="text-muted fst-italic">Sin dirección fiscal</span>'}</span>
                                </div>
                            </div>

                            <!-- CONTACTO -->
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 pt-1">
                                ${telefonoHtml}
                                ${correoHtml}
                            </div>
                        </div>

                        <!-- FOOTER Y BOTONES DE ACCIÓN -->
                        <div class="pt-3 border-top mt-2 d-flex justify-content-end align-items-center gap-2">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center" onclick="ver(${empresa.id})" title="Ver detalles completos">
                                <i class="fas fa-eye me-1"></i> Ver
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center" onclick="editar(${empresa.id})" title="Editar empresa">
                                <i class="fas fa-edit me-1"></i> Editar
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-sm d-inline-flex align-items-center" onclick="eliminar(${empresa.id}, '${nombre}')" title="Eliminar empresa">
                                <i class="fas fa-trash-alt me-1"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $contenedor.append(cardHtml);
    });
};

const mostrarSkeletonLoading = function () {
    let skeletons = "";
    for (let i = 0; i < 3; i++) {
        skeletons += `
            <div class="col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm p-4" style="border-radius: 16px;">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="placeholder-glow" style="width: 48px; height: 48px;"><span class="placeholder col-12 h-100 rounded-3"></span></div>
                        <div class="placeholder-glow w-75">
                            <span class="placeholder col-8 mb-1"></span>
                            <span class="placeholder col-5"></span>
                        </div>
                    </div>
                    <hr class="my-3 opacity-10">
                    <div class="placeholder-glow mb-3">
                        <span class="placeholder col-10 mb-2"></span>
                        <span class="placeholder col-12 mb-2"></span>
                    </div>
                    <div class="placeholder-glow d-flex gap-2">
                        <span class="placeholder col-4 rounded-pill"></span>
                        <span class="placeholder col-6 rounded-pill"></span>
                    </div>
                </div>
            </div>
        `;
    }
    $("#contenedorEmpresas").html(skeletons);
};

const crear = function () {
    isEditar = false;
    idEmpresaActual = null;
    urlAccion = urlGuardar;

    $("#modalEmpresa").modal("show");
    $("#modalEmpresaTituloTexto").text("Nueva Empresa");
    $("#modalEmpresaSubtituloTexto").text(
        "Completa la información de la empresa o sede",
    );
    $("#modalEmpresaIcono").attr(
        "class",
        "fas fa-building me-2 text-warning fs-5",
    );

    $("#formularioEmpresa")[0].reset();
    resetPreviewLogo();
    $("#maneja_motos").prop("checked", false);
    $("#formularioEmpresa")
        .find("input, select, textarea")
        .prop("disabled", false);
    $("#tipo_cedula").val("J-");
    $("#codigo_pais").val("+58");

    $("#modalEmpresaBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalEmpresaTextoGuardar").text("Guardar");
};

const ver = async function (id) {
    try {
        idEmpresaActual = id;
        const empresa = await consultarRegistro(urlDetalles, id);

        $("#modalEmpresa").modal("show");
        $("#modalEmpresaTituloTexto").text("Detalles de la Empresa");
        $("#modalEmpresaSubtituloTexto").text(
            "Consulta la información de la empresa",
        );
        $("#modalEmpresaIcono").attr("class", "fas fa-eye me-2 text-info fs-5");

        llenarFormularioEmpresa(empresa);

        $("#formularioEmpresa")
            .find("input, select, textarea")
            .prop("disabled", true);
        $("#modalEmpresaBtnGuardar").prop("hidden", true);
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudieron cargar los datos de la empresa.",
        });
    }
};

const editar = async function (id) {
    try {
        isEditar = true;
        idEmpresaActual = id;
        urlAccion = urlEditar + id;
        const empresa = await consultarRegistro(urlDetalles, id);

        $("#modalEmpresa").modal("show");
        $("#modalEmpresaTituloTexto").text(`Editar Empresa: ${empresa.nombre}`);
        $("#modalEmpresaSubtituloTexto").text(
            "Modifica los datos de la empresa",
        );
        $("#modalEmpresaIcono").attr(
            "class",
            "fas fa-edit me-2 text-warning fs-5",
        );

        $("#formularioEmpresa")
            .find("input, select, textarea")
            .prop("disabled", false);
        llenarFormularioEmpresa(empresa);

        $("#modalEmpresaBtnGuardar")
            .prop("hidden", false)
            .prop("disabled", false);
        $("#modalEmpresaTextoGuardar").text("Actualizar Cambios");
    } catch (error) {
        notificacion.fire({
            icon: "error",
            title: "Error",
            text: "No se pudo cargar la información de la empresa.",
        });
    }
};

const llenarFormularioEmpresa = (data) => {
    $("#nombre").val(data.nombre || "");
    $("#razon_social").val(data.razon_social || "");
    $("#correo").val(data.correo || "");
    $("#direccion").val(data.direccion || "");
    $("#logo").val("");
    $("#maneja_motos").prop("checked", !!data.maneja_motos);

    if (data.logo) {
        $("#previewLogo").attr("src", "/storage/" + data.logo).removeClass("d-none");
        $("#iconoPlaceholderLogo").addClass("d-none");
    } else {
        resetPreviewLogo();
    }

    const rif = desglosarCedula(data.rif);
    $("#tipo_cedula").val(rif.tipo || "J-");
    $("#cedula_numero").val(rif.numero || "");

    const telefono = desglosarTelefono(data.telefono);
    $("#codigo_pais").val(telefono.codigo || "+58");
    $("#telefono_numero").val(telefono.numero || "");
};

const eliminar = function (id, nombreEmpresa) {
    cambiarEstadoRegistro({
        url: urlEliminar,
        id: id,
        nombre: nombreEmpresa,
        titulo: "¿Eliminar Empresa?",
        mensaje: `Se desactivará la empresa "${nombreEmpresa}". Podrás reactivarla en cualquier momento.`,
        confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Sí, eliminar',
        onSuccess: function () {
            cargarEmpresas();
        },
    });
};

$("#formularioEmpresa").on("submit", function (e) {
    e.preventDefault();

    const nombre = $("#nombre").val().trim();
    const razonSocial = $("#razon_social").val().trim();
    const rifNum = $("#cedula_numero").val().trim();
    const direccion = $("#direccion").val().trim();

    if (nombre.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "El nombre comercial debe tener al menos 2 caracteres",
        });
    }

    if (razonSocial.length < 2) {
        return notificacion.fire({
            icon: "warning",
            title: "La razón social debe tener al menos 2 caracteres",
        });
    }

    if (rifNum.length < 5) {
        return notificacion.fire({
            icon: "warning",
            title: "Número de RIF / Identificación incompleto",
        });
    }

    if (direccion.length < 3) {
        return notificacion.fire({
            icon: "warning",
            title: "La dirección fiscal es obligatoria",
        });
    }

    enviarFormulario({
        form: this,
        url: urlAccion,
        isEditar: isEditar,
        modalSelector: "#modalEmpresa",
        btnSubmit: "#modalEmpresaBtnGuardar",
        textoGuardarOriginal: $("#modalEmpresaTextoGuardar").text(),
        antesDeEnviar: function (formData) {
            const rifCompleto = $("#tipo_cedula").val() + rifNum;
            formData.set("rif", rifCompleto);

            const telNum = $("#telefono_numero").val().trim();
            if (telNum) {
                formData.set("telefono", $("#codigo_pais").val() + telNum);
            } else {
                formData.delete("telefono");
            }

            formData.set("maneja_motos", $("#maneja_motos").is(":checked") ? "1" : "0");
        },
        onSuccess: function () {
            cargarEmpresas();
        },
    });
});
