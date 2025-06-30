@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-gray-300 text-sm font-medium mb-2']) }}>
    {{ $value ?? $slot }}
</label>
