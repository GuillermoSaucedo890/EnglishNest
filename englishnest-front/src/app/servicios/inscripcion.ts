import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';

@Injectable({
  providedIn: 'root',
})
export class InscripcionService {
  private http = inject(HttpClient);

  private apiUrl = 'http://englishnest-api.test/api';

  misCursos() {
    return this.http.get<any>(`${this.apiUrl}/mis-cursos`);
  }

  inscribirse(cursoId: number) {
    return this.http.post<any>(`${this.apiUrl}/inscripciones`, {
      curso_id: cursoId,
    });
  }

  cancelarInscripcion(cursoId: number) {
    // Ruta real del backend: routes/api.php -> POST /api/inscripciones/{cursoId}/cancelar
    // Quitamos el fallback viejo /cursos/{id}/cancelar-inscripcion porque esa ruta no existe.
    return this.http.post<any>(`${this.apiUrl}/inscripciones/${cursoId}/cancelar`, {});
  }
}
