<x-auth-layout>
    <h1 class="text-white text-3xl font-bold mb-4 text-center md:text-left">Google Authenticator Verification</h1>
    <p class="text-gray-400 mb-6 text-center md:text-left">
        Please enter the 6-digit code from your Google Authenticator app to continue.
    </p>
    @if (session('status'))
        <div class="mb-4 text-green-500 font-semibold text-center">
            {{ session('status') }}
        </div>
    @endif
    <div class="flex flex-col items-center">
        <div class="bg-green-400 rounded-full w-16 h-16 flex items-center justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4-4-4 4m8 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6" /></svg>
        </div>
        <form method="POST" action="{{ route('two-factor.verify') }}" id="otp-form" class="w-full flex flex-col items-center">
            @csrf
            <div class="flex justify-center gap-1 mb-4 px-6 max-w-md mx-auto">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-9 h-10 text-lg text-center rounded-lg bg-[#232b3e] text-black border border-gray-600 focus:border-green-400 focus:ring-0" autocomplete="one-time-code" required />
                @endfor
            </div>
            <input type="hidden" name="code" id="otp-merged" />
            <x-input-error :messages="$errors->get('code')" class="mb-4" />
            <div class="flex w-full justify-between gap-4 mt-2">
                <button type="button" onclick="window.location='{{ route('logout') }}'" class="flex-1 py-2 rounded-lg bg-gray-400 text-[#232b3e] font-semibold text-base hover:bg-gray-500 transition">Cancel</button>
                <button type="submit" class="flex-1 py-2 rounded-lg bg-green-400 text-[#232b3e] font-semibold text-base hover:bg-green-500 transition disabled:opacity-50" id="verify-btn" disabled>Verify Code</button>
            </div>
        </form>
    </div>
    <script>
        // OTP input logic
        const inputs = document.querySelectorAll('.otp-input');
        const merged = document.getElementById('otp-merged');
        const verifyBtn = document.getElementById('verify-btn');
        inputs.forEach((input, idx) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
                updateMerged();
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    inputs[idx - 1].focus();
                }
            });
        });
        function updateMerged() {
            let code = '';
            inputs.forEach(input => code += input.value);
            merged.value = code;
            verifyBtn.disabled = code.length !== 6;
        }
    </script>
</x-auth-layout> 