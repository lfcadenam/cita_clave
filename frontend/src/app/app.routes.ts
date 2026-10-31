import { Routes } from '@angular/router';
import { BookingComponent } from './features/booking/booking.component';
import { AppointmentsCalendarComponent } from './features/admin/appointments-calendar/appointments-calendar.component';

export const routes: Routes = [
  {
    path: '',
    component: BookingComponent
  },
  {
    path: 'admin',
    redirectTo: 'admin/citas',
    pathMatch: 'full'
  },
  {
    path: 'admin/citas',
    component: AppointmentsCalendarComponent
  },
  {
    path: 'admin/calendario',
    component: AppointmentsCalendarComponent
  },
  {
    path: '**',
    redirectTo: ''
  }
];
