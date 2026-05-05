import { CommonModule } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, FormGroup, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../servicios/auth';
import { AdminService } from '../../servicios/admin';
import { CursoService } from '../../servicios/curso';
import { InscripcionService } from '../../servicios/inscripcion';
import { DocentePerfilService } from '../../servicios/docente-perfil';

@Component({
  selector: 'app-panel',
  standalone: true,
  imports: [CommonModule, RouterModule, ReactiveFormsModule, FormsModule],
  templateUrl: './panel.html',
  styleUrl: './panel.css',
})
export class Panel implements OnInit {
  private authService = inject(AuthService);
  private adminService = inject(AdminService);
  private cursoService = inject(CursoService);
  private inscripcionService = inject(InscripcionService);
  private docentePerfilService = inject(DocentePerfilService);
  private router = inject(Router);
  private fb = inject(FormBuilder);

  usuario = this.authService.usuarioActual;

  rolPanel = signal('');

  docentesPendientes = signal<any[]>([]);
  docentes = signal<any[]>([]);
  areas = signal<any[]>([]);
  inscripciones = signal<any[]>([]);
  perfilDocente = signal<any | null>(null);

  cargando = signal(false);
  accionCargando = signal('');
  mensaje = signal('');
  error = signal('');

  modalDocenteAbierto = signal(false);
  cargandoDetalleDocente = signal(false);
  docenteSeleccionado = signal<any | null>(null);
  motivoRechazo = '';

  formularioCurso: FormGroup;
  formularioPerfilDocente: FormGroup;

  constructor() {
    this.formularioCurso = this.fb.group({
      titulo: ['', [Validators.required]],
      descripcion: ['', [Validators.required]],
      docente_id: ['', [Validators.required]],
      area_id: ['', [Validators.required]],
      nivel: ['basico', [Validators.required]],
    });

    this.formularioPerfilDocente = this.fb.group({
      especialidad: ['', [Validators.required, Validators.minLength(3)]],
      estudios: [''],
      biografia: [''],
    });
  }

  ngOnInit(): void {
    this.cargarSesion();
  }

