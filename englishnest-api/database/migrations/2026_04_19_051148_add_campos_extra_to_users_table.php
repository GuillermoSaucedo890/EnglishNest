<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración modifica la tabla users de Laravel
|--------------------------------------------------------------------------
| Laravel ya creó la tabla users por defecto.
| Nosotros no la vamos a reemplazar.
| Solo vamos a adaptarla a nuestro sistema.
|
| Cambios:
| - quitamos el campo name
| - agregamos rol_id
| - agregamos nombres
| - agregamos apellidos
| - agregamos estado
| - agregamos fecha_prueba_usada
*/
return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Primer bloque: quitar columna name
        |--------------------------------------------------------------------------
        | Laravel trae la columna "name" por defecto.
        | Como nosotros usaremos "nombres" y "apellidos",
        | esa columna ya no nos sirve.
        */
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        /*
        |--------------------------------------------------------------------------
        | Segundo bloque: agregar nuestras columnas nuevas
        |--------------------------------------------------------------------------
        */
        Schema::table('users', function (Blueprint $table) {
            // FK al rol del usuario
            // Ejemplo: admin, docente, estudiante
            $table->foreignId('rol_id')
                ->after('id')
                ->constrained('roles')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Nombres del usuario
            $table->string('nombres', 100)->after('rol_id');

            // Apellidos del usuario
            $table->string('apellidos', 100)->after('nombres');

            /*
            Estado del usuario:
            - activo: puede usar el sistema
            - bloqueado: el admin lo bloqueó
            */
            $table->enum('estado', ['activo', 'bloqueado'])
                ->default('activo')
                ->after('remember_token');

            /*
            Fecha en la que usó la prueba gratis.
            Si nunca la usó, queda NULL.
            */
            $table->dateTime('fecha_prueba_usada')
                ->nullable()
                ->after('estado');
        });
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Si hacemos rollback:
        | - quitamos FK
        | - quitamos columnas nuevas
        | - restauramos name
        |--------------------------------------------------------------------------
        */
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rol_id']);

            $table->dropColumn([
                'rol_id',
                'nombres',
                'apellidos',
                'estado',
                'fecha_prueba_usada',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });
    }
};