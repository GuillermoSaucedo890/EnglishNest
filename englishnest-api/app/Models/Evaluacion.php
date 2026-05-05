<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'curso_id',
        'leccion_id',
        'titulo',
        'tipo',
        'puntaje_minimo_aprobacion',
        'estado',
    ];

    protected $casts = [
        'puntaje_minimo_aprobacion' => 'decimal:2',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function leccion()
    {
        return $this->belongsTo(Leccion::class, 'leccion_id');
    }

    public function preguntas()
    {
        return $this->hasMany(Pregunta::class, 'evaluacion_id')->orderBy('orden', 'asc');
    }

    public function intentos()
    {
        return $this->hasMany(IntentoEvaluacion::class, 'evaluacion_id');
    }

    public function esExamenFinal(): bool
    {
        return $this->tipo === 'examen_final';
    }

    public function esQuizLeccion(): bool
    {
        return $this->tipo === 'quiz_leccion';
    }
}