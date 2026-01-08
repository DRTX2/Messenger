import { inject } from '@angular/core';
import { Router, type CanActivateFn } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Auth Guard - Protects routes that require authentication
 * Uses the new functional guard pattern (Angular 15+)
 */
export const authGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.isAuthenticated()) {
    return true;
  }

  // Redirect to login page
  router.navigate(['/login']);
  return false;
};

/**
 * Guest Guard - Protects routes for non-authenticated users (login, register)
 */
export const guestGuard: CanActivateFn = (route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (!authService.isAuthenticated()) {
    return true;
  }

  // Already logged in, redirect to chat
  router.navigate(['/chat']);
  return false;
};
