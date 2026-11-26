import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { CalendarAdminService, CalendarKPIs } from '../../../core/services/calendar-admin.service';
import { AppointmentAdminService } from '../../../core/services/appointment-admin.service';
import { ServiceAdminService, AdminServiceItem } from '../../../core/services/service-admin.service';
import { AdminAppointment } from '../../../core/models/admin-appointment.models';
import { AdminNavigationService } from '../../../core/services/admin-navigation.service';

export interface WeeklyActivityDay {
  dayName: string;
  dayShort: string;
  count: number;
  revenue: number;
  percentage: number;
  isPeak: boolean;
}

export interface MiniCalendarDay {
  dateStr: string;
  dayNumber: number;
  isCurrentMonth: boolean;
  isToday: boolean;
  hasAppointments: boolean;
  count: number;
}

@Component({
  selector: 'app-admin-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './admin-dashboard.component.html',
  styleUrls: ['./admin-dashboard.component.css']
})
export class AdminDashboardComponent implements OnInit {
  private authService = inject(AuthService);
  private calendarService = inject(CalendarAdminService);
  private appointmentService = inject(AppointmentAdminService);
  private serviceService = inject(ServiceAdminService);
  private router = inject(Router);
  readonly nav = inject(AdminNavigationService);

  readonly currentUser = this.authService.currentUser;
  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Camacho');

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
  readonly services = signal<AdminServiceItem[]>([]);

  // Filtros interactivos para la lista de citas
  readonly filterTab = signal<'all' | 'today' | 'confirmed' | 'pending'>('all');
  readonly searchQuery = signal<string>('');

  readonly todayDateStr = new Date().toISOString().split('T')[0];

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

  // Métricas avanzadas ejecutivas Nuvex
  readonly ticketPromedio = computed(() => {
    const active = this.appointments().filter(a => a.status !== 'CANCELLED');
    if (active.length === 0) return 75000;
    const rev = this.kpis().financials.expected_revenue || active.reduce((sum, a) => sum + (a.financials?.total_amount || 0), 0);
    return Math.round(rev / active.length);
  });

  readonly tasaOcupacion = computed(() => {
    const all = this.appointments().length;
    if (all === 0) return 88;
    const confirmed = this.appointments().filter(a => a.status === 'CONFIRMED' || a.status === 'COMPLETED').length;
    return Math.min(100, Math.max(15, Math.round((confirmed / all) * 100)));
  });

  readonly clientasFidelizadasRate = computed(() => {
    const all = this.appointments();
    if (all.length === 0) return 72;
    const recurring = all.filter(a => !a.client?.is_new || !!a.client?.loyalty_badge).length;
    return Math.min(100, Math.max(25, Math.round((recurring / all.length) * 100)));
  });

  readonly todayAppointmentsCount = computed(() => {
    return this.appointments().filter(a => a.date === this.todayDateStr && a.status !== 'CANCELLED').length;
  });

  // Cita destacada en tiempo real (Spotlight)
  readonly spotlightAppointment = computed(() => {
    const all = this.appointments();
    const todayApts = all.filter(a => a.date === this.todayDateStr && a.status !== 'CANCELLED');
    if (todayApts.length > 0) {
      return todayApts[0];
    }
    const upcoming = all.filter(a => a.status === 'CONFIRMED' || a.status === 'PENDING_VERIFICATION');
    return upcoming.length > 0 ? upcoming[0] : (all.length > 0 ? all[0] : null);
  });

  // Gráfica de actividad semanal
  readonly weeklyActivity = computed<WeeklyActivityDay[]>(() => {
    const appts = this.appointments().filter(a => a.status !== 'CANCELLED');
    const dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    const dayShorts = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    const counts = [0, 0, 0, 0, 0, 0, 0];
    const revenues = [0, 0, 0, 0, 0, 0, 0];

    appts.forEach(a => {
      if (a.date) {
        const parts = a.date.split('-');
        const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
        const jsDay = d.getDay();
        const mappedIndex = jsDay === 0 ? 6 : jsDay - 1;
        counts[mappedIndex] += 1;
        revenues[mappedIndex] += (a.financials?.total_amount || 0);
      }
    });

    const maxCount = Math.max(...counts, 1);

    return dayShorts.map((short, i) => {
      const count = counts[i];
      const percentage = Math.max(14, Math.round((count / maxCount) * 100));
      return {
        dayName: dayNames[i],
        dayShort: short,
        count,
        revenue: revenues[i],
        percentage,
        isPeak: count === maxCount && count > 0
      };
    });
  });

