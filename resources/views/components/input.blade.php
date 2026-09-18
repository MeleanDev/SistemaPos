@props([
    'name',
    'id' => null,
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'maxlength' => 255,
    'col' => 'col-12',
    'value' => '',
    'readonly' => false,
    'disabled' => false,
    'autocomplete' => 'off',
    'optionalText' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div class="{{ $col }}">
    @if($label)
        <label for="{{ $inputId }}" class="form-label-executive">
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
    <input 
        type="{{ $type }}" 
        id="{{ $inputId }}" 
        name="{{ $name }}" 
        value="{{ $value }}"
        maxlength="{{ $maxlength }}" 
        autocomplete="{{ $autocomplete }}" 
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($readonly) readonly @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge(['class' => 'form-control form-control-executive']) }}
    >
</div>
