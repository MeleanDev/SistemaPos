@props([
    'name',
    'id' => null,
    'value' => '1',
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'switch' => false,
    'inline' => false,
    'col' => null,
    'labelClass' => null,
    'wrapperClass' => null,
])

@php
    $checkboxId = $id ?? ($name . '_' . Str::slug($value));
    $baseWrapperClass = $switch ? 'form-check form-switch' : 'form-check';
    if ($inline) {
        $baseWrapperClass .= ' form-check-inline';
    }
    if ($wrapperClass) {
        $baseWrapperClass .= ' ' . $wrapperClass;
    }
    $finalLabelClass = $labelClass ?? 'form-check-label fw-semibold cursor-pointer';
    if (!str_contains($finalLabelClass, 'form-check-label')) {
        $finalLabelClass = 'form-check-label ' . $finalLabelClass;
    }
@endphp

@if($col)<div class="{{ $col }}">@endif
    <div class="{{ $baseWrapperClass }}">
        <input class="form-check-input cursor-pointer" type="checkbox" 
            name="{{ $name }}" id="{{ $checkboxId }}" value="{{ $value }}"
            @if($checked) checked @endif
            @if($disabled) disabled @endif
            {{ $attributes }}>
        @if($label || $slot->isNotEmpty())
            <label class="{{ $finalLabelClass }}" for="{{ $checkboxId }}">
                {!! $label ?? $slot !!}
                @if($description)
                    <small class="text-muted d-block fw-normal">{{ $description }}</small>
                @endif
            </label>
        @endif
    </div>
@if($col)</div>@endif
