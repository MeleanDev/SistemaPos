@props([
    'id' => 'datatable_' . uniqid(),
    'headers' => [],
    'withCard' => true,
    'tableClass' => 'table border-0 w-100 align-middle',
    'theadClass' => 'bg-dark text-white text-center',
    'cardClass' => 'card border-0 shadow-sm rounded-4 overflow-hidden',
    'cardBodyClass' => 'card-body p-3 p-md-4',
])

@if($withCard)
    <div class="{{ $cardClass }}">
        <div class="{{ $cardBodyClass }}">
@endif

<div class="table-responsive">
    <table id="{{ $id }}" {{ $attributes->merge(['class' => $tableClass]) }}>
        <thead class="{{ $theadClass }}">
            <tr>
                @if(!empty($headers))
                    @foreach($headers as $header)
                        @if(is_array($header))
                            <th class="{{ $header['class'] ?? '' }}" @if(isset($header['width'])) width="{{ $header['width'] }}" @endif style="{{ $header['style'] ?? '' }}">
                                {!! $header['title'] ?? $header['name'] ?? '' !!}
                            </th>
                        @else
                            <th>{!! $header !!}</th>
                        @endif
                    @endforeach
                @else
                    {{ $thead ?? '' }}
                @endif
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>

@if($withCard)
        </div>
    </div>
@endif
