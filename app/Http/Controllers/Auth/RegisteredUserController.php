<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        // El autorregistro (vía contraseña maestra) no puede crear cuentas de
        // Administrador — ese rol solo se asigna desde Usuarios (autenticado
        // como admin). Evita que cualquiera con la contraseña maestra se
        // autoasigne acceso total al sistema.
        $roles = Role::where('nombre', '!=', 'Administrador')->get();

        return view('auth.register', compact('roles'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'dni' => ['nullable', 'string', 'size:8', 'unique:'.User::class],
            'telefono' => ['nullable', 'string', 'max:15'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.unique' => 'Este correo ya está registrado.',
            'dni.size' => 'El DNI debe tener 8 dígitos.',
            'dni.unique' => 'Este DNI ya está registrado.',
            'role_id.required' => 'Debe seleccionar un rol.',
            'role_id.exists' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        // Defensa en profundidad: aunque el formulario ya no ofrece
        // "Administrador", un POST directo con ese role_id debe rechazarse.
        // Cuentas de Administrador solo se crean autenticado, desde Usuarios.
        $rolSolicitado = Role::find($request->role_id);
        if ($rolSolicitado && $rolSolicitado->nombre === 'Administrador') {
            return back()->withErrors([
                'role_id' => 'No se puede crear una cuenta de Administrador desde el autorregistro.',
            ])->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'dni' => $request->dni,
            'telefono' => $request->telefono,
            'role_id' => $request->role_id,
            'password' => Hash::make($request->password),
            'estado' => 'activo',
        ]);

        event(new Registered($user));

        // Obtener el nombre del rol para el mensaje
        $roleName = $user->role->nombre;

        // Limpiar la verificación de contraseña maestra de la sesión
        session()->forget('master_password_verified');

        // NO iniciar sesión automáticamente
        // En su lugar, redirigir al login con mensaje de éxito
        return redirect()->route('login')->with('status', "¡Registro exitoso! Tu cuenta como {$roleName} ha sido creada. Ahora puedes iniciar sesión.");
    }
}