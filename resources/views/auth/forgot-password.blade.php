<x-guest>
    <div class="mb-6 text-center">
        <div class="mx-auto w-16 h-16 bg-indigo-100 dark:bg-indigo-900/40 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-key text-indigo-600 dark:text-indigo-400 text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200 mb-2">¿Olvidaste tu contraseña?</h2>
        <p class="text-sm text-gray-600 dark:text-slate-400">
            No hay problema. Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900/40 border border-green-400 text-green-700 dark:text-green-300 rounded-md">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
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
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 dark:text-slate-400 hover:text-gray-900 dark:hover:text-slate-100 underline">
                <i class="fas fa-arrow-left mr-1"></i>
                Volver al login
            </a>

            <x-primary-button>
                <i class="fas fa-paper-plane mr-2"></i>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 p-3 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-md">
        <div class="flex">
            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2"></i>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">
                <strong>Nota:</strong> Solo el administrador del sistema puede crear nuevas cuentas. Si no tienes cuenta, contacta al administrador.
            </p>
        </div>
    </div>
</x-guest>