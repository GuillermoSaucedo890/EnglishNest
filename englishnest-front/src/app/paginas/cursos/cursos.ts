import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { CursoService } from '../../servicios/curso';
import { InscripcionService } from '../../servicios/inscripcion';

@Component({
  selector: 'app-cursos',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './cursos.html',
  styleUrl: './cursos.css'
})
export class Cursos implements OnInit {

  private cursoService = inject(CursoService);
  private inscripcionService = inject(InscripcionService);

  cursos: any[] = [];
  inscripciones: any[] = [];
  error = '';
  cargando = true;

  ngOnInit(): void {
    this.cargarCursos();
    this.cargarInscripciones();
  }

  cargarCursos(): void {
    this.cargando = true;

    this.cursoService.obtenerCursos().subscribe({
      next: (res: any) => {
        this.cursos = Array.isArray(res) ? res : res.cursos;
        this.cargando = false;
      },
      error: (err) => {
        console.error(err);
        this.error = 'Error al cargar cursos';
        this.cargando = false;
      }
    });
  }

  cargarInscripciones(): void {
    this.inscripcionService.obtenerInscripciones().subscribe({
      next: (res: any) => {
        this.inscripciones = Array.isArray(res) ? res : res.inscripciones;
      },
      error: (err) => {
        console.error(err);
        this.inscripciones = [];
      }
    });
  }

  estaInscrito(cursoId: number): boolean {
    return this.inscripciones.some(i => i.curso_id === cursoId || i.id === cursoId);
  }

  inscribirme(cursoId: number): void {
    if (this.estaInscrito(cursoId)) {
      alert('Ya estás inscrito en este curso');
      return;
    }

    this.inscripcionService.inscribirse(cursoId).subscribe({
      next: (res: any) => {
        alert(res.message || res.mensaje || 'Inscripción realizada correctamente');
        this.cargarInscripciones();
      },
      error: (err) => {
        console.error(err);

        if (err.status === 409) {
          alert('Ya estás inscrito en este curso');
        } else {
          alert('Error al inscribirse');
        }
      }
    });
  }

  cursosRecomendados(): any[] {
    return this.cursos
      .filter(curso => !this.estaInscrito(curso.id))
      .slice(0, 3);
  }
}
