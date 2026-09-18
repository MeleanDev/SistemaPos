<link rel="stylesheet" href="{{ asset('estilos/assets/extra-libs/datatables.net-bs4/css/dataTables.bootstrap4.css') }}">
<link rel="stylesheet"
    href="{{ asset('estilos/assets/extra-libs/datatables.net-bs4/css/responsive.dataTables.min.css') }}">

<script src="{{ asset('estilos/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('estilos/assets/extra-libs/datatables.net-bs4/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('estilos/dist/js/pages/datatable/datatable-basic.init.js') }}"></script>

<style>
    /* ==========================================================================
       DATATABLES MODERN EXECUTIVE THEME - DataBioSystem
       ========================================================================== */

    .dataTables_wrapper {
        font-family: inherit;
        color: #334155;
    }

    /* --- FILA SUPERIOR: MOSTRAR REGISTROS & BUSCADOR --- */
    .dataTables_wrapper > .row:first-child {
        margin-bottom: 1.25rem !important;
        align-items: center;
        row-gap: 0.75rem;
    }

    /* Mostrar registros (Length selector) */
    .dataTables_wrapper .dataTables_length {
        display: flex;
        align-items: center;
    }

    .dataTables_wrapper .dataTables_length label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0;
        font-size: 0.85rem;
        font-weight: 500;
        color: #64748b;
    }

    .dataTables_wrapper .dataTables_length select {
        border-radius: 50rem !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
        padding: 0.35rem 2rem 0.35rem 0.85rem !important;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        background-position: right 0.75rem center;
    }

    .dataTables_wrapper .dataTables_length select:focus,
    .dataTables_wrapper .dataTables_length select:hover {
        border-color: #93c5fd !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15) !important;
        outline: none;
    }

    /* Buscador (Filter input) */
    .dataTables_wrapper .dataTables_filter {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .dataTables_wrapper .dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0;
        font-size: 0.85rem;
        font-weight: 500;
        color: #64748b;
        width: 100%;
        max-width: 320px;
        justify-content: flex-end;
    }

    .dataTables_wrapper .dataTables_filter input {
        border-radius: 50rem !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        color: #1e293b !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        padding: 0.45rem 1rem 0.45rem 2.25rem !important;
        width: 100% !important;
        min-width: 220px;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 0.75rem center;
        background-size: 1rem 1rem;
    }

    .dataTables_wrapper .dataTables_filter input:focus,
    .dataTables_wrapper .dataTables_filter input:hover {
        border-color: #6366f1 !important;
        background-color: #ffffff !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15) !important;
        outline: none;
    }

    /* --- TABLA: ENCABEZADOS Y CELDAS --- */
    table.dataTable {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        width: 100% !important;
        margin-top: 0.5rem !important;
        margin-bottom: 1.25rem !important;
    }

    table.dataTable thead th {
        background-color: #f8fafc !important;
        color: #475569 !important;
        font-size: 0.78rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        padding: 0.85rem 1rem !important;
        border-bottom: 2px solid #e2e8f0 !important;
        border-top: none !important;
        vertical-align: middle !important;
    }

    table.dataTable tbody td {
        padding: 0.85rem 1rem !important;
        vertical-align: middle !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #334155;
        font-size: 0.875rem;
    }

    table.dataTable tbody tr {
        transition: background-color 0.15s ease-in-out;
    }

    table.dataTable tbody tr:hover {
        background-color: #f8fafc !important;
    }

    /* --- FILA INFERIOR: INFO & PAGINACIÓN --- */
    .dataTables_wrapper > .row:last-child {
        margin-top: 0.75rem !important;
        align-items: center;
        row-gap: 0.75rem;
    }

    .dataTables_wrapper .dataTables_info {
        font-size: 0.85rem;
        color: #64748b;
        font-weight: 500;
        padding-top: 0.25rem;
    }

    /* Paginación moderna tipo pastillas */
    .dataTables_wrapper .dataTables_paginate {
        display: flex;
        justify-content: flex-end;
    }

    .dataTables_wrapper .dataTables_paginate ul.pagination {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 5px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .dataTables_wrapper .dataTables_paginate .page-item .page-link {
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #ffffff !important;
        color: #475569 !important;
        font-weight: 600;
        font-size: 0.85rem;
        min-width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px;
        margin: 0 !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        text-decoration: none;
    }

    .dataTables_wrapper .dataTables_paginate .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #eff6ff !important;
        border-color: #bfdbfe !important;
        color: #1d4ed8 !important;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0, 0, 0, 0.06);
    }

    /* Página activa */
    .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
        background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
        border-color: transparent !important;
        color: #ffffff !important;
        font-weight: 700;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3) !important;
        transform: translateY(-1px);
    }

    /* Botones deshabilitados (Anterior / Siguiente) */
    .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
        background-color: #f8fafc !important;
        border-color: #f1f5f9 !important;
        color: #94a3b8 !important;
        cursor: not-allowed !important;
        box-shadow: none !important;
        transform: none !important;
        opacity: 0.7;
    }

    /* Botones de Anterior y Siguiente */
    .dataTables_wrapper .dataTables_paginate .page-item.previous .page-link,
    .dataTables_wrapper .dataTables_paginate .page-item.next .page-link {
        padding: 0 14px;
        font-weight: 600;
    }

    /* --- RESPONSIVE TABLE DETAILS (Subfilas en pantallas pequeñas) --- */
    table.dataTable > tbody > tr.child ul.dtr-details {
        width: 100%;
        padding: 0;
        margin: 0;
        list-style: none;
    }
    
    table.dataTable > tbody > tr.child ul.dtr-details > li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    table.dataTable > tbody > tr.child ul.dtr-details > li:last-child {
        border-bottom: none;
    }
    
    table.dataTable > tbody > tr.child ul.dtr-details > li .dtr-title {
        font-weight: 700;
        color: #64748b;
        font-size: 0.85rem;
    }
    
    table.dataTable > tbody > tr.child ul.dtr-details > li .dtr-data {
        text-align: right;
    }

    /* --- RESPONSIVE BREAKPOINTS (Mobile & Tablet) --- */
    @media (max-width: 768px) {
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            justify-content: center !important;
            width: 100%;
        }

        .dataTables_wrapper .dataTables_filter label {
            max-width: 100%;
        }

        .dataTables_wrapper .dataTables_info {
            text-align: center !important;
            margin-bottom: 8px !important;
            white-space: normal;
        }

        .dataTables_wrapper .dataTables_paginate {
            justify-content: center !important;
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .dataTables_wrapper .dataTables_paginate ul.pagination {
            justify-content: center !important;
            gap: 4px;
        }

        .dataTables_wrapper .dataTables_paginate .page-item .page-link {
            padding: 0 8px;
            font-size: 0.8rem;
            min-width: 32px;
            height: 32px;
            border-radius: 6px !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .page-item.previous .page-link,
        .dataTables_wrapper .dataTables_paginate .page-item.next .page-link {
            padding: 0 10px;
        }
    }
</style>
