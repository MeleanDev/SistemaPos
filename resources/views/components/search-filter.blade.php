@props([
    'inputId' => 'buscador',
    'counterId' => 'contador',
    'placeholder' => 'Buscar en el directorio...',
    'loadingText' => 'Cargando registros...',
    'counterIcon' => 'fas fa-list',
    'badgeClass' => '',
])

<div {{ $attributes->merge(['class' => 'card card-search-filter border-0 shadow-sm rounded-4 mb-4']) }} style="border: 1.5px solid #eef2f6 !important; background: #ffffff;">
    <div class="card-body p-3 px-md-4 py-md-3">
        <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-between gap-3">
            <!-- BUSCADOR Y FILTROS EXTRA (IZQUIERDA) -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2.5 flex-grow-1">
                <div class="search-input-wrapper d-flex align-items-center rounded-pill px-3 py-1.5 transition-all flex-grow-1" style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; transition: all 0.25s ease;">
                    <div class="search-icon-box d-flex align-items-center justify-content-center text-primary me-2" style="width: 28px; height: 28px; min-width: 28px;">
                        <i class="fas fa-search" style="font-size: 0.95rem; opacity: 0.85;"></i>
                    </div>
                    <input
                        type="text"
                        id="{{ $inputId }}"
                        class="form-control border-0 bg-transparent shadow-none px-1 py-1 text-dark"
                        placeholder="{{ $placeholder }}"
                        autocomplete="off"
                        style="font-size: 0.9rem; font-weight: 500;"
                        oninput="const val = $(this).val().trim(); $(this).siblings('.btn-clear-search').toggleClass('d-none', !val);"
                    >
                    <button
                        type="button"
                        class="btn btn-xs text-muted border-0 bg-transparent p-0 ms-2 d-none btn-clear-search"
                        style="cursor: pointer; opacity: 0.6; transition: opacity 0.2s ease;"
                        onmouseover="$(this).css('opacity', 1)"
                        onmouseout="$(this).css('opacity', 0.6)"
                        onclick="$(this).siblings('input').val('').trigger('input').focus(); $(this).addClass('d-none');"
                        title="Limpiar búsqueda"
                    >
                        <i class="fas fa-times-circle fs-6"></i>
                    </button>
                </div>

                @if (isset($filters))
                    <div class="flex-shrink-0">
                        {{ $filters }}
                    </div>
                @endif
            </div>

            <!-- ACCIONES Y CONTADOR (DERECHA) -->
            <div class="d-flex align-items-center justify-content-between justify-content-lg-end gap-2 flex-shrink-0 flex-nowrap">
                {{ $actions ?? '' }}

                <div id="{{ $counterId }}" class="counter-badge-executive d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill shadow-xs flex-shrink-0 text-nowrap" style="background: #ffffff; border: 1.5px solid #e2e8f0; transition: all 0.2s ease; height: 42px;">
                    <span class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-xs" style="width: 24px; height: 24px; min-width: 24px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.72rem;">
                        <i class="{{ $counterIcon }}"></i>
                    </span>
                    <span class="fw-bold text-dark font-monospace" style="font-size: 0.84rem; letter-spacing: -0.01em;">
                        {{ $loadingText }}
                    </span>
                </div>
            </div>
        </div>

        @if (isset($bottom))
            <div class="pt-2.5 mt-2.5 border-top" style="border-color: #f1f5f9 !important;">
                {{ $bottom }}
            </div>
        @endif
    </div>
</div>

<style>
    .search-input-wrapper:focus-within {
        background-color: #ffffff !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12) !important;
    }
    .search-input-wrapper input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    .counter-badge-executive:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
</style>
