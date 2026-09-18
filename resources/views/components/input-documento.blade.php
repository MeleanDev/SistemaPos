@props([
    'label' => 'Identificación / Cédula / RIF',
    'icon' => 'fas fa-id-card',
    'selectName' => 'tipo_cedula',
    'inputName' => 'cedula_numero',
    'required' => true,
    'col' => 'col-md-6',
    'maxlength' => '20',
    'placeholder' => '12345678',
    'defaultType' => 'V-',
    'types' => null,
])

@php
    $documentTypes = $types ?? config('pos.prefijos_cedula', ['V-', 'J-', 'E-', 'G-', 'P-']);
@endphp

<div class="{{ $col }}">
    <label for="{{ $inputName }}" class="form-label-executive">
        @if($icon)
            <i class="{{ $icon }}"></i>
        @endif
        {{ $label }}
        @if($required)
            <span class="text-danger ms-1">*</span>
        @endif
    </label>
    <div class="input-group input-group-executive">
        <select class="form-select form-select-executive" id="{{ $selectName }}" name="{{ $selectName }}" style="max-width: 85px;">
            @foreach($documentTypes as $key => $val)
                @php
                    $valClean = is_numeric($key) ? $val : $key;
                    $textClean = is_numeric($key) ? $val : $val;
                @endphp
                <option value="{{ $valClean }}" @selected($valClean === $defaultType)>{{ $textClean }}</option>
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
