<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    protected $fillable = [
        'usuario_id',
        'plan_id',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'renovacion_automatica',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'renovacion_automatica' => 'boolean',
    ];

    // Suscripción pertenece a un plan
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    // Suscripción pertenece a un usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    // Devuelve true si la suscripción está activa y no venció
    public function estaVigente(): bool
    {
        return $this->estado === 'activa' && $this->fecha_fin > now();
    }
}