@extends('Sistema.layouts.pos-layout')

@section('titulo', 'Punto de Venta (POS)')

@push('css')
<style>
    .pos-kiosk-navbar {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border-bottom: 1px solid #334155;
    }

    .kiosk-badge-tasa {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .kiosk-btn-action {
        background: rgba(255, 255, 255, 0.08);
        color: #e2e8f0;
        border: 1px solid rgba(255, 255, 255, 0.12);
        font-size: 0.78rem;
        padding: 0.35rem 0.75rem;
        transition: all 0.15s ease;
    }
    .kiosk-btn-action:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.25);
        transform: translateY(-1px);
    }

    .pos-client-card {
        border: 1.5px solid #e0e7ff;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .btn-action-client-switch {
        background: #eef2ff;
        color: #4338ca;
        border: 1.5px solid #c7d2fe;
        transition: all 0.2s ease;
        font-size: 0.78rem;
    }
    .btn-action-client-switch:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(79, 70, 229, 0.25);
    }
    .btn-action-client-switch:hover i,
    .btn-action-client-switch:hover .text-primary {
        color: #ffffff !important;
    }
    .btn-action-client-switch:hover .badge-f2-subtle {
        background: rgba(255, 255, 255, 0.25);
        color: #ffffff;
        border-color: transparent;
    }
    .badge-f2-subtle {
        background: #ffffff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
        border-radius: 9999px;
        padding: 1px 6px;
        font-size: 0.65rem;
        font-weight: 800;
    }

    .btn-action-client-edit {
        width: 32px;
        height: 32px;
        min-width: 32px;
        border-radius: 50%;
        background: #f8fafc;
        color: #475569;
        border: 1.5px solid #e2e8f0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .btn-action-client-edit:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(79, 70, 229, 0.25);
    }

    .btn-action-start-sale {
        background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
        color: #ffffff;
        border: none;
        box-shadow: 0 3px 10px rgba(79, 70, 229, 0.35);
        transition: all 0.2s ease;
        font-size: 0.80rem;
    }
    .btn-action-start-sale:hover {
        background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.45);
    }
    .badge-f2-vibrant {
        background: #ffffff;
        color: #4f46e5;
        border-radius: 9999px;
        padding: 2px 7px;
        font-size: 0.68rem;
        font-weight: 800;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .tarifa-pill-container {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 9999px;
        padding: 2px;
        display: flex;
        align-items: center;
        gap: 2px;
    }
    .tarifa-pill-btn {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 0.78rem;
        font-weight: 700;
        border-radius: 9999px;
        padding: 0.40rem 0.6rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #64748b;
    }
    .tarifa-pill-btn:hover {
        color: #0f172a;
    }
    .tarifa-pill-btn.active-detal {
        background: #4f46e5;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);
    }
    .tarifa-pill-btn.active-mayor {
        background: #7e22ce;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(126, 34, 206, 0.3);
    }

    .pos-scanner-box {
        background: #ffffff;
        border: 2px solid #e0e7ff;
        border-radius: 9999px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
        transition: all 0.25s ease;
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.05);
    }
    .pos-scanner-box:focus-within {
        border-color: #4f46e5;
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
    }
    .pos-scanner-box .scanner-icon-wrap {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 50% !important;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        margin-right: 8px;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        flex-shrink: 0;
    }
    .pos-scanner-box input {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        outline: none !important;
        font-size: 0.90rem;
        font-family: monospace;
        color: #0f172a;
        font-weight: 600;
        padding: 0 4px;
        width: 100%;
    }

    .pos-counter-badge {
        background: #eef2ff;
        color: #3730a3;
        border: 1.5px solid #c7d2fe;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        font-size: 0.82rem;
    }

    .kbd-tag {
        display: inline-block;
        padding: 0.15rem 0.40rem;
        font-size: 0.68rem;
        font-weight: 700;
        font-family: monospace;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.35rem;
        color: #cbd5e1;
    }

    .pos-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 2px solid #e2e8f0;
        padding: 0.60rem 0.75rem;
    }
    .pos-table tbody td {
        padding: 0.50rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .pos-table tbody tr {
        transition: background-color 0.15s ease;
    }
    .pos-table tbody tr.fila-activa {
        background: #f5f7ff !important;
        border-left: 4px solid #4f46e5 !important;
    }

    .pos-qty-control {
        background: #ffffff;
        border: 1.5px solid #c7d2fe;
        border-radius: 9999px;
        padding: 2px 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 2px;
        box-shadow: 0 1px 3px rgba(79, 70, 229, 0.08);
        transition: all 0.2s ease;
    }
    .pos-qty-control:hover, .pos-qty-control:focus-within {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .pos-qty-btn, .btn-qty-circle {
        width: 26px;
        height: 26px;
        min-width: 26px;
        border-radius: 50% !important;
        border: none !important;
        background: #eef2ff;
        color: #4338ca;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.85rem;
        line-height: 1;
        padding: 0;
        cursor: pointer;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        outline: none !important;
    }
    .pos-qty-btn:hover, .btn-qty-circle:hover {
        background: #4f46e5;
        color: #ffffff;
        transform: scale(1.1);
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.3);
    }
    .pos-qty-btn:active, .btn-qty-circle:active {
        transform: scale(0.95);
    }
    .pos-qty-input, .input-qty-clean {
        width: 44px;
        border: none !important;
        background: transparent !important;
        text-align: center;
        font-weight: 800;
        font-family: monospace;
        font-size: 0.92rem;
        color: #0f172a;
        outline: none !important;
        box-shadow: none !important;
        padding: 0 2px;
        -moz-appearance: textfield;
    }
    .pos-qty-input::-webkit-outer-spin-button,
    .pos-qty-input::-webkit-inner-spin-button,
    .input-qty-clean::-webkit-outer-spin-button,
    .input-qty-clean::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .total-card-kiosk {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border: 1.5px solid #334155;
        border-radius: 1rem;
        box-shadow: 0 4px 15px rgba(15, 23, 42, 0.2);
    }

    .btn-quick-cash {
        background: #f8fafc;
        color: #1e293b;
        border: 1.5px solid #cbd5e1;
        font-family: monospace;
        font-weight: 700;
        font-size: 0.82rem;
        border-radius: 9999px;
        padding: 0.35rem 0.5rem;
        transition: all 0.15s ease;
    }
    .btn-quick-cash:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);
    }

    .btn-action-kiosk-danger {
        background: #fee2e2;
        color: #b91c1c;
        border: 1.5px solid #fca5a5;
        transition: all 0.15s ease;
    }
    .btn-action-kiosk-danger:hover {
        background: #ef4444;
        color: #ffffff;
        border-color: #ef4444;
    }
    .btn-action-kiosk-info {
        background: #e0f2fe;
        color: #0369a1;
        border: 1.5px solid #7dd3fc;
        transition: all 0.15s ease;
    }
    .btn-action-kiosk-info:hover {
        background: #0284c7;
        color: #ffffff;
        border-color: #0284c7;
    }
    .btn-action-kiosk-primary {
        background: #eef2ff;
        color: #4338ca;
        border: 1.5px solid #c7d2fe;
        transition: all 0.15s ease;
    }
    .btn-action-kiosk-primary:hover {
        background: #4f46e5;
        color: #ffffff;
        border-color: #4f46e5;
    }

    .badge-f-tag {
        border-radius: 9999px;
        padding: 1px 6px;
        font-size: 0.65rem;
        font-weight: 800;
        font-family: monospace;
        margin-left: 2px;
    }
    .badge-danger-tag {
        background: #fca5a5;
        color: #7f1d1d;
    }
    .badge-info-tag {
        background: #bae6fd;
        color: #075985;
    }
    .badge-primary-tag {
        background: #c7d2fe;
        color: #312e81;
    }

    .modal-pos-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #ffffff;
        border-bottom: 1px solid #334155;
    }
    .modal-header-icon-wrap {
        width: 40px;
        height: 40px;
        min-width: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    }

    .card-fast-sale {
        background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
        border: 1.5px solid #c7d2fe;
        border-radius: 1rem;
        transition: all 0.2s ease;
    }
    .card-fast-sale:hover {
        border-color: #818cf8;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
        transform: translateY(-1px);
    }

    .dropdown-item-hover-pos {
        background: #eef2ff !important;
        border-left: 4px solid #4f46e5 !important;
        color: #1e1b4b !important;
    }

    .banner-unregistered-client {
        background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
        border: 1.5px dashed #c7d2fe;
        border-radius: 1rem;
        padding: 0.85rem 1.25rem;
    }

    .kbd-chip {
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
        border-radius: 6px;
        padding: 2px 7px;
        font-size: 0.72rem;
        font-weight: 700;
        font-family: monospace;
    }

    .btn-pos-cancel {
        background: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1.5px solid #fca5a5 !important;
        border-radius: 9999px !important;
        padding: 0.5rem 1.4rem !important;
        font-weight: 700 !important;
        font-family: monospace !important;
        font-size: 0.82rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.35rem !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 1px 3px rgba(239, 68, 68, 0.12) !important;
        cursor: pointer !important;
        text-decoration: none !important;
    }
    .btn-pos-cancel:hover {
        background: #ef4444 !important;
        color: #ffffff !important;
        border-color: #ef4444 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25) !important;
    }
    .btn-pos-cancel i {
        color: inherit !important;
    }
