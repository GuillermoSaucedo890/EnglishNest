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

    protected $casts = [
        'completado' => 'boolean',
        'fecha_completado' => 'datetime',
        'ultima_visualizacion' => 'datetime',
    ];

    public $incrementing = false;

    protected $primaryKey = null;

    // Laravel no maneja PK compuesta por defecto.
    // Esto evita errores al actualizar progreso_leccion.
    protected function setKeysForSaveQuery($query)
    {
        $query->where('usuario_id', $this->getAttribute('usuario_id'));
        $query->where('leccion_id', $this->getAttribute('leccion_id'));

        return $query;
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function leccion()
    {
        return $this->belongsTo(Leccion::class, 'leccion_id');
    }
}