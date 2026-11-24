export interface LoyaltyBadge {
  label: string;
  description: string;
  color: string;
  bg_class: string;
}

export interface AdminCustomer {
  id: number;
  name: string;
  phone: string;
  display_phone: string;
  clean_phone: string;
  email?: string | null;
  is_legacy: boolean;
  loyalty_badge?: LoyaltyBadge | null;
  completed_appointments_count: number;
  total_spent: number;
  whatsapp_url: string;
  notes?: string | null;
  registered_at?: string;
}
