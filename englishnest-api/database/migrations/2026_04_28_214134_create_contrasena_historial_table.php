<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contrasena_historial', function (Blueprint $table) {
            $table->id();
            // Relación con tu tabla users
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('password_hash'); 
            $table->timestamps(); // Para saber cuándo se usó esa clave
        });
    }

    public function down(): void {
        Schema::dropIfExists('contrasena_historial');
    }
};