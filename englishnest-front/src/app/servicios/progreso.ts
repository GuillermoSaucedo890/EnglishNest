import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { API_URL } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class ProgresoService {
  private http = inject(HttpClient);

  obtenerProgreso(): Observable<any> {
    return this.http.get(`${API_URL}/progreso`);
  }

  verificarPago(): Observable<any> {
    return this.http.get(`${API_URL}/pagos/estado`);
  }

  marcarLeccion(leccionId: number, completado: boolean): Observable<any> {
    return this.http.post(`${API_URL}/progreso`, {
      leccion_id: leccionId,
      completado,
    });
  }

  obtenerPorcentajeCurso(cursoId: number): Observable<any> {
    return this.http.get(`${API_URL}/progreso/curso/${cursoId}/porcentaje`);
  }
}