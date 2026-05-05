<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla roles
|--------------------------------------------------------------------------
| Aquí guardaremos los tipos de usuario del sistema:
| - admin
| - docente
| - estudiante
|
| Laravel ejecuta el método up() para crear la estructura.
| Y ejecuta down() si algún día quieres revertirla.
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | up()
    |--------------------------------------------------------------------------
    | Este método se ejecuta cuando corres:
    | php artisan migrate
    |
    | Aquí creamos la tabla roles.
    */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // id() crea una clave primaria BIGINT UNSIGNED AUTO_INCREMENT
            $table->id();

            // Nombre del rol, por ejemplo: admin, docente, estudiante
            // unique() evita que se repita el mismo nombre
            $table->string('nombre', 30)->unique();

            // created_at y updated_at
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | down()
    |--------------------------------------------------------------------------
    | Este método se ejecuta si haces rollback.
    | Borra la tabla roles.
    */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};