@props([
    'title' => 'Métrica',
    'value' => '0',
    'icon' => 'fas fa-chart-line',
    'color' => 'primary',
    'subtitle' => null,
    'badge' => null,
    'badgeColor' => 'success'
])

<div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted fw-semibold small text-uppercase tracking-wider">{{ $title }}</span>
            <div class="rounded-circle bg-{{ $color }}-subtle text-{{ $color }} d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="{{ $icon }} fs-5"></i>
            </div>
        </div>
        <div class="d-flex align-items-baseline justify-content-between">
            <h3 class="fw-bold text-dark mb-0">{{ $value }}</h3>
            @if($badge)
                <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} rounded-pill px-2 py-1 small">
                    {{ $badge }}
                </span>
            @endif
        </div>
        @if($subtitle)
            <p class="text-muted small mb-0 mt-2">{{ $subtitle }}</p>
        @endif
    </div>
</div>
