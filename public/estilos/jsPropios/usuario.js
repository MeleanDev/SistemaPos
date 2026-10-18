const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = `${urlBase}/lista`;
const urlDetalles = `${urlBase}/`;
const urlEliminar = `${urlBase}/`;
const urlGuardar = urlBase;
const urlEditar = `${urlBase}/actualizar/`;

let urlAccion = urlGuardar;
let isEditar = false;
let idUsuarioActual = null;
let listaUsuarios = [];
let vistaActual = localStorage.getItem("pos_usuarios_vista") || "grid";
let filtroRolActual = "";
let filtroEmpresaActual = "";

const mostrarSkeletonLoading = function () {
    const contenedorGrid = $("#contenedorUsuariosGrid");
    contenedorGrid.empty();

    for (let i = 0; i < 6; i++) {
        contenedorGrid.append(`
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-executive h-100 border-0 shadow-sm rounded-4 p-4 bg-white">
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

const actualizarContadoresRoles = function (usuarios) {
    const todos = (usuarios || []).length;
    const superAdmins = (usuarios || []).filter(u => u.rol === "SuperAdmin").length;
    const admins = (usuarios || []).filter(u => u.rol === "Admin").length;
    const operadores = (usuarios || []).filter(u => u.rol === "Operador").length;

    $("#countRolTodos").text(todos);
    $("#countRolSuperAdmin").text(superAdmins);
    $("#countRolAdmin").text(admins);
    $("#countRolOperador").text(operadores);
};

const obtenerUsuariosFiltrados = function () {
    const busqueda = ($("#buscadorUsuarios").val() || "").toLowerCase().trim();

    return listaUsuarios.filter((usuario) => {
        // Filtro por Rol
        if (filtroRolActual && usuario.rol !== filtroRolActual) {
            return false;
        }

        // Filtro por Sede / Empresa
        if (filtroEmpresaActual) {
            if (usuario.rol !== "SuperAdmin") {
                const tieneEmpresa = (usuario.empresas || []).some(
                    e => (e.nombre || "").toLowerCase() === filtroEmpresaActual.toLowerCase()
                );
                if (!tieneEmpresa) return false;
            }
        }

        // Búsqueda textual
        if (busqueda) {
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
        }

        return true;
    });
};

const renderizarUsuarios = function () {
    const usuarios = obtenerUsuariosFiltrados();
    const contenedorGrid = $("#contenedorUsuariosGrid");

    contenedorGrid.empty();

    const total = usuarios.length;
    const totalGeneral = (listaUsuarios || []).length;
    const esFiltrado = total !== totalGeneral;

    if (esFiltrado) {
        $("#contadorUsuarios").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); font-size: 0.72rem;">
                <i class="fas fa-filter"></i>
            </span>
            <span class="fw-bold text-dark font-monospace" style="font-size: 0.84rem;">
                ${total} de ${totalGeneral} usuarios
            </span>`
        );
    } else {
        $("#contadorUsuarios").html(
            `<span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.72rem;">
                <i class="fas fa-users"></i>
            </span>
            <span class="fw-bold text-dark font-monospace" style="font-size: 0.84rem;">
                ${total} ${total === 1 ? "Usuario" : "Usuarios"}
            </span>`
        );
    }

    if (total === 0) {
        const mensajeVacio = esFiltrado
            ? `
            <div class="col-12 text-center py-5">
                <div class="p-4 bg-white rounded-4 shadow-sm d-inline-block border" style="max-width: 480px;">
                    <i class="fas fa-search fs-1 text-muted opacity-50 mb-3"></i>
                    <h5 class="fw-bold text-dark">Sin resultados coincidentes</h5>
                    <p class="text-muted small mb-0">No se encontraron usuarios para los filtros seleccionados.</p>
                </div>
            </div>`
            : `
            <div class="col-12 text-center py-5">
                <div class="p-5 bg-white rounded-4 shadow-sm d-inline-block border" style="max-width: 500px;">
                    <div class="avatar-executive-md mx-auto mb-3 bg-primary-subtle text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px; font-size: 1.8rem;">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4 class="fw-bold text-dark">No hay usuarios registrados</h4>
                    <p class="text-muted small mb-4">Comienza registrando administradores u operadores para tu sistema POS.</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="crear()">
                        <i class="fas fa-plus me-1"></i> Crear Primer Usuario
                    </button>
                </div>
            </div>`;

        contenedorGrid.html(mensajeVacio);
        return;
    }

    usuarios.forEach((usuario) => {
        const nombreCompleto = `${usuario.nombre || ""} ${usuario.apellido || ""}`.trim() || usuario.name;
        const iniciales = (usuario.nombre ? usuario.nombre.substring(0, 1) : "") + (usuario.apellido ? usuario.apellido.substring(0, 1) : "");
        const inicialesDisplay = iniciales.toUpperCase() || usuario.name.substring(0, 2).toUpperCase() || "US";

        let badgeRol = "";
        let badgeRolTabla = "";
        if (usuario.rol === "SuperAdmin") {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); color: #7e22ce; border-color: #d8b4fe !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(168, 85, 247, 0.12);">
                    <i class="fas fa-crown text-warning me-1"></i> SuperAdmin
                </span>
            `;
            badgeRolTabla = `
                <span class="badge bg-white text-purple border shadow-xs rounded-pill px-2.5 py-1 fw-bold font-monospace" style="color: #7e22ce !important; border-color: #d8b4fe !important;">
                    <i class="fas fa-crown text-warning me-1"></i> SuperAdmin
                </span>
            `;
        } else if (usuario.rol === "Admin") {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e40af; border-color: #93c5fd !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(59, 130, 246, 0.12);">
                    <i class="fas fa-user-shield text-primary me-1"></i> Administrador
                </span>
            `;
            badgeRolTabla = `
                <span class="badge bg-white text-primary border border-primary-subtle shadow-xs rounded-pill px-2.5 py-1 fw-bold font-monospace">
                    <i class="fas fa-user-shield me-1"></i> Administrador
                </span>
            `;
        } else {
            badgeRol = `
                <span class="badge px-3 py-1.5 rounded-pill fw-bold border" style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); color: #0e7490; border-color: #67e8f9 !important; font-size: 0.76rem; box-shadow: 0 2px 6px rgba(6, 182, 212, 0.12);">
                    <i class="fas fa-headset text-info me-1"></i> Operador
                </span>
            `;
            badgeRolTabla = `
                <span class="badge bg-white text-info border border-info-subtle shadow-xs rounded-pill px-2.5 py-1 fw-bold font-monospace">
                    <i class="fas fa-headset me-1"></i> Operador
                </span>
            `;
        }

        let empresasHtml = "";
        let empresasTablaHtml = "";
        if (usuario.rol === "SuperAdmin") {
            empresasHtml = `
                <div class="d-inline-flex align-items-center gap-1.5 fw-semibold text-primary" style="font-size: 0.82rem;">
                    <i class="fas fa-globe text-primary me-0.5"></i>
                    <span>Acceso Global a Todas las Sedes</span>
                </div>
            `;
            empresasTablaHtml = `
                <span class="fw-semibold text-dark font-monospace" style="font-size: 0.82rem;">
                    <i class="fas fa-globe text-primary me-1"></i> Todas las Sedes
                </span>
            `;
        } else if (usuario.empresas && usuario.empresas.length > 0) {
            const badges = usuario.empresas.map(e => `
                <span class="d-inline-flex align-items-center gap-1.5 fw-semibold text-dark me-2" style="font-size: 0.82rem;">
                    <i class="fas fa-building text-primary me-0.5"></i>
                    <span>${e.nombre}</span>
                </span>
            `).join("");
            empresasHtml = `<div class="d-flex flex-wrap align-items-center">${badges}</div>`;
            empresasTablaHtml = `<div class="d-flex flex-wrap align-items-center">${badges}</div>`;
        } else {
            empresasHtml = `
                <span class="text-muted small fst-italic" style="font-size: 0.78rem;">
                    <i class="fas fa-circle-exclamation me-1 text-warning"></i> Sin sedes asignadas
                </span>
            `;
            empresasTablaHtml = `
                <span class="text-muted small" style="font-size: 0.78rem;">
                    Sin sedes
                </span>
            `;
        }

        let permisosInfoHtml = "";
        let botonPermisos = "";
        let permisosColumnaTabla = "";

        if (usuario.rol === "Operador") {
            const cantPermisos = (usuario.permisos || []).length;
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt text-primary me-1"></i> Permisos Modulares:
                    </span>
                    <button type="button" class="btn btn-sm p-0 border-0 bg-transparent" onclick="abrirModalPermisos(${usuario.id})" title="Gestionar permisos">
                        <span class="badge bg-white text-info border border-info-subtle shadow-xs px-2.5 py-1 rounded-pill font-monospace fw-bold" style="font-size: 0.73rem;">
                            <i class="fas fa-key me-1"></i> ${cantPermisos} Activos
                        </span>
                    </button>
                </div>
            `;

            botonPermisos = `
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-1 fw-bold shadow-none text-nowrap d-inline-flex align-items-center" onclick="abrirModalPermisos(${usuario.id})" title="Gestionar Permisos del Operador" style="font-size: 0.76rem;">
                    <i class="fas fa-user-lock me-1"></i> Permisos
                </button>
            `;

            permisosColumnaTabla = `
                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-0.5 fw-bold font-monospace" onclick="abrirModalPermisos(${usuario.id})" style="font-size: 0.74rem;">
                    <i class="fas fa-key me-1"></i> ${cantPermisos} activos
                </button>
            `;
        } else if (usuario.rol === "SuperAdmin") {
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt me-1" style="color: #9333ea;"></i> Nivel de Acceso:
                    </span>
                    <span class="badge bg-white text-purple border shadow-xs px-2.5 py-1 rounded-pill font-monospace fw-bold" style="color: #7e22ce !important; border-color: #d8b4fe !important; font-size: 0.73rem;">
                        <i class="fas fa-crown me-1 text-warning"></i> Control Total
                    </span>
                </div>
            `;
            permisosColumnaTabla = `<span class="badge bg-white text-purple border shadow-xs rounded-pill px-2 py-0.5 font-monospace" style="color: #7e22ce !important; border-color: #d8b4fe !important; font-size: 0.72rem;">Control Total</span>`;
        } else {
            permisosInfoHtml = `
                <div class="mt-3 pt-2.5 border-top d-flex align-items-center justify-content-between">
                    <span class="text-muted small fw-semibold" style="font-size: 0.76rem;">
                        <i class="fas fa-shield-alt text-primary me-1"></i> Perfil Administrativo:
                    </span>
                    <span class="badge bg-white text-primary border border-primary-subtle shadow-xs px-2.5 py-1 rounded-pill font-monospace fw-bold" style="font-size: 0.73rem;">
                        <i class="fas fa-user-shield me-1"></i> Gestión de Sedes
                    </span>
                </div>
            `;
            permisosColumnaTabla = `<span class="badge bg-white text-primary border border-primary-subtle shadow-xs rounded-pill px-2 py-0.5 font-monospace" style="font-size: 0.72rem;">Gestor de Sede</span>`;
        }

        // RENDER VISTA GRID (TARJETA EJECUTIVA BLANCA ELEVADA)
        const card = `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-executive h-100 border rounded-4 shadow-sm bg-white overflow-hidden position-relative" style="border-color: #edf2f7 !important;">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-executive-md rounded-4 shadow-sm d-flex align-items-center justify-content-center text-white fw-bold fs-5 flex-shrink-0" style="width: 52px; height: 52px; min-width: 52px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                                        ${inicialesDisplay}
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="fw-bold text-dark mb-1 text-capitalize text-truncate" style="font-size: 1rem; letter-spacing: -0.01em;" title="${nombreCompleto}">
                                            ${nombreCompleto}
                                        </h6>
                                        <span class="d-inline-flex align-items-center gap-2 font-monospace fw-semibold text-secondary" style="font-size: 0.82rem;">
                                            <i class="fas fa-id-card text-primary"></i>
                                            <span>${usuario.name}</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    ${badgeRol}
                                </div>
                            </div>

                            <div class="mb-3 d-flex flex-column gap-1">
                                <a href="mailto:${usuario.email}" class="text-muted text-truncate w-100 py-1 text-decoration-none d-flex align-items-center gap-2" title="${usuario.email}" style="font-size: 0.84rem;">
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
                            <small class="text-muted text-nowrap d-flex align-items-center gap-1 font-monospace" style="font-size: 0.74rem;">
                                <i class="fas fa-calendar-alt text-muted opacity-75"></i> ${usuario.created_at || 'Activo'}
                            </small>
                            <div class="d-flex align-items-center gap-1.5 flex-nowrap">
                                ${botonPermisos}
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-bold shadow-none text-nowrap" onclick="editar(${usuario.id})" title="Editar Usuario" style="font-size: 0.76rem;">
                                    <i class="fas fa-edit me-1"></i> Editar
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-0 shadow-none d-flex align-items-center justify-content-center flex-shrink-0" style="width: 30px; height: 30px;" onclick="eliminar(${usuario.id}, '${nombreCompleto.replace(/'/g, "\\'")}')" title="Eliminar Usuario">
                                    <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        contenedorGrid.append(card);

    });
};

let datatableUsuarios = null;

const inicializarDataTableUsuarios = function () {
    if (datatableUsuarios) {
        recargarDataTable("#datatable_usuarios");
        return;
    }

    datatableUsuarios = crearDataTable({
        selector: "#datatable_usuarios",
        url: urlLista,
        searchPlaceholder: "Cédula, nombre, correo, rol...",
        dataExtra: function (d) {
            d.rol = filtroRolActual || "";
            d.empresa = filtroEmpresaActual || "";
        },
        columns: [
            {
                data: "nombre",
                name: "nombre",
                className: "text-start align-middle ps-3",
                render: function (data, type, row) {
                    const nombreCompleto = `${row.nombre || ""} ${row.apellido || ""}`.trim() || row.name;
                    const n = (row.nombre || "").trim();
                    const a = (row.apellido || "").trim();
                    const iniciales = (n.charAt(0) + (a ? a.charAt(0) : "")).toUpperCase() || row.name.substring(0, 2).toUpperCase() || "US";

                    return `
                        <div class="d-flex align-items-center gap-2.5 py-1">
                            <div class="avatar-executive-sm me-1 text-white fw-bold d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); width: 36px; height: 36px; border-radius: 10px;">
                                ${iniciales}
                            </div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-dark text-capitalize" style="font-size: 0.93rem; letter-spacing: -0.01em;">${nombreCompleto}</span>
                                <small class="text-muted font-monospace" style="font-size: 0.74rem;">Registrado: ${row.created_at || 'Activo'}</small>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "name",
                name: "name",
                className: "text-center align-middle font-monospace",
                render: function (data) {
                    return `<span class="font-monospace fw-semibold text-secondary" style="font-size: 0.84rem;">${data}</span>`;
                },
            },
            {
                data: "email",
                name: "email",
                className: "text-start align-middle",
                render: function (data) {
                    return data
                        ? `<a href="mailto:${data}" class="contacto-item email" title="${data}"><i class="fas fa-envelope"></i><span class="text-truncate" style="max-width: 200px; display: inline-block; vertical-align: middle;">${data}</span></a>`
                        : '<span class="text-muted small fst-italic">Sin correo</span>';
                },
            },
            {
                data: "rol",
                name: "rol",
                className: "text-center align-middle",
                render: function (data) {
                    if (data === "SuperAdmin") {
                        return `<span class="fw-semibold text-purple font-monospace" style="color: #7e22ce !important; font-size: 0.82rem;"><i class="fas fa-crown text-warning me-1"></i>SuperAdmin</span>`;
                    } else if (data === "Admin") {
                        return `<span class="fw-semibold text-primary font-monospace" style="font-size: 0.82rem;"><i class="fas fa-user-shield me-1"></i>Administrador</span>`;
                    }
                    return `<span class="fw-semibold text-info font-monospace" style="font-size: 0.82rem;"><i class="fas fa-headset me-1"></i>Operador</span>`;
                },
            },
            {
                data: "empresas",
                name: "empresas",
                className: "text-start align-middle",
                orderable: false,
                render: function (data, type, row) {
                    if (row.rol === "SuperAdmin") {
                        return `<span class="fw-semibold text-primary font-monospace" style="font-size: 0.82rem;"><i class="fas fa-globe text-primary me-1"></i>Todas las Sedes</span>`;
                    }
                    if (data && data.length > 0) {
                        return data.map(e => `
                            <span class="d-inline-flex align-items-center gap-1.5 fw-semibold text-dark me-2" style="font-size: 0.82rem;">
                                <i class="fas fa-building text-primary me-0.5"></i>
                                <span>${e.nombre}</span>
                            </span>
                        `).join("");
                    }
                    return `<span class="text-muted small fst-italic" style="font-size: 0.78rem;">Sin sedes</span>`;
                },
            },
            {
                data: "permisos",
                name: "permisos",
                className: "text-center align-middle",
                orderable: false,
                render: function (data, type, row) {
                    if (row.rol === "SuperAdmin") {
                        return `<span class="fw-semibold text-purple font-monospace" style="color: #7e22ce !important; font-size: 0.80rem;">Control Total</span>`;
                    }
                    if (row.rol === "Admin") {
                        return `<span class="fw-semibold text-primary font-monospace" style="font-size: 0.80rem;">Gestor de Sede</span>`;
                    }
                    const count = (data || []).length;
                    return `
                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2.5 py-0.5 fw-bold font-monospace" onclick="abrirModalPermisos(${row.id})" style="font-size: 0.74rem;">
                            <i class="fas fa-key me-1"></i> ${count} activos
                        </button>
                    `;
                },
            },
            {
                data: null,
                width: "120px",
                className: "text-center align-middle pe-3",
                orderable: false,
                render: function (data, type, row) {
                    const nombreCompleto = `${row.nombre || ""} ${row.apellido || ""}`.trim() || row.name;
                    const nombreEscapado = nombreCompleto.replace(/'/g, "\\'");
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            ${row.rol === "Operador" ? `
                                <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="abrirModalPermisos(${row.id});" title="Gestionar Permisos" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-key"></i>
                                </button>
                            ` : `
                                <button type="button" class="btn btn-outline-info btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Ver detalles" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            `}
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-circle shadow-sm" onclick="editar(${row.id});" title="Editar" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle shadow-sm" onclick="eliminar(${row.id}, '${nombreEscapado}');" title="Eliminar usuario" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                },
            },
        ],
    });
};

