<x-guest>
    <div class="auth-form-header">
        <div class="welcome-icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <h2>Crear cuenta</h2>
        <p>Completa tus datos para registrarte</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="input-group">
            <label for="name">Nombre Completo</label>
            <div class="input-wrap">
                <i class="fas fa-user input-icon"></i>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            </div>
            @error('name')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- DNI -->
        <div class="input-group">
            <label for="dni">DNI</label>
            <div class="input-wrap">
                <i class="fas fa-id-card input-icon"></i>
                <input id="dni" type="text" name="dni" value="{{ old('dni') }}" maxlength="8" pattern="[0-9]{8}" placeholder="12345678">
            </div>
            @error('dni')
                <span class="field-error">{{ $message }}</span>
            @enderror
            <p class="hint">Opcional — 8 dígitos</p>
        </div>

        <!-- Teléfono -->
        <div class="input-group">
            <label for="telefono">Teléfono</label>
            <div class="input-wrap">
                <i class="fas fa-phone input-icon"></i>
                <input id="telefono" type="text" name="telefono" value="{{ old('telefono') }}" maxlength="15" placeholder="987654321">
            </div>
            @error('telefono')
                <span class="field-error">{{ $message }}</span>
            @enderror
            <p class="hint">Opcional</p>
        </div>

        <!-- Email Address -->
        <div class="input-group">
            <label for="email">Correo Electrónico</label>
            <div class="input-wrap">
                <i class="fas fa-envelope input-icon"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nombre@empresa.com" required autocomplete="username">
            </div>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Role -->
        <div class="input-group">
            <label for="role_id">Rol</label>
            <div class="input-wrap">
                <i class="fas fa-user-tag input-icon"></i>
                <select id="role_id" name="role_id" required>
                    <option value="">Seleccione un rol</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('role_id')
                <span class="field-error">{{ $message }}</span>
            @enderror
            <p class="hint">Selecciona tu rol en el sistema</p>
        </div>

        <!-- Password -->
        <div class="input-group" x-data="{ mostrar: false }">
            <label for="password">Contraseña</label>
            <div class="input-wrap">
                <i class="fas fa-key input-icon"></i>
                <input id="password" :type="mostrar ? 'text' : 'password'" name="password" required autocomplete="new-password">
                <button type="button" @click="mostrar = !mostrar" class="toggle-pw" tabindex="-1">
                    <i :class="mostrar ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
            </div>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="input-group" x-data="{ mostrar: false }">
            <label for="password_confirmation">Confirmar Contraseña</label>
            <div class="input-wrap">
                <i class="fas fa-key input-icon"></i>
                <input id="password_confirmation" :type="mostrar ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password">
                <button type="button" @click="mostrar = !mostrar" class="toggle-pw" tabindex="-1">
                    <i :class="mostrar ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                </button>
            </div>
            @error('password_confirmation')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <!-- Submit -->
        <button type="submit" class="auth-btn">
            <i class="fas fa-user-plus"></i>
            Registrarse
        </button>

        <!-- Divider + Login -->
        <div class="auth-divider"><span>o</span></div>
        <div class="auth-switch-row">
            <span>¿Ya tienes cuenta? </span>
            <a href="{{ route('login') }}">Iniciar sesión</a>
        </div>
    </form>
</x-guest>
