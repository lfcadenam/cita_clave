import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // Flujo Público de Reservas para Clientes
  {
    path: '',
    loadComponent: () => import('./features/booking/booking.component').then(m => m.BookingComponent)
  },
  
  // Acceso Administrativo (Login sin Layout)
  {
    path: 'admin/login',
    loadComponent: () => import('./features/admin/login/admin-login.component').then(m => m.AdminLoginComponent)
  },

  // Shell Administrativo Nuvex Modern Curve UI con Submódulos Desacoplados (Lazy Loaded)
  {
    path: 'admin',
    loadComponent: () => import('./features/admin/layout/admin-layout.component').then(m => m.AdminLayoutComponent),
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'calendario', pathMatch: 'full' },
      { 
        path: 'escritorio', 
        loadComponent: () => import('./features/admin/dashboard/admin-dashboard.component').then(m => m.AdminDashboardComponent) 
      },
      { 
        path: 'calendario', 
        loadComponent: () => import('./features/admin/appointments-calendar/appointments-calendar.component').then(m => m.AppointmentsCalendarComponent) 
      },
      { 
        path: 'citas', 
        loadComponent: () => import('./features/admin/appointments/admin-appointments.component').then(m => m.AdminAppointmentsComponent) 
      },
      { 
        path: 'clientes', 
        loadComponent: () => import('./features/admin/customers/admin-customers.component').then(m => m.AdminCustomersComponent) 
      },
      { 
        path: 'espera', 
        loadComponent: () => import('./features/admin/waitlist/admin-waitlist.component').then(m => m.AdminWaitlistComponent) 
      },
      { 
        path: 'servicios', 
        loadComponent: () => import('./features/admin/services/admin-services.component').then(m => m.AdminServicesComponent) 
      },
      { 
        path: 'horarios', 
        loadComponent: () => import('./features/admin/schedules/admin-schedules.component').then(m => m.AdminSchedulesComponent) 
      },
      { 
        path: 'bloqueos', 
        loadComponent: () => import('./features/admin/schedules/admin-schedules.component').then(m => m.AdminSchedulesComponent) 
      },
      { 
        path: 'whatsapp', 
        loadComponent: () => import('./features/admin/whatsapp-settings/admin-whatsapp-settings.component').then(m => m.AdminWhatsAppSettingsComponent) 
      },
      { 
        path: 'plantillas', 
        loadComponent: () => import('./features/admin/whatsapp-templates/admin-whatsapp-templates.component').then(m => m.AdminWhatsAppTemplatesComponent) 
      },
      { 
        path: 'difusion', 
        loadComponent: () => import('./features/admin/whatsapp-broadcast/admin-whatsapp-broadcast.component').then(m => m.AdminWhatsAppBroadcastComponent) 
      },
      { 
        path: 'superadmin', 
        loadComponent: () => import('./features/admin/superadmin/admin-superadmin.component').then(m => m.AdminSuperAdminComponent) 
      }
    ]
  },

  // Redirecciones heredadas y por compatibilidad multi-tenant
  {
    path: 'admin/labellenails',
    redirectTo: 'admin/calendario',
    pathMatch: 'full'
  },
  {
    path: 'admin/labellenails/:sub',
    redirectTo: 'admin/calendario',
    pathMatch: 'full'
  },
  {
    path: 'admin/:tenant',
    redirectTo: 'admin/calendario',
    pathMatch: 'full'
  },

  // Fallback 404
  {
    path: '**',
    redirectTo: ''
  }
];
