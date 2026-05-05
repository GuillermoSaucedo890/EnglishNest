import { CommonModule } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { AuthService } from '../../servicios/auth';
import { CursoService } from '../../servicios/curso';
import { InscripcionService } from '../../servicios/inscripcion';

type FiltroAdmin =
  | 'todos'
  | 'revision'
  | 'borrador'
  | 'rechazado'
  | 'sin_edicion'
  | 'publicado'
  | 'oculto';

type VistaDocente = 'mis-cursos' | 'catalogo';

@Component({
  selector: 'app-cursos',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './cursos.html',
  styleUrl: './cursos.css',
})
export class Cursos implements OnInit {
  private authService = inject(AuthService);
  private cursoService = inject(CursoService);
  private inscripcionService = inject(InscripcionService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  usuario = this.authService.usuarioActual;

  rol = signal('');
  cursos = signal<any[]>([]);
  cursosPublicos = signal<any[]>([]);
  inscripciones = signal<any[]>([]);

  cargando = signal(false);
  accionCargando = signal('');
  mensaje = signal('');
  error = signal('');

  busqueda = signal('');
  filtroCategoria = signal('todos');
  filtroAdmin = signal<FiltroAdmin>('todos');

  vistaDocente = signal<VistaDocente>('mis-cursos');

  ngOnInit(): void {
    this.route.queryParamMap.subscribe((params) => {
      const vista = params.get('vista');

      if (vista === 'catalogo') {
        this.vistaDocente.set('catalogo');
      } else {
        this.vistaDocente.set('mis-cursos');
      }
    });

    this.cargarPagina();
  }

  cambiarVistaDocente(vista: VistaDocente): void {
    this.vistaDocente.set(vista);

    this.router.navigate(['/cursos'], {
      queryParams: { vista },
    });
  }

  cargarPagina(): void {
    this.cargando.set(true);
    this.error.set('');
    this.mensaje.set('');

    const token = this.authService.getToken();

    if (!token) {
      this.rol.set('');
      this.cargarCatalogoPublico();
      return;
    }

    this.authService.me().subscribe({
      next: (res: any) => {
        this.authService.usuarioActual.set(res.usuario);
        this.rol.set(this.obtenerRol(res.usuario));

        this.cargarCursosSegunRol();

        if (this.esEstudiante()) {
          this.cargarInscripciones();
        }

        if (this.esDocente() || this.esEstudiante()) {
          this.cargarCatalogoPublicoSinLoader();
        }
      },
      error: () => {
        this.authService.limpiarSesion();
        this.rol.set('');
        this.cargarCatalogoPublico();
      },
    });
  }

  cargarCursosSegunRol(): void {
    this.cursoService.obtenerCursos().subscribe({
      next: (res: any) => {
        this.cursos.set(res.cursos ?? []);
        this.cargando.set(false);
      },
      error: (err: any) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarCatalogoPublico(): void {
    this.cursoService.obtenerCursosPublicos().subscribe({
      next: (res: any) => {
        this.cursosPublicos.set(res.cursos ?? []);
        this.cargando.set(false);
      },
      error: (err: any) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarCatalogoPublicoSinLoader(): void {
    this.cursoService.obtenerCursosPublicos().subscribe({
      next: (res: any) => {
        this.cursosPublicos.set(res.cursos ?? []);
      },
      error: () => {
        this.cursosPublicos.set([]);
      },
    });
  }

  cargarInscripciones(): void {
    this.inscripcionService.misCursos().subscribe({
      next: (res: any) => {
        const todas = res.inscripciones ?? [];
        const activas = todas.filter((inscripcion: any) => this.inscripcionActiva(inscripcion));

        this.inscripciones.set(activas);
      },
      error: () => {
        this.inscripciones.set([]);
      },
    });
  }

  obtenerRol(usuario: any): string {
    const rol =
      usuario?.rol?.nombre ??
      usuario?.rol_nombre ??
      usuario?.rol ??
      '';

    if (typeof rol === 'object') {
      return String(rol?.nombre ?? '').toLowerCase();
    }

    return String(rol).toLowerCase();
  }

  esAdmin(): boolean {
    return this.rol() === 'admin';
  }

  esDocente(): boolean {
    return this.rol() === 'docente';
  }

  esEstudiante(): boolean {
    return this.rol() === 'estudiante';
  }

  estadoOficial(curso: any): string {
    return String(curso?.estado_oficial ?? curso?.estado ?? '').toLowerCase();
  }

  estadoActual(curso: any): string {
    const posibles = [
      curso?.estado_actual,
      curso?.estado_edicion,
      curso?.edicion_estado,
      curso?.edicion?.estado,
      curso?.edicion_activa?.estado,
      curso?.curso_edicion?.estado,
    ];

    for (const valor of posibles) {
      const estado = String(valor ?? '').toLowerCase();

      if (['borrador', 'pendiente_revision', 'rechazado'].includes(estado)) {
        return estado;
      }
    }

    return '';
  }

  tieneEdicionActiva(curso: any): boolean {
    return !!curso?.tiene_edicion || this.estadoActual(curso) !== '';
  }

  tieneSolicitudRevision(curso: any): boolean {
    return this.estadoActual(curso) === 'pendiente_revision' || curso?.solicita_publicacion === true;
  }

  textoEstadoOficial(curso: any): string {
    const estado = this.estadoOficial(curso);

    const textos: any = {
      publicado: 'Publicado',
      borrador: 'Borrador',
      oculto: 'Oculto',
      archivado: 'Oculto',
      rechazado: 'Rechazado',
    };

    return textos[estado] ?? 'Sin estado';
  }

  claseEstadoOficial(curso: any): string {
    const estado = this.estadoOficial(curso);

    if (estado === 'publicado') return 'badge-verde';
    if (estado === 'oculto' || estado === 'archivado') return 'badge-gris';
    if (estado === 'borrador') return 'badge-azul';
    if (estado === 'rechazado') return 'badge-rojo';

    return 'badge-gris';
  }

  textoEstadoActual(curso: any): string {
    if (this.tieneSolicitudRevision(curso)) {
      return 'Solicitud de revisión';
    }

    const estado = this.estadoActual(curso);

    if (estado === 'borrador') {
      return 'Modificación en borrador';
    }

    if (estado === 'rechazado') {
      return 'Cambios rechazados';
    }

    return 'Sin edición activa';
  }

  claseEstadoActual(curso: any): string {
    if (this.tieneSolicitudRevision(curso)) {
      return 'badge-amarillo badge-pulso';
    }

    const estado = this.estadoActual(curso);

    if (estado === 'borrador') {
      return 'badge-azul';
    }

    if (estado === 'rechazado') {
      return 'badge-rojo';
    }

    return 'badge-gris';
  }

  cursosAdminFiltrados(): any[] {
    const filtro = this.filtroAdmin();

    return this.cursos().filter((curso) => {
      if (filtro === 'todos') return true;
      if (filtro === 'revision') return this.tieneSolicitudRevision(curso);
      if (filtro === 'borrador') return this.estadoActual(curso) === 'borrador';
      if (filtro === 'rechazado') return this.estadoActual(curso) === 'rechazado';
      if (filtro === 'sin_edicion') return !this.tieneEdicionActiva(curso);
      if (filtro === 'publicado') return this.estadoOficial(curso) === 'publicado';
      if (filtro === 'oculto') return ['oculto', 'archivado'].includes(this.estadoOficial(curso));

      return true;
    });
  }

  cantidadFiltroAdmin(filtro: FiltroAdmin): number {
    if (filtro === 'todos') return this.cursos().length;
    if (filtro === 'revision') return this.cursos().filter((curso) => this.tieneSolicitudRevision(curso)).length;
    if (filtro === 'borrador') return this.cursos().filter((curso) => this.estadoActual(curso) === 'borrador').length;
    if (filtro === 'rechazado') return this.cursos().filter((curso) => this.estadoActual(curso) === 'rechazado').length;
    if (filtro === 'sin_edicion') return this.cursos().filter((curso) => !this.tieneEdicionActiva(curso)).length;
    if (filtro === 'publicado') return this.cursos().filter((curso) => this.estadoOficial(curso) === 'publicado').length;
    if (filtro === 'oculto') return this.cursos().filter((curso) => ['oculto', 'archivado'].includes(this.estadoOficial(curso))).length;

    return 0;
  }

  nombreDocente(curso: any): string {
    return curso?.docente_nombre ?? 'Docente por asignar';
  }

  textoArea(curso: any): string {
    return curso?.area?.nombre ?? curso?.area_nombre ?? 'Sin área';
  }

  categoriasDisponibles(): string[] {
    const nombres = this.cursosPublicos()
      .map((curso) => this.textoArea(curso))
      .filter((nombre) => !!nombre && nombre !== 'Sin área');

    return Array.from(new Set(nombres));
  }

  cursosCatalogoFiltrados(): any[] {
    const texto = this.busqueda().trim().toLowerCase();
    const categoria = this.filtroCategoria();

    return this.cursosPublicos().filter((curso) => {
      const coincideTexto =
        !texto ||
        String(curso?.titulo ?? '').toLowerCase().includes(texto) ||
        String(curso?.descripcion ?? '').toLowerCase().includes(texto) ||
        String(this.textoArea(curso)).toLowerCase().includes(texto) ||
        String(this.nombreDocente(curso)).toLowerCase().includes(texto);

      const coincideCategoria =
        categoria === 'todos' ||
        this.textoArea(curso) === categoria;

      return coincideTexto && coincideCategoria;
    });
  }

  cursosRecomendados(): any[] {
    return this.cursosCatalogoFiltrados().slice(0, 4);
  }

  esMiCursoDocente(curso: any): boolean {
    const cursoId = Number(curso?.id);

    return this.cursos().some((miCurso) => Number(miCurso?.id) === cursoId);
  }

  inscripcionActiva(inscripcion: any): boolean {
    const estado = String(inscripcion?.estado ?? 'activa').toLowerCase();

    return ['activa', 'activo', 'vigente'].includes(estado);
  }

  estaSuscrito(curso: any): boolean {
    const cursoId = Number(curso?.id);

    return this.inscripciones().some((inscripcion) => {
      const id =
        Number(inscripcion?.curso_id) ||
        Number(inscripcion?.curso?.id) ||
        Number(inscripcion?.id);

      return id === cursoId;
    });
  }

  textoNivel(curso: any): string {
    const nivel = String(curso?.nivel ?? '').toLowerCase();

    const textos: any = {
      basico: 'Básico',
      intermedio: 'Intermedio',
      avanzado: 'Avanzado',
    };

    return textos[nivel] ?? 'Nivel';
  }

  publicarCurso(curso: any): void {
    this.error.set('');
    this.mensaje.set('');
    this.accionCargando.set('publicar-' + curso.id);

    this.cursoService.publicarCurso(curso.id).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso publicado correctamente.');
        this.cargarCursosSegunRol();
      },
      error: (err: any) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  ocultarCurso(curso: any): void {
    this.error.set('');
    this.mensaje.set('');
    this.accionCargando.set('ocultar-' + curso.id);

    this.cursoService.ocultarCurso(curso.id).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso ocultado correctamente.');
        this.cargarCursosSegunRol();
      },
      error: (err: any) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }
}