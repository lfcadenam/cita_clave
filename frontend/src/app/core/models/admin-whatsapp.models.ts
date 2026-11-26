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

export interface TemplateVariableItem {
  key: string;
  label: string;
  sample: string;
  token: string;
}

export interface OfficialWhatsAppTemplate {
  key: string;
  name: string;
  display_name: string;
  description: string;
  category: string;
  language: string;
  body: string;
  samples: string[];
  variable_keys?: string[];
  trigger: string;
  exists_in_meta: boolean;
  meta_status: string;
  meta_id?: string | null;
  meta_language?: string;
  meta_category?: string;
  preview_text: string;
  is_customized?: boolean;
  original_body?: string;
}

export interface TemplatesCatalogResponse {
  success: boolean;
  meta_connected: boolean;
  meta_error_message?: string | null;
  available_variables?: TemplateVariableItem[];
  templates: OfficialWhatsAppTemplate[];
}

export interface TemplateCreateResponse {
  success: boolean;
  message: string;
  data?: any;
  catalog?: OfficialWhatsAppTemplate[];
}

export interface BroadcastAudienceCounts {
  all: number;
  recurring: number;
  inactive_30: number;
  new: number;
}

export interface BroadcastAudienceSample {
  id: number;
  name: string;
  nickname?: string | null;
  display_name: string;
  phone: string;
  is_legacy: boolean;
  total_appointments: number;
}

export interface BroadcastAudienceResponse {
  success: boolean;
  current_segment: string;
  counts: BroadcastAudienceCounts;
  preview: {
    segment: string;
    total_count: number;
    sample: BroadcastAudienceSample[];
  };
}

export interface BroadcastRecipientItem {
  id: number;
  customer_id?: number | null;
  phone: string;
  client_name: string;
  client_nickname?: string | null;
  status: 'pending' | 'sent' | 'failed';
  error_message?: string | null;
  sent_at?: string | null;
}

export interface BroadcastCampaignItem {
  id: number;
  name: string;
  channel: string;
  template_key?: string | null;
  segment: string;
  message_body: string;
  status: 'draft' | 'processing' | 'completed' | 'failed' | 'cancelled';
  total_recipients: number;
  sent_count: number;
  failed_count: number;
  metadata?: any;
  created_at: string;
  completed_at?: string | null;
  recipients?: BroadcastRecipientItem[];
}

export interface CreateBroadcastPayload {
  name: string;
  segment: string;
  template_key?: string | null;
  message_body: string;
  promo_text?: string;
  target_url?: string;
  customer_ids?: number[];
}

