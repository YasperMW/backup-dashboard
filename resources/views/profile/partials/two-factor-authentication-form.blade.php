<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Two Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Add additional security to your account using two factor authentication.') }}
        </p>
    </header>

    @if (session('status') == 'two-factor-authentication-enabled')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('Two factor authentication has been enabled.') }}
        </div>
    @endif

    @if (session('status') == 'two-factor-authentication-disabled')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('Two factor authentication has been disabled.') }}
        </div>
    @endif

    @if (session('status') == 'recovery-codes-generated')
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ __('New recovery codes have been generated.') }}
        </div>
    @endif

    @if (! Auth::user()->two_factor_secret)
        <form method="POST" action="{{ route('two-factor.enable') }}">
            @csrf
            <x-primary-button type="submit">
                {{ __('Enable Two Factor Authentication') }}
            </x-primary-button>
        </form>
    @else
        <form method="POST" action="{{ route('two-factor.disable') }}">
            @csrf
            @method('DELETE')
            <x-danger-button>
                {{ __('Disable Two Factor Authentication') }}
            </x-danger-button>
        </form>

        @if (Auth::user()->two_factor_secret && ! empty(json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true)))
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mt-4">
                {{ __('Recovery Codes') }}
            </h3>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two factor authentication device is lost.') }}
            </p>

            <div class="grid gap-1 max-w-xl mt-4 px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-gray-900 rounded-lg">
                @foreach (json_decode(decrypt(Auth::user()->two_factor_recovery_codes), true) as $code)
                    <div>{{ $code }}</div>
                @endforeach
            </div>

            <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-4">
                @csrf
                <x-secondary-button>
                    {{ __('Regenerate Recovery Codes') }}
                </x-secondary-button>
            </form>
        @endif
    @endif

    @if (Auth::user()->two_factor_secret)
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mt-4">
            {{ __('Enable Two Factor Authentication') }}
        </h3>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
        </p>

        <div class="mt-4">
            {!! Auth::user()->twoFactorQrCodeSvg() !!}
        </div>
    @endif
</section> 