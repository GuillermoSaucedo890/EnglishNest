<?php

namespace App\Models;

// Trait para factories
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Clase base de modelo
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    // Nombre exacto de la tabla en la base de datos
    protected $table = 'planes';

    // Campos permitidos para inserción masiva
    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'duracion_dias',
        'limite_cursos_mes',
        'acceso_total',
        'es_prueba',
        'soporte_24_7',
        'estado',
    ];
}