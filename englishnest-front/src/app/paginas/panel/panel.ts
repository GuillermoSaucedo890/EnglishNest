import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../servicios/auth';
import { CursoService } from '../../servicios/curso';
import { InscripcionService } from '../../servicios/inscripcion';
import { LeccionService } from '../../servicios/leccion';
import { ProgresoService } from '../../servicios/progreso';

@Component({
  selector: 'app-panel',
  imports: [CommonModule, RouterLink],
  templateUrl: './panel.html',
  styleUrl: './panel.css'
})
export class Panel implements OnInit {
  private authService = inject(AuthService);
  private cursoService = inject(CursoService);
  private inscripcionService = inject(InscripcionService);
  private leccionService = inject(LeccionService);
  private progresoService = inject(ProgresoService);
  private router = inject(Router);

  usuario: any = null;
  cursos: any[] = [];
  inscripciones: any[] = [];
  lecciones: any[] = [];
  progreso: any[] = [];
  tieneAccesoPago = false;
  error = '';

  ngOnInit(): void {
    if (!this.authService.getToken()) {
      this.router.navigate(['/login']);
      return;
    }

    this.authService.me().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.usuario;

        if (!this.usuario.correo_verificado) {
          this.router.navigate(['/verificar-correo']);
          return;
        }

        this.cargarDatos();
      },
      error: (error) => {
        this.error = this.authService.extraerError(error);
        this.authService.limpiarSesion();
        this.router.navigate(['/login']);
      }
    });
  }

  cargarDatos(): void {
    this.cargarCursos();
    this.cargarInscripciones();
    this.cargarLecciones();
    this.cargarProgreso();
    this.verificarPago();
  }

  cargarCursos(): void {
    this.cursoService.obtenerCursos().subscribe({
      next: (res: any) => this.cursos = Array.isArray(res) ? res : res.cursos,
      error: () => this.error = 'No se pudieron cargar los cursos.'
    });
  }

  cargarInscripciones(): void {
    this.inscripcionService.obtenerInscripciones().subscribe({
      next: (res) => this.inscripciones = res.inscripciones,
      error: () => this.error = 'No se pudieron cargar tus inscripciones.'
    });
  }

  cargarLecciones(): void {
    this.leccionService.obtenerLecciones().subscribe({
      next: (res) => this.lecciones = res.lecciones,
      error: () => this.error = 'No se pudieron cargar las lecciones.'
    });
  }

  cargarProgreso(): void {
    this.progresoService.obtenerProgreso().subscribe({
      next: (res) => this.progreso = res.progreso,
      error: () => this.error = 'No se pudo cargar el progreso.'
    });
  }

  verificarPago(): void {
    this.progresoService.verificarPago().subscribe({
      next: (res) => this.tieneAccesoPago = res.tiene_acceso,
      error: () => this.tieneAccesoPago = false
    });
  }

  misCursos(): any[] {
    return this.inscripciones.map(i => i.curso).filter(c => c);
  }

  estaInscrito(cursoId: number): boolean {
    return this.inscripciones.some(i => i.curso_id === cursoId);
  }

  inscribirme(cursoId: number): void {
    this.inscripcionService.inscribirse(cursoId).subscribe({
      next: (res) => {
        alert(res.mensaje);
        this.cargarInscripciones();
      },
      error: () => alert('No se pudo realizar la inscripción.')
    });
  }

  leccionesDelCurso(cursoId: number): any[] {
    return this.lecciones.filter(l => l.curso_id === cursoId);
  }

  progresoCurso(cursoId: number): number {
    const leccionesCurso = this.leccionesDelCurso(cursoId);
    if (leccionesCurso.length === 0) return 0;

    const completadas = leccionesCurso.filter(l =>
      this.progreso.some(p => p.leccion_id === l.id && p.completado)
    ).length;

    return Math.round((completadas / leccionesCurso.length) * 100);
  }

  marcarLeccion(leccionId: number): void {
    if (!this.tieneAccesoPago) {
      alert('Debes realizar un pago para avanzar en las lecciones.');
      return;
    }

    this.progresoService.marcarLeccion(leccionId, true).subscribe({
      next: () => this.cargarProgreso(),
      error: () => alert('No se pudo actualizar el progreso.')
    });
  }

  generarCertificado(curso: any): void {
    const nombre = `${this.usuario.nombres} ${this.usuario.apellidos}`;
    const ventana = window.open('', '_blank');

    if (!ventana) return;

    ventana.document.write(`
      <html>
      <head>
        <title>Certificado</title>
        <style>
          body { font-family: Arial; text-align: center; padding: 40px; }
          .certificado { border: 8px solid #2563eb; padding: 50px; border-radius: 20px; }
          h1 { font-size: 40px; }
          h2 { font-size: 28px; margin-top: 20px; }
          p { font-size: 18px; }
        </style>
      </head>
      <body>
        <div class="certificado">
          <h1>Certificado de Finalización</h1>
          <p>Se certifica que</p>
          <h2>${nombre}</h2>
          <p>ha completado el curso</p>
          <h2>${curso.titulo}</h2>
          <p>Fecha: ${new Date().toLocaleDateString()}</p>
        </div>
        <script>window.print()</script>
      </body>
      </html>
    `);

    ventana.document.close();
  }
}
