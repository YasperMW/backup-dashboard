<x-auth-layout>
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ session('status') }}
        </div>
    @endif
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
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password Field -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me & Forget Password -->
        <div class="flex items-center justify-between">
            

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
