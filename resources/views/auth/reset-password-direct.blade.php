<x-guest>
    <div class="mb-6 text-center">
        <div class="mx-auto w-16 h-16 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-key text-indigo-600 dark:text-indigo-400 text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-2">Restablecer Contraseña</h2>
        <p class="text-sm text-gray-600 dark:text-slate-400">
            Ingresa tu correo y tu nueva contraseña para restablecer el acceso a tu cuenta.
        </p>
    </div>

    <form method="POST" action="{{ route('password.update-direct') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo Electrónico" />
            <x-text-input 
                id="email" 
                class="block mt-1 w-full" 
                type="email" 
                name="email" 
                :value="old('email')" 
                required 
                autofocus 
                placeholder="tu@correo.com"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Ingresa el correo de tu cuenta</p>
        </div>

        <!-- New Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Nueva Contraseña" />
            <div class="relative">
                <x-text-input 
                    id="password" 
                    class="block mt-1 w-full pr-10" 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="••••••••"
                />
                <button 
                    type="button" 
                    onclick="togglePassword('password', 'toggleIcon1')" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300"
                >
                    <i class="fas fa-eye" id="toggleIcon1"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmar Nueva Contraseña" />
            <div class="relative">
                <x-text-input 
                    id="password_confirmation" 
                    class="block mt-1 w-full pr-10" 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    placeholder="••••••••"
                />
                <button 
                    type="button" 
                    onclick="togglePassword('password_confirmation', 'toggleIcon2')" 
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300"
                >
                    <i class="fas fa-eye" id="toggleIcon2"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Info -->
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-md">
            <div class="flex">
                <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                <p class="text-xs text-blue-700 dark:text-blue-300">
                    La contraseña debe tener al menos 8 caracteres.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 underline">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver al login
            </a>

            <x-primary-button>
                <i class="fas fa-check mr-2"></i>
                Restablecer contraseña
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <div class="flex">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2"></i>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">
                <strong>Nota:</strong> Solo puedes cambiar la contraseña de cuentas existentes. Si no tienes cuenta, contacta al administrador.
            </p>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</x-guest>