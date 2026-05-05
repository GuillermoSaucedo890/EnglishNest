import { Injectable, signal } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class LoadingService {
  // Usamos un Signal para que sea ultra rápido y reactivo
  cargando = signal(false);
  mensaje = signal('Cargando...');

  mostrar(texto: string = 'Cargando...') {
    this.mensaje.set(texto);
    this.cargando.set(true);
  }

  ocultar() {
    this.cargando.set(false);
  }
}