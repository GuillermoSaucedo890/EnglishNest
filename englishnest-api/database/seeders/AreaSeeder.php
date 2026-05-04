<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    /**
     * Inserta las áreas base del sistema.
     */
    public function run(): void
    {
        Area::updateOrCreate(
            ['nombre' => 'Tecnologia'],
            ['descripcion' => 'Cursos orientados a desarrollo y programacion']
        );

        Area::updateOrCreate(
            ['nombre' => 'Redes'],
            ['descripcion' => 'Cursos orientados a networking y soporte']
        );

        Area::updateOrCreate(
            ['nombre' => 'Soporte'],
            ['descripcion' => 'Cursos orientados a soporte tecnico']
        );

        Area::updateOrCreate(
            ['nombre' => 'Negocios'],
            ['descripcion' => 'Cursos orientados a comunicacion profesional']
        );
    }
}