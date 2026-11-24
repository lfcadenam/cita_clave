import { Routes } from '@angular/router';
import { BookingComponent } from './features/booking/booking.component';
import { AppointmentsCalendarComponent } from './features/admin/appointments-calendar/appointments-calendar.component';
import { AdminLoginComponent } from './features/admin/login/admin-login.component';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: '',
    component: BookingComponent
  },
  {
    path: 'admin/login',
    component: AdminLoginComponent
  },
  {
    path: 'admin',
    redirectTo: 'admin/calendario',
    pathMatch: 'full'
  },
  {
    path: 'admin/citas',
    redirectTo: 'admin/calendario',
    pathMatch: 'full'
  },
  {
    path: 'admin/calendario',
    component: AppointmentsCalendarComponent,
    canActivate: [authGuard]
  },
  {
    path: '**',
    redirectTo: ''
  }
];
