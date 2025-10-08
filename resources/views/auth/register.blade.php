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
                <!-- Strength meter -->
                <div class="mt-2">
                    <div class="w-full h-2 bg-gray-200 rounded">
                        <div id="pwd-strength-bar" class="h-2 rounded bg-red-500" style="width: 0%"></div>
                    </div>
                    <div id="pwd-strength-text" class="text-xs mt-1 text-gray-400">Too weak</div>
                </div>
                <!-- Requirements -->
                <ul class="mt-2 text-xs text-gray-400 space-y-1" id="pwd-req-list">
                    <li id="req-length" class="flex items-center"><span class="w-4 mr-1">•</span> At least 8 characters</li>
                    <li id="req-upper" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 uppercase letter (A-Z)</li>
                    <li id="req-lower" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 lowercase letter (a-z)</li>
                    <li id="req-digit" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 number (0-9)</li>
                    <li id="req-symbol" class="flex items-center"><span class="w-4 mr-1">•</span> At least 1 symbol (!@#$…)</li>
                </ul>
            </div>

            <!-- Confirm Password Field -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                <div id="pwd-match-msg" class="mt-2 text-xs text-gray-400">Re-enter the password to confirm</div>
                <x-input-error for="password_confirmation" class="mt-2" />
            </div>
        </div>

        <!-- Register Button -->
        <div class="flex justify-center md:justify-start">
            <x-primary-button id="register-btn" disabled class="opacity-60 cursor-not-allowed">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const pwd = document.getElementById('password');
        const pwd2 = document.getElementById('password_confirmation');
        const bar = document.getElementById('pwd-strength-bar');
        const text = document.getElementById('pwd-strength-text');
        const reqs = {
            length: document.getElementById('req-length'),
            upper: document.getElementById('req-upper'),
            lower: document.getElementById('req-lower'),
            digit: document.getElementById('req-digit'),
            symbol: document.getElementById('req-symbol'),
        };
        const btn = document.getElementById('register-btn');
        const matchMsg = document.getElementById('pwd-match-msg');

        function scorePassword(v) {
            let score = 0;
            const tests = {
                length: v.length >= 8,
                upper: /[A-Z]/.test(v),
                lower: /[a-z]/.test(v),
                digit: /\d/.test(v),
                symbol: /[^A-Za-z0-9]/.test(v),
            };
            // Update checklist colors
            Object.entries(tests).forEach(([k, ok]) => {
                const el = reqs[k];
                if (!el) return;
                el.className = 'flex items-center ' + (ok ? 'text-green-600' : 'text-gray-400');
            });
            score = Object.values(tests).filter(Boolean).length; // 0..5
            return { score, tests };
        }

        function updateStrength() {
            const v = pwd.value || '';
            const { score } = scorePassword(v);
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
            const t = {
                length: v.length >= 8,
                upper: /[A-Z]/.test(v),
                lower: /[a-z]/.test(v),
                digit: /\d/.test(v),
                symbol: /[^A-Za-z0-9]/.test(v),
            };
            return Object.values(t).every(Boolean);
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
</x-auth-layout>
