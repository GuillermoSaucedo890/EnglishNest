<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContrasenaHistorial extends Model {
    protected $table = 'contrasena_historial';
    protected $fillable = ['usuario_id', 'password_hash'];

    public function usuario() {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}