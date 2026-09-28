<x-guest>
    <!-- Session Status / Success Messages -->
    @if (session('status'))
        <div class="auth-alert">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <div class="auth-form-header">
        <div class="welcome-icon">
            <i class="fas fa-lock"></i>
        </div>
        <h2>Bienvenido de vuelta</h2>
        <p>Ingresa tus credenciales para continuar</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div class="input-group">
            <label for="email">Correo Electrónico</label>
            <div class="input-wrap">
                <i class="fas fa-envelope input-icon"></i>
                <input id="email" type="email" name="email"
                       value="{{ old('email') }}"
                       placeholder="nombre@empresa.com"
                       required autofocus autocomplete="username">
            </div>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div class="input-group" x-data="{ mostrar: false }">
            <label for="password">Contraseña</label>
            <div class="input-wrap">
                <i class="fas fa-key input-icon"></i>
                <input id="password"
                       :type="mostrar ? 'text' : 'password'"
                       name="password"
                       placeholder="••••••••"
                       required autocomplete="current-password">
                <button type="button" @click="mostrar = !mostrar" class="toggle-pw" tabindex="-1">
                    <i :class="mostrar ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
            </div>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Remember / Forgot -->
        <div class="extras-row">
            <label class="remember-check">
                <input id="remember_me" type="checkbox" name="remember">
                <span>Recordarme</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit" class="auth-btn">
            <i class="fas fa-arrow-right-to-bracket"></i>
            Iniciar Sesión
        </button>

        <!-- Divider + Register -->
        <div class="auth-divider"><span>o</span></div>
        <div class="auth-switch-row">
            <span>¿No tienes cuenta? </span>
            <a href="{{ route('register') }}">Crear cuenta</a>
        </div>
    </form>
</x-guest>
