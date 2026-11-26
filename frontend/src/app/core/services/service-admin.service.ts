import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface AdminServiceItem {
  id: number;
  name: string;
  category?: string;
  description?: string;
  duration_minutes: number;
  formatted_duration?: string;
  base_price: number;
  deposit_amount: number;
  is_active: boolean;
  is_favorite?: boolean;
  requires_deposit?: boolean;
  image_url?: string | null;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedServicesResponse {
  data: AdminServiceItem[];
  meta: PaginationMeta;
}

@Injectable({
  providedIn: 'root'
})
export class ServiceAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/services`;

  /**
   * Obtener catálogo de tratamientos completo (sin paginación) para modales y dropdowns.
   */
  getServices(activeOnly: boolean = false): Observable<AdminServiceItem[]> {
    let params = new HttpParams().set('all', '1');
    if (activeOnly) {
      params = params.set('active_only', '1');
    }
    return this.http.get<{ success: boolean; data: AdminServiceItem[] }>(this.apiUrl, { params }).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Obtener catálogo de tratamientos con paginación del backend (por defecto 10 registros).
   */
  getServicesPaginated(page: number = 1, perPage: number = 10, query: string = ''): Observable<PaginatedServicesResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (query.trim()) {
      params = params.set('q', query.trim());
    }

    return this.http.get<{ success: boolean; data: AdminServiceItem[]; meta: PaginationMeta }>(this.apiUrl, { params }).pipe(
      map(res => ({
        data: res.data || [],
        meta: res.meta || { current_page: page, last_page: 1, per_page: perPage, total: res.data?.length || 0 }
      }))
    );
  }

  /**
   * Consultar detalle de un tratamiento específico.
   */
  getService(id: number): Observable<AdminServiceItem> {
    return this.http.get<{ success: boolean; data: AdminServiceItem }>(`${this.apiUrl}/${id}`).pipe(
      map(res => res.data)
    );
  }

  /**
   * Crear nuevo tratamiento.
   */
  createService(payload: Partial<AdminServiceItem> | FormData): Observable<AdminServiceItem> {
    return this.http.post<{ success: boolean; data: AdminServiceItem }>(this.apiUrl, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualizar tratamiento existente.
   */
  updateService(id: number, payload: Partial<AdminServiceItem> | FormData): Observable<AdminServiceItem> {
    if (payload instanceof FormData) {
      return this.http.post<{ success: boolean; data: AdminServiceItem }>(`${this.apiUrl}/${id}`, payload).pipe(
        map(res => res.data)
      );
    }
    return this.http.put<{ success: boolean; data: AdminServiceItem }>(`${this.apiUrl}/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Alternar estado favorito/destacado de un tratamiento.
   */
  toggleFavorite(id: number): Observable<AdminServiceItem> {
    return this.http.patch<{ success: boolean; data: AdminServiceItem }>(`${this.apiUrl}/${id}/toggle-favorite`, {}).pipe(
      map(res => res.data)
    );
  }

  /**
   * Eliminar o desactivar tratamiento.
   */
  deleteService(id: number): Observable<void> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/${id}`).pipe(
      map(() => void 0)
    );
  }
}
