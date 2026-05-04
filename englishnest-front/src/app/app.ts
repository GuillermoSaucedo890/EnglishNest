import { CommonModule } from '@angular/common';
import { Component, DoCheck, inject } from '@angular/core';
import { Router, RouterLink, RouterOutlet } from '@angular/router';
import { AuthService } from './servicios/auth';

@Component({
  selector: 'app-root',
  imports: [CommonModule, RouterOutlet, RouterLink],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App implements DoCheck {
  private authService = inject(AuthService);
  private router = inject(Router);

  usuario: any = null;
  menuAbierto = false;
  ultimoToken: string | null = null;

  ngDoCheck(): void {
    const tokenActual = this.authService.getToken();

    if (tokenActual !== this.ultimoToken) {
      this.ultimoToken = tokenActual;

      if (tokenActual) {
        this.cargarUsuario();
      } else {
        this.usuario = null;
        this.menuAbierto = false;
      }
    }
  }

  cargarUsuario() {
    this.authService.me().subscribe({
      next: (respuesta) => {
        this.usuario = respuesta.usuario;
      },
      error: () => {
        this.authService.limpiarSesion();
        this.usuario = null;
      }
    });
  }

  estaAutenticado(): boolean {
    return !!this.authService.getToken();
  }

  esAdmin(): boolean {
    return this.usuario?.rol === 'admin';
  }

  toggleMenu() {
    this.menuAbierto = !this.menuAbierto;
  }

  iniciales(): string {
    if (!this.usuario) return '?';

    const n = this.usuario.nombres?.charAt(0) ?? '';
    const a = this.usuario.apellidos?.charAt(0) ?? '';

    return `${n}${a}`.toUpperCase();
  }

  cerrarSesion() {
    if (!this.authService.getToken()) {
      this.router.navigate(['/login']);
      return;
    }

    this.authService.logout().subscribe({
      next: () => {
        this.usuario = null;
        this.menuAbierto = false;
        this.router.navigate(['/login']);
      },
      error: () => {
        this.authService.limpiarSesion();
        this.usuario = null;
        this.menuAbierto = false;
        this.router.navigate(['/login']);
      }
    });
  }
}
