<section class="space-y-8">
    <header>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.104.896-2 2-2s2 .896 2 2-.896 2-2 2-2-.896-2-2zm0 0V7m0 4v4m0 0h4m-4 0H8"/></svg>
            {{ __('Two-Factor Authentication') }}
        </h2>
        <p class="mt-2 text-gray-600 dark:text-gray-400 text-base">
            {{ __('Add an extra layer of security to your account by requiring a code from your phone when logging in.') }}
        </p>
    </header>

    @if (session('status'))
        <div class="mb-4 flex items-center gap-2 text-green-600 dark:text-green-400">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span class="font-medium text-sm">
                @if (session('status') == 'two-factor-authentication-enabled')
                    {{ __('Two-factor authentication has been enabled.') }}
                @elseif (session('status') == 'two-factor-authentication-disabled')
                    {{ __('Two-factor authentication has been disabled.') }}
                @elseif (session('status') == 'recovery-codes-generated')
                    {{ __('New recovery codes have been generated.') }}
                @endif
            </span>
        </div>
    @endif

    @if (! Auth::user()->two_factor_secret)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Step 1: Enable Two-Factor Authentication') }}</h3>
            <p class="text-gray-600 dark:text-gray-400">{{ __('Click the button below to start setting up 2FA for your account.') }}</p>
            <form method="POST" action="{{ route('two-factor.enable') }}">
                @csrf
                <x-primary-button type="submit">
                    {{ __('Enable Two-Factor Authentication') }}
                </x-primary-button>
            </form>
        </div>
    @elseif (Auth::user()->two_factor_secret && !Auth::user()->two_factor_confirmed_at)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col gap-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Step 2: Set Up Your Authenticator App') }}</h3>
            <ol class="list-decimal list-inside text-gray-700 dark:text-gray-300 mb-4">
                <li>Open your Google Authenticator or compatible app.</li>
                <li>Scan the QR code below or enter the setup key manually.</li>
                <li>Enter the 6-digit code from your app to confirm setup.</li>
            </ol>
            <div class="flex flex-col items-center gap-2">
                <img src="{{ Auth::user()->getTwoFactorQrCodeUrl() }}" alt="QR Code" class="border rounded p-2 bg-white">
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                    <span class="font-semibold">{{ __('Setup Key:') }}</span>
                    <span class="font-mono text-base">{{ Auth::user()->getTwoFactorSecret() }}</span>
                </div>
            </div>
            <form id="two-factor-confirm-form" method="POST" action="{{ route('settings.two-factor.confirm') }}" class="mt-4 flex flex-col gap-2 max-w-xs">
                @csrf
                <label for="code" class="block text-sm font-medium text-gray-700">{{ __('Enter the 6-digit code from your app:') }}</label>
                <input type="text" name="code" id="code" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required autofocus autocomplete="one-time-code">
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                <div id="two-factor-message" class="mt-2"></div>
                <x-primary-button type="submit" class="mt-2">
                    {{ __('Confirm Two-Factor Authentication') }}
                </x-primary-button>
            </form>
            <script>
const messageDiv = document.getElementById('two-factor-message');
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('two-factor-confirm-form');

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        messageDiv.innerHTML = '';

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                messageDiv.innerHTML = `<span class="text-green-600">${data.message}</span>`;
                // Reload the 2FA section
                fetch('/settings/two-factor/partial')
                    .then(resp => resp.text())
                    .then(html => {
                        document.getElementById('two-factor-section').innerHTML = html;
                    });
            } else {
                messageDiv.innerHTML = `<span class="text-red-600">${data.message || 'An error occurred.'}</span>`;
            }
        })
        .catch(() => {
            messageDiv.innerHTML = `<span class="text-red-600">An error occurred. Please try again.</span>`;
        });
    });
});
</script>
            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-2">
                @csrf
                @method('DELETE')
                <x-danger-button>
                    {{ __('Cancel') }}
                </x-danger-button>
            </form>
        </div>
    @elseif (Auth::user()->two_factor_secret && Auth::user()->two_factor_confirmed_at)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('Two-Factor Authentication is Enabled') }}</h3>
            </div>
            <p class="text-gray-600 dark:text-gray-400">{{ __('You will be required to enter a code from your authenticator app when logging in.') }}</p>
            @if (Auth::user()->two_factor_recovery_codes)
                <div class="mt-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ __('Recovery Codes') }}</h4>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">{{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.') }}</p>
                    <div class="grid gap-1 max-w-xl px-4 py-4 font-mono text-sm bg-gray-100 dark:bg-gray-900 rounded-lg">
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
                </div>
            @endif
            <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-4">
                @csrf
                @method('DELETE')
                <x-danger-button>
                    {{ __('Disable Two-Factor Authentication') }}
                </x-danger-button>
            </form>
        </div>
    @endif
</section> 