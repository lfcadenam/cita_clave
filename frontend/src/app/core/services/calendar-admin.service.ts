import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { AdminSlotsResponse, CalendarSummary } from '../models/admin-appointment.models';

@Injectable({
  providedIn: 'root'
})
export class CalendarAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/calendar`;

  /**
   * Obtener horarios disponibles agrupados en Mañana y Tarde.
   */
  getSlots(serviceId: number, date: string, strictAntiGaps: boolean = false, durationMinutes?: number): Observable<AdminSlotsResponse> {
    let params = new HttpParams()
      .set('service_id', serviceId.toString())
      .set('date', date)
      .set('strict_anti_gaps', strictAntiGaps ? '1' : '0');

    if (durationMinutes && durationMinutes > 0) {
      params = params.set('duration_minutes', durationMinutes.toString());
    }

    return this.http.get<{ success: boolean; data: AdminSlotsResponse }>(`${this.apiUrl}/slots`, { params }).pipe(
      map(res => res.data)
    );
  }

  /**
   * Obtener resumen de KPIs y Spotlight para el día seleccionado.
   */
  getSummary(date?: string): Observable<CalendarSummary> {
    let params = new HttpParams();
    if (date) {
      params = params.set('date', date);
    }

    return this.http.get<{ success: boolean; data: CalendarSummary }>(`${this.apiUrl}/summary`, { params }).pipe(
      map(res => res.data)
    );
  }
}
