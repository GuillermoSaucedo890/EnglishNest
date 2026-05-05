<?php

namespace App\Models;

// Trait para factories
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Clase base de modelo normal
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    // Tabla asociada
    protected $table = 'roles';

    // Campos que se pueden llenar
    protected $fillable = [
        'nombre',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Un rol tiene muchos usuarios
    // Ejemplo: el rol "estudiante" puede tener muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }
}