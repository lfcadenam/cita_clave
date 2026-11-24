import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { AppointmentAdminService, CreateAppointmentPayload } from '../../../core/services/appointment-admin.service';
import { CalendarAdminService } from '../../../core/services/calendar-admin.service';
import { CustomerAdminService } from '../../../core/services/customer-admin.service';
import { ServiceAdminService, AdminServiceItem } from '../../../core/services/service-admin.service';
import { WorkingScheduleService } from '../../../core/services/working-schedule.service';
import { WaitlistAdminService } from '../../../core/services/waitlist-admin.service';
import { WhatsAppAdminService } from '../../../core/services/whatsapp-admin.service';
import { SuperAdminService } from '../../../core/services/super-admin.service';

import { 
  AdminAppointment, 
  AppointmentStatus, 
  CalendarKPIs, 
  SpotlightAppointment, 
  AdminSlot, 
  AdminSlotsResponse 
} from '../../../core/models/admin-appointment.models';
import { AdminCustomer } from '../../../core/models/admin-customer.models';
import { WorkingSchedule, BlockedSlot } from '../../../core/models/admin-schedule.models';
import { WaitlistItem } from '../../../core/models/admin-waitlist.models';
import { 
  WhatsAppSettings, 
  WhatsAppAccountStatus, 
  WhatsAppTemplate, 
  WhatsAppTestResponse 
} from '../../../core/models/admin-whatsapp.models';
import { PlatformAnalytics, SuperTenant, SuperUser } from '../../../core/models/super-admin.models';

export interface CalendarWeekDay {
  date: Date;
  dateStr: string;
  dayName: string;
  dayNumber: number;
  isToday: boolean;
  isSelected: boolean;
}

export interface CalendarMonthCell {
  date: Date;
  dateStr: string;
  dayNumber: number;
  isCurrentMonth: boolean;
  isToday: boolean;
  isSelected: boolean;
  appointments: AdminAppointment[];
}

export interface CalendarHour {
  hour24: string;
  hour12: string;
  hourNum: number;
}

export interface PositionedAppointment {
  appointment: AdminAppointment;
  topPx: number;
  heightPx: number;
  leftPercent: number;
  widthPercent: number;
  isCompact: boolean;
  isMicro: boolean;
  statusClass: string;
}

export type AdminSection = 
  | 'escritorio'
  | 'calendario' 
  | 'citas' 
  | 'servicios' 
  | 'clientes' 
  | 'horarios' 
  | 'espera' 
  | 'whatsapp' 
  | 'superadmin';

@Component({
  selector: 'app-appointments-calendar',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './appointments-calendar.component.html',
  styleUrls: ['./appointments-calendar.component.css']
})
export class AppointmentsCalendarComponent implements OnInit {
  authService = inject(AuthService);
  private appointmentService = inject(AppointmentAdminService);
  private calendarService = inject(CalendarAdminService);
  private customerService = inject(CustomerAdminService);
  private serviceAdminService = inject(ServiceAdminService);
  private scheduleService = inject(WorkingScheduleService);
  private waitlistService = inject(WaitlistAdminService);
  private whatsappService = inject(WhatsAppAdminService);
  private superAdminService = inject(SuperAdminService);
  private router = inject(Router);

  // Perfil del Salón y Usuario Activo
  currentUser = this.authService.currentUser;
  currentTenant = this.authService.currentTenant;
  isSuperAdmin = this.authService.isSuperAdmin;

  systemBrand = signal<string>('Nuvex Agendamiento');
  salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  // Módulo / Sección Activa en el Hub Administrativo
  activeSection = signal<AdminSection>('escritorio');

  // Menú Lateral Móvil (Drawer)
  sidebarOpen = signal<boolean>(false);

  // Estado del Menú de Usuario y Topbar CitaClave
  userMenuOpen = signal<boolean>(false);
  showProfileModal = signal<boolean>(false);
  selectedPeriod = signal<'semana' | 'mes' | 'trimestre' | 'semestre'>('mes');

  userInitials = computed(() => {
    const name = this.currentUser()?.name?.trim() || 'Paola Camacho';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  });

  sectionHeaderTitle = computed(() => {
    switch (this.activeSection()) {
      case 'escritorio': return 'DASHBOARD';
      case 'calendario': return 'CALENDARIO DE CITAS';
      case 'citas': return 'CITAS & RESERVAS';
      case 'servicios': return 'TRATAMIENTOS & SERVICIOS';
      case 'clientes': return 'CLIENTAS (CRM)';
      case 'horarios': return 'HORARIOS & PAUSAS';
      case 'espera': return 'LISTA DE ESPERA';
      case 'whatsapp': return 'WHATSAPP META API';
      case 'superadmin': return 'SUPERADMIN NUVEX';
      default: return 'DASHBOARD';
    }
  });

  toggleSidebar(): void {
    this.sidebarOpen.update(v => !v);
  }

  closeSidebar(): void {
    this.sidebarOpen.set(false);
  }

  toggleUserMenu(): void {
    this.userMenuOpen.update(v => !v);
  }

  closeUserMenu(): void {
    this.userMenuOpen.set(false);
  }

  openProfileModal(): void {
    this.closeUserMenu();
    this.showProfileModal.set(true);
  }

  closeProfileModal(): void {
    this.showProfileModal.set(false);
  }

  setDashboardPeriod(period: 'semana' | 'mes' | 'trimestre' | 'semestre'): void {
    this.selectedPeriod.set(period);
    if (this.activeSection() === 'calendario') {
      if (period === 'semana') {
        this.setView('week');
      } else if (period === 'mes') {
        this.setView('month');
      }
    }
  }

  // Vistas del Calendario: 'mes' | 'semana' | 'dia'
  viewMode = signal<'month' | 'week' | 'day'>('week');

  // Tema Dual
  isDarkMode = signal<boolean>(false);

  // Fechas de Referencia
  todayStr = this.formatDate(new Date());
  currentDate = signal<Date>(new Date());
  selectedDateStr = signal<string>(this.todayStr);

  // Estado de Datos de Agenda
  isLoading = signal<boolean>(false);
  appointments = signal<AdminAppointment[]>([]);
  servicesList = signal<AdminServiceItem[]>([]);

  // Filtros de la Paleta Lateral (Calendario)
  filterStatus = signal<string>('all');
  selectedServiceFilter = signal<number | null>(null);
  searchQuery = signal<string>('');

