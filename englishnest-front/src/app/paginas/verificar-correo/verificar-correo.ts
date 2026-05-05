import { CommonModule } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';
import { ActivatedRoute, RouterModule } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-verificar-correo',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './verificar-correo.html',
  styleUrl: './verificar-correo.css',
})
export class VerificarCorreo implements OnInit {
  private authService = inject(AuthService);
  private route = inject(ActivatedRoute);

  usuario = this.authService.usuarioActual;

  estado: 'pendiente' | 'verificado' = 'pendiente';

  mensaje = 'Tu cuenta fue creada correctamente.';
  detalle = 'Te enviamos un correo de verificación. Haz clic en el enlace para activar tu cuenta.';
  error = '';

  cargandoReenvio = false;
  correoReenviado = false;

  ngOnInit(): void {
    this.route.queryParamMap.subscribe((params) => {
      const estadoUrl = params.get('estado');

      if (estadoUrl === 'ok') {
        this.estado = 'verificado';
        this.mensaje = '¡Correo verificado correctamente!';
        this.detalle = 'Tu cuenta ya está activa. Ahora puedes iniciar sesión y continuar usando EnglishNest.';

        // Si hay token, refrescamos el usuario para que Angular sepa que ya verificó correo
        if (this.authService.getToken()) {
          this.authService.me().subscribe({
            next: (res) => {
              this.authService.usuarioActual.set(res.usuario);
            },
            error: () => {},
          });
        }
      }
    });

    // Si la app se abrió con token guardado pero usuario vacío, intentamos cargarlo
    if (this.authService.getToken() && !this.usuario()) {
      this.authService.cargarSesionInicial();
    }
  }

  puedeReenviar(): boolean {
    return !!this.authService.getToken() && this.estado !== 'verificado';
  }

  reenviar(): void {
    this.error = '';
    this.correoReenviado = false;

    if (!this.authService.getToken()) {
      this.error = 'Para reenviar el correo debes iniciar sesión primero.';
      return;
    }

    this.cargandoReenvio = true;

    this.authService.reenviarVerificacion().subscribe({
      next: (respuesta) => {
        this.cargandoReenvio = false;
        this.correoReenviado = true;
        this.mensaje = respuesta.mensaje ?? 'Correo reenviado correctamente.';
        this.detalle = 'Revisa tu bandeja de entrada o la carpeta de spam.';
      },
      error: (error) => {
        this.cargandoReenvio = false;
        this.error = this.authService.extraerError(error);
      },
    });
  }

  abrirCorreo(): void {
    const email = this.usuario()?.email?.toLowerCase() ?? '';

    const url =
      email.includes('hotmail') || email.includes('outlook')
        ? 'https://outlook.live.com'
        : 'https://mail.google.com';

    window.open(url, '_blank');
  }

  estaAutenticado(): boolean {
    return !!this.authService.getToken();
  }
}