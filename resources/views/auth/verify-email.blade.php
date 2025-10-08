<x-auth-layout>
    <h1 class="text-white text-3xl font-bold mb-4 text-center md:text-left">Please check your email</h1>
    <p class="text-gray-400 mb-6 text-center md:text-left">
        We've sent a code to <span class="font-semibold">{{ auth()->user()->email ?? 'username@example.com' }}</span>
    </p>
    <div class="flex flex-col items-center">
        <div class="bg-green-400 rounded-full w-16 h-16 flex items-center justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4-4-4 4m8 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6" /></svg>
        </div>
        <form method="POST" action="{{ route('verification.verify') }}" id="otp-form" class="w-full flex flex-col items-center">
            @csrf
            <div class="flex justify-center gap-1 mb-4 px-6 max-w-md mx-auto">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-9 h-10 text-lg text-center rounded-lg bg-[#232b3e] text-black border border-gray-600 focus:border-green-400 focus:ring-0" autocomplete="one-time-code" required />
                @endfor
            </div>
            <input type="hidden" name="code" id="otp-merged" />
            <div class="mb-4 text-center text-sm">
                Didn't get the code? 
                <a href="#" id="resend-link" class="text-blue-300 hover:underline disabled:opacity-50" style="pointer-events: none; opacity: 0.5;">Resend code in <span id="resend-timer">24</span>s</a>
            </div>
            <div class="w-full mt-2 flex items-center justify-center">
                
               
                <button type="submit" class="w-full max-w-md py-2 rounded-lg bg-green-400 text-[#232b3e] font-semibold text-base hover:bg-green-500 transition disabled:opacity-50" id="verify-btn" disabled>Verify Code</button>
            </div>
        </form>
        <br>
        <form action="{{ route('logout') }}" method="POST" class="w-full flex items-center justify-center">
                    @csrf
                    <button type="submit" class="w-full max-w-md py-2 rounded-lg bg-gray-400 text-[#232b3e] font-semibold text-base hover:bg-gray-500 transition">Cancel</button>
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
        // Resend timer logic
        let timer = 24;
        const resendLink = document.getElementById('resend-link');
        const resendTimer = document.getElementById('resend-timer');
        resendLink.style.pointerEvents = 'none';
        resendLink.style.opacity = '0.5';
        let interval = setInterval(() => {
            timer--;
            resendTimer.textContent = timer;
            if (timer <= 0) {
                resendLink.textContent = 'Click to resend';
                resendLink.style.pointerEvents = 'auto';
                resendLink.style.opacity = '1';
                clearInterval(interval);
            }
        }, 1000);
        resendLink.onclick = function(e) {
            if (timer > 0) {
                e.preventDefault();
                return false;
            }
            e.preventDefault();
            resendLink.textContent = 'Resend code in 24s';
            resendLink.style.pointerEvents = 'none';
            resendLink.style.opacity = '0.5';
            timer = 24;
            resendTimer.textContent = timer;
            interval = setInterval(() => {
                timer--;
                resendTimer.textContent = timer;
                if (timer <= 0) {
                    resendLink.textContent = 'Click to resend';
                    resendLink.style.pointerEvents = 'auto';
                    resendLink.style.opacity = '1';
                    clearInterval(interval);
                }
            }, 1000);
            document.getElementById('otp-form').insertAdjacentHTML('afterend', `<form id=\"resend-form\" method=\"POST\" action=\"{{ route('verification.send') }}\">@csrf</form>`);
            document.getElementById('resend-form').submit();
        };
    </script>
</x-auth-layout>