  // Resumen / KPIs
  kpis = signal<CalendarKPIs>({
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

  // Horas de la Grilla (08:00 a 18:00)
  hours: CalendarHour[] = [
    { hour24: '08:00', hour12: '08:00 AM', hourNum: 8 },
    { hour24: '09:00', hour12: '09:00 AM', hourNum: 9 },
    { hour24: '10:00', hour12: '10:00 AM', hourNum: 10 },
    { hour24: '11:00', hour12: '11:00 AM', hourNum: 11 },
    { hour24: '12:00', hour12: '12:00 PM', hourNum: 12 },
    { hour24: '13:00', hour12: '01:00 PM', hourNum: 13 },
    { hour24: '14:00', hour12: '02:00 PM', hourNum: 14 },
    { hour24: '15:00', hour12: '03:00 PM', hourNum: 15 },
    { hour24: '16:00', hour12: '04:00 PM', hourNum: 16 },
    { hour24: '17:00', hour12: '05:00 PM', hourNum: 17 },
    { hour24: '18:00', hour12: '06:00 PM', hourNum: 18 }
  ];

  readonly baseHour = 8;
  readonly slotHeightPx = 64;

  // Detalle y Edición de Cita Seleccionada
  selectedAppointment = signal<AdminAppointment | null>(null);
  isEditingAppointment = signal<boolean>(false);
  isSavingAppointmentEdit = signal<boolean>(false);
  editAppointmentError = signal<string | null>(null);

  editServiceId = signal<number | null>(null);
  editAppointmentDate = signal<string>('');
  editStartTime = signal<string>('');
  editEndTime = signal<string>('');
  editClientName = signal<string>('');
  editClientPhone = signal<string>('');
  editTotalAmount = signal<number>(0);
  editDepositPaid = signal<number>(0);
  editStatus = signal<AppointmentStatus>('CONFIRMED');
  editAdminNotes = signal<string>('');
  protected readonly Math = Math;

  // =========================================================================
  // MODAL: AGENDAR NUEVA CITA / RESERVA (Diseño en 2 Columnas)
  // =========================================================================
  showNewAppointmentModal = signal<boolean>(false);
  isSavingAppointment = signal<boolean>(false);
  conflictError = signal<string | null>(null);

  // Columna Izquierda: Datos de la Clienta
  clientSearchQuery = signal<string>('');
  customerSearchResults = signal<AdminCustomer[]>([]);
  isSearchingCustomer = signal<boolean>(false);
  selectedCustomer = signal<AdminCustomer | null>(null);

  clientName = signal<string>('');
  clientPhone = signal<string>('');
  clientEmail = signal<string>('');

  // Columna Izquierda: Programación del Servicio (Soporte Multi-Servicio)
  selectedServices = signal<AdminServiceItem[]>([]);
  newApptServiceId = computed(() => this.selectedServices()[0]?.id || null);
  serviceSearchQuery = signal<string>('');
  serviceDropdownOpen = signal<boolean>(false);

  totalDurationMinutes = computed(() => {
    return this.selectedServices().reduce((sum, s) => sum + (s.duration_minutes || 0), 0);
  });

  formattedTotalDuration = computed(() => {
    const total = this.totalDurationMinutes();
    const hours = Math.floor(total / 60);
    const minutes = total % 60;
    if (hours > 0 && minutes > 0) return `${hours}h ${minutes}min`;
    if (hours > 0) return `${hours} hora${hours > 1 ? 's' : ''}`;
    return `${minutes} min`;
  });

  selectedServiceItem = computed(() => {
    return this.selectedServices()[0] || null;
  });

  filteredServicesForModal = computed(() => {
    const q = this.serviceSearchQuery().toLowerCase().trim();
    const list = this.servicesList();
    if (!q) return list;
    return list.filter(s =>
      s.name.toLowerCase().includes(q) ||
      (s.category && s.category.toLowerCase().includes(q)) ||
      s.duration_minutes.toString().includes(q) ||
      s.base_price.toString().includes(q)
    );
  });

  toggleServiceDropdown(): void {
    this.serviceDropdownOpen.update(v => !v);
  }

  closeServiceDropdown(): void {
    this.serviceDropdownOpen.set(false);
  }

  toggleServiceInModal(svc: AdminServiceItem): void {
    if (this.isServiceSelected(svc.id)) {
      this.removeServiceFromModal(svc.id);
    } else {
      this.addServiceToModal(svc);
    }
  }

  addServiceToModal(svc: AdminServiceItem): void {
    if (!this.selectedServices().some(s => s.id === svc.id)) {
      this.selectedServices.update(list => [...list, svc]);
      this.onServicesChange();
    }
  }

  removeServiceFromModal(svcId: number): void {
    this.selectedServices.update(list => list.filter(s => s.id !== svcId));
    this.onServicesChange();
  }

  isServiceSelected(svcId: number): boolean {
    return this.selectedServices().some(s => s.id === svcId);
  }

  selectServiceForModal(svc: AdminServiceItem): void {
    this.toggleServiceInModal(svc);
  }

  newApptDate = signal<string>(this.todayStr);
  suggestedSlotValue = signal<string>('');
  availableSlots = signal<AdminSlotsResponse | null>(null);
  isLoadingSlots = signal<boolean>(false);
  startTime = signal<string>('09:00');
  endTime = signal<string>('09:50');

  // Columna Derecha: Estado y Liquidación de Pagos
  newApptStatus = signal<AppointmentStatus>('CONFIRMED');
  newApptPaymentMethod = signal<string>('CASH_AT_LOCATION');
  totalAmount = signal<number>(0);
  depositRequired = signal<number>(0);
  depositPaid = signal<number>(0);
  verificationNotes = signal<string>('');

  balanceDue = computed(() => {
    return Math.max(0, this.totalAmount() - this.depositPaid());
  });

  // Control de flujo por fases (Stepper tipo Wizard):
  // 1: Datos de la Clienta, 2: Servicio, 3: Horario, 4: Abono
  modalStep = signal<number>(1);
  isCustomerNew = signal<boolean>(true); // true: Nueva ($50.000 abono fijo), false: Recurrente ($0 abono)

  setIsCustomerNew(isNew: boolean): void {
    this.isCustomerNew.set(isNew);
    this.updateDepositRule();
  }

  updateDepositRule(): void {
    if (this.isCustomerNew()) {
      // Cliente nuevo: siempre 50 mil independientemente del servicio o cantidad de servicios
      this.depositRequired.set(50000);
      this.depositPaid.set(50000);
    } else {
      // Cliente recurrente / antiguo: sin abono ($0)
      this.depositRequired.set(0);
      this.depositPaid.set(0);
    }
  }

  setModalStep(step: number): void {
    if (step === this.modalStep()) return;
    if (step > this.modalStep()) {
      if (!this.canProceedFromStep(this.modalStep())) return;
    }
    this.conflictError.set(null);
    this.modalStep.set(step);
    if (step === 3) {
      this.fetchSlotsForModal();
    }
  }

  canProceedFromStep(step: number): boolean {
    this.conflictError.set(null);
    if (step === 1) {
      // Paso 1: Datos de la Clienta
      if (!this.clientName().trim()) {
        this.conflictError.set('Por favor ingresa el nombre de la clienta.');
        return false;
      }
      if (!this.clientPhone().trim()) {
        this.conflictError.set('Por favor ingresa el celular WhatsApp de la clienta.');
        return false;
      }
    } else if (step === 2) {
      // Paso 2: Servicio (Tratamientos)
      if (this.selectedServices().length === 0) {
        this.conflictError.set('Por favor selecciona al menos un servicio o tratamiento para continuar.');
        return false;
      }
    } else if (step === 3) {
      // Paso 3: Horario
      if (!this.newApptDate() || !this.startTime() || !this.endTime()) {
        this.conflictError.set('Por favor define la fecha y franja horaria de la cita.');
        return false;
      }
    }
    return true;
  }

  nextModalStep(): void {
    if (this.canProceedFromStep(this.modalStep())) {
      this.conflictError.set(null);
      const next = Math.min(4, this.modalStep() + 1);
      this.modalStep.set(next);
      if (next === 3) {
        this.fetchSlotsForModal();
      }
    }
  }

  prevModalStep(): void {
    this.conflictError.set(null);
    this.modalStep.update(s => Math.max(1, s - 1));
  }

  // =========================================================================
  // ESTADO Y GESTIÓN PARA SERVICIOS (TRATAMIENTOS CRUD)
  // =========================================================================
  servicesTable = signal<AdminServiceItem[]>([]);
  servicesPage = signal<number>(1);
  servicesTotalPages = signal<number>(1);
  servicesTotalItems = signal<number>(0);
  servicesPerPage = 10;
  servicesSearch = signal<string>('');
  isLoadingServices = signal<boolean>(false);
  showServiceModal = signal<boolean>(false);
  editingService = signal<AdminServiceItem | null>(null);
  serviceForm = {
    name: '',
    category: 'Manicura y Pedicura',
    description: '',
    duration_minutes: 60,
    base_price: 35000,
    deposit_amount: 10000,
    requires_deposit: true,
    is_active: true,
    image_url: ''
  };
  serviceImageFile: File | null = null;
  serviceImagePreview = signal<string | null>(null);
  isSavingService = signal<boolean>(false);

  // =========================================================================
  // ESTADO Y GESTIÓN PARA CRM (CLIENTAS)
  // =========================================================================
  crmCustomers = signal<AdminCustomer[]>([]);
  crmPage = signal<number>(1);
  crmTotalPages = signal<number>(1);
  crmTotalItems = signal<number>(0);
  crmPerPage = 10;
  isLoadingCustomers = signal<boolean>(false);
  crmSearch = signal<string>('');
  showCustomerModal = signal<boolean>(false);
  editingCustomer = signal<AdminCustomer | null>(null);
  customerForm = {
    name: '',
    phone: '',
    email: '',
    notes: ''
  };

  // =========================================================================
  // ESTADO Y GESTIÓN PARA HORARIOS & BLOQUEOS
  // =========================================================================
  schedulesTab = signal<'weekly' | 'blocks'>('weekly');
  weeklySchedules = signal<WorkingSchedule[]>([]);
  blockedSlots = signal<BlockedSlot[]>([]);
  isSavingSchedules = signal<boolean>(false);
  showBlockModal = signal<boolean>(false);
  blockForm = {
    title: '',
    blocked_date: '',
    start_time: '13:00',
    end_time: '14:00',
    is_recurring: true,
    reason: 'Pausa para almuerzo'
  };

  // =========================================================================
  // ESTADO Y GESTIÓN PARA LISTA DE ESPERA
  // =========================================================================
  waitlist = signal<WaitlistItem[]>([]);
  isLoadingWaitlist = signal<boolean>(false);

  // =========================================================================
  // ESTADO Y GESTIÓN PARA WHATSAPP META CLOUD API
  // =========================================================================
  whatsAppSettings = signal<WhatsAppSettings | null>(null);
  whatsAppAccountStatus = signal<WhatsAppAccountStatus | null>(null);
  isCheckingWhatsAppStatus = signal<boolean>(false);
  isSavingWhatsApp = signal<boolean>(false);
  isRegisteringPin = signal<boolean>(false);
  metaPinCode = signal<string>('123456');
  isSyncingTemplates = signal<boolean>(false);
  wabaTemplates = signal<WhatsAppTemplate[]>([]);
  hasSyncedTemplates = signal<boolean>(false);
  whatsAppTestPhone = signal<string>('');
  whatsAppTestMode = signal<'template' | 'text'>('template');
  whatsAppTestTemplateName = signal<string>('reserva_confirmada');
  whatsAppTestTemplateLang = signal<string>('es_CO');
  whatsAppTestCustomMessage = signal<string>('');
  whatsAppTestResult = signal<WhatsAppTestResponse | null>(null);
  isSendingWhatsAppTest = signal<boolean>(false);
  whatsAppForm = {
    whatsapp_driver: 'meta',
    whatsapp_number: '',
    meta_phone_number_id: '',
    meta_waba_id: '',
    meta_access_token: '',
    meta_verify_token: 'nuvex_meta_verify_2026'
  };

  // =========================================================================
  // ESTADO Y GESTIÓN PARA SUPERADMIN NUVEX
  // =========================================================================
  superAnalytics = signal<PlatformAnalytics | null>(null);
  superTenants = signal<SuperTenant[]>([]);
  superUsers = signal<SuperUser[]>([]);
  showTenantModal = signal<boolean>(false);
  tenantForm = {
    name: '',
    slug: '',
    domain: '',
    phone: '',
    email: ''
  };
  showUserModal = signal<boolean>(false);
  userForm = {
    name: '',
    email: '',
    password: '',
    role: 'ADMIN',
    tenant_id: null as number | null
  };

  // =========================================================================
  // COMPUTED PROPERTIES: TITULOS Y DIAS DE CALENDARIO
  // =========================================================================

  calendarTitle = computed<string>(() => {
    const d = this.currentDate();
    const months = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    if (this.viewMode() === 'month') {
      return `${months[d.getMonth()]} ${d.getFullYear()}`.toUpperCase();
    }

    if (this.viewMode() === 'week') {
      const monday = this.getMondayOfDate(d);
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);

      const mStart = months[monday.getMonth()].substring(0, 3);
      const mEnd = months[sunday.getMonth()].substring(0, 3);

      if (monday.getMonth() === sunday.getMonth()) {
        return `${monday.getDate()} – ${sunday.getDate()} ${mStart}, ${monday.getFullYear()}`;
      }
      return `${monday.getDate()} ${mStart} – ${sunday.getDate()} ${mEnd}, ${sunday.getFullYear()}`;
    }

    const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    return `${days[d.getDay()]}, ${d.getDate()} de ${months[d.getMonth()]} de ${d.getFullYear()}`;
  });

  weekDays = computed<CalendarWeekDay[]>(() => {
    const monday = this.getMondayOfDate(this.currentDate());
    const days: CalendarWeekDay[] = [];
    const names = ['LUN', 'MAR', 'MIÉ', 'JUE', 'VIE', 'SÁB', 'DOM'];

    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      const dStr = this.formatDate(d);

      days.push({
        date: d,
        dateStr: dStr,
        dayName: names[i],
        dayNumber: d.getDate(),
        isToday: dStr === this.todayStr,
        isSelected: dStr === this.selectedDateStr()
      });
    }

    return days;
  });

