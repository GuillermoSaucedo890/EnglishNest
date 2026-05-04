<?php

namespace Database\Seeders;

// Seeder base de Laravel
use Illuminate\Database\Seeder;

// Importamos el modelo Rol
use App\Models\Rol;

class RolSeeder extends Seeder
{
    /**
     * Este método inserta los roles básicos del sistema.
     */
    public function run(): void
    {
        /*
        updateOrCreate significa:
        - si ya existe un rol con ese nombre, lo actualiza
        - si no existe, lo crea

        Esto evita duplicados si ejecutas el seeder más de una vez.
        */

        Rol::updateOrCreate(
            ['nombre' => 'admin'],
            ['nombre' => 'admin']
        );

        Rol::updateOrCreate(
            ['nombre' => 'docente'],
            ['nombre' => 'docente']
        );

        Rol::updateOrCreate(
            ['nombre' => 'estudiante'],
            ['nombre' => 'estudiante']
        );
    }
}