</style>
@endpush

@section('contenido')
<!-- ========================================================================= -->
<!-- 1. TOP HEADER DEL POS (KIOSK TOPBAR)                                      -->
<!-- ========================================================================= -->
<header class="pos-top-header pos-kiosk-navbar">
    <div class="d-flex align-items-center gap-3">
        <div class="d-flex align-items-center gap-2.5">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 36px; height: 36px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <i class="fas fa-cash-register" style="font-size: 0.95rem; color: #ffffff;"></i>
            </div>
            <div>
                <strong class="text-white font-monospace d-block lh-1" style="font-size: 0.90rem;">PUNTO DE VENTA</strong>
                <small class="text-white-50 font-monospace" style="font-size: 0.70rem;">{{ Auth::user()->empresaActiva()?->nombre_comercial ?? 'Sistema POS' }}</small>
            </div>
        </div>

        <div class="vr bg-white opacity-25 d-none d-md-block" style="height: 24px;"></div>

        <!-- SELECTOR DE ALMACÉN ACTIVO -->
        <div class="d-flex align-items-center gap-1.5 font-monospace text-white-50 small">
            <i class="fas fa-warehouse text-info"></i>
            <select id="posSelectAlmacen" class="form-select form-select-sm py-0.5 ps-2 pe-4 border-0 bg-dark text-white rounded-pill font-monospace" style="font-size: 0.76rem; width: auto; max-width: 170px;" onchange="cambiarAlmacenActivo()">
                <!-- Almacenes cargados dinámicamente -->
            </select>
        </div>

        <!-- TASA BCV OFICIAL -->
        <span class="badge rounded-pill kiosk-badge-tasa px-3 py-1 font-monospace fw-bold d-flex align-items-center gap-1.5" style="font-size: 0.78rem;">
            <i class="fas fa-coins text-warning"></i>
            <span>1 USD = <strong class="text-white" id="posBadgeTasaDia">1,00</strong> Bs.</span>
        </span>
    </div>

    <!-- BOTONERA SUPERIOR DE ACCIONES RÁPIDAS -->
    <div class="d-flex align-items-center gap-1.5">
        <button type="button" class="btn btn-sm rounded-pill kiosk-btn-action font-monospace" onclick="abrirModalConsultaProducto()" title="Consultar Precios & Stock (F3)">
            <i class="fas fa-search text-warning me-1"></i> <span class="d-none d-xl-inline">Precios</span> <span class="kbd-tag ms-1">F3</span>
        </button>

        <button type="button" class="btn btn-sm rounded-pill kiosk-btn-action font-monospace" onclick="abrirModalInicioCliente()" title="Identificar Cliente / Iniciar Venta (F2)">
            <i class="fas fa-user-check text-success me-1"></i> <span class="d-none d-xl-inline">Cliente</span> <span class="kbd-tag ms-1">F2</span>
        </button>

        <button type="button" class="btn btn-sm rounded-pill kiosk-btn-action font-monospace" onclick="abrirModalCuentasEspera()" title="Cuentas en Espera (F6)">
            <i class="fas fa-pause text-info me-1"></i> <span class="d-none d-xl-inline">En Espera</span> <span class="kbd-tag ms-1">F6</span>
        </button>

        <button type="button" class="btn btn-sm rounded-pill kiosk-btn-action font-monospace" onclick="abrirModalDevolucion()" title="Devolución de Factura (F7)">
            <i class="fas fa-undo text-danger me-1"></i> <span class="d-none d-xl-inline">Devolución</span> <span class="kbd-tag ms-1">F7</span>
        </button>

        <button type="button" class="btn btn-sm rounded-pill kiosk-btn-action font-monospace" onclick="abrirModalReimprimir()" title="Reimprimir Comprobante (F8)">
            <i class="fas fa-print text-primary me-1"></i> <span class="d-none d-xl-inline">Reimprimir</span> <span class="kbd-tag ms-1">F8</span>
        </button>

        <div class="vr bg-white opacity-25 mx-1" style="height: 24px;"></div>

        <!-- RELOJ Y USUARIO EN VIVO -->
        <div class="text-end font-monospace d-none d-lg-block me-1">
            <span class="text-white fw-bold d-block lh-1" id="posLiveClock" style="font-size: 0.80rem;">--:--:--</span>
            <small class="text-white-50" style="font-size: 0.68rem;"><i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}</small>
        </div>

        <button type="button" class="btn btn-sm rounded-circle kiosk-btn-action" onclick="alternarPantallaCompleta()" title="Pantalla Completa (F11)" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
            <i class="fas fa-expand"></i>
        </button>

        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 font-monospace fw-bold ms-1" style="font-size: 0.78rem;">
            <i class="fas fa-door-open me-1"></i> <span class="d-none d-sm-inline">Salir</span>
        </a>
    </div>
