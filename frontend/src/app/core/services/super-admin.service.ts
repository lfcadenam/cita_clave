import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { PlatformAnalytics, SuperTenant, SuperUser } from '../models/super-admin.models';

export * from '../models/super-admin.models';

@Injectable({
  providedIn: 'root'
})
export class SuperAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/super-admin`;

  /**
   * Obtener métricas y analíticas globales de la plataforma.
   */
  getAnalytics(): Observable<PlatformAnalytics> {
    return this.http.get<{ success: boolean; data: PlatformAnalytics }>(`${this.apiUrl}/analytics`).pipe(
      map(res => res.data)
    );
  }

  /**
   * Listar todos los tenants registrados en Nuvex.
   */
  getTenants(): Observable<SuperTenant[]> {
    return this.http.get<{ success: boolean; data: SuperTenant[] }>(`${this.apiUrl}/tenants`).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Crear nuevo tenant / salón.
   */
  createTenant(payload: { name: string; slug?: string; domain?: string; phone?: string; email?: string }): Observable<SuperTenant> {
    return this.http.post<{ success: boolean; data: SuperTenant }>(`${this.apiUrl}/tenants`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualizar tenant existente.
   */
  updateTenant(id: number, payload: Partial<SuperTenant>): Observable<SuperTenant> {
    return this.http.put<{ success: boolean; data: SuperTenant }>(`${this.apiUrl}/tenants/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Listar todos los usuarios del sistema.
   */
  getUsers(): Observable<SuperUser[]> {
    return this.http.get<{ success: boolean; data: any[] }>(`${this.apiUrl}/users`).pipe(
      map(res => (res.data || []).map(u => ({
        id: u.id,
        name: u.name,
        email: u.email,
        phone: u.phone ?? null,
        role: u.role,
        is_active: Boolean(u.is_active),
        tenant_id: u.tenant ? u.tenant.id : (u.tenant_id ?? null),
        tenant_name: u.tenant ? u.tenant.name : (u.tenant_name ?? 'Plataforma Global'),
        tenant_slug: u.tenant ? u.tenant.slug : (u.tenant_slug ?? ''),
        tenant: u.tenant ?? null,
        created_at: u.created_at
      })))
    );
  }

  /**
   * Crear nuevo usuario administrativo o especialista.
   */
  createUser(payload: { name: string; email: string; password: string; role: string; tenant_id?: number | null; is_active?: boolean }): Observable<SuperUser> {
    return this.http.post<{ success: boolean; data: SuperUser }>(`${this.apiUrl}/users`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualizar usuario.
   */
  updateUser(id: number, payload: { name?: string; email?: string; password?: string; role?: string; tenant_id?: number | null; is_active?: boolean }): Observable<SuperUser> {
    return this.http.put<{ success: boolean; data: SuperUser }>(`${this.apiUrl}/users/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Eliminar usuario.
   */
  deleteUser(id: number): Observable<void> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/users/${id}`).pipe(
      map(() => void 0)
    );
  }
}
