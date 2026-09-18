import { Routes } from '@angular/router';
import { BookingComponent } from './features/booking/booking.component';

export const routes: Routes = [
  {
    path: '',
    component: BookingComponent
  },
  {
    path: '**',
    redirectTo: ''
  }
];
