/**
 * Componente Consultor AJAX para el Sistema POS
 * Centraliza las peticiones de consulta de registros individuales y llamadas HTTP
 */

/**
 * Consulta los datos de un registro específico por su ID.
 * 
 * @param {string} urlBase - URL base del módulo (ej. /clientes/ o /proveedores/)
 * @param {number|string} id - ID del registro a consultar
 * @returns {Promise} jQuery AJAX Promise
 */
window.consultarRegistro = function (urlBase, id, callback) {
    const urlLimpia = urlBase.endsWith("/") ? urlBase : `${urlBase}/`;
    const peticion = $.ajax({
        url: `${urlLimpia}${id}`,
        type: "GET",
        dataType: "json",
    });

    if (typeof callback === "function") {
        peticion.done(callback);
    }

    return peticion;
};

/**
 * Petición AJAX genérica con manejo de tipos estándar.
 * 
 * @param {Object} opciones
 * @param {string} opciones.url - URL de la petición
 * @param {string} [opciones.method='GET'] - Método HTTP
 * @param {Object|FormData} [opciones.data=null] - Datos a enviar
 * @param {string} [opciones.dataType='json'] - Tipo de respuesta
 * @returns {Promise} jQuery AJAX Promise
 */
window.peticionAjax = function (opciones) {
    const config = Object.assign(
        {
            method: "GET",
            dataType: "json",
        },
        opciones
    );

    return $.ajax(config);
};
