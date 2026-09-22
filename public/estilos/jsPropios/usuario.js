const baseUrl = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = baseUrl + "/lista";
const urlDetalles = baseUrl + "/";
const urlEliminar = baseUrl + "/";
const urlGuardar = baseUrl;
const urlEditar = baseUrl + "/actualizar/";

let urlAccion = urlGuardar;
let isEditar = false;
let idUsuarioActual = null;
let listaUsuarios = [];

const mostrarSkeletonLoading = function () {
    const contenedor = $("#contenedorUsuarios");
    contenedor.empty();

    for (let i = 0; i < 6; i++) {
        contenedor.append(`
            <div class="col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="skeleton" style="width: 52px; height: 52px; border-radius: 16px;"></div>
                        <div class="flex-grow-1">
                            <div class="skeleton mb-2" style="width: 60%; height: 16px; border-radius: 6px;"></div>
                            <div class="skeleton" style="width: 40%; height: 12px; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <div class="skeleton mb-2" style="width: 80%; height: 14px; border-radius: 4px;"></div>
                    <div class="skeleton mb-3" style="width: 50%; height: 14px; border-radius: 4px;"></div>
                    <div class="skeleton mt-auto" style="width: 100%; height: 32px; border-radius: 20px;"></div>
                </div>
            </div>
        `);
    }
};

