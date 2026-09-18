@props([
    'id' => 'modalGeneral',
    'title' => 'Formulario',
    'icon' => 'fas fa-edit',
    'size' => 'modal-lg',
    'headerColor' => 'bg-primary',
    'formId' => null,
    'submitButton' => true,
    'submitText' => 'Guardar',
    'submitIcon' => 'fas fa-save',
    'headerExtra' => null,
])

<div id="{{ $id }}" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}Titulo" aria-hidden="true">
    <div class="modal-dialog {{ $size }} modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

            <!-- Modal Header -->
            <div id="{{ $id }}Header" class="modal-header border-0 {{ $headerColor }} text-white rounded-top-4 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center mb-0" id="{{ $id }}Titulo">
                    <i class="{{ $icon }} me-2" id="{{ $id }}Icono"></i>
                    <span id="{{ $id }}TituloTexto">{{ $title }}</span>
                    @if($headerExtra)
                        {!! $headerExtra !!}
                    @endif
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                {{ $slot }}
            </div>

            <!-- Modal Footer (opcional / por defecto) -->
            @if(isset($footer))
                <div class="modal-footer border-0 bg-light px-4 pb-4 pt-0">
                    {{ $footer }}
                </div>
            @elseif($submitButton)
                <div class="modal-footer border-0 bg-light px-4 pb-4 pt-0 text-end">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" @if($formId) form="{{ $formId }}" @endif id="{{ $id }}BtnGuardar" class="btn btn-primary rounded-pill px-4 ms-2 shadow-sm">
                        <i class="{{ $submitIcon }} me-1"></i> <span id="{{ $id }}TextoGuardar">{{ $submitText }}</span>
                    </button>
                </div>
            @endif

        </div>
    </div>
</div>
