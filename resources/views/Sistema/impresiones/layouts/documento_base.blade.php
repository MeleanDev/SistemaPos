<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('titulo', 'Documento Fiscal')</title>
        <style>
            @page {
                size: @yield('tamano_papel_css', 'letter portrait')

                ;

                margin: @yield('margen_pagina_css', '12mm 12mm 14mm 12mm')

                ;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background-color: #f8fafc;
                color: #0f172a;
                font-size: 10px;
                line-height: 1.35;
                position: relative;
            }

            /* 🔒 MARCA DE AGUA INMUTABLE DEL SISTEMA */
            .system-watermark-container {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 380px;
                max-width: 75%;
                opacity: 0.042;
                z-index: -999;
                pointer-events: none;
                text-align: center;
            }

            .system-watermark-container img {
                width: 100%;
                height: auto;
                filter: grayscale(100%);
                display: block;
                margin: 0 auto;
            }

            /* BARRA SUPERIOR DE ACCIONES (NO IMPRIMIBLE) */
            .no-print {
                max-width: 200mm;
                margin: 15px auto 10px auto;
                display: flex;
                gap: 10px;
                justify-content: space-between;
                align-items: center;
                padding: 0 10px;
            }

            .btn-action {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 12.5px;
                font-weight: 600;
                cursor: pointer;
                border: none;
                text-decoration: none;
                transition: all 0.2s;
            }

            .btn-print {
                background-color: #1e40af;
                color: #ffffff;
            }

            .btn-print:hover {
                background-color: #1d4ed8;
            }

            .btn-pdf {
                background-color: #b91c1c;
                color: #ffffff;
            }

            .btn-pdf:hover {
                background-color: #991b1b;
            }

            .btn-ticket {
                background-color: #059669;
                color: #ffffff;
            }

            .btn-ticket:hover {
                background-color: #047857;
            }

            .btn-garantia {
                background-color: #d97706;
                color: #ffffff;
            }

            .btn-garantia:hover {
                background-color: #b45309;
            }

            .btn-close {
                background-color: #64748b;
                color: #ffffff;
            }

            .btn-close:hover {
                background-color: #475569;
            }

            /* CONTENEDOR DE PÁGINA IMPRESA */
            .page-container {
                max-width: 195mm;
                margin: 10px auto 30px auto;
                background: #ffffff;
                padding: 14px 18px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
                position: relative;
            }

            .text-center {
                text-align: center;
            }

            .text-end {
                text-align: right;
            }

            .text-start {
                text-align: left;
            }

            .fw-bold {
                font-weight: bold;
            }

            .fw-semibold {
                font-weight: 600;
            }

            .text-uppercase {
                text-transform: uppercase;
            }

            .font-mono {
                font-family: 'Courier New', Courier, monospace;
            }

            .text-muted {
                color: #64748b;
            }

            @media print {
                body {
                    background: #ffffff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .no-print {
                    display: none !important;
                }

                .page-container {
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    border: none !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                }

                .system-watermark-container {
                    display: block !important;
                    opacity: 0.048 !important;
                }
            }

            @if (!empty($esPdf))
                body {
                    background-color: #ffffff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                .page-container {
                    max-width: 100% !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    border: none !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                }

                .system-watermark-container {
                    opacity: 0.04 !important;
                }
            @endif

            @yield('estilos_adicionales')
        </style>
    </head>

    <body>

        @php
            $watermarkPath = public_path('estilos/imgPropio/logo.png');
            $watermarkBase64 = file_exists($watermarkPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($watermarkPath))
                : asset('estilos/imgPropio/logo.png');
        @endphp

        <!-- 🔒 MARCA DE AGUA PERMANENTE E INMUTABLE DEL SISTEMA -->
        <div class="system-watermark-container">
            <img src="{{ $watermarkBase64 }}" alt="Sistema POS Watermark">
        </div>

        @if (empty($esPdf))
            <!-- BARRA DE HERRAMIENTAS EN PANTALLA -->
            <div class="no-print">
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button onclick="window.print()" class="btn-action btn-print">
                        🖨️ Imprimir
                    </button>
                    @yield('botones_barra')
                </div>
                <div>
                    <button onclick="window.close()" class="btn-action btn-close">
                        ✕ Cerrar
                    </button>
                </div>
            </div>
        @endif

        <!-- CONTENEDOR DEL DOCUMENTO -->
        <div class="page-container">
            @yield('contenido_documento')
        </div>

        @yield('scripts_impresion')
    </body>

</html>
