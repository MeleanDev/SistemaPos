@props([
    'label' => 'Teléfono Móvil',
    'icon' => 'fas fa-phone',
    'selectName' => 'codigo_pais',
    'inputName' => 'telefono_numero',
    'required' => false,
    'optionalText' => 'Opcional',
    'col' => 'col-md-6',
    'maxlength' => '50',
    'placeholder' => '4121234567',
    'defaultCode' => '+58',
    'countries' => null,
])

@php
    $countryList = $countries ?? config('pos.codigos_pais', [
        ['codigo' => '+58', 'pais' => 'Venezuela', 'bandera' => '🇻🇪'],
        ['codigo' => '+1', 'pais' => 'Estados Unidos / Canadá', 'bandera' => '🇺🇸'],
        ['codigo' => '+57', 'pais' => 'Colombia', 'bandera' => '🇨🇴'],
        ['codigo' => '+56', 'pais' => 'Chile', 'bandera' => '🇨🇱'],
        ['codigo' => '+51', 'pais' => 'Perú', 'bandera' => '🇵🇪'],
        ['codigo' => '+55', 'pais' => 'Brasil', 'bandera' => '🇧🇷'],
        ['codigo' => '+593', 'pais' => 'Ecuador', 'bandera' => '🇪🇨'],
        ['codigo' => '+34', 'pais' => 'España', 'bandera' => '🇪🇸'],
        ['codigo' => '+54', 'pais' => 'Argentina', 'bandera' => '🇦🇷'],
        ['codigo' => '+507', 'pais' => 'Panamá', 'bandera' => '🇵🇦'],
        ['codigo' => '+52', 'pais' => 'México', 'bandera' => '🇲🇽'],
    ]);
@endphp

<div class="{{ $col }}">
    <label for="{{ $inputName }}" class="form-label-executive">
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
    <div class="input-group input-group-executive">
        <select class="form-select form-select-executive" id="{{ $selectName }}" name="{{ $selectName }}" style="max-width: 125px; background-position: right 0.5rem center;">
            @foreach($countryList as $country)
                <option value="{{ $country['codigo'] }}" @selected($country['codigo'] === $defaultCode)>
                    {{ $country['bandera'] }} {{ $country['codigo'] }}
                </option>
            @endforeach
        </select>
        <input
            type="text"
            maxlength="{{ $maxlength }}"
            autocomplete="off"
            @if($required) required @endif
            class="form-control form-control-executive"
            id="{{ $inputName }}"
            name="{{ $inputName }}"
            placeholder="{{ $placeholder }}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
            {{ $attributes }}
        >
    </div>
</div>
