import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { LoadingService } from '../servicios/loading';
import { finalize } from 'rxjs';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const loading = inject(LoadingService);
  const token = localStorage.getItem('token');

  // 1. Decidir el mensaje de carga según la URL
  let texto = 'Cargando...';
  if (req.url.includes('/login')) texto = 'Iniciando sesión...';
  if (req.url.includes('/registro')) texto = 'Creando cuenta...';
  if (req.url.includes('/restablecer')) texto = 'Actualizando contraseña...';

  loading.mostrar(texto);

  // 2. Clonar la petición para añadir el Token (si existe)
  let pedidoClonado = req;
  if (token) {
    pedidoClonado = req.clone({
      setHeaders: { Authorization: `Bearer ${token}` }
    });
  }

  // 3. Ejecutar y ocultar carga al terminar (aunque haya error)
  return next(pedidoClonado).pipe(
    finalize(() => loading.ocultar())
  );
};