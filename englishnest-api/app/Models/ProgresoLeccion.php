<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgresoLeccion extends Model
{
    use HasFactory;

    protected $table = 'progreso_leccion';

    protected $fillable = [
        'usuario_id',
        'leccion_id',
        'completado',
        'fecha_completado',
        'ultima_visualizacion',
    ];

    public $incrementing = false;

    protected $primaryKey = null;
}
