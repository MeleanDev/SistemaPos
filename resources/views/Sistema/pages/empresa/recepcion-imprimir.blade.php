<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Recepción {{ $recepcion->codigo }} - {{ $empresa->nombre ?? 'Sistema POS' }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #1e3a8a;
            --primary-dark: #0f172a;
            --accent-color: #2563eb;
            --border-color: #cbd5e1;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #e2e8f0;
            color: #1e293b;
            font-size: 12px;
            margin: 0;
            padding: 20px 0;
            -webkit-font-smoothing: antialiased;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .hoja-impresion {
            background: #ffffff;
            width: 216mm;
            min-height: 279mm;
            margin: 0 auto;
            padding: 20mm 18mm;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            box-sizing: border-box;
            position: relative;
        }

        .barra-acciones {
            width: 216mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-header {
            border-bottom: 2px solid var(--primary-dark);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }

        .box-info {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 14px;
            height: 100%;
        }

        .box-info-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary-color);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
        }

        .tabla-detalles {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-top: 12px;
        }

        .tabla-detalles th {
            background-color: #f1f5f9;
            color: var(--primary-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .tabla-detalles td {
            padding: 6px 6px;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .tabla-detalles tbody tr:nth-child(even) {
            background-color: #fafbfc;
        }

        .resumen-totales {
            width: 100%;
            font-size: 11px;
            border-collapse: collapse;
        }

        .resumen-totales td {
            padding: 4px 8px;
        }

        .resumen-totales .total-row {
            font-size: 13px;
            font-weight: 800;
            border-top: 2px solid var(--primary-dark);
            border-bottom: 2px solid var(--primary-dark);
            background-color: #f8fafc;
        }

        .seccion-firmas {
            margin-top: 35px;
            page-break-inside: avoid;
        }

        .linea-firma {
            border-top: 1px dashed #64748b;
            margin-top: 40px;
            padding-top: 4px;
            text-align: center;
            font-size: 10.5px;
            color: #475569;
        }

        .badge-condicion {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .badge-contado {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
        }

        .badge-credito {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
        }

        .badge-anulada {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
        }

        .badge-procesada {
            background-color: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }

        @media print {
            body {
                background: none;
                padding: 0;
                color: #000000;
            }

            .barra-acciones {
                display: none !important;
            }

            .hoja-impresion {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }

            @page {
                size: letter portrait;
                margin: 10mm 12mm;
            }

            .tabla-detalles th {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge-condicion, .box-info-title, .resumen-totales .total-row {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA FLOTANTE DE ACCIONES -->
    <div class="barra-acciones d-flex justify-content-between align-items-center">
        <div>
            <a href="javascript:window.close();" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-sm bg-white">
                <i class="fas fa-arrow-left me-1"></i> Cerrar Ventana
            </a>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print();" class="btn btn-sm btn-primary rounded-pill px-4 shadow-sm fw-bold">
                <i class="fas fa-print me-1"></i> Imprimir Comprobante
            </button>
        </div>
    </div>

    <!-- HOJA FÍSICA DE IMPRESIÓN -->
    <div class="hoja-impresion">

        <!-- HEADER DEL DOCUMENTO -->
        <div class="document-header d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-3">
                @if($empresa->logo)
                    <img src="{{ asset($empresa->logo) }}" alt="Logo" style="max-height: 55px; max-width: 120px; object-fit: contain;">
                @else
                    <div style="width: 48px; height: 48px; background: #1e3a8a; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800;">
                        {{ strtoupper(substr($empresa->nombre ?? 'P', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $empresa->nombre ?? 'EMPRESA / COMERCIO' }}</h5>
                    <div class="text-muted font-mono" style="font-size: 11px;">RIF: <strong>{{ $empresa->rif ?? 'J-00000000-0' }}</strong></div>
                    <div class="text-muted" style="font-size: 10.5px;">{{ $empresa->direccion ?? 'Dirección Fiscal Principal' }}</div>
                    <div class="text-muted" style="font-size: 10.5px;">Tel: {{ $empresa->telefono ?? 'N/A' }} | Email: {{ $empresa->correo ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="text-end" style="min-width: 220px;">
                <div class="text-uppercase fw-bold text-primary" style="font-size: 13px; letter-spacing: 0.5px;">Comprobante de Recepción</div>
                <div class="font-mono fw-bold text-dark fs-5 mt-0">{{ $recepcion->codigo }}</div>
                <div class="mt-1">
                    @if($recepcion->estado === 'anulada')
                        <span class="badge-condicion badge-anulada"><i class="fas fa-times-circle me-1"></i>ANULADA</span>
                    @else
                        <span class="badge-condicion badge-procesada"><i class="fas fa-check-circle me-1"></i>PROCESADA</span>
                    @endif
                </div>
                <div class="text-muted font-mono mt-1" style="font-size: 10px;">
                    Registro: {{ \Carbon\Carbon::parse($recepcion->created_at)->format('d/m/Y h:i A') }}
                </div>
            </div>
        </div>

        <!-- GRILLA DE INFORMACIÓN: DATOS DE FACTURA, PROVEEDOR Y LOGÍSTICA -->
        <div class="row g-2 mb-3">
            <!-- DATOS DEL PROVEEDOR -->
            <div class="col-6">
                <div class="box-info">
                    <div class="box-info-title">
                        <i class="fas fa-truck"></i> Datos del Proveedor
                    </div>
                    <table class="w-100" style="font-size: 11px; line-height: 1.4;">
                        <tr>
                            <td class="text-muted" style="width: 80px;">Razón Social:</td>
                            <td class="fw-bold text-dark">{{ $recepcion->proveedor->nombre ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">RIF / Cédula:</td>
                            <td class="font-mono fw-semibold">{{ $recepcion->proveedor->rif ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Teléfono:</td>
                            <td>{{ $recepcion->proveedor->telefono ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dirección:</td>
                            <td class="text-truncate" style="max-width: 200px;">{{ $recepcion->proveedor->direccion ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- DATOS DE LA FACTURA & CONDICIONES -->
            <div class="col-6">
                <div class="box-info">
                    <div class="box-info-title">
                        <i class="fas fa-file-invoice"></i> Documento & Condiciones
                    </div>
                    <table class="w-100" style="font-size: 11px; line-height: 1.4;">
                        <tr>
                            <td class="text-muted" style="width: 105px;">N° Documento:</td>
                            <td class="font-mono fw-bold text-dark">
                                {{ $recepcion->numero_documento }} 
                                <span class="badge bg-light text-secondary border px-1" style="font-size: 9px;">{{ strtoupper(str_replace('_', ' ', $recepcion->tipo_documento ?? 'Factura')) }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">N° de Control:</td>
                            <td class="font-mono">{{ $recepcion->numero_control ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Fecha Emisión:</td>
                            <td>{{ $recepcion->fecha_emision ? \Carbon\Carbon::parse($recepcion->fecha_emision)->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Fecha Entrada:</td>
                            <td class="fw-semibold">{{ $recepcion->fecha_recepcion ? \Carbon\Carbon::parse($recepcion->fecha_recepcion)->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Condición:</td>
                            <td>
                                @if($recepcion->condicion_pago === 'credito')
                                    <span class="badge-condicion badge-credito">CRÉDITO ({{ $recepcion->dias_credito }} días - Vence: {{ $recepcion->fecha_vencimiento ? \Carbon\Carbon::parse($recepcion->fecha_vencimiento)->format('d/m/Y') : 'N/A' }})</span>
                                @else
                                    <span class="badge-condicion badge-contado">CONTADO</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tasa de Cambio:</td>
                            <td class="font-mono text-primary fw-bold">{{ number_format($recepcion->tasa_cambio, 4, ',', '.') }} Bs/$ ({{ $recepcion->moneda_documento ?? 'USD' }})</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- ALMACÉN Y RESPONSABLE -->
        <div class="row g-2 mb-3">
            <div class="col-12">
                <div class="p-2 border rounded-3 bg-light d-flex flex-wrap justify-content-between align-items-center" style="font-size: 11px;">
                    <div>
                        <i class="fas fa-warehouse text-primary me-1"></i>
                        <strong>Almacén General:</strong> {{ $recepcion->almacen->nombre ?? 'N/A' }}
                    </div>
                    <div>
                        <i class="fas fa-user-shield text-secondary me-1"></i>
                        <strong>Operador / Usuario:</strong> {{ $recepcion->usuario->name ?? 'Usuario Sistema' }}
                    </div>
                    <div>
                        <i class="fas fa-boxes-stacked text-success me-1"></i>
                        <strong>Total Renglones:</strong> {{ count($recepcion->detalles) }} ítems ({{ number_format($recepcion->detalles->sum('cantidad'), 0, ',', '.') }} unidades)
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLA DE ARTÍCULOS DETALLADOS -->
        <table class="tabla-detalles">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 65px;">Código</th>
                    <th class="text-start">Descripción del Producto</th>
                    <th style="width: 80px;">Almacén</th>
                    <th style="width: 50px;">Bultos</th>
                    <th style="width: 60px;">Total Und.</th>
                    <th style="width: 80px;" class="text-end">Costo ($ / Bs.)</th>
                    <th style="width: 45px;">Desc%</th>
                    <th style="width: 45px;">IVA%</th>
                    <th style="width: 80px;" class="text-end">Detal ($ / Bs.)</th>
                    <th style="width: 80px;" class="text-end">Mayor ($ / Bs.)</th>
                    <th style="width: 90px;" class="text-end">Subtotal ($ / Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recepcion->detalles as $idx => $item)
                    <tr>
                        <td class="text-center font-mono">{{ $idx + 1 }}</td>
                        <td class="text-center font-mono" style="font-size: 10px;">{{ $item->producto->codigo_interno ?? '--' }}</td>
                        <td>
                            <strong class="text-dark d-block">{{ $item->producto->nombre ?? 'N/A' }}</strong>
                            <small class="text-muted" style="font-size: 9.5px;">{{ $item->producto->categoria->nombre ?? 'General' }}</small>
                        </td>
                        <td class="text-center" style="font-size: 10px;">
                            {{ $item->almacen->nombre ?? ($recepcion->almacen->nombre ?? 'N/A') }}
                        </td>
                        <td class="text-center font-mono">
                            {{ $item->cantidad_bultos > 0 ? (float)$item->cantidad_bultos : '--' }}
                        </td>
                        <td class="text-center font-mono fw-bold">
                            {{ number_format($item->cantidad, 0, ',', '.') }} <small class="text-muted">{{ $item->producto->unidad_medida ?? 'und' }}</small>
                        </td>
                        <td class="text-end font-mono">
                            <span class="d-block fw-bold text-dark">$ {{ number_format($item->costo_unitario_usd, 2, ',', '.') }}</span>
                            <small class="d-block text-primary fw-semibold" style="font-size: 9px;">Bs. {{ number_format($item->costo_unitario_bs, 2, ',', '.') }}</small>
                        </td>
                        <td class="text-center font-mono">{{ $item->descuento_porcentaje > 0 ? (float)$item->descuento_porcentaje.'%' : '--' }}</td>
                        <td class="text-center font-mono">{{ $item->aplica_iva ? (float)$item->iva_porcentaje.'%' : 'Exento' }}</td>
                        <td class="text-end font-mono">
                            <span class="d-block fw-bold text-dark">$ {{ number_format($item->precio_detal_usd, 2, ',', '.') }}</span>
                            <small class="d-block text-primary fw-semibold" style="font-size: 9px;">Bs. {{ number_format($item->precio_detal_bs, 2, ',', '.') }}</small>
                        </td>
                        <td class="text-end font-mono">
                            <span class="d-block fw-bold text-dark">$ {{ number_format($item->precio_mayorista_usd, 2, ',', '.') }}</span>
                            <small class="d-block text-primary fw-semibold" style="font-size: 9px;">Bs. {{ number_format($item->precio_mayorista_bs, 2, ',', '.') }}</small>
                        </td>
                        <td class="text-end font-mono">
                            <span class="d-block fw-bold text-dark">$ {{ number_format($item->subtotal_usd, 2, ',', '.') }}</span>
                            <small class="d-block text-primary fw-semibold" style="font-size: 9.5px;">Bs. {{ number_format($item->subtotal_bs, 2, ',', '.') }}</small>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- DESGLOSE FINANCIERO Y LIQUIDACIÓN -->
        <div class="row g-2 mt-2">
            <div class="col-7">
                <div class="box-info h-100">
                    <div class="box-info-title">
                        <i class="fas fa-comment-dots"></i> Observaciones y Notas
                    </div>
                    <p class="text-muted mb-0" style="font-size: 11px; min-height: 45px;">
                        {{ $recepcion->observaciones ?: 'Sin observaciones registradas para este documento.' }}
                    </p>
                </div>
            </div>

            <div class="col-5">
                <div class="box-info p-2">
                    <table class="resumen-totales font-mono">
                        <tr>
                            <td class="text-muted">Monto Bruto Factura:</td>
                            <td class="text-end">$ {{ number_format($recepcion->monto_bruto_usd, 2, ',', '.') }}</td>
                        </tr>
                        @if($recepcion->descuento_global_usd > 0)
                        <tr>
                            <td class="text-danger">Descuento Global ({{ (float)$recepcion->descuento_global_porcentaje }}%):</td>
                            <td class="text-end text-danger">- $ {{ number_format($recepcion->descuento_global_usd, 2, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="text-muted">Subtotal Neto:</td>
                            <td class="text-end">$ {{ number_format($recepcion->subtotal_usd, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">IVA Liquidado:</td>
                            <td class="text-end">$ {{ number_format($recepcion->iva_usd, 2, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row">
                            <td class="text-dark fw-bold">TOTAL USD:</td>
                            <td class="text-end text-success fw-bold fs-6">$ {{ number_format($recepcion->total_usd, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-primary fw-bold" style="font-size: 11.5px;">TOTAL BS.:</td>
                            <td class="text-end text-primary fw-bold" style="font-size: 11.5px;">Bs. {{ number_format($recepcion->total_bs, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE FIRMAS Y CONFORMIDAD -->
        <div class="seccion-firmas">
            <div class="row text-center">
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Entregado por</strong><br>
                        <span class="text-muted" style="font-size: 9.5px;">Transportista / Proveedor</span><br>
                        <span class="text-muted" style="font-size: 9px;">C.I.: ___________________</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Recibido Conforme</strong><br>
                        <span class="text-muted" style="font-size: 9.5px;">Jefe / Auxiliar de Almacén</span><br>
                        <span class="text-muted" style="font-size: 9px;">C.I.: ___________________</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Revisado & Aprobado</strong><br>
                        <span class="text-muted" style="font-size: 9.5px;">Administración / Compras</span><br>
                        <span class="text-muted" style="font-size: 9px;">Sello y Firma</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