const cambiarVista = function (vista) {
    vistaActual = vista;
    localStorage.setItem("pos_usuarios_vista", vista);

    $(".btn-toggle-vista").removeClass("active text-white").addClass("text-muted");
    if (vista === "tabla") {
        $("#btnVistaTabla").addClass("active text-white").removeClass("text-muted");
        $("#contenedorUsuariosGrid").addClass("d-none");
        $("#contenedorUsuariosTabla").removeClass("d-none");
        inicializarDataTableUsuarios();
        setTimeout(function () {
            if (datatableUsuarios) {
                datatableUsuarios.columns.adjust().responsive.recalc();
            }
        }, 50);
    } else {
        $("#btnVistaGrid").addClass("active text-white").removeClass("text-muted");
        $("#contenedorUsuariosTabla").addClass("d-none");
        $("#contenedorUsuariosGrid").removeClass("d-none");
    }
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
            "background-color": "#ffffff",
            "box-shadow": "0 4px 14px rgba(99, 102, 241, 0.15)",
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
            actualizarContadoresRoles(listaUsuarios);
            renderizarUsuarios();
            if (datatableUsuarios) {
                recargarDataTable("#datatable_usuarios");
            }
        } else {
            listaUsuarios = [];
            actualizarContadoresRoles([]);
            renderizarUsuarios();
            if (datatableUsuarios) {
                recargarDataTable("#datatable_usuarios");
            }
        }
    } catch (error) {
        $("#contenedorUsuariosGrid").html(`
            <div class="col-12 text-center py-5">
                <div class="card border rounded-4 p-4 d-inline-block bg-white shadow-sm" style="max-width: 440px;">
                    <i class="fas fa-triangle-exclamation fs-2 text-danger mb-2"></i>
                    <h6 class="mb-1 fw-bold text-dark">Error al cargar usuarios</h6>
                    <small class="text-muted d-block">No se pudo sincronizar la lista de usuarios con el servidor.</small>
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill mt-3 px-3" onclick="cargarUsuarios()">
                        <i class="fas fa-rotate-right me-1"></i> Reintentar
                    </button>
                </div>
            </div>
        `);
        $("#contadorUsuarios").html('<i class="fas fa-circle-exclamation text-danger me-1"></i> Error');
    }
};