const renderizarUsuarios = function (usuarios, esBusqueda = false) {
    const contenedor = $("#contenedorUsuarios");
    contenedor.empty();

    const total = usuarios.length;
    const totalGeneral = (listaUsuarios || []).length;

    if (esBusqueda) {
        $("#contadorUsuarios").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.72rem;">
                <i class="fas fa-filter"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} de ${totalGeneral} usuarios
            </span>`
        );
    } else {
        $("#contadorUsuarios").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.72rem;">
                <i class="fas fa-users"></i>
            </span>
            <span class="fw-bold text-dark" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                ${total} ${total === 1 ? "Usuario" : "Usuarios"}
            </span>`
        );
    }

    if (total === 0) {
        if (esBusqueda) {
            contenedor.html(`
                <div class="col-12 text-center py-5">
                    <div class="p-4 bg-white rounded-4 shadow-sm d-inline-block" style="max-width: 480px;">
                        <i class="fas fa-search fs-1 text-muted opacity-50 mb-3"></i>
                        <h5 class="fw-bold text-dark">Sin resultados de búsqueda</h5>
                        <p class="text-muted small mb-0">No se encontraron usuarios que coincidan con el término ingresado.</p>
                    </div>
                </div>
            `);
        } else {
            contenedor.html(`
                <div class="col-12 text-center py-5">
                    <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block" style="max-width: 500px;">
                        <div class="avatar-executive-md mx-auto mb-3 bg-light-primary text-primary" style="width: 70px; height: 70px; font-size: 1.8rem;">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4 class="fw-bold text-dark">No hay usuarios registrados</h4>
                        <p class="text-muted small mb-4">Comienza registrando administradores u operadores para tu sistema POS.</p>
                        <button type="button" class="btn btn-primary btn-gradient-primary rounded-pill px-4 py-2 fw-bold" onclick="crear()">
                            <i class="fas fa-plus me-1"></i> Crear Primer Usuario
                        </button>
                    </div>
                </div>
            `);
        }
        return;
    }

    usuarios.forEach((usuario) => {
        const nombreCompleto = `${usuario.nombre || ""} ${usuario.apellido || ""}`.trim() || usuario.name;
        const iniciales = (usuario.nombre ? usuario.nombre.substring(0, 1) : "") + (usuario.apellido ? usuario.apellido.substring(0, 1) : "");
        const inicialesDisplay = iniciales.toUpperCase() || usuario.name.substring(0, 2).toUpperCase() || "US";

        let badgeRol = "";
        if (usuario.rol === "SuperAdmin") {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); color: #7e22ce; border-color: #d8b4fe !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(168, 85, 247, 0.12);">
                    <i class="fas fa-crown text-warning me-1"></i> SuperAdmin
                </span>
            `;
        } else if (usuario.rol === "Admin") {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af; border-color: #93c5fd !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.12);">
                    <i class="fas fa-user-shield text-primary me-1"></i> Administrador
                </span>
            `;
        } else {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); color: #0e7490; border-color: #67e8f9 !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(6, 182, 212, 0.12);">
                    <i class="fas fa-headset text-info me-1"></i> Operador
                </span>
            `;
        }

        let empresasHtml = "";
        if (usuario.rol === "SuperAdmin") {
            empresasHtml = `
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill" style="background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%); border: 1.5px solid #c7d2fe; box-shadow: 0 2px 8px rgba(99, 102, 241, 0.08);">
                    <span class="d-flex align-items-center justify-content-center rounded-circle text-white" style="width: 22px; height: 22px; min-width: 22px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); font-size: 0.68rem; box-shadow: 0 2px 6px rgba(99, 102, 241, 0.35);">
                        <i class="fas fa-globe"></i>
                    </span>
                    <span class="fw-bold" style="font-size: 0.8rem; color: #4338ca; letter-spacing: -0.01em;">
                        Acceso Global a Todas las Sedes
                    </span>
                </div>
            `;
        } else if (usuario.empresas && usuario.empresas.length > 0) {
            const badges = usuario.empresas.map(e => `
                <span class="d-inline-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill bg-white border" style="border-color: #cbd5e1; font-size: 0.76rem; font-weight: 600; color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.03);" title="RIF: ${e.rif || ''}">
                    <span class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 16px; height: 16px; font-size: 0.58rem;">
                        <i class="fas fa-building"></i>
                    </span>
                    <span>${e.nombre}</span>
                </span>
            `).join("");
            empresasHtml = `<div class="d-flex flex-wrap gap-1.5">${badges}</div>`;
        } else {
            empresasHtml = `
                <span class="badge bg-light text-muted border px-2.5 py-1 rounded-pill small fst-italic" style="font-size: 0.74rem;">
                    <i class="fas fa-exclamation-circle me-1"></i> Sin sedes asignadas
                </span>
            `;
        }

        let permisosInfoHtml = "";
        let botonPermisos = "";
        if (usuario.rol === "Operador") {
            const cantPermisos = (usuario.permisos || []).length;
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt text-primary me-1"></i> Permisos Modulares:
                    </span>
                    <span class="badge px-3 py-1 rounded-pill fw-bold border" style="background: #e0f2fe; color: #0369a1; border-color: #bae6fd; font-size: 0.73rem;">
                        <i class="fas fa-key me-1 text-info"></i> ${cantPermisos} ${cantPermisos === 1 ? "Permiso Activo" : "Permisos Activos"}
                    </span>
                </div>
            `;

            botonPermisos = `
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-1 fw-bold shadow-none text-nowrap d-inline-flex align-items-center" onclick="abrirModalPermisos(${usuario.id})" title="Gestionar Permisos del Operador" style="font-size: 0.76rem;">
                    <i class="fas fa-user-lock me-1"></i> Permisos
                </button>
            `;
        } else if (usuario.rol === "SuperAdmin") {
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt me-1" style="color: #9333ea;"></i> Nivel de Acceso:
                    </span>
                    <span class="badge px-3 py-1 rounded-pill fw-bold border" style="background: #faf5ff; color: #7e22ce; border-color: #e9d5ff; font-size: 0.73rem;">
                        <i class="fas fa-crown me-1 text-warning"></i> Control Total
                    </span>
                </div>
            `;
        } else {
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt text-primary me-1"></i> Perfil Administrativo:
                    </span>
                    <span class="badge px-3 py-1 rounded-pill fw-bold border" style="background: #eff6ff; color: #1e40af; border-color: #bfdbfe; font-size: 0.73rem;">
                        <i class="fas fa-user-shield me-1 text-primary"></i> Gestión de Sedes
                    </span>
                </div>
            `;
        }

        const card = `
            <div class="col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-executive-md rounded-4 shadow-sm d-flex align-items-center justify-content-center text-white fw-bold fs-5" style="width: 52px; height: 52px; min-width: 52px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                                        ${inicialesDisplay}
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1 text-capitalize" style="font-size: 1rem; letter-spacing: -0.01em;">
                                            ${nombreCompleto}
                                        </h6>
                                        <span class="badge-documento">
                                            <i class="fas fa-id-card me-1"></i>${usuario.name}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    ${badgeRol}
                                </div>
                            </div>

                            <div class="mb-3 d-flex flex-column gap-1">
                                <a href="mailto:${usuario.email}" class="contacto-item email text-truncate w-100 py-1" title="${usuario.email}">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <span class="text-truncate">${usuario.email}</span>
                                </a>
                            </div>

                            <div class="mb-2">
                                <div class="d-flex align-items-center gap-1 text-muted fw-bold mb-1.5" style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                    <i class="fas fa-store-alt text-primary me-1"></i> Sedes Autorizadas:
                                </div>
                                ${empresasHtml}
                            </div>

                            ${permisosInfoHtml}
                        </div>

                        <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between flex-nowrap gap-2">
                            <small class="text-muted text-nowrap d-flex align-items-center gap-1" style="font-size: 0.74rem;">
                                <i class="fas fa-calendar-alt text-muted opacity-75"></i> ${usuario.created_at || 'Activo'}
                            </small>
                            <div class="d-flex align-items-center gap-1.5 flex-nowrap">
                                ${botonPermisos}
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-bold shadow-none text-nowrap" onclick="editar(${usuario.id})" title="Editar Usuario" style="font-size: 0.76rem;">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-0 shadow-none d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px;" onclick="eliminar(${usuario.id}, '${nombreCompleto}')" title="Eliminar Usuario">
                                    <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        contenedor.append(card);
    });
};

const ajustarVisibilidadPorRol = function (rol) {
    const contenedorEmpresas = $("#contenedorSeccionEmpresas");

    if (rol === "SuperAdmin") {
        contenedorEmpresas.slideUp(200);
        $(".card-empresa-select").each(function () {
            actualizarEstiloCardEmpresa($(this), false);
        });
    } else {
        contenedorEmpresas.slideDown(200);
    }
};

const actualizarEstiloCardEmpresa = function (card, checked) {
    const checkbox = card.find(".check-empresa");
    checkbox.prop("checked", checked);

    const indicator = card.find(".check-empresa-indicator");
    const icon = indicator.find(".fa-check");

    if (checked) {
        card.css({
            "border-color": "#6366f1",
            "background-color": "#eef2ff",
            "box-shadow": "0 4px 14px rgba(99, 102, 241, 0.12)",
        });
        indicator.css({
            "background-color": "#6366f1",
            "border-color": "#6366f1",
        });
        icon.removeClass("d-none");
    } else {
        card.css({
            "border-color": "#e2e8f0",
            "background-color": "#ffffff",
            "box-shadow": "none",
        });
        indicator.css({
            "background-color": "#ffffff",
            "border-color": "#cbd5e1",
        });
        icon.addClass("d-none");
    }
};

const toggleEmpresaCard = function (element) {
    const card = $(element);
    const checkbox = card.find(".check-empresa");
    const isChecked = checkbox.prop("checked");

    actualizarEstiloCardEmpresa(card, !isChecked);
};

const marcarTodasLasEmpresas = function () {
    const total = $(".card-empresa-select").length;
    const marcadas = $(".check-empresa:checked").length;
    const nuevoEstado = marcadas < total;

    $(".card-empresa-select").each(function () {
        actualizarEstiloCardEmpresa($(this), nuevoEstado);
    });
};

const cargarUsuarios = async function () {
    mostrarSkeletonLoading();

    try {
        const respuesta = await peticionAjax({
            url: urlLista,
            type: "GET",
        });

        if (respuesta && respuesta.success && Array.isArray(respuesta.data)) {
            listaUsuarios = respuesta.data;
            renderizarUsuarios(listaUsuarios);
        } else {
            listaUsuarios = [];
            renderizarUsuarios([]);
        }
    } catch (error) {
        $("#contenedorUsuarios").html(`
            <div class="col-12 text-center py-5">
                <div class="alert alert-danger d-inline-flex align-items-center rounded-4 shadow-sm px-4 py-3">
                    <i class="fas fa-exclamation-triangle fs-3 me-3 text-danger"></i>
                    <div class="text-start">
                        <h6 class="mb-0 fw-bold">Error al cargar usuarios</h6>
                        <small>No se pudo conectar con el servidor. Intente nuevamente.</small>
                    </div>
                </div>
            </div>
        `);
        $("#contadorUsuarios").html('<i class="fas fa-times-circle me-1"></i> Error al cargar');
    }
};

const abrirModalPermisos = async function (id) {
    try {
        const datos = await consultarRegistro(urlDetalles, id);

        const nombreCompleto = `${datos.nombre || ""} ${datos.apellido || ""}`.trim() || datos.name;
        const iniciales = (datos.nombre ? datos.nombre.substring(0, 1) : "") + (datos.apellido ? datos.apellido.substring(0, 1) : "");
        const inicialesDisplay = iniciales.toUpperCase() || datos.name.substring(0, 2).toUpperCase() || "OP";

        $("#permisos_usuario_id").val(datos.id);
        $("#permisos_nombre_display").text(nombreCompleto);
        $("#permisos_cedula_display").html(`<i class="fas fa-id-card me-1"></i>${datos.name}`);
        $("#permisos_avatar_display").text(inicialesDisplay);

        $(".modal-permiso-chk").prop("checked", false);

        if (Array.isArray(datos.permisos)) {
            datos.permisos.forEach(permisoName => {
                const cleanId = permisoName.replace(/\./g, "_");
                $(`#chk_perm_${cleanId}`).prop("checked", true);
            });
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalPermisosUsuario")).show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error al consultar operador",
                text: "No se pudieron obtener los datos de permisos.",
            });
        }
    }
};

