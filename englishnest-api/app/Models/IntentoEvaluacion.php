<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentoEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'intentos_evaluacion';

    protected $fillable = [
        'usuario_id',
        'evaluacion_id',
        'intento_numero',
        'puntaje',
        'porcentaje',
        'aprobado',
        'respuestas_json',
        'fecha_intento',
    ];

    protected $casts = [
        'aprobado' => 'boolean',
        'respuestas_json' => 'array',
        'fecha_intento' => 'datetime',
        'puntaje' => 'decimal:2',
        'porcentaje' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }
}