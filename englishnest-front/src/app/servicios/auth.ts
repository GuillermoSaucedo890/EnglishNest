import { Injectable, computed, inject, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, catchError, of, tap } from 'rxjs';
import { API_URL } from '../config/api.config';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);

  // Usuario actual disponible para toda la app
  usuarioActual = signal<any | null>(null);

  // Rol actual calculado desde el usuario
  rolActual = computed(() => this.usuarioActual()?.rol ?? '');

  // Saber si hay sesión iniciada
  estaAutenticado = computed(() => !!this.getToken());

  // Carga usuario si ya existe token al abrir/refrescar la web
  cargarSesionInicial(): void {
    if (!this.getToken()) {
      this.usuarioActual.set(null);
      return;
    }

    this.me().subscribe({
      next: (res) => {
        this.usuarioActual.set(res.usuario);
      },
      error: () => {
        this.limpiarSesion();
      },
    });
  }

  // Login
  login(datos: any): Observable<any> {
    return this.http.post<any>(`${API_URL}/auth/login`, datos).pipe(
      tap((res) => {
        if (res.token) {
          localStorage.setItem('token', res.token);
        }

        if (res.usuario) {
          this.usuarioActual.set(res.usuario);
        }
      })
    );
  }

  // Registro estudiante
  registrarEstudiante(datos: any): Observable<any> {
    return this.http.post<any>(`${API_URL}/auth/registro-estudiante`, datos).pipe(
      tap((res) => {
        if (res.token) {
          localStorage.setItem('token', res.token);
        }

        if (res.usuario) {
          this.usuarioActual.set(res.usuario);
        }
      })
    );
  }

  // Registro docente
  registrarDocente(datos: any): Observable<any> {
    return this.http.post<any>(`${API_URL}/auth/registro-docente`, datos).pipe(
      tap((res) => {
        if (res.token) {
          localStorage.setItem('token', res.token);
        }

        if (res.usuario) {
          this.usuarioActual.set(res.usuario);
        }
      })
    );
  }

  // Solicitar recuperación
  solicitarRecuperacion(email: string): Observable<any> {
    return this.http.post(`${API_URL}/auth/olvide-mi-contrasena`, { email });
  }

  // Restablecer contraseña
  restablecerPassword(datos: any): Observable<any> {
    return this.http.post(`${API_URL}/auth/restablecer-contrasena`, datos);
  }

  // Datos del usuario autenticado
  me(): Observable<any> {
    return this.http.get(`${API_URL}/auth/me`);
  }

  // Cerrar sesión
  logout(): Observable<any> {
    return this.http.post(`${API_URL}/auth/logout`, {}).pipe(
      tap(() => this.limpiarSesion())
    );
  }

  // Reenviar correo de verificación
  reenviarVerificacion(): Observable<any> {
    return this.http.post(`${API_URL}/auth/reenviar-verificacion`, {});
  }

  // Compatibilidad con páginas viejas
  docentesPendientes(): Observable<any> {
    return this.http.get(`${API_URL}/admin/docentes/pendientes`);
  }

  aprobarDocente(usuarioId: number): Observable<any> {
    return this.http.post(`${API_URL}/admin/docentes/aprobar/${usuarioId}`, {});
  }

  // Token actual
  getToken(): string | null {
    return localStorage.getItem('token');
  }

  // Limpia sesión completa
  limpiarSesion(): void {
    localStorage.removeItem('token');
    this.usuarioActual.set(null);
  }

  // Extrae mensaje claro desde errores Laravel/Angular
  extraerError(error: any): string {
    if (error?.status === 0) {
      return 'No hay conexión con el servidor.';
    }

    if (error?.error?.mensaje) {
      return error.error.mensaje;
    }

    if (error?.error?.message) {
      return error.error.message;
    }

    const errores = error?.error?.errors;

    if (errores) {
      const primeraClave = Object.keys(errores)[0];
      return errores[primeraClave][0];
    }

    return 'Ocurrió un error inesperado.';
  }
}