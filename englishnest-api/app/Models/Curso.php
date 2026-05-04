<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'docente_id',
        'area_id',
        'titulo',
        'descripcion',
        'nivel',
        'estado',
        'precio_referencia',
        'fecha_publicacion',
    ];
}