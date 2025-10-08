<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            <!-- Strength meter -->
            <div class="mt-2">
                <div class="w-full h-2 bg-gray-200 rounded">
                    <div id="upd-pwd-strength-bar" class="h-2 rounded bg-red-500" style="width: 0%"></div>
                </div>
                <div id="upd-pwd-strength-text" class="text-xs mt-1 text-gray-400">Too weak</div>
            </div>
            <!-- Requirements -->
            <ul class="mt-2 text-xs text-gray-400 space-y-1" id="upd-pwd-req-list">
                <li id="upd-req-length" class="flex items-center"><span class="w-4 mr-1">•</span> At least 8 characters</li>
                <li id="upd-req-upper" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 uppercase letter (A-Z)</li>
                <li id="upd-req-lower" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 lowercase letter (a-z)</li>
                <li id="upd-req-digit" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 number (0-9)</li>
                <li id="upd-req-symbol" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 symbol (!@#$…)</li>
            </ul>
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <div id="upd-pwd-match-msg" class="mt-2 text-xs text-gray-400">Re-enter the password to confirm</div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button id="update-password-btn" disabled class="opacity-60 cursor-not-allowed">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pwd = document.getElementById('update_password_password');
            const pwd2 = document.getElementById('update_password_password_confirmation');
            const bar = document.getElementById('upd-pwd-strength-bar');
            const text = document.getElementById('upd-pwd-strength-text');
            const reqs = {
                length: document.getElementById('upd-req-length'),
                upper: document.getElementById('upd-req-upper'),
                lower: document.getElementById('upd-req-lower'),
                digit: document.getElementById('upd-req-digit'),
                symbol: document.getElementById('upd-req-symbol'),
            };
            const btn = document.getElementById('update-password-btn');
            const matchMsg = document.getElementById('upd-pwd-match-msg');

            function scorePassword(v) {
                const tests = {
                    length: v.length >= 8,
                    upper: /[A-Z]/.test(v),
                    lower: /[a-z]/.test(v),
                    digit: /\d/.test(v),
                    symbol: /[^A-Za-z0-9]/.test(v),
                };
                Object.entries(tests).forEach(([k, ok]) => {
                    const el = reqs[k];
                    if (!el) return;
                    el.className = 'flex items-center ' + (ok ? 'text-green-600' : 'text-gray-400');
                });
                return Object.values(tests).filter(Boolean).length; // 0..5
            }

            function updateStrength() {
                const v = pwd.value || '';
                const score = scorePassword(v);
                const pct = (score / 5) * 100;
                bar.style.width = pct + '%';
                let color = 'bg-red-500', label = 'Too weak';
                if (score >= 2) { color = 'bg-yellow-500'; label = 'Weak'; }
                if (score >= 3) { color = 'bg-amber-500'; label = 'Fair'; }
                if (score >= 4) { color = 'bg-green-500'; label = 'Strong'; }
                if (score === 5) { color = 'bg-emerald-600'; label = 'Very strong'; }
                bar.className = 'h-2 rounded ' + color;
                text.textContent = label;
                updateValidity();
            }

            function updateMatch() {
                const a = pwd.value || '';
                const b = pwd2.value || '';
                if (!b) {
                    matchMsg.textContent = 'Re-enter the password to confirm';
                    matchMsg.className = 'mt-2 text-xs text-gray-400';
                } else if (a === b) {
                    matchMsg.textContent = 'Passwords match';
                    matchMsg.className = 'mt-2 text-xs text-green-600';
                } else {
                    matchMsg.textContent = 'Passwords do not match';
                    matchMsg.className = 'mt-2 text-xs text-red-600';
                }
                updateValidity();
            }

            function meetsAll(v) {
                return v.length >= 8 && /[A-Z]/.test(v) && /[a-z]/.test(v) && /\d/.test(v) && /[^A-Za-z0-9]/.test(v);
            }

            function updateValidity() {
                const ok = meetsAll(pwd.value || '') && pwd.value === (pwd2.value || '');
                if (ok) {
                    btn.removeAttribute('disabled');
                    btn.classList.remove('opacity-60','cursor-not-allowed');
                } else {
                    btn.setAttribute('disabled', 'disabled');
                    btn.classList.add('opacity-60','cursor-not-allowed');
                }
            }

            pwd.addEventListener('input', () => { updateStrength(); updateMatch(); });
            pwd2.addEventListener('input', updateMatch);
            updateStrength();
            updateMatch();
        });
        </script>
    </form>
</section>
