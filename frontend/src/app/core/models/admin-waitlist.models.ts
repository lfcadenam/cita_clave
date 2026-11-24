export interface WaitlistItem {
  id: number;
  client_name: string;
  client_phone: string;
  client_email?: string | null;
  requested_date: string;
  preferred_time_range?: string | null;
  status: 'PENDING' | 'NOTIFIED' | 'BOOKED' | 'CANCELLED';
  notes?: string | null;
  notified_at?: string | null;
  created_at: string;
  service?: {
    id: number;
    name: string;
    duration_minutes: number;
  } | null;
}
