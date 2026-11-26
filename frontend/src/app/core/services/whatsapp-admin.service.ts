import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  WhatsAppSettings,
  WhatsAppAccountStatus,
  WhatsAppTemplate,
  WhatsAppTestPayload,
  WhatsAppTestResponse,
  OfficialWhatsAppTemplate,
  TemplatesCatalogResponse,
  TemplateCreateResponse,
  BroadcastAudienceResponse,
  BroadcastCampaignItem,
  CreateBroadcastPayload
} from '../models/admin-whatsapp.models';

export * from '../models/admin-whatsapp.models';

@Injectable({
  providedIn: 'root'
})
export class WhatsAppAdminService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/admin/settings/whatsapp`;
  private broadcastApiUrl = `${environment.apiUrl}/admin/broadcasts`;

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

  /**
   * Obtener catálogo de plantillas oficiales con su estado en vivo en Meta.
   */
  getTemplatesCatalog(): Observable<TemplatesCatalogResponse> {
    return this.http.get<TemplatesCatalogResponse>(`${this.apiUrl}/templates/catalog`);
  }

  /**
   * Crear una plantilla específica del catálogo en Meta Cloud API en 1 solo clic.
   */
  createTemplate(templateKey: string): Observable<TemplateCreateResponse> {
    return this.http.post<TemplateCreateResponse>(`${this.apiUrl}/templates/create`, { template_key: templateKey });
  }

  /**
   * Crear todas las plantillas oficiales del catálogo en Meta Cloud API en 1 solo clic.
   */
  createAllTemplates(): Observable<TemplateCreateResponse> {
    return this.http.post<TemplateCreateResponse>(`${this.apiUrl}/templates/create`, { create_all: true });
  }

  /**
   * Actualizar y personalizar una plantilla para el Tenant.
   */
  updateTemplate(key: string, payload: { body: string; samples?: string[]; variable_keys?: string[]; sync_to_meta?: boolean }): Observable<TemplateCreateResponse> {
    return this.http.put<TemplateCreateResponse>(`${this.apiUrl}/templates/${key}`, payload);
  }

  /**
   * Restablecer una plantilla a su versión oficial del sistema.
   */
  resetTemplate(key: string): Observable<TemplateCreateResponse> {
    return this.http.post<TemplateCreateResponse>(`${this.apiUrl}/templates/${key}/reset`, {});
  }

  // =========================================================================
  // LISTAS DE DIFUSIÓN Y CAMPAÑAS MASIVAS (WHATSAPP)
  // =========================================================================

  /**
   * Obtener conteo y desglose de clientas según el segmento seleccionado.
   */
  getBroadcastAudience(segment: string = 'all'): Observable<BroadcastAudienceResponse> {
    return this.http.get<BroadcastAudienceResponse>(`${this.broadcastApiUrl}/audience?segment=${segment}`);
  }

  /**
   * Listar historial de campañas de difusión enviadas.
   */
  getBroadcastCampaigns(page: number = 1): Observable<{ success: boolean; campaigns: { data: BroadcastCampaignItem[]; total: number; current_page: number; last_page: number } }> {
    return this.http.get<{ success: boolean; campaigns: { data: BroadcastCampaignItem[]; total: number; current_page: number; last_page: number } }>(
      `${this.broadcastApiUrl}?page=${page}`
    );
  }

  /**
   * Enviar un mensaje de prueba al teléfono del administrador antes de lanzar la difusión.
   */
  sendTestBroadcast(payload: { phone: string; template_key?: string | null; message_body?: string; promo_text?: string; target_url?: string }): Observable<{ success: boolean; message: string }> {
    return this.http.post<{ success: boolean; message: string }>(`${this.broadcastApiUrl}/test`, payload);
  }

  /**
   * Crear y ejecutar de inmediato una campaña de difusión masiva.
   */
  createAndExecuteBroadcast(payload: CreateBroadcastPayload): Observable<{ success: boolean; message: string; campaign: BroadcastCampaignItem; summary?: any }> {
    return this.http.post<{ success: boolean; message: string; campaign: BroadcastCampaignItem; summary?: any }>(
      this.broadcastApiUrl,
      payload
    );
  }

  /**
   * Consultar detalle de una campaña con sus destinatarios y estados de entrega.
   */
  getBroadcastCampaignDetails(id: number): Observable<{ success: boolean; campaign: BroadcastCampaignItem }> {
    return this.http.get<{ success: boolean; campaign: BroadcastCampaignItem }>(`${this.broadcastApiUrl}/${id}`);
  }
}

