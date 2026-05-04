<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Inserta los planes iniciales del sistema.
     */
    public function run(): void
    {
        /*
        TRIAL:
        - prueba gratis
        - 7 días
        - acceso limitado
        */
        Plan::updateOrCreate(
            ['nombre' => 'TRIAL'],
            [
                'descripcion' => 'Prueba gratis de 7 dias',
                'precio' => 0,
                'duracion_dias' => 7,
                'limite_cursos_mes' => 1,
                'acceso_total' => false,
                'es_prueba' => true,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        /*
        BASICO:
        - acceso limitado
        - 30 días
        */
        Plan::updateOrCreate(
            ['nombre' => 'BASICO'],
            [
                'descripcion' => 'Plan basico',
                'precio' => 0,
                'duracion_dias' => 30,
                'limite_cursos_mes' => 3,
                'acceso_total' => false,
                'es_prueba' => false,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        /*
        PRO:
        - más cursos por mes
        */
        Plan::updateOrCreate(
            ['nombre' => 'PRO'],
            [
                'descripcion' => 'Plan pro',
                'precio' => 0,
                'duracion_dias' => 30,
                'limite_cursos_mes' => 10,
                'acceso_total' => false,
                'es_prueba' => false,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        /*
        PREMIUM:
        - acceso total
        - soporte 24/7
        */
        Plan::updateOrCreate(
            ['nombre' => 'PREMIUM'],
            [
                'descripcion' => 'Plan premium',
                'precio' => 0,
                'duracion_dias' => 30,
                'limite_cursos_mes' => null,
                'acceso_total' => true,
                'es_prueba' => false,
                'soporte_24_7' => true,
                'estado' => 'activo',
            ]
        );
    }
}