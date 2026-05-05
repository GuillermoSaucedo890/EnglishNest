<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilDocente extends Model
{
    use HasFactory;

    protected $table = 'perfiles_docentes';

    protected $fillable = [
        'usuario_id',
        'estudios',
        'especialidad',
        'biografia',
        'estado_aprobacion',
        'motivo_rechazo',

        // Cambios posteriores cuando el docente ya fue aprobado.
        'cambios_pendientes_json',
        'estado_revision_cambios',
        'motivo_rechazo_cambios',
        'fecha_solicitud_cambios',
    ];

    protected $casts = [
        'cambios_pendientes_json' => 'array',
        'fecha_solicitud_cambios' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}