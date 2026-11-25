export interface LoyaltyBadge {
  label: string;
  description: string;
  color: string;
  bg_class: string;
}

export interface CustomerAppointmentHistoryItem {
  id: number;
  appointment_number: string;
  date: string;
  date_formatted: string;
  time_range: string;
  start_time?: string;
  end_time?: string;
  service_name: string;
  status: string;
  status_label: string;
  status_color?: string;
  total_amount: number;
  deposit_paid: number;
  balance_due: number;
  admin_notes?: string | null;
  client_notes?: string | null;
  cancellation_reason?: string | null;
}

export interface AdminCustomer {
  id: number;
  name: string;
  nickname?: string | null;
  notification_name?: string;
  phone: string;
  display_phone: string;
  clean_phone: string;
  email?: string | null;
  is_legacy: boolean;
  is_active?: boolean;
  has_appointments?: boolean;
  appointments_count?: number;
  loyalty_badge?: LoyaltyBadge | null;
  completed_appointments_count: number;
  total_spent: number;
  whatsapp_url: string;
  notes?: string | null;
  registered_at?: string;
  appointments?: CustomerAppointmentHistoryItem[];
}
