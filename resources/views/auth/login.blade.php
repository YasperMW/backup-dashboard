<x-auth-layout>
    <h1 class="text-white text-4xl font-bold mb-4 text-center md:text-left">WELCOME!</h1>
    <p class="text-gray-400 mb-8 text-center md:text-left">
        Don't have an account?
        <a href="{{ route('register') }}" class="text-blue-500 hover:underline">Sign up</a>
    </p>

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Field -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="deniel123@gmail.com" />
            <x-input-error for="email" class="mt-2" />
        </div>

        <!-- Password Field -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error for="password" class="mt-2" />
        </div>

        <!-- Remember Me & Forget Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-600 rounded" name="remember">
                <span class="ml-2 text-sm text-gray-400">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-blue-500 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Login Button -->
        <x-primary-button>
            {{ __('Log in') }}
        </x-primary-button>
    </form>
</x-auth-layout>
