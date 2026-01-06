import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = 'http://localhost:8000/api';
  currentUser = signal<any>(null);

  constructor(private http: HttpClient) {
    // Try to load user from storage on init
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
      this.currentUser.set(JSON.parse(storedUser));
    }
  }

  login(credentials: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/login`, credentials).pipe(
      tap((response: any) => {
        if (response.access_token) {
          localStorage.setItem('token', response.access_token);
          // Fetch user details after login usually, or if provided in login response
          this.getMe().subscribe();
        }
      })
    );
  }

  register(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/register`, data);
  }

  logout() {
    this.http.post(`${this.apiUrl}/logout`, {}).subscribe();
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    this.currentUser.set(null);
  }

  getMe(): Observable<any> {
    return this.http.get(`${this.apiUrl}/me`).pipe(
      tap((user: any) => {
        this.currentUser.set(user);
        localStorage.setItem('user', JSON.stringify(user));
      })
    );
  }

  getToken(): string | null {
    return localStorage.getItem('token');
  }
}
