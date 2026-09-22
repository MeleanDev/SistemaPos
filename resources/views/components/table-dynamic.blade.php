@props([
    'id' => null,
    'bodyId' => null,
    'headers' => [],
    'class' => '',
])

<div class="table-responsive border rounded-4 bg-white shadow-xs {{ $class }}">
    <table class="table table-hover align-middle mb-0" @if($id) id="{{ $id }}" @endif>
        <thead style="background-color: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
            <tr>
                @foreach($headers as $header)
                    @if(is_array($header))
                        <th 
                            @if(isset($header['width'])) style="width: {{ $header['width'] }};" @endif
                            class="py-2.5 px-3 fw-bold text-dark {{ $header['class'] ?? '' }}"
                            style="font-size: 0.80rem; letter-spacing: 0.02em; {{ isset($header['width']) ? 'width: ' . $header['width'] . ';' : '' }}"
                        >
                            {{ $header['label'] ?? '' }}
                        </th>
                    @else
                        <th 
                            class="py-2.5 px-3 fw-bold text-dark"
                            style="font-size: 0.80rem; letter-spacing: 0.02em;"
                        >
                            {{ $header }}
                        </th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody @if($bodyId) id="{{ $bodyId }}" @endif>
            {{ $slot }}
        </tbody>
    </table>
</div>
