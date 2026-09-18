<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Historial Clínico - {{ $paciente->cedula ?? $paciente->codigo_registro }}</title>
        <style>
            @page {
                margin-top: 50px;
                margin-bottom: 90px;
                margin-left: 40px;
                margin-right: 40px;
            }

            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 12px;
                color: #1e293b;
                line-height: 1.5;
                margin: 0;
                padding: 10px 20px;
            }

            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid !important;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }

            /* Marca de Agua */
            .watermark {
                position: fixed;
                top: 30%;
                left: 20%;
                width: 60%;
                opacity: 0.12;
                z-index: -100;
            }

            /* Cabecera Premium */
            .header {
                width: 100%;
                border-bottom: 3px solid #0891b2;
                padding-bottom: 15px;
                margin-bottom: 15px;
            }

            .header table {
                width: 100%;
            }

            .header td {
                vertical-align: middle;
            }

            .logo-container {
                width: 45%;
            }

            .logo {
                max-width: 220px;
                max-height: 85px;
            }

            .empresa-info {
                text-align: right;
                font-size: 10.5px;
                color: #475569;
                width: 55%;
            }

            .empresa-nombre {
                font-size: 16px;
                font-weight: bold;
                color: #0f172a;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            /* Información del Paciente */
            .paciente-info {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                margin-bottom: 10px;
                overflow: hidden;
            }

            .paciente-info th,
            .paciente-info td {
                padding: 3px 8px;
                border-bottom: 1px solid #e2e8f0;
                text-align: left;
                font-size: 10.5px;
            }

            .paciente-info th {
                background-color: #f8fafc;
                width: 15%;
                font-weight: 600;
                color: #475569;
                font-size: 10px;
                text-transform: uppercase;
                border-right: 1px solid #e2e8f0;
            }

            .paciente-info td {
                color: #0f172a;
                font-weight: 500;
            }

            /* Títulos de Exámenes */
            .titulo-general {
                text-align: center;
                font-size: 15px;
                font-weight: 800;
                color: #0891b2;
                letter-spacing: 1px;
                margin: 0px 0 10px 0;
                text-transform: uppercase;
            }

            .titulo-seccion {
                background-color: #0891b2;
                color: white;
                padding: 4px 8px;
                margin-top: 8px;
                margin-bottom: 4px;
                font-weight: bold;
                font-size: 11.5px;
                border-radius: 4px;
            }

            .subtitulo-seccion {
                margin: 6px 0 2px 0;
                color: #334155;
                font-size: 11px;
                font-weight: bold;
                border-bottom: 1.5px solid #e2e8f0;
                padding-bottom: 2px;
            }

            /* Tablas de Resultados */
            .orden-bloque, .categoria-bloque {
                page-break-inside: avoid !important;
                margin-bottom: 15px;
            }

            .resultados-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
                page-break-inside: avoid !important;
            }

            .resultados-table th {
                padding: 3px 6px;
                background-color: #f1f5f9;
                color: #475569;
                font-size: 9.5px;
                text-transform: uppercase;
                border-bottom: 1px solid #cbd5e1;
                text-align: left;
            }

            .resultados-table td {
                padding: 2px 6px;
                border-bottom: 1px solid #f8fafc;
                color: #1e293b;
                font-size: 10.5px;
            }

            .anomalo {
                color: #dc2626;
                font-weight: bold;
            }
            
            /* Separador de Orden */
            .orden-separator {
                background-color: #334155;
                color: white;
                padding: 6px 10px;
                margin-top: 25px;
                margin-bottom: 10px;
                font-size: 11px;
                font-weight: bold;
                border-radius: 4px;
                display: flex;
                justify-content: space-between;
            }
            .orden-separator span {
                display: inline-block;
                margin-right: 15px;
            }
            .resultados-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
            }

            .resultados-table th {
                padding: 3px 6px;
                background-color: #f1f5f9;
                color: #475569;
                font-size: 9.5px;
                text-transform: uppercase;
                border-bottom: 1px solid #cbd5e1;
                text-align: left;
            }

            .resultados-table td {
                padding: 2px 6px;
                border-bottom: 1px solid #f8fafc;
                color: #1e293b;
                font-size: 10.5px;
            }

            .anomalo {
                color: #dc2626;
                font-weight: bold;
            }

            /* Contenedor de Firma */
            .firma-wrapper {
                width: 100%;
                margin-top: 35px;
                page-break-inside: avoid;
            }

            .firma-container {
                width: 300px;
                margin: 0 auto;
                text-align: center;
            }

            .firma-img {
                max-width: 180px;
                max-height: 75px;
                margin-bottom: 5px;
            }

            .firma-line {
                border-top: 1px solid #475569;
                width: 100%;
                margin: 5px 0;
            }

            .firma-nombre {
                font-size: 12.5px;
                font-weight: bold;
                color: #0f172a;
                margin-top: 5px;
            }

            .firma-credencial {
                font-size: 11px;
                color: #64748b;
            }

            .firma-alerta {
                color: #dc2626;
                font-size: 11px;
                font-weight: bold;
                border: 1px dashed #dc2626;
                padding: 5px;
                border-radius: 4px;
                margin-bottom: 10px;
            }

            /* Footer */
            .footer {
                position: fixed;
                bottom: -30px;
                left: 0px;
                right: 0px;
                height: 50px;
                font-size: 10px;
                text-align: center;
                color: #94a3b8;
                border-top: 1px solid #e2e8f0;
                padding-top: 15px;
            }
        </style>
    </head>

    <body>

        @php
            $pacienteNombreCompleto = trim(($paciente->nombreUno ?? '') . ' ' . ($paciente->nombreDos ?? '') . ' ' . ($paciente->apellidoUno ?? '') . ' ' . ($paciente->apellidoDos ?? ''));
            if (empty($pacienteNombreCompleto)) {
                $pacienteNombreCompleto = trim(($paciente->nombreUno ?? '') . ' ' . ($paciente->apellidoUno ?? ''));
            }
            if (empty($pacienteNombreCompleto)) {
                $pacienteNombreCompleto = 'SIN NOMBRE';
            }

            if ($paciente->es_menor) {
                $pacienteDoc = 'MENOR' . (!empty($paciente->codigo_registro) ? ' (' . $paciente->codigo_registro . ')' : '');
            } else {
                $pacienteDoc = $paciente->cedula ?? ($paciente->codigo_registro ?? 'Sin C.I.');
            }
        @endphp

        <!-- Marca de agua de DataBioSystem -->
        @if (file_exists(public_path('estilos/imgPropio/logo.png')))
            <img src="{{ public_path('estilos/imgPropio/logo.png') }}" class="watermark" alt="Watermark">
        @endif

        <div class="footer">
            Tecnología provista por <strong>DataBioSystem</strong> - Software Integral para Laboratorios Clínicos
        </div>

        <div class="header">
            <table>
                <tr>
                    <td class="logo-container">
                        @if ($paciente->empresa->logo_path && file_exists(storage_path('app/public/' . $paciente->empresa->logo_path)))
                            <img src="{{ storage_path('app/public/' . $paciente->empresa->logo_path) }}" class="logo"
                                alt="Logo Empresa">
                        @endif
                    </td>
                    <td class="empresa-info">
                        <div class="empresa-nombre">{{ $paciente->empresa->nombre }}</div>
                        <div>RIF: {{ $paciente->empresa->rif }}</div>
                        <div>Tlf: {{ $paciente->empresa->telefonoUno }}</div>
                        <div>{{ $paciente->empresa->direccion }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="paciente-info">
            <tr>
                <th>PACIENTE:</th>
                <td style="border-right: 1px solid #e2e8f0;">{{ $pacienteNombreCompleto }}</td>
                <th>DOCUMENTO:</th>
                <td>
                    @if ($paciente->es_menor)
                        <span
                            style="background-color: #fef08a; color: #854d0e; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; border: 1px solid #fde047;">MENOR</span>
                    @else
                        {{ $paciente->cedula ?? ($paciente->codigo_registro ?? 'Sin C.I.') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>EDAD:</th>
                <td style="border-right: 1px solid #e2e8f0;">
                    {{ \Carbon\Carbon::parse($paciente->fechaNacimiento)->age }} años</td>
                <th>SEXO:</th>
                <td>{{ $paciente->sexo == 'M' ? 'Masculino' : 'Femenino' }}</td>
            </tr>
            <tr>
                <th style="border-bottom: none;">TELÉFONO:</th>
                <td style="border-bottom: none; border-right: 1px solid #e2e8f0; color: #0891b2; font-weight: bold;">
                    {{ $paciente->telefono ?? 'N/A' }}</td>
                <th style="border-bottom: none;">REGISTRO:</th>
                <td style="border-bottom: none;">{{ $paciente->created_at ? $paciente->created_at->format('d/m/Y') : 'N/A' }}</td>
            </tr>
        </table>

        <div class="titulo-general">HISTORIAL CLÍNICO</div>
        <hr style="border: none; border-bottom: 1px dashed #cbd5e1; margin-bottom: 15px;">

        @if($paciente->ordenes->isEmpty())
            <div style="text-align: center; color: #64748b; font-style: italic; margin-top: 30px;">
                No hay órdenes de servicio registradas para este paciente.
            </div>
        @else
            @foreach($paciente->ordenes as $orden)
                <div class="orden-separator" style="background-color: #334155; color: white; padding: 6px 10px; margin-top: 25px; margin-bottom: 10px; font-size: 11px; font-weight: bold; border-radius: 4px;">
                    <span style="display: inline-block; margin-right: 25px;">ORDEN: <span style="color: #38bdf8;">{{ $orden->codigo }}</span></span>
                    <span style="display: inline-block; margin-right: 25px;">FECHA: {{ $orden->created_at->format('d/m/Y h:i A') }}</span>
                    <span style="display: inline-block;">BIOANALISTA: {{ $orden->bioanalista ? $orden->bioanalista->nombre . ' ' . $orden->bioanalista->apellido : 'No asignado' }}</span>
                </div>

                @if($orden->detalles->isEmpty())
                    <div style="text-align: center; color: #94a3b8; font-size: 10px; margin-bottom: 15px;">Sin resultados registrados en esta orden.</div>
                @else
                    @foreach ($orden->detalles->groupBy(function ($d) {
                        return $d->examen_id ? 'EXÁMENES INDIVIDUALES' : 'PERFILES';
                    }) as $grupo => $detallesGrupo)
                        <div class="titulo-seccion">{{ $grupo }}</div>
                        <table class="resultados-table">
                            <thead>
                                <tr>
                                    <th width="35%">Característica</th>
                                    <th width="20%">Resultado Obtenido</th>
                                    <th width="15%">Unidad</th>
                                    <th width="30%">Valores de Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($detallesGrupo as $detalle)
                                    @php
                                        $elementos = $detalle->examen ? [$detalle->examen] : $detalle->perfil->examenes;
                                    @endphp
                                    @foreach($elementos as $examen)
                                        @foreach ($examen->parametros->groupBy('seccion') as $seccion => $parametros)
                                            @foreach ($parametros as $param)
                                                @php
                                                    $resultado = $detalle->resultados
                                                        ->where('examen_id', $examen->id)
                                                        ->where('parametro_id', $param->id)
                                                        ->first();
                                                    $valor = $resultado ? $resultado->valor : '';
                                                    $esAnomalo = $resultado && $resultado->anomalo;
                                                @endphp
                                                <tr>
                                                    <td>{{ $param->nombre }}</td>
                                                    <td class="{{ $esAnomalo ? 'anomalo' : '' }}">{{ $valor }}</td>
                                                    <td>{{ $param->unidad_medida }}</td>
                                                    <td style="color: #64748b;">{{ $param->rango_referencia }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @endif
            @endforeach
        @endif
    </body>

</html>