</header>

<!-- ========================================================================= -->
<!-- 2. ESPACIO PRINCIPAL: SPLIT VIEW 2 COLUMNAS (100% ALTURA / CERO SCROLL)   -->
<!-- ========================================================================= -->
<main class="pos-main-workspace">
    <div class="row g-2 h-100 m-0">

        <!-- ===================================================================== -->
        <!-- COLUMNA IZQUIERDA (65%): CLIENTE, SCANNER Y CARRITO DE PRODUCTOS       -->
        <!-- ===================================================================== -->
        <div class="col-12 col-lg-8 col-xl-8 d-flex flex-column h-100 p-1">
            <div class="pos-card h-100 d-flex flex-column overflow-hidden">
                
                <!-- 1. BARRA SUPERIOR: IDENTIFICACIÓN DE CLIENTE Y TARIFA (UN SOLO BLOQUE LIMPIO) -->
                <div class="p-2.5 bg-white border-bottom">
                    <div class="row g-2 align-items-center">
                        
                        <div class="col-12 col-md-8">
                            <!-- A. CHIP / TARJETA DEL CLIENTE ACTIVO (SELECCIONADO) -->
                            <div id="contenedorClienteActivo" class="pos-client-card rounded-4 p-2 px-3 bg-white border d-flex align-items-center justify-content-between gap-2 shadow-xs d-none">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="avatar-executive-sm rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); box-shadow: 0 2px 6px rgba(79, 70, 229, 0.25);">
                                        <i class="fas fa-user-check" style="font-size: 0.90rem;"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-dark font-monospace text-truncate" style="font-size: 0.88rem;" id="posClienteNombre">Consumidor Final</strong>
                                            <span id="posClienteTipoBadge" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill py-0.5 px-2 font-monospace" style="font-size: 0.68rem;">Detal</span>
                                        </div>
                                        <small class="text-secondary font-monospace text-truncate d-block" style="font-size: 0.72rem;">
                                            <span id="posClienteCedula">V-00000000</span> • <span id="posClienteTelefono">Sin teléfono</span>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-action-client-switch rounded-pill px-3 py-1 font-monospace fw-bold" onclick="abrirModalInicioCliente()" title="Cambiar Cliente (F2)">
                                        <i class="fas fa-arrows-rotate me-1 text-primary"></i> Cambiar <span class="badge-f2-subtle ms-1">F2</span>
                                    </button>
                                    <button type="button" class="btn btn-action-client-edit rounded-circle" onclick="abrirModalEditarCliente()" title="Editar Datos del Cliente">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- B. MODO CONSULTA (CUANDO NO HAY CLIENTE ASIGNADO AÚN) -->
                            <div id="contenedorModoConsulta" class="pos-client-card rounded-4 p-2 px-3 bg-white border d-flex align-items-center justify-content-between gap-2 shadow-xs">
                                <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                    <div class="avatar-executive-sm rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); box-shadow: 0 2px 6px rgba(14, 165, 233, 0.25);">
                                        <i class="fas fa-search" style="font-size: 0.90rem;"></i>
                                    </div>
                                    <div class="text-truncate">
                                        <strong class="text-dark font-monospace d-block lh-1" style="font-size: 0.86rem;">Modo Consulta de Precios & Existencias</strong>
                                        <small class="text-secondary font-monospace text-truncate d-block mt-0.5" style="font-size: 0.70rem;">Escanea productos libremente • Presiona <strong class="text-primary font-monospace">F2</strong> para identificar cliente</small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-action-start-sale rounded-pill px-3.5 py-1.5 font-monospace fw-bold flex-shrink-0" onclick="abrirModalInicioCliente()" title="Iniciar Venta (F2)">
                                    <i class="fas fa-cart-plus me-1.5"></i> Iniciar Venta <span class="badge-f2-vibrant ms-1.5">F2</span>
                                </button>
                            </div>
                        </div>

                        <!-- SWITCH DE TARIFA (DETAL / MAYORISTA) -->
                        <div class="col-12 col-md-4">
                            <div class="tarifa-pill-container shadow-xs">
                                <button type="button" class="tarifa-pill-btn active-detal" id="btnTarifaDetal" onclick="cambiarTipoVenta('detal')">
                                    <i class="fas fa-store me-1.5"></i> Detal
                                </button>
                                <button type="button" class="tarifa-pill-btn" id="btnTarifaMayor" onclick="cambiarTipoVenta('mayor')">
                                    <i class="fas fa-boxes-stacked me-1.5"></i> Mayor
                                </button>
                            </div>
                            <span id="posTarifaStatusBadge" class="d-none">Tarifa Detal</span>
                        </div>

                    </div>
                </div>

                <!-- 2. CAMPO DE BÚSQUEDA / ESCÁNER DE PRODUCTOS -->
                <div class="p-2.5 bg-white border-bottom position-relative">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-9 col-sm-8">
                            <div class="pos-scanner-box">
                                <div class="scanner-icon-wrap">
                                    <i class="fas fa-barcode"></i>
                                </div>
                                <input type="text" id="posInputBuscadorProducto" placeholder="Escanear código de barras / serial o escribir *código para elegir almacén... [F1]" autocomplete="off">
                            </div>
                            <!-- RESULTADOS FLOTANTES DE BÚSQUEDA INTERACTIVA -->
                            <div id="dropdownProductosPos" class="list-group position-absolute w-100 start-0 mt-1 shadow-lg rounded-4" style="z-index: 1070; display: none; max-height: 320px; overflow-y: auto; background: #ffffff; border: 1.5px solid #c7d2fe;"></div>
                        </div>

                        <div class="col-md-3 col-sm-4 text-end">
                            <span class="badge pos-counter-badge font-monospace px-3 py-2 fw-bold rounded-pill" id="posContadorItems">
                                <i class="fas fa-shopping-basket text-primary me-1.5"></i> 0 Ítems (0 Unid.)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. TABLA DEL CARRITO (SCROLL INTERNO SUAVE) -->
                <div class="table-responsive pos-scroll-custom flex-grow-1 bg-white" style="overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 pos-table" id="tablaPosVenta">
                        <thead class="sticky-top" style="z-index: 5;">
                            <tr>
                                <th style="width: 120px;">Código</th>
                                <th style="min-width: 230px;">Producto / Descripción</th>
                                <th class="text-center" style="width: 130px;">Cantidad</th>
                                <th class="text-end" style="width: 130px;">Precio Unit.</th>
                                <th class="text-center" style="width: 90px;">IVA</th>
                                <th class="text-end" style="width: 140px;">Subtotal</th>
                                <th class="text-center" style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="contenedorFilasPos">
                            <tr id="filaPosVacia">
                                <td colspan="7" class="text-center py-5">
                                    <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; font-size: 1.5rem;">
                                        <i class="fas fa-cash-register"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1" style="font-size: 0.98rem;">Carrito de Venta Vacío</h6>
                                    <p class="mb-0 font-monospace text-muted" style="font-size: 0.80rem;">
                                        Escanea un código de barras o presiona <strong class="text-primary">[F1]</strong> para buscar productos.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- ===================================================================== -->
        <!-- COLUMNA DERECHA (35%): TOTALES, PAGOS RÁPIDOS Y BOTÓN DE COBRO GIGANTE  -->
        <!-- ===================================================================== -->
        <div class="col-12 col-lg-4 col-xl-4 d-flex flex-column h-100 p-1">
            <div class="pos-card h-100 d-flex flex-column justify-content-between p-3 overflow-hidden">
                
                <!-- 1. DESGLOSE DE TOTALES (SUB-TOTALES & IMPUESTOS) -->
                <div>
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2.5">
                        <strong class="text-dark font-monospace" style="font-size: 0.88rem;">
                            <i class="fas fa-receipt text-primary me-1.5"></i> Resumen de Factura
                        </strong>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" style="font-size: 0.70rem;">Multimoneda</span>
                    </div>

                    <div class="row g-2 font-monospace mb-2">
                        <div class="col-6">
                            <div class="p-2 rounded-3 bg-white border shadow-xs">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Sub-Total Neto:</span>
                                <strong class="text-dark d-block" id="posTotalNetoUsd" style="font-size: 0.95rem;">$ 0.00</strong>
                                <small class="text-muted" id="posTotalNetoBs" style="font-size: 0.74rem;">Bs. 0.00</small>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="p-2 rounded-3 bg-white border shadow-xs">
                                <span class="text-muted small d-block" style="font-size: 0.72rem;">Total IVA (16%):</span>
                                <strong class="text-dark d-block" id="posTotalIvaUsd" style="font-size: 0.95rem;">$ 0.00</strong>
                                <small class="text-muted" id="posTotalIvaBs" style="font-size: 0.74rem;">Bs. 0.00</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. MEGA DISPLAY DE TOTAL A PAGAR (ALTO CONTRASTE) -->
                <div class="p-3 total-card-kiosk text-white text-center my-auto shadow-sm">
                    <span class="text-white-50 small font-monospace d-block letter-spacing-1" style="font-size: 0.78rem;">TOTAL A PAGAR</span>
                    <h1 class="fw-bold mb-1 text-warning font-monospace" id="posTotalVentaUsd" style="font-size: 2.5rem; letter-spacing: -1px;">$ 0.00</h1>
                    <div class="d-inline-block">
                        <span class="badge rounded-pill px-3 py-1 font-monospace fw-bold shadow-xs" id="posTotalVentaBs" style="background-color: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.25); font-size: 1.15rem;">
                            Bs. 0.00
                        </span>
                    </div>
                </div>

                <!-- 3. DENOMINACIONES RÁPIDAS DE EFECTIVO (QUICK CASH) -->
                <div class="mb-2">
                    <span class="text-muted font-monospace small d-block mb-1" style="font-size: 0.70rem;">Denominaciones Rápidas:</span>
                    <div class="d-flex flex-wrap gap-1 justify-content-between">
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">Exacto</button>
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">$ 5</button>
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">$ 10</button>
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">$ 20</button>
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">$ 50</button>
                        <button type="button" class="btn btn-quick-cash flex-fill" onclick="abrirModalCobro()">$ 100</button>
                    </div>
                </div>

                <!-- 4. BOTÓN GIGANTE DE COBRO & ACCIONES RÁPIDAS -->
                <div>
                    <button type="button" class="btn btn-success btn-lg rounded-pill fw-bold py-3 w-100 shadow d-flex align-items-center justify-content-center gap-2 mb-2" id="btnCobrarPos" onclick="abrirModalCobro()" style="font-size: 1.25rem;">
                        <i class="fas fa-credit-card fs-4"></i>
                        <span>COBRAR / PAGAR</span>
                        <span class="badge bg-white text-success font-monospace rounded-pill px-2 py-1 ms-1" style="font-size: 0.75rem;">F4</span>
                    </button>

                    <div class="d-flex gap-1.5">
                        <button type="button" class="btn btn-action-kiosk-danger btn-sm rounded-pill flex-fill py-1.5 font-monospace fw-bold" onclick="limpiarPantallaPos()" title="Vaciar Carrito (F10 / Supr)">
                            <i class="fas fa-trash-alt me-1"></i> Cancelar <span class="badge-f-tag badge-danger-tag">F10</span>
                        </button>

                        <button type="button" class="btn btn-action-kiosk-info btn-sm rounded-pill flex-fill py-1.5 font-monospace fw-bold" onclick="abrirModalCuentasEspera()" title="Pausar Pedido (F6)">
                            <i class="fas fa-pause me-1"></i> Pausar <span class="badge-f-tag badge-info-tag">F6</span>
                        </button>

                        <button type="button" class="btn btn-action-kiosk-primary btn-sm rounded-pill flex-fill py-1.5 font-monospace fw-bold" onclick="modificarRenglonSeleccionado()" title="Modificar Renglón Seleccionado (F9)">
                            <i class="fas fa-sliders-h me-1"></i> Editar <span class="badge-f-tag badge-primary-tag">F9</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<!-- ========================================================================= -->
