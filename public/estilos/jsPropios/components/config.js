/**
 * Configuración y Constantes Globales para el Sistema POS
 */
window.CONFIG_POS = {
    // Códigos telefónicos internacionales soportados
    CODIGOS_PAIS: [
        { codigo: "+58", pais: "Venezuela", bandera: "🇻🇪" },
        { codigo: "+1", pais: "Estados Unidos / Canadá", bandera: "🇺🇸" },
        { codigo: "+57", pais: "Colombia", bandera: "🇨🇴" },
        { codigo: "+56", pais: "Chile", bandera: "🇨🇱" },
        { codigo: "+51", pais: "Perú", bandera: "🇵🇪" },
        { codigo: "+55", pais: "Brasil", bandera: "🇧🇷" },
        { codigo: "+593", pais: "Ecuador", bandera: "🇪🇨" },
        { codigo: "+34", pais: "España", bandera: "🇪🇸" },
        { codigo: "+54", pais: "Argentina", bandera: "🇦🇷" },
        { codigo: "+507", pais: "Panamá", bandera: "🇵🇦" },
        { codigo: "+52", pais: "México", bandera: "🇲🇽" },
    ],

    // Prefijos de identificación / RIF soportados
    PREFIJOS_CEDULA: ["V-", "J-", "E-", "G-", "P-"],

    // Diccionario de traducción estándar en Español para DataTables
    DATATABLE_LENGUAJE_ES: {
        sSearch: "Buscar:",
        searchPlaceholder: "Buscar en la tabla...",
        zeroRecords: "No se encontraron registros coincidentes",
        emptyTable: "No hay datos disponibles en la tabla",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 al 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros en total)",
        oPaginate: {
            sFirst: "Primero",
            sLast: "Último",
            sNext: "Siguiente",
            sPrevious: "Anterior",
        },
        sProcessing: "Cargando datos...",
    },
};
