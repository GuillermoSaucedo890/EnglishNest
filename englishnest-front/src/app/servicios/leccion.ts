import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class LeccionService {
  private http = inject(HttpClient);
  private apiUrl = 'http://127.0.0.1:8000/api';

  obtenerLecciones(): Observable<any> {
    return this.http.get(`${this.apiUrl}/lecciones`, {
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