<!-- 3. BARRA INFERIOR DE ATAJOS DE TECLADO (KIOSK STATUS BAR)                -->
<!-- ========================================================================= -->
<footer class="pos-bottom-statusbar">
    <div class="d-flex align-items-center gap-3 overflow-hidden text-truncate">
        <span><strong class="text-white">[F1]</strong> Buscar Producto</span>
        <span><strong class="text-white">[F2]</strong> Cliente / Venta</span>
        <span><strong class="text-white">[F3]</strong> Precios</span>
        <span><strong class="text-white">[F4]</strong> Cobrar</span>
        <span><strong class="text-white">[F6]</strong> En Espera</span>
        <span><strong class="text-white">[F7]</strong> Devolución</span>
        <span><strong class="text-white">[F8]</strong> Reimprimir</span>
        <span><strong class="text-white">[F9]</strong> Editar Renglón</span>
        <span><strong class="text-white">[F10]</strong> Cancelar</span>
        <span><strong class="text-white">[F11]</strong> Pantalla Completa</span>
    </div>
    <div class="d-none d-md-flex align-items-center gap-2 text-white-50">
        <span><strong class="text-white">[ESC]</strong> Cerrar / Consulta</span>
    </div>
</footer>

<!-- ========================================================================= -->
<!-- MODALES DEL PUNTO DE VENTA                                                -->
<!-- ========================================================================= -->

