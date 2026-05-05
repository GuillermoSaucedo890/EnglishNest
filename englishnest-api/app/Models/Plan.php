<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion_dias',
        'limite_cursos',
        'limite_dispositivos',
        'limite_mensajes_ia_dia',
        'permite_certificado',
        'es_prueba',
        'soporte_24_7',
        'estado',
    ];

    // Un plan puede tener muchas suscripciones
    public function suscripciones()
    {
        return $this->hasMany(Suscripcion::class, 'plan_id');
    }

    // Indica si el plan permite cursos ilimitados
    public function cursosIlimitados(): bool
    {
        return $this->limite_cursos === null;
    }
}