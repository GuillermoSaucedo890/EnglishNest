<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla planes
|--------------------------------------------------------------------------
| Aquí guardaremos los planes del sistema:
| - TRIAL
| - BASICO
| - PRO
| - PREMIUM
|
| Cada plan define:
| - precio
| - duración
| - límite de cursos
| - si es prueba
| - si tiene acceso total
| - si incluye soporte 24/7
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla planes
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            // Clave primaria
            $table->id();

            // Nombre del plan
            // Ejemplo: TRIAL, BASICO, PRO, PREMIUM
            $table->string('nombre', 30)->unique();

            // Descripción corta del plan
            $table->string('descripcion', 255)->nullable();

            // Precio del plan
            // 10,2 significa hasta 10 dígitos en total y 2 decimales
            $table->decimal('precio', 10, 2)->default(0);

            // Cuántos días dura el plan
            // Ejemplo: trial = 7, básico = 30
            $table->integer('duracion_dias');

            /*
            Límite de cursos por mes.
            Si es null, puede significar que no tiene límite
            o que dependerá de otra regla del sistema.
            */
            $table->integer('limite_cursos_mes')->nullable();

            /*
            acceso_total:
            1 = puede acceder a todo
            0 = acceso limitado
            */
            $table->boolean('acceso_total')->default(false);

            /*
            es_prueba:
            1 = este plan es un trial
            0 = plan normal
            */
            $table->boolean('es_prueba')->default(false);

            /*
            soporte_24_7:
            1 = sí tiene soporte permanente
            0 = no
            */
            $table->boolean('soporte_24_7')->default(false);

            /*
            Estado del plan:
            activo o inactivo
            */
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            // Fechas automáticas
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};