<!-- MODAL DE IDENTIFICACIÓN DE CLIENTE / INICIO DE VENTA (F2) -->
<div class="modal fade" id="modalInicioVentaCliente" tabindex="-1" aria-labelledby="modalInicioVentaClienteLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #ffffff;">
                        <i class="fas fa-user-check" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalInicioVentaClienteLabel">Identificar Cliente / Iniciar Venta</h5>
                        <small class="text-white-50">Selecciona o busca un cliente para comenzar a registrar productos</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <!-- 1. BOTÓN DE VENTA RÁPIDA (CONSUMIDOR FINAL) -->
                <div class="card card-fast-sale p-3 mb-3 shadow-xs" onclick="asignarConsumidorFinalRapido()" style="cursor: pointer;">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); box-shadow: 0 3px 8px rgba(79, 70, 229, 0.3);">
                                <i class="fas fa-bolt" style="font-size: 1.15rem; color: #ffffff;"></i>
                            </div>
                            <div>
                                <strong class="text-dark font-monospace d-block" style="font-size: 0.95rem;">Venta Rápida (Consumidor Final)</strong>
                                <small class="text-muted font-monospace">Presiona <span class="badge bg-primary text-white font-monospace px-2 py-0.5 rounded-pill">Enter</span> directamente sin escribir para continuar de inmediato</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-action-start-sale rounded-pill px-4 py-2 font-monospace fw-bold flex-shrink-0" onclick="event.stopPropagation(); asignarConsumidorFinalRapido();">
                            <i class="fas fa-arrow-right me-1.5"></i> Continuar [Enter]
                        </button>
                    </div>
                </div>

                <!-- 2. BUSCADOR CON TECLADO -->
                <div class="position-relative mb-3">
                    <label class="form-label-executive mb-1 text-dark fw-bold">
                        <i class="fas fa-search text-primary me-1.5"></i> O Buscar Cliente Registrado (Nombre, Cédula o RIF):
                    </label>
                    <div class="pos-scanner-box">
                        <div class="scanner-icon-wrap">
                            <i class="fas fa-user"></i>
                        </div>
                        <input type="text" id="posInputClienteModal" placeholder="Escribe nombre o cédula... (Navega con flechas ↑ ↓ y pulsa Enter)" autocomplete="off">
                    </div>
                    <!-- DROPDOWN DE RESULTADOS DE CLIENTES -->
                    <div id="dropdownClientesModalPos" class="list-group position-absolute w-100 start-0 mt-1 shadow-lg rounded-4" style="z-index: 1070; display: none; max-height: 260px; overflow-y: auto; background: #ffffff; border: 1.5px solid #c7d2fe;"></div>
                </div>

                <!-- 3. BANNER DE REGISTRO RÁPIDO SI NO ESTÁ REGISTRADO -->
                <div class="banner-unregistered-client d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #eef2ff;">
                            <i class="fas fa-user-plus" style="font-size: 0.85rem;"></i>
                        </div>
                        <span class="text-dark font-monospace small fw-medium">
                            ¿El cliente no está registrado aún en la base de datos?
                        </span>
                    </div>
                    <button type="button" class="btn btn-sm btn-action-client-switch rounded-pill px-3.5 py-1.5 font-monospace fw-bold" onclick="abrirModalNuevoClienteDesdeInicio()">
                        <i class="fas fa-plus me-1 text-primary"></i> Registrar Nuevo Cliente
                    </button>
                </div>
            </div>

            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar [ESC] (Modo Consulta)
                </button>
                <div class="d-flex align-items-center gap-2 text-muted font-monospace small">
                    <span class="kbd-chip">↑ ↓ Moverse</span>
                    <span>•</span>
                    <span class="kbd-chip">Enter Seleccionar</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE COBRO Y FACTURACIÓN MULTIMONEDA -->
