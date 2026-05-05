import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class AdminService {
  private http = inject(HttpClient);

  private url = 'http://englishnest-api.test/api';

  obtenerDocentesPendientes(): Observable<any> {
    return this.http.get(`${this.url}/admin/docentes/pendientes`);
  }

  obtenerDocentesAprobados(): Observable<any> {
    return this.http.get(`${this.url}/admin/docentes/aprobados`);
  }

  verDocente(usuarioId: number): Observable<any> {
    return this.http.get(`${this.url}/admin/docentes/${usuarioId}/detalle`);
  }

  aprobarDocente(usuarioId: number): Observable<any> {
    return this.http.post(`${this.url}/admin/docentes/aprobar/${usuarioId}`, {});
  }

  rechazarDocente(usuarioId: number, motivo: string): Observable<any> {
    return this.http.post(`${this.url}/admin/docentes/rechazar/${usuarioId}`, {
      motivo_rechazo: motivo,
    });
  }
}