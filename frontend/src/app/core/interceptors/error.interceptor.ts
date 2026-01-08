import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { inject } from '@angular/core';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      let errorMessage = 'An unexpected error occurred';

      if (error.error instanceof ErrorEvent) {
        // Client-side error
        errorMessage = `Error: ${error.error.message}`;
      } else {
        // Server-side error
        if (error.status === 401) {
          // Handled by guarded routes usually, or redirect to login
          errorMessage = 'Session expired. Please login again.';
        } else if (error.status === 403) {
          errorMessage = error.error?.message || 'You are not authorized to perform this action.';
          alert(errorMessage); // Simple Alert for now
        } else {
          errorMessage = error.error?.message || `Error Code: ${error.status}\nMessage: ${error.message}`;
        }
      }

      console.error(errorMessage);
      return throwError(() => error);
    })
  );
};
