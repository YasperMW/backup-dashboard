<x-auth-layout>
    <h1 class="text-white text-4xl font-bold mb-4 text-center md:text-left">CREATE ACCOUNT</h1>
    <p class="text-gray-400 mb-8 text-center md:text-left">
        Already have an account?
        <a href="{{ route('login') }}" class="text-blue-500 hover:underline">Sign in</a>
    </p>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Firstname Field -->
            <div>
                <x-input-label for="firstname" :value="__('First Name')" />
                <x-text-input id="firstname" type="text" name="firstname" :value="old('firstname')" required autofocus autocomplete="given-name" placeholder="John" />
                <x-input-error for="firstname" class="mt-2" />
            </div>
            <!-- Lastname Field -->
            <div>
                <x-input-label for="lastname" :value="__('Last Name')" />
                <x-text-input id="lastname" type="text" name="lastname" :value="old('lastname')" required autocomplete="family-name" placeholder="Doe" />
                <x-input-error for="lastname" class="mt-2" />
            </div>

            <!-- Email Field -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="john@example.com" />
                <x-input-error for="email" class="mt-2" />
            </div>

            <!-- Password Field -->
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                <x-input-error for="password" class="mt-2" />
            </div>

            <!-- Confirm Password Field -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                <x-input-error for="password_confirmation" class="mt-2" />
            </div>
        </div>

        <!-- Register Button -->
        <div class="flex justify-center md:justify-start">
            <x-primary-button>
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-auth-layout>
