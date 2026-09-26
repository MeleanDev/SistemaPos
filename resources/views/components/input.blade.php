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
    'addonIcon' => null,
    'addonText' => null,
    'addonPosition' => 'left',
    'helpText' => null,
])

@php
    $inputId = $id ?? $name;
    $hasAddon = !empty($addonIcon) || !empty($addonText);
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

    @if($hasAddon)
        <div class="input-group input-group-executive">
            @if($addonPosition === 'left')
                <span class="input-group-text bg-white text-primary font-monospace">
                    @if($addonIcon)<i class="{{ $addonIcon }}"></i>@endif
                    @if($addonText){{ $addonText }}@endif
                </span>
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
                {{ $attributes->merge(['class' => 'form-control form-control-executive ' . ($readonly ? 'bg-light text-dark' : '')]) }}
            >
            @if($addonPosition === 'right')
                <span class="input-group-text bg-white text-primary font-monospace">
                    @if($addonIcon)<i class="{{ $addonIcon }}"></i>@endif
                    @if($addonText){{ $addonText }}@endif
                </span>
            @endif
        </div>
    @else
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
            {{ $attributes->merge(['class' => 'form-control form-control-executive ' . ($readonly ? 'bg-light text-dark' : '')]) }}
        >
    @endif

    @if($helpText)
        <small class="text-muted font-monospace d-block mt-1" style="font-size: 0.74rem;">{{ $helpText }}</small>
    @endif
</div>
