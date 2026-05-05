import { CommonModule } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import {
  FormBuilder,
  FormGroup,
  FormsModule,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { CursoService } from '../../servicios/curso';
import { AuthService } from '../../servicios/auth';
import { EvaluacionService } from '../../servicios/evaluacion';

@Component({
  selector: 'app-editar-curso',
  standalone: true,
  imports: [CommonModule, RouterModule, ReactiveFormsModule, FormsModule],
  templateUrl: './editar-curso.html',
  styleUrl: './editar-curso.css',
})
export class EditarCurso implements OnInit {
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private fb = inject(FormBuilder);
  private cursoService = inject(CursoService);
  private authService = inject(AuthService);
  private evaluacionService = inject(EvaluacionService);

  usuario = this.authService.usuarioActual;
  rolActual = this.authService.rolActual;

  cursoId = '';
  curso = signal<any | null>(null);
  areas = signal<any[]>([]);

  mensaje = signal('');
  error = signal('');

  cargandoCurso = signal(false);
  guardandoCurso = signal(false);
  guardandoLeccion = signal(false);
  accionCargando = signal('');

  modoEdicionLeccion = false;
  leccionIdEdicion: number | string | null = null;

  modalRechazoAbierto = false;
  motivoRechazo = '';

  modalQuitarAbierto = false;
  leccionParaQuitar: any | null = null;

  modalEvaluacionAbierto = signal(false);
  cargandoEvaluacion = signal(false);
  guardandoEvaluacion = signal(false);
  evaluacionActual = signal<any | null>(null);
  tipoEvaluacionActual = signal<'quiz_leccion' | 'examen_final'>('quiz_leccion');
  leccionEvaluacionActual: any | null = null;
  tituloEvaluacion = '';
  preguntasEvaluacion: any[] = [];

  formularioCurso: FormGroup;
  formularioLeccion: FormGroup;

  constructor() {
    this.formularioCurso = this.fb.group({
      titulo: ['', [Validators.required]],
      descripcion: ['', [Validators.required]],
      nivel: ['basico', [Validators.required]],
      area_id: ['', [Validators.required]],
    });

    this.formularioLeccion = this.fb.group({
      titulo: ['', [Validators.required]],
      descripcion: [''],
      contenido_texto: [''],
      url_video: [''],
      tipo: ['normal', [Validators.required]],
      es_gratis: [false],
    });
  }

  ngOnInit(): void {
    this.cursoId = this.route.snapshot.paramMap.get('id') ?? '';

    if (!this.cursoId) {
      this.error.set('No se encontró el ID del curso en la URL.');
      return;
    }

    this.cargarSesionYCargarCurso();
  }

  cargarSesionYCargarCurso(): void {
    this.error.set('');

    this.authService.me().subscribe({
      next: (res) => {
        this.authService.usuarioActual.set(res.usuario);
        this.cargarAreas();
        this.cargarCurso();
      },
      error: (err) => {
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarAreas(): void {
    this.cursoService.obtenerAreas().subscribe({
      next: (res) => {
        this.areas.set(res.areas ?? []);
      },
      error: () => {
        this.areas.set([]);
      },
    });
  }

  cargarCurso(): void {
    this.mensaje.set('');
    this.error.set('');
    this.cargandoCurso.set(true);

    this.cursoService.obtenerCursoEdicion(this.cursoId).subscribe({
      next: (res) => {
        const curso = res.curso;

        this.curso.set(curso);

        this.formularioCurso.patchValue({
          titulo: curso.titulo,
          descripcion: curso.descripcion,
          nivel: curso.nivel ?? 'basico',
          area_id: curso.area_id,
        });

        this.cargandoCurso.set(false);
      },
      error: (err) => {
        this.cargandoCurso.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  esAdmin(): boolean {
    return this.rolActual() === 'admin';
  }

  esDocente(): boolean {
    return this.rolActual() === 'docente';
  }

  verComoEstudiante(): void {
    this.router.navigate(['/curso', this.cursoId], {
      queryParams: {
        preview: 1,
        volver: `/editar-curso/${this.cursoId}`,
      },
    });
  }

  textoEstado(estado: string): string {
    const estados: any = {
      borrador: 'Borrador de edición',
      pendiente_revision: 'Pendiente de revisión',
      publicado: 'Publicado',
      archivado: 'Oculto',
      oculto: 'Oculto',
      rechazado: 'Rechazado',
    };

    return estados[estado] ?? estado;
  }

  claseEstado(estado: string): string {
    return `estado-${estado}`;
  }

  textoEstadoLeccion(leccion: any): string {
    const estado = leccion?._estado_edicion ?? 'oficial';

    const textos: any = {
      oficial: '',
      nueva: 'Nueva',
      modificada: 'Modificada',
    };

    return textos[estado] ?? '';
  }

  claseEstadoLeccion(leccion: any): string {
    return `badge-edicion-${leccion?._estado_edicion ?? 'oficial'}`;
  }

  guardarDatosCurso(): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.formularioCurso.invalid) {
      this.error.set('Completa correctamente los datos del curso.');
      return;
    }

    this.guardandoCurso.set(true);

    this.cursoService.actualizarCursoEdicion(this.cursoId, this.formularioCurso.value).subscribe({
      next: (res) => {
        this.guardandoCurso.set(false);
        this.mensaje.set(res.mensaje ?? 'Cambios guardados en borrador.');
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.guardandoCurso.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  editarLeccion(leccion: any): void {
    this.modoEdicionLeccion = true;
    this.leccionIdEdicion = leccion.id;

    this.formularioLeccion.patchValue({
      titulo: leccion.titulo,
      descripcion: leccion.descripcion,
      contenido_texto: leccion.contenido_texto,
      url_video: leccion.url_video,
      tipo: leccion.tipo ?? 'normal',
      es_gratis: leccion.es_gratis ?? false,
    });

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  cancelarEdicionLeccion(): void {
    this.modoEdicionLeccion = false;
    this.leccionIdEdicion = null;

    this.formularioLeccion.reset({
      titulo: '',
      descripcion: '',
      contenido_texto: '',
      url_video: '',
      tipo: 'normal',
      es_gratis: false,
    });
  }

  guardarLeccion(): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.formularioLeccion.invalid) {
      this.error.set('La lección debe tener al menos un título.');
      return;
    }

    this.guardandoLeccion.set(true);

    if (this.modoEdicionLeccion && this.leccionIdEdicion) {
      this.cursoService
        .actualizarLeccion(this.leccionIdEdicion, this.formularioLeccion.value)
        .subscribe({
          next: (res) => {
            this.guardandoLeccion.set(false);
            this.mensaje.set(res.mensaje ?? 'Lección actualizada en borrador.');
            this.cancelarEdicionLeccion();
            this.curso.set(res.curso);
          },
          error: (err) => {
            this.guardandoLeccion.set(false);
            this.error.set(this.authService.extraerError(err));
          },
        });

      return;
    }

    this.cursoService.crearLeccion(this.cursoId, this.formularioLeccion.value).subscribe({
      next: (res) => {
        this.guardandoLeccion.set(false);
        this.mensaje.set(res.mensaje ?? 'Lección creada en borrador.');
        this.cancelarEdicionLeccion();
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.guardandoLeccion.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  abrirModalQuitar(leccion: any): void {
    this.leccionParaQuitar = leccion;
    this.modalQuitarAbierto = true;
    this.error.set('');
    this.mensaje.set('');
  }

  cerrarModalQuitar(): void {
    this.leccionParaQuitar = null;
    this.modalQuitarAbierto = false;
  }

  confirmarQuitarLeccion(): void {
    if (!this.leccionParaQuitar) {
      return;
    }

    const leccion = this.leccionParaQuitar;

    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('quitar-' + leccion.id);

    this.cursoService.eliminarLeccion(leccion.id).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Lección quitada del borrador.');
        this.curso.set(res.curso);
        this.cerrarModalQuitar();
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
        this.cerrarModalQuitar();
      },
    });
  }

  enviarRevision(solicitaPublicacion: boolean): void {
    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('revision');

    this.cursoService.enviarRevision(this.cursoId, solicitaPublicacion).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso enviado a revisión correctamente.');
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  aprobarRevision(): void {
    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('aprobar');

    this.cursoService.aprobarRevision(this.cursoId).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Edición aprobada y publicada correctamente.');
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  abrirModalRechazo(): void {
    this.modalRechazoAbierto = true;
    this.motivoRechazo = '';
    this.error.set('');
  }

  cerrarModalRechazo(): void {
    this.modalRechazoAbierto = false;
    this.motivoRechazo = '';
  }

  rechazarRevision(): void {
    if (!this.motivoRechazo.trim() || this.motivoRechazo.trim().length < 5) {
      this.error.set('Escribe un motivo claro para rechazar el curso.');
      return;
    }

    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('rechazar');

    this.cursoService.rechazarRevision(this.cursoId, this.motivoRechazo.trim()).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Edición rechazada correctamente.');
        this.curso.set(res.curso);
        this.cerrarModalRechazo();
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }


  esIntroduccion(leccion: any): boolean {
    return leccion?.tipo === 'introduccion' || Number(leccion?.orden ?? 0) === 1;
  }

  textoBotonCuestionario(leccion: any): string {
    if (this.esIntroduccion(leccion)) {
      return 'Sin cuestionario';
    }

    return 'Cuestionario';
  }

  abrirCuestionarioLeccion(leccion: any): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.esIntroduccion(leccion)) {
      this.error.set('La introducción no debe tener cuestionario. El primer cuestionario empieza en la lección 1.');
      return;
    }

    this.tipoEvaluacionActual.set('quiz_leccion');
    this.leccionEvaluacionActual = leccion;
    this.modalEvaluacionAbierto.set(true);
    this.cargandoEvaluacion.set(true);
    this.evaluacionActual.set(null);
    this.tituloEvaluacion = 'Cuestionario de la lección';
    this.preguntasEvaluacion = [];

    this.evaluacionService.obtenerEvaluacionLeccionEdicion(this.cursoId, leccion.id).subscribe({
      next: (res) => {
        const evaluacion = res.evaluacion;
        this.evaluacionActual.set(evaluacion);
        this.tituloEvaluacion = evaluacion?.titulo ?? 'Cuestionario de la lección';
        this.preguntasEvaluacion = this.normalizarPreguntas(evaluacion, 3);
        this.cargandoEvaluacion.set(false);
      },
      error: (err) => {
        this.cargandoEvaluacion.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  abrirEvaluacionFinal(): void {
    this.mensaje.set('');
    this.error.set('');

    this.tipoEvaluacionActual.set('examen_final');
    this.leccionEvaluacionActual = null;
    this.modalEvaluacionAbierto.set(true);
    this.cargandoEvaluacion.set(true);
    this.evaluacionActual.set(null);
    this.tituloEvaluacion = 'Evaluación final';
    this.preguntasEvaluacion = [];

    this.evaluacionService.obtenerEvaluacionFinalEdicion(this.cursoId).subscribe({
      next: (res) => {
        const evaluacion = res.evaluacion;
        this.evaluacionActual.set(evaluacion);
        this.tituloEvaluacion = evaluacion?.titulo ?? 'Evaluación final';
        this.preguntasEvaluacion = this.normalizarPreguntas(evaluacion, 6);
        this.cargandoEvaluacion.set(false);
      },
      error: (err) => {
        this.cargandoEvaluacion.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cerrarModalEvaluacion(): void {
    this.modalEvaluacionAbierto.set(false);
    this.cargandoEvaluacion.set(false);
    this.guardandoEvaluacion.set(false);
    this.evaluacionActual.set(null);
    this.leccionEvaluacionActual = null;
    this.tituloEvaluacion = '';
    this.preguntasEvaluacion = [];
  }

  normalizarPreguntas(evaluacion: any, minimo: number): any[] {
    const preguntas = Array.isArray(evaluacion?.preguntas) ? evaluacion.preguntas : [];

    if (preguntas.length > 0) {
      return preguntas.map((pregunta: any, index: number) => ({
        id: pregunta.id ?? null,
        pregunta: pregunta.pregunta ?? '',
        orden: pregunta.orden ?? index + 1,
        opciones: this.normalizarOpciones(pregunta.opciones ?? []),
      }));
    }

    return Array.from({ length: minimo }, (_, index) => this.crearPreguntaVacia(index));
  }

  normalizarOpciones(opciones: any[]): any[] {
    const opcionesBase = [...opciones];

    while (opcionesBase.length < 3) {
      opcionesBase.push({ texto: '', es_correcta: false });
    }

    return opcionesBase.slice(0, 3).map((opcion: any, index: number) => ({
      id: opcion.id ?? null,
      texto: opcion.texto ?? '',
      es_correcta: opcion.es_correcta === true || opcion.es_correcta === 1 || (index === 0 && !opciones.some((o) => o.es_correcta)),
      orden: index + 1,
    }));
  }

  crearPreguntaVacia(index: number): any {
    return {
      pregunta: '',
      orden: index + 1,
      opciones: [
        { texto: '', es_correcta: true, orden: 1 },
        { texto: '', es_correcta: false, orden: 2 },
        { texto: '', es_correcta: false, orden: 3 },
      ],
    };
  }

  minimoPreguntasEvaluacion(): number {
    return this.tipoEvaluacionActual() === 'examen_final' ? 6 : 3;
  }

  maximoPreguntasEvaluacion(): number {
    return this.tipoEvaluacionActual() === 'examen_final' ? 10 : 6;
  }

  textoTipoEvaluacion(): string {
    return this.tipoEvaluacionActual() === 'examen_final'
      ? 'Evaluación final'
      : 'Cuestionario de lección';
  }

  descripcionTipoEvaluacion(): string {
    if (this.tipoEvaluacionActual() === 'examen_final') {
      return 'Debe tener mínimo 6 preguntas y máximo 10. Esta evaluación suma 30 puntos de la nota final.';
    }

    return 'Debe tener mínimo 3 preguntas y máximo 6. Los cuestionarios de lección suman 70 puntos de la nota final.';
  }

  agregarPreguntaEvaluacion(): void {
    if (this.preguntasEvaluacion.length >= this.maximoPreguntasEvaluacion()) {
      this.error.set(`Máximo permitido: ${this.maximoPreguntasEvaluacion()} preguntas.`);
      return;
    }

    this.preguntasEvaluacion.push(this.crearPreguntaVacia(this.preguntasEvaluacion.length));
  }

  quitarPreguntaEvaluacion(index: number): void {
    if (this.preguntasEvaluacion.length <= this.minimoPreguntasEvaluacion()) {
      this.error.set(`Mínimo requerido: ${this.minimoPreguntasEvaluacion()} preguntas.`);
      return;
    }

    this.preguntasEvaluacion.splice(index, 1);
    this.reordenarPreguntasEvaluacion();
  }

  reordenarPreguntasEvaluacion(): void {
    this.preguntasEvaluacion = this.preguntasEvaluacion.map((pregunta, index) => ({
      ...pregunta,
      orden: index + 1,
    }));
  }

  marcarOpcionCorrecta(indicePregunta: number, indiceOpcion: number): void {
    const pregunta = this.preguntasEvaluacion[indicePregunta];

    if (!pregunta) {
      return;
    }

    pregunta.opciones = pregunta.opciones.map((opcion: any, index: number) => ({
      ...opcion,
      es_correcta: index === indiceOpcion,
    }));
  }

  validarEvaluacionAntesDeGuardar(): boolean {
    const minimo = this.minimoPreguntasEvaluacion();
    const maximo = this.maximoPreguntasEvaluacion();

    if (!this.tituloEvaluacion.trim()) {
      this.error.set('Escribe el título de la evaluación.');
      return false;
    }

    if (this.preguntasEvaluacion.length < minimo || this.preguntasEvaluacion.length > maximo) {
      this.error.set(`La evaluación debe tener entre ${minimo} y ${maximo} preguntas.`);
      return false;
    }

    for (const [indicePregunta, pregunta] of this.preguntasEvaluacion.entries()) {
      if (!String(pregunta.pregunta ?? '').trim()) {
        this.error.set(`Completa el texto de la pregunta ${indicePregunta + 1}.`);
        return false;
      }

      if (!Array.isArray(pregunta.opciones) || pregunta.opciones.length !== 3) {
        this.error.set(`La pregunta ${indicePregunta + 1} debe tener exactamente 3 opciones.`);
        return false;
      }

      const correctas = pregunta.opciones.filter((opcion: any) => opcion.es_correcta === true).length;

      if (correctas !== 1) {
        this.error.set(`La pregunta ${indicePregunta + 1} debe tener exactamente una opción correcta.`);
        return false;
      }

      for (const [indiceOpcion, opcion] of pregunta.opciones.entries()) {
        if (!String(opcion.texto ?? '').trim()) {
          this.error.set(`Completa la opción ${indiceOpcion + 1} de la pregunta ${indicePregunta + 1}.`);
          return false;
        }
      }
    }

    return true;
  }

  guardarEvaluacionActual(): void {
    this.mensaje.set('');
    this.error.set('');

    if (!this.validarEvaluacionAntesDeGuardar()) {
      return;
    }

    const datos = {
      titulo: this.tituloEvaluacion.trim(),
      preguntas: this.preguntasEvaluacion.map((pregunta, indicePregunta) => ({
        pregunta: String(pregunta.pregunta).trim(),
        orden: indicePregunta + 1,
        opciones: pregunta.opciones.map((opcion: any, indiceOpcion: number) => ({
          texto: String(opcion.texto).trim(),
          es_correcta: opcion.es_correcta === true,
          orden: indiceOpcion + 1,
        })),
      })),
    };

    this.guardandoEvaluacion.set(true);

    const peticion = this.tipoEvaluacionActual() === 'examen_final'
      ? this.evaluacionService.guardarEvaluacionFinalEdicion(this.cursoId, datos)
      : this.evaluacionService.guardarEvaluacionLeccionEdicion(this.cursoId, this.leccionEvaluacionActual?.id, datos);

    peticion.subscribe({
      next: (res) => {
        this.guardandoEvaluacion.set(false);
        this.mensaje.set(res.mensaje ?? 'Evaluación guardada correctamente.');

        if (res.curso) {
          this.curso.set(res.curso);
        }

        this.cerrarModalEvaluacion();
      },
      error: (err) => {
        this.guardandoEvaluacion.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  publicarCurso(): void {
    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('publicar');

    this.cursoService.publicarCurso(this.cursoId).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso publicado correctamente.');
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  ocultarCurso(): void {
    this.mensaje.set('');
    this.error.set('');
    this.accionCargando.set('ocultar');

    this.cursoService.ocultarCurso(this.cursoId).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso ocultado correctamente.');
        this.curso.set(res.curso);
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }
}