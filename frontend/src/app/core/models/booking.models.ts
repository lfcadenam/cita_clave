export interface Service {
  id: number;
  name: string;
  slug?: string;
  category?: string;
  description?: string;
  duration_minutes: number;
  base_price?: number;
  price?: number;
  deposit_amount: number;
  image_url?: string;
  is_active: boolean;
  featured?: boolean;
}

export interface AvailableSlot {
  start_time: string;
  end_time: string;
  formatted_start?: string;
  formatted_end?: string;
  is_continuous?: boolean;
}

export interface ClientProfile {
  name: string;
  phone: string;
  email: string;
  instagram?: string;
  notes?: string;
  preferences?: string[];
  total_past_appointments?: number;
  is_vip?: boolean;
}

export interface AppointmentBookingRequest {
  service_id: number;
  booking_date: string;
  start_time: string;
  client_name: string;
  client_phone: string;
  client_email?: string;
  client_notes?: string;
  payment_method: 'NEQUI' | 'BOLD' | 'CASH';
  receipt_file?: File;
}

export interface AppointmentResponse {
  success: boolean;
  message: string;
  data: {
    id: number;
    appointment_number: string;
    client_name: string;
    client_phone: string;
    service_name: string;
    booking_date: string;
    start_time: string;
    end_time: string;
    status: string;
    payment_status: string;
    payment_method: string;
    total_price: number;
    deposit_amount: number;
    balance_due: number;
    whatsapp_link?: string;
    receipt_path?: string;
  };
}

export interface NequiInfoResponse {
  success: boolean;
  data: {
    account_number: string;
    account_holder: string;
    qr_code_url?: string;
    instructions: string;
  };
}

export interface SalonProfile {
  id?: number;
  name: string;
  short_name?: string;
  display_name: string;
  business_type: string;
  slug: string;
  city: string;
  address?: string;
  phone?: string;
  whatsapp_number?: string;
  whatsapp_url: string;
  email?: string;
  specialties: string;
  schedule_summary: string;
  primary_color?: string;
  logo_path?: string;
}

