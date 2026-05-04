<?php

namespace App\Models;

// Esto le dice a Laravel que el usuario debe verificar su correo
use Illuminate\Contracts\Auth\MustVerifyEmail;

// Trait para factories
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Clase base del usuario autenticable
use Illuminate\Foundation\Auth\User as Authenticatable;

// Trait para notificaciones
use Illuminate\Notifications\Notifiable;

// Trait para tokens de Sanctum
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /*
    |--------------------------------------------------------------------------
    | Traits que usará el modelo
    |--------------------------------------------------------------------------
    |
    | HasApiTokens = permite tokens para API
    | HasFactory   = permite factories
    | Notifiable   = permite notificaciones, como verificar correo
    */
    use HasApiTokens, HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Nombre real de la tabla
    |--------------------------------------------------------------------------
    |
    | La tabla de Laravel se llama users, no usuario.
    */
    protected $table = 'users';

    /*
    |--------------------------------------------------------------------------
    | Campos permitidos para asignación masiva
    |--------------------------------------------------------------------------
    |
    | Son los campos que puedes llenar desde formularios.
    */
    protected $fillable = [
        'rol_id',
        'nombres',
        'apellidos',
        'email',
        'password',
        'estado',
        'fecha_prueba_usada',
    ];

    /*
    |--------------------------------------------------------------------------
    | Campos que no quiero mostrar fácilmente
    |--------------------------------------------------------------------------
    */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Conversión automática de tipos
    |--------------------------------------------------------------------------
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_prueba_usada' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Un usuario pertenece a un rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    // Un usuario puede tener un perfil docente
    public function perfilDocente()
    {
        return $this->hasOne(PerfilDocente::class, 'usuario_id');
    }
}