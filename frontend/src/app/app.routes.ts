import { Routes } from '@angular/router';
import { authGuard, guestGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    redirectTo: 'chat',
    pathMatch: 'full'
  },
  {
    path: 'login',
    loadChildren: () => import('./features/auth/auth.routes').then(m => m.AUTH_ROUTES),
    canActivate: [guestGuard]
  },
  {
    path: 'chat',
    loadChildren: () => import('./features/chat/chat.routes').then(m => m.CHAT_ROUTES),
    canActivate: [authGuard]
  },
  {
    path: '**',
    redirectTo: 'chat'
  }
];
