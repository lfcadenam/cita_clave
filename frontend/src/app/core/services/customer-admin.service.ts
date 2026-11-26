import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AdminCustomer } from '../models/admin-customer.models';

export * from '../models/admin-customer.models';

export interface CustomerPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginatedCustomersResponse {
  data: AdminCustomer[];
  meta: CustomerPaginationMeta;
}

@Injectable({
  providedIn: 'root'
})
export class CustomerAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/customers`;

  /**
   * Obtener todas las clientas (primeros 100 por defecto).
   */
  getCustomers(): Observable<AdminCustomer[]> {
    return this.searchCustomers('', 100);
  }

  /**
   * Buscar clientas por nombre, teléfono o email para autocompletado y gestión rápida.
   */
  searchCustomers(query: string = '', perPage: number = 50): Observable<AdminCustomer[]> {
    let params = new HttpParams().set('per_page', perPage.toString());
    if (query.trim()) {
      params = params.set('q', query.trim());
    }

    return this.http.get<{ success: boolean; data: AdminCustomer[] }>(this.apiUrl, { params }).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Obtener listado de clientas con paginación del backend (por defecto 10 registros).
   */
  getCustomersPaginated(page: number = 1, perPage: number = 10, query: string = ''): Observable<PaginatedCustomersResponse> {
    let params = new HttpParams()
      .set('page', page.toString())
      .set('per_page', perPage.toString());

    if (query.trim()) {
      params = params.set('q', query.trim());
    }

    return this.http.get<{ success: boolean; data: AdminCustomer[]; meta: CustomerPaginationMeta }>(this.apiUrl, { params }).pipe(
      map(res => ({
        data: res.data || [],
        meta: res.meta || { current_page: page, last_page: 1, per_page: perPage, total: res.data?.length || 0 }
      }))
    );
  }

  /**
   * Consultar perfil detallado con historial de citas de la clienta.
   */
  getCustomer(id: number): Observable<AdminCustomer> {
    return this.http.get<{ success: boolean; data: AdminCustomer }>(`${this.apiUrl}/${id}`).pipe(
      map(res => res.data)
    );
  }

  /**
   * Crear nueva clienta en el CRM.
   */
  createCustomer(payload: Partial<AdminCustomer>): Observable<AdminCustomer> {
    return this.http.post<{ success: boolean; data: AdminCustomer }>(this.apiUrl, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualizar clienta existente en el CRM.
   */
  updateCustomer(id: number, payload: Partial<AdminCustomer>): Observable<AdminCustomer> {
    return this.http.put<{ success: boolean; data: AdminCustomer }>(`${this.apiUrl}/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Eliminar o desactivar clienta según tenga citas asociadas.
   */
  deleteCustomer(id: number): Observable<{ success: boolean; action: 'deactivated' | 'deleted'; message: string; data?: AdminCustomer }> {
    return this.http.delete<{ success: boolean; action: 'deactivated' | 'deleted'; message: string; data?: AdminCustomer }>(`${this.apiUrl}/${id}`);
  }

  /**
   * Alternar estado activo / inactivo de la clienta.
   */
  toggleActive(id: number): Observable<{ success: boolean; message: string; data: AdminCustomer }> {
    return this.http.post<{ success: boolean; message: string; data: AdminCustomer }>(`${this.apiUrl}/${id}/toggle-active`, {});
  }
}
