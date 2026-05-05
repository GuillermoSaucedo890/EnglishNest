<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'nivel',
        'estado',
        'solicita_publicacion',
        'motivo_rechazo',
        'precio_referencia',
        'promedio_calificacion',
        'cantidad_calificaciones',
        'fecha_publicacion',
        'docente_id',
        'area_id',
    ];

    protected $casts = [
        'solicita_publicacion' => 'boolean',
        'fecha_publicacion' => 'datetime',
        'promedio_calificacion' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Curso $curso) {
            Evaluacion::firstOrCreate(
                [
                    'curso_id' => $curso->id,
                    'tipo' => 'examen_final',
                ],
                [
                    'leccion_id' => null,
                    'titulo' => 'Evaluación final',
                    'puntaje_minimo_aprobacion' => 70,
                    'estado' => 'borrador',
                ]
            );
        });
    }

    public function docente()
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function lecciones()
    {
        return $this->hasMany(Leccion::class, 'curso_id')->orderBy('orden', 'asc');
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class, 'curso_id');
    }

    public function evaluacionFinal()
    {
        return $this->hasOne(Evaluacion::class, 'curso_id')
            ->where('tipo', 'examen_final');
    }

    public function ediciones()
    {
        return $this->hasMany(CursoEdicion::class, 'curso_id');
    }

    public function edicionActiva()
    {
        return $this->hasOne(CursoEdicion::class, 'curso_id')
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latestOfMany();
    }

    public function estaPublicado(): bool
    {
        return $this->estado === 'publicado';
    }
}