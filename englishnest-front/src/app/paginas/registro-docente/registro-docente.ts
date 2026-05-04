import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-registro-docente',
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './registro-docente.html',
  styleUrl: './registro-docente.css'
})
export class RegistroDocente {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  mensaje = '';
  error = '';

  formulario = this.fb.group({
    nombres: ['', [Validators.required]],
    apellidos: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', [Validators.required]],
    estudios: ['', [Validators.required]],
    especialidad: [''],
    biografia: [''],
  });

  enviar() {
    this.mensaje = '';
    this.error = '';

    if (this.formulario.invalid) {
      this.error = 'Completa correctamente el formulario.';
      return;
    }

    this.authService.registrarDocente(this.formulario.getRawValue()).subscribe({
      next: (respuesta) => {
        this.mensaje = respuesta.mensaje;
        this.router.navigate(['/verificar-correo']);
      },
      error: (err) => {
        this.error = err?.error?.message || 'No se pudo registrar el docente.';
      }
    });
  }
}