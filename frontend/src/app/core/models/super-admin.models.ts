export interface PlatformAnalytics {
  tenants_count: number;
  active_tenants_count: number;
  users_count: number;
  total_appointments: number;
  total_revenue: number;
}

export interface SuperTenant {
  id: number;
  name: string;
  slug: string;
  domain?: string | null;
  phone?: string | null;
  email?: string | null;
  is_active: boolean;
  appointments_count: number;
  users_count: number;
  whatsapp_configured: boolean;
  created_at: string;
}

export interface SuperUser {
  id: number;
  name: string;
  email: string;
  role: 'SUPER_ADMIN' | 'ADMIN' | 'RECEPTIONIST' | 'SPECIALIST';
  is_active: boolean;
  tenant_id: number | null;
  tenant_name: string;
  tenant_slug: string;
  created_at: string;
}
