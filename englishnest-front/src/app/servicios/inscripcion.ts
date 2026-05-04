import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class InscripcionService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api';

  inscribirse(cursoId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/inscripciones`, {
      curso_id: cursoId
    }, {
      headers: this.getHeaders()
    });
  }

  obtenerInscripciones(): Observable<any> {
    return this.http.get(`${this.apiUrl}/inscripciones`, {
      headers: this.getHeaders()
    });
  }

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('token');

    return new HttpHeaders({
      Authorization: `Bearer ${token}`
    });
  }
}
