@props([
    'title',
    'subtitle' => null,
    'description' => null,
    'icon' => null,
    'class' => '',
])

@php
    $desc = $subtitle ?? $description;
@endphp

<div {{ $attributes->merge(['class' => 'card border rounded-4 p-3 mb-3 bg-white shadow-xs ' . $class]) }}>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2.5">
            @if($icon)
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px; background-color: #eff6ff; color: #2563eb; font-size: 1rem;">
                    <i class="{{ $icon }}"></i>
                </div>
            @endif
            <div>
                <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.92rem; letter-spacing: -0.01em;">{{ $title }}</h6>
                @if($desc)
                    <small class="text-muted d-block" style="font-size: 0.78rem;">{{ $desc }}</small>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