const marcarTodosLosPermisosModal = function (marcar) {
    $(".modal-permiso-chk").prop("checked", marcar);
};

const toggleModuloPermisosModal = function (btn) {
    const card = $(btn).closest(".card");
    const checks = card.find(".modal-permiso-chk");
    const total = checks.length;
    const marcados = checks.filter(":checked").length;
    checks.prop("checked", marcados < total);
};

const crear = function () {
    isEditar = false;
    idUsuarioActual = null;
    urlAccion = urlGuardar;

    $("#formularioUsuario")[0].reset();

    $(".card-empresa-select").each(function () {
        actualizarEstiloCardEmpresa($(this), false);
    });

    $("#rol").val("Operador");
    ajustarVisibilidadPorRol("Operador");

    $("#password").prop("required", true);
    $("#password").attr("placeholder", "Mínimo 6 caracteres");

    $("#modalUsuarioTitulo").text("Nuevo Usuario");
    $("#modalUsuarioSubtitulo").text("Completa los datos de acceso, rol y sedes del usuario");
    $("#modalUsuarioIcono").attr("class", "fas fa-user-plus text-warning fs-5");
    $("#modalUsuarioTextoGuardar").text("Guardar Usuario");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalUsuario")).show();
};

const editar = async function (id) {
    try {
        isEditar = true;
        idUsuarioActual = id;
        urlAccion = urlEditar + id;

        const datos = await consultarRegistro(urlDetalles, id);

        $("#formularioUsuario")[0].reset();

        const desglosado = desglosarCedula(datos.name);
        $("#tipo_cedula").val(desglosado.tipo || desglosado.prefijo || "V-").trigger("change");
        $("#cedula_numero").val(desglosado.numero);

        $("#email").val(datos.email);
        $("#nombre").val(datos.nombre);
        $("#apellido").val(datos.apellido);

        $("#password").prop("required", false);
        $("#password").attr("placeholder", "Dejar en blanco para no cambiar");

        $("#rol").val(datos.rol || "Operador");
        ajustarVisibilidadPorRol(datos.rol || "Operador");

        $(".card-empresa-select").each(function () {
            actualizarEstiloCardEmpresa($(this), false);
        });

        if (Array.isArray(datos.empresas)) {
            datos.empresas.forEach(empId => {
                const card = $(`#card_empresa_${empId}`);
                if (card.length) {
                    actualizarEstiloCardEmpresa(card, true);
                }
            });
        }

        $("#modalUsuarioTitulo").text("Editar Usuario");
        $("#modalUsuarioSubtitulo").text(`Modificando credenciales y sedes de ${datos.nombre || datos.name}`);
        $("#modalUsuarioIcono").attr("class", "fas fa-user-edit text-warning fs-5");
        $("#modalUsuarioTextoGuardar").text("Actualizar Usuario");

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalUsuario")).show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error al consultar usuario",
                text: "No se pudieron obtener los datos del usuario seleccionado.",
            });
        }
    }
};

