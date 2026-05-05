import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { finalize } from 'rxjs';
import { LoadingService } from '../servicios/loading';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const loading = inject(LoadingService);
  const token = localStorage.getItem('token');

  // Permite saltar el loading si en algún servicio mandamos esta cabecera
  const saltarLoading = req.headers.get('X-Skip-Loading') === 'true';

  let texto = 'Cargando...';

  if (req.url.includes('/login')) texto = 'Iniciando sesión...';
  if (req.url.includes('/registro')) texto = 'Creando cuenta...';
  if (req.url.includes('/olvide-mi-contrasena')) texto = 'Enviando correo...';
  if (req.url.includes('/restablecer-contrasena')) texto = 'Actualizando contraseña...';
  if (req.url.includes('/aprobar')) texto = 'Aprobando...';
  if (req.url.includes('/rechazar')) texto = 'Rechazando...';
  if (req.url.includes('/enviar-revision')) texto = 'Enviando a revisión...';

  if (!saltarLoading) {
    loading.mostrar(texto);
  }

  // Quitamos la cabecera interna para que Laravel no la reciba
  let pedido = req.clone({
    headers: req.headers.delete('X-Skip-Loading'),
  });

  // Si hay token, lo agregamos automáticamente
  if (token) {
    pedido = pedido.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
      },
    });
  }

  return next(pedido).pipe(
    finalize(() => {
      if (!saltarLoading) {
        loading.ocultar();
      }
    })
  );
};