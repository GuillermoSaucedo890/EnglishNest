import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class CursoService {
  private http = inject(HttpClient);

  private url = 'http://englishnest-api.test/api';

  obtenerAreas(): Observable<any> {
    return this.http.get(`${this.url}/areas`);
  }

  obtenerCursos(): Observable<any> {
    return this.http.get(`${this.url}/cursos`);
  }

  crearCurso(datos: any): Observable<any> {
    return this.http.post(`${this.url}/cursos`, datos);
  }

  obtenerCursosPublicos(): Observable<any> {
    return this.http.get(`${this.url}/public/cursos`);
  }

  obtenerCursoPublico(cursoId: number | string): Observable<any> {
    return this.http.get(`${this.url}/public/cursos/${cursoId}`);
  }

  obtenerCurso(cursoId: number | string): Observable<any> {
    return this.http.get(`${this.url}/cursos/${cursoId}`);
  }

  obtenerCursoEdicion(cursoId: number | string): Observable<any> {
    return this.http.get(`${this.url}/cursos/${cursoId}/edicion`);
  }

  actualizarCursoEdicion(cursoId: number | string, datos: any): Observable<any> {
    return this.http.put(`${this.url}/cursos/${cursoId}/edicion`, datos);
  }

  crearLeccion(cursoId: number | string, datos: any): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/edicion/lecciones`, datos);
  }

  actualizarLeccion(leccionId: number | string, datos: any): Observable<any> {
    return this.http.put(`${this.url}/cursos/lecciones/${leccionId}/edicion`, datos);
  }

  eliminarLeccion(leccionId: number | string): Observable<any> {
    return this.http.delete(`${this.url}/cursos/lecciones/${leccionId}/edicion`);
  }

  enviarRevision(cursoId: number | string, solicitaPublicacion: boolean): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/edicion/enviar-revision`, {
      solicita_publicacion: solicitaPublicacion,
    });
  }

  aprobarRevision(cursoId: number | string): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/edicion/aprobar`, {});
  }

  rechazarRevision(cursoId: number | string, motivo: string): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/edicion/rechazar`, {
      motivo_rechazo: motivo,
    });
  }

  publicarCurso(cursoId: number | string): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/publicar`, {});
  }

  ocultarCurso(cursoId: number | string): Observable<any> {
    return this.http.post(`${this.url}/cursos/${cursoId}/ocultar`, {});
  }
}