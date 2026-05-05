<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CursoEdicion extends Model
{
    use HasFactory;

    protected $table = 'curso_ediciones';

    protected $fillable = [
        'curso_id',
        'creado_por_id',
        'estado',
        'solicita_publicacion',
        'motivo_rechazo',
        'datos_json',
    ];

    protected $casts = [
        'solicita_publicacion' => 'boolean',
        'datos_json' => 'array',
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }
}