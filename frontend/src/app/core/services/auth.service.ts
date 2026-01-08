import { Injectable, signal, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap, catchError, of, switchMap, map } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { User } from '../../shared/models';

interface LoginCredentials {
  email: string;
  password: string;
}

interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface AuthResponse {
  access_token: string;
  token_type: string;
  expires_in: number;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly apiUrl = environment.apiUrl;

  // Reactive state with signals
  readonly currentUser = signal<User | null>(null);
  readonly isLoading = signal(false);
  readonly error = signal<string | null>(null);

  // Computed values
  readonly isAuthenticated = computed(() => !!this.currentUser() && !!this.getToken());
  readonly userName = computed(() => this.currentUser()?.name ?? 'Guest');

  constructor(
    private http: HttpClient,
    private router: Router
  ) {
    this.loadUserFromStorage();
  }

  private loadUserFromStorage(): void {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      try {
        this.currentUser.set(JSON.parse(storedUser));
      } catch {
        localStorage.removeItem('user');
      }
    }
  }

  login(credentials: LoginCredentials): Observable<AuthResponse> {
    this.isLoading.set(true);
    this.error.set(null);

    return this.http.post<AuthResponse>(`${this.apiUrl}/login`, credentials).pipe(
      tap((response) => {
        if (response.access_token) {
          localStorage.setItem('token', response.access_token);
        }
      }),
      switchMap((response) => {
        // Fetch user and wait for it to complete before returning
        return this.http.get<User>(`${this.apiUrl}/me`).pipe(
          tap((user) => {
            this.currentUser.set(user);
            localStorage.setItem('user', JSON.stringify(user));
            this.isLoading.set(false);
          }),
          map(() => response) // Return original auth response
        );
      }),
      catchError((err) => {
        this.error.set(err.error?.message || 'Login failed');
        this.isLoading.set(false);
        throw err;
      })
    );
  }

  register(data: RegisterData): Observable<any> {
    this.isLoading.set(true);
    this.error.set(null);

    return this.http.post(`${this.apiUrl}/register`, data).pipe(
      tap(() => this.isLoading.set(false)),
      catchError((err) => {
        this.error.set(err.error?.message || 'Registration failed');
        this.isLoading.set(false);
        throw err;
      })
    );
  }

  logout(): void {
    this.http.post(`${this.apiUrl}/logout`, {}).subscribe({
      complete: () => this.clearSession()
    });
    // Clear immediately for UX
    this.clearSession();
  }

  private clearSession(): void {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.currentUser.set(null);
    this.router.navigate(['/login']);
  }

  fetchCurrentUser(): void {
    this.http.get<User>(`${this.apiUrl}/me`).subscribe({
      next: (user) => {
        this.currentUser.set(user);
        localStorage.setItem('user', JSON.stringify(user));
      },
      error: () => this.clearSession()
    });
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }

  refreshToken(): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/refresh`, {}).pipe(
      tap((response) => {
        if (response.access_token) {
          localStorage.setItem('token', response.access_token);
        }
      })
    );
  }
}
