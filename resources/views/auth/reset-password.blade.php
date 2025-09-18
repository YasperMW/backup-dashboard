<x-auth-layout>
    <h1 class="text-white text-3xl font-bold mb-4 text-center md:text-left">Reset Your Password</h1>
    <p class="text-gray-400 mb-6 text-center md:text-left">
        Please create a new password for your account.
    </p>

    <x-validation-errors class="mb-4 text-red-400 text-sm text-center" />

    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600 text-center">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex flex-col items-center">
        <div class="bg-green-400 rounded-full w-16 h-16 flex items-center justify-center mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="w-full flex flex-col items-center space-y-6">
            @csrf

            <!-- Email Address -->
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="token" value="{{ $token }}">

            <!-- Password -->
            <div class="w-full max-w-md">
                <label for="password" class="block text-sm font-medium text-white mb-2 text-center md:text-left">New Password</label>
                <input 
                    id="password" 
                    class="block w-full h-10 text-lg text-center md:text-left rounded-lg bg-[#232b3e] text-black border border-gray-600 focus:border-green-400 focus:ring-0 px-3" 
                    type="password" 
                    name="password" 
                    required 
                    autocomplete="new-password" 
                    placeholder="••••••••"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-400 text-sm text-center md:text-left" />
            </div>

            <!-- Confirm Password -->
            <div class="w-full max-w-md">
                <label for="password_confirmation" class="block text-sm font-medium text-white mb-2 text-center md:text-left">Confirm Password</label>
                <input 
                    id="password_confirmation" 
                    class="block w-full h-10 text-lg text-center md:text-left rounded-lg bg-[#232b3e] text-black border border-gray-600 focus:border-green-400 focus:ring-0 px-3" 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-400 text-sm text-center md:text-left" />
            </div>

            <div class="flex w-full justify-between gap-4 mt-2 max-w-md">
                <form action="{{ route('login') }}" method="GET" style="display:inline;">
                    <button type="submit" class="flex-1 py-2 rounded-lg bg-gray-400 text-[#232b3e] font-semibold text-base hover:bg-gray-500 transition">Cancel</button>
                </form>
                <button type="submit" class="flex-1 py-2 rounded-lg bg-green-400 text-[#232b3e] font-semibold text-base hover:bg-green-500 transition">Reset Password</button>
            </div>
        </form>
    </div>
</x-auth-layout>