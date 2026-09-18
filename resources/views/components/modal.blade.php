@props([
    'id' => 'modalGeneral',
    'title' => 'Nuevo Registro',
    'subtitle' => 'Completa la información solicitada',
    'icon' => 'fas fa-user text-warning fs-5',
    'size' => 'modal-lg',
    'headerColor' => 'bg-dark text-white',
    'formId' => null,
    'submitButton' => true,
    'submitText' => 'Guardar',
    'submitIcon' => 'fas fa-save me-1',
])

<div id="{{ $id }}" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}Titulo"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered {{ $size }}">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            
            <!-- Modal Header Executive -->
            <div id="{{ $id }}Header" class="modal-header border-0 px-4 py-3 {{ $headerColor }}">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white bg-opacity-10 p-2 me-3 d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px;">
                        <i class="{{ $icon }}" id="{{ $id }}Icono"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="{{ $id }}Titulo">{{ $title }}</h5>
                        @if($subtitle)
                            <small class="text-white-50" id="{{ $id }}Subtitulo" style="font-size: 0.75rem;">{{ $subtitle }}</small>
                        @endif
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                {{ $slot }}
            </div>

            <!-- Modal Footer -->
            @if(isset($footer))
                <div class="modal-footer bg-light border-0 px-4 py-3 d-flex justify-content-between">
                    {{ $footer }}
                </div>
            @elseif($submitButton)
                <div class="modal-footer bg-light border-0 px-4 py-3 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-executive-cancel" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" @if($formId) form="{{ $formId }}" @endif id="{{ $id }}BtnGuardar"
                        class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm btn-gradient-primary">
                        <i class="{{ $submitIcon }}"></i> <span id="{{ $id }}TextoGuardar">{{ $submitText }}</span>
                    </button>
                </div>
            @endif

        </div>
    </div>
</div>
