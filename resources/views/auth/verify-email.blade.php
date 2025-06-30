@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex">
    <!-- Left Branding Section -->
    <div class="w-1/2 bg-[#1a2236] flex flex-col justify-center items-center text-center p-12">
        <h1 class="text-4xl font-bold text-green-400 mb-2">SafeGuardX</h1>
        <h2 class="text-6xl font-extrabold text-white mb-4">3D Folder</h2>
        <h3 class="text-2xl font-bold text-white mb-2">Secure Your Data.<br>Launch It Beyond Threats.</h3>
        <p class="text-gray-400 mt-4">Protecting your digital assets with cutting-edge technology.</p>
    </div>
    <!-- Right Verification Section -->
    <div class="w-1/2 flex flex-col justify-center items-center bg-[#232b3e] p-12">
        <div class="w-full max-w-md">
            <h2 class="text-3xl font-bold text-white mb-4 text-center">VERIFY EMAIL</h2>
            <p class="text-gray-300 text-center mb-8">We've sent a 6-digit code to your email address.<br>Enter the code below to verify your account.</p>
            <form method="POST" action="{{ route('verification.verify') }}" id="otp-form">
                @csrf
                <div class="flex justify-center gap-2 mb-6">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-input w-12 h-12 text-2xl text-center rounded bg-[#1a2236] text-white border border-gray-600 focus:border-green-400 focus:ring-0" autocomplete="one-time-code" required />
                    @endfor
                </div>
                <input type="hidden" name="code" id="otp-merged" />
                <button type="submit" class="w-full py-3 rounded bg-green-600 text-white font-semibold text-lg hover:bg-green-700 transition disabled:opacity-50" id="verify-btn" disabled>Verify Code</button>
            </form>
            <div class="text-center mt-6">
                <span class="text-gray-400">Didn't receive the code?</span><br>
                <form method="POST" action="{{ route('verification.send') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-green-400 hover:underline" id="resend-btn" disabled>Resend code in <span id="resend-timer">24</span>s</button>
                </form>
            </div>
            <div class="text-center mt-6">
                <a href="{{ route('register') }}" class="text-blue-400 hover:underline">&larr; Back to Sign Up</a>
            </div>
        </div>
    </div>
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
    const resendBtn = document.getElementById('resend-btn');
    const resendTimer = document.getElementById('resend-timer');
    resendBtn.disabled = true;
    const interval = setInterval(() => {
        timer--;
        resendTimer.textContent = timer;
        if (timer <= 0) {
            resendBtn.disabled = false;
            resendBtn.textContent = 'Resend code';
            clearInterval(interval);
        }
    }, 1000);
</script>
@endsection
