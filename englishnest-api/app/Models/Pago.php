<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'usuario_id',
        'suscripcion_id',
        'monto',
        'proveedor',
        'referencia_externa',
        'estado',
        'fecha_pago',
    ];
}