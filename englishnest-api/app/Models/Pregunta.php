<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    use HasFactory;

    protected $table = 'preguntas';

    protected $fillable = [
        'evaluacion_id',
        'pregunta',
        'orden',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    public function opciones()
    {
        return $this->hasMany(OpcionPregunta::class, 'pregunta_id')->orderBy('orden', 'asc');
    }
}