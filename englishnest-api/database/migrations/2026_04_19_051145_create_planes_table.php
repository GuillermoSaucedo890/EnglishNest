<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea los planes del sistema
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->id();

            // Nombre del plan: TRIAL, BASICO, INTERMEDIO, PREMIUM
            $table->string('nombre', 30)->unique();

            // Información visual del plan
            $table->string('descripcion', 255)->nullable();

            // Precio del plan
            $table->decimal('precio', 10, 2)->default(0);

            // Duración del plan en días
            $table->integer('duracion_dias');

            // Cuántos cursos activos puede tener el estudiante
            // null = ilimitado
            $table->integer('limite_cursos')->nullable();

            // Cuántos dispositivos puede tener activos
            $table->integer('limite_dispositivos')->default(1);

            // Cantidad de mensajes permitidos al tutor IA por día
            // null = ilimitado
            $table->integer('limite_mensajes_ia_dia')->nullable();

            // Si permite descargar certificado
            $table->boolean('permite_certificado')->default(false);

            // Si es plan de prueba
            $table->boolean('es_prueba')->default(false);

            // Si tiene soporte 24/7
            $table->boolean('soporte_24_7')->default(false);

            // Estado del plan
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            $table->timestamps();
        });
    }

    // Borra la tabla si hacemos rollback
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};