const eliminar = function (id, nombre) {
    cambiarEstadoRegistro({
        url: urlEliminar + id,
        id: id,
        nombre: `al usuario "${nombre}"`,
        titulo: "¿Eliminar usuario del sistema?",
        mensaje: `Se dará de baja al usuario "${nombre}". No podrá ingresar al sistema.`,
        onSuccess: function () {
            cargarUsuarios();
        },
    });
};

$(document).ready(function () {
    cargarUsuarios();

    $("#buscadorUsuarios").on("input", function () {
        const busqueda = $(this).val().toLowerCase().trim();

        if (!busqueda) {
            renderizarUsuarios(listaUsuarios);
            return;
        }

        const filtrados = listaUsuarios.filter((usuario) => {
            const cedula = (usuario.name || "").toLowerCase();
            const nombre = (usuario.nombre || "").toLowerCase();
            const apellido = (usuario.apellido || "").toLowerCase();
            const nombreCompleto = `${nombre} ${apellido}`.trim();
            const email = (usuario.email || "").toLowerCase();
            const rol = (usuario.rol || "").toLowerCase();
            const empresasStr = (usuario.empresas || []).map(e => (e.nombre || "").toLowerCase()).join(" ");

            return (
                cedula.includes(busqueda) ||
                nombre.includes(busqueda) ||
                apellido.includes(busqueda) ||
                nombreCompleto.includes(busqueda) ||
                email.includes(busqueda) ||
                rol.includes(busqueda) ||
                empresasStr.includes(busqueda)
            );
        });

        renderizarUsuarios(filtrados, true);
    });

    $("#rol").on("change", function () {
        ajustarVisibilidadPorRol($(this).val());
    });

    $("#formularioPermisosUsuario").on("submit", function (e) {
        e.preventDefault();

        const usuarioId = $("#permisos_usuario_id").val();
        if (!usuarioId) return;

        enviarFormulario({
            form: this,
            url: `${baseUrl}/${usuarioId}/permisos`,
            isEditar: false,
            modalSelector: "#modalPermisosUsuario",
            btnSubmit: "#modalPermisosUsuarioBtnGuardar",
            textoGuardarOriginal: $("#modalPermisosUsuarioTextoGuardar").text(),
            onSuccess: function () {
                cargarUsuarios();
            },
        });
    });

    $("#formularioUsuario").on("submit", function (e) {
        e.preventDefault();

        const cedulaNum = $("#cedula_numero").val().trim();
        const nombre = $("#nombre").val().trim();
        const apellido = $("#apellido").val().trim();
        const email = $("#email").val().trim();
        const rol = $("#rol").val();
        const password = $("#password").val().trim();

        if (cedulaNum.length < 5) {
            return window.notificacion && window.notificacion.fire({
                icon: "warning",
                title: "Cédula / Identificación incompleta",
                text: "Ingresa al menos 5 dígitos numéricos.",
            });
        }

        if (nombre.length < 2 || apellido.length < 2) {
            return window.notificacion && window.notificacion.fire({
                icon: "warning",
                title: "Nombre y Apellido requeridos",
                text: "Deben tener al menos 2 caracteres cada uno.",
            });
        }

        if (!isEditar && password.length < 6) {
            return window.notificacion && window.notificacion.fire({
                icon: "warning",
                title: "Contraseña requerida",
                text: "La contraseña debe tener al menos 6 caracteres.",
            });
        }

        if (rol !== "SuperAdmin") {
            const empresasSeleccionadas = $(".check-empresa:checked").length;
            if (empresasSeleccionadas === 0) {
                return window.notificacion && window.notificacion.fire({
                    icon: "warning",
                    title: "Empresa requerida",
                    text: "Debes asignar al menos 1 sede autorizada para este usuario.",
                });
            }
        }

        enviarFormulario({
            form: this,
            url: urlAccion,
            isEditar: isEditar,
            modalSelector: "#modalUsuario",
            btnSubmit: "#modalUsuarioBtnGuardar",
            textoGuardarOriginal: $("#modalUsuarioTextoGuardar").text(),
            antesDeEnviar: function (formData) {
                const cedulaCompleta = $("#tipo_cedula").val() + cedulaNum;
                formData.set("name", cedulaCompleta);

                if (isEditar && !password) {
                    formData.delete("password");
                }
            },
            onSuccess: function () {
                cargarUsuarios();
            },
        });
    });

    aplicarRestriccionesInput();
});
