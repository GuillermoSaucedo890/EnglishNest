<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoporteMensaje extends Model
{
    use HasFactory;

    protected $table = 'soporte_mensajes';

    protected $fillable = [
        'conversacion_id',
        'emisor_tipo',
        'mensaje',
        'fecha_envio',
    ];
}