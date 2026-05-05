import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class DocentePerfilService {
  private http = inject(HttpClient);

  private url = 'http://englishnest-api.test/api';

  obtenerMiPerfil(): Observable<any> {
    return this.http.get(`${this.url}/docente/perfil`);
  }

  actualizarPerfil(datos: any): Observable<any> {
    return this.http.put(`${this.url}/docente/perfil`, datos);
  }

  reenviarSolicitud(): Observable<any> {
    return this.http.post(`${this.url}/docente/perfil/reenviar-solicitud`, {});
  }
}