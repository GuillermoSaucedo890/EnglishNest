import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-registro-estudiante',
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './registro-estudiante.html',
  styleUrl: './registro-estudiante.css'
})
export class RegistroEstudiante {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  mensaje = '';
  error = '';

  formulario = this.fb.group({
    nombres: ['', [Validators.required]],
    apellidos: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [
      Validators.required, 
      Validators.minLength(10),
      Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/)
    ]],
    password_confirmation: ['', [Validators.required]],
  });

  enviar() {
    this.mensaje = '';
    this.error = '';

    if (this.formulario.get('password')?.hasError('pattern')) {
      this.error = 'La contraseña debe tener 10 caracteres, una mayúscula, un número y un símbolo.';
      return;
    }

    if (this.formulario.value.password !== this.formulario.value.password_confirmation) {
      this.error = 'Las contraseñas no coinciden.';
      return;
    }

    if (this.formulario.invalid) {
      this.error = 'Completa todos los campos obligatorios.';
      return;
    }

    this.authService.registrarEstudiante(this.formulario.value).subscribe({
      next: (res) => {
        this.mensaje = res.mensaje;
        this.router.navigate(['/verificar-correo']);
      },
      error: (err) => this.error = this.authService.extraerError(err)
    });
  }
}