  cargarSesion(): void {
    this.cargando.set(true);
    this.error.set('');
    this.mensaje.set('');

    this.authService.me().subscribe({
      next: (res) => {
        this.authService.usuarioActual.set(res.usuario);

        const rol = this.obtenerRol(res.usuario);
        this.rolPanel.set(rol);

        if (this.esAdmin()) {
          this.cargarPanelAdmin();
          return;
        }

        if (this.esDocente()) {
          this.cargarPanelDocente();
          return;
        }

        if (this.esEstudiante()) {
          this.cargarMisCursosEstudiante();
          return;
        }

        this.cargando.set(false);
      },
      error: () => {
        this.cargando.set(false);
        this.router.navigate(['/login']);
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
    return this.rolPanel() === 'admin';
  }

  esDocente(): boolean {
    return this.rolPanel() === 'docente';
  }

  esEstudiante(): boolean {
    return this.rolPanel() === 'estudiante';
  }

  nombreUsuario(): string {
    const usuario = this.usuario();

    return `${usuario?.nombres ?? ''} ${usuario?.apellidos ?? ''}`.trim();
  }

  cargarPanelAdmin(): void {
    this.cargarAreas();
    this.cargarDocentes();
    this.cargarDocentesPendientes();

    this.cargando.set(false);
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

  cargarDocentes(): void {
    this.adminService.obtenerDocentesAprobados().subscribe({
      next: (res) => {
        this.docentes.set(res.docentes ?? []);
      },
      error: () => {
        this.docentes.set([]);
      },
    });
  }

  cargarDocentesPendientes(): void {
    this.adminService.obtenerDocentesPendientes().subscribe({
      next: (res) => {
        this.docentesPendientes.set(res.docentes ?? []);
      },
      error: () => {
        this.docentesPendientes.set([]);
      },
    });
  }

  crearCurso(): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.formularioCurso.invalid) {
      this.error.set('Completa título, descripción, docente, área y nivel.');
      return;
    }

    this.accionCargando.set('crear-curso');

    this.cursoService.crearCurso(this.formularioCurso.value).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Curso creado correctamente.');

        this.formularioCurso.reset({
          titulo: '',
          descripcion: '',
          docente_id: '',
          area_id: '',
          nivel: 'basico',
        });
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarPanelDocente(): void {
    this.docentePerfilService.obtenerMiPerfil().subscribe({
      next: (res) => {
        const perfil = res.perfil;
        const cambios = perfil?.cambios_pendientes_json ?? null;

        this.perfilDocente.set(perfil);

        this.formularioPerfilDocente.patchValue({
          especialidad: cambios?.especialidad ?? perfil?.especialidad ?? '',
          estudios: cambios?.estudios ?? perfil?.estudios ?? '',
          biografia: cambios?.biografia ?? perfil?.biografia ?? '',
        });

        this.cargando.set(false);
      },
      error: (err) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  guardarPerfilDocente(): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.formularioPerfilDocente.invalid) {
      this.error.set('Completa al menos la especialidad.');
      return;
    }

    this.accionCargando.set('guardar-perfil-docente');

    this.docentePerfilService.actualizarPerfil(this.formularioPerfilDocente.value).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Perfil docente actualizado correctamente.');
        this.perfilDocente.set(res.perfil);
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  reenviarSolicitudDocente(): void {
    this.mensaje.set('');
    this.error.set('');

    if (this.formularioPerfilDocente.invalid) {
      this.error.set('Completa al menos la especialidad antes de reenviar.');
      return;
    }

    this.accionCargando.set('reenviar-solicitud-docente');

    this.docentePerfilService.actualizarPerfil(this.formularioPerfilDocente.value).subscribe({
      next: () => {
        this.docentePerfilService.reenviarSolicitud().subscribe({
          next: (res) => {
            this.accionCargando.set('');
            this.mensaje.set(res.mensaje ?? 'Solicitud reenviada correctamente.');
            this.perfilDocente.set(res.perfil);
          },
          error: (err) => {
            this.accionCargando.set('');
            this.error.set(this.authService.extraerError(err));
          },
        });
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  estadoPerfilDocente(): string {
    return String(this.perfilDocente()?.estado_aprobacion ?? 'pendiente').toLowerCase();
  }

  textoEstadoPerfilDocente(): string {
    const estado = this.estadoPerfilDocente();

    const textos: any = {
      pendiente: 'Pendiente de aprobación',
      aprobado: 'Aprobado',
      rechazado: 'Rechazado',
    };

    return textos[estado] ?? estado;
  }

  claseEstadoPerfilDocente(): string {
    return `estado-docente-${this.estadoPerfilDocente()}`;
  }

  perfilDocenteAprobado(): boolean {
    return this.estadoPerfilDocente() === 'aprobado';
  }

  perfilDocentePendiente(): boolean {
    return this.estadoPerfilDocente() === 'pendiente';
  }

  perfilDocenteRechazado(): boolean {
    return this.estadoPerfilDocente() === 'rechazado';
  }

  motivoRechazoDocente(): string {
    return this.perfilDocente()?.motivo_rechazo ?? '';
  }

  estadoCambiosPerfilDocente(): string {
    return String(this.perfilDocente()?.estado_revision_cambios ?? '').toLowerCase();
  }

  tieneCambiosPendientesPerfilDocente(): boolean {
    return this.estadoCambiosPerfilDocente() === 'pendiente';
  }

  tieneCambiosRechazadosPerfilDocente(): boolean {
    return this.estadoCambiosPerfilDocente() === 'rechazado';
  }

  motivoRechazoCambiosDocente(): string {
    return this.perfilDocente()?.motivo_rechazo_cambios ?? '';
  }

  puedeReenviarSolicitudDocente(): boolean {
    return this.perfilDocenteRechazado();
  }

  textoBotonGuardarPerfilDocente(): string {
    if (this.perfilDocenteAprobado()) {
      if (this.tieneCambiosRechazadosPerfilDocente()) {
        return 'Enviar cambios corregidos a revisión';
      }

      return 'Enviar cambios a revisión';
    }

    return 'Guardar cambios';
  }

  perfilDocenteCorreo(): string {
    return this.perfilDocente()?.usuario?.email ?? this.usuario()?.email ?? '';
  }

  perfilDocenteCorreoVerificado(): boolean {
    return !!(
      this.perfilDocente()?.usuario?.email_verified_at ||
      this.usuario()?.email_verified_at
    );
  }

  perfilDocenteData(docente: any): any {
    return docente?.perfil_docente ?? docente?.perfilDocente ?? docente ?? {};
  }

  usuarioDocente(docente: any): any {
    return docente?.usuario ?? docente ?? {};
  }

  usuarioIdDocente(docente: any): number {
    return Number(
      docente?.usuario_id ??
      docente?.usuario?.id ??
      docente?.id ??
      0
    );
  }

  nombreDocente(docente: any): string {
    const usuario = this.usuarioDocente(docente);

    return `${usuario?.nombres ?? ''} ${usuario?.apellidos ?? ''}`.trim() || 'Docente sin nombre';
  }

  correoDocente(docente: any): string {
    const usuario = this.usuarioDocente(docente);

    return usuario?.email ?? 'Sin correo';
  }

  especialidadDocente(docente: any): string {
    return this.perfilDocenteData(docente)?.especialidad ?? 'Sin especialidad registrada';
  }

  estudiosDocente(docente: any): string {
    return this.perfilDocenteData(docente)?.estudios ?? 'Sin estudios registrados';
  }

  biografiaDocente(docente: any): string {
    return this.perfilDocenteData(docente)?.biografia ?? 'Sin biografía registrada';
  }

  estadoDocente(docente: any): string {
    return String(this.perfilDocenteData(docente)?.estado_aprobacion ?? 'pendiente').toLowerCase();
  }

  textoEstadoDocente(docente: any): string {
    const estado = this.estadoDocente(docente);

    const textos: any = {
      pendiente: 'Pendiente de aprobación',
      aprobado: 'Aprobado',
      rechazado: 'Rechazado',
    };

    return textos[estado] ?? estado;
  }

  correoVerificadoDocente(docente: any): boolean {
    const usuario = this.usuarioDocente(docente);

    return !!usuario?.email_verified_at;
  }

  textoTipoRevisionDocente(docente: any): string {
    const tipo = docente?.tipo_revision ?? '';

    if (tipo === 'cambios_perfil') {
      return 'Cambios de perfil';
    }

    return 'Solicitud inicial';
  }

  docenteTieneCambiosPerfil(docente: any): boolean {
    return docente?.tipo_revision === 'cambios_perfil';
  }

  cambioDocente(docente: any, campo: string): string {
    return docente?.cambios_pendientes_json?.[campo] ?? '';
  }

  abrirModalDocente(docente: any): void {
    const usuarioId = this.usuarioIdDocente(docente);

    this.modalDocenteAbierto.set(true);
    this.docenteSeleccionado.set(docente);
    this.motivoRechazo = '';
    this.error.set('');
    this.mensaje.set('');

    if (!usuarioId) {
      return;
    }

    this.cargandoDetalleDocente.set(true);

    this.adminService.verDocente(usuarioId).subscribe({
      next: (res) => {
        this.cargandoDetalleDocente.set(false);
        this.docenteSeleccionado.set(res.docente ?? docente);
      },
      error: () => {
        this.cargandoDetalleDocente.set(false);
      },
    });
  }

  cerrarModalDocente(): void {
    this.modalDocenteAbierto.set(false);
    this.docenteSeleccionado.set(null);
    this.motivoRechazo = '';
    this.accionCargando.set('');
  }

  aprobarDocenteDesdeModal(): void {
    const docente = this.docenteSeleccionado();
    const usuarioId = this.usuarioIdDocente(docente);

    if (!usuarioId) {
      this.error.set('No se encontró el usuario del docente.');
      return;
    }

    this.accionCargando.set('aprobar-docente-' + usuarioId);
    this.error.set('');
    this.mensaje.set('');

    this.adminService.aprobarDocente(usuarioId).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Docente aprobado correctamente.');
        this.cerrarModalDocente();
        this.cargarDocentesPendientes();
        this.cargarDocentes();
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  rechazarDocenteDesdeModal(): void {
    const docente = this.docenteSeleccionado();
    const usuarioId = this.usuarioIdDocente(docente);

    if (!usuarioId) {
      this.error.set('No se encontró el usuario del docente.');
      return;
    }

    if (!this.motivoRechazo.trim() || this.motivoRechazo.trim().length < 5) {
      this.error.set('Escribe un motivo de rechazo válido para el docente.');
      return;
    }

    this.accionCargando.set('rechazar-docente-' + usuarioId);
    this.error.set('');
    this.mensaje.set('');

    this.adminService.rechazarDocente(usuarioId, this.motivoRechazo.trim()).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Solicitud docente rechazada correctamente.');
        this.cerrarModalDocente();
        this.cargarDocentesPendientes();
        this.cargarDocentes();
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cargarMisCursosEstudiante(): void {
    this.inscripcionService.misCursos().subscribe({
      next: (res) => {
        const todas = res.inscripciones ?? [];
        const activas = todas.filter((inscripcion: any) => this.inscripcionActiva(inscripcion));

        this.inscripciones.set(activas);
        this.cargando.set(false);
      },
      error: (err) => {
        this.cargando.set(false);
        this.error.set(this.authService.extraerError(err));
      },
    });
  }

  cursoDe(inscripcion: any): any {
    return inscripcion?.curso ?? inscripcion;
  }

  cursoId(inscripcion: any): number {
    return Number(
      inscripcion?.curso_id ??
      inscripcion?.curso?.id ??
      inscripcion?.id
    );
  }

  tituloCurso(inscripcion: any): string {
    return this.cursoDe(inscripcion)?.titulo ?? 'Curso sin título';
  }

  estadoInscripcion(inscripcion: any): string {
    return String(inscripcion?.estado ?? 'activa').toLowerCase();
  }

  inscripcionActiva(inscripcion: any): boolean {
    // En Mis cursos también deben aparecer cursos completados/aprobados.
    // Solo se ocultan cuando el estudiante se desuscribe y quedan cancelados.
    return ['activa', 'activo', 'vigente', 'completada', 'aprobada', 'reprobada', 'bloqueada'].includes(this.estadoInscripcion(inscripcion));
  }

  progreso(inscripcion: any): number {
    return Number(
      inscripcion?.progreso_porcentaje ??
      inscripcion?.progreso ??
      0
    );
  }

  cancelarInscripcion(inscripcion: any): void {
    const cursoId = this.cursoId(inscripcion);

    this.error.set('');
    this.mensaje.set('');
    this.accionCargando.set('cancelar-' + cursoId);

    this.inscripcionService.cancelarInscripcion(cursoId).subscribe({
      next: (res) => {
        this.accionCargando.set('');
        this.mensaje.set(res.mensaje ?? 'Te desuscribiste del curso.');
        this.cargarMisCursosEstudiante();
      },
      error: (err) => {
        this.accionCargando.set('');
        this.error.set(this.authService.extraerError(err));
      },
    });
  }
}