const abrirModalPermisos = async function (id) {
    idUsuarioActual = id;
    $("#permisos_usuario_id").val(id);
    $(".modal-permiso-chk").prop("checked", false);

    const usuario = listaUsuarios.find(u => u.id === id);
    if (usuario) {
        const nombreCompleto = `${usuario.nombre || ""} ${usuario.apellido || ""}`.trim() || usuario.name;
        const iniciales = (usuario.nombre ? usuario.nombre.substring(0, 1) : "") + (usuario.apellido ? usuario.apellido.substring(0, 1) : "");
        const inicialesDisplay = iniciales.toUpperCase() || usuario.name.substring(0, 2).toUpperCase() || "OP";

        $("#permisos_nombre_display").text(nombreCompleto);
        $("#permisos_cedula_display").html(`<i class="fas fa-id-card text-primary me-2"></i><span>${usuario.name}</span>`);
        $("#permisos_avatar_display").text(inicialesDisplay);
    }

    try {
        const res = await peticionAjax({
            url: `${urlDetalles}${id}`,
            type: "GET",
        });

        if (res && res.permisos && Array.isArray(res.permisos)) {
            res.permisos.forEach(permisoName => {
                $(`.modal-permiso-chk[value="${permisoName}"]`).prop("checked", true);
            });
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById("modalPermisosUsuario")).show();
    } catch (error) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error al cargar permisos",
                text: "No se pudieron obtener los permisos del usuario seleccionado.",
            });
        }
    }
};

