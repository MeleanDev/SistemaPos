@extends('Sistema.layouts.app')

@section('subtitulo', 'Ingreso de Resultados')

@section('contenido')
    <style>
        .lab-card {
            border-radius: 1.25rem;
            border: none;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
            background-color: #ffffff;
            transition: all 0.2s ease-in-out;
        }

        .patient-hero-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 1.25rem;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .stat-pill {
            background: #ffffff;
            border-radius: 0.9rem;
            border: 1px solid #e2e8f0;
            padding: 0.65rem 0.85rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            transition: all 0.2s ease;
        }

        .stat-pill:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .order-code-badge {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            border-radius: 1.25rem;
            padding: 1rem 1.25rem;
        }

        .profile-container-card {
            border-radius: 1.25rem;
            border: 1px solid #e0f2fe;
            background: #f8fafc;
            overflow: hidden;
        }

        .profile-header-bar {
            background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
            border-bottom: 1px solid #bae6fd;
        }

        .sticky-actions-bar {
            border-radius: 1.25rem;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }

        .table thead th {
            font-size: 0.76rem;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 700;
        }

        @media (max-width: 767.98px) {
            .mobile-btn-full {
                width: 100% !important;
                display: block;
            }
            .mobile-flex-fill {
                flex: 1 1 auto;
            }
        }

        .select2-container--bootstrap-5 .select2-selection,
        .select2-container .select2-selection--single {
            border-radius: 50rem !important;
            height: 33px !important;
            padding: 0.2rem 0.85rem !important;
            border: 1px solid #dee2e6 !important;
            display: flex !important;
            align-items: center !important;
            background-color: #ffffff !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
        .select2-container .select2-selection--single .select2-selection__rendered {
            padding-left: 0.15rem !important;
            color: #212529 !important;
            font-size: 0.82rem !important;
            font-weight: 500 !important;
        }
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow,
        .select2-container .select2-selection--single .select2-selection__arrow {
            right: 12px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
    </style>

    <!-- Encabezado de la página -->
    <div class="row align-items-center mb-3 mb-md-4">
        <div class="col-12 col-md-auto mb-2 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-2.5 rounded-circle shadow-sm d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                    <i class="fas fa-flask fs-5"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h4 h3-md">Ingreso de Resultados</h2>
                    <p class="text-muted mb-0 small">Procesa y registra los valores de los exámenes</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto ms-auto text-end">
            <a href="{{ route('ordenes.index') }}" class="btn btn-white bg-white border rounded-pill px-3 px-md-4 shadow-sm fw-medium text-secondary btn-sm btn-md-normal">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Encabezado de Información del Paciente y la Orden -->
        <div class="col-md-12 mb-3 mb-md-4">
            <div class="card patient-hero-card shadow-sm border-0">
                <div class="card-body p-3 p-md-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8 border-lg-end pe-lg-4">

                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm flex-shrink-0"
                                    style="width: 52px; height: 52px; font-size: 1.3rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ms-3 min-w-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <h4 class="mb-0 fw-bold text-dark text-truncate h5 h4-md">
                                            {{ $orden->paciente->nombreUno }} {{ $orden->paciente->nombreDos }}
                                            {{ $orden->paciente->apellidoUno }} {{ $orden->paciente->apellidoDos }}
                                        </h4>
                                        <span
                                            class="badge bg-{{ $orden->estado == 'Validada' || $orden->estado == 'Completada' || $orden->estado == 'Entregada' ? 'success' : ($orden->estado == 'En Proceso' ? 'warning' : ($orden->estado == 'Transcrita' ? 'info' : 'danger')) }} bg-opacity-10 text-{{ $orden->estado == 'Validada' || $orden->estado == 'Completada' || $orden->estado == 'Entregada' ? 'success' : ($orden->estado == 'En Proceso' ? 'warning' : ($orden->estado == 'Transcrita' ? 'info' : 'danger')) }} border border-{{ $orden->estado == 'Validada' || $orden->estado == 'Completada' || $orden->estado == 'Entregada' ? 'success' : ($orden->estado == 'En Proceso' ? 'warning' : ($orden->estado == 'Transcrita' ? 'info' : 'danger')) }} border-opacity-25 rounded-pill px-2.5 py-0.5" style="font-size: 0.78rem;">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> {{ $orden->estado }}
                                        </span>
                                    </div>
                                    <div class="text-muted small mt-0.5 text-truncate" style="font-size: 0.8rem;">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i> {{ \Carbon\Carbon::parse($orden->created_at)->format('d/m/Y h:i A') }}
                                        @if($orden->medico)
                                            <span class="ms-2 d-none d-sm-inline"><i class="fas fa-user-md me-1 text-primary"></i> Dr(a): {{ $orden->medico->nombre }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-6 col-sm-3">
                                    <div class="stat-pill">
                                        <div class="text-muted small text-uppercase fw-bold mb-0.5" style="font-size: 0.7rem;"><i
                                                class="fas fa-id-card me-1 text-primary"></i> Cédula</div>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">{{ $orden->paciente->cedula ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="stat-pill">
                                        <div class="text-muted small text-uppercase fw-bold mb-0.5" style="font-size: 0.7rem;"><i
                                                class="fas fa-venus-mars me-1 text-primary"></i> Sexo</div>
                                        <div class="fw-bold text-dark" style="font-size: 0.88rem;">
                                            {{ $orden->paciente->sexo == 'M' ? 'Masculino' : ($orden->paciente->sexo == 'F' ? 'Femenino' : 'N/A') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="stat-pill">
                                        <div class="text-muted small text-uppercase fw-bold mb-0.5" style="font-size: 0.7rem;"><i
                                                class="fas fa-birthday-cake me-1 text-primary"></i> Edad</div>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">
                                            @if ($orden->paciente->fechaNacimiento)
                                                {{ \Carbon\Carbon::parse($orden->paciente->fechaNacimiento)->age }} años
                                            @else
                                                N/A
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 col-sm-3">
                                    <div class="stat-pill">
                                        <div class="text-muted small text-uppercase fw-bold mb-0.5" style="font-size: 0.7rem;"><i
                                                class="fas fa-file-alt me-1 text-primary"></i> Factura</div>
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.88rem;">{{ $orden->factura->correlativo ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 text-center text-lg-end mt-3 mt-lg-0 ps-lg-4">
                            <div class="order-code-badge text-center d-inline-block w-100">
                                <div class="text-primary small text-uppercase fw-bold mb-0.5" style="font-size: 0.72rem;">
                                    <i class="fas fa-barcode me-1"></i> Orden de Laboratorio
                                </div>
                                <h3 class="fw-bold text-primary mb-0.5" style="letter-spacing: 1px;">{{ $orden->codigo }}</h3>
                                <div class="text-muted small" style="font-size: 0.8rem;">
                                    Total exámenes: <span class="fw-bold text-dark">{{ $orden->detalles->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Resultados -->
        <div class="col-md-12">
            <form id="formProcesarOrden">
                @csrf
                @php
                    $bloqueada = in_array($orden->estado, ['Transcrita', 'Validada', 'Entregada', 'Completada']);
                    if (Auth::user()->can('validar_resultados')) {
                        $bloqueada = false;
                    }

                    // Resultados guardados reales en BD
                    $resCount = $orden->detalles->flatMap->resultados->whereNotNull('valor')->where('valor', '!=', '')->count();
                    $bactCount = $orden->detalles->filter(fn($d) => !empty($d->datos_bacteriologia))->count();
                    $tieneResultadosGuardados = ($resCount > 0 || $bactCount > 0);
                    $esPendiente = ($orden->estado === 'Pendiente' && !$tieneResultadosGuardados);
                    $esFinalizada = in_array($orden->estado, ['Validada', 'Entregada', 'Completada']);
                @endphp

                <input type="hidden" id="orden_id" value="{{ $orden->id }}">

                @foreach ($orden->detalles as $detalle)
                    @if ($detalle->examen)
                        <!-- Es un Examen Individual -->
                        @include('Sistema.pages.empresa.components.examen_card', [
                            'examen' => $detalle->examen,
                            'detalle' => $detalle,
                        ])
                    @elseif($detalle->perfil)
                        <!-- Es un Perfil (contiene varios exámenes) -->
                        <div class="profile-container-card shadow-sm mb-4">
                            <div class="profile-header-bar px-3 px-md-4 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 38px; height: 38px;">
                                        <i class="fas fa-cubes fs-6"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-dark fw-bold" style="font-size: 0.98rem;">Perfil: {{ $detalle->perfil->nombre }}</h5>
                                        <small class="text-muted" style="font-size: 0.78rem;">{{ $detalle->perfil->examenes->count() }} exámenes en este panel</small>
                                    </div>
                                </div>
                                <div class="form-check form-check-inline m-0 d-flex align-items-center bg-white border border-primary-subtle px-3 py-1.5 rounded-pill shadow-sm ms-auto" title="Seleccionar perfil completo para imprimir">
                                    <input class="form-check-input check-imprimir m-0 me-2"
                                           type="checkbox"
                                           value="{{ $detalle->id }}"
                                           id="check_detalle_{{ $detalle->id }}"
                                           style="cursor: pointer; width: 1.15em; height: 1.15em;">
                                    <label class="form-check-label small fw-bold text-primary cursor-pointer select-none mb-0" for="check_detalle_{{ $detalle->id }}" style="font-size: 0.8rem; cursor: pointer;">
                                        <i class="fas fa-print me-1"></i> Imprimir Perfil
                                    </label>
                                </div>
                            </div>
                            <div class="p-3 p-md-4">
                                @foreach ($detalle->perfil->examenes as $examenPerfil)
                                    @include('Sistema.pages.empresa.components.examen_card', [
                                        'examen' => $examenPerfil,
                                        'detalle' => $detalle,
                                        'esPerfil' => true
                                    ])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                <!-- Observación General -->
                <div class="card lab-card shadow-sm mt-4 mb-4 border-0">
                    <div class="card-header bg-white border-bottom border-light-subtle py-3 px-3 px-md-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <h5 class="mb-0 fw-bold text-dark d-flex align-items-center" style="font-size: 1rem;">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-2.5 shadow-sm" style="width: 36px; height: 36px;">
                                <i class="fas fa-clipboard-list fs-6"></i>
                            </div>
                            Observación General
                        </h5>
                        <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small fw-medium d-none d-sm-inline-block" style="font-size: 0.75rem;">
                            <i class="fas fa-print me-1"></i> Se imprime al pie del reporte
                        </span>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <textarea class="form-control rounded-4 shadow-none p-3 border-light-subtle" id="observacion_general" rows="3"
                            placeholder="Escribe aquí cualquier observación médica, nota de muestra o conclusión sobre los resultados de esta orden..."
                            {{ $bloqueada ? 'disabled' : '' }}>{{ $orden->observacion }}</textarea>
                        <div class="text-muted small mt-2 d-flex align-items-center" style="font-size: 0.78rem;">
                            <i class="fas fa-info-circle text-primary me-1 flex-shrink-0"></i>
                            <span>Esta nota aparecerá en la sección inferior del informe impreso y en el reporte enviado por correo.</span>
                        </div>
                    </div>
                </div>

                <!-- Barra de Acciones Inteligente y Totalmente Adaptable (Sticky) -->
                <div class="card sticky-actions-bar mt-4 mb-4 border-0">
                    <div class="card-body p-3 p-md-4 d-flex flex-column flex-lg-row justify-content-between align-items-stretch align-items-lg-center gap-3">
                        
                        <!-- Lado Izquierdo: Opciones de Despacho (Impresión y Correo) -->
                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-start align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if ($esPendiente)
                                <div class="text-muted small d-flex align-items-center bg-light px-3 py-2 rounded-pill border w-100">
                                    <i class="fas fa-info-circle text-primary me-2 fs-6 flex-shrink-0"></i>
                                    <span>Ingrese resultados y presione <strong>Guardar Progreso</strong> para habilitar despacho.</span>
                                </div>
                            @elseif ($esFinalizada)
                                <a href="{{ route('ordenes.imprimir-resultados', $orden->id) }}" target="_blank"
                                    class="btn btn-primary rounded-pill shadow-sm px-3 px-md-4 fw-bold flex-fill text-center" title="Imprimir reporte oficial completo">
                                    <i class="fas fa-print me-1"></i> Imprimir Reporte Oficial
                                </a>
                                <button type="button" class="btn btn-white bg-white border rounded-pill shadow-sm px-3 fw-bold text-dark flex-fill text-center"
                                    onclick="imprimirSeleccionados({{ $orden->id }})" id="btn-imprimir-sel" title="Imprimir solo los exámenes seleccionados con casilla">
                                    <i class="fas fa-tasks me-1 text-primary"></i> Seleccionados
                                    <span class="badge bg-primary ms-1" id="contador-sel" style="display:none;">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-primary rounded-pill shadow-sm px-3 fw-bold flex-fill text-center"
                                    onclick="enviarCorreo({{ $orden->id }})" id="btnEnviarCorreo" title="Enviar resultados por correo al paciente">
                                    <i class="fas fa-envelope me-1"></i> Enviar Correo
                                </button>
                            @else
                                {{-- Estado En Proceso o Transcrita con resultados guardados --}}
                                <a href="{{ route('ordenes.imprimir-resultados', $orden->id) }}?solo_listos=1" target="_blank"
                                    class="btn btn-outline-primary rounded-pill shadow-sm px-3 fw-bold flex-fill text-center" title="Imprimir únicamente los exámenes que ya tienen resultados">
                                    <i class="fas fa-check-circle me-1"></i> Solo Listos (Parcial)
                                </a>
                                <button type="button" class="btn btn-white bg-white border rounded-pill shadow-sm px-3 fw-bold text-dark flex-fill text-center"
                                    onclick="imprimirSeleccionados({{ $orden->id }})" id="btn-imprimir-sel" title="Imprimir solo los exámenes seleccionados con casilla">
                                    <i class="fas fa-tasks me-1 text-primary"></i> Seleccionados
                                    <span class="badge bg-dark ms-1" id="contador-sel" style="display:none;">0</span>
                                </button>
                                <a href="{{ route('ordenes.imprimir-resultados', $orden->id) }}" target="_blank"
                                    class="btn btn-light border rounded-pill shadow-sm px-3 fw-bold text-muted flex-fill text-center" title="Ver / Imprimir vista previa de toda la orden">
                                    <i class="fas fa-print me-1"></i> Todo
                                </a>
                                <button type="button" class="btn btn-primary rounded-pill shadow-sm px-3 fw-bold flex-fill text-center"
                                    onclick="enviarCorreo({{ $orden->id }})" id="btnEnviarCorreo" title="Enviar resultados disponibles por correo al paciente">
                                    <i class="fas fa-envelope me-1"></i> Enviar Correo
                                </button>
                            @endif
                        </div>

                        <!-- Lado Derecho: Flujo de Guardado, Transcripción y Validación -->
                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 justify-content-end align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if ($bloqueada)
                                <div class="w-100 text-center">
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-2 d-inline-block w-100 w-sm-auto">
                                        <i class="fas fa-lock me-1"></i> Orden en estado {{ $orden->estado }}
                                    </span>
                                </div>
                            @elseif ($esFinalizada)
                                <button type="button" class="btn btn-success rounded-pill shadow-sm px-4 fw-bold flex-fill text-center"
                                    onclick="guardar('{{ $orden->estado }}')">
                                    <i class="fas fa-sync me-1"></i> Actualizar {{ $orden->estado }}
                                </button>
                            @else
                                @if ($orden->estado !== 'Transcrita')
                                    <button type="button" class="btn btn-warning rounded-pill shadow-sm px-4 text-dark fw-bold flex-fill text-center"
                                        onclick="guardar('En Proceso')" title="Guardar borrador de resultados">
                                        <i class="fas fa-save me-1"></i> Guardar Progreso
                                    </button>
                                @endif

                                @can('validar_resultados')
                                    @if ($orden->estado === 'Transcrita')
                                        <button type="button" class="btn btn-outline-danger rounded-pill shadow-sm px-4 fw-bold flex-fill text-center"
                                            onclick="guardar('En Proceso')" title="Regresar al transcriptor para correcciones">
                                            <i class="fas fa-undo me-1"></i> Devolver
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-success rounded-pill shadow-sm px-4 fw-bold flex-fill text-center"
                                        onclick="guardar('Validada')" title="Validar definitivamente y aplicar firma">
                                        <i class="fas fa-check-circle me-1"></i> Guardar y Validar
                                    </button>
                                @else
                                    @if (Auth::user()->empresa->requiere_validacion)
                                        <button type="button" class="btn btn-primary rounded-pill shadow-sm px-4 fw-bold flex-fill text-center"
                                            onclick="guardar('Transcrita')" title="Enviar resultados para revisión del bioanalista">
                                            <i class="fas fa-keyboard me-1"></i> Guardar Transcripción
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success rounded-pill shadow-sm px-4 fw-bold flex-fill text-center"
                                            onclick="guardar('Validada')" title="Validar definitivamente">
                                            <i class="fas fa-check-circle me-1"></i> Guardar y Validar
                                        </button>
                                    @endif
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        const consumosPropuestos = @json($consumosPropuestos ?? []);
        const inventariosList = @json($inventarios ?? []);
    </script>
    <script
        src="{{ asset('estilos/jsPropios/procesar_orden.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/procesar_orden.js')) ?: time() }}">
    </script>
@endsection
