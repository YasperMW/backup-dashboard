@props(['messages' => null, 'for' => null])
@php
    $messages = $messages ?? ($for ? $errors->get($for) : []);
@endphp
@if ($messages && count($messages))
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 mt-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif 