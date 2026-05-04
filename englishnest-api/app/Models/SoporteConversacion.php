<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoporteConversacion extends Model
{
    use HasFactory;

    protected $table = 'soporte_conversaciones';

    protected $fillable = [
        'usuario_id',
        'atendido_por',
        'estado',
        'cerrada_en',
    ];
}