import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import { WaitlistItem } from '../models/admin-waitlist.models';

@Injectable({
  providedIn: 'root'
})
export class WaitlistAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/waitlist`;

  /**
   * Obtener solicitudes en lista de espera.
   */
  getWaitlist(): Observable<WaitlistItem[]> {
    return this.http.get<{ success: boolean; data: WaitlistItem[] }>(this.apiUrl).pipe(
      map(res => res.data || [])
    );
  }

  /**
   * Actualizar estado de una solicitud (PENDING, NOTIFIED, BOOKED, CANCELLED).
   */
  updateStatus(id: number, status: 'PENDING' | 'NOTIFIED' | 'BOOKED' | 'CANCELLED'): Observable<WaitlistItem> {
    return this.http.patch<{ success: boolean; data: WaitlistItem }>(`${this.apiUrl}/${id}/status`, { status }).pipe(
      map(res => res.data)
    );
  }

  /**
   * Eliminar solicitud de lista de espera.
   */
  deleteWaitlist(id: number): Observable<void> {
    return this.http.delete<{ success: boolean }>(`${this.apiUrl}/${id}`).pipe(
      map(() => void 0)
    );
  }
}
