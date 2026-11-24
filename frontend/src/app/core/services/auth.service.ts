import { Injectable, inject, signal, computed } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, catchError, of, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AdminUser, LoginCredentials, LoginResponse, Tenant } from '../models/auth.models';

const TOKEN_KEY = 'nuvex_admin_token';
const USER_KEY = 'nuvex_admin_user';
const TENANT_KEY = 'nuvex_admin_tenant';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);
  private apiUrl = environment.apiUrl;

  // Reactive State Signals
  readonly token = signal<string | null>(this.getStoredToken());
  readonly currentUser = signal<AdminUser | null>(this.getStoredUser());
  readonly currentTenant = signal<Tenant | null>(this.getStoredTenant());

  // Computed Selectors
  readonly isAuthenticated = computed(() => !!this.token());
  readonly isAdmin = computed(() => {
    const role = this.currentUser()?.role;
    return role === 'ADMIN' || role === 'SUPER_ADMIN';
  });
  readonly isSuperAdmin = computed(() => this.currentUser()?.role === 'SUPER_ADMIN');

  /**
   * Iniciar sesión en el portal administrativo.
   */
  login(credentials: LoginCredentials): Observable<LoginResponse> {
    return this.http.post<LoginResponse>(`${this.apiUrl}/auth/login`, credentials).pipe(
      tap(res => {
        if (res.success && res.data.token) {
          this.setSession(res.data.token, res.data.user, res.data.tenant);
        }
      })
    );
  }

  /**
   * Consultar datos del usuario autenticado y su salón.
   */
  loadProfile(): Observable<any> {
    if (!this.token()) {
      return of(null);
    }

    return this.http.get<{ success: boolean; data: { user: AdminUser; tenant: Tenant } }>(`${this.apiUrl}/auth/me`).pipe(
      tap(res => {
        if (res.success && res.data) {
          this.currentUser.set(res.data.user);
          this.currentTenant.set(res.data.tenant);
          this.persistItem(USER_KEY, res.data.user);
          this.persistItem(TENANT_KEY, res.data.tenant);
        }
      }),
      catchError(err => {
        if (err.status === 401) {
          this.clearSession();
        }
        return throwError(() => err);
      })
    );
  }

  /**
   * Cerrar sesión y limpiar estado.
   */
  logout(): void {
    if (this.token()) {
      this.http.post(`${this.apiUrl}/auth/logout`, {}).subscribe({
        next: () => {},
        error: () => {}
      });
    }
    this.clearSession();
    this.router.navigate(['/admin/login']);
  }

  /**
   * Guardar sesión en Storage y Signals.
   */
  setSession(token: string, user: AdminUser, tenant: Tenant | null): void {
    this.token.set(token);
    this.currentUser.set(user);
    this.currentTenant.set(tenant);

    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.setItem(TOKEN_KEY, token);
      localStorage.setItem(USER_KEY, JSON.stringify(user));
      if (tenant) {
        localStorage.setItem(TENANT_KEY, JSON.stringify(tenant));
      } else {
        localStorage.removeItem(TENANT_KEY);
      }
    }
  }

  /**
   * Limpiar credenciales locales.
   */
  clearSession(): void {
    this.token.set(null);
    this.currentUser.set(null);
    this.currentTenant.set(null);

    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
      localStorage.removeItem(TENANT_KEY);
    }
  }

  private getStoredToken(): string | null {
    if (typeof window !== 'undefined' && window.localStorage) {
      return localStorage.getItem(TOKEN_KEY);
    }
    return null;
  }

  private getStoredUser(): AdminUser | null {
    if (typeof window !== 'undefined' && window.localStorage) {
      const data = localStorage.getItem(USER_KEY);
      return data ? JSON.parse(data) : null;
    }
    return null;
  }

  private getStoredTenant(): Tenant | null {
    if (typeof window !== 'undefined' && window.localStorage) {
      const data = localStorage.getItem(TENANT_KEY);
      return data ? JSON.parse(data) : null;
    }
    return null;
  }

  private persistItem(key: string, value: any): void {
    if (typeof window !== 'undefined' && window.localStorage) {
      localStorage.setItem(key, JSON.stringify(value));
    }
  }
}
