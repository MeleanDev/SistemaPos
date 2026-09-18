@props([
    'icon' => 'fas fa-plus',
    'text' => null,
    'type' => 'button',
    'variant' => 'primary',
    'href' => null,
    'target' => null,
])

@php
    $gradientClass = match($variant) {
        'primary' => 'btn-primary btn-gradient-primary',
        'success' => 'btn-success',
        'danger' => 'btn-danger',
        'warning' => 'btn-warning text-dark',
        'info' => 'btn-info text-white',
        'dark' => 'btn-dark',
        default => "btn-{$variant}",
    };

    $classes = "btn {$gradientClass} rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center";
@endphp

@if($href)
    <a href="{{ $href }}" @if($target) target="{{ $target }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="{{ $icon }} me-2"></i>
        @endif
        <span>{{ $text ?? $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <i class="{{ $icon }} me-2"></i>
        @endif
        <span>{{ $text ?? $slot }}</span>
    </button>
@endif
