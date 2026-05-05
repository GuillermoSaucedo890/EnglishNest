import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import {
  AbstractControl,
  FormBuilder,
  ReactiveFormsModule,
  ValidationErrors,
  ValidatorFn,
  Validators,
} from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../servicios/auth';

// Valida que password y password_confirmation sean iguales
export const passwordsCoincidenValidator: ValidatorFn = (
  control: AbstractControl
): ValidationErrors | null => {
  const password = control.get('password')?.value;
  const confirmacion = control.get('password_confirmation')?.value;

  if (!password || !confirmacion) {
    return null;
  }

  return password === confirmacion ? null : { passwordsNoCoinciden: true };
};

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  // Vista actual dentro de la misma pantalla
  vistaActual: 'login' | 'registro' | 'recuperar' = 'login';

  // Tipo de registro
  modoRegistro: 'estudiante' | 'docente' = 'estudiante';

  // Estados visuales
  cargandoLogin = false;
  cargandoRegistro = false;
  cargandoRecuperacion = false;

  recuperacionEnviada = false;
  emailEnviado = '';

  mensaje = '';
  error = '';

  formularioLogin = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  formularioRegistro = this.fb.group(
    {
      nombres: ['', [Validators.required]],
      apellidos: ['', [Validators.required]],
      email: ['', [Validators.required, Validators.email]],

      password: [
        '',
        [
          Validators.required,
          Validators.minLength(10),
          Validators.pattern(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{10,}$/),
        ],
      ],

      password_confirmation: ['', [Validators.required]],

      // Estos solo serán obligatorios si el usuario se registra como docente
      especialidad: [''],
      estudios: [''],
      biografia: [''],
    },
    {
      validators: passwordsCoincidenValidator,
    }
  );

  formularioRecuperar = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
  });

  // Cambia a login
  irLogin(): void {
    this.vistaActual = 'login';
    this.limpiarMensajes();
  }

  // Cambia a registro
  irRegistro(): void {
    this.vistaActual = 'registro';
    this.limpiarMensajes();
  }

  // Cambia a recuperación
  irRecuperar(): void {
    this.vistaActual = 'recuperar';
    this.recuperacionEnviada = false;
    this.limpiarMensajes();
  }

  // Cambia entre estudiante/docente y ajusta validadores
  cambiarModoRegistro(): void {
    this.modoRegistro = this.modoRegistro === 'estudiante' ? 'docente' : 'estudiante';
    this.limpiarMensajes();
    this.aplicarValidadoresDocente();
  }

  // Hace obligatorios los campos docentes solo cuando corresponde
  aplicarValidadoresDocente(): void {
    const estudios = this.formularioRegistro.get('estudios');
    const especialidad = this.formularioRegistro.get('especialidad');

    if (!estudios || !especialidad) return;

    if (this.modoRegistro === 'docente') {
      estudios.setValidators([Validators.required]);
      especialidad.setValidators([Validators.required]);
    } else {
      estudios.clearValidators();
      especialidad.clearValidators();

      estudios.setValue('');
      especialidad.setValue('');
      this.formularioRegistro.get('biografia')?.setValue('');
    }

    estudios.updateValueAndValidity();
    especialidad.updateValueAndValidity();
  }

  enviarLogin(): void {
    this.limpiarMensajes();

    if (this.formularioLogin.invalid) {
      this.error = 'Escribe tu correo y contraseña.';
      return;
    }

    this.cargandoLogin = true;

    this.authService.login(this.formularioLogin.getRawValue()).subscribe({
      next: (respuesta) => {
        this.cargandoLogin = false;

        const usuario = respuesta.usuario;

        const verificado =
          usuario?.correo_verificado || usuario?.email_verified_at;

        if (!verificado) {
          this.router.navigate(['/verificar-correo']);
          return;
        }

        this.redirigirSegunRol(usuario);
      },
      error: (err) => {
        this.cargandoLogin = false;
        this.error = this.authService.extraerError(err);
      },
    });
  }

  redirigirSegunRol(usuario: any): void {
    const rol = this.obtenerRolUsuario(usuario);

    // El estudiante siempre entra primero al catálogo general de cursos.
    if (rol === 'estudiante') {
      this.router.navigate(['/cursos']);
      return;
    }

    // El docente entra a cursos para gestionar/ver sus cursos.
    if (rol === 'docente') {
      this.router.navigate(['/cursos']);
      return;
    }

    // El admin entra al panel principal.
    if (rol === 'admin') {
      this.router.navigate(['/panel']);
      return;
    }

    // Respaldo por si el backend no manda rol claro.
    this.router.navigate(['/cursos']);
  }

  obtenerRolUsuario(usuario: any): string {
    return String(
      usuario?.rol?.nombre ??
      usuario?.rol_nombre ??
      usuario?.rol ??
      ''
    ).toLowerCase();
  }

  enviarRegistro(): void {
    this.limpiarMensajes();
    this.aplicarValidadoresDocente();

    const password = this.formularioRegistro.get('password');

    if (password?.hasError('pattern') || password?.hasError('minlength')) {
      this.error =
        'La contraseña debe tener mínimo 10 caracteres, una mayúscula, una minúscula, un número y un símbolo.';
      return;
    }

    if (this.formularioRegistro.hasError('passwordsNoCoinciden')) {
      this.error = 'Las contraseñas no coinciden.';
      return;
    }

    if (this.formularioRegistro.invalid) {
      this.error = 'Completa correctamente los campos obligatorios.';
      return;
    }

    this.cargandoRegistro = true;

    const datos = this.formularioRegistro.getRawValue();

    const peticion =
      this.modoRegistro === 'estudiante'
        ? this.authService.registrarEstudiante(datos)
        : this.authService.registrarDocente(datos);

    peticion.subscribe({
      next: (res) => {
        this.cargandoRegistro = false;
        this.mensaje = res.mensaje ?? 'Cuenta creada correctamente.';
        this.router.navigate(['/verificar-correo']);
      },
      error: (err) => {
        this.cargandoRegistro = false;
        this.error = this.authService.extraerError(err);
      },
    });
  }

  enviarRecuperacion(): void {
    this.limpiarMensajes();

    const email = this.formularioRecuperar.value.email;

    if (this.formularioRecuperar.invalid || !email) {
      this.error = 'Escribe un correo válido.';
      return;
    }

    this.cargandoRecuperacion = true;

    this.authService.solicitarRecuperacion(email).subscribe({
      next: (res) => {
        this.cargandoRecuperacion = false;
        this.recuperacionEnviada = true;
        this.emailEnviado = email;
        this.mensaje = res.mensaje ?? 'Te enviamos un enlace de recuperación.';
      },
      error: (err) => {
        this.cargandoRecuperacion = false;
        this.error = this.authService.extraerError(err);
      },
    });
  }

  abrirCorreo(): void {
    const correo = this.emailEnviado.toLowerCase();

    const url =
      correo.includes('hotmail') || correo.includes('outlook')
        ? 'https://outlook.live.com'
        : 'https://mail.google.com';

    window.open(url, '_blank');
  }

  limpiarMensajes(): void {
    this.mensaje = '';
    this.error = '';
  }
}