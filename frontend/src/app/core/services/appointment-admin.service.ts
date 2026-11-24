import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AdminAppointment, AppointmentStatus } from '../models/admin-appointment.models';

export interface AppointmentFilterParams {
  date?: string;
  start_date?: string;
  end_date?: string;
  status?: string;
  service_id?: number;
  search?: string;
  all?: boolean;
  per_page?: number;
  page?: number;
}

export interface CreateAppointmentPayload {
  service_id: number;
  service_ids?: number[];
  appointment_date: string;
  start_time: string;
  end_time?: string;
  client_name: string;
  client_phone: string;
  client_email?: string;
  deposit_paid?: number;
  deposit_amount?: number;
  total_amount?: number;
  status?: AppointmentStatus;
  payment_method?: string;
  admin_notes?: string;
  client_notes?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AppointmentAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/appointments`;

  /**
   * Listar citas con filtros avanzados.
   */
  getAppointments(filters: AppointmentFilterParams = {}): Observable<{ data: AdminAppointment[]; meta?: any }> {
    let params = new HttpParams();

    if (filters.date) params = params.set('date', filters.date);
    if (filters.start_date) params = params.set('start_date', filters.start_date);
    if (filters.end_date) params = params.set('end_date', filters.end_date);
    if (filters.status && filters.status !== 'all') params = params.set('status', filters.status);
    if (filters.service_id) params = params.set('service_id', filters.service_id.toString());
    if (filters.search) params = params.set('search', filters.search);
    if (filters.all) params = params.set('all', '1');
    if (filters.per_page) params = params.set('per_page', filters.per_page.toString());
    if (filters.page) params = params.set('page', filters.page.toString());

    return this.http.get<{ success: boolean; data: AdminAppointment[]; meta?: any }>(this.apiUrl, { params }).pipe(
      map(res => ({
        data: res.data || [],
        meta: res.meta
      }))
    );
  }

  /**
   * Consultar detalle de una cita por ID.
   */
  getAppointment(id: number): Observable<AdminAppointment> {
    return this.http.get<{ success: boolean; data: AdminAppointment }>(`${this.apiUrl}/${id}`).pipe(
      map(res => res.data)
    );
  }

  /**
   * Agendar nueva cita interna con validación de conflictos.
   */
  createAppointment(payload: CreateAppointmentPayload): Observable<AdminAppointment> {
    return this.http.post<{ success: boolean; data: AdminAppointment }>(this.apiUrl, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Modificar datos de una cita existente.
   */
  updateAppointment(id: number, payload: Partial<CreateAppointmentPayload>): Observable<AdminAppointment> {
    return this.http.put<{ success: boolean; data: AdminAppointment }>(`${this.apiUrl}/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Transición de estado de cita.
   */
  updateStatus(id: number, status: AppointmentStatus, cancellationReason?: string): Observable<AdminAppointment> {
    return this.http.patch<{ success: boolean; data: AdminAppointment }>(`${this.apiUrl}/${id}/status`, {
      status,
      cancellation_reason: cancellationReason
    }).pipe(
      map(res => res.data)
    );
  }

  /**
   * Validar y confirmar comprobante de transferencia Nequi.
   */
  approveNequi(id: number, depositPaid?: number, notes?: string): Observable<AdminAppointment> {
    return this.http.post<{ success: boolean; data: AdminAppointment }>(`${this.apiUrl}/${id}/approve-nequi`, {
      deposit_paid: depositPaid,
      notes
    }).pipe(
      map(res => res.data)
    );
  }

  /**
   * Rechazar comprobante de transferencia Nequi y notificar al cliente.
   */
  rejectNequi(id: number, reason?: string): Observable<AdminAppointment> {
    return this.http.post<{ success: boolean; data: AdminAppointment }>(`${this.apiUrl}/${id}/reject-nequi`, {
      reason
    }).pipe(
      map(res => res.data)
    );
  }
}
