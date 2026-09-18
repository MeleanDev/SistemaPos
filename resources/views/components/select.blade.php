@props([
    'name',
    'id' => null,
    'label' => null,
    'icon' => null,
    'required' => false,
    'col' => 'col-12',
    'disabled' => false,
    'optionalText' => null,
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
        {{ $attributes->merge(['class' => 'form-select form-select-executive']) }}
    >
        {{ $slot }}
    </select>
</div>
