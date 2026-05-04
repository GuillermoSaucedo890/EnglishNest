<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla areas
|--------------------------------------------------------------------------
| Aquí guardaremos las áreas de aprendizaje, por ejemplo:
| - Tecnología
| - Redes
| - Soporte
| - Negocios
|
| Esta tabla nos servirá para clasificar los cursos.
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            // Clave primaria principal
            $table->id();

            // Nombre del área
            // unique() evita repetir dos veces la misma área
            $table->string('nombre', 100)->unique();

            // Descripción corta del área
            // Puede quedar vacía
            $table->string('descripcion', 255)->nullable();

            // Fechas automáticas: created_at y updated_at
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    | Si algún día quieres revertir esta migración,
    | Laravel borrará la tabla areas.
    */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};