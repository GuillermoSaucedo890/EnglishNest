import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-admin-docentes',
  imports: [CommonModule],
  templateUrl: './admin-docentes.html',
  styleUrl: './admin-docentes.css'
})
export class AdminDocentes implements OnInit {
  private authService = inject(AuthService);
  private router = inject(Router);

  docentes: any[] = [];
  mensaje = '';
  error = '';

  ngOnInit(): void {
    if (!this.authService.getToken()) {
      this.router.navigate(['/login']);
      return;
    }

    this.cargarPendientes();
  }

  cargarPendientes() {
    this.authService.docentesPendientes().subscribe({
      next: (respuesta) => {
        this.docentes = respuesta.docentes;
      },
      error: (error) => {
        this.error = this.authService.extraerError(error);
      }
    });
  }

  aprobar(usuarioId: number) {
    this.mensaje = '';
    this.error = '';

    this.authService.aprobarDocente(usuarioId).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.mensaje;
        this.cargarPendientes();
      },
      error: (error) => {
        this.error = this.authService.extraerError(error);
      }
    });
  }
}