<div class="modal fade" id="modalCobroVenta" tabindex="-1" aria-labelledby="modalCobroVentaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff;">
                        <i class="fas fa-cash-register" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalCobroVentaLabel">Procesar Pago & Facturar Venta</h5>
                        <small class="text-white-50">Ingresa los métodos de pago recibidos del cliente</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                
                <!-- RESUMEN DE TOTAL A PAGAR -->
                <div class="card border-0 p-3 mb-3 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-white-50 small font-monospace">TOTAL FACTURA A PAGAR:</span>
                            <h2 class="fw-bold mb-0 text-warning font-monospace" id="cobroModalTotalUsd">$ 0.00</h2>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs" style="background-color: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.25); font-size: 1.05rem;" id="cobroModalTotalBs">
                                Bs. 0.00
                            </span>
                            <small class="text-white-50 d-block mt-1 font-monospace" style="font-size: 0.72rem;">Tasa: <span id="cobroModalTasa">1.0000</span> Bs.</small>
                        </div>
                    </div>
                </div>

                <!-- CONDICIÓN DE VENTA (CONTADO O CRÉDITO CXC) -->
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label-executive mb-1"><i class="fas fa-file-contract text-primary me-1"></i> Condición de Pago</label>
                            <select id="cobroCondicionPago" class="form-select form-select-executive font-monospace" onchange="toggleCondicionPagoCobro()">
                                <option value="contado" selected>Contado (Pago Total / Vuelto)</option>
                                <option value="credito">Crédito (Generar Cuenta por Cobrar CXC)</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="contenedorDiasCreditoPos" style="display: none;">
                            <label class="form-label-executive mb-1"><i class="fas fa-calendar-alt text-warning me-1"></i> Días de Crédito</label>
                            <div class="input-group">
                                <input type="number" min="1" max="365" id="cobroDiasCredito" class="form-control form-control-executive font-monospace" value="15" oninput="calcularVencimientoCobro()">
                                <span class="input-group-text bg-white text-muted small font-monospace border" id="cobroFechaVenceBadge">Vence: --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORMULARIO DE INGRESO DE FORMA DE PAGO -->
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-plus-circle text-success me-1"></i> Agregar Forma de Pago</h6>
                    
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label-executive mb-1">Método de Pago</label>
                            <select id="cobroSelectMetodo" class="form-select form-select-executive font-monospace" onchange="actualizarMonedaPagoSeleccionada()">
                                <!-- Cargados dinámicamente -->
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-executive mb-1">Monto a Entregar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-primary font-monospace" id="cobroSimboloMonedaPago">$</span>
                                <input type="number" step="any" min="0.01" id="cobroInputMonto" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-executive mb-1">Referencia <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="cobroInputReferencia" class="form-control form-control-executive font-monospace" placeholder="Ej. 1234">
                        </div>

                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-primary rounded-circle shadow-xs" onclick="agregarPagoALista()" title="Añadir Pago" style="width: 40px; height: 40px; padding: 0;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABLA DE PAGOS AGREGADOS -->
                <div class="card border rounded-4 mb-3 bg-white shadow-xs overflow-hidden">
                    <div class="p-2.5 bg-white border-bottom d-flex align-items-center justify-content-between">
                        <strong class="text-dark small"><i class="fas fa-list-check text-primary me-1"></i> Pagos Registrados</strong>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill font-monospace" id="cobroContadorPagos">0 Pagos</span>
                    </div>
                    <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="tablaCobroPagos">
                            <thead class="bg-white border-bottom font-monospace small">
                                <tr>
                                    <th>Método</th>
                                    <th>Moneda</th>
                                    <th class="text-end">Monto Original</th>
                                    <th class="text-end">Equivalente USD</th>
                                    <th>Referencia</th>
                                    <th class="text-center" style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasCobroPagos">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3 small">No has ingresado ningún pago aún.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- BALANCES: TOTAL PAGADO, FALTANTE Y VUELTO -->
                <div class="row g-2 font-monospace">
                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small">Total Pagado:</span>
                            <strong class="text-dark" id="cobroBalancePagadoUsd">$ 0.00</strong>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small" id="cobroLabelFaltante">Resta por Pagar:</span>
                            <strong class="text-danger" id="cobroBalanceFaltanteUsd">$ 0.00</strong>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small">Cambio / Vuelto:</span>
                            <strong class="text-success" id="cobroBalanceVueltoUsd">$ 0.00</strong>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-5 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2" id="btnConfirmarVentaFinal" onclick="procesarVentaFinal()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                    <i class="fas fa-check-circle fs-5"></i>
                    <span>Completar Venta & Imprimir Ticket</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL DE DEVOLUCIÓN DE VENTA -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-labelledby="modalDevolucionLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff;">
                        <i class="fas fa-undo" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDevolucionLabel">Devolución de Factura & Reintegro de Inventario</h5>
                        <small class="text-white-50">Busca la factura emitida para revertir los productos al stock</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div class="row g-2 mb-3">
                    <div class="col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white"><i class="fas fa-receipt text-primary"></i></span>
                            <input type="text" id="devInputBusquedaFactura" class="form-control form-control-executive font-monospace" placeholder="Ingresa el código de comprobante (Ej: VEN-00001)...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold shadow-xs" onclick="buscarFacturaParaDevolucion()">
                            <i class="fas fa-search me-1"></i> Buscar Factura
                        </button>
                    </div>
                </div>

                <div id="contenedorDetallesFacturaDevolucion" style="display: none;">
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" id="devFacturaCodigo">VEN-00001</h6>
                                <span class="text-muted small" id="devFacturaCliente">Cliente: --</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 font-monospace fw-bold" onclick="marcarTodoDevolucion()">
                                    <i class="fas fa-check-double me-1"></i> Devolver Todo
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2.5 py-1 font-monospace" onclick="desmarcarTodoDevolucion()">
                                    <i class="fas fa-times me-1"></i> Limpiar
                                </button>
                                <div class="text-end ms-2">
                                    <span class="badge bg-success rounded-pill px-3 py-1 font-monospace fw-bold" id="devFacturaTotal">$ 0.00</span>
                                    <small class="text-muted d-block" id="devFacturaFecha">Fecha: --</small>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="bg-white border-bottom font-monospace small">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant. Facturada</th>
                                        <th class="text-end">Precio Unit.</th>
                                        <th class="text-center" style="width: 140px;">Cant. a Devolver</th>
                                        <th class="text-end" style="width: 120px;">Subtotal Dev.</th>
                                    </tr>
                                </thead>
                                <tbody id="contenedorFilasItemsDevolucion">
                                    <!-- Cargados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card border rounded-4 p-3 bg-white shadow-xs">
                        <label class="form-label-executive mb-1"><i class="fas fa-comment text-secondary me-1"></i> Motivo de la Devolución <span class="text-danger">*</span></label>
                        <input type="text" id="devInputMotivo" class="form-control form-control-executive" placeholder="Ej. Devolución de cliente en mostrador..." value="Devolución de cliente en mostrador">
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning rounded-pill px-5 py-2 fw-bold shadow-sm" id="btnConfirmarDevolucion" style="display: none;" onclick="ejecutarDevolucion()">
                    <i class="fas fa-undo me-1"></i> Procesar Devolución & Reintegrar Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CUENTAS EN ESPERA (PAUSAR / RECUPERAR) -->