  monthCells = computed<CalendarMonthCell[]>(() => {
    const ref = this.currentDate();
    const year = ref.getFullYear();
    const month = ref.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const startMonday = this.getMondayOfDate(firstDayOfMonth);

    const cells: CalendarMonthCell[] = [];
    const allApts = this.filteredAppointments().filter(a => a.status !== 'CANCELLED');

    for (let i = 0; i < 42; i++) {
      const d = new Date(startMonday);
      d.setDate(startMonday.getDate() + i);
      const dStr = this.formatDate(d);

      const dayApts = allApts.filter(a => a.date === dStr);

      cells.push({
        date: d,
        dateStr: dStr,
        dayNumber: d.getDate(),
        isCurrentMonth: d.getMonth() === month,
        isToday: dStr === this.todayStr,
        isSelected: dStr === this.selectedDateStr(),
        appointments: dayApts
      });
    }

    return cells;
  });

  filteredAppointments = computed<AdminAppointment[]>(() => {
    let list = this.appointments();
    const status = this.filterStatus();
    const svcId = this.selectedServiceFilter();
    const q = this.searchQuery().toLowerCase().trim();

    if (status !== 'all') {
      list = list.filter(a => a.status === status);
    } else {
      // Las citas canceladas nunca deben aparecer en el calendario ni en la vista general
      list = list.filter(a => a.status !== 'CANCELLED');
    }

    if (svcId) {
      list = list.filter(a => a.service?.id === svcId);
    }

    if (q) {
      list = list.filter(a =>
        a.client.name.toLowerCase().includes(q) ||
        a.client.phone.includes(q) ||
        a.appointment_number.toLowerCase().includes(q) ||
        (a.service && a.service.name.toLowerCase().includes(q))
      );
    }

    return list;
  });

