import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class EvaluacionService {
  private http = inject(HttpClient);

  private apiUrl = 'http://englishnest-api.test/api';

  // =========================================================
  // DOCENTE / ADMIN: EDICIÓN SEGURA
  // Estas rutas guardan dentro de curso_ediciones.datos_json.
  // No modifican las tablas oficiales hasta que el admin apruebe.
  // =========================================================

  obtenerEvaluacionLeccionEdicion(cursoId: number | string, leccionId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/cursos/${cursoId}/edicion/lecciones/${leccionId}/evaluacion`);
  }

  guardarEvaluacionLeccionEdicion(cursoId: number | string, leccionId: number | string, datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/cursos/${cursoId}/edicion/lecciones/${leccionId}/evaluacion`, datos);
  }

  obtenerEvaluacionFinalEdicion(cursoId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/cursos/${cursoId}/edicion/evaluacion-final`);
  }

  guardarEvaluacionFinalEdicion(cursoId: number | string, datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/cursos/${cursoId}/edicion/evaluacion-final`, datos);
  }

  // =========================================================
  // ESTUDIANTE: EVALUACIONES OFICIALES PUBLICADAS
  // Estas rutas trabajan sobre evaluaciones/preguntas oficiales.
  // =========================================================

  obtenerEvaluacionLeccion(leccionId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/lecciones/${leccionId}/evaluacion`);
  }

  guardarEvaluacionLeccion(leccionId: number | string, datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/lecciones/${leccionId}/evaluacion`, datos);
  }

  obtenerEvaluacionFinal(cursoId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/cursos/${cursoId}/evaluacion-final`);
  }

  guardarEvaluacionFinal(cursoId: number | string, datos: any): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/cursos/${cursoId}/evaluacion-final`, datos);
  }

  obtenerEstadoEvaluacion(evaluacionId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/evaluaciones/${evaluacionId}/estado`);
  }

  responderEvaluacion(evaluacionId: number | string, respuestas: any[]): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/evaluaciones/${evaluacionId}/responder`, {
      respuestas,
    });
  }

  repetirCurso(cursoId: number | string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/cursos/${cursoId}/repetir`, {});
  }

  obtenerEstadoCurso(cursoId: number | string): Observable<any> {
    return this.http.get<any>(`${this.apiUrl}/progreso/curso/${cursoId}/estado`);
  }

  marcarLeccionCompletada(leccionId: number | string): Observable<any> {
    return this.http.post<any>(`${this.apiUrl}/progreso`, {
      leccion_id: leccionId,
      completado: true,
    });
  }
}
