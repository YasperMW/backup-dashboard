<button {{ $attributes->merge(['type' => 'submit', 'class' => 'w-full bg-green-600 text-white py-3 rounded-lg font-semibold hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition duration-200 shadow-lg']) }}>
    {{ $slot }}
</button>
