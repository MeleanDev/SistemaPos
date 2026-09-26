@props([
    'name',
    'id' => null,
    'value',
    'label' => null,
    'description' => null,
    'checked' => false,
    'disabled' => false,
    'inline' => false,
    'card' => false,
    'cardId' => null,
    'cardClass' => '',
    'icon' => null,
    'iconColor' => 'text-primary',
    'iconBg' => 'bg-primary bg-opacity-10',
    'col' => null,
    'labelClass' => null,
])

@php
    $radioId = $id ?? ($name . '_' . Str::slug($value));
    $finalLabelClass = $labelClass ?? 'form-check-label fw-semibold cursor-pointer';
    if (!str_contains($finalLabelClass, 'form-check-label')) {
        $finalLabelClass = 'form-check-label ' . $finalLabelClass;
    }
@endphp

@if($card)
    <div class="{{ $col ?? 'col-md-6' }}">
        <div class="card card-moneda-seleccion h-100 p-3 rounded-4 border-2 cursor-pointer transition-all {{ $checked ? 'active-moneda' : '' }} {{ $cardClass }}"
            @if($cardId) id="{{ $cardId }}" @endif>
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    @if($icon)
                        <div class="avatar-executive-sm rounded-3 {{ $iconBg }} {{ $iconColor }} d-flex align-items-center justify-content-center"
                            style="width: 44px; height: 44px; font-size: 1.3rem;">
                            <i class="{{ $icon }}"></i>
                        </div>
                    @endif
                    <div>
                        @if($label || $slot->isNotEmpty())
                            <h6 class="fw-bold text-dark mb-0">{!! $label ?? $slot !!}</h6>
                        @endif
                        @if($description)
                            <small class="text-muted">{{ $description }}</small>
                        @endif
                    </div>
                </div>
                <div class="form-check m-0">
                    <input class="form-check-input" type="radio"
                        name="{{ $name }}" id="{{ $radioId }}" value="{{ $value }}"
                        @if($checked) checked @endif
                        @if($disabled) disabled @endif
                        {{ $attributes }}>
                </div>
            </div>
        </div>
    </div>
@else
    @if($col)<div class="{{ $col }}">@endif
        <div class="form-check {{ $inline ? 'form-check-inline' : '' }}">
            <input class="form-check-input" type="radio" 
                name="{{ $name }}" id="{{ $radioId }}" value="{{ $value }}"
                @if($checked) checked @endif
                @if($disabled) disabled @endif
                {{ $attributes }}>
            @if($label || $slot->isNotEmpty())
                <label class="{{ $finalLabelClass }}" for="{{ $radioId }}">
                    {!! $label ?? $slot !!}
                    @if($description)
                        <small class="text-muted d-block fw-normal">{{ $description }}</small>
                    @endif
                </label>
            @endif
        </div>
    @if($col)</div>@endif
@endif
