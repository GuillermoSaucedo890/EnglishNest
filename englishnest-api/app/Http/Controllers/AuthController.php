<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Rol;
use App\Models\User;
use App\Models\Suscripcion;
use App\Models\PerfilDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Mostrar formularios
    |--------------------------------------------------------------------------
    */

    // Formulario de login
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    // Formulario de registro de estudiante
    public function mostrarRegistroEstudiante()
    {
        return view('auth.registro-estudiante');
    }

    // Formulario de registro de docente
    public function mostrarRegistroDocente()
    {
        return view('auth.registro-docente');
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de estudiante
    |--------------------------------------------------------------------------
    */
    public function registrarEstudiante(Request $request)
    {
        // Validamos los datos que vienen del formulario
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        // Buscamos el rol estudiante
        $rolEstudiante = Rol::where('nombre', 'estudiante')->first();

        // Buscamos el plan trial
        $planTrial = Plan::where('nombre', 'TRIAL')->first();

        // Transacción = todo o nada
        // Si algo falla, no guarda medias cosas
        $usuario = DB::transaction(function () use ($request, $rolEstudiante, $planTrial) {

            // Creamos el usuario
            $usuario = User::create([
                'rol_id' => $rolEstudiante->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => $request->password,
                'estado' => 'activo',
                'fecha_prueba_usada' => now(),
            ]);

            // Creamos su suscripción trial
            Suscripcion::create([
                'usuario_id' => $usuario->id,
                'plan_id' => $planTrial->id,
                'estado' => 'activa',
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addDays($planTrial->duracion_dias),
                'renovacion_automatica' => false,
            ]);

            return $usuario;
        });

        // Dispara el evento de registro para que Laravel mande el correo de verificación
        event(new Registered($usuario));

        // Inicia sesión automáticamente
        Auth::login($usuario);

        // Lo mandamos a la pantalla que avisa que debe verificar el correo
        return redirect()->route('verification.notice')
            ->with('ok', 'Registro exitoso. Revisa tu correo para verificar tu cuenta.');
    }

    /*
    |--------------------------------------------------------------------------
    | Registro de docente
    |--------------------------------------------------------------------------
    */
    public function registrarDocente(Request $request)
    {
        // Validamos formulario
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'estudios' => ['required', 'string'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'biografia' => ['nullable', 'string'],
        ]);

        // Buscamos el rol docente
        $rolDocente = Rol::where('nombre', 'docente')->first();

        $usuario = DB::transaction(function () use ($request, $rolDocente) {

            // Creamos el usuario
            $usuario = User::create([
                'rol_id' => $rolDocente->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => $request->password,
                'estado' => 'activo',
                'fecha_prueba_usada' => null,
            ]);

            // Creamos el perfil docente
            PerfilDocente::create([
                'usuario_id' => $usuario->id,
                'estudios' => $request->estudios,
                'especialidad' => $request->especialidad,
                'biografia' => $request->biografia,
                'estado_aprobacion' => 'pendiente',
            ]);

            return $usuario;
        });

        // Evento para mandar correo de verificación
        event(new Registered($usuario));

        // Iniciar sesión automático
        Auth::login($usuario);

        return redirect()->route('verification.notice')
            ->with('ok', 'Registro de docente enviado. Revisa tu correo y espera aprobación del administrador.');
    }

    /*
    |--------------------------------------------------------------------------
    | Iniciar sesión
    |--------------------------------------------------------------------------
    */
    public function iniciarSesion(Request $request)
    {
        // Validamos email y contraseña
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentamos autenticar
        if (!Auth::attempt($credenciales, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Correo o contraseña incorrectos.',
            ])->onlyInput('email');
        }

        // Regeneramos sesión por seguridad
        $request->session()->regenerate();

        return redirect()->route('panel');
    }

    /*
    |--------------------------------------------------------------------------
    | Cerrar sesión
    |--------------------------------------------------------------------------
    */
    public function cerrarSesion(Request $request)
    {
        Auth::logout();

        // Invalidamos la sesión actual
        $request->session()->invalidate();

        // Regeneramos el token CSRF
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}