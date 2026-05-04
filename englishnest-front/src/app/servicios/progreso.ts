import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ProgresoService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api';

  obtenerProgreso(): Observable<any> {
    return this.http.get(`${this.apiUrl}/progreso`, {
      headers: this.getHeaders()
    });
  }
  verificarPago(): Observable<any> {
  return this.http.get(`${this.apiUrl}/pagos/estado`, {
    headers: this.getHeaders()
  });
}

  marcarLeccion(leccionId: number, completado: boolean): Observable<any> {
    return this.http.post(`${this.apiUrl}/progreso`, {
      leccion_id: leccionId,
      completado
    }, {
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
