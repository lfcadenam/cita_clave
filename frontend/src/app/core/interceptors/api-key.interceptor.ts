import { HttpInterceptorFn } from '@angular/common/http';
import { environment } from '../../../environments/environment';

/**
 * Interceptor funcional que inyecta la cabecera X-API-Key en todas las peticiones
 * salientes hacia la API del backend de La Belle Nails.
 */
export const apiKeyInterceptor: HttpInterceptorFn = (req, next) => {
  if (environment.apiKey) {
    const authReq = req.clone({
      setHeaders: {
        'X-API-Key': environment.apiKey
      }
    });
    return next(authReq);
  }
  return next(req);
};
