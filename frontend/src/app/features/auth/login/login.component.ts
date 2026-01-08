import { Component, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  private authService = inject(AuthService);
  private router = inject(Router);

  // Form state
  email = signal('');
  password = signal('');
  name = signal('');
  isRegistering = signal(false);

  // Derived state from service
  isLoading = this.authService.isLoading;
  error = this.authService.error;

  onSubmit(): void {
    if (this.isRegistering()) {
      this.register();
    } else {
      this.login();
    }
  }

  private login(): void {
    this.authService.login({
      email: this.email(),
      password: this.password()
    }).subscribe({
      next: () => this.router.navigate(['/chat']),
      error: () => {} // Error handled by service
    });
  }

  private register(): void {
    this.authService.register({
      name: this.name(),
      email: this.email(),
      password: this.password(),
      password_confirmation: this.password()
    }).subscribe({
      next: () => this.login(),
      error: () => {}
    });
  }

  toggleMode(): void {
    this.isRegistering.update(v => !v);
    this.authService.error.set(null);
  }
}
