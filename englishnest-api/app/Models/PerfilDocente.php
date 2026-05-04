<?php

namespace App\Models;

// Trait para factories
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Clase base de modelo normal
use Illuminate\Database\Eloquent\Model;

class PerfilDocente extends Model
{
    use HasFactory;

    // Nombre de tabla
    protected $table = 'perfiles_docentes';

    // Campos permitidos para llenar
    protected $fillable = [
        'usuario_id',
        'estudios',
        'especialidad',
        'biografia',
        'estado_aprobacion',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Este perfil docente pertenece a un usuario
    // Ejemplo: el perfil docente de Juan pertenece al user Juan
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}