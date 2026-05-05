import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { API_URL } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class LeccionService {
  private http = inject(HttpClient);

  obtenerLecciones(cursoId?: number): Observable<any> {
    const url = cursoId
      ? `${API_URL}/lecciones?curso_id=${cursoId}`
      : `${API_URL}/lecciones`;

    return this.http.get(url);
  }

  obtenerContenido(leccionId: number): Observable<any> {
    return this.http.get(`${API_URL}/lecciones/${leccionId}/contenido`);
  }
}