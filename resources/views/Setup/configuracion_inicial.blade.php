<!DOCTYPE html>
<html dir="ltr" lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Configuración Inicial — Sistema POS Multisede</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Framework & Icons -->
    <link href="{{ asset('estilos/dist/css/style.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

    <style>
        :root {
            --pos-font: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --pos-primary: #4f46e5;
            --pos-primary-hover: #4338ca;
            --pos-primary-light: #eef2ff;
            --pos-primary-glow: rgba(99, 102, 241, 0.25);
            --pos-dark: #0f172a;
            --pos-dark-card: #1e293b;
            --pos-border: #e2e8f0;
            --pos-text-main: #0f172a;
            --pos-text-muted: #64748b;
            --pos-success: #10b981;
            --pos-radius-card: 28px;
            --pos-radius-sm: 12px;
            --pos-radius-md: 16px;
            --pos-radius-pill: 9999px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: var(--pos-font) !important;
            background-color: #0b0f19;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.18) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(59, 130, 246, 0.12) 0px, transparent 50%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 16px;
            margin: 0;
            color: var(--pos-text-main);
            -webkit-font-smoothing: antialiased;
        }

        /* CARD PRINCIPAL */
        .setup-card {
            background: #ffffff;
            border-radius: var(--pos-radius-card);
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 900px;
            position: relative;
            z-index: 10;
        }

        /* ENCABEZADO */
        .setup-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            color: #ffffff;
            padding: 34px 44px 28px 44px;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .header-brand-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.35rem;
        }

        .badge-step-pill {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            color: #e0e7ff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 16px;
            border-radius: var(--pos-radius-pill);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.03em;
        }

        /* STEPPER PROGRESS */
        .wizard-stepper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin-top: 28px;
            padding: 0 10px;
        }

        .wizard-stepper::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 45px;
            right: 45px;
            height: 3px;
            background: rgba(255, 255, 255, 0.15);
            z-index: 1;
            border-radius: 3px;
        }

        .stepper-progress-bar {
            position: absolute;
            top: 20px;
            left: 45px;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #a855f7);
            z-index: 2;
            border-radius: 3px;
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            width: 0%;
        }

        .stepper-item {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            cursor: default;
        }

        .stepper-node {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #1e293b;
            color: rgba(255, 255, 255, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.95rem;
            border: 3px solid #0f172a;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.15);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stepper-item.active .stepper-node {
            background: #6366f1;
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 0 0 3px #818cf8, 0 8px 20px rgba(99, 102, 241, 0.5);
            transform: scale(1.08);
        }

        .stepper-item.completed .stepper-node {
            background: var(--pos-success);
            color: #ffffff;
            border-color: #0f172a;
            box-shadow: 0 0 0 2px #34d399;
        }

        .stepper-title {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.82rem;
            font-weight: 600;
            transition: color 0.3s ease;
            white-space: nowrap;
        }

        .stepper-item.active .stepper-title {
            color: #ffffff;
            font-weight: 700;
        }

        .stepper-item.completed .stepper-title {
            color: #a7f3d0;
        }

        /* CONTENIDO DE PASOS */
        .step-panel {
            display: none;
            padding: 38px 44px 44px 44px;
        }

        .step-panel.active {
            display: block;
            animation: slideInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* SECCIONES Y ENCABEZADOS DE PASO */
        .step-header-box {
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid #f1f5f9;
        }

        .step-main-title {
            font-size: 1.28rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-subtitle {
            font-size: 0.88rem;
            color: var(--pos-text-muted);
            margin-bottom: 0;
            line-height: 1.45;
        }

        /* FORM CONTROLS & LABELS ESTILIZADOS */
        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label-custom i {
            color: #6366f1;
            font-size: 0.9rem;
        }

        .form-control-custom, .form-select-custom {
            font-family: var(--pos-font) !important;
            font-size: 0.9rem;
            font-weight: 500;
            color: #0f172a;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: var(--pos-radius-sm);
            padding: 11px 16px;
            transition: all 0.2s ease-in-out;
            width: 100%;
        }

        .form-control-custom:focus, .form-select-custom:focus {
            background-color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
            outline: none;
        }

        .form-control-custom::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        /* INPUT GROUPS ELEGANTES */
        .input-group-custom {
            display: flex;
            border-radius: var(--pos-radius-sm);
            box-shadow: none;
        }

        .input-group-custom .input-group-text-prefix {
            background-color: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            border-top-left-radius: var(--pos-radius-sm);
            border-bottom-left-radius: var(--pos-radius-sm);
            padding: 0 12px;
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 0.88rem;
            color: #475569;
        }

        .input-group-custom select.form-select-prefix {
            background-color: #f1f5f9;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            border-top-left-radius: var(--pos-radius-sm);
            border-bottom-left-radius: var(--pos-radius-sm);
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            font-weight: 700;
            font-size: 0.88rem;
            color: #334155;
            padding: 11px 10px;
            max-width: 95px;
            cursor: pointer;
        }

        .input-group-custom select.form-select-prefix:focus {
            border-color: #6366f1;
            outline: none;
            z-index: 3;
        }

        .input-group-custom input.form-control-input {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }

        /* LOGO UPLOAD AREA */
        .logo-upload-card {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: var(--pos-radius-md);
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.2s ease;
        }

        .logo-upload-card:hover {
            border-color: #818cf8;
            background: #faf5ff;
        }

        .logo-preview-box {
            width: 68px;
            height: 68px;
            min-width: 68px;
            border-radius: var(--pos-radius-sm);
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
        }

        .logo-preview-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* PAYMENT METHOD CARDS */
        .payment-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: var(--pos-radius-md);
            padding: 16px 18px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
            position: relative;
        }

        .payment-card:hover {
            border-color: #a5b4fc;
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .payment-card.selected {
            border-color: #6366f1;
            background: #eef2ff;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.12);
        }

        .payment-icon-squircle {
            width: 44px;
            height: 44px;
            min-width: 44px;
            border-radius: var(--pos-radius-sm);
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.06);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease;
        }

        .payment-card.selected .payment-icon-squircle {
            transform: scale(1.05);
        }

        .payment-check-indicator {
            width: 24px;
            height: 24px;
            min-width: 24px;
            border-radius: 50%;
            border: 2px solid #cbd5e1;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
            font-size: 0.72rem;
            transition: all 0.2s ease;
        }

        .payment-card.selected .payment-check-indicator {
            background: #6366f1;
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
        }

        /* BOTONES DE ACCIÓN */
        .btn-wizard-primary {
            font-family: var(--pos-font) !important;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: #ffffff !important;
            border: none;
            border-radius: var(--pos-radius-pill);
            padding: 12px 28px;
            font-weight: 700;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-wizard-primary:hover {
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
            box-shadow: 0 12px 26px rgba(79, 70, 229, 0.4);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-wizard-success {
            font-family: var(--pos-font) !important;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff !important;
            border: none;
            border-radius: var(--pos-radius-pill);
            padding: 12px 32px;
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .btn-wizard-success:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            box-shadow: 0 12px 26px rgba(16, 185, 129, 0.45);
            transform: translateY(-2px);
            color: #ffffff;
        }

        .btn-wizard-secondary {
            font-family: var(--pos-font) !important;
            background: #ffffff;
            color: #475569 !important;
            border: 1.5px solid #e2e8f0;
            border-radius: var(--pos-radius-pill);
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.92rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .btn-wizard-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #1e293b !important;
        }

        .wizard-footer-actions {
            margin-top: 36px;
            padding-top: 24px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            body {
                padding: 16px 10px;
            }
            .setup-header {
                padding: 24px 20px;
            }
            .step-panel {
                padding: 24px 20px;
            }
            .stepper-title {
                display: none;
            }
            .wizard-stepper::before, .stepper-progress-bar {
                left: 25px;
                right: 25px;
            }
        }
    </style>
</head>

<body>
    <div class="setup-card">
        <!-- ======================================================== -->
        <!-- ENCABEZADO PRINCIPAL DEL ASISTENTE                      -->
        <!-- ======================================================== -->
        <div class="setup-header">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="header-brand-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-white" style="letter-spacing: -0.02em; font-size: 1.35rem;">Instalación Inicial</h4>
                        <span class="text-white-50" style="font-size: 0.85rem;">Configuración de Empresa Matriz y SuperAdministrador</span>
                    </div>
                </div>
                <div class="badge-step-pill">
                    Paso <span id="pasoActualTexto">1</span> de 3
                </div>
            </div>

            <!-- STEPPER PROGRESS BAR -->
            <div class="wizard-stepper">
                <div class="stepper-progress-bar" id="stepperProgressBar"></div>

                <div class="stepper-item active" id="indicadorPaso1">
                    <div class="stepper-node" id="circuloPaso1">1</div>
                    <span class="stepper-title">Empresa Matriz</span>
                </div>

                <div class="stepper-item" id="indicadorPaso2">
                    <div class="stepper-node" id="circuloPaso2">2</div>
                    <span class="stepper-title">SuperAdministrador</span>
                </div>

                <div class="stepper-item" id="indicadorPaso3">
                    <div class="stepper-node" id="circuloPaso3">3</div>
                    <span class="stepper-title">Métodos de Pago</span>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- FORMULARIO MULTI-PASO                                    -->
        <!-- ======================================================== -->
        <form id="formularioSetup" enctype="multipart/form-data">
            @csrf

            <!-- ==================================================== -->
            <!-- PASO 1: DATOS DE LA EMPRESA MATRIZ                   -->
            <!-- ==================================================== -->
            <div class="step-panel active" id="contenidoPaso1">
                <div class="step-header-box">
                    <div class="step-main-title">
                        <i class="fas fa-building text-primary"></i>
                        <span>1. Información de la Empresa / Sede Matriz</span>
                    </div>
                    <p class="step-subtitle">Ingresa la información fiscal y comercial de la sede central para la emisión de facturas y control de inventarios.</p>
                </div>

                <div class="row g-3">
                    <!-- Nombre Comercial -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="empresa_nombre">
                            <i class="fas fa-store"></i> Nombre Comercial <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control-custom" id="empresa_nombre" name="empresa_nombre" placeholder="Ej. Mi Supermercado Central" maxlength="150" required autocomplete="off">
                    </div>

                    <!-- Razón Social -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="empresa_razon_social">
                            <i class="fas fa-landmark"></i> Razón Social / Fiscal <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control-custom" id="empresa_razon_social" name="empresa_razon_social" placeholder="Ej. Distribuidora Central C.A." maxlength="150" required autocomplete="off">
                    </div>

                    <!-- RIF / Documento Fiscal -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="empresa_cedula_numero">
                            <i class="fas fa-id-card"></i> RIF / Documento Fiscal <span class="text-danger">*</span>
                        </label>
                        <div class="input-group-custom">
                            <select class="form-select-prefix" id="empresa_tipo_cedula" name="empresa_tipo_cedula">
                                <option value="J-" selected>J-</option>
                                <option value="V-">V-</option>
                                <option value="G-">G-</option>
                                <option value="E-">E-</option>
                                <option value="P-">P-</option>
                            </select>
                            <input type="text" class="form-control-custom form-control-input" id="empresa_cedula_numero" name="empresa_cedula_numero" placeholder="123456789" maxlength="20" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="empresa_telefono_numero">
                            <i class="fas fa-phone-alt"></i> Teléfono de Contacto
                        </label>
                        <div class="input-group-custom">
                            <select class="form-select-prefix" id="empresa_codigo_pais" name="empresa_codigo_pais" style="max-width: 105px;">
                                <option value="+58" selected>🇻🇪 +58</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+57">🇨🇴 +57</option>
                                <option value="+56">🇨🇱 +56</option>
                                <option value="+54">🇦🇷 +54</option>
                                <option value="+34">🇪🇸 +34</option>
                            </select>
                            <input type="text" class="form-control-custom form-control-input" id="empresa_telefono_numero" name="empresa_telefono_numero" placeholder="4121234567" maxlength="20" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <!-- Correo de la Empresa -->
                    <div class="col-12">
                        <label class="form-label-custom" for="empresa_correo">
                            <i class="fas fa-envelope"></i> Correo Electrónico Empresarial <span class="text-muted fw-normal small">(Opcional)</span>
                        </label>
                        <input type="email" class="form-control-custom" id="empresa_correo" name="empresa_correo" placeholder="contacto@empresa.com" maxlength="150">
                    </div>

                    <!-- Dirección Fiscal -->
                    <div class="col-12">
                        <label class="form-label-custom" for="empresa_direccion">
                            <i class="fas fa-map-marker-alt"></i> Dirección Fiscal / Sede Central <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control-custom" id="empresa_direccion" name="empresa_direccion" placeholder="Av. Principal, Edificio Central, Local 1-A" maxlength="255" required>
                    </div>

                    <!-- Logo de la Empresa -->
                    <div class="col-12">
                        <label class="form-label-custom">
                            <i class="fas fa-image"></i> Logo de la Empresa <span class="text-muted fw-normal small">(Opcional)</span>
                        </label>
                        <div class="logo-upload-card">
                            <div class="logo-preview-box" id="previewLogoBox">
                                <i class="fas fa-building text-muted fs-3" id="placeholderLogoIcon"></i>
                                <img id="logoPreviewImg" src="" alt="Logo Preview" class="d-none">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" class="form-control form-control-custom" id="empresa_logo" name="empresa_logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                                <span class="text-muted d-block mt-1" style="font-size: 0.78rem;">Formatos recomendados: PNG, JPG o WEBP con fondo transparente (Máx. 2MB)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Giro de Negocio: Módulo de Motos y Seriales Únicos -->
                    <div class="col-12">
                        <div class="p-3 rounded-4 border bg-light d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white shadow-sm text-primary" style="width: 44px; height: 44px; font-size: 1.25rem;">
                                    <i class="fas fa-motorcycle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">¿Esta empresa comercializa Motos o Vehículos?</h6>
                                    <p class="mb-0 text-muted small">Habilita el módulo de recepción y control por seriales únicos (N.I.V., Chasis, Motor, Certificado de Origen).</p>
                                </div>
                            </div>
                            <div class="form-check form-switch fs-4 mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="empresa_maneja_motos" name="empresa_maneja_motos" value="1">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wizard-footer-actions justify-content-end">
                    <button type="button" class="btn-wizard-primary" onclick="irAlPaso(2)">
                        <span>Continuar al Paso 2</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- PASO 2: CUENTA DEL SUPERADMINISTRADOR RAÍZ          -->
            <!-- ==================================================== -->
            <div class="step-panel" id="contenidoPaso2">
                <div class="step-header-box">
                    <div class="step-main-title">
                        <i class="fas fa-user-shield text-primary"></i>
                        <span>2. Cuenta Principal del SuperAdministrador</span>
                    </div>
                    <p class="step-subtitle">Esta cuenta tendrá acceso maestro a todas las empresas, asignación de usuarios y configuraciones de la plataforma.</p>
                </div>

                <div class="row g-3">
                    <!-- Cédula de Identidad (Login Username) -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_cedula_numero">
                            <i class="fas fa-id-card"></i> Cédula de Identidad (Usuario Login) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group-custom">
                            <select class="form-select-prefix" id="admin_tipo_cedula" name="admin_tipo_cedula">
                                <option value="V-" selected>V-</option>
                                <option value="E-">E-</option>
                                <option value="J-">J-</option>
                            </select>
                            <input type="text" class="form-control-custom form-control-input" id="admin_cedula_numero" name="admin_cedula_numero" placeholder="12345678" maxlength="20" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                    </div>

                    <!-- Correo Electrónico Personal -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_email">
                            <i class="fas fa-envelope"></i> Correo Electrónico del Administrador <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control-custom" id="admin_email" name="admin_email" placeholder="admin@ejemplo.com" maxlength="150" required>
                    </div>

                    <!-- Nombre -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_nombre">
                            <i class="fas fa-user"></i> Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control-custom" id="admin_nombre" name="admin_nombre" placeholder="Ej. Carlos" maxlength="100" required>
                    </div>

                    <!-- Apellido -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_apellido">
                            <i class="fas fa-user"></i> Apellido <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control-custom" id="admin_apellido" name="admin_apellido" placeholder="Ej. Pérez" maxlength="100" required>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_password">
                            <i class="fas fa-lock"></i> Contraseña de Acceso <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control-custom" id="admin_password" name="admin_password" placeholder="Mínimo 8 caracteres" maxlength="100" required>
                    </div>

                    <!-- Confirmar Contraseña -->
                    <div class="col-md-6">
                        <label class="form-label-custom" for="admin_password_confirmation">
                            <i class="fas fa-shield-alt"></i> Confirmar Contraseña <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control-custom" id="admin_password_confirmation" name="admin_password_confirmation" placeholder="Repite la contraseña" maxlength="100" required>
                    </div>
                </div>

                <div class="wizard-footer-actions">
                    <button type="button" class="btn-wizard-secondary" onclick="irAlPaso(1)">
                        <i class="fas fa-arrow-left"></i>
                        <span>Paso Anterior</span>
                    </button>
                    <button type="button" class="btn-wizard-primary" onclick="irAlPaso(3)">
                        <span>Continuar al Paso 3</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- PASO 3: MÉTODOS DE PAGO DISPONIBLES                  -->
            <!-- ==================================================== -->
            <div class="step-panel" id="contenidoPaso3">
                <div class="step-header-box">
                    <div class="step-main-title">
                        <i class="fas fa-credit-card text-primary"></i>
                        <span>3. Métodos y Formas de Pago Iniciales</span>
                    </div>
                    <p class="step-subtitle">Selecciona los métodos que estarán habilitados para cobros en el Punto de Venta. Podrás modificarlos luego en cualquier momento.</p>
                </div>

                <div class="row g-3">
                    @php
                        $metodosConfig = [
                            ['nombre' => 'Efectivo USD', 'icono' => 'fas fa-dollar-sign', 'color' => '#10b981', 'desc' => 'Dólares en efectivo'],
                            ['nombre' => 'Efectivo Bs', 'icono' => 'fas fa-money-bill-wave', 'color' => '#06b6d4', 'desc' => 'Bolívares en efectivo'],
                            ['nombre' => 'Pago Móvil', 'icono' => 'fas fa-mobile-alt', 'color' => '#6366f1', 'desc' => 'Transferencias interbancarias'],
                            ['nombre' => 'Punto de Venta / Débito', 'icono' => 'fas fa-credit-card', 'color' => '#f59e0b', 'desc' => 'Tarjetas de débito y crédito'],
                            ['nombre' => 'Transferencia Bancaria', 'icono' => 'fas fa-university', 'color' => '#64748b', 'desc' => 'Transferencias directas'],
                            ['nombre' => 'Zelle', 'icono' => 'fas fa-exchange-alt', 'color' => '#8b5cf6', 'desc' => 'Pagos electrónicos internacionales'],
                        ];
                    @endphp

                    @foreach ($metodosConfig as $metodo)
                        <div class="col-md-6">
                            <div class="payment-card selected" onclick="toggleMetodoCard(this)">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="payment-icon-squircle" style="color: {{ $metodo['color'] }};">
                                        <i class="{{ $metodo['icono'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.92rem;">{{ $metodo['nombre'] }}</div>
                                        <div class="text-muted" style="font-size: 0.78rem;">{{ $metodo['desc'] }}</div>
                                    </div>
                                </div>
                                <div class="payment-check-indicator">
                                    <i class="fas fa-check"></i>
                                </div>
                                <input type="checkbox" name="metodos_pago[]" value="{{ $metodo['nombre'] }}" class="check-metodo d-none" checked>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="wizard-footer-actions">
                    <button type="button" class="btn-wizard-secondary" onclick="irAlPaso(2)">
                        <i class="fas fa-arrow-left"></i>
                        <span>Paso Anterior</span>
                    </button>
                    <button type="submit" id="btnFinalizarSetup" class="btn-wizard-success">
                        <i class="fas fa-rocket"></i>
                        <span>Finalizar e Iniciar Sistema</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Scripts Esenciales -->
    <script src="{{ asset('estilos/assets/libs/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
    @include('Sistema.components.alert.sweetalert')
    @include('Sistema.components.alert.toast')
    @include('Sistema.components.js-components')

    <script src="{{ asset('estilos/jsPropios/setup.js') }}?v={{ time() }}"></script>
</body>

</html>
