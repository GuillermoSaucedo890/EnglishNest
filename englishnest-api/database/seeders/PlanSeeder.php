<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    // Inserta los planes iniciales del sistema
    public function run(): void
    {
        Plan::updateOrCreate(
            ['nombre' => 'TRIAL'],
            [
                'descripcion' => 'Prueba gratis de 7 días',
                'precio' => 0,
                'duracion_dias' => 7,
                'limite_cursos' => 1,
                'limite_dispositivos' => 1,
                'limite_mensajes_ia_dia' => 10,
                'permite_certificado' => false,
                'es_prueba' => true,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        Plan::updateOrCreate(
            ['nombre' => 'BASICO'],
            [
                'descripcion' => 'Plan básico mensual',
                'precio' => 20,
                'duracion_dias' => 30,
                'limite_cursos' => 5,
                'limite_dispositivos' => 3,
                'limite_mensajes_ia_dia' => 40,
                'permite_certificado' => true,
                'es_prueba' => false,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        Plan::updateOrCreate(
            ['nombre' => 'INTERMEDIO'],
            [
                'descripcion' => 'Plan intermedio semestral',
                'precio' => 50,
                'duracion_dias' => 180,
                'limite_cursos' => 10,
                'limite_dispositivos' => 5,
                'limite_mensajes_ia_dia' => 100,
                'permite_certificado' => true,
                'es_prueba' => false,
                'soporte_24_7' => false,
                'estado' => 'activo',
            ]
        );

        Plan::updateOrCreate(
            ['nombre' => 'PREMIUM'],
            [
                'descripcion' => 'Plan premium anual',
                'precio' => 100,
                'duracion_dias' => 365,
                'limite_cursos' => null,
                'limite_dispositivos' => 10,
                'limite_mensajes_ia_dia' => null,
                'permite_certificado' => true,
                'es_prueba' => false,
                'soporte_24_7' => true,
                'estado' => 'activo',
            ]
        );
    }
}