<!-- Configuración Global POS (Single Source of Truth) -->
<script>
    window.CONFIG_POS = {
        CODIGOS_PAIS: @json(config('pos.codigos_pais')),
        PREFIJOS_CEDULA: @json(array_values(config('pos.prefijos_cedula'))),
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
</script>

<!-- Componentes JS Reutilizables del Sistema POS -->
<script src="{{ asset('estilos/jsPropios/components/consultor.component.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/components/consultor.component.js')) ?: time() }}"></script>
<script src="{{ asset('estilos/jsPropios/components/datatable.component.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/components/datatable.component.js')) ?: time() }}"></script>
<script src="{{ asset('estilos/jsPropios/components/formulario.component.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/components/formulario.component.js')) ?: time() }}"></script>
<script src="{{ asset('estilos/jsPropios/components/utilidades.component.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/components/utilidades.component.js')) ?: time() }}"></script>