const toggleModuloPermisosModal = function (btn) {
    const $card = $(btn).closest(".card-modulo-permiso");
    const $checkboxes = $card.find(".modal-permiso-chk");
    const total = $checkboxes.length;
    const marcados = $checkboxes.filter(":checked").length;
    const nuevoEstado = marcados < total;

    $checkboxes.prop("checked", nuevoEstado);
};

const marcarTodosLosPermisosModal = function (estado) {
    $(".modal-permiso-chk").prop("checked", estado);
};

const crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;
    idUsuarioActual = null;

    $("#formularioUsuario")[0].reset();
    $("#tipo_cedula").val("V-");
    $("#cedula_numero").prop("readonly", false).val("");
    $("#password").prop("required", true).attr("placeholder", "Mínimo 6 caracteres");
    $("#rol").val("Operador");

    $(".card-empresa-select").each(function () {
        actualizarEstiloCardEmpresa($(this), false);
    });
    ajustarVisibilidadPorRol("Operador");

    $("#modalUsuarioTitulo").text("Nuevo Usuario");
    $("#modalUsuarioSubtitulo").text("Completa los datos de acceso, rol y sedes del usuario");
    $("#modalUsuarioIcono").attr("class", "fas fa-user-plus text-primary fs-5");
    $("#modalUsuarioBtnGuardar").prop("hidden", false).prop("disabled", false);
    $("#modalUsuarioTextoGuardar").text("Guardar Usuario");

    bootstrap.Modal.getOrCreateInstance(document.getElementById("modalUsuario")).show();
};

