<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leccion extends Model
{
    use HasFactory;

    protected $table = 'lecciones';

    protected $fillable = [
        'curso_id',
        'titulo',
        'descripcion',
        'contenido_texto',
        'url_video',
        'orden',
        'tipo',
        'es_gratis',
        'activo',
    ];

    protected $casts = [
        'es_gratis' => 'boolean',
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::created(function (Leccion $leccion) {
            if ($leccion->tipo === 'introduccion') {
                return;
            }

            Evaluacion::firstOrCreate(
                [
                    'leccion_id' => $leccion->id,
                    'tipo' => 'quiz_leccion',
                ],
                [
                    'curso_id' => $leccion->curso_id,
                    'titulo' => 'Cuestionario de la lección',
                    'puntaje_minimo_aprobacion' => 70,
                    'estado' => 'borrador',
                ]
            );
        });
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function evaluacion()
    {
        return $this->hasOne(Evaluacion::class, 'leccion_id')
            ->where('tipo', 'quiz_leccion');
    }

    public function progreso()
    {
        return $this->hasMany(ProgresoLeccion::class, 'leccion_id');
    }
}