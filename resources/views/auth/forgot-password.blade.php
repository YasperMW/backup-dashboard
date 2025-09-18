<x-auth-layout>
    <h1 class="text-white text-3xl font-bold mb-4 text-center md:text-left">Forgot Your Password?</h1>
    <p class="text-gray-400 mb-6 text-center md:text-left">
        Enter your email address and we’ll send you a one-time verification code to reset your password.
    </p>

    @session('status')
        <div class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ $value }}
        </div>
    @endsession

    <x-validation-errors class="mb-4 text-red-400 text-sm text-center" />

    <div class="flex flex-col items-center">
        <div class="bg-green-400 rounded-full w-16 h-16 flex items-center justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12l-4-4-4 4m8 0v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16 0V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6" />
            </svg>
        </div>

        <form method="POST" action="{{ route('password.request') }}" class="w-full flex flex-col items-center space-y-6">
            @csrf

            <div class="w-full max-w-md">
                <label for="email" class="block text-sm font-medium text-white mb-2 text-center md:text-left">Email</label>
                <input 
                    id="email" 
                    class="block w-full h-10 text-lg text-center md:text-left rounded-lg bg-[#232b3e] text-black border border-gray-600 focus:border-green-400 focus:ring-0 px-3" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autofocus 
                    autocomplete="username"
                    placeholder="Enter your email"
                />
                <x-validation-errors :messages="$errors->get('email')" class="mt-2 text-red-400 text-sm text-center md:text-left" />
            </div>

            <div class="flex w-full justify-between gap-4 mt-2 max-w-md">
                <form action="{{ route('login') }}" method="GET" style="display:inline;">
                    <button type="submit" class="flex-1 py-2 rounded-lg bg-gray-400 text-[#232b3e] font-semibold text-base hover:bg-gray-500 transition">Cancel</button>
                </form>
                <button type="submit" class="flex-1 py-2 rounded-lg bg-green-400 text-[#232b3e] font-semibold text-base hover:bg-green-500 transition">Send Verification Code</button>
            </div>
        </form>
    </div>
</x-auth-layout>