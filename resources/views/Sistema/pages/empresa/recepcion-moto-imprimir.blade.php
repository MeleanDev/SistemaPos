<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Recepción de Motos {{ $recepcion->codigo }} - {{ $empresa->nombre ?? 'Sistema POS' }}</title>
    
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
            font-size: 11.5px;
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
            padding: 16mm 16mm;
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
            margin-bottom: 14px;
        }

        .box-info {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 9px 12px;
            height: 100%;
        }

        .box-info-title {
            font-size: 10.5px;
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
            font-size: 10.5px;
            margin-top: 10px;
        }

        .tabla-detalles th {
            background-color: #f1f5f9;
            color: var(--primary-dark);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            padding: 6px 5px;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .tabla-detalles td {
            padding: 5px 5px;
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
            font-size: 12.5px;
            font-weight: 800;
            border-top: 2px solid var(--primary-dark);
            border-bottom: 2px solid var(--primary-dark);
            background-color: #f8fafc;
        }

        .seccion-firmas {
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .linea-firma {
            border-top: 1px dashed #64748b;
            margin-top: 35px;
            padding-top: 4px;
            text-align: center;
            font-size: 10px;
            color: #475569;
        }

        .badge-condicion {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 9.5px;
            font-weight: 700;
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
                margin: 8mm 10mm;
            }

            .tabla-detalles th {
                background-color: #f1f5f9 !important;
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
                    <img src="{{ asset('storage/' . $empresa->logo) }}" alt="Logo" style="max-height: 55px; max-width: 120px; object-fit: contain;">
                @else
                    <div style="width: 48px; height: 48px; background: #1e3a8a; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800;">
                        <i class="fas fa-motorcycle"></i>
                    </div>
                @endif
                <div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $empresa->nombre ?? 'EMPRESA / CONCESIONARIO' }}</h5>
                    <div class="text-muted font-mono" style="font-size: 10.5px;">RIF: <strong>{{ $empresa->rif ?? 'J-00000000-0' }}</strong></div>
                    <div class="text-muted" style="font-size: 10px;">{{ $empresa->direccion ?? 'Dirección Fiscal Principal' }}</div>
                    <div class="text-muted" style="font-size: 10px;">Tel: {{ $empresa->telefono ?? 'N/A' }} | Email: {{ $empresa->correo ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="text-end" style="min-width: 230px;">
                <div class="text-uppercase fw-bold text-primary" style="font-size: 12.5px; letter-spacing: 0.5px;">Recepción de Motos & Vehículos</div>
                <div class="font-mono fw-bold text-dark fs-5 mt-0">{{ $recepcion->codigo }}</div>
                <div class="mt-1">
                    @if($recepcion->estado === 'anulada')
                        <span class="badge-condicion badge-anulada"><i class="fas fa-times-circle me-1"></i>ANULADA</span>
                    @else
                        <span class="badge-condicion badge-procesada"><i class="fas fa-check-circle me-1"></i>PROCESADA</span>
                    @endif
                </div>
                <div class="text-muted font-mono mt-1" style="font-size: 9.5px;">
                    Registro: {{ \Carbon\Carbon::parse($recepcion->created_at)->format('d/m/Y h:i A') }}
                </div>
            </div>
        </div>

        <!-- GRILLA DE INFORMACIÓN -->
        <div class="row g-2 mb-2">
            <div class="col-6">
                <div class="box-info">
                    <div class="box-info-title">
                        <i class="fas fa-truck"></i> Proveedor / Ensambladora
                    </div>
                    <table class="w-100" style="font-size: 10.5px; line-height: 1.35;">
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
                    </table>
                </div>
            </div>

            <div class="col-6">
                <div class="box-info">
                    <div class="box-info-title">
                        <i class="fas fa-file-invoice"></i> Documento & Condiciones
                    </div>
                    <table class="w-100" style="font-size: 10.5px; line-height: 1.35;">
                        <tr>
                            <td class="text-muted" style="width: 100px;">N° Factura:</td>
                            <td class="font-mono fw-bold text-dark">{{ $recepcion->numero_documento }} ({{ strtoupper($recepcion->tipo_documento) }})</td>
                        </tr>
                        <tr>
                            <td class="text-muted">N° Control:</td>
                            <td class="font-mono">{{ $recepcion->numero_control ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Fecha Emisión:</td>
                            <td>{{ $recepcion->fecha_emision ? \Carbon\Carbon::parse($recepcion->fecha_emision)->format('d/m/Y') : 'N/A' }} | Entrada: {{ $recepcion->fecha_recepcion ? \Carbon\Carbon::parse($recepcion->fecha_recepcion)->format('d/m/Y') : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Condición:</td>
                            <td>
                                @if($recepcion->condicion_pago === 'credito')
                                    <span class="badge-condicion badge-credito">CRÉDITO ({{ $recepcion->dias_credito }}d - Vence: {{ $recepcion->fecha_vencimiento ? \Carbon\Carbon::parse($recepcion->fecha_vencimiento)->format('d/m/Y') : 'N/A' }})</span>
                                @else
                                    <span class="badge-condicion badge-contado">CONTADO</span>
                                @endif
                                <span class="font-mono text-primary ms-1">Tasa: {{ number_format($recepcion->tasa_cambio, 4, ',', '.') }} Bs/$</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- BARRA RESUMEN DE ENTRADA -->
        <div class="p-2 border rounded-3 bg-light d-flex flex-wrap justify-content-between align-items-center mb-2" style="font-size: 10.5px;">
            <div><i class="fas fa-warehouse text-primary me-1"></i><strong>Almacén General:</strong> {{ $recepcion->almacen->nombre ?? 'N/A' }}</div>
            <div><i class="fas fa-user text-secondary me-1"></i><strong>Responsable:</strong> {{ $recepcion->usuario->name ?? 'Usuario Sistema' }}</div>
            <div><i class="fas fa-motorcycle text-success me-1"></i><strong>Total Motos Recibidas:</strong> {{ $recepcion->total_unidades }} unidades</div>
        </div>

        <!-- TABLA DE SERIALES ÚNICOS DE MOTOS INGRESADAS -->
        <div class="fw-bold text-dark mb-1" style="font-size: 11px;">
            <i class="fas fa-fingerprint text-success me-1"></i> Control de Seriales Únicos por Unidad Física
        </div>
        <table class="tabla-detalles">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th style="width: 110px;">Marca / Modelo</th>
                    <th style="width: 75px;">Año / Color</th>
                    <th style="width: 140px;">N.I.V. (VIN)</th>
                    <th style="width: 120px;">N° Chasis</th>
                    <th style="width: 110px;">N° Motor</th>
                    <th style="width: 110px;">Cert. Origen</th>
                    <th style="width: 60px;">Placa</th>
                    <th style="width: 80px;">Almacén</th>
                    <th style="width: 75px;" class="text-end">Costo ($)</th>
                </tr>
            </thead>
            <tbody>
                @php $contadorMoto = 1; @endphp
                @foreach($recepcion->detalles as $detalle)
                    @foreach($detalle->motos as $moto)
                        <tr>
                            <td class="text-center font-mono fw-bold">{{ $contadorMoto++ }}</td>
                            <td>
                                <strong class="text-dark d-block">{{ $moto->marca }} {{ $moto->modelo }}</strong>
                                <small class="text-muted font-mono" style="font-size: 9px;">Ref: {{ $moto->referencia }}</small>
                            </td>
                            <td class="text-center">
                                <span class="d-block">{{ $moto->anio }}</span>
                                <small class="text-muted">{{ $moto->color }}</small>
                            </td>
                            <td class="font-mono fw-bold text-dark text-center" style="font-size: 9.5px;">{{ $moto->numero_niv }}</td>
                            <td class="font-mono text-center" style="font-size: 9.5px;">{{ $moto->numero_chasis }}</td>
                            <td class="font-mono text-center" style="font-size: 9.5px;">{{ $moto->numero_motor }}</td>
                            <td class="font-mono text-center" style="font-size: 9.5px;">{{ $moto->certificado_origen }}</td>
                            <td class="font-mono text-center">{{ $moto->placa ?: '--' }}</td>
                            <td class="text-center" style="font-size: 9.5px;">{{ $moto->almacen->nombre ?? 'N/A' }}</td>
                            <td class="text-end font-mono fw-bold text-dark">$ {{ number_format($moto->precio_costo_usd, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <!-- DESGLOSE FINANCIERO -->
        <div class="row g-2 mt-2">
            <div class="col-7">
                <div class="box-info h-100">
                    <div class="box-info-title">
                        <i class="fas fa-comment-dots"></i> Observaciones de la Recepción
                    </div>
                    <p class="text-muted mb-0" style="font-size: 10.5px; min-height: 40px;">
                        {{ $recepcion->observaciones ?: 'Sin observaciones adicionales registradas.' }}
                    </p>
                </div>
            </div>

            <div class="col-5">
                <div class="box-info p-2">
                    <table class="resumen-totales font-mono">
                        <tr>
                            <td class="text-muted">Subtotal Neto:</td>
                            <td class="text-end">$ {{ number_format($recepcion->subtotal_usd, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">IVA Liquidado:</td>
                            <td class="text-end">$ {{ number_format($recepcion->iva_usd, 2, ',', '.') }}</td>
                        </tr>
                        @if($recepcion->descuento_global_usd > 0)
                        <tr>
                            <td class="text-danger">Descuento Global:</td>
                            <td class="text-end text-danger">- $ {{ number_format($recepcion->descuento_global_usd, 2, ',', '.') }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td class="text-dark fw-bold">TOTAL USD:</td>
                            <td class="text-end text-success fw-bold fs-6">$ {{ number_format($recepcion->total_usd, 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td class="text-primary fw-bold">TOTAL BS.:</td>
                            <td class="text-end text-primary fw-bold">Bs. {{ number_format($recepcion->total_bs, 2, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE FIRMAS -->
        <div class="seccion-firmas">
            <div class="row text-center">
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Entregado por</strong><br>
                        <span class="text-muted" style="font-size: 9px;">Transporte / Ensambladora</span><br>
                        <span class="text-muted" style="font-size: 8.5px;">C.I.: ___________________</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Recibido Conforme</strong><br>
                        <span class="text-muted" style="font-size: 9px;">Control de Calidad / Almacén</span><br>
                        <span class="text-muted" style="font-size: 8.5px;">C.I.: ___________________</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="linea-firma">
                        <strong>Aprobado</strong><br>
                        <span class="text-muted" style="font-size: 9px;">Gerencia de Operaciones</span><br>
                        <span class="text-muted" style="font-size: 8.5px;">Sello y Firma</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
