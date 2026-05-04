import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IaService } from '../../servicios/ia';

@Component({
  selector: 'app-ia',
  imports: [CommonModule, FormsModule],
  templateUrl: './ia.html',
  styleUrl: './ia.css'
})
export class Ia {
  private iaService = inject(IaService);

  mensaje = '';
  chat: { texto: string, tipo: 'user' | 'ia' }[] = [];

  enviar() {
    if (!this.mensaje.trim()) return;

    const userMsg = this.mensaje;

    this.chat.push({ texto: userMsg, tipo: 'user' });

    this.mensaje = '';

    this.iaService.enviarMensaje(userMsg).subscribe({
      next: (res) => {
        this.chat.push({ texto: res.respuesta, tipo: 'ia' });
      },
      error: () => {
        this.chat.push({ texto: 'Error al conectar con la IA.', tipo: 'ia' });
      }
    });
  }
}
