export interface WhatsAppAccountStatus {
  connected: boolean;
  state?: string;
  message?: string;
  phone_number_id?: string;
  display_phone_number?: string;
  verified_name?: string;
  quality_rating?: string;
  code_verification_status?: string;
  error_code?: number | string;
}

export interface WhatsAppTemplate {
  name: string;
  status: string;
  language: string;
  category?: string;
  id?: string;
}

export interface WhatsAppSettings {
  whatsapp_driver: string;
  whatsapp_number: string | null;
  meta_phone_number_id: string | null;
  meta_waba_id: string | null;
  meta_access_token?: string | null;
  meta_verify_token: string | null;
  webhook_url?: string;
  salon_name?: string;
  is_configured: boolean;
  account_status?: WhatsAppAccountStatus;
}

export interface WhatsAppTestPayload {
  phone: string;
  test_mode?: 'template' | 'text';
  template_name?: string;
  template_lang?: string;
  message?: string;
}

export interface WhatsAppTestResponse {
  success: boolean;
  message: string;
  error_code?: number | string | null;
  has_pin_error?: boolean;
  account_status?: WhatsAppAccountStatus;
}
