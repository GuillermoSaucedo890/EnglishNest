import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { API_URL } from '../config/api.config';

@Injectable({
  providedIn: 'root',
})
export class IaService {
  private http = inject(HttpClient);

  enviarMensaje(mensaje: string): Observable<any> {
    return this.http.post(`${API_URL}/ia/tutor`, {
      mensaje,
    });
  }
}