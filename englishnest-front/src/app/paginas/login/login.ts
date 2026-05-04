import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../servicios/auth';

@Component({
  selector: 'app-login',
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './login.html',
  styleUrl: './login.css'
})
export class Login {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  modoRegistro: 'estudiante' | 'docente' = 'estudiante';

  mensajeLogin = '';
  errorLogin = '';

  mensajeRegistro = '';
  errorRegistro = '';

  formularioLogin = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  formularioRegistro = this.fb.group({
    nombres: ['', [Validators.required]],
    apellidos: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', [Validators.required]],
    especialidad: [''],
    estudios: [''],
    biografia: [''],
  });

  cambiarModoRegistro() {
    this.modoRegistro = this.modoRegistro === 'estudiante' ? 'docente' : 'estudiante';
    this.mensajeRegistro = '';
    this.errorRegistro = '';

    if (this.modoRegistro === 'estudiante') {
      this.formularioRegistro.patchValue({
        especialidad: '',
        estudios: '',
        biografia: '',
      });
    }
  }

  enviarLogin() {
    this.mensajeLogin = '';
    this.errorLogin = '';

    if (this.formularioLogin.invalid) {
      this.errorLogin = 'Completa correctamente el formulario de acceso.';
      return;
    }

    this.authService.login(this.formularioLogin.getRawValue()).subscribe({
      next: (respuesta) => {
        this.mensajeLogin = respuesta.mensaje;

        if (respuesta?.usuario?.correo_verificado) {
          this.router.navigate(['/panel']);
        } else {
          this.router.navigate(['/verificar-correo']);
        }
      },
      error: (error) => {
        this.errorLogin = this.authService.extraerError(error);
      }
    });
  }

  enviarRegistro() {
    this.mensajeRegistro = '';
    this.errorRegistro = '';

    if (this.formularioRegistro.invalid) {
      this.errorRegistro = 'Completa correctamente el formulario.';
      return;
    }

    const datos = this.formularioRegistro.getRawValue();

    if (this.modoRegistro === 'estudiante') {
      this.authService.registrarEstudiante({
        nombres: datos.nombres,
        apellidos: datos.apellidos,
        email: datos.email,
        password: datos.password,
        password_confirmation: datos.password_confirmation,
      }).subscribe({
        next: (respuesta) => {
          this.mensajeRegistro = respuesta.mensaje;
          this.router.navigate(['/verificar-correo']);
        },
        error: (error) => {
          this.errorRegistro = this.authService.extraerError(error);
        }
      });

      return;
    }

    if (!datos.estudios || datos.estudios.trim() === '') {
      this.errorRegistro = 'Para docente, el campo estudios es obligatorio.';
      return;
    }

    this.authService.registrarDocente({
      nombres: datos.nombres,
      apellidos: datos.apellidos,
      email: datos.email,
      password: datos.password,
      password_confirmation: datos.password_confirmation,
      especialidad: datos.especialidad,
      estudios: datos.estudios,
      biografia: datos.biografia,
    }).subscribe({
      next: (respuesta) => {
        this.mensajeRegistro = respuesta.mensaje;
        this.router.navigate(['/verificar-correo']);
      },
      error: (error) => {
        this.errorRegistro = this.authService.extraerError(error);
      }
    });
  }
}