const editar = async function (id) {
    isEditar = true;
    urlAccion = `${urlEditar}${id}`;
    idUsuarioActual = id;

    $("#formularioUsuario")[0].reset();
    $(".card-empresa-select").each(function () {
        actualizarEstiloCardEmpresa($(this), false);
    });

    try {
        const datos = await peticionAjax({
            url: `${urlDetalles}${id}`,
            type: "GET",
        });

        if (datos.name) {
            if (typeof window.desglosarCedula === "function") {
                const doc = window.desglosarCedula(datos.name);
                $("#tipo_cedula").val(doc.tipo || doc.prefijo || "V-");
                $("#cedula_numero").val(doc.numero || "");
            } else {
                const match = datos.name.match(/^([JVGEPjvgep]-?)(.+)$/);
                if (match) {
                    const pref = (match[1].replace("-", "") + "-").toUpperCase();
                    $("#tipo_cedula").val(pref);
                    $("#cedula_numero").val(match[2]);
                } else {
                    $("#cedula_numero").val(datos.name);
                }
            }
        }
        $("#cedula_numero").prop("readonly", true);

        $("#email").val(datos.email || "");
        $("#nombre").val(datos.nombre || "");
        $("#apellido").val(datos.apellido || "");

        $("#password").prop("required", false);
        $("#password").attr("placeholder", "Dejar en blanco para no cambiar");

        $("#rol").val(datos.rol || "Operador");
        ajustarVisibilidadPorRol(datos.rol || "Operador");

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
        $("#modalUsuarioIcono").attr("class", "fas fa-user-edit text-primary fs-5");
        $("#modalUsuarioBtnGuardar").prop("hidden", false).prop("disabled", false);
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
        url: urlEliminar,
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
    cambiarVista(vistaActual);
    cargarUsuarios();

    // Eventos de Filtro y Búsqueda
    $("#buscadorUsuarios").on("input", function () {
        const val = $(this).val().trim();
        $("#btnLimpiarBuscador").toggleClass("d-none", !val);
        renderizarUsuarios();
        if (datatableUsuarios) {
            datatableUsuarios.search(val).draw();
        }
    });

    $("#btnLimpiarBuscador").on("click", function () {
        $("#buscadorUsuarios").val("");
        $(this).addClass("d-none");
        renderizarUsuarios();
        if (datatableUsuarios) {
            datatableUsuarios.search("").draw();
        }
    });

    $("#filtroEmpresa").on("change", function () {
        filtroEmpresaActual = $(this).val();
        renderizarUsuarios();
        if (datatableUsuarios) {
            recargarDataTable("#datatable_usuarios");
        }
    });

    $(".filter-role-chip").on("click", function () {
        $(".filter-role-chip").removeClass("active bg-primary text-white").addClass("bg-white border text-dark");
        $(this).addClass("active bg-primary text-white").removeClass("bg-white border text-dark");

        filtroRolActual = $(this).data("rol") || "";
        renderizarUsuarios();
        if (datatableUsuarios) {
            recargarDataTable("#datatable_usuarios");
        }
    });

    // Toggle de Vista
    $(".btn-toggle-vista").on("click", function () {
        const vista = $(this).data("vista");
        cambiarVista(vista);
    });

    $("#rol").on("change", function () {
        ajustarVisibilidadPorRol($(this).val());
    });

    $(document).on("change", ".modal-permiso-chk", function () {
        const $chk = $(this);
        const isChecked = $chk.is(":checked");
        const $card = $chk.closest(".card-modulo-permiso");
        const $verChk = $card.find('.modal-permiso-chk[value$=".ver"], .modal-permiso-chk[value$=".kardex"], .modal-permiso-chk[value$=".acceso"]');

        if (isChecked) {
            if ($verChk.length && !$verChk.is(":checked")) {
                $verChk.prop("checked", true);
            }
        } else {
            if ($chk.is($verChk)) {
                $card.find(".modal-permiso-chk").prop("checked", false);
            }
        }
    });

    $("#formularioPermisosUsuario").on("submit", function (e) {
        e.preventDefault();

        const usuarioId = $("#permisos_usuario_id").val();
        if (!usuarioId) return;

        enviarFormulario({
            form: this,
            url: `${urlBase}/${usuarioId}/permisos`,
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

        const cedulaNum = ($("#cedula_numero").val() || "").trim();
        const password = ($("#password").val() || "").trim();

        enviarFormulario({
            form: this,
            url: urlAccion,
            isEditar: isEditar,
            modalSelector: "#modalUsuario",
            btnSubmit: "#modalUsuarioBtnGuardar",
            textoGuardarOriginal: $("#modalUsuarioTextoGuardar").text(),
            antesDeEnviar: function (formData) {
                if (cedulaNum) {
                    const cedulaCompleta = $("#tipo_cedula").val() + cedulaNum;
                    formData.set("name", cedulaCompleta);
                }

                if (isEditar && !password) {
                    formData.delete("password");
                }
            },
            onSuccess: function () {
                cargarUsuarios();
            },
        });
    });

    if (typeof aplicarRestriccionesInput === "function") {
        aplicarRestriccionesInput();
    }
});