<div class="modal fade" id="modalCuentasEspera" tabindex="-1" aria-labelledby="modalCuentasEsperaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: #ffffff;">
                        <i class="fas fa-pause" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalCuentasEsperaLabel">Cuentas en Espera (Pedidos Pausados)</h5>
                        <small class="text-white-50">Pausa la venta actual para atender a otro cliente o recupera un pedido guardado</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bookmark text-primary me-1"></i> Pausar Venta Actual</h6>
                    <div class="input-group">
                        <input type="text" id="inputNotaEspera" class="form-control form-control-executive font-monospace" placeholder="Nota o referencia (Ej. Cliente Camisa Azul, Mostrador #2)...">
                        <button type="button" class="btn btn-primary rounded-end-pill px-4 fw-bold" onclick="guardarCarritoEnEspera()">
                            <i class="fas fa-save me-1"></i> Poner en Espera
                        </button>
                    </div>
                </div>

                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden">
                    <div class="p-2.5 bg-white border-bottom">
                        <strong class="text-dark small"><i class="fas fa-history text-secondary me-1"></i> Ventas Pausadas Disponibles</strong>
                    </div>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-white border-bottom font-monospace small">
                                <tr>
                                    <th>Referencia / Nota</th>
                                    <th>Cliente</th>
                                    <th>Hora</th>
                                    <th class="text-end">Total USD</th>
                                    <th class="text-center" style="width: 140px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasCuentasEspera">
                                <!-- Cargados dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top py-3 px-4">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE CONSULTA DE PRODUCTO / VERIFICADOR DE PRECIOS & EXISTENCIAS -->
<div class="modal fade" id="modalConsultaProducto" tabindex="-1" aria-labelledby="modalConsultaProductoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff;">
                        <i class="fas fa-search-dollar" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalConsultaProductoLabel">Verificador de Precios & Existencias</h5>
                        <small class="text-white-50">Consulta precios detal, mayorista y stock disponible. Haz clic en cualquier producto para cargarlo a la venta.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <div class="input-group input-group-lg mb-3">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="fas fa-search text-primary"></i></span>
                    <input type="text" id="inputConsultaProdFiltro" class="form-control form-control-executive border-start-0 rounded-end-pill font-monospace" placeholder="Buscar por código de barras, SKU, nombre o categoría..." oninput="filtrarConsultaProductos()">
                </div>

                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-white border-bottom sticky-top font-monospace small" style="z-index: 3;">
                                <tr>
                                    <th style="width: 120px;">Código</th>
                                    <th>Producto / Descripción</th>
                                    <th class="text-center" style="width: 140px;">Stock Total</th>
                                    <th class="text-end" style="width: 140px;">Precio Detal</th>
                                    <th class="text-end" style="width: 140px;">Precio Mayor</th>
                                    <th class="text-center" style="width: 100px;">IVA</th>
                                    <th class="text-center" style="width: 110px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasConsultaProductos">
                                <!-- Cargados dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white border-top py-3 px-4">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE SELECCIÓN DE CANTIDAD Y ALMACÉN -->
<div class="modal fade" id="modalDetalleVentaProducto" tabindex="-1" aria-labelledby="modalDetalleVentaProductoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #ffffff;">
                        <i class="fas fa-boxes-stacked" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDetalleVentaProductoLabel">Detalle de Renglón & Despacho</h5>
                        <small class="text-white-50">Configura la cantidad exacta a vender y el almacén de salida</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formDetalleVentaProducto" onsubmit="confirmarAgregarConDetalle(event)">
                <input type="hidden" id="modalDetalleProdId">
                <input type="hidden" id="modalDetalleProdTipo">

                <div class="modal-body p-4 bg-white">
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div id="modalDetalleIcono" class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 font-monospace" id="modalDetalleProdNombre">Nombre del Producto</h6>
                                    <div class="small font-monospace text-muted mt-0.5">
                                        <span id="modalDetalleProdCodigo" class="badge bg-white text-secondary border">#0000</span>
                                        <span id="modalDetalleProdCategoria" class="ms-1">General</span>
                                        <span id="modalDetalleBadgeIva" class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace ms-1">IVA 16%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end font-monospace">
                                <span class="text-muted small d-block">Tarifa Detal / Mayor:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold" id="modalDetallePrecioDetal">$ 0.00</span>
                                    <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle rounded-pill px-2.5 py-1 fw-bold" id="modalDetallePrecioMayor">$ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <div id="modalDetalleContenedorMoto" class="p-2.5 rounded-3 bg-warning-subtle text-warning-emphasis font-monospace small mb-2 border border-warning-subtle" style="display: none;">
                            <div class="row g-1">
                                <div class="col-md-4"><strong>NIV:</strong> <span id="modalDetalleNiv">--</span></div>
                                <div class="col-md-4"><strong>Motor:</strong> <span id="modalDetalleMotor">--</span></div>
                                <div class="col-md-4"><strong>Chasis:</strong> <span id="modalDetalleChasis">--</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label-executive mb-1"><i class="fas fa-warehouse text-primary me-1"></i> Almacén de Despacho <span class="text-danger">*</span></label>
                                <select id="modalDetalleSelectAlmacen" class="form-select form-select-executive font-monospace" onchange="actualizarStockAlmacenModalDetalle()">
                                    <!-- Opciones cargadas dinámicamente -->
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label-executive mb-1"><i class="fas fa-cubes text-secondary me-1"></i> Stock en Almacén</label>
                                <div class="p-2 rounded-3 bg-white border d-flex align-items-center justify-content-between font-monospace" style="min-height: 42px;">
                                    <span class="text-muted small">Disponible:</span>
                                    <span id="modalDetalleStockBadge" class="badge bg-success rounded-pill px-3 py-1 fw-bold fs-6">0 UND</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label-executive mb-1"><i class="fas fa-calculator text-primary me-1"></i> Cantidad a Vender <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <button class="btn btn-outline-primary px-3" type="button" onclick="alterarCantidadModalDetalle(-1)">-</button>
                                    <input type="number" step="any" min="0.001" id="modalDetalleInputCantidad" class="form-control form-control-executive text-center font-monospace fw-bold fs-4" value="1" oninput="actualizarSubtotalModalDetalle()" required>
                                    <button class="btn btn-outline-primary px-3" type="button" onclick="alterarCantidadModalDetalle(1)">+</button>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-2" id="modalDetallePresets">
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(1)">+1</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(5)">+5</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(10)">+10</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(25)">+25</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(50)">+50</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill font-monospace px-2.5 py-0.5 fw-bold" onclick="sumarPresetModalDetalle(100)">+100</button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive mb-1"><i class="fas fa-percent text-warning me-1"></i> Descuento Especial (%)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white text-muted font-monospace border">%</span>
                                    <input type="number" step="any" min="0" max="100" id="modalDetalleInputDescuento" class="form-control form-control-executive font-monospace text-center fw-bold" value="0" oninput="actualizarSubtotalModalDetalle()">
                                </div>
                                <small class="text-muted font-monospace mt-1 d-block">Aplica únicamente a este renglón</small>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 rounded-4 bg-white border d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-xs">
                        <div>
                            <span class="text-muted small font-monospace d-block">Subtotal Estimado Renglón:</span>
                            <h4 class="fw-bold mb-0 text-dark font-monospace" id="modalDetalleSubtotalUsd">$ 0.00</h4>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 1rem;" id="modalDetalleSubtotalBs">
                                Bs. 0.00
                            </span>
                            <small class="text-muted font-monospace d-block mt-0.5">Tasa: <span id="modalDetalleTasa">1.0000</span> Bs.</small>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                        <i class="fas fa-cart-plus fs-5"></i>
                        <span>Agregar al Carrito [Enter]</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL DE REIMPRESIÓN DE TICKET -->
