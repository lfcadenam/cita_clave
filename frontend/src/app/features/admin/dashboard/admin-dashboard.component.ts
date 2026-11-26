import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { CalendarAdminService, CalendarKPIs } from '../../../core/services/calendar-admin.service';
import { AppointmentAdminService } from '../../../core/services/appointment-admin.service';
import { AdminAppointment } from '../../../core/models/admin-appointment.models';
import { AdminNavigationService } from '../../../core/services/admin-navigation.service';

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrls: ['./admin-dashboard.component.css']
})
export class AdminDashboardComponent implements OnInit {
  private authService = inject(AuthService);
  private calendarService = inject(CalendarAdminService);
  private appointmentService = inject(AppointmentAdminService);
  private router = inject(Router);
  readonly nav = inject(AdminNavigationService);

  readonly currentUser = this.authService.currentUser;
  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  readonly selectedPeriod = this.nav.selectedPeriod;

  readonly kpis = signal<CalendarKPIs>({
    total_appointments: 0,
    confirmed_count: 0,
    pending_verification_count: 0,
    in_progress_count: 0,
    completed_count: 0,
    cancelled_count: 0,
    financials: {
      expected_revenue: 0,
      collected_deposits: 0,
      pending_balance: 0
    }
  });

  readonly appointments = signal<AdminAppointment[]>([]);

  readonly statusCounts = computed(() => {
    const all = this.appointments();
    const active = all.filter(a => a.status !== 'CANCELLED');
    return {
      all: active.length,
      confirmed: all.filter(a => a.status === 'CONFIRMED').length,
      pendingVerification: all.filter(a => a.status === 'PENDING_VERIFICATION').length,
      completed: all.filter(a => a.status === 'COMPLETED').length,
      cancelled: all.filter(a => a.status === 'CANCELLED').length
    };
  });

  readonly recentOrUpcomingAppointments = computed(() => {
    const list = this.appointments().filter(a => a.status !== 'CANCELLED');
    list.sort((a, b) => (b.date + ' ' + b.start_time).localeCompare(a.date + ' ' + a.start_time));
    return list.slice(0, 6);
  });

  ngOnInit(): void {
    this.loadDashboardData();
  }

  loadDashboardData(): void {
    const today = new Date().toISOString().split('T')[0];
    this.calendarService.getSummary(today).subscribe({
      next: (summary) => this.kpis.set(summary.kpis),
      error: (err) => console.warn('Error al cargar KPIs del dashboard:', err)
    });

    this.appointmentService.getAppointments({
      per_page: 20,
      order_dir: 'desc',
      exclude_cancelled: true
    }).subscribe({
      next: (res) => this.appointments.set(res.data || []),
      error: (err) => console.warn('Error al cargar citas recientes para dashboard:', err)
    });
  }

  setDashboardPeriod(period: 'semana' | 'mes' | 'trimestre' | 'semestre'): void {
    this.nav.setPeriod(period);
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount);
  }

  getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'CONFIRMED': return 'bg-emerald-50 text-emerald-800 border border-emerald-200';
      case 'PENDING_VERIFICATION': return 'bg-amber-50 text-amber-800 border border-amber-200';
      case 'COMPLETED': return 'bg-purple-50 text-purple-800 border border-purple-200';
      case 'CANCELLED': return 'bg-rose-50 text-rose-800 border border-rose-200';
      default: return 'bg-slate-50 text-slate-700 border border-slate-200';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'CONFIRMED': return 'Confirmada';
      case 'PENDING_VERIFICATION': return 'Por Validar Nequi';
      case 'COMPLETED': return 'Completada';
      case 'CANCELLED': return 'Cancelada';
      default: return status;
    }
  }

  openWhatsAppChat(phone: string, clientName: string, date?: string, time?: string, service?: string): void {
    const cleanPhone = phone.replace(/\D/g, '');
    const fullPhone = cleanPhone.startsWith('57') ? cleanPhone : `57${cleanPhone}`;
    let msg = `¡Hola ${clientName}! Te saludamos desde ${this.salonTitle()}.`;
    if (date && time && service) {
      msg += ` Te escribimos respecto a tu cita de ${service} programada para el día ${date} a las ${time}.`;
    }
    const url = `https://wa.me/${fullPhone}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
  }

  openNewAppointmentModal(): void {
    this.nav.triggerNewAppointment();
  }

  onAppointmentCardClicked(apt: AdminAppointment, event: Event): void {
    this.router.navigate(['/admin/citas']);
  }
}
