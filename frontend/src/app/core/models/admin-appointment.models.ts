import { LoyaltyBadge } from './admin-customer.models';

export type AppointmentStatus =
  | 'PENDING_DEPOSIT'
  | 'PENDING_VERIFICATION'
  | 'CONFIRMED'
  | 'IN_PROGRESS'
  | 'COMPLETED'
  | 'CANCELLED'
  | 'NO_SHOW';

export type PaymentMethod =
  | 'BOLD_ONLINE'
  | 'NEQUI_TRANSFER'
  | 'CASH_AT_LOCATION';

export interface AdminAppointmentService {
  id: number;
  name: string;
  duration_minutes: number;
  formatted_duration: string;
  base_price: number;
}

export interface AdminAppointmentClient {
  name: string;
  phone: string;
  display_phone: string;
  email?: string | null;
  whatsapp_url: string;
  loyalty_badge?: LoyaltyBadge | null;
  is_new: boolean;
}

export interface AdminAppointmentFinancials {
  total_amount: number;
  deposit_amount: number;
  deposit_paid: number;
  balance_due: number;
}

export interface AdminAppointment {
  id: number;
  appointment_number: string;
  date: string;
  date_formatted: string;
  start_time: string;
  end_time: string;
  time_range: string;
  status: AppointmentStatus;
  status_label: string;
  status_color: string;
  payment_method: PaymentMethod;
  payment_method_label: string;
  financials: AdminAppointmentFinancials;
  client: AdminAppointmentClient;
  service: AdminAppointmentService;
  notes: {
    admin_notes?: string | null;
    client_notes?: string | null;
  };
  verification?: {
    deposit_proof_image?: string | null;
    verification_notes?: string | null;
    verified_at?: string | null;
  };
  cancellation?: {
    cancellation_reason?: string | null;
    cancelled_at?: string | null;
  };
  created_at: string;
}

export interface AdminSlot {
  start_time: string;
  end_time: string;
  start_formatted: string;
  end_formatted: string;
  duration_minutes: number;
  label: string;
  is_recommended: boolean;
}

export interface AdminSlotsGroup {
  label: string;
  count: number;
  slots: AdminSlot[];
}

export interface AdminSlotsResponse {
  service: {
    id: number;
    name: string;
    duration_minutes: number;
    base_price: number;
    deposit_amount: number;
  };
  date: string;
  day_name: string;
  is_available: boolean;
  total_slots: number;
  groups: {
    morning: AdminSlotsGroup;
    afternoon: AdminSlotsGroup;
  };
  slots: AdminSlot[];
}

export interface CalendarKPIs {
  total_appointments: number;
  confirmed_count: number;
  pending_verification_count: number;
  in_progress_count: number;
  completed_count: number;
  cancelled_count: number;
  financials: {
    expected_revenue: number;
    collected_deposits: number;
    pending_balance: number;
  };
}

export interface SpotlightAppointment {
  id: number;
  appointment_number: string;
  client_name: string;
  client_phone: string;
  whatsapp_url?: string;
  service_name: string;
  start_time: string;
  end_time: string;
  status: AppointmentStatus;
  status_label: string;
  status_color: string;
  loyalty_badge?: LoyaltyBadge | null;
}

export interface CalendarSummary {
  date: string;
  date_formatted: string;
  kpis: CalendarKPIs;
  spotlight: SpotlightAppointment | null;
  appointments_count: number;
}
