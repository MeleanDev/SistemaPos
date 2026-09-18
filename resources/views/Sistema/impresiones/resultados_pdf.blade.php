<!DOCTYPE html>
<html lang="es">

    <head>
        <meta charset="UTF-8">
        <title>Resultados - {{ $orden->codigo }}</title>
        <style>
            @page {
                margin-top: 35px;
                margin-bottom: 120px;
                margin-left: 30px;
                margin-right: 30px;
            }

            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                font-size: 11px;
                color: #1e293b;
                line-height: 1.4;
                margin: 0;
                padding: 5px 15px;
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
                opacity: 0.10;
                z-index: -100;
            }

            /* Cabecera Premium */
            .header {
                width: 100%;
                border-bottom: 2.5px solid #0891b2;
                padding-bottom: 10px;
                margin-bottom: 10px;
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
                max-width: 200px;
                max-height: 75px;
            }

            .empresa-info {
                text-align: right;
                font-size: 10px;
                color: #475569;
                width: 55%;
                line-height: 1.3;
            }

            .empresa-nombre {
                font-size: 15px;
                font-weight: bold;
                color: #0f172a;
                margin-bottom: 3px;
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
                font-size: 10px;
            }

            .paciente-info th {
                background-color: #f8fafc;
                width: 15%;
                font-weight: 600;
                color: #475569;
                font-size: 9.5px;
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
                font-size: 14px;
                font-weight: 800;
                color: #0891b2;
                letter-spacing: 1px;
                margin: 4px 0 8px 0;
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
            .categoria-bloque {
                page-break-inside: auto !important;
                margin-bottom: 12px;
            }

            .resultados-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
                page-break-inside: auto !important;
            }

            tbody.avoid-break {
                page-break-inside: avoid !important;
            }

            .resultados-table th {
                padding: 3px 6px;
                background-color: #f1f5f9;
                color: #475569;
                font-size: 9px;
                text-transform: uppercase;
                border-bottom: 1px solid #cbd5e1;
                text-align: left;
                font-weight: bold;
            }

            .resultados-table td {
                padding: 2.5px 6px;
                border-bottom: 1px solid #f1f5f9;
                color: #1e293b;
                font-size: 10px;
            }

            .resultados-table td.anomalo {
                color: #dc2626 !important;
                font-weight: bold !important;
            }

            /* Firma Original en Pie Fijo */
            .footer-fixed {
                position: fixed;
                bottom: -105px;
                left: 0px;
                right: 0px;
                height: 105px;
                text-align: center;
            }

            .firma-container {
                width: 320px;
                margin: 0 auto;
                text-align: center;
            }

            .firma-img {
                max-width: 180px;
                max-height: 52px;
                margin-bottom: 2px;
            }

            .firma-line {
                border-top: 1.5px solid #475569;
                width: 100%;
                margin: 3px 0;
            }

            .firma-nombre {
                font-size: 12px;
                font-weight: bold;
                color: #0f172a;
                margin-top: 2px;
            }

            .firma-credencial {
                font-size: 9.5px;
                color: #475569;
                line-height: 1.25;
            }

            .footer-system-text {
                font-size: 8px;
                color: #94a3b8;
                margin-top: 4px;
                border-top: 0.5px solid #e2e8f0;
                padding-top: 2px;
            }
        </style>
    </head>

    <body>

        @php
            $pacienteNombreCompleto = trim(($orden->paciente->nombreUno ?? '') . ' ' . ($orden->paciente->nombreDos ?? '') . ' ' . ($orden->paciente->apellidoUno ?? '') . ' ' . ($orden->paciente->apellidoDos ?? ''));
            if (empty($pacienteNombreCompleto)) {
                $pacienteNombreCompleto = trim(($orden->paciente->nombreUno ?? '') . ' ' . ($orden->paciente->apellidoUno ?? ''));
            }
            if (empty($pacienteNombreCompleto)) {
                $pacienteNombreCompleto = 'SIN NOMBRE';
            }

            if ($orden->paciente->es_menor) {
                $pacienteDoc = 'MENOR' . (!empty($orden->paciente->codigo_registro) ? ' (' . $orden->paciente->codigo_registro . ')' : '');
            } else {
                $pacienteDoc = $orden->paciente->cedula ?? ($orden->paciente->codigo_registro ?? 'Sin C.I.');
            }
        @endphp

        <!-- Marca de agua de DataBioSystem -->
        @if (file_exists(public_path('estilos/imgPropio/logo.png')))
            <img src="{{ public_path('estilos/imgPropio/logo.png') }}" class="watermark" alt="Watermark">
        @endif

        <!-- Pie Fijo con Firma Original del Bioanalista y Pie del Sistema en Todas las Hojas -->
        <div class="footer-fixed">
            <div class="firma-container">
                @if ($orden->bioanalista)
                    @if ($orden->bioanalista->firma_path && file_exists(storage_path('app/public/' . $orden->bioanalista->firma_path)))
                        <img src="{{ storage_path('app/public/' . $orden->bioanalista->firma_path) }}"
                            class="firma-img" alt="Firma del Bioanalista">
                    @else
                        <div style="height: 38px; line-height: 38px; color: #94a3b8; font-style: italic; font-size: 9.5px;">
                            (Firma Digital)
                        </div>
                    @endif
                    <div class="firma-line"></div>
                    <div class="firma-nombre">Lic. {{ $orden->bioanalista->nombre }} {{ $orden->bioanalista->apellido }}</div>
                    <div class="firma-credencial">Bioanalista Titular</div>
                    @if (!empty($orden->bioanalista->credenciales))
                        <div class="firma-credencial">{{ $orden->bioanalista->credenciales }}</div>
                    @endif
                @else
                    <div style="color: #dc2626; font-size: 9.5px; font-weight: bold; border: 1px dashed #dc2626; padding: 3px; border-radius: 4px; margin-bottom: 3px;">
                        DOCUMENTO NO VALIDADO
                    </div>
                    <div class="firma-line" style="border-color: #dc2626;"></div>
                    <div class="firma-credencial" style="color: #dc2626; font-size: 9.5px;">Falta firma del Bioanalista</div>
                @endif
            </div>
            <div class="footer-system-text">
                Tecnología provista por <strong>DataBioSystem</strong> - Software Integral para Laboratorios Clínicos
            </div>
        </div>

        <div class="header">
            <table>
                <tr>
                    <td class="logo-container">
                        @if ($orden->empresa->logo)
                            @if (Str::startsWith($orden->empresa->logo, 'http'))
                                <img src="{{ $orden->empresa->logo }}" class="logo" alt="Logo Empresa">
                            @elseif(file_exists(public_path('storage/' . $orden->empresa->logo)))
                                <img src="{{ public_path('storage/' . $orden->empresa->logo) }}" class="logo"
                                    alt="Logo Empresa">
                            @endif
                        @else
                            <div class="empresa-nombre" style="color: #0891b2;">{{ $orden->empresa->nombre }}</div>
                        @endif
                    </td>
                    <td class="empresa-info">
                        <div class="empresa-nombre">{{ $orden->empresa->nombre }}</div>
                        <div>RIF: {{ $orden->empresa->rif }}</div>
                        <div>Tlf: {{ $orden->empresa->telefonoUno }}</div>
                        <div>{{ $orden->empresa->direccion }}</div>
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
                    @if ($orden->paciente->es_menor)
                        <span
                            style="background-color: #fef08a; color: #854d0e; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 10px; border: 1px solid #fde047;">MENOR</span>
                    @else
                        {{ $orden->paciente->cedula ?? ($orden->paciente->codigo_registro ?? 'Sin C.I.') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th>EDAD:</th>
                <td style="border-right: 1px solid #e2e8f0;">
                    {{ \Carbon\Carbon::parse($orden->paciente->fechaNacimiento)->age }} años</td>
                <th>SEXO:</th>
                <td>{{ $orden->paciente->sexo == 'M' ? 'Masculino' : 'Femenino' }}</td>
            </tr>
            <tr>
                <th style="border-bottom: none;">ORDEN N°:</th>
                <td style="border-bottom: none; border-right: 1px solid #e2e8f0; color: #0891b2; font-weight: bold;">
                    {{ $orden->codigo }}</td>
                <th style="border-bottom: none;">FECHA:</th>
                <td style="border-bottom: none;">{{ $orden->created_at->format('d/m/Y h:i A') }}</td>
            </tr>
        </table>

        <div class="titulo-general">
            RESULTADOS DE LABORATORIO
        </div>

        @php
            $grupos = [];
            $hayOmitidos = false;
            foreach ($orden->detalles as $detalle) {
                if (!empty($soloListos)) {
                    $tieneResultados = false;
                    if ($detalle->resultados && $detalle->resultados->where('valor', '!=', '')->whereNotNull('valor')->count() > 0) {
                        $tieneResultados = true;
                    }
                    if ($detalle->examen && $detalle->examen->requiere_antibiograma && !empty($detalle->datos_bacteriologia)) {
                        $tieneResultados = true;
                    }
                    if (!$tieneResultados) {
                        $hayOmitidos = true;
                        continue;
                    }
                }

                if ($detalle->examen) {
                    $catName = $detalle->examen->categoria ? $detalle->examen->categoria->nombre : 'OTROS EXÁMENES';
                    $grupos[$catName][] = $detalle;
                } elseif ($detalle->perfil) {
                    $profName = $detalle->perfil->nombre;
                    $grupos[$profName][] = $detalle;
                }
            }
            if ($hayOmitidos) {
                $esParcial = true;
            }
        @endphp

        @foreach ($grupos as $tituloGrupo => $detallesGrupo)
            <div class="categoria-bloque">
                <table class="resultados-table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th colspan="4" style="background-color: #0891b2; color: white; padding: 4px 8px; font-weight: bold; font-size: 11.5px; text-align: left; border: none;">
                            {{ mb_strtoupper($tituloGrupo) }}
                        </th>
                    </tr>
                    <tr>
                        <th width="35%">Característica</th>
                        <th width="20%">Resultado Obtenido</th>
                        <th width="15%">Unidad</th>
                        <th width="30%">Valores de Referencia</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($detallesGrupo as $detalle)
                    @if ($detalle->examen)
                        @php
                            $mostrarSubtitulo = true;
                            if ($detalle->examen->parametros->count() === 1) {
                                $unico = $detalle->examen->parametros->first();
                                $exName = mb_strtoupper($detalle->examen->nombre);
                                $paName = mb_strtoupper($unico->nombre);
                                $caName = mb_strtoupper($tituloGrupo);

                                $exClean = preg_replace('/[^A-Z0-9]/', '', $exName);
                                $paClean = preg_replace('/[^A-Z0-9]/', '', $paName);
                                $caClean = preg_replace('/[^A-Z0-9]/', '', $caName);

                                if (
                                    $exClean === $paClean ||
                                    str_contains($exClean, $paClean) ||
                                    str_contains($paClean, $exClean)
                                ) {
                                    $mostrarSubtitulo = false;
                                }
                                if (
                                    $exClean === $caClean ||
                                    str_contains($exClean, $caClean) ||
                                    str_contains($caClean, $exClean)
                                ) {
                                    $mostrarSubtitulo = false;
                                }
                            }
                            $secciones = $detalle->examen->parametros->groupBy('seccion');
                            $esPrimerGrupo = true;
                        @endphp

                        @foreach ($secciones as $seccion => $parametros)
                            <tr style="page-break-inside: avoid !important;">
                                <td colspan="4" style="padding: 0; border: none;">
                                    <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                        <colgroup>
                                            <col style="width: 35%;">
                                            <col style="width: 20%;">
                                            <col style="width: 15%;">
                                            <col style="width: 30%;">
                                        </colgroup>
                                        <tbody>
                                            @if ($esPrimerGrupo && $mostrarSubtitulo)
                                                <tr>
                                                    <td colspan="4"
                                                        style="color: #334155; padding-top: 10px; padding-bottom: 4px; font-weight: bold; font-size: 11px; border-bottom: 1px solid #e2e8f0;">
                                                        {{ mb_strtoupper($detalle->examen->nombre) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if (!empty($seccion))
                                                <tr>
                                                    <td colspan="4"
                                                        style="background-color: #f8fafc; color: #0f172a; font-weight: 600; text-align: left; padding: 4px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                                                        <i style="color: #0891b2; margin-right: 4px;">&#9656;</i>
                                                        {{ $seccion }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @foreach ($parametros as $param)
                                                @php
                                                    $resultado = $detalle->resultados->where('parametro_id', $param->id)->first();
                                                    $valor = $resultado ? $resultado->valor : '';
                                                    $esAnomalo = $resultado && $resultado->anomalo;
                                                @endphp
                                                <tr>
                                                    <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 10px; padding-left: {{ empty($seccion) ? '8px' : '16px' }};">
                                                        {{ $param->nombre }}
                                                    </td>
                                                    <td class="{{ $esAnomalo ? 'anomalo' : '' }}" style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px;">
                                                        {{ $valor }}
                                                        {!! $esAnomalo
                                                            ? '<span style="font-size: 8.5px; background-color: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 1px 3px; border-radius: 3px; margin-left: 3px;">ALERTA</span>'
                                                            : '' !!}
                                                    </td>
                                                    <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 10px;">
                                                        {{ $param->unidad_medida }}
                                                    </td>
                                                    <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 10.5px;">
                                                        {{ $param->rango_referencia }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            @php $esPrimerGrupo = false; @endphp
                        @endforeach
                    @endif

                    @if ($detalle->perfil)
                        @foreach ($detalle->perfil->examenes as $examen)
                            @php
                                $mostrarSubtitulo = true;
                                if ($examen->parametros->count() === 1) {
                                    $unico = $examen->parametros->first();
                                    $exName = mb_strtoupper($examen->nombre);
                                    $paName = mb_strtoupper($unico->nombre);
                                    $profName = mb_strtoupper($tituloGrupo);

                                    $exClean = preg_replace('/[^A-Z0-9]/', '', $exName);
                                    $paClean = preg_replace('/[^A-Z0-9]/', '', $paName);
                                    $profClean = preg_replace('/[^A-Z0-9]/', '', $profName);

                                    if (
                                        $exClean === $paClean ||
                                        str_contains($exClean, $paClean) ||
                                        str_contains($paClean, $exClean)
                                    ) {
                                        $mostrarSubtitulo = false;
                                    }
                                    if (
                                        $exClean === $profClean ||
                                        str_contains($exClean, $profClean) ||
                                        str_contains($profClean, $exClean)
                                    ) {
                                        $mostrarSubtitulo = false;
                                    }
                                }
                                $secciones = $examen->parametros->groupBy('seccion');
                                $esPrimerGrupo = true;
                            @endphp

                            @foreach ($secciones as $seccion => $parametros)
                                <tr style="page-break-inside: avoid !important;">
                                    <td colspan="4" style="padding: 0; border: none;">
                                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                            <colgroup>
                                                <col style="width: 35%;">
                                                <col style="width: 20%;">
                                                <col style="width: 15%;">
                                                <col style="width: 30%;">
                                            </colgroup>
                                            <tbody>
                                                @if ($esPrimerGrupo && $mostrarSubtitulo)
                                                    <tr>
                                                        <td colspan="4"
                                                            style="color: #334155; padding-top: 10px; padding-bottom: 4px; font-weight: bold; font-size: 11px; border-bottom: 1px solid #e2e8f0;">
                                                            {{ mb_strtoupper($examen->nombre) }}
                                                        </td>
                                                    </tr>
                                                @endif
                                                @if (!empty($seccion))
                                                    <tr>
                                                        <td colspan="4"
                                                            style="background-color: #f8fafc; color: #0f172a; font-weight: 600; text-align: left; padding: 4px 8px; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                                                            <i style="color: #0891b2; margin-right: 4px;">&#9656;</i>
                                                            {{ $seccion }}
                                                        </td>
                                                    </tr>
                                                @endif
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
                                                        <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 10px; padding-left: {{ empty($seccion) ? '8px' : '16px' }};">
                                                            {{ $param->nombre }}
                                                        </td>
                                                        <td class="{{ $esAnomalo ? 'anomalo' : '' }}" style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px;">
                                                            {{ $valor }}
                                                            {!! $esAnomalo
                                                                ? '<span style="font-size: 8.5px; background-color: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; padding: 1px 3px; border-radius: 3px; margin-left: 3px;">ALERTA</span>'
                                                                : '' !!}
                                                        </td>
                                                        <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-size: 10px;">
                                                            {{ $param->unidad_medida }}
                                                        </td>
                                                        <td style="padding: 2.5px 6px; border-bottom: 1px solid #f1f5f9; color: #64748b; font-size: 10.5px;">
                                                            {{ $param->rango_referencia }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                                @php $esPrimerGrupo = false; @endphp
                            @endforeach
                        @endforeach
                    @endif
                    
                    @php
                        $bact = $detalle->datos_bacteriologia ?? [];
                        $tipoCultivo = $bact['tipo_cultivo'] ?? 'positivo';
                        $aislamientosBrutos = ($tipoCultivo === 'positivo') ? ($bact['aislamientos'] ?? []) : [];
                        $aislamientos = [];
                        foreach ($aislamientosBrutos as $index => $aisl) {
                            if (is_array($aisl)) {
                                if (count(array_filter($aisl)) > 0) {
                                    $aislamientos[$index] = $aisl;
                                }
                            } else if (is_string($aisl) && trim($aisl) !== '') {
                                $aislamientos[$index] = ['genero' => $aisl];
                            }
                        }
                        $antibiograma = ($tipoCultivo === 'positivo') ? ($bact['antibiograma'] ?? []) : [];
                    @endphp
                    
                    @if ($tipoCultivo === 'positivo' && (count($aislamientos) > 0 || count($antibiograma) > 0))
                        <tr style="page-break-inside: avoid !important;">
                            <td colspan="4" style="padding: 10px 0; border: none;">
                                <div style="border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; margin-top: 5px;">
                                    <div style="font-weight: bold; font-size: 11px; margin-bottom: 8px; color: #0f172a; text-transform: uppercase;">
                                        Estudio Bacteriológico (Antibiograma)
                                    </div>
                                    
                                    @if (count($aislamientos) > 0)
                                        <div style="margin-bottom: 12px;">
                                            <div style="background-color: #f1f5f9; text-align: center; font-weight: bold; font-size: 10px; padding: 3px; border: 1px solid #cbd5e1; letter-spacing: 2px; margin-bottom: 2px;">
                                                A I S L A M I E N T O S
                                            </div>
                                            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                                                <thead>
                                                    <tr>
                                                        <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">#</th>
                                                        <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">Género y Especie</th>
                                                        <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">Crecimiento</th>
                                                        <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">Hemólisis</th>
                                                        <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">Grupo S.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($aislamientos as $index => $aisl)
                                                        <tr>
                                                            <td style="padding: 2px; border-bottom: 1px solid #f1f5f9;">{{ $index + 1 }}</td>
                                                            <td style="padding: 2px; border-bottom: 1px solid #f1f5f9; font-style: italic; font-weight: bold;">
                                                                {{ is_array($aisl) ? (($aisl['genero'] ?? '') . ' ' . ($aisl['especie'] ?? '')) : $aisl }}
                                                            </td>
                                                            <td style="padding: 2px; border-bottom: 1px solid #f1f5f9;">{{ is_array($aisl) ? ($aisl['crecimiento'] ?? '-') : '-' }}</td>
                                                            <td style="padding: 2px; border-bottom: 1px solid #f1f5f9;">{{ is_array($aisl) ? ($aisl['hemolisis'] ?? '-') : '-' }}</td>
                                                            <td style="padding: 2px; border-bottom: 1px solid #f1f5f9;">{{ is_array($aisl) ? ($aisl['grupo_s'] ?? '-') : '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    @if (count($antibiograma) > 0)
                                        @php
                                            $numCols = 2; 
                                            $filasValidas = [];
                                            foreach($antibiograma as $fila) {
                                                if (is_array($fila) && isset($fila['antibiotico'])) {
                                                    $filasValidas[] = $fila;
                                                }
                                            }
                                            if(count($filasValidas) > 0) {
                                                $chunkSize = ceil(count($filasValidas) / $numCols);
                                                $chunks = array_chunk($filasValidas, $chunkSize);
                                            } else {
                                                $chunks = [];
                                            }
                                        @endphp
                                        @if(count($chunks) > 0)
                                            <div style="background-color: #f1f5f9; text-align: center; font-weight: bold; font-size: 10px; padding: 3px; border: 1px solid #cbd5e1; letter-spacing: 2px; margin-bottom: 2px;">
                                                A N T I B I O G R A M A
                                            </div>
                                            <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
                                                <tbody>
                                                    <tr>
                                                        @foreach($chunks as $chunk)
                                                            <td style="vertical-align: top; width: 50%; padding: 0 4px;">
                                                                <table style="width: 100%; border-collapse: collapse;">
                                                                    <thead>
                                                                        <tr>
                                                                            <th style="text-align: left; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">Antibiótico</th>
                                                                            @foreach (array_keys($aislamientos) as $index)
                                                                                <th style="text-align: center; border-bottom: 1px solid #cbd5e1; padding: 2px; color: #475569;">{{ $index + 1 }}</th>
                                                                            @endforeach
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($chunk as $fila)
                                                                            <tr>
                                                                                <td style="padding: 2px; border-bottom: 1px solid #f1f5f9;">{{ $fila['antibiotico'] }}</td>
                                                                                @foreach (array_keys($aislamientos) as $i)
                                                                                    @php
                                                                                        $valorRes = $fila['resultados'][$i] ?? '-';
                                                                                        if (empty($valorRes)) $valorRes = '-';
                                                                                        $color = '#1e293b';
                                                                                        if ($valorRes === 'S') $color = '#16a34a';
                                                                                        if ($valorRes === 'I') $color = '#ca8a04';
                                                                                        if ($valorRes === 'R') $color = '#dc2626';
                                                                                    @endphp
                                                                                    <td style="text-align: center; padding: 2px; border-bottom: 1px solid #f1f5f9; font-weight: bold; color: {{ $color }}; border-left: 1px solid #f1f5f9;">
                                                                                        {{ $valorRes !== '-' ? $valorRes : '' }}
                                                                                    </td>
                                                                                @endforeach
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                </tbody>
                                            </table>
                                        @endif
                                        <div style="font-size: 8.5px; color: #64748b; margin-top: 5px;">
                                            <strong>Leyenda:</strong> S = Sensible | I = Intermedio | R = Resistente
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

        @if (!empty($orden->observacion))
            <div style="margin-top: 25px; page-break-inside: avoid;">
                <div style="border-bottom: 2px solid #334155; padding-bottom: 3px; margin-bottom: 8px;">
                    <strong style="font-size: 11px; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                        Observación
                    </strong>
                </div>
                <p style="margin: 0; font-size: 11px; color: #1e293b; text-align: justify; line-height: 1.5;">
                    {!! nl2br(e($orden->observacion)) !!}
                </p>
            </div>
        @endif
    </body>

</html>
