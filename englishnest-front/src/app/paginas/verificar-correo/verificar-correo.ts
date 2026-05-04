import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-verificar-correo',
  imports: [CommonModule],
  templateUrl: './verificar-correo.html',
  styleUrl: './verificar-correo.css'
})
export class VerificarCorreo {
  private authService = inject(AuthService);
  private route = inject(ActivatedRoute);

  mensaje = 'Tu cuenta fue creada. Revisa tu correo y haz clic en el enlace de verificación.';
  error = '';

  constructor() {
    this.route.queryParamMap.subscribe(params => {
      if (params.get('estado') === 'ok') {
        this.mensaje = 'Correo verificado correctamente. Ya puedes volver a iniciar sesión.';
      }
    });
  }

  reenviar() {
    this.error = '';

    this.authService.reenviarVerificacion().subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.mensaje;
      },
      error: (error) => {
        this.error = this.authService.extraerError(error);
      }
    });
  }
}