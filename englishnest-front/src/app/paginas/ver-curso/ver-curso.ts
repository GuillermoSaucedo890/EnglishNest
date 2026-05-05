import { CommonModule } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { AuthService } from '../../servicios/auth';
import { CursoService } from '../../servicios/curso';
import { InscripcionService } from '../../servicios/inscripcion';
import { EvaluacionService } from '../../servicios/evaluacion';

@Component({
  selector: 'app-ver-curso',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './ver-curso.html',
  styleUrl: './ver-curso.css',
})
export class VerCurso implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private sanitizer = inject(DomSanitizer);
  private authService = inject(AuthService);
  private cursoService = inject(CursoService);
  private inscripcionService = inject(InscripcionService);
  private evaluacionService = inject(EvaluacionService);

  usuario = this.authService.usuarioActual;
  rolActual = this.authService.rolActual;

  cursoId = '';

  curso = signal<any | null>(null);
  leccionSeleccionada = signal<any | null>(null);
  inscripcionesEstudiante = signal<any[]>([]);
  progresoLecciones = signal<Record<string, any>>({});

  videoSeguro = signal<SafeResourceUrl | null>(null);
  videoIdActual = signal('');

  cargando = signal(false);
  accionCargando = signal('');

  mensaje = signal('');
  error = signal('');

  modalBloqueoAbierto = signal(false);
  leccionBloqueada = signal<any | null>(null);
  motivoBloqueo = signal<'plan' | 'avance'>('plan');

  modalPlanAbierto = signal(false);
  mensajePlan = signal('');

  modalDesuscripcionAbierto = signal(false);
  modalRepetirCursoAbierto = signal(false);

  modalResultadoAbierto = signal(false);
  resultadoModal = signal<any | null>(null);

  vistaActual = signal<'leccion' | 'evaluacion_final'>('leccion');

  cargandoEvaluacion = signal(false);
  evaluacionActual = signal<any | null>(null);
  estadoEvaluacionActual = signal<any | null>(null);
  respuestasActuales = signal<Record<string, number>>({});
  resultadoEvaluacionActual = signal<any | null>(null);
  evaluacionAbierta = signal(false);

  cargandoEvaluacionFinal = signal(false);
  evaluacionFinal = signal<any | null>(null);
  estadoEvaluacionFinal = signal<any | null>(null);
  respuestasFinales = signal<Record<string, number>>({});
  resultadoEvaluacionFinal = signal<any | null>(null);
  evaluacionFinalAbierta = signal(false);

  ngOnInit(): void {
    this.cursoId = this.route.snapshot.paramMap.get('id') ?? '';

    if (!this.cursoId) {
      this.error.set('No se encontró el curso solicitado.');
      return;
    }

    this.prepararVista();
  }

  prepararVista(): void {
    if (!this.authService.getToken()) {
      this.cargarCursoPublico();
      return;
    }

    this.authService.me().subscribe({
      next: (res: any) => {
        this.authService.usuarioActual.set(res.usuario);
        this.cargarCursoPrivado();
      },
      error: () => {
        this.authService.limpiarSesion();
        this.cargarCursoPublico();
      },
    });
  }

  cargarCursoPublico(): void {
    this.cargando.set(true);
    this.error.set('');
    this.mensaje.set('');
    this.inscripcionesEstudiante.set([]);
    this.progresoLecciones.set({});

    this.cursoService.obtenerCursoPublico(this.cursoId).subscribe({
      next: (res: any) => {
        this.cargando.set(false);
        this.configurarCurso(res.curso);
      },
      error: (err: any) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarCursoPrivado(): void {
    this.cargando.set(true);
    this.error.set('');
    this.mensaje.set('');

    this.cursoService.obtenerCurso(this.cursoId).subscribe({
      next: (resCurso: any) => {
        if (!this.esEstudiante()) {
          this.cargando.set(false);
          this.inscripcionesEstudiante.set([]);
          this.configurarCurso(resCurso.curso);
          return;
        }

        this.inscripcionService.misCursos().subscribe({
          next: (resInscripciones: any) => {
            this.cargando.set(false);
            this.inscripcionesEstudiante.set(this.normalizarInscripciones(resInscripciones));
            this.configurarCurso(resCurso.curso);
            this.cargarEstadoProgresoCurso();
          },
          error: () => {
            this.cargando.set(false);
            this.inscripcionesEstudiante.set([]);
            this.configurarCurso(resCurso.curso);
          },
        });
      },
      error: (err: any) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarEstadoProgresoCurso(): void {
    if (!this.esEstudiante() || !this.estaAutenticado()) {
      return;
    }

    this.evaluacionService.obtenerEstadoCurso(this.cursoId).subscribe({
      next: (res: any) => {
        const mapa: Record<string, any> = {};

        for (const item of res?.lecciones ?? []) {
          mapa[String(item.leccion_id)] = item;
        }

        this.progresoLecciones.set(mapa);
        this.actualizarPermisosLeccionesDesdeProgreso();
      },
      error: () => {
        this.progresoLecciones.set({});
      },
    });
  }

  normalizarInscripciones(res: any): any[] {
    const posiblesListas = [
      res?.inscripciones,
      res?.cursos,
      res?.data,
      res,
    ];

    for (const lista of posiblesListas) {
      if (Array.isArray(lista)) {
        return lista;
      }
    }

    return [];
  }

  inscripcionActual(): any | null {
    const idBuscado = Number(this.cursoId);

    return this.inscripcionesEstudiante().find((item: any) => {
      const idCurso =
        Number(item?.curso_id) ||
        Number(item?.curso?.id) ||
        Number(item?.id);

      return idCurso === idBuscado;
    }) ?? null;
  }

  estaCursoEnMisCursos(cursoId: number | string): boolean {
    const idBuscado = Number(cursoId);

    return this.inscripcionesEstudiante().some((item: any) => {
      const estado = String(
        item?.estado ??
        item?.inscripcion_estado ??
        item?.pivot?.estado ??
        item?.inscripcion?.estado ??
        'activa'
      ).toLowerCase();

      const estaActiva =
        estado === 'activa' ||
        estado === 'activo' ||
        estado === 'vigente' ||
        estado === 'completada' ||
        estado === 'reprobada' ||
        estado === 'bloqueada' ||
        estado === '';

      const idCurso =
        Number(item?.curso_id) ||
        Number(item?.curso?.id) ||
        Number(item?.id);

      return estaActiva && idCurso === idBuscado;
    });
  }

  configurarCurso(curso: any): void {
    if (!curso) {
      this.error.set('No se encontró información del curso.');
      return;
    }

    const leccionSeleccionadaAntes = this.leccionSeleccionada();
    const leccionIdSeleccionadaAntes = leccionSeleccionadaAntes?.id;

    const cursoEstaInscrito =
      this.cursoInscritoDesdeCurso(curso) ||
      this.estaCursoEnMisCursos(curso?.id ?? this.cursoId);

    const lecciones = this.ordenarLecciones(curso.lecciones ?? []);

    const leccionesNormalizadas = lecciones.map((leccion: any, index: number) => {
      const esPrimera = index === 0;
      const esIntro = leccion.tipo === 'introduccion';
      const esGratis = leccion.es_gratis === true || leccion.es_gratis === 1;
      const tipoNormalizado = esPrimera ? 'introduccion' : (leccion.tipo ?? 'normal');

      const puedeVer = this.calcularPuedeVerLeccion(
        {
          ...leccion,
          tipo: tipoNormalizado,
          es_gratis: esPrimera ? true : esGratis,
        },
        index,
        cursoEstaInscrito
      );

      return {
        ...leccion,
        tipo: tipoNormalizado,
        es_gratis: esPrimera ? true : esGratis,
        puede_ver: puedeVer,
        bloqueada: !puedeVer,
        completada: this.leccionCompletada(leccion),
        motivo_bloqueo: this.obtenerMotivoBloqueo(cursoEstaInscrito, puedeVer),
        url_video: this.obtenerUrlVideoDesdeLeccion(leccion),
      };
    });

    const cursoNormalizado = {
      ...curso,
      esta_inscrito: cursoEstaInscrito,
      inscripcion_activa: cursoEstaInscrito,
      estado_inscripcion: cursoEstaInscrito ? 'activa' : curso?.estado_inscripcion,
      lecciones: leccionesNormalizadas,
    };

    this.curso.set(cursoNormalizado);

    const leccionParaMantener =
      leccionesNormalizadas.find((leccion: any) => String(leccion.id) === String(leccionIdSeleccionadaAntes)) ??
      null;

    const primeraDisponible =
      leccionesNormalizadas.find((leccion: any, index: number) => this.puedeVerLeccion(leccion, index)) ??
      null;

    this.seleccionarLeccionInterna(leccionParaMantener ?? primeraDisponible);
  }

  actualizarPermisosLeccionesDesdeProgreso(): void {
    const cursoActual = this.curso();

    if (!cursoActual) {
      return;
    }

    const leccionesActualizadas = this.ordenarLecciones(cursoActual.lecciones ?? []).map((leccion: any, index: number) => {
      const puedeVer = this.calcularPuedeVerLeccion(leccion, index, this.cursoInscrito());

      return {
        ...leccion,
        puede_ver: puedeVer,
        bloqueada: !puedeVer,
        completada: this.leccionCompletada(leccion),
        motivo_bloqueo: this.obtenerMotivoBloqueo(this.cursoInscrito(), puedeVer),
      };
    });

    this.curso.set({
      ...cursoActual,
      lecciones: leccionesActualizadas,
    });

    const leccionActual = this.leccionSeleccionada();

    if (!leccionActual) {
      return;
    }

    const actualizada = leccionesActualizadas.find((leccion: any) => String(leccion.id) === String(leccionActual.id));

    if (actualizada) {
      this.leccionSeleccionada.set(actualizada);
    }
  }

  calcularPuedeVerLeccion(leccion: any, index: number, cursoEstaInscrito: boolean): boolean {
    if (this.esAdmin() || this.esDocente()) {
      return true;
    }

    // La introducción siempre se puede ver.
    // Las demás lecciones se desbloquean con avance real.
    if (this.esIntroduccionPorIndice(leccion, index)) {
      return true;
    }

    if (!cursoEstaInscrito) {
      return false;
    }

    // Incluso la primera lección normal queda bloqueada hasta que el estudiante
    // presione “Continuar” en la introducción. Así, al repetir curso vuelve a 0%.
    return this.leccionAnteriorCompletada(index);
  }

  obtenerMotivoBloqueo(cursoEstaInscrito: boolean, puedeVer: boolean): 'plan' | 'avance' {
    if (puedeVer) {
      return 'avance';
    }

    return cursoEstaInscrito ? 'avance' : 'plan';
  }

  ordenarLecciones(lecciones: any[]): any[] {
    return [...lecciones].sort((a, b) => Number(a.orden ?? 0) - Number(b.orden ?? 0));
  }

  obtenerUrlVideoDesdeLeccion(leccion: any): string | null {
    return (
      leccion?.url_video ??
      leccion?.video_url ??
      leccion?.youtube_url ??
      leccion?.urlYoutube ??
      leccion?.url_youtube ??
      null
    );
  }

  actualizarVideoSeguro(leccion: any | null): void {
    const url = this.obtenerUrlVideoDesdeLeccion(leccion);

    if (!url) {
      this.videoIdActual.set('');
      this.videoSeguro.set(null);
      return;
    }

    const id = this.extraerYoutubeId(url);

    if (!id) {
      this.videoIdActual.set('');
      this.videoSeguro.set(null);
      return;
    }

    if (this.videoIdActual() === id && this.videoSeguro()) {
      return;
    }

    this.videoIdActual.set(id);

    this.videoSeguro.set(
      this.sanitizer.bypassSecurityTrustResourceUrl(
        `https://www.youtube.com/embed/${id}?rel=0&modestbranding=1`
      )
    );
  }

  seleccionarLeccionInterna(leccion: any | null): void {
    this.vistaActual.set('leccion');
    this.leccionSeleccionada.set(leccion);
    this.actualizarVideoSeguro(leccion);
    this.reiniciarEvaluacionActual();

    if (leccion && !this.esIntroduccionActual()) {
      this.cargarEvaluacionDeLeccion(leccion);
    }
  }

  esAdmin(): boolean {
    return this.rolActual() === 'admin';
  }

  esDocente(): boolean {
    return this.rolActual() === 'docente';
  }

  esEstudiante(): boolean {
    return this.rolActual() === 'estudiante';
  }

  estaAutenticado(): boolean {
    return !!this.authService.getToken();
  }

  cursoInscrito(): boolean {
    return this.cursoInscritoDesdeCurso(this.curso()) || this.estaCursoEnMisCursos(this.cursoId);
  }

  cursoInscritoDesdeCurso(curso: any): boolean {
    const estadoInscripcion = String(
      curso?.estado_inscripcion ??
      curso?.inscripcion_estado ??
      curso?.inscripcion?.estado ??
      ''
    ).toLowerCase();

    const tieneEstadoActivo =
      estadoInscripcion === 'activa' ||
      estadoInscripcion === 'activo' ||
      estadoInscripcion === 'vigente' ||
      estadoInscripcion === 'completada' ||
      estadoInscripcion === 'reprobada' ||
      estadoInscripcion === 'bloqueada';

    return !!(
      curso?.esta_inscrito === true ||
      curso?.inscripcion_activa === true ||
      tieneEstadoActivo
    );
  }

  cursoReprobado(): boolean {
    const inscripcion = this.inscripcionActual();
    const estado = String(inscripcion?.estado ?? inscripcion?.inscripcion_estado ?? '').toLowerCase();

    return estado === 'reprobada' || estado === 'bloqueada';
  }

  certificadoDisponible(): boolean {
    const inscripcion = this.inscripcionActual();

    return !!(
      inscripcion?.certificado_disponible === true ||
      inscripcion?.aprobado === true ||
      this.curso()?.certificado_disponible === true ||
      this.curso()?.aprobado === true
    );
  }

  nombreDocente(): string {
    const docente = this.curso()?.docente;

    if (!docente) {
      return this.curso()?.docente_nombre ?? 'Docente por asignar';
    }

    return `${docente.nombres ?? ''} ${docente.apellidos ?? ''}`.trim();
  }

  nombreArea(): string {
    return this.curso()?.area?.nombre ?? 'Sin categoría';
  }

  lecciones(): any[] {
    return this.curso()?.lecciones ?? [];
  }

  leccionesNormales(): any[] {
    return this.lecciones().filter((leccion: any, index: number) => !this.esIntroduccionPorIndice(leccion, index));
  }

  progresoPorcentaje(): number {
    const normales = this.leccionesNormales();

    if (normales.length <= 0) {
      return 0;
    }

    const completadas = normales.filter((leccion: any) => this.leccionCompletada(leccion)).length;
    const finalRespondida = this.evaluacionFinalRespondida();
    const totalPasos = normales.length + 1;
    const pasosCompletados = completadas + (finalRespondida ? 1 : 0);

    return Math.min(100, Math.round((pasosCompletados / totalPasos) * 100));
  }

  evaluacionFinalRespondida(): boolean {
    return !!(
      this.estadoEvaluacionFinal()?.intentos_usados > 0 ||
      this.resultadoEvaluacionFinal() ||
      this.inscripcionActual()?.nota_examen_final > 0 ||
      this.certificadoDisponible()
    );
  }

  leccionCompletada(leccion: any): boolean {
    const progreso = this.progresoLecciones()[String(leccion?.id)];

    // La fuente de verdad visual será progresoLecciones.
    // No usamos leccion.completada porque después de reiniciar podía quedar
    // una marca vieja en memoria y mostrar 50% aunque el backend ya borró todo.
    return progreso?.completado === true;
  }

  leccionAnteriorCompletada(index: number): boolean {
    const anterior = this.lecciones()[index - 1];

    if (!anterior) {
      return false;
    }

    return this.leccionCompletada(anterior);
  }

  puedeVerLeccion(leccion: any, index: number): boolean {
    return this.calcularPuedeVerLeccion(leccion, index, this.cursoInscrito());
  }

  esIntroduccionPorIndice(leccion: any, index: number): boolean {
    return index === 0 || leccion?.tipo === 'introduccion';
  }

  esIntroduccionActual(): boolean {
    const leccionActual = this.leccionSeleccionada();

    if (!leccionActual) {
      return false;
    }

    const index = this.lecciones().findIndex((leccion: any) => String(leccion.id) === String(leccionActual.id));

    return this.esIntroduccionPorIndice(leccionActual, index);
  }


  puedeContinuarDesdeIntroduccion(): boolean {
    return !!this.leccionSeleccionada() && this.esIntroduccionActual();
  }

  continuarDesdeIntroduccion(): void {
    const intro = this.leccionSeleccionada();

    if (!intro) {
      return;
    }

    if (this.esAdmin() || this.esDocente()) {
      this.irSiguienteLeccionDesdeLeccionActual();
      return;
    }

    if (!this.estaAutenticado()) {
      this.router.navigate(['/login']);
      return;
    }

    if (!this.cursoInscrito()) {
      this.abrirModalBloqueo(this.lecciones()[1] ?? { titulo: 'Primera lección' });
      return;
    }

    this.accionCargando.set('continuar-intro');

    this.evaluacionService.marcarLeccionCompletada(intro.id).subscribe({
      next: () => {
        this.accionCargando.set('');
        this.progresoLecciones.set({
          ...this.progresoLecciones(),
          [String(intro.id)]: {
            leccion_id: intro.id,
            completado: true,
          },
        });

        this.actualizarPermisosLeccionesDesdeProgreso();
        this.irSiguienteLeccionDesdeLeccionActual();
      },
      error: (err: any) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  irSiguienteLeccionDesdeLeccionActual(): void {
    const actual = this.leccionSeleccionada();
    const lecciones = this.lecciones();
    const indiceActual = lecciones.findIndex((leccion: any) => String(leccion.id) === String(actual?.id));

    if (indiceActual < 0) {
      return;
    }

    const siguiente = lecciones[indiceActual + 1] ?? null;

    if (!siguiente) {
      if (this.puedeAbrirEvaluacionFinal()) {
        this.abrirEvaluacionFinal();
      }
      return;
    }

    const indiceSiguiente = indiceActual + 1;

    if (this.puedeVerLeccion(siguiente, indiceSiguiente)) {
      this.seleccionarLeccion(siguiente, indiceSiguiente);
    }
  }

  seleccionarLeccion(leccion: any, index: number): void {
    this.mensaje.set('');
    this.error.set('');

    if (!this.puedeVerLeccion(leccion, index)) {
      this.abrirModalBloqueo(leccion);
      return;
    }

    this.seleccionarLeccionInterna(leccion);
  }

  abrirModalBloqueo(leccion: any): void {
    this.leccionBloqueada.set(leccion);

    const motivo = leccion?.motivo_bloqueo === 'avance' ? 'avance' : 'plan';
    this.motivoBloqueo.set(motivo);

    this.modalBloqueoAbierto.set(true);
  }

  cerrarModalBloqueo(): void {
    this.modalBloqueoAbierto.set(false);
    this.leccionBloqueada.set(null);
  }

  cerrarModalPlan(): void {
    this.modalPlanAbierto.set(false);
    this.mensajePlan.set('');
  }

  abrirModalPlan(mensaje: string): void {
    this.mensajePlan.set(mensaje);
    this.modalPlanAbierto.set(true);
  }

  inscribirmeAlCurso(): void {
    this.error.set('');
    this.mensaje.set('');

    if (!this.estaAutenticado()) {
      this.router.navigate(['/login']);
      return;
    }

    if (!this.esEstudiante()) {
      this.error.set('Solo una cuenta de estudiante puede inscribirse a cursos.');
      return;
    }

    this.accionCargando.set('inscripcion');

    this.inscripcionService.inscribirse(Number(this.cursoId)).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Te inscribiste correctamente al curso.');
        this.cerrarModalBloqueo();

        this.marcarCursoComoInscrito(res?.inscripcion ?? null);
        this.cargarEstadoProgresoCurso();
      },
      error: (err: any) => {
        this.accionCargando.set('');

        const mensaje = this.authService.extraerError(err);
        const mensajeMinuscula = mensaje.toLowerCase();

        if (
          mensajeMinuscula.includes('límite') ||
          mensajeMinuscula.includes('limite') ||
          mensajeMinuscula.includes('plan')
        ) {
          this.cerrarModalBloqueo();
          this.abrirModalPlan(mensaje);
          return;
        }

        this.error.set(mensaje);
      },
    });
  }

  marcarCursoComoInscrito(inscripcionNueva: any | null = null): void {
    const cursoActual = this.curso();

    if (!cursoActual) {
      return;
    }

    const yaExiste = this.inscripcionActual();

    if (!yaExiste) {
      this.inscripcionesEstudiante.set([
        ...this.inscripcionesEstudiante(),
        inscripcionNueva ?? {
          curso_id: Number(this.cursoId),
          estado: 'activa',
          curso: cursoActual,
        },
      ]);
    } else if (inscripcionNueva) {
      this.actualizarInscripcionLocal(inscripcionNueva);
    }

    const leccionesActualizadas = (cursoActual.lecciones ?? []).map((leccion: any, index: number) => {
      const puedeVer = this.calcularPuedeVerLeccion(leccion, index, true);

      return {
        ...leccion,
        puede_ver: puedeVer,
        bloqueada: !puedeVer,
        motivo_bloqueo: 'avance',
      };
    });

    this.curso.set({
      ...cursoActual,
      esta_inscrito: true,
      inscripcion_activa: true,
      estado_inscripcion: 'activa',
      lecciones: leccionesActualizadas,
    });
  }

  abrirConfirmarDesuscripcion(): void {
    this.error.set('');
    this.mensaje.set('');
    this.modalDesuscripcionAbierto.set(true);
  }

  cerrarConfirmarDesuscripcion(): void {
    this.modalDesuscripcionAbierto.set(false);
  }

  confirmarDesuscripcion(): void {
    this.error.set('');
    this.mensaje.set('');
    this.accionCargando.set('cancelar-inscripcion');

    this.inscripcionService.cancelarInscripcion(Number(this.cursoId)).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.modalDesuscripcionAbierto.set(false);
        this.mensaje.set(res.mensaje ?? 'Te desuscribiste del curso.');

        this.marcarCursoComoNoInscrito();
      },
      error: (err: any) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  marcarCursoComoNoInscrito(): void {
    const cursoActual = this.curso();

    if (!cursoActual) {
      return;
    }

    this.inscripcionesEstudiante.set(
      this.inscripcionesEstudiante().filter((item: any) => {
        const idCurso =
          Number(item?.curso_id) ||
          Number(item?.curso?.id) ||
          Number(item?.id);

        return idCurso !== Number(this.cursoId);
      })
    );

    const leccionesActualizadas = (cursoActual.lecciones ?? []).map((leccion: any, index: number) => {
      const puedeVer = this.calcularPuedeVerLeccion(leccion, index, false);

      return {
        ...leccion,
        puede_ver: puedeVer,
        bloqueada: !puedeVer,
        motivo_bloqueo: puedeVer ? 'avance' : 'plan',
      };
    });

    this.curso.set({
      ...cursoActual,
      esta_inscrito: false,
      inscripcion_activa: false,
      estado_inscripcion: 'cancelada',
      lecciones: leccionesActualizadas,
    });

    const leccionActual = this.leccionSeleccionada();
    const indexLeccionActual = leccionesActualizadas.findIndex((leccion: any) => String(leccion.id) === String(leccionActual?.id));

    if (leccionActual && indexLeccionActual >= 0 && !this.puedeVerLeccion(leccionesActualizadas[indexLeccionActual], indexLeccionActual)) {
      const primeraDisponible =
        leccionesActualizadas.find((leccion: any, index: number) => this.puedeVerLeccion(leccion, index)) ??
        null;

      this.seleccionarLeccionInterna(primeraDisponible);
    }
  }

  actualizarInscripcionLocal(inscripcion: any): void {
    const idCurso = Number(inscripcion?.curso_id ?? inscripcion?.curso?.id ?? this.cursoId);
    let encontrado = false;

    const actualizadas = this.inscripcionesEstudiante().map((item: any) => {
      const itemCursoId = Number(item?.curso_id ?? item?.curso?.id ?? item?.id);

      if (itemCursoId === idCurso) {
        encontrado = true;
        return {
          ...item,
          ...inscripcion,
          curso_id: idCurso,
        };
      }

      return item;
    });

    if (!encontrado) {
      actualizadas.push({
        ...inscripcion,
        curso_id: idCurso,
      });
    }

    this.inscripcionesEstudiante.set(actualizadas);
  }

  irAPlanes(): void {
    this.cerrarModalBloqueo();
    this.abrirModalPlan('Aquí conectaremos los planes con Stripe. Desde esta ventana el estudiante podrá mejorar su plan para desbloquear más cursos.');
  }

  esLeccionActual(leccion: any): boolean {
    return this.vistaActual() === 'leccion' && String(this.leccionSeleccionada()?.id) === String(leccion.id);
  }

  estadoLeccion(leccion: any, index: number): string {
    if (this.leccionCompletada(leccion)) {
      return 'Completada';
    }

    return this.puedeVerLeccion(leccion, index) ? 'Disponible' : 'Bloqueada';
  }

  claseEstadoLeccion(leccion: any, index: number): string {
    if (this.leccionCompletada(leccion)) {
      return 'completada';
    }

    return this.puedeVerLeccion(leccion, index) ? 'disponible' : 'bloqueada';
  }

  reiniciarEvaluacionActual(): void {
    this.evaluacionActual.set(null);
    this.estadoEvaluacionActual.set(null);
    this.respuestasActuales.set({});
    this.resultadoEvaluacionActual.set(null);
    this.evaluacionAbierta.set(false);
    this.cargandoEvaluacion.set(false);
  }

  cargarEvaluacionDeLeccion(leccion: any): void {
    if (!leccion?.id) {
      return;
    }

    if (!this.estaAutenticado()) {
      return;
    }

    this.cargandoEvaluacion.set(true);
    this.evaluacionActual.set(null);
    this.estadoEvaluacionActual.set(null);
    this.resultadoEvaluacionActual.set(null);
    this.respuestasActuales.set({});

    this.evaluacionService.obtenerEvaluacionLeccion(leccion.id).subscribe({
      next: (res: any) => {
        const evaluacion = res?.evaluacion ?? null;
        this.cargandoEvaluacion.set(false);
        this.evaluacionActual.set(evaluacion);

        if (evaluacion?.id) {
          this.cargarEstadoEvaluacionActual(evaluacion.id);
        }
      },
      error: (err: any) => {
        this.cargandoEvaluacion.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarEstadoEvaluacionActual(evaluacionId: number | string): void {
    this.evaluacionService.obtenerEstadoEvaluacion(evaluacionId).subscribe({
      next: (res: any) => {
        this.estadoEvaluacionActual.set(res);
      },
      error: () => {
        this.estadoEvaluacionActual.set(null);
      },
    });
  }

  preguntasEvaluacionActual(): any[] {
    return this.evaluacionActual()?.preguntas ?? [];
  }

  resultadoCuestionarioVisible(): any | null {
    const resultadoTemporal = this.resultadoEvaluacionActual();

    if (resultadoTemporal) {
      return {
        porcentaje: Number(resultadoTemporal?.porcentaje ?? 0),
        correctas: Number(resultadoTemporal?.correctas ?? 0),
        total: Number(resultadoTemporal?.total_preguntas ?? this.preguntasEvaluacionActual().length),
        intentosRestantes: Number(resultadoTemporal?.intentos_restantes ?? 0),
        aprobado: Number(resultadoTemporal?.porcentaje ?? 0) >= 51,
      };
    }

    const estado = this.estadoEvaluacionActual();
    const intento = estado?.mejor_intento ?? estado?.ultimo_intento ?? null;

    if (!intento) {
      return null;
    }

    return {
      porcentaje: Number(intento?.porcentaje ?? 0),
      correctas: Number(intento?.puntaje ?? 0),
      total: Number(this.preguntasEvaluacionActual().length),
      intentosRestantes: Number(estado?.intentos_restantes ?? 0),
      aprobado: Number(intento?.porcentaje ?? 0) >= 51,
    };
  }

  cuestionarioActualRespondido(): boolean {
    return !!this.resultadoCuestionarioVisible() || Number(this.estadoEvaluacionActual()?.intentos_usados ?? 0) > 0;
  }

  claseResultadoCuestionarioVisible(): string {
    const resultado = this.resultadoCuestionarioVisible();

    if (!resultado) {
      return '';
    }

    return resultado.aprobado ? 'resultado-ok' : 'resultado-error';
  }

  tieneEvaluacionActualLista(): boolean {
    const evaluacion = this.evaluacionActual();

    return !!(evaluacion?.id && Array.isArray(evaluacion?.preguntas) && evaluacion.preguntas.length > 0 && evaluacion.estado === 'activa');
  }

  cuestionarioActualBloqueadoPorIntentos(): boolean {
    const estado = this.estadoEvaluacionActual();
    const mejor = Number(estado?.mejor_intento?.porcentaje ?? this.resultadoEvaluacionActual()?.porcentaje ?? 0);

    return mejor >= 100 || Number(estado?.intentos_restantes ?? 0) <= 0;
  }

  cuestionarioActualPerfecto(): boolean {
    const estado = this.estadoEvaluacionActual();
    const mejor = Number(estado?.mejor_intento?.porcentaje ?? this.resultadoEvaluacionActual()?.porcentaje ?? 0);

    return mejor >= 100;
  }

  puedeReintentarCuestionarioActual(): boolean {
    return !this.cuestionarioActualPerfecto()
      && Number(this.estadoEvaluacionActual()?.intentos_restantes ?? this.resultadoEvaluacionActual()?.intentos_restantes ?? 0) > 0;
  }

  seleccionarRespuestaActual(preguntaId: number | string, opcionId: number | string): void {
    if (this.cuestionarioActualBloqueadoPorIntentos()) {
      return;
    }

    this.respuestasActuales.set({
      ...this.respuestasActuales(),
      [String(preguntaId)]: Number(opcionId),
    });
  }

  respuestaActualSeleccionada(preguntaId: number | string, opcionId: number | string): boolean {
    return Number(this.respuestasActuales()[String(preguntaId)]) === Number(opcionId);
  }

  preguntasActualesRespondidas(): boolean {
    return this.preguntasEvaluacionActual().every((pregunta: any) => !!this.respuestasActuales()[String(pregunta.id)]);
  }

  enviarCuestionarioActual(): void {
    this.error.set('');
    this.mensaje.set('');

    const evaluacion = this.evaluacionActual();

    if (!evaluacion?.id) {
      this.error.set('Este cuestionario todavía no está disponible.');
      return;
    }

    if (!this.esEstudiante()) {
      this.error.set('Solo un estudiante puede responder cuestionarios.');
      return;
    }

    if (!this.preguntasActualesRespondidas()) {
      this.error.set('Responde todas las preguntas antes de enviar el cuestionario.');
      return;
    }

    if (this.cuestionarioActualBloqueadoPorIntentos()) {
      this.mensaje.set('Este cuestionario ya no tiene intentos disponibles. Puedes continuar con la siguiente lección.');
      return;
    }

    const respuestas = this.preguntasEvaluacionActual().map((pregunta: any) => ({
      pregunta_id: pregunta.id,
      opcion_id: this.respuestasActuales()[String(pregunta.id)],
    }));

    this.accionCargando.set('responder-cuestionario');

    this.evaluacionService.responderEvaluacion(evaluacion.id, respuestas).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.resultadoEvaluacionActual.set(res);
        this.evaluacionAbierta.set(false);

        this.estadoEvaluacionActual.set({
          ...(this.estadoEvaluacionActual() ?? {}),
          intentos_usados: res?.intentos_usados ?? this.estadoEvaluacionActual()?.intentos_usados,
          intentos_restantes: res?.intentos_restantes ?? this.estadoEvaluacionActual()?.intentos_restantes,
          aprobado: res?.aprobado ?? this.estadoEvaluacionActual()?.aprobado,
          ultimo_intento: res?.intento ?? null,
          mejor_intento: this.mejorIntentoLocal(this.estadoEvaluacionActual()?.mejor_intento, res?.intento),
        });

        if (res?.inscripcion) {
          this.actualizarInscripcionLocal(res.inscripcion);
        }

        // Resolver el cuestionario ya completa la lección y desbloquea la siguiente.
        if (this.leccionSeleccionada()?.id) {
          this.progresoLecciones.set({
            ...this.progresoLecciones(),
            [String(this.leccionSeleccionada()?.id)]: {
              leccion_id: this.leccionSeleccionada()?.id,
              completado: true,
            },
          });
          this.actualizarPermisosLeccionesDesdeProgreso();
        }

        this.abrirModalResultadoCuestionario(res);
      },
      error: (err: any) => {
        this.accionCargando.set('');
        const mensaje = this.authService.extraerError(err);
        this.error.set(mensaje);
      },
    });
  }

  prepararIntentoCuestionarioActual(): void {
    if (this.puedeReintentarCuestionarioActual() && this.cuestionarioActualRespondido()) {
      this.reintentarCuestionarioActual();
      return;
    }

    this.abrirCerrarCuestionarioActual();
  }

  reintentarCuestionarioActual(): void {
    const leccion = this.leccionSeleccionada();

    if (!leccion) {
      return;
    }

    this.cerrarModalResultado();
    this.resultadoEvaluacionActual.set(null);
    this.respuestasActuales.set({});
    this.evaluacionAbierta.set(true);
    this.cargarEvaluacionDeLeccion(leccion);
  }

  abrirCerrarCuestionarioActual(): void {
    if (!this.tieneEvaluacionActualLista()) {
      return;
    }

    this.evaluacionAbierta.set(!this.evaluacionAbierta());
  }

  puedeAbrirEvaluacionFinal(): boolean {
    if (this.esAdmin() || this.esDocente()) {
      return true;
    }

    if (!this.cursoInscrito()) {
      return false;
    }

    const normales = this.leccionesNormales();

    if (normales.length <= 0) {
      return false;
    }

    return normales.every((leccion: any) => this.leccionCompletada(leccion));
  }

  estadoEvaluacionFinalTexto(): string {
    if (this.certificadoDisponible()) {
      return 'Curso aprobado';
    }

    if (!this.cursoInscrito()) {
      return 'Requiere inscripción';
    }

    if (this.puedeAbrirEvaluacionFinal()) {
      return 'Disponible';
    }

    return 'Completa las lecciones';
  }

  claseEvaluacionFinal(): string {
    if (this.certificadoDisponible()) {
      return 'completada';
    }

    return this.puedeAbrirEvaluacionFinal() ? 'disponible' : 'bloqueada';
  }

  abrirEvaluacionFinal(): void {
    this.mensaje.set('');
    this.error.set('');

    if (!this.puedeAbrirEvaluacionFinal()) {
      this.leccionBloqueada.set({ titulo: 'Evaluación final' });
      this.motivoBloqueo.set(this.cursoInscrito() ? 'avance' : 'plan');
      this.modalBloqueoAbierto.set(true);
      return;
    }

    this.vistaActual.set('evaluacion_final');
    this.leccionSeleccionada.set(null);
    this.videoSeguro.set(null);
    this.videoIdActual.set('');
    this.cargarEvaluacionFinal();
  }

  cargarEvaluacionFinal(): void {
    if (!this.estaAutenticado()) {
      return;
    }

    this.cargandoEvaluacionFinal.set(true);
    this.evaluacionFinal.set(null);
    this.estadoEvaluacionFinal.set(null);
    this.resultadoEvaluacionFinal.set(null);
    this.respuestasFinales.set({});

    this.evaluacionService.obtenerEvaluacionFinal(this.cursoId).subscribe({
      next: (res: any) => {
        const evaluacion = res?.evaluacion ?? null;
        this.cargandoEvaluacionFinal.set(false);
        this.evaluacionFinal.set(evaluacion);

        if (evaluacion?.id) {
          this.cargarEstadoEvaluacionFinal(evaluacion.id);
        }
      },
      error: (err: any) => {
        this.cargandoEvaluacionFinal.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarEstadoEvaluacionFinal(evaluacionId: number | string): void {
    this.evaluacionService.obtenerEstadoEvaluacion(evaluacionId).subscribe({
      next: (res: any) => {
        this.estadoEvaluacionFinal.set(res);
      },
      error: () => {
        this.estadoEvaluacionFinal.set(null);
      },
    });
  }

  preguntasEvaluacionFinal(): any[] {
    return this.evaluacionFinal()?.preguntas ?? [];
  }

  tieneEvaluacionFinalLista(): boolean {
    const evaluacion = this.evaluacionFinal();

    return !!(evaluacion?.id && Array.isArray(evaluacion?.preguntas) && evaluacion.preguntas.length > 0 && evaluacion.estado === 'activa');
  }

  evaluacionFinalBloqueadaPorIntentos(): boolean {
    const estado = this.estadoEvaluacionFinal();
    const mejor = Number(estado?.mejor_intento?.porcentaje ?? this.resultadoEvaluacionFinal()?.porcentaje ?? 0);

    return mejor >= 100 || Number(estado?.intentos_restantes ?? 0) <= 0;
  }

  evaluacionFinalPerfecta(): boolean {
    const estado = this.estadoEvaluacionFinal();
    const mejor = Number(estado?.mejor_intento?.porcentaje ?? this.resultadoEvaluacionFinal()?.porcentaje ?? 0);

    return mejor >= 100;
  }

  puedeReintentarEvaluacionFinal(): boolean {
    return !this.evaluacionFinalPerfecta()
      && Number(this.estadoEvaluacionFinal()?.intentos_restantes ?? this.resultadoEvaluacionFinal()?.intentos_restantes ?? 0) > 0;
  }

  seleccionarRespuestaFinal(preguntaId: number | string, opcionId: number | string): void {
    if (this.evaluacionFinalBloqueadaPorIntentos()) {
      return;
    }

    this.respuestasFinales.set({
      ...this.respuestasFinales(),
      [String(preguntaId)]: Number(opcionId),
    });
  }

  respuestaFinalSeleccionada(preguntaId: number | string, opcionId: number | string): boolean {
    return Number(this.respuestasFinales()[String(preguntaId)]) === Number(opcionId);
  }

  preguntasFinalesRespondidas(): boolean {
    return this.preguntasEvaluacionFinal().every((pregunta: any) => !!this.respuestasFinales()[String(pregunta.id)]);
  }

  enviarEvaluacionFinal(): void {
    this.error.set('');
    this.mensaje.set('');

    const evaluacion = this.evaluacionFinal();

    if (!evaluacion?.id) {
      this.error.set('La evaluación final todavía no está disponible.');
      return;
    }

    if (!this.esEstudiante()) {
      this.error.set('Solo un estudiante puede responder la evaluación final.');
      return;
    }

    if (!this.preguntasFinalesRespondidas()) {
      this.error.set('Responde todas las preguntas antes de enviar la evaluación final.');
      return;
    }

    if (this.evaluacionFinalBloqueadaPorIntentos()) {
      if (!this.certificadoDisponible()) {
        this.abrirConfirmarRepetirCurso();
      }
      return;
    }

    const respuestas = this.preguntasEvaluacionFinal().map((pregunta: any) => ({
      pregunta_id: pregunta.id,
      opcion_id: this.respuestasFinales()[String(pregunta.id)],
    }));

    this.accionCargando.set('responder-final');

    this.evaluacionService.responderEvaluacion(evaluacion.id, respuestas).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.resultadoEvaluacionFinal.set(res);
        this.evaluacionFinalAbierta.set(false);

        this.estadoEvaluacionFinal.set({
          ...(this.estadoEvaluacionFinal() ?? {}),
          intentos_usados: res?.intentos_usados ?? this.estadoEvaluacionFinal()?.intentos_usados,
          intentos_restantes: res?.intentos_restantes ?? this.estadoEvaluacionFinal()?.intentos_restantes,
          aprobado: res?.aprobado ?? this.estadoEvaluacionFinal()?.aprobado,
          ultimo_intento: res?.intento ?? null,
          mejor_intento: this.mejorIntentoLocal(this.estadoEvaluacionFinal()?.mejor_intento, res?.intento),
        });

        if (res?.inscripcion) {
          this.actualizarInscripcionLocal(res.inscripcion);
        }

        if (res?.calificacion?.aprobado_curso || res?.inscripcion?.certificado_disponible) {
          this.mensaje.set('¡Curso aprobado! Tu certificado ya está disponible.');
        }

        this.abrirModalResultadoFinal(res);
      },
      error: (err: any) => {
        this.accionCargando.set('');
        const mensaje = this.authService.extraerError(err);
        this.error.set(mensaje);

        if (mensaje.toLowerCase().includes('repetir')) {
          this.abrirConfirmarRepetirCurso();
        }
      },
    });
  }

  reintentarEvaluacionFinal(): void {
    this.cerrarModalResultado();
    this.resultadoEvaluacionFinal.set(null);
    this.respuestasFinales.set({});
    this.evaluacionFinalAbierta.set(true);
    this.cargarEvaluacionFinal();
  }

  mejorIntentoLocal(actual: any, nuevo: any): any {
    if (!nuevo) {
      return actual ?? null;
    }

    if (!actual) {
      return nuevo;
    }

    return Number(nuevo?.porcentaje ?? 0) > Number(actual?.porcentaje ?? 0) ? nuevo : actual;
  }

  abrirModalResultadoCuestionario(res: any): void {
    const nota = this.redondearNumero(res?.porcentaje ?? 0);
    const perfecto = nota >= 100;
    const intentosRestantes = Number(res?.intentos_restantes ?? 0);

    this.resultadoModal.set({
      tipo: 'cuestionario',
      icono: perfecto ? '🏆' : (res?.aprobado ? '✅' : '🧩'),
      titulo: perfecto
        ? 'Cuestionario perfecto'
        : (res?.aprobado ? 'Cuestionario aprobado' : 'Cuestionario revisado'),
      mensaje: perfecto
        ? 'Lograste la nota máxima. Este cuestionario queda cerrado porque ya no necesitas mejorarlo.'
        : 'La lección quedó completada y la siguiente clase ya se desbloqueó. Tu nota se guardó para el promedio final.',
      nota,
      correctas: res?.correctas ?? 0,
      total: res?.total_preguntas ?? 0,
      intentosRestantes,
      puedeReintentar: !perfecto && intentosRestantes > 0,
      puedeAvanzar: true,
      puedeDescargarCertificado: false,
      debeRepetirCurso: false,
    });

    this.modalResultadoAbierto.set(true);
  }

  abrirModalResultadoFinal(res: any): void {
    const notaEvaluacion = this.redondearNumero(res?.porcentaje ?? 0);
    const notaFinalCurso = this.redondearNumero(res?.calificacion?.nota_final ?? this.notaInscripcion());
    const cursoAprobado = !!(res?.calificacion?.aprobado_curso || res?.inscripcion?.certificado_disponible || notaFinalCurso >= 51);
    const intentosRestantes = Number(res?.intentos_restantes ?? 0);
    const perfecto = notaEvaluacion >= 100;
    const debeRepetirCurso = !cursoAprobado && intentosRestantes <= 0;

    this.resultadoModal.set({
      tipo: 'final',
      icono: cursoAprobado ? '🎓' : '📉',
      titulo: cursoAprobado ? 'Curso aprobado' : 'Evaluación final revisada',
      mensaje: cursoAprobado
        ? 'Tu nota final alcanza para aprobar. Ya puedes descargar tu certificado.'
        : (debeRepetirCurso
            ? 'Tu nota final no alcanza 51 puntos y ya no tienes intentos. Puedes repetir el curso desde cero.'
            : 'Tu nota final todavía no alcanza 51 puntos. Puedes usar otro intento para mejorar.'),
      nota: notaEvaluacion,
      notaFinalCurso,
      correctas: res?.correctas ?? 0,
      total: res?.total_preguntas ?? 0,
      intentosRestantes,
      puedeReintentar: !perfecto && intentosRestantes > 0,
      puedeAvanzar: false,
      puedeDescargarCertificado: cursoAprobado,
      debeRepetirCurso,
    });

    this.modalResultadoAbierto.set(true);
  }

  cerrarModalResultado(): void {
    this.modalResultadoAbierto.set(false);
    this.resultadoModal.set(null);
  }

  accionPrincipalResultado(): void {
    const resultado = this.resultadoModal();

    if (!resultado) {
      return;
    }

    if (resultado.tipo === 'cuestionario') {
      this.irSiguienteLeccionDesdeModal();
      return;
    }

    if (resultado.puedeDescargarCertificado) {
      this.descargarCertificadoPdf();
      this.cerrarModalResultado();
      return;
    }

    if (resultado.debeRepetirCurso) {
      this.cerrarModalResultado();
      this.abrirConfirmarRepetirCurso();
    }
  }

  reintentarDesdeModal(): void {
    const resultado = this.resultadoModal();

    if (!resultado) {
      return;
    }

    if (resultado.tipo === 'cuestionario') {
      this.reintentarCuestionarioActual();
      return;
    }

    this.reintentarEvaluacionFinal();
  }

  irSiguienteLeccionDesdeModal(): void {
    this.cerrarModalResultado();

    const actual = this.leccionSeleccionada();
    const lecciones = this.lecciones();
    const indiceActual = lecciones.findIndex((leccion: any) => String(leccion.id) === String(actual?.id));

    if (indiceActual >= 0) {
      const siguiente = lecciones
        .slice(indiceActual + 1)
        .find((leccion: any, indexRelativo: number) => this.puedeVerLeccion(leccion, indiceActual + 1 + indexRelativo));

      if (siguiente) {
        const indiceSiguiente = lecciones.findIndex((leccion: any) => String(leccion.id) === String(siguiente.id));
        this.seleccionarLeccion(siguiente, indiceSiguiente);
        return;
      }
    }

    if (this.puedeAbrirEvaluacionFinal()) {
      this.abrirEvaluacionFinal();
    }
  }

  abrirConfirmarRepetirCurso(): void {
    this.modalRepetirCursoAbierto.set(true);
  }

  cerrarConfirmarRepetirCurso(): void {
    this.modalRepetirCursoAbierto.set(false);
  }

  confirmarRepetirCurso(): void {
    this.error.set('');
    this.mensaje.set('');
    this.accionCargando.set('repetir-curso');

    this.evaluacionService.repetirCurso(this.cursoId).subscribe({
      next: (res: any) => {
        this.accionCargando.set('');
        this.modalRepetirCursoAbierto.set(false);
        this.mensaje.set(res?.mensaje ?? 'Curso reiniciado correctamente.');

        if (res?.inscripcion) {
          this.actualizarInscripcionLocal(res.inscripcion);
        }

        this.reiniciarEstadoLocalDelCurso();

        const primeraDisponible = this.lecciones().find((leccion: any, index: number) => this.puedeVerLeccion(leccion, index)) ?? null;
        this.seleccionarLeccionInterna(primeraDisponible);
        this.cargarEstadoProgresoCurso();
      },
      error: (err: any) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  reiniciarEstadoLocalDelCurso(): void {
    this.progresoLecciones.set({});
    this.respuestasActuales.set({});
    this.respuestasFinales.set({});
    this.resultadoEvaluacionActual.set(null);
    this.resultadoEvaluacionFinal.set(null);
    this.estadoEvaluacionActual.set(null);
    this.estadoEvaluacionFinal.set(null);
    this.evaluacionAbierta.set(false);
    this.evaluacionFinalAbierta.set(false);

    const cursoActual = this.curso();

    if (!cursoActual) {
      return;
    }

    const leccionesReiniciadas = this.ordenarLecciones(cursoActual.lecciones ?? []).map((leccion: any, index: number) => {
      const puedeVer = this.calcularPuedeVerLeccion(
        {
          ...leccion,
          completada: false,
        },
        index,
        this.cursoInscrito()
      );

      return {
        ...leccion,
        completada: false,
        puede_ver: puedeVer,
        bloqueada: !puedeVer,
        motivo_bloqueo: this.obtenerMotivoBloqueo(this.cursoInscrito(), puedeVer),
      };
    });

    this.curso.set({
      ...cursoActual,
      certificado_disponible: false,
      aprobado: false,
      lecciones: leccionesReiniciadas,
    });
  }

  notaInscripcion(): number {
    const inscripcion = this.inscripcionActual();
    return Number(inscripcion?.nota_final ?? this.curso()?.nota_final ?? 0);
  }

  notaCuestionarios(): number {
    const inscripcion = this.inscripcionActual();
    return Number(inscripcion?.nota_cuestionarios ?? 0);
  }

  notaFinal(): number {
    const inscripcion = this.inscripcionActual();
    return Number(inscripcion?.nota_examen_final ?? 0);
  }

  redondearNumero(valor: any): number {
    return Math.round(Number(valor ?? 0));
  }

  textoNivel(): string {
    const nivel = String(this.curso()?.nivel ?? 'basico').toLowerCase();

    if (nivel === 'basico') {
      return 'Básico';
    }

    if (nivel === 'intermedio') {
      return 'Intermedio';
    }

    if (nivel === 'avanzado') {
      return 'Avanzado';
    }

    return nivel;
  }

  nombreEstudiante(): string {
    const usuario = this.usuario();
    const nombres = `${usuario?.nombres ?? ''} ${usuario?.apellidos ?? ''}`.trim();

    return nombres || usuario?.name || usuario?.email || 'Estudiante';
  }

  descargarCertificadoPdf(): void {
    if (!this.certificadoDisponible()) {
      this.error.set('El certificado estará disponible cuando apruebes el curso.');
      return;
    }

    const datos = {
      nombre: this.nombreEstudiante(),
      curso: this.curso()?.titulo ?? 'Curso',
      docente: this.nombreDocente(),
      area: this.nombreArea(),
      nivel: this.textoNivel(),
      nota: this.redondearNumero(this.notaInscripcion()),
      fecha: new Date().toLocaleDateString('es-BO'),
    };

    this.abrirCertificadoImprimible(datos);
  }

  abrirCertificadoImprimible(datos: any): void {
    const ventana = window.open('', '_blank', 'width=1100,height=800');

    if (!ventana) {
      this.error.set('El navegador bloqueó la ventana del certificado. Permite ventanas emergentes e inténtalo nuevamente.');
      return;
    }

    const nombreArchivo = `certificado-${this.sanitizarNombreArchivo(datos.curso)}.pdf`;

    // Coloca tu logo aquí cuando lo tengas:
    // src/assets/logo-englishnest.png
    const logoUrl = '/assets/logo-englishnest.png';

    ventana.document.open();
    ventana.document.write(`
      <!doctype html>
      <html lang="es">
      <head>
        <meta charset="utf-8">
        <title>${this.escaparHtml(nombreArchivo)}</title>
        <style>
          @page {
            size: A4 landscape;
            margin: 0;
          }

          * {
            box-sizing: border-box;
          }

          body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: #eaf2ff;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
          }

          .certificado {
            width: 1120px;
            height: 760px;
            padding: 46px;
            background:
              radial-gradient(circle at top right, rgba(37, 99, 235, 0.16), transparent 34%),
              radial-gradient(circle at bottom left, rgba(34, 197, 94, 0.12), transparent 30%),
              linear-gradient(135deg, #ffffff, #f8fbff);
            border: 8px solid #2563eb;
            outline: 2px solid #bfdbfe;
            outline-offset: -22px;
            position: relative;
            overflow: hidden;
          }

          .marca-agua {
            position: absolute;
            right: -45px;
            bottom: -80px;
            font-size: 190px;
            font-weight: 900;
            color: rgba(37, 99, 235, 0.055);
            letter-spacing: -14px;
            transform: rotate(-8deg);
            user-select: none;
          }

          .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 28px;
            position: relative;
            z-index: 2;
          }

          .brand {
            display: flex;
            align-items: center;
            gap: 18px;
          }

          .logo-box {
            width: 86px;
            height: 86px;
            border-radius: 28px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 18px 36px rgba(37, 99, 235, 0.22);
            overflow: hidden;
          }

          .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
            background: white;
          }

          .logo-fallback {
            color: white;
            font-weight: 900;
            font-size: 28px;
            letter-spacing: -2px;
          }

          .brand h1 {
            margin: 0;
            font-size: 34px;
            letter-spacing: -2px;
          }

          .brand p {
            margin: 4px 0 0;
            color: #64748b;
            font-weight: 700;
          }

          .folio {
            text-align: right;
            color: #475569;
            font-weight: 800;
            font-size: 14px;
          }

          .contenido {
            margin-top: 72px;
            text-align: center;
            position: relative;
            z-index: 2;
          }

          .subtitulo {
            display: inline-flex;
            padding: 10px 18px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 13px;
          }

          h2 {
            margin: 20px 0 10px;
            font-size: 54px;
            line-height: 1;
            letter-spacing: -3px;
          }

          .otorgado {
            margin: 28px 0 0;
            color: #64748b;
            font-size: 18px;
            font-weight: 700;
          }

          .nombre {
            margin: 10px auto 0;
            max-width: 850px;
            font-size: 42px;
            font-weight: 900;
            letter-spacing: -2px;
            color: #0f172a;
            padding-bottom: 16px;
            border-bottom: 2px solid #bfdbfe;
          }

          .curso-label {
            margin-top: 24px;
            color: #64748b;
            font-size: 17px;
            font-weight: 700;
          }

          .curso {
            margin-top: 8px;
            font-size: 30px;
            font-weight: 900;
            color: #1d4ed8;
          }

          .datos-grid {
            width: 86%;
            margin: 36px auto 0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
          }

          .dato {
            background: #f8fafc;
            border: 1px solid #dbeafe;
            border-radius: 18px;
            padding: 15px 12px;
          }

          .dato span {
            display: block;
            color: #64748b;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .6px;
          }

          .dato strong {
            display: block;
            margin-top: 7px;
            color: #0f172a;
            font-size: 16px;
          }

          .firmas {
            width: 86%;
            margin: 50px auto 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: end;
          }

          .firma {
            border-top: 2px solid #cbd5e1;
            padding-top: 12px;
            color: #334155;
            font-weight: 900;
          }

          .firma small {
            display: block;
            margin-top: 4px;
            color: #64748b;
            font-weight: 700;
          }

          .nota-final {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            height: 58px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            font-size: 24px;
            font-weight: 900;
          }

          .pie {
            position: absolute;
            left: 46px;
            right: 46px;
            bottom: 28px;
            display: flex;
            justify-content: space-between;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
          }

          @media print {
            body {
              background: white;
            }

            .certificado {
              width: 100vw;
              height: 100vh;
              border-width: 7px;
            }
          }
        </style>
      </head>
      <body>
        <section class="certificado">
          <div class="marca-agua">EN</div>

          <div class="top">
            <div class="brand">
              <div class="logo-box">
                <img src="${logoUrl}" alt="EnglishNest" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <span class="logo-fallback" style="display:none;">EN</span>
              </div>

              <div>
                <h1>EnglishNest</h1>
                <p>Inglés profesional para tu carrera</p>
              </div>
            </div>

            <div class="folio">
              Certificado digital<br>
              Emitido: ${this.escaparHtml(datos.fecha)}
            </div>
          </div>

          <div class="contenido">
            <span class="subtitulo">Certificado de finalización</span>
            <h2>Curso aprobado</h2>

            <p class="otorgado">Otorgado a</p>
            <div class="nombre">${this.escaparHtml(datos.nombre)}</div>

            <p class="curso-label">Por haber completado y aprobado satisfactoriamente el curso</p>
            <div class="curso">${this.escaparHtml(datos.curso)}</div>

            <div class="datos-grid">
              <div class="dato">
                <span>Área</span>
                <strong>${this.escaparHtml(datos.area)}</strong>
              </div>

              <div class="dato">
                <span>Nivel</span>
                <strong>${this.escaparHtml(datos.nivel)}</strong>
              </div>

              <div class="dato">
                <span>Docente</span>
                <strong>${this.escaparHtml(datos.docente)}</strong>
              </div>

              <div class="dato">
                <span>Nota final</span>
                <strong class="nota-final">${this.escaparHtml(String(datos.nota))}</strong>
              </div>
            </div>

            <div class="firmas">
              <div class="firma">
                EnglishNest
                <small>Plataforma académica</small>
              </div>

              <div class="firma">
                ${this.escaparHtml(datos.docente)}
                <small>Docente titular</small>
              </div>
            </div>
          </div>

          <div class="pie">
            <span>Este certificado fue generado por EnglishNest.</span>
            <span>${this.escaparHtml(nombreArchivo)}</span>
          </div>
        </section>

        <script>
          window.onload = function () {
            setTimeout(function () {
              window.focus();
              window.print();
            }, 500);
          };
        </script>
      </body>
      </html>
    `);
    ventana.document.close();
  }

  escaparHtml(texto: string): string {
    return String(texto ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  sanitizarNombreArchivo(texto: string): string {
    return String(texto ?? 'curso')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'curso';
  }

  extraerYoutubeId(url: string): string | null {
    const texto = String(url).trim();

    const patrones = [
      /youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/,
      /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
      /youtu\.be\/([a-zA-Z0-9_-]{11})/,
      /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/,
    ];

    for (const patron of patrones) {
      const resultado = texto.match(patron);

      if (resultado && resultado[1]) {
        return resultado[1];
      }
    }

    return null;
  }

  volverAEdicion(): void {
    this.router.navigate(['/editar-curso', this.cursoId]);
  }

  volverACursos(): void {
    this.router.navigate(['/cursos']);
  }

  accionPendiente(nombre: string): void {
    this.mensaje.set(`${nombre} se conectará en la siguiente fase.`);
  }
}
