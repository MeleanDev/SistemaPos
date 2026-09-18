/**
 * Componente DataTable para el Sistema POS
 * Simplifica y estandariza la inicialización y comportamiento de tablas interactivas
 */

/**
 * Inicializa un DataTable con la configuración ejecutiva del sistema POS.
 * 
 * @param {Object} config
 * @param {string|jQuery} config.selector - Selector jQuery de la tabla (ej. '#datatable_clientes')
 * @param {string} config.url - URL del endpoint que retorna los datos JSON de DataTables
 * @param {Array} config.columns - Definición de columnas de DataTables
 * @param {Function|Object} [config.dataExtra] - Parámetros extra GET a enviar (filtros, etc.)
 * @param {Array} [config.order] - Orden inicial [[colIndex, 'asc'|'desc']]
 * @param {Array} [config.columnDefs] - Definiciones adicionales de columnas
 * @param {number} [config.pageLength=10] - Cantidad de registros por página por defecto
 * @param {string} [config.searchPlaceholder] - Placeholder personalizado para el buscador
 * @returns {Object} Instancia del DataTable
 */
window.crearDataTable = function (config) {
    if (!config.selector || !config.url || !config.columns) {
        console.error("crearDataTable: 'selector', 'url' y 'columns' son obligatorios.", config);
        return null;
    }

    const idioma = Object.assign({}, window.CONFIG_POS?.DATATABLE_LENGUAJE_ES || {
        sSearch: "Buscar:",
        searchPlaceholder: "Buscar...",
        zeroRecords: "No se encontraron registros",
        emptyTable: "No hay registros disponibles",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 al 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros)",
        oPaginate: { sFirst: "Primero", sLast: "Último", sNext: "Siguiente", sPrevious: "Anterior" },
        sProcessing: "Cargando datos...",
    });

    if (config.searchPlaceholder) {
        idioma.searchPlaceholder = config.searchPlaceholder;
    }

    const opciones = {
        ajax: {
            url: config.url,
            type: "GET",
            data: typeof config.dataExtra === "function" ? config.dataExtra : function (d) {
                if (config.dataExtra && typeof config.dataExtra === "object") {
                    Object.assign(d, config.dataExtra);
                }
            },
        },
        responsive: config.responsive !== undefined ? config.responsive : true,
        processing: config.processing !== undefined ? config.processing : true,
        serverSide: config.serverSide !== undefined ? config.serverSide : true,
        pageLength: config.pageLength || 10,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100],
        ],
        columns: config.columns,
        language: idioma,
    };

    if (config.order) {
        opciones.order = config.order;
    }

    if (config.columnDefs) {
        opciones.columnDefs = config.columnDefs;
    }

    return $(config.selector).DataTable(opciones);
};

/**
 * Recarga los datos de un DataTable específico.
 * 
 * @param {string|jQuery} selector - Selector jQuery de la tabla
 * @param {boolean} [resetPaging=false] - Si es true, reinicia la paginación a la página 1
 */
window.recargarDataTable = function (selector, resetPaging = false) {
    if ($(selector).length && $.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().ajax.reload(null, resetPaging);
    }
};
