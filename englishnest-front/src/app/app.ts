import { CommonModule } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterOutlet } from '@angular/router';
import { AuthService } from './servicios/auth';
import { LoadingService } from './servicios/loading';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, RouterOutlet, RouterLink],
  templateUrl: './app.html',
  styleUrl: './app.css',
})
export class App implements OnInit {
  private authService = inject(AuthService);

  // Usamos el LoadingService que ya existe en tu proyecto.
  // No agregamos interceptores ni tocamos configuración.
  loadingService = inject(LoadingService);

  router = inject(Router);

  usuario = this.authService.usuarioActual;

  cargandoSesion = signal(false);
  menuAbierto = signal(false);

  ngOnInit(): void {
    this.cargarSesionSiExiste();
  }

  cargarSesionSiExiste(): void {
    const token = this.authService.getToken();

    if (!token) {
      return;
    }

    this.cargandoSesion.set(true);

    this.authService.me().subscribe({
      next: (res: any) => {
        this.authService.usuarioActual.set(res.usuario);
        this.cargandoSesion.set(false);
      },
      error: () => {
        this.authService.limpiarSesion();
        this.cargandoSesion.set(false);
      },
    });
  }

  mostrarOverlayGlobal(): boolean {
    return this.loadingService.cargando();
  }

  textoOverlayGlobal(): string {
    return this.loadingService.mensaje();
  }

  estaLogueado(): boolean {
    return !!this.authService.getToken() && !!this.usuario();
  }

  rutaActual(): string {
    return this.router.url;
  }

  rolActual(): string {
    const usuario = this.usuario();

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
    return this.rolActual() === 'admin';
  }

  esDocente(): boolean {
    return this.rolActual() === 'docente';
  }

  esEstudiante(): boolean {
    return this.rolActual() === 'estudiante';
  }

  nombreUsuario(): string {
    const usuario = this.usuario();

    const nombre = `${usuario?.nombres ?? ''} ${usuario?.apellidos ?? ''}`.trim();

    if (nombre) {
      return nombre;
    }

    return usuario?.name ?? usuario?.email ?? 'Usuario';
  }

  correoUsuario(): string {
    return this.usuario()?.email ?? '';
  }

  inicialesUsuario(): string {
    const nombre = this.nombreUsuario();

    const partes = nombre
      .split(' ')
      .map((parte) => parte.trim())
      .filter((parte) => parte.length > 0);

    if (partes.length >= 2) {
      return `${partes[0][0]}${partes[1][0]}`.toUpperCase();
    }

    return nombre.substring(0, 2).toUpperCase();
  }

  correoVerificadoTexto(): string {
    const usuario = this.usuario();

    if (usuario?.email_verified_at || usuario?.correo_verificado) {
      return 'Verificado';
    }

    return 'Sin verificar';
  }

  planTexto(): string {
    const usuario = this.usuario();

    return usuario?.plan_actual?.nombre ?? usuario?.plan ?? 'TRIAL';
  }

  docenteEstadoTexto(): string {
    const usuario = this.usuario();

    return usuario?.perfil_docente?.estado_revision ?? usuario?.estado_docente ?? 'pendiente';
  }

  abrirCerrarMenu(): void {
    this.menuAbierto.set(!this.menuAbierto());
  }

  cerrarMenu(): void {
    this.menuAbierto.set(false);
  }

  irPanel(): void {
    this.cerrarMenu();
    this.router.navigate(['/panel']);
  }

  irMisCursosDocente(): void {
    this.cerrarMenu();
    this.router.navigate(['/cursos'], {
      queryParams: { vista: 'mis-cursos' },
    });
  }

  irCatalogoDocente(): void {
    this.cerrarMenu();
    this.router.navigate(['/cursos'], {
      queryParams: { vista: 'catalogo' },
    });
  }

  cerrarSesion(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.authService.limpiarSesion();
        this.menuAbierto.set(false);
        this.router.navigate(['/login']);
      },
      error: () => {
        this.authService.limpiarSesion();
        this.menuAbierto.set(false);
        this.router.navigate(['/login']);
      },
    });
  }

  docenteMisCursosActivo(): boolean {
    return this.rutaActual().startsWith('/cursos') && !this.rutaActual().includes('vista=catalogo');
  }

  docenteCatalogoActivo(): boolean {
    return this.rutaActual().startsWith('/cursos') && this.rutaActual().includes('vista=catalogo');
  }
}