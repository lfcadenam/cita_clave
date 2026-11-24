import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  WhatsAppSettings,
  WhatsAppAccountStatus,
  WhatsAppTemplate,
  WhatsAppTestPayload,
  WhatsAppTestResponse
} from '../models/admin-whatsapp.models';

@Injectable({
  providedIn: 'root'
})
export class WhatsAppAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/settings/whatsapp`;

  /**
   * Obtener configuración actual de WhatsApp Meta Cloud API y su estado en vivo.
   */
  getSettings(): Observable<WhatsAppSettings> {
    return this.http.get<{ success: boolean; data: WhatsAppSettings }>(this.apiUrl).pipe(
      map(res => res.data)
    );
  }

  /**
   * Verificar en vivo la conectividad con Meta Graph API / Evolution API.
   */
  checkStatus(): Observable<WhatsAppAccountStatus> {
    return this.http.get<{ success: boolean; account_status: WhatsAppAccountStatus }>(`${this.apiUrl}/status`).pipe(
      map(res => res.account_status)
    );
  }

  /**
   * Guardar cambios en configuración de WhatsApp.
   */
  updateSettings(payload: Partial<WhatsAppSettings>): Observable<{ data: WhatsAppSettings; message: string; account_status?: WhatsAppAccountStatus }> {
    return this.http.put<{ success: boolean; message: string; data: WhatsAppSettings; account_status?: WhatsAppAccountStatus }>(this.apiUrl, payload).pipe(
      map(res => ({ data: res.data, message: res.message, account_status: res.account_status }))
    );
  }

  /**
   * Registrar / activar el número con PIN de 6 dígitos en Meta (Resuelve Error #133010).
   */
  registerPin(pin: string, extraCreds?: { meta_phone_number_id?: string; meta_access_token?: string }): Observable<{ success: boolean; message: string; account_status?: WhatsAppAccountStatus }> {
    return this.http.post<{ success: boolean; message: string; account_status?: WhatsAppAccountStatus }>(
      `${this.apiUrl}/register-pin`,
      { meta_pin: pin, ...extraCreds }
    );
  }

  /**
   * Sincronizar y obtener plantillas oficiales aprobadas en la WABA de Meta.
   */
  syncTemplates(): Observable<{ success: boolean; templates: WhatsAppTemplate[]; message?: string }> {
    return this.http.get<{ success: boolean; templates: WhatsAppTemplate[]; message?: string }>(`${this.apiUrl}/templates`);
  }

  /**
   * Enviar mensaje de prueba (vía plantilla oficial o texto libre).
   */
  sendTest(payload: WhatsAppTestPayload): Observable<WhatsAppTestResponse> {
    return this.http.post<WhatsAppTestResponse>(`${this.apiUrl}/test`, payload);
  }
}
