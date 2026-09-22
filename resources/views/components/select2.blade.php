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
])

@php
    $selectId = $id ?? $name;
@endphp

<div class="{{ $col }}">
    @if($label)
        <label for="{{ $selectId }}" class="form-label-executive">
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
