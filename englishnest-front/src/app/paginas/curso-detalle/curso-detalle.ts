import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-curso-detalle',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './curso-detalle.html',
  styleUrl: './curso-detalle.css'
})
export class CursoDetalle {

  modulos = [
    {
      titulo: 'General',
      abierto: true,
      contenidos: [
        { tipo: 'foro', titulo: 'Avisos', subtitulo: 'Foro general del curso' },
        { tipo: 'info', titulo: 'Bienvenida al curso', subtitulo: 'Presentación general de la materia' },
      ]
    },
    {
      titulo: 'Direccionamiento u organización',
      abierto: true,
      descripcion: 'En esta fase se hallan las directrices de la asignatura, información del docente y normas del curso.',
      contenidos: [
        { tipo: 'link', titulo: 'Enlace de grupo de WhatsApp', subtitulo: 'URL' },
        { tipo: 'carpeta', titulo: '1. Información del docente', subtitulo: 'Carpeta' },
        { tipo: 'carpeta', titulo: '2. Datos informativos sobre la asignatura', subtitulo: 'Carpeta' },
        { tipo: 'carpeta', titulo: '3. Normas de la asignatura', subtitulo: 'Carpeta' },
        { tipo: 'tarea', titulo: 'Actividad diagnóstica', subtitulo: 'Tarea' },
      ]
    },
    {
      titulo: 'Planeación o Gestión del Conocimiento',
      abierto: true,
      descripcion: 'Recursos digitales preparados para el desarrollo de las unidades temáticas.',
      contenidos: [
        { tipo: 'carpeta', titulo: '1. Texto guía', subtitulo: 'Carpeta' },
        { tipo: 'carpeta', titulo: '2. Presentaciones de la asignatura', subtitulo: 'Carpeta' },
        { tipo: 'carpeta', titulo: '3. Bibliografía de apoyo', subtitulo: 'Carpeta' },
        { tipo: 'video', titulo: '4. Videos de apoyo para la asignatura', subtitulo: 'Video' },
        { tipo: 'carpeta', titulo: '5. Grabaciones de clases', subtitulo: 'Carpeta' },
      ]
    },
    {
      titulo: 'Ejecución',
      abierto: true,
      descripcion: 'Actividades que el estudiante debe realizar y que tienen ponderación.',
      contenidos: [
        { tipo: 'tarea', titulo: '1. Actividad 01 - Vocabulario técnico', subtitulo: 'Tarea' },
        { tipo: 'tarea', titulo: '2. Actividad 02 - Lectura técnica', subtitulo: 'Tarea' },
      ]
    },
    {
      titulo: 'Comunicación o Socialización',
      abierto: true,
      descripcion: 'Actividad final relacionada con el proyecto formativo.',
      contenidos: [
        { tipo: 'tarea', titulo: '1. Entregable final del curso', subtitulo: 'Tarea' },
        { tipo: 'encuesta', titulo: 'Retroalimentación', subtitulo: 'Módulo de encuesta' },
        { tipo: 'link', titulo: 'Evaluación al docente', subtitulo: 'URL' },
      ]
    }
  ];

  toggleModulo(modulo: any): void {
    modulo.abierto = !modulo.abierto;
  }

  icono(tipo: string): string {
    switch (tipo) {
      case 'foro': return '💬';
      case 'info': return '✅';
      case 'link': return '↗️';
      case 'carpeta': return '📁';
      case 'tarea': return '📤';
      case 'video': return '🎥';
      case 'encuesta': return '🧾';
      default: return '📄';
    }
  }
}