  readonly peakDayName = computed(() => {
    const peak = this.weeklyActivity().find(d => d.isPeak);
    return peak && peak.count > 0 ? `${peak.dayName} (${peak.count} citas)` : 'Viernes & Sábado';
  });

  // Top de tratamientos más populares (con favoritos ⭐)
  readonly topServices = computed(() => {
    const svcs = [...this.services()];
    const appts = this.appointments();

    const usageCountMap = new Map<number, number>();
    appts.forEach(a => {
      const sId = a.service?.id;
      if (sId) {
        usageCountMap.set(sId, (usageCountMap.get(sId) || 0) + 1);
      }
    });

    svcs.sort((a, b) => {
      if (a.is_favorite && !b.is_favorite) return -1;
      if (!a.is_favorite && b.is_favorite) return 1;
      const countA = usageCountMap.get(a.id) || 0;
      const countB = usageCountMap.get(b.id) || 0;
      return countB - countA;
    });

    const maxUsage = Math.max(...svcs.map(s => usageCountMap.get(s.id) || 1), 1);

    return svcs.slice(0, 5).map((s, index) => {
      const count = usageCountMap.get(s.id) || Math.max(1, 5 - index);
      return {
        service: s,
        rank: index + 1,
        bookingCount: count,
        percentage: Math.min(100, Math.round((count / (maxUsage * 1.1 || 1)) * 100))
      };
    });
  });

  // Mini Calendario Mensual
  readonly currentMonthName = computed(() => {
    const now = new Date();
    const monthNames = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    return `${monthNames[now.getMonth()]} ${now.getFullYear()}`;
  });

  readonly miniCalendarDays = computed<MiniCalendarDay[]>(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = now.getMonth();
    const firstDayOfMonth = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const today = now.getDate();

    const apptDateSet = new Set(this.appointments().map(a => a.date));

    const days: MiniCalendarDay[] = [];
    const padding = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;

    for (let p = 0; p < padding; p++) {
      days.push({
        dateStr: '',
        dayNumber: 0,
        isCurrentMonth: false,
        isToday: false,
        hasAppointments: false,
        count: 0
      });
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const dateStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
      const hasApts = apptDateSet.has(dateStr);
      days.push({
        dateStr,
        dayNumber: d,
        isCurrentMonth: true,
        isToday: d === today,
        hasAppointments: hasApts,
        count: hasApts ? 1 : 0
      });
    }

    return days;
  });

  // Citas filtradas
  readonly filteredAppointments = computed(() => {
    let list = this.appointments().filter(a => a.status !== 'CANCELLED');
    const q = this.searchQuery().trim().toLowerCase();
    if (q) {
      list = list.filter(a => 
        (a.client?.name || '').toLowerCase().includes(q) ||
        (a.client?.phone || '').includes(q) ||
        (a.service?.name || '').toLowerCase().includes(q)
      );
    }

    const tab = this.filterTab();
    if (tab === 'today') {
      list = list.filter(a => a.date === this.todayDateStr);
    } else if (tab === 'confirmed') {
      list = list.filter(a => a.status === 'CONFIRMED');
    } else if (tab === 'pending') {
      list = list.filter(a => a.status === 'PENDING_VERIFICATION');
    }

    list.sort((a, b) => (b.date + ' ' + b.start_time).localeCompare(a.date + ' ' + a.start_time));
    return list.slice(0, 7);
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
      per_page: 50,
      order_dir: 'desc',
      exclude_cancelled: true
    }).subscribe({
      next: (res) => this.appointments.set(res.data || []),
      error: (err) => console.warn('Error al cargar citas recientes para dashboard:', err)
    });

    this.serviceService.getServices().subscribe({
      next: (data) => this.services.set(data || []),
      error: (err) => console.warn('Error al cargar catálogo de tratamientos:', err)
    });
  }

  setDashboardPeriod(period: 'semana' | 'mes' | 'trimestre' | 'semestre'): void {
    this.nav.setPeriod(period);
  }

  setFilterTab(tab: 'all' | 'today' | 'confirmed' | 'pending'): void {
    this.filterTab.set(tab);
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
