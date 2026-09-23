@props([
    'name',
    'id' => null,
    'label' => null,
    'icon' => null,
    'placeholder' => 'Seleccione una opción...',
    'required' => false,
    'col' => 'col-12',
    'disabled' => false,
    'optionalText' => null,
    'modalParent' => null,
    'allowClear' => true,
    'actionText' => null,
    'actionIcon' => 'fas fa-plus',
    'actionOnClick' => null,
])

@php
    $selectId = $id ?? $name;
@endphp

<div class="{{ $col }}">
    @if($label)
        <div class="d-flex align-items-center justify-content-between mb-1">
            <label for="{{ $selectId }}" class="form-label-executive mb-0">
                @if($icon)
                    <i class="{{ $icon }}"></i>
                @endif
                {{ $label }}
                @if($required)
                    <span class="text-danger ms-1">*</span>
                @elseif($optionalText)
                    <span class="text-muted fw-normal text-lowercase ms-1">({{ $optionalText }})</span>
                @endif
            </label>
            @if($actionText && $actionOnClick)
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-0.5 fw-bold d-inline-flex align-items-center gap-1 shadow-xs" onclick="{{ $actionOnClick }}" style="font-size: 0.72rem;">
                    @if($actionIcon)<i class="{{ $actionIcon }}"></i>@endif
                    <span>{{ $actionText }}</span>
                </button>
            @endif
        </div>
    @endif
    <select 
        id="{{ $selectId }}" 
        name="{{ $name }}" 
        @if($required) required @endif
        @if($disabled) disabled @endif
        data-placeholder="{{ $placeholder }}"
        @if($modalParent) data-modal-parent="{{ $modalParent }}" @endif
        @if($allowClear) data-allow-clear="true" @endif
        {{ $attributes->merge(['class' => 'form-select select2-executive']) }}
    >
        {{ $slot }}
    </select>
</div>
