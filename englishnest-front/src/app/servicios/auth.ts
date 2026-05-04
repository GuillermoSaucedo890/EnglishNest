import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api';

  registrarEstudiante(datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/auth/registro-estudiante`, datos).pipe(
      tap((respuesta) => {
        if (respuesta?.token) {
          localStorage.setItem('token', respuesta.token);
        }
      })
    );
  }

  registrarDocente(datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/auth/registro-docente`, datos).pipe(
      tap((respuesta) => {
        if (respuesta?.token) {
          localStorage.setItem('token', respuesta.token);
        }
      })
    );
  }

  login(datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/auth/login`, datos).pipe(
      tap((respuesta) => {
        if (respuesta?.token) {
          localStorage.setItem('token', respuesta.token);
        }
      })
    );
  }

  me(): Observable<any> {
    return this.http.get(`${this.apiUrl}/auth/me`, {
      headers: this.getHeaders()
    });
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/logout`, {}, {
      headers: this.getHeaders()
    }).pipe(
      tap(() => localStorage.removeItem('token'))
    );
  }

  reenviarVerificacion(): Observable<any> {
    return this.http.post(`${this.apiUrl}/auth/reenviar-verificacion`, {}, {
      headers: this.getHeaders()
    });
  }

  docentesPendientes(): Observable<any> {
    return this.http.get(`${this.apiUrl}/admin/docentes/pendientes`, {
      headers: this.getHeaders()
    });
  }

  aprobarDocente(usuarioId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/admin/docentes/aprobar/${usuarioId}`, {}, {
      headers: this.getHeaders()
    });
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  limpiarSesion() {
    localStorage.removeItem('token');
  }

  extraerError(error: any): string {
    if (error?.error?.mensaje) return error.error.mensaje;
    if (error?.error?.message) return error.error.message;

    const errores = error?.error?.errors;
    if (errores) {
      const clave = Object.keys(errores)[0];
      if (clave && errores[clave]?.length) {
        return errores[clave][0];
      }
    }

    if (error?.status === 401) return 'Correo o contraseña incorrectos, o tu sesión expiró.';
    if (error?.status === 403) return 'No tienes permisos para esta acción.';
    if (error?.status === 0) return 'No se pudo conectar con el servidor.';

    return 'Ocurrió un error inesperado.';
  }

  private getHeaders(): HttpHeaders {
    const token = this.getToken();

    return new HttpHeaders({
      Authorization: `Bearer ${token}`
    });
  }
}
