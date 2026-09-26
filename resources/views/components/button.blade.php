@props([
    'variant' => 'primary',
    'size' => 'md',
    'rounded' => 'pill',
    'type' => 'button',
    'icon' => null,
    'iconColor' => null,
    'iconPosition' => 'left',
    'text' => null,
    'badge' => null,
    'badgeVariant' => 'primary',
    'badgeId' => null,
    'href' => null,
    'target' => null,
    'disabled' => false,
])

@php
    $variantClass = match($variant) {
        'primary' => 'btn-primary btn-gradient-primary text-white',
        'secondary' => 'btn-secondary',
        'success' => 'btn-success text-white',
        'danger' => 'btn-danger text-white',
        'warning' => 'btn-warning text-dark',
        'info' => 'btn-info text-white',
        'dark' => 'btn-dark text-white',
        'light' => 'btn-light',
        'outline-primary' => 'btn-outline-primary',
        'outline-secondary' => 'btn-outline-secondary',
        'outline-success' => 'btn-outline-success',
        'outline-danger' => 'btn-outline-danger',
        'outline-warning' => 'btn-outline-warning',
        'outline-info' => 'btn-outline-info',
        'outline-dark' => 'btn-outline-dark',
        default => "btn-{$variant}",
    };

    $sizeClass = match($size) {
        'sm' => 'btn-sm px-3 py-1.5',
        'xs' => 'btn-sm px-2.5 py-1',
        'lg' => 'btn-lg px-4 py-2.5',
        default => 'px-3.5 py-2',
    };

    $roundedClass = match($rounded) {
        'pill' => 'rounded-pill',
        '3' => 'rounded-3',
        '4' => 'rounded-4',
        'circle' => 'rounded-circle p-2',
        '0' => 'rounded-0',
        default => "rounded-{$rounded}",
    };

    $classes = "btn {$variantClass} {$sizeClass} {$roundedClass} fw-bold shadow-xs d-inline-flex align-items-center justify-content-center gap-2 transition-all";
@endphp

@if($href)
    <a href="{{ $href }}" @if($target) target="{{ $target }}" @endif @if($disabled) tabindex="-1" aria-disabled="true" @endif {{ $attributes->merge(['class' => $classes . ($disabled ? ' disabled' : '')]) }}>
        @if($icon && $iconPosition === 'left')
            <i class="{{ $icon }} {{ $iconColor }}"></i>
        @endif
        @if($text || $slot->isNotEmpty())
            <span>{{ $text ?? $slot }}</span>
        @endif
        @if($icon && $iconPosition === 'right')
            <i class="{{ $icon }} {{ $iconColor }}"></i>
        @endif
        @if($badge !== null)
            <span class="badge rounded-pill bg-{{ $badgeVariant }} ms-1" @if($badgeId) id="{{ $badgeId }}" @endif>{{ $badge }}</span>
        @endif
    </a>
@else
    <button type="{{ $type }}" @if($disabled) disabled @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon && $iconPosition === 'left')
            <i class="{{ $icon }} {{ $iconColor }}"></i>
        @endif
        @if($text || $slot->isNotEmpty())
            <span>{{ $text ?? $slot }}</span>
        @endif
        @if($icon && $iconPosition === 'right')
            <i class="{{ $icon }} {{ $iconColor }}"></i>
        @endif
        @if($badge !== null)
            <span class="badge rounded-pill bg-{{ $badgeVariant }} ms-1" @if($badgeId) id="{{ $badgeId }}" @endif>{{ $badge }}</span>
        @endif
    </button>
@endif
