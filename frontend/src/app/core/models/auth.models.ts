export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: 'SUPER_ADMIN' | 'ADMIN' | 'CLIENT';
  role_label: string;
  phone?: string;
}

export interface Tenant {
  id: number;
  name: string;
  slug: string;
  logo_url?: string | null;
  domain?: string | null;
  phone?: string | null;
  address?: string | null;
}

export interface LoginCredentials {
  email: string;
  password: string;
  tenant_slug?: string;
}

export interface LoginResponse {
  success: boolean;
  message: string;
  data: {
    token: string;
    token_type: string;
    user: AdminUser;
    tenant: Tenant | null;
  };
}
