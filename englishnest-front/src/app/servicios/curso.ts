import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CursoService {

  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api';

  // 🔹 Obtener todos los cursos
  obtenerCursos(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/cursos`, {
      headers: this.getHeaders()
    });
  }

  // 🔹 Inscribirse a un curso
  inscribirse(curso_id: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/inscripciones`, {
      curso_id
    }, {
      headers: this.getHeaders()
    });
  }

  // 🔹 Obtener mis cursos
  obtenerMisCursos(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/inscripciones`, {
      headers: this.getHeaders()
    });
  }

  // 🔹 Crear curso (lo dejamos por si lo usas)
  crearCurso(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/cursos`, data, {
      headers: this.getHeaders()
    });
  }

  // 🔐 Headers con token
  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');

    return new HttpHeaders({
      Authorization: `Bearer ${token}`,
      Accept: 'application/json'
    });
  }
}
