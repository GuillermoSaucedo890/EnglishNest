import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { RouterLink } from '@angular/router';
import { InscripcionService } from '../../servicios/inscripcion';

@Component({
  selector: 'app-mis-cursos',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './mis-cursos.html',
  styleUrl: './mis-cursos.css'
})
export class MisCursos implements OnInit {

  private inscripcionService = inject(InscripcionService);

  inscripciones: any[] = [];
  cargando = true;
  error = '';

  ngOnInit(): void {
    this.cargarMisCursos();
  }

  cargarMisCursos(): void {
    this.inscripcionService.obtenerInscripciones().subscribe({
      next: (res: any) => {
        this.inscripciones = res.inscripciones || [];
        this.cargando = false;
      },
      error: (err) => {
        console.error(err);
        this.error = 'No se pudieron cargar tus cursos.';
        this.cargando = false;
      }
    });
  }
}
