import { Routes } from '@angular/router';
import { BookingComponent } from './features/booking/booking.component';
import { AdminLoginComponent } from './features/admin/login/admin-login.component';
import { AdminLayoutComponent } from './features/admin/layout/admin-layout.component';
import { AppointmentsCalendarComponent } from './features/admin/appointments-calendar/appointments-calendar.component';
import { AdminDashboardComponent } from './features/admin/dashboard/admin-dashboard.component';
import { AdminAppointmentsComponent } from './features/admin/appointments/admin-appointments.component';
import { AdminCustomersComponent } from './features/admin/customers/admin-customers.component';
import { AdminWaitlistComponent } from './features/admin/waitlist/admin-waitlist.component';
import { AdminServicesComponent } from './features/admin/services/admin-services.component';
import { AdminSchedulesComponent } from './features/admin/schedules/admin-schedules.component';
import { AdminWhatsAppSettingsComponent } from './features/admin/whatsapp-settings/admin-whatsapp-settings.component';
import { AdminWhatsAppTemplatesComponent } from './features/admin/whatsapp-templates/admin-whatsapp-templates.component';
import { AdminWhatsAppBroadcastComponent } from './features/admin/whatsapp-broadcast/admin-whatsapp-broadcast.component';
import { AdminSuperAdminComponent } from './features/admin/superadmin/admin-superadmin.component';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  // Flujo Público de Reservas para Clientes
  {
    path: '',
    component: BookingComponent
  },
  
  // Acceso Administrativo (Login sin Layout)
  {
    path: 'admin/login',
    component: AdminLoginComponent
  },

  // Shell Administrativo Nuvex Modern Curve UI con Submódulos Desacoplados
  {
    path: 'admin',
    component: AdminLayoutComponent,
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'calendario', pathMatch: 'full' },
      { path: 'escritorio', component: AdminDashboardComponent },
      { path: 'calendario', component: AppointmentsCalendarComponent },
      { path: 'citas', component: AdminAppointmentsComponent },
      { path: 'clientes', component: AdminCustomersComponent },
      { path: 'espera', component: AdminWaitlistComponent },
      { path: 'servicios', component: AdminServicesComponent },
      { path: 'horarios', component: AdminSchedulesComponent },
      { path: 'bloqueos', component: AdminSchedulesComponent },
      { path: 'whatsapp', component: AdminWhatsAppSettingsComponent },
      { path: 'plantillas', component: AdminWhatsAppTemplatesComponent },
      { path: 'difusion', component: AdminWhatsAppBroadcastComponent },
      { path: 'superadmin', component: AdminSuperAdminComponent }
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
