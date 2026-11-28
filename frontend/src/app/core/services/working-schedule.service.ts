import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { WorkingSchedule, BlockedSlot } from '../models/admin-schedule.models';

export * from '../models/admin-schedule.models';

@Injectable({
  providedIn: 'root'
})
export class WorkingScheduleService {
  private http = inject(HttpClient);
  private scheduleUrl = `${environment.apiUrl}/admin/working-schedules`;
  private blockedUrl = `${environment.apiUrl}/admin/blocked-slots`;

  /**
   * Obtener jornadas semanales (Lunes a Domingo).
   */
  getSchedules(): Observable<WorkingSchedule[]> {
    return this.http.get<{ success: boolean; data: WorkingSchedule[] }>(this.scheduleUrl).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Actualizar jornada de un día específico.
   */
  updateSchedule(id: number, payload: Partial<WorkingSchedule>): Observable<WorkingSchedule> {
    return this.http.put<{ success: boolean; data: WorkingSchedule }>(`${this.scheduleUrl}/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualización masiva de las jornadas semanales.
   */
  batchUpdateSchedules(schedules: Partial<WorkingSchedule>[]): Observable<WorkingSchedule[]> {
    return this.http.post<{ success: boolean; data: WorkingSchedule[] }>(`${this.scheduleUrl}/batch`, { schedules }).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Obtener franjas horarias bloqueadas (almuerzos, descansos, vacaciones).
   */
  getBlockedSlots(upcomingOnly: boolean = false): Observable<BlockedSlot[]> {
    const url = upcomingOnly ? `${this.blockedUrl}?upcoming=1` : this.blockedUrl;
    return this.http.get<{ success: boolean; data: BlockedSlot[] }>(url).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Crear nuevo bloqueo de horario.
   */
  createBlockedSlot(payload: Partial<BlockedSlot>): Observable<BlockedSlot> {
    return this.http.post<{ success: boolean; data: BlockedSlot }>(this.blockedUrl, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Actualizar bloqueo de horario existente.
   */
  updateBlockedSlot(id: number, payload: Partial<BlockedSlot>): Observable<BlockedSlot> {
    return this.http.put<{ success: boolean; data: BlockedSlot }>(`${this.blockedUrl}/${id}`, payload).pipe(
      map(res => res.data)
    );
  }

  /**
   * Eliminar bloqueo de horario.
   */
  deleteBlockedSlot(id: number): Observable<void> {
    return this.http.delete<{ success: boolean }>(`${this.blockedUrl}/${id}`).pipe(
      map(() => void 0)
    );
  }
}