<div class="modal fade" id="modalReimprimirTicket" tabindex="-1" aria-labelledby="modalReimprimirTicketLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header modal-pos-header py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff;">
                        <i class="fas fa-print" style="font-size: 1.1rem; color: #ffffff;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalReimprimirTicketLabel">Reimprimir Comprobante / Ticket</h5>
                        <small class="text-white-50">Ingresa el código de comprobante para reimprimir</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-white">
                <label class="form-label-executive mb-1">Código de Factura (Ej: VEN-00001)</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white"><i class="fas fa-receipt text-primary"></i></span>
                    <input type="text" id="inputCodigoReimprimir" class="form-control form-control-executive font-monospace" placeholder="VEN-00001">
                </div>
            </div>

            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" onclick="ejecutarReimpresionCarta()">
                        <i class="fas fa-file-invoice me-1"></i> Factura Carta
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-3.5 py-2 fw-bold shadow-sm" onclick="ejecutarReimpresionTicket()">
                        <i class="fas fa-receipt me-1"></i> Ticket Térmico
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RÁPIDO DE CLIENTE (CREAR O EDITAR) -->
<div class="modal fade" id="modalRapidoClientePos" tabindex="-1" aria-labelledby="modalRapidoClientePosTitulo" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div id="modalRapidoClientePosHeader" class="modal-header modal-pos-header px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-header-icon-wrap" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #ffffff;">
                        <i class="fas fa-user-plus" style="font-size: 1.1rem; color: #ffffff;" id="modalRapidoClientePosIcono"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="modalRapidoClientePosTitulo">Nuevo Cliente</h5>
                        <small class="text-white-50" id="modalRapidoClientePosSubtitulo" style="font-size: 0.75rem;">Completa la información del cliente</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formRapidoClientePos" onsubmit="guardarClienteRapidoPos(event)">
                @csrf
                <input type="hidden" name="cliente_id" id="rapidoClienteId">
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        <x-input name="nombre" id="rapido_nombre" label="Nombre / Razón Comercial" icon="fas fa-user" placeholder="Ej. Juan" required maxlength="100" col="col-md-6" />

                        <x-input name="apellido" id="rapido_apellido" label="Apellido" icon="fas fa-user" placeholder="Ej. Pérez" required maxlength="100" col="col-md-6" />

                        <x-input-documento
                            selectName="rapido_tipo_cedula"
                            inputName="rapido_cedula_numero"
                            required
                            col="col-md-6"
                        />

                        <x-input-telefono
                            selectName="rapido_codigo_pais"
                            inputName="rapido_telefono_numero"
                            col="col-md-6"
                        />

                        <x-select name="tipo_cliente" id="rapido_tipo_cliente" label="Tipo de Cliente" icon="fas fa-tag" required col="col-md-6">
                            <option value="detal" selected>Detal / Particular</option>
                            <option value="mayorista">Mayorista / Empresa</option>
                        </x-select>

                        <x-input name="correo" id="rapido_correo" type="email" label="Correo Electrónico" icon="fas fa-envelope" placeholder="cliente@ejemplo.com" maxlength="150" col="col-md-6" optionalText="Opcional" />

                        <x-input name="direccion" id="rapido_direccion" label="Dirección de Habitación / Fiscal" icon="fas fa-map-marker-alt" placeholder="Calle 123, Sector, Casa/Apto 456" maxlength="255" col="col-12" optionalText="Opcional" />
                    </div>
                </div>

                <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-pos-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" id="modalRapidoClientePosBtnGuardar" class="btn btn-primary rounded-pill px-4 py-2 font-monospace fw-bold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); border: none;">
                        <i class="fas fa-save me-1"></i> <span id="modalRapidoClientePosTextoGuardar">Guardar Cliente</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const urlDashboard = "{{ route('dashboard') }}";
    const urlPosDatos = "{{ url('/pos/datos') }}";
    const urlPosBuscarClientes = "{{ url('/pos/buscar-clientes') }}";
    const urlPosGuardarCliente = "{{ url('/pos/guardar-cliente-rapido') }}";
    const urlPosGuardarVenta = "{{ url('/pos/guardar') }}";
    const urlPosEnEsperaGuardar = "{{ url('/pos/en-espera/guardar') }}";
    const urlPosEnEsperaLista = "{{ url('/pos/en-espera/lista') }}";
    const urlPosEnEsperaRecuperar = "{{ url('/pos/en-espera') }}";
    const urlPosEnEsperaEliminar = "{{ url('/pos/en-espera') }}";
    const urlPosDevolucionBuscar = "{{ url('/pos/devolucion/buscar') }}";
    const urlPosDevolucionProcesar = "{{ url('/pos/devolucion/procesar') }}";
    const urlPosImprimir = "{{ url('/pos/imprimir') }}";
    const urlPosImprimirCarta = "{{ url('/pos/imprimir-carta') }}";
    const urlPosImprimirTicket = "{{ url('/pos/imprimir-ticket') }}";
</script>
<script src="{{ asset('estilos/jsPropios/pos.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/pos.js')) ?: time() }}"></script>
@endsection
