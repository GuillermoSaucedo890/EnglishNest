import { CommonModule } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  ValidatorFn,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { AuthService } from '../../servicios/auth';

// Valida que password y confirmación sean iguales
export const coincidenValidator: ValidatorFn = (
  control: AbstractControl
): ValidationErrors | null => {
  const password = control.get('password')?.value;
  const confirmacion = control.get('password_confirmation')?.value;

  if (!password || !confirmacion) {
    return null;
  }

  return password === confirmacion ? null : { noCoinciden: true };
};

@Component({
  selector: 'app-restablecer-password',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './restablecer-password.html',
  styleUrl: './restablecer-password.css',
})
export class RestablecerPassword implements OnInit {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  token = '';
  email = '';

  mensaje = '';
  error = '';
  exito = false;
  cargando = false;

  formulario = this.fb.group(
    {
      password: [
        '',
        [
          Validators.required,
          Validators.minLength(10),
          Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/),
        ],
      ],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: coincidenValidator }
  );

  ngOnInit(): void {
    this.token = this.route.snapshot.queryParams['token'];
    this.email = this.route.snapshot.queryParams['email'];

    if (!this.token || !this.email) {
      this.error = 'El enlace de recuperación no es válido o está incompleto.';
    }
  }

  passwordValor(): string {
    return this.formulario.get('password')?.value ?? '';
  }

  tieneMinimo(): boolean {
    return this.passwordValor().length >= 10;
  }

  tieneMayuscula(): boolean {
    return /[A-Z]/.test(this.passwordValor());
  }

  tieneMinuscula(): boolean {
    return /[a-z]/.test(this.passwordValor());
  }

  tieneNumero(): boolean {
    return /\d/.test(this.passwordValor());
  }

  tieneSimbolo(): boolean {
    return /[\W_]/.test(this.passwordValor());
  }

  passwordsCoinciden(): boolean {
    const password = this.formulario.get('password')?.value;
    const confirmacion = this.formulario.get('password_confirmation')?.value;

    if (!password || !confirmacion) {
      return false;
    }

    return password === confirmacion;
  }

  formularioListo(): boolean {
    return (
      this.tieneMinimo() &&
      this.tieneMayuscula() &&
      this.tieneMinuscula() &&
      this.tieneNumero() &&
      this.tieneSimbolo() &&
      this.passwordsCoinciden() &&
      !!this.token &&
      !!this.email
    );
  }

  enviar(): void {
    this.mensaje = '';
    this.error = '';

    if (!this.token || !this.email) {
      this.error = 'El enlace de recuperación no es válido o ya expiró.';
      return;
    }

    if (!this.formularioListo()) {
      this.error = 'Completa una contraseña segura y confirma que ambas coincidan.';
      return;
    }

    this.cargando = true;

    const datos = {
      password: this.formulario.value.password,
      password_confirmation: this.formulario.value.password_confirmation,
      token: this.token,
      email: this.email,
    };

    this.authService.restablecerPassword(datos).subscribe({
      next: (res) => {
        this.cargando = false;
        this.exito = true;
        this.mensaje = res.mensaje ?? 'Contraseña actualizada correctamente.';

        setTimeout(() => {
          this.router.navigate(['/login']);
        }, 2800);
      },
      error: (err) => {
        this.cargando = false;
        this.error = this.authService.extraerError(err);
      },
    });
  }
}