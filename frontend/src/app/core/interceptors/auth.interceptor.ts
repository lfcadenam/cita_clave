import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../services/auth.service';

/**
 * Interceptor funcional que inyecta automáticamente el token Bearer Sanctum
 * y el slug del tenant activo en las peticiones administrativas.
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  let headersConfig: Record<string, string> = {
    'Accept': 'application/json',
  };

  const token = authService.token();
  if (token) {
    headersConfig['Authorization'] = `Bearer ${token}`;
  }

  const tenant = authService.currentTenant();
  if (tenant && tenant.slug) {
    headersConfig['X-Tenant-Slug'] = tenant.slug;
  }

  let modifiedReq = req;
  if (Object.keys(headersConfig).length > 0) {
    modifiedReq = req.clone({
      setHeaders: headersConfig
    });
  }

  return next(modifiedReq).pipe(
    catchError((error: HttpErrorResponse) => {
      // Si el servidor responde 401 (No autorizado) en una ruta de admin, reiniciar sesión
      if (error.status === 401 && !req.url.includes('/auth/login')) {
        authService.clearSession();
        router.navigate(['/admin/login']);
      }
      return throwError(() => error);
    })
  );
};
