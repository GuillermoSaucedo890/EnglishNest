<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected $fillable = [
        'usuario_id',
        'curso_id',
        'suscripcion_id',
        'estado',
        'progreso_porcentaje',
        'nota_cuestionarios',
        'nota_examen_final',
        'nota_final',
        'aprobado',
        'certificado_disponible',
        'fecha_inscripcion',
        'fecha_cancelacion',
        'fecha_completado',
        'fecha_reprobacion',
    ];

    protected $casts = [
        'aprobado' => 'boolean',
        'certificado_disponible' => 'boolean',
        'progreso_porcentaje' => 'decimal:2',
        'nota_cuestionarios' => 'decimal:2',
        'nota_examen_final' => 'decimal:2',
        'nota_final' => 'decimal:2',
        'fecha_inscripcion' => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'fecha_completado' => 'datetime',
        'fecha_reprobacion' => 'datetime',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function suscripcion()
    {
        return $this->belongsTo(Suscripcion::class, 'suscripcion_id');
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activa';
    }

    public function estaCompletada(): bool
    {
        return $this->estado === 'completada';
    }

    public function estaBloqueadaPorReprobacion(): bool
    {
        return in_array($this->estado, ['bloqueada', 'reprobada'], true);
    }
}