  statusCounts = computed(() => {
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

  recentOrUpcomingAppointments = computed(() => {
    const list = this.appointments().filter(a => a.status !== 'CANCELLED');
    list.sort((a, b) => (b.date + ' ' + b.start_time).localeCompare(a.date + ' ' + a.start_time));
    return list.slice(0, 6);
  });

  ngOnInit(): void {
    this.loadCatalogServices(1);
    this.loadCrmCustomers(1);
    this.loadAppointmentsForCurrentRange();
    this.loadDailySummary();
  }

  // =========================================================================
  // CAMBIO DE SECCIÓN Y CARGA BAJO DEMANDA
  // =========================================================================
  setSection(section: AdminSection): void {
    this.activeSection.set(section);
    this.closeSidebar();

    if (section === 'escritorio' || section === 'calendario') {
      this.loadAppointmentsForCurrentRange();
      this.loadDailySummary();
    } else if (section === 'citas') {
      this.loadAppointmentsForCurrentRange();
    } else if (section === 'servicios') {
      this.loadCatalogServices();
    } else if (section === 'clientes') {
      this.loadCrmCustomers();
    } else if (section === 'horarios') {
      this.loadWeeklySchedules();
      this.loadBlockedSlots();
    } else if (section === 'espera') {
      this.loadWaitlist();
    } else if (section === 'whatsapp') {
      this.loadWhatsAppSettings();
    } else if (section === 'superadmin' && this.isSuperAdmin()) {
      this.loadSuperAdminData();
    }
  }

  // =========================================================================
  // CARGA DE DATOS DESDE LA API REST (CALENDARIO)
  // =========================================================================

  loadAppointmentsForCurrentRange(): void {
    this.isLoading.set(true);
    let startDate: string;
    let endDate: string;

    if (this.viewMode() === 'month') {
      const ref = this.currentDate();
      const first = new Date(ref.getFullYear(), ref.getMonth(), 1);
      const startMonday = this.getMondayOfDate(first);
      const endSunday = new Date(startMonday);
      endSunday.setDate(startMonday.getDate() + 41);

      startDate = this.formatDate(startMonday);
      endDate = this.formatDate(endSunday);
    } else if (this.viewMode() === 'week') {
      const monday = this.getMondayOfDate(this.currentDate());
      const sunday = new Date(monday);
      sunday.setDate(monday.getDate() + 6);

      startDate = this.formatDate(monday);
      endDate = this.formatDate(sunday);
    } else {
      startDate = this.formatDate(this.currentDate());
      endDate = startDate;
    }

    this.appointmentService.getAppointments({
      start_date: startDate,
      end_date: endDate,
      exclude_cancelled: true,
      all: true
    }).subscribe({
      next: (res) => {
        this.appointments.set(res.data);
        this.isLoading.set(false);
      },
      error: (err) => {
        console.error('Error al cargar citas de la grilla:', err);
        this.isLoading.set(false);
      }
    });
  }

  loadDailySummary(): void {
    const targetDate = this.formatDate(this.currentDate());
    this.calendarService.getSummary(targetDate).subscribe({
      next: (summary) => {
        this.kpis.set(summary.kpis);
      },
      error: (err) => {
        console.warn('No se pudo cargar el resumen diario:', err);
      }
    });
  }

  loadCatalogServices(page: number = 1): void {
    this.isLoadingServices.set(true);
    this.serviceAdminService.getServicesPaginated(page, this.servicesPerPage, this.servicesSearch()).subscribe({
      next: (res) => {
        this.servicesTable.set(res.data);
        this.servicesPage.set(res.meta.current_page);
        this.servicesTotalPages.set(res.meta.last_page);
        this.servicesTotalItems.set(res.meta.total);
        this.isLoadingServices.set(false);
      },
      error: (err) => {
        this.isLoadingServices.set(false);
        console.error('Error al cargar servicios paginados:', err);
      }
    });

    // Mantener la lista completa para modales de agendamiento si aún no se ha cargado
    if (this.servicesList().length === 0) {
      this.serviceAdminService.getServices(true).subscribe({
        next: (svcs) => this.servicesList.set(svcs),
        error: (err) => console.warn('Error al precargar servicios completos:', err)
      });
    }
  }

  ensureServicesLoaded(): void {
    if (this.servicesList().length === 0) {
      this.serviceAdminService.getServices(true).subscribe({
        next: (svcs) => this.servicesList.set(svcs),
        error: (err) => console.warn('Error al precargar servicios completos:', err)
      });
    }
  }

  onServicesSearchChange(q: string): void {
    this.servicesSearch.set(q);
    this.loadCatalogServices(1);
  }

  setServicesPage(page: number): void {
    if (page >= 1 && page <= this.servicesTotalPages() && page !== this.servicesPage()) {
      this.loadCatalogServices(page);
    }
  }

  // =========================================================================
  // MÉTODOS PARA SERVICIOS (TRATAMIENTOS CRUD)
  // =========================================================================
  openCreateServiceModal(): void {
    this.editingService.set(null);
    this.serviceImageFile = null;
    this.serviceImagePreview.set(null);
    this.serviceForm = {
      name: '',
      category: 'Manicura y Pedicura',
      description: '',
      duration_minutes: 60,
      base_price: 40000,
      deposit_amount: 15000,
      requires_deposit: true,
      is_active: true,
      image_url: ''
    };
    this.showServiceModal.set(true);
  }

  openEditServiceModal(svc: AdminServiceItem): void {
    this.editingService.set(svc);
    this.serviceImageFile = null;
    this.serviceImagePreview.set(svc.image_url || null);
    this.serviceForm = {
      name: svc.name,
      category: svc.category || 'Manicura y Pedicura',
      description: svc.description || '',
      duration_minutes: svc.duration_minutes,
      base_price: svc.base_price,
      deposit_amount: svc.deposit_amount,
      requires_deposit: svc.requires_deposit ?? (svc.deposit_amount > 0),
      is_active: svc.is_active,
      image_url: svc.image_url || ''
    };
    this.showServiceModal.set(true);
  }

  onServiceImageSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      this.serviceImageFile = file;
      const reader = new FileReader();
      reader.onload = (e) => {
        this.serviceImagePreview.set(e.target?.result as string);
      };
      reader.readAsDataURL(file);
    }
  }

  removeServiceImage(): void {
    this.serviceImageFile = null;
    this.serviceImagePreview.set(null);
    this.serviceForm.image_url = '';
  }

  saveService(): void {
    if (!this.serviceForm.name.trim()) return;

    this.isSavingService.set(true);

    const formData = new FormData();
    formData.append('name', this.serviceForm.name.trim());
    formData.append('category', this.serviceForm.category || 'Manicura y Pedicura');
    formData.append('description', this.serviceForm.description || '');
    formData.append('duration_minutes', this.serviceForm.duration_minutes.toString());
    formData.append('base_price', this.serviceForm.base_price.toString());
    formData.append('deposit_amount', this.serviceForm.deposit_amount.toString());
    formData.append('is_active', this.serviceForm.is_active ? '1' : '0');

    if (this.serviceImageFile) {
      formData.append('image', this.serviceImageFile);
    } else if (!this.serviceImagePreview()) {
      formData.append('remove_image', '1');
    } else if (this.serviceForm.image_url) {
      formData.append('image_url', this.serviceForm.image_url);
    }

    const request$ = this.editingService()
      ? this.serviceAdminService.updateService(this.editingService()!.id, formData)
      : this.serviceAdminService.createService(formData);

    request$.subscribe({
      next: () => {
        this.isSavingService.set(false);
        this.showServiceModal.set(false);
        this.loadCatalogServices(this.editingService() ? this.servicesPage() : 1);
        // Refrescar también la lista completa para agendamiento
        this.serviceAdminService.getServices(true).subscribe(svcs => this.servicesList.set(svcs));
      },
      error: (err) => {
        this.isSavingService.set(false);
        console.error('Error al guardar servicio:', err);
      }
    });
  }

  deleteService(id: number): void {
    if (!confirm('¿Deseas dar de baja este tratamiento del catálogo?')) return;
    this.serviceAdminService.deleteService(id).subscribe({
      next: () => {
        this.loadCatalogServices(this.servicesPage());
        this.serviceAdminService.getServices(true).subscribe(svcs => this.servicesList.set(svcs));
      },
      error: (err) => console.error('Error al eliminar servicio:', err)
    });
  }

  // =========================================================================
  // MÉTODOS PARA CRM (CLIENTAS)
  // =========================================================================
  loadCrmCustomers(page: number = 1): void {
    this.isLoadingCustomers.set(true);
    this.customerService.getCustomersPaginated(page, this.crmPerPage, this.crmSearch()).subscribe({
      next: (res) => {
        this.crmCustomers.set(res.data);
        this.crmPage.set(res.meta.current_page);
        this.crmTotalPages.set(res.meta.last_page);
        this.crmTotalItems.set(res.meta.total);
        this.isLoadingCustomers.set(false);
      },
      error: (err) => {
        this.isLoadingCustomers.set(false);
        console.error('Error al cargar clientas paginadas:', err);
      }
    });
  }

  onCrmSearchChange(query: string): void {
    this.crmSearch.set(query);
    this.loadCrmCustomers(1);
  }

  setCrmPage(page: number): void {
    if (page >= 1 && page <= this.crmTotalPages() && page !== this.crmPage()) {
      this.loadCrmCustomers(page);
    }
  }

  getPageNumbers(currentPage: number, totalPages: number): number[] {
    const pages: number[] = [];
    const maxVisible = 5;
    let start = Math.max(1, currentPage - Math.floor(maxVisible / 2));
    let end = Math.min(totalPages, start + maxVisible - 1);
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }

  getMin(a: number, b: number): number {
    return Math.min(a, b);
  }

  openCreateCustomerModal(): void {
    this.editingCustomer.set(null);
    this.customerForm = {
      name: '',
      phone: '',
      email: '',
      notes: ''
    };
    this.showCustomerModal.set(true);
  }

  openEditCustomerModal(cust: AdminCustomer): void {
    this.editingCustomer.set(cust);
    this.customerForm = {
      name: cust.name,
      phone: cust.phone,
      email: cust.email || '',
      notes: cust.notes || ''
    };
    this.showCustomerModal.set(true);
  }

  saveCustomer(): void {
    if (!this.customerForm.name.trim() || !this.customerForm.phone.trim()) return;

    if (this.editingCustomer()) {
      this.customerService.updateCustomer(this.editingCustomer()!.id, this.customerForm).subscribe({
        next: () => {
          this.showCustomerModal.set(false);
          this.loadCrmCustomers(this.crmPage());
        },
        error: (err) => console.error('Error al actualizar clienta:', err)
      });
    } else {
      this.customerService.createCustomer(this.customerForm).subscribe({
        next: () => {
          this.showCustomerModal.set(false);
          this.loadCrmCustomers(1);
        },
        error: (err) => console.error('Error al registrar clienta:', err)
      });
    }
  }

  // =========================================================================
  // MÉTODOS PARA HORARIOS & BLOQUEOS
  // =========================================================================
  loadWeeklySchedules(): void {
    this.scheduleService.getSchedules().subscribe({
      next: (res) => this.weeklySchedules.set(res),
      error: (err) => console.error('Error al cargar jornadas:', err)
    });
  }

