import { HttpInterceptorFn } from '@angular/common/http';

/**
 * Auth Interceptor - Adds JWT token to all outgoing requests
 * Uses the new functional interceptor pattern (Angular 15+)
 */
export const authInterceptor: HttpInterceptorFn = (req, next) => {
  const token = localStorage.getItem('token');

  if (token) {
    const clonedRequest = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    });
    return next(clonedRequest);
  }

  return next(req);
};
