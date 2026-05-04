<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla perfiles_docentes
|--------------------------------------------------------------------------
| Esta tabla guarda datos extra SOLO de docentes.
| Así no ensuciamos la tabla users con campos que
| no necesitan estudiantes ni admins.
|
| Aquí guardaremos:
| - estudios
| - especialidad
| - biografia
| - estado_aprobacion
*/
return new class extends Migration
{
    public function up(): void
    {
        /*
        Ojo:
        Aunque el archivo se llame create_perfil_docentes_table,
        la tabla real la vamos a llamar perfiles_docentes.
        */
        Schema::create('perfiles_docentes', function (Blueprint $table) {
            // PK principal
            $table->id();

            /*
            usuario_id:
            relación 1 a 1 con users
            Cada docente tendrá solo un perfil docente.
            Por eso usamos unique().
            */
            $table->foreignId('usuario_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Estudios del docente
            $table->text('estudios');

            // Especialidad del docente
            $table->string('especialidad', 120)->nullable();

            // Biografía corta o presentación
            $table->text('biografia')->nullable();

            /*
            Estado de aprobación:
            - pendiente: el admin todavía no decide
            - aprobado: ya puede actuar como docente
            - rechazado: solicitud rechazada
            */
            $table->enum('estado_aprobacion', ['pendiente', 'aprobado', 'rechazado'])
                ->default('pendiente');

            // created_at y updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_docentes');
    }
};