  saveWeeklySchedules(): void {
    this.isSavingSchedules.set(true);
    this.scheduleService.batchUpdateSchedules(this.weeklySchedules()).subscribe({
      next: () => {
        this.isSavingSchedules.set(false);
        alert('Horarios semanales actualizados exitosamente.');
      },
      error: (err) => {
        this.isSavingSchedules.set(false);
        console.error('Error al guardar horarios:', err);
      }
    });
  }

  loadBlockedSlots(): void {
    this.scheduleService.getBlockedSlots().subscribe({
      next: (res) => this.blockedSlots.set(res),
      error: (err) => console.error('Error al cargar bloqueos:', err)
    });
  }

  openCreateBlockModal(): void {
    this.blockForm = {
      title: 'Almuerzo / Pausa Activa',
      blocked_date: '',
      start_time: '13:00',
      end_time: '14:00',
      is_recurring: true,
      reason: 'Pausa para almuerzo'
    };
    this.showBlockModal.set(true);
  }

  saveBlockedSlot(): void {
    if (!this.blockForm.title.trim()) return;
    this.scheduleService.createBlockedSlot({
      title: this.blockForm.title,
      blocked_date: this.blockForm.is_recurring ? null : this.blockForm.blocked_date,
      start_time: this.blockForm.start_time,
      end_time: this.blockForm.end_time,
      is_recurring: this.blockForm.is_recurring,
      reason: this.blockForm.reason
    }).subscribe({
      next: () => {
        this.showBlockModal.set(false);
        this.loadBlockedSlots();
      },
      error: (err) => console.error('Error al crear bloqueo:', err)
    });
  }

  deleteBlockedSlot(id: number): void {
    if (!confirm('¿Deseas eliminar este bloqueo de horario?')) return;
    this.scheduleService.deleteBlockedSlot(id).subscribe({
      next: () => this.loadBlockedSlots(),
      error: (err) => console.error('Error al eliminar bloqueo:', err)
    });
  }

  // =========================================================================
  // MÉTODOS PARA LISTA DE ESPERA
  // =========================================================================
  loadWaitlist(): void {
    this.isLoadingWaitlist.set(true);
    this.waitlistService.getWaitlist().subscribe({
      next: (res) => {
        this.waitlist.set(res);
        this.isLoadingWaitlist.set(false);
      },
      error: (err) => {
        console.error('Error al cargar lista de espera:', err);
        this.isLoadingWaitlist.set(false);
      }
    });
  }

  updateWaitlistStatus(id: number, status: 'PENDING' | 'NOTIFIED' | 'BOOKED' | 'CANCELLED'): void {
    this.waitlistService.updateStatus(id, status).subscribe({
      next: () => this.loadWaitlist(),
      error: (err) => console.error('Error al actualizar estado en espera:', err)
    });
  }

  deleteWaitlistItem(id: number): void {
    if (!confirm('¿Deseas retirar esta solicitud de la lista de espera?')) return;
    this.waitlistService.deleteWaitlist(id).subscribe({
      next: () => this.loadWaitlist(),
      error: (err) => console.error('Error al eliminar de lista de espera:', err)
    });
  }

  // =========================================================================
  // MÉTODOS PARA WHATSAPP META CLOUD API & EVOLUTION API
  // =========================================================================
  loadWhatsAppSettings(): void {
    this.whatsappService.getSettings().subscribe({
      next: (res) => {
        this.whatsAppSettings.set(res);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.whatsAppForm = {
          whatsapp_driver: res.whatsapp_driver || 'meta',
          whatsapp_number: res.whatsapp_number || '',
          meta_phone_number_id: res.meta_phone_number_id || '',
          meta_waba_id: res.meta_waba_id || '',
          meta_access_token: res.meta_access_token || '',
          meta_verify_token: res.meta_verify_token || 'nuvex_meta_verify_2026'
        };
        if (!this.whatsAppTestPhone().trim()) {
          const defaultPhone = (res.whatsapp_number || '').replace(/\D/g, '');
          this.whatsAppTestPhone.set(defaultPhone || '3106080402');
        }
      },
      error: (err) => console.error('Error al cargar WhatsApp settings:', err)
    });
  }

  checkWhatsAppStatus(): void {
    this.isCheckingWhatsAppStatus.set(true);
    this.whatsappService.checkStatus().subscribe({
      next: (status) => {
        this.whatsAppAccountStatus.set(status);
        this.isCheckingWhatsAppStatus.set(false);
      },
      error: (err) => {
        this.isCheckingWhatsAppStatus.set(false);
        this.whatsAppAccountStatus.set({
          connected: false,
          state: 'unreachable',
          message: err?.error?.message || 'Error al conectar con la API de WhatsApp.'
        });
      }
    });
  }

  saveWhatsAppSettings(): void {
    this.isSavingWhatsApp.set(true);
    const payload: Partial<WhatsAppSettings> = {
      whatsapp_driver: this.whatsAppForm.whatsapp_driver,
      whatsapp_number: this.whatsAppForm.whatsapp_number,
      meta_phone_number_id: this.whatsAppForm.meta_phone_number_id,
      meta_waba_id: this.whatsAppForm.meta_waba_id,
      meta_access_token: this.whatsAppForm.meta_access_token.trim(),
      meta_verify_token: this.whatsAppForm.meta_verify_token
    };

    this.whatsappService.updateSettings(payload).subscribe({
      next: (res) => {
        this.whatsAppSettings.set(res.data);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.isSavingWhatsApp.set(false);
      },
      error: (err) => {
        this.isSavingWhatsApp.set(false);
        console.error('Error al guardar configuración WhatsApp:', err);
      }
    });
  }

  registerMetaPin(): void {
    const pin = this.metaPinCode().trim();
    if (!/^\d{6}$/.test(pin)) {
      alert('El PIN debe contener exactamente 6 dígitos numéricos (ej: 123456).');
      return;
    }

    this.isRegisteringPin.set(true);
    this.whatsappService.registerPin(pin, {
      meta_phone_number_id: this.whatsAppForm.meta_phone_number_id,
      meta_access_token: this.whatsAppForm.meta_access_token
    }).subscribe({
      next: (res) => {
        this.isRegisteringPin.set(false);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.checkWhatsAppStatus();
      },
      error: (err) => {
        this.isRegisteringPin.set(false);
        alert(err?.error?.message || 'Error al registrar el número con PIN en Meta.');
      }
    });
  }

  syncWhatsAppTemplates(): void {
    this.isSyncingTemplates.set(true);
    this.whatsappService.syncTemplates().subscribe({
      next: (res) => {
        this.isSyncingTemplates.set(false);
        this.wabaTemplates.set(res.templates || []);
        this.hasSyncedTemplates.set(true);
      },
      error: (err) => {
        this.isSyncingTemplates.set(false);
        this.wabaTemplates.set([]);
        console.error('Error al sincronizar plantillas de Meta:', err);
      }
    });
  }

  selectWhatsAppTemplate(name: string, lang: string): void {
    this.whatsAppTestTemplateName.set(name);
    this.whatsAppTestTemplateLang.set(lang);
  }

  sendWhatsAppTest(): void {
    const phone = this.whatsAppTestPhone().trim();
    if (!phone) {
      alert('Ingresa un número de celular de destino.');
      return;
    }

    this.isSendingWhatsAppTest.set(true);
    this.whatsAppTestResult.set(null);

    const payload = {
      phone,
      test_mode: this.whatsAppTestMode(),
      template_name: this.whatsAppTestTemplateName(),
      template_lang: this.whatsAppTestTemplateLang(),
      message: this.whatsAppTestCustomMessage().trim()
    };

    this.whatsappService.sendTest(payload).subscribe({
      next: (res) => {
        this.isSendingWhatsAppTest.set(false);
        this.whatsAppTestResult.set(res);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
      },
      error: (err) => {
        this.isSendingWhatsAppTest.set(false);
        const errData = err?.error || {};
        this.whatsAppTestResult.set({
          success: false,
          message: errData.message || 'Error al enviar mensaje de prueba.',
          error_code: errData.error_code,
          has_pin_error: errData.has_pin_error || (errData.message && errData.message.includes('133010')),
          account_status: errData.account_status
        });
        if (errData.account_status) {
          this.whatsAppAccountStatus.set(errData.account_status);
        }
      }
    });
  }

  // =========================================================================
  // MÉTODOS PARA SUPERADMIN NUVEX
  // =========================================================================
  loadSuperAdminData(): void {
    this.superAdminService.getAnalytics().subscribe({
      next: (res) => this.superAnalytics.set(res),
      error: (err) => console.error('Error al cargar analíticas SuperAdmin:', err)
    });

    this.superAdminService.getTenants().subscribe({
      next: (res) => this.superTenants.set(res),
      error: (err) => console.error('Error al cargar tenants:', err)
    });

    this.superAdminService.getUsers().subscribe({
      next: (res) => this.superUsers.set(res),
      error: (err) => console.error('Error al cargar usuarios:', err)
    });
  }

  openCreateTenantModal(): void {
    this.tenantForm = {
      name: '',
      slug: '',
      domain: '',
      phone: '',
      email: ''
    };
    this.showTenantModal.set(true);
  }

  saveTenant(): void {
    if (!this.tenantForm.name.trim()) return;
    this.superAdminService.createTenant(this.tenantForm).subscribe({
      next: () => {
        this.showTenantModal.set(false);
        this.loadSuperAdminData();
      },
      error: (err) => console.error('Error al crear tenant:', err)
    });
  }

  toggleTenantStatus(tenant: SuperTenant): void {
    this.superAdminService.updateTenant(tenant.id, { is_active: !tenant.is_active }).subscribe({
      next: () => this.loadSuperAdminData(),
      error: (err) => console.error('Error al alternar estado tenant:', err)
    });
  }

  openCreateUserModal(): void {
    this.userForm = {
      name: '',
      email: '',
      password: '',
      role: 'ADMIN',
      tenant_id: this.superTenants().length > 0 ? this.superTenants()[0].id : null
    };
    this.showUserModal.set(true);
  }

  saveSuperUser(): void {
    if (!this.userForm.name.trim() || !this.userForm.email.trim() || !this.userForm.password.trim()) return;
    this.superAdminService.createUser(this.userForm).subscribe({
      next: () => {
        this.showUserModal.set(false);
        this.loadSuperAdminData();
      },
      error: (err) => console.error('Error al crear usuario:', err)
    });
  }

  deleteSuperUser(id: number): void {
    if (!confirm('¿Deseas eliminar este usuario de la plataforma?')) return;
    this.superAdminService.deleteUser(id).subscribe({
      next: () => this.loadSuperAdminData(),
      error: (err) => console.error('Error al eliminar usuario:', err)
    });
  }

  // =========================================================================
  // GESTIÓN DE CITAS: APROBAR NEQUI, ESTADOS Y ACCIONES DIRECTAS
  // =========================================================================
  approveNequi(apt: AdminAppointment): void {
    if (!confirm(`¿Confirmar recepción del abono para la cita ${apt.appointment_number} de ${apt.client.name}?`)) return;

    const deposit = apt.financials?.deposit_paid || apt.financials?.deposit_amount || 15000;
    this.appointmentService.approveNequi(apt.id, deposit, 'Comprobante aprobado desde el panel administrativo').subscribe({
      next: () => {
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
      },
      error: (err) => {
        console.error('Error al aprobar Nequi:', err);
        alert(err?.error?.message || 'Error al validar comprobante.');
      }
    });
  }

  updateAppointmentStatus(id: number, status: AppointmentStatus): void {
    this.appointmentService.updateStatus(id, status).subscribe({
      next: () => {
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
      },
      error: (err) => console.error('Error al actualizar estado:', err)
    });
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

  // =========================================================================
  // NAVEGACIÓN TEMPORAL DEL CALENDARIO
  // =========================================================================

  goToToday(): void {
    this.currentDate.set(new Date());
    this.selectedDateStr.set(this.todayStr);
    this.loadAppointmentsForCurrentRange();
    this.loadDailySummary();
  }

  prev(): void {
    const d = new Date(this.currentDate());
    if (this.viewMode() === 'month') {
      d.setMonth(d.getMonth() - 1);
    } else if (this.viewMode() === 'week') {
      d.setDate(d.getDate() - 7);
    } else {
      d.setDate(d.getDate() - 1);
    }
    this.currentDate.set(d);
    this.loadAppointmentsForCurrentRange();
  }

  next(): void {
    const d = new Date(this.currentDate());
    if (this.viewMode() === 'month') {
      d.setMonth(d.getMonth() + 1);
    } else if (this.viewMode() === 'week') {
      d.setDate(d.getDate() + 7);
    } else {
      d.setDate(d.getDate() + 1);
    }
    this.currentDate.set(d);
    this.loadAppointmentsForCurrentRange();
  }

  setView(mode: 'month' | 'week' | 'day'): void {
    this.viewMode.set(mode);
    this.loadAppointmentsForCurrentRange();
  }

  toggleTheme(): void {
    this.isDarkMode.update(v => !v);
  }

  logout(): void {
    this.authService.logout();
  }

  // =========================================================================
  // INTERACCIÓN CON CITAS Y GRILLA
  // =========================================================================

  onEmptyCellClicked(dateStr: string, hour24: string): void {
    this.selectedDateStr.set(dateStr);
    this.openNewAppointmentModal(dateStr, hour24);
  }

  onAppointmentCardClicked(apt: AdminAppointment, event: MouseEvent): void {
    event.stopPropagation();
    this.selectedAppointment.set(apt);
  }

  closeDetailModal(): void {
    this.selectedAppointment.set(null);
    this.isEditingAppointment.set(false);
    this.editAppointmentError.set(null);
  }

  startEditingSelectedAppointment(): void {
    const apt = this.selectedAppointment();
    if (!apt) return;

    this.ensureServicesLoaded();
    this.editServiceId.set(apt.service?.id || null);
    this.editAppointmentDate.set(apt.date);
    this.editStartTime.set(apt.start_time ? apt.start_time.substring(0, 5) : '08:00');
    this.editEndTime.set(apt.end_time ? apt.end_time.substring(0, 5) : '09:00');
    this.editClientName.set(apt.client.name);
    this.editClientPhone.set(apt.client.phone);
    this.editTotalAmount.set(apt.financials?.total_amount || 0);
    this.editDepositPaid.set(apt.financials?.deposit_paid || 0);
    this.editStatus.set(apt.status);
    this.editAdminNotes.set(apt.notes?.admin_notes || '');
    this.editAppointmentError.set(null);
    this.isEditingAppointment.set(true);
  }

  cancelEditingSelectedAppointment(): void {
    this.isEditingAppointment.set(false);
    this.editAppointmentError.set(null);
  }

  onEditServiceChange(serviceId: any): void {
    const numId = Number(serviceId);
    this.editServiceId.set(numId);
    const svc = this.servicesList().find(s => s.id === numId);
    if (svc) {
      if (this.editStartTime()) {
        const [h, m] = this.editStartTime().split(':').map(n => parseInt(n, 10) || 0);
        const totalMin = h * 60 + m + (svc.duration_minutes || 60);
        const endH = Math.floor(totalMin / 60) % 24;
        const endM = totalMin % 60;
        this.editEndTime.set(`${endH.toString().padStart(2, '0')}:${endM.toString().padStart(2, '0')}`);
      }
      if (!this.editTotalAmount() || this.editTotalAmount() === 0) {
        this.editTotalAmount.set(svc.base_price || 0);
      }
    }
  }

  onEditStartTimeChange(startTime: string): void {
    this.editStartTime.set(startTime);
    const svc = this.servicesList().find(s => s.id === Number(this.editServiceId()));
    const dur = svc?.duration_minutes || 60;
    if (startTime && startTime.includes(':')) {
      const [h, m] = startTime.split(':').map(n => parseInt(n, 10) || 0);
      const totalMin = h * 60 + m + dur;
      const endH = Math.floor(totalMin / 60) % 24;
      const endM = totalMin % 60;
      this.editEndTime.set(`${endH.toString().padStart(2, '0')}:${endM.toString().padStart(2, '0')}`);
    }
  }

  saveEditedAppointment(): void {
    const apt = this.selectedAppointment();
    if (!apt) return;

    if (!this.editClientName().trim() || !this.editClientPhone().trim()) {
      this.editAppointmentError.set('El nombre y teléfono de la clienta son obligatorios.');
      return;
    }

    if (!this.editAppointmentDate() || !this.editStartTime()) {
      this.editAppointmentError.set('Debes indicar fecha y hora de inicio.');
      return;
    }

    this.isSavingAppointmentEdit.set(true);
    this.editAppointmentError.set(null);

    const payload: Partial<CreateAppointmentPayload> = {
      client_name: this.editClientName().trim(),
      client_phone: this.editClientPhone().trim(),
      appointment_date: this.editAppointmentDate(),
      start_time: this.editStartTime(),
      end_time: this.editEndTime(),
      service_id: Number(this.editServiceId()) || apt.service.id,
      total_amount: Number(this.editTotalAmount()) || 0,
      deposit_paid: Number(this.editDepositPaid()) || 0,
      status: this.editStatus(),
      admin_notes: this.editAdminNotes()
    };

    this.appointmentService.updateAppointment(apt.id, payload).subscribe({
      next: (updatedApt) => {
        this.isSavingAppointmentEdit.set(false);
        this.isEditingAppointment.set(false);
        this.selectedAppointment.set(updatedApt);
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
      },
      error: (err) => {
        this.isSavingAppointmentEdit.set(false);
        if (err.status === 422 && err.error?.message) {
          this.editAppointmentError.set(err.error.message);
        } else {
          this.editAppointmentError.set('No se pudo actualizar la cita. Verifica que no haya conflictos de horario.');
        }
      }
    });
  }

  // =========================================================================
  // LÓGICA DEL MODAL: AGENDAR NUEVA CITA / RESERVA (2 COLUMNAS)
  // =========================================================================

  openNewAppointmentModal(prefillDate?: string, prefillTime?: string): void {
    this.conflictError.set(null);
    this.isSavingAppointment.set(false);

    this.newApptDate.set(prefillDate || this.selectedDateStr() || this.todayStr);
    this.startTime.set(prefillTime || '09:00');

    this.clientName.set('');
    this.clientPhone.set('');
    this.clientEmail.set('');
    this.clientSearchQuery.set('');
    this.selectedCustomer.set(null);
    this.customerSearchResults.set([]);

    // Ningún servicio preseleccionado por defecto
    this.selectedServices.set([]);
    this.serviceSearchQuery.set('');
    this.serviceDropdownOpen.set(false);
    this.totalAmount.set(0);
    this.isCustomerNew.set(true);
    this.updateDepositRule();
    this.availableSlots.set(null);
    this.suggestedSlotValue.set('');
    this.recalculateEndTime(this.startTime(), 50);
    this.modalStep.set(1);

    this.showNewAppointmentModal.set(true);
  }

  closeNewAppointmentModal(): void {
    this.showNewAppointmentModal.set(false);
    this.serviceDropdownOpen.set(false);
    this.serviceSearchQuery.set('');
    this.selectedServices.set([]);
    this.modalStep.set(1);
    this.conflictError.set(null);
  }

  onServicesChange(): void {
    const services = this.selectedServices();
    if (services.length === 0) {
      this.totalAmount.set(0);
      this.updateDepositRule();
      this.availableSlots.set(null);
      this.recalculateEndTime(this.startTime(), 50);
      return;
    }

    const total = services.reduce((sum, s) => sum + (s.base_price || 0), 0);
    this.totalAmount.set(total);
    this.updateDepositRule();

    const dur = this.totalDurationMinutes() || 50;
    this.recalculateEndTime(this.startTime(), dur);
    this.fetchSlotsForModal();
  }

  onServiceChange(): void {
    this.onServicesChange();
  }

  onStartTimeManuallyChanged(newStart: string): void {
    this.startTime.set(newStart);
    const duration = this.totalDurationMinutes() || 50;
    this.recalculateEndTime(newStart, duration);
  }

  recalculateEndTime(start: string, durationMinutes: number): void {
    if (!start || !start.includes(':')) return;
    const [hStr, mStr] = start.split(':');
    let totalMinutes = parseInt(hStr, 10) * 60 + parseInt(mStr, 10) + durationMinutes;

    const endH = Math.floor(totalMinutes / 60) % 24;
    const endM = totalMinutes % 60;

    const endFormatted = `${endH.toString().padStart(2, '0')}:${endM.toString().padStart(2, '0')}`;
    this.endTime.set(endFormatted);
  }

  fetchSlotsForModal(): void {
    const date = this.newApptDate();
    const svcId = this.newApptServiceId();
    if (!date || !svcId) return;

    const totalDuration = this.totalDurationMinutes() || 50;

    this.isLoadingSlots.set(true);
    this.calendarService.getSlots(Number(svcId), date, false, totalDuration).subscribe({
      next: (res) => {
        this.availableSlots.set(res);
        this.isLoadingSlots.set(false);

        if (this.suggestedSlotValue()) {
          const [s] = this.suggestedSlotValue().split('|');
          if (s) {
            this.recalculateEndTime(s, totalDuration);
          }
        } else if (this.startTime()) {
          this.recalculateEndTime(this.startTime(), totalDuration);
        }
      },
      error: (err) => {
        console.warn('No se pudieron obtener turnos disponibles para el modal:', err);
        this.availableSlots.set(null);
        this.isLoadingSlots.set(false);
      }
    });
  }

  onSuggestedSlotSelected(slotValue: string): void {
    this.suggestedSlotValue.set(slotValue);
    if (!slotValue) return;

    const [start, end] = slotValue.split('|');
    if (start) {
      this.startTime.set(start);
      const totalDuration = this.totalDurationMinutes() || 50;
      this.recalculateEndTime(start, totalDuration);
    }
  }

  searchClients(query: string): void {
    this.clientSearchQuery.set(query);
    if (!query || query.trim().length < 2) {
      this.customerSearchResults.set([]);
      return;
    }

    this.isSearchingCustomer.set(true);
    this.customerService.searchCustomers(query.trim()).subscribe({
      next: (results) => {
        this.customerSearchResults.set(results);
        this.isSearchingCustomer.set(false);
      },
      error: () => {
        this.isSearchingCustomer.set(false);
      }
    });
  }

  selectExistingClient(cust: AdminCustomer): void {
    this.selectedCustomer.set(cust);
    this.clientName.set(cust.name);
    this.clientPhone.set(cust.phone);
    this.clientEmail.set(cust.email || '');
    this.clientSearchQuery.set(cust.name);
    this.customerSearchResults.set([]);

    // Determinar si es clienta recurrente / antigua
    const isRecurring = cust.is_legacy ||
      (cust.completed_appointments_count ?? 0) >= 1 ||
      cust.loyalty_badge?.label?.includes('Antigua') ||
      cust.loyalty_badge?.label?.includes('Recurrente') ||
      cust.loyalty_badge?.label?.includes('VIP');

    this.isCustomerNew.set(!isRecurring);
    this.updateDepositRule();
  }

  onClientPhoneChange(phone: string): void {
    this.clientPhone.set(phone);
    if (!phone || phone.trim().length < 7) {
      if (!this.selectedCustomer()) {
        this.isCustomerNew.set(true);
        this.updateDepositRule();
      }
    }
  }

  onPhoneBlur(): void {
    const rawPhone = this.clientPhone().trim();
    const cleanPhone = rawPhone.replace(/\D/g, '');
    if (cleanPhone.length >= 7) {
      this.customerService.searchCustomers(cleanPhone).subscribe({
        next: (results) => {
          const match = results.find(c => {
            const cPhone = c.phone.replace(/\D/g, '');
            return cPhone.endsWith(cleanPhone) || cleanPhone.endsWith(cPhone);
          });
          if (match) {
            const isRecurring = match.is_legacy ||
              (match.completed_appointments_count ?? 0) >= 1 ||
              match.loyalty_badge?.label?.includes('Antigua') ||
              match.loyalty_badge?.label?.includes('Recurrente') ||
              match.loyalty_badge?.label?.includes('VIP');
            this.isCustomerNew.set(!isRecurring);
            if (!this.clientName().trim()) {
              this.clientName.set(match.name);
            }
            if (!this.clientEmail().trim() && match.email) {
              this.clientEmail.set(match.email);
            }
            this.selectedCustomer.set(match);
          } else if (!this.selectedCustomer()) {
            this.isCustomerNew.set(true);
          }
          this.updateDepositRule();
        },
        error: () => {}
      });
    }
  }

  clearSelectedClient(): void {
    this.selectedCustomer.set(null);
    this.clientName.set('');
    this.clientPhone.set('');
    this.clientEmail.set('');
    this.clientSearchQuery.set('');
    this.isCustomerNew.set(true);
    this.updateDepositRule();
  }

  applyRequiredDeposit(): void {
    this.depositPaid.set(this.depositRequired());
  }

  submitNewAppointment(): void {
    this.conflictError.set(null);

    if (!this.clientName().trim()) {
      this.conflictError.set('Por favor ingresa el nombre de la clienta.');
      return;
    }

    if (!this.clientPhone().trim()) {
      this.conflictError.set('Por favor ingresa el teléfono o WhatsApp de la clienta.');
      return;
    }

    if (this.selectedServices().length === 0) {
      this.conflictError.set('Por favor selecciona al menos un servicio o tratamiento.');
      return;
    }

    const primaryService = this.selectedServices()[0];
    const serviceIds = this.selectedServices().map(s => s.id);

    const payload = {
      service_id: Number(primaryService.id),
      service_ids: serviceIds,
      appointment_date: this.newApptDate(),
      start_time: this.startTime(),
      end_time: this.endTime(),
      client_name: this.clientName().trim(),
      client_phone: this.clientPhone().trim(),
      client_email: this.clientEmail().trim() || undefined,
      admin_notes: this.verificationNotes().trim() || undefined,
      status: this.newApptStatus(),
      payment_method: this.newApptPaymentMethod(),
      total_amount: this.totalAmount(),
      deposit_amount: this.depositRequired(),
      deposit_paid: this.depositPaid()
    };

    this.isSavingAppointment.set(true);

    this.appointmentService.createAppointment(payload).subscribe({
      next: () => {
        this.isSavingAppointment.set(false);
        this.closeNewAppointmentModal();
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
      },
      error: (err) => {
        this.isSavingAppointment.set(false);
        if (err.status === 422 && err.error?.message) {
          this.conflictError.set(err.error.message);
        } else {
          this.conflictError.set('No se pudo guardar la cita. Verifica que no haya un choque de horarios.');
        }
      }
    });
  }

  // =========================================================================
  // HELPERS DE TIEMPO, POSICIONAMIENTO Y FORMATEO
  // =========================================================================

  getAppointmentsForDateAndHour(dateStr: string, hourNum: number): AdminAppointment[] {
    return this.filteredAppointments().filter(a => {
      if (a.date !== dateStr) return false;
      if (a.status === 'CANCELLED') return false;
      const startH = parseInt(a.start_time.split(':')[0], 10);
      return startH === hourNum;
    });
  }

  getAppointmentsForDate(dateStr: string): AdminAppointment[] {
    return this.filteredAppointments().filter(a => a.date === dateStr && a.status !== 'CANCELLED');
  }

  getPositionedAppointmentsForDate(dateStr: string): PositionedAppointment[] {
    const apts = this.filteredAppointments().filter(a => a.date === dateStr && a.status !== 'CANCELLED');
    if (!apts.length) return [];

    // 1. Mapeo a objetos con métricas de tiempo y geometría vertical
    interface RawEvent {
      appointment: AdminAppointment;
      startMinutes: number;
      topPx: number;
      heightPx: number;
      visualEndPx: number;
    }

    const rawList: RawEvent[] = apts.map(apt => {
      const [startH, startM] = apt.start_time.split(':').map(n => parseInt(n, 10) || 0);
      const startMinutes = (startH - this.baseHour) * 60 + startM;

      let durationMinutes = apt.service?.duration_minutes || 30;
      if (apt.end_time && apt.end_time.includes(':')) {
        const [endH, endM] = apt.end_time.split(':').map(n => parseInt(n, 10) || 0);
        const endMinutes = (endH - this.baseHour) * 60 + endM;
        if (endMinutes > startMinutes) {
          durationMinutes = endMinutes - startMinutes;
        }
      }

      const topPx = Math.max(0, (startMinutes / 60) * this.slotHeightPx);
      // Altura mínima visual de 26px para legibilidad y evitar corte de texto
      const heightPx = Math.max(26, ((durationMinutes / 60) * this.slotHeightPx) - 2);
      const visualEndPx = topPx + heightPx;

      return {
        appointment: apt,
        startMinutes,
        topPx,
        heightPx,
        visualEndPx
      };
    });

    // 2. Ordenar por inicio ascendente; si empatan, mayor duración primero
    rawList.sort((a, b) => a.topPx - b.topPx || b.heightPx - a.heightPx);

    // 3. Agrupar en clusters de colisión continua (Google Calendar Algorithm)
    const clusters: RawEvent[][] = [];
    let currentCluster: RawEvent[] = [];
    let currentClusterEnd = -1;

    for (const evt of rawList) {
      if (currentCluster.length === 0) {
        currentCluster.push(evt);
        currentClusterEnd = evt.visualEndPx;
      } else {
        if (evt.topPx < currentClusterEnd) {
          // Hay solapamiento visual: se une al mismo cluster
          currentCluster.push(evt);
          currentClusterEnd = Math.max(currentClusterEnd, evt.visualEndPx);
        } else {
          // Inicia un nuevo cluster independiente
          clusters.push(currentCluster);
          currentCluster = [evt];
          currentClusterEnd = evt.visualEndPx;
        }
      }
    }
    if (currentCluster.length > 0) {
      clusters.push(currentCluster);
    }

    // 4. Asignación voraz (greedy) de subcolumnas paralelas
    const result: PositionedAppointment[] = [];

    for (const cluster of clusters) {
      const columns: number[] = []; // Registra el visualEndPx de la última cita en cada columna
      const placements: { evt: RawEvent; col: number }[] = [];

      for (const evt of cluster) {
        let placedCol = -1;
        for (let c = 0; c < columns.length; c++) {
          if (columns[c] <= evt.topPx) {
            placedCol = c;
            columns[c] = evt.visualEndPx;
            break;
          }
        }
        if (placedCol === -1) {
          placedCol = columns.length;
          columns.push(evt.visualEndPx);
        }
        placements.push({ evt, col: placedCol });
      }

      const numCols = Math.max(1, columns.length);
      const colWidthPercent = 100 / numCols;

      for (const p of placements) {
        const leftPercent = p.col * colWidthPercent;
        const widthPercent = numCols > 1 ? (colWidthPercent - 1.2) : 98;
        const h = p.evt.heightPx;
        const isMicro = h < 34;
        const isCompact = h >= 34 && h < 54;

        let statusClass = 'bg-emerald-50/95 border-emerald-300 text-emerald-950 border-l-[3px] border-l-emerald-500 hover:bg-emerald-100/90';
        if (p.evt.appointment.status === 'PENDING_VERIFICATION') {
          statusClass = 'bg-amber-50/95 border-amber-300 text-amber-950 border-l-[3px] border-l-amber-500 hover:bg-amber-100/90';
        } else if (p.evt.appointment.status === 'COMPLETED') {
          statusClass = 'bg-purple-50/95 border-purple-300 text-purple-950 border-l-[3px] border-l-purple-500 hover:bg-purple-100/90';
        } else if (p.evt.appointment.status === 'CANCELLED') {
          statusClass = 'bg-slate-100/95 border-slate-300 text-slate-700 border-l-[3px] border-l-slate-400 hover:bg-slate-200/90';
        }

        result.push({
          appointment: p.evt.appointment,
          topPx: Math.round(p.evt.topPx),
          heightPx: Math.round(p.evt.heightPx),
          leftPercent: Number(leftPercent.toFixed(2)),
          widthPercent: Number(widthPercent.toFixed(2)),
          isMicro,
          isCompact,
          statusClass
        });
      }
    }

    return result;
  }

  calculateEventTop(startTime: string): number {
    const [hStr, mStr] = startTime.split(':');
    const h = parseInt(hStr, 10);
    const m = parseInt(mStr, 10);

    const diffHours = (h - this.baseHour) + (m / 60);
    return Math.max(0, diffHours * this.slotHeightPx);
  }

  calculateEventHeight(durationMinutes: number): number {
    const hours = durationMinutes / 60;
    return Math.max(28, (hours * this.slotHeightPx) - 4);
  }

  formatDate(d: Date): string {
    const y = d.getFullYear();
    const m = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  getMondayOfDate(d: Date): Date {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    date.setDate(diff);
    return date;
  }

  formatCurrency(value: number): string {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      maximumFractionDigits: 0
    }).format(value);
  }

  getStatusBadgeClass(status: AppointmentStatus): string {
    switch (status) {
      case 'CONFIRMED':
        return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
      case 'PENDING_VERIFICATION':
        return 'bg-amber-100 text-amber-800 border border-amber-200';
      case 'COMPLETED':
        return 'bg-purple-100 text-purple-800 border border-purple-200';
      case 'CANCELLED':
        return 'bg-rose-100 text-rose-800 border border-rose-200';
      default:
        return 'bg-slate-100 text-slate-700 border border-slate-200';
    }
  }

  getStatusLabel(status: AppointmentStatus): string {
    switch (status) {
      case 'CONFIRMED':
        return 'Confirmada';
      case 'PENDING_VERIFICATION':
        return 'Por Validar';
      case 'COMPLETED':
        return 'Completada';
      case 'CANCELLED':
        return 'Cancelada';
      case 'IN_PROGRESS':
        return 'En Curso';
      case 'PENDING_DEPOSIT':
        return 'Pendiente Abono';
      default:
        return status;
    }
  }
}
