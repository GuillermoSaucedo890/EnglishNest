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
        'estado',
        'fecha_inscripcion',
    ];
public function curso()
{
    return $this->belongsTo(Curso::class, 'curso_id');
}
    public function estudiante()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
