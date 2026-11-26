import { Component, OnInit, signal, computed, inject, effect } from '@angular/core';
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
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';
import { AdminNavigationService } from '../../../core/services/admin-navigation.service';

import { 
  AdminAppointment, 
  AppointmentStatus, 
  CalendarKPIs, 
  SpotlightAppointment, 
  AdminSlot, 
  AdminSlotsResponse 
} from '../../../core/models/admin-appointment.models';
import { AdminCustomer, CustomerAppointmentHistoryItem } from '../../../core/models/admin-customer.models';
import { WorkingSchedule, BlockedSlot } from '../../../core/models/admin-schedule.models';
import { WaitlistItem } from '../../../core/models/admin-waitlist.models';
import { 
  WhatsAppSettings, 
  WhatsAppAccountStatus, 
  WhatsAppTemplate, 
  WhatsAppTestResponse,
  OfficialWhatsAppTemplate,
  TemplateVariableItem,
  BroadcastAudienceCounts,
  BroadcastAudienceSample,
  BroadcastCampaignItem,
  CreateBroadcastPayload
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

export interface PositionedBlockedSlot {
  slot: BlockedSlot;
  topPx: number;
  heightPx: number;
  timeRange: string;
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
  | 'plantillas'
  | 'difusion'
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
  private nuvexNotify = inject(NuvexNotificationService);
  private router = inject(Router);
  readonly nav = inject(AdminNavigationService, { optional: true });
  readonly isEmbeddedInLayout = computed(() => !!this.nav);

  // Perfil del Salón y Usuario Activo
  currentUser = this.authService.currentUser;
  currentTenant = this.authService.currentTenant;
  isSuperAdmin = this.authService.isSuperAdmin;

  systemBrand = signal<string>('Nuvex Agendamiento');
  salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  // Módulo / Sección Activa en el Hub Administrativo
  activeSection = signal<AdminSection>('calendario');

  constructor() {
    if (this.nav) {
      this.activeSection.set('calendario');
      effect(() => {
        const trigger = this.nav!.openNewAppointmentModalTrigger();
        if (trigger > 0) {
          this.openNewAppointmentModal();
        }
      });
    }
  }

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
      case 'plantillas': return 'PLANTILLAS WHATSAPP META';
      case 'difusion': return 'LISTAS DE DIFUSIÓN & CAMPAÑAS';
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

  // Estado y Paginación para Tabla de Gestión de Citas y Reservas
  tableAppointments = signal<AdminAppointment[]>([]);
  tableCitasPage = signal<number>(1);
  tableCitasPerPage = signal<number>(10);
  tableCitasTotalPages = signal<number>(1);
  tableCitasTotalItems = signal<number>(0);
  tableCitasStatusFilter = signal<string>('all');
  tableCitasSearch = signal<string>('');
  isLoadingTableCitas = signal<boolean>(false);

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
    let list = this.servicesList();
    if (q) {
      list = list.filter(s =>
        s.name.toLowerCase().includes(q) ||
        (s.category && s.category.toLowerCase().includes(q)) ||
        s.duration_minutes.toString().includes(q) ||
        s.base_price.toString().includes(q)
      );
    }
    return list.slice().sort((a, b) => {
      const favA = a.is_favorite ? 1 : 0;
      const favB = b.is_favorite ? 1 : 0;
      return favB - favA;
    });
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
  isSavingCustomer = signal<boolean>(false);
  customerFormError = signal<string | null>(null);

  // Modal para Eliminar / Desactivar clienta
  showDeleteCustomerModal = signal<boolean>(false);
  customerToDelete = signal<AdminCustomer | null>(null);
  isDeletingCustomer = signal<boolean>(false);

  customerForm: {
    name: string;
    nickname: string;
    phone: string;
    email: string;
    notes: string;
    is_legacy?: boolean;
    is_active?: boolean;
  } = {
    name: '',
    nickname: '',
    phone: '',
    email: '',
    notes: '',
    is_legacy: false,
    is_active: true
  };

  // Modal de Citas Agendadas por Clienta (Historial CRM)
  showCustomerAppointmentsModal = signal<boolean>(false);
  selectedCustomerForAppointments = signal<AdminCustomer | null>(null);
  customerAppointmentsList = signal<CustomerAppointmentHistoryItem[]>([]);
  isLoadingCustomerAppointments = signal<boolean>(false);

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

  // Catálogo Oficial de Plantillas y Previsualización
  officialTemplates = signal<OfficialWhatsAppTemplate[]>([]);
  isLoadingTemplatesCatalog = signal<boolean>(false);
  isCreatingMetaTemplate = signal<string | null>(null);
  selectedTemplateForPreview = signal<OfficialWhatsAppTemplate | null>(null);
  templateActionFeedback = signal<{ success: boolean; message: string } | null>(null);

  // Variables Disponibles y Edición de Plantillas
  availableVariables = signal<TemplateVariableItem[]>([]);
  selectedTemplateForEdit = signal<OfficialWhatsAppTemplate | null>(null);
  editTemplateBody = signal<string>('');
  editTemplateSamples = signal<string[]>([]);
  isSavingTemplate = signal<boolean>(false);
  templateEditValidationError = signal<string | null>(null);

  editTemplateLivePreview = computed(() => {
    let preview = this.editTemplateBody();
    const samples = this.editTemplateSamples();
    samples.forEach((sample, idx) => {
      preview = preview.replaceAll(`{{${idx + 1}}}`, sample || `[Variable ${idx + 1}]`);
    });
    return preview;
  });

  templatesSummary = computed(() => {
    const list = this.officialTemplates();
    const approved = list.filter(t => t.meta_status === 'APPROVED').length;
    const pending = list.filter(t => t.meta_status === 'PENDING').length;
    const notCreated = list.filter(t => !t.exists_in_meta || t.meta_status === 'NOT_REGISTERED').length;
    const rejected = list.filter(t => t.meta_status === 'REJECTED').length;
    return {
      total: list.length,
      approved,
      pending,
      notCreated,
      rejected
    };
  });

  templatesFilter = signal<'ALL' | 'APPROVED' | 'PENDING'>('ALL');

  filteredOfficialTemplates = computed(() => {
    const list = this.officialTemplates();
    const f = this.templatesFilter();
    if (f === 'APPROVED') {
      return list.filter(t => t.meta_status === 'APPROVED');
    }
    if (f === 'PENDING') {
      return list.filter(t => t.meta_status !== 'APPROVED');
    }
    return list;
  });

  // =========================================================================
  // LISTAS DE DIFUSIÓN Y CAMPAÑAS MASIVAS (WHATSAPP)
  // =========================================================================
  broadcastSegment = signal<'all' | 'recurring' | 'inactive_30' | 'new'>('all');
  broadcastAudienceCounts = signal<BroadcastAudienceCounts>({ all: 0, recurring: 0, inactive_30: 0, new: 0 });
  broadcastAudienceSample = signal<BroadcastAudienceSample[]>([]);
  isLoadingBroadcastAudience = signal<boolean>(false);

  broadcastName = signal<string>('Promo Especial ' + new Date().toLocaleDateString('es-CO', { month: 'short', day: 'numeric' }));
  broadcastTemplateKey = signal<string>('promocion_salon');
  broadcastPromoText = signal<string>('20% de descuento en todos los servicios de Manicura y Pestañas este fin de semana');
  broadcastTargetUrl = signal<string>('https://labellenailsbog.com');
  broadcastCustomBody = signal<string>('Hola {{1}}, en {{2}} tenemos una promoción especial para ti: {{3}}.\n\nReserva tu cita en el siguiente enlace: {{4}}\n\n¡Esperamos verte pronto!');
  broadcastTestPhone = signal<string>('');
  isSendingTestBroadcast = signal<boolean>(false);
  testBroadcastFeedback = signal<{ success: boolean; message: string } | null>(null);

  isLaunchingBroadcast = signal<boolean>(false);
  broadcastLaunchFeedback = signal<{ success: boolean; message: string; campaign?: BroadcastCampaignItem } | null>(null);

  broadcastCampaignsList = signal<BroadcastCampaignItem[]>([]);
  isLoadingBroadcastCampaigns = signal<boolean>(false);
  selectedCampaignDetail = signal<BroadcastCampaignItem | null>(null);

  broadcastLivePreview = computed(() => {
    const tKey = this.broadcastTemplateKey();
    const salonName = this.salonTitle() || 'La Belle Nails';
    const promo = this.broadcastPromoText() || 'Promoción especial';
    const link = this.broadcastTargetUrl() || 'https://labellenailsbog.com';
    const sampleNickname = 'Cami';

    if (tKey === 'promocion_salon') {
      return `Hola ${sampleNickname}, en ${salonName} tenemos una promoción especial para ti: ${promo}.\n\nReserva tu cita en el siguiente enlace: ${link}\n\n¡Esperamos verte pronto!`;
    } else if (tKey === 'reactivacion_clientas') {
      return `Hola ${sampleNickname}, te extrañamos en ${salonName}. Hace días que no te consentimos y queremos invitarte a renovar tu look.\n\nAgenda tu próxima cita aquí: ${link}\n\n¡Te esperamos con una atención especial!`;
    } else {
      let body = this.broadcastCustomBody();
      body = body.replaceAll('{{1}}', sampleNickname).replaceAll('{{apodo}}', sampleNickname);
      body = body.replaceAll('{{2}}', salonName).replaceAll('{{salon}}', salonName);
      body = body.replaceAll('{{3}}', promo).replaceAll('{{mensaje_promo}}', promo);
      body = body.replaceAll('{{4}}', link).replaceAll('{{link_portal}}', link);
      return body;
    }
  });

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
    this.loadBlockedSlots();
    this.loadTableAppointments(1);
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
      this.loadBlockedSlots();
      this.loadDailySummary();
    } else if (section === 'citas') {
      this.loadTableAppointments(1);
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
    } else if (section === 'plantillas') {
      this.loadTemplatesCatalog();
    } else if (section === 'difusion') {
      this.loadBroadcastAudience();
      this.loadBroadcastCampaigns();
    } else if (section === 'superadmin' && this.isSuperAdmin()) {
      this.loadSuperAdminData();
    }
  }

  // =========================================================================
  // CARGA DE DATOS PARA GESTIÓN DE CITAS Y RESERVAS (TABLA COMPLETA PAGINADA)
  // =========================================================================
  loadTableAppointments(page: number = 1): void {
    this.isLoadingTableCitas.set(true);
    const statusVal = this.tableCitasStatusFilter();

    this.appointmentService.getAppointments({
      page,
      per_page: this.tableCitasPerPage(),
      status: statusVal !== 'all' ? statusVal : undefined,
      search: this.tableCitasSearch().trim() || undefined,
      order_dir: 'desc'
    }).subscribe({
      next: (res) => {
        this.tableAppointments.set(res.data || []);
        if (res.meta) {
          this.tableCitasPage.set(res.meta.current_page || page);
          this.tableCitasTotalPages.set(res.meta.last_page || 1);
          this.tableCitasTotalItems.set(res.meta.total || res.data?.length || 0);
        }
        this.isLoadingTableCitas.set(false);
      },
      error: (err) => {
        console.error('Error al cargar tabla completa de citas:', err);
        this.isLoadingTableCitas.set(false);
      }
    });
  }

  onTableSearchChange(query: string): void {
    this.tableCitasSearch.set(query);
    this.loadTableAppointments(1);
  }

  setTableStatusFilter(status: string): void {
    this.tableCitasStatusFilter.set(status);
    this.loadTableAppointments(1);
  }

  setTableCitasPage(page: number): void {
    if (page >= 1 && page <= this.tableCitasTotalPages() && page !== this.tableCitasPage()) {
      this.loadTableAppointments(page);
    }
  }

  setTableCitasPerPage(perPage: number): void {
    this.tableCitasPerPage.set(perPage);
    this.loadTableAppointments(1);
  }

  // =========================================================================
  // CARGA DE DATOS DESDE LA API REST (CALENDARIO)
  // =========================================================================

  loadAppointmentsForCurrentRange(): void {
    this.isLoading.set(true);
    this.loadBlockedSlots();
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
      this.ensureServicesLoaded();
    }
  }

  ensureServicesLoaded(force: boolean = false): void {
    if (force || this.servicesList().length === 0) {
      this.serviceAdminService.getServices(true).subscribe({
        next: (svcs) => {
          const sorted = (svcs || []).slice().sort((a, b) => {
            const favA = a.is_favorite ? 1 : 0;
            const favB = b.is_favorite ? 1 : 0;
            return favB - favA;
          });
          this.servicesList.set(sorted);
        },
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

  async deleteService(id: number): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Dar de baja este tratamiento?',
      subtitle: 'El servicio dejará de estar disponible para las clientas en el portal',
      message: 'Los turnos y citas existentes que ya lo tengan asignado no se verán afectados.',
      confirmText: 'Sí, Dar de Baja',
      cancelText: 'Cancelar',
      variant: 'danger',
      icon: 'trash'
    });
    if (!ok) return;

    this.serviceAdminService.deleteService(id).subscribe({
      next: () => {
        this.nuvexNotify.success('Tratamiento retirado', 'El servicio ha sido dado de baja del catálogo.');
        this.loadCatalogServices(this.servicesPage());
        this.serviceAdminService.getServices(true).subscribe(svcs => this.servicesList.set(svcs));
      },
      error: (err) => {
        this.nuvexNotify.error('Error al dar de baja', err?.error?.message || 'No fue posible eliminar el servicio.');
      }
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
    this.customerFormError.set(null);
    this.customerForm = {
      name: '',
      nickname: '',
      phone: '',
      email: '',
      notes: '',
      is_legacy: false,
      is_active: true
    };
    this.showCustomerModal.set(true);
  }

  openEditCustomerModal(cust: AdminCustomer): void {
    this.editingCustomer.set(cust);
    this.customerFormError.set(null);
    this.customerForm = {
      name: cust.name,
      nickname: cust.nickname || '',
      phone: cust.phone,
      email: cust.email || '',
      notes: cust.notes || '',
      is_legacy: !!cust.is_legacy,
      is_active: cust.is_active !== false
    };
    this.showCustomerModal.set(true);
  }

  closeCustomerModal(): void {
    this.showCustomerModal.set(false);
    this.editingCustomer.set(null);
    this.customerFormError.set(null);
  }

  saveCustomer(): void {
    if (!this.customerForm.name.trim() || !this.customerForm.phone.trim()) {
      this.customerFormError.set('El nombre y el celular de la clienta son campos obligatorios.');
      return;
    }

    this.isSavingCustomer.set(true);
    this.customerFormError.set(null);

    const payload = {
      name: this.customerForm.name.trim(),
      nickname: this.customerForm.nickname?.trim() || null,
      phone: this.customerForm.phone.trim(),
      email: this.customerForm.email?.trim() || null,
      notes: this.customerForm.notes?.trim() || null,
      is_legacy: this.customerForm.is_legacy,
      is_active: this.customerForm.is_active
    };

    if (this.editingCustomer()) {
      this.customerService.updateCustomer(this.editingCustomer()!.id, payload).subscribe({
        next: () => {
          this.isSavingCustomer.set(false);
          this.closeCustomerModal();
          this.loadCrmCustomers(this.crmPage());
        },
        error: (err) => {
          this.isSavingCustomer.set(false);
          const msg = err.error?.message || 'Error al actualizar los datos de la clienta.';
          this.customerFormError.set(msg);
          console.error('Error al actualizar clienta:', err);
        }
      });
    } else {
      this.customerService.createCustomer(payload).subscribe({
        next: () => {
          this.isSavingCustomer.set(false);
          this.closeCustomerModal();
          this.loadCrmCustomers(1);
        },
        error: (err) => {
          this.isSavingCustomer.set(false);
          const msg = err.error?.message || 'Error al registrar la nueva clienta.';
          this.customerFormError.set(msg);
          console.error('Error al registrar clienta:', err);
        }
      });
    }
  }

  openDeleteCustomerConfirm(cust: AdminCustomer): void {
    this.customerToDelete.set(cust);
    this.showDeleteCustomerModal.set(true);
  }

  closeDeleteCustomerConfirm(): void {
    this.showDeleteCustomerModal.set(false);
    this.customerToDelete.set(null);
  }

  confirmDeleteOrDeactivateCustomer(): void {
    const cust = this.customerToDelete();
    if (!cust) return;

    this.isDeletingCustomer.set(true);
    this.customerService.deleteCustomer(cust.id).subscribe({
      next: () => {
        this.isDeletingCustomer.set(false);
        this.closeDeleteCustomerConfirm();
        this.loadCrmCustomers(this.crmPage());
      },
      error: (err) => {
        this.isDeletingCustomer.set(false);
        console.error('Error al eliminar o desactivar clienta:', err);
      }
    });
  }

  toggleCustomerActive(cust: AdminCustomer): void {
    this.customerService.toggleActive(cust.id).subscribe({
      next: () => {
        this.loadCrmCustomers(this.crmPage());
      },
      error: (err) => {
        console.error('Error al cambiar estado activo/inactivo de la clienta:', err);
      }
    });
  }

  openCustomerAppointmentsModal(customer: AdminCustomer): void {
    this.selectedCustomerForAppointments.set(customer);
    this.customerAppointmentsList.set([]);
    this.isLoadingCustomerAppointments.set(true);
    this.showCustomerAppointmentsModal.set(true);

    this.customerService.getCustomer(customer.id).subscribe({
      next: (fullCustomer) => {
        this.selectedCustomerForAppointments.set(fullCustomer);
        this.customerAppointmentsList.set(fullCustomer.appointments || []);
        this.isLoadingCustomerAppointments.set(false);
      },
      error: (err) => {
        console.error('Error al cargar historial de citas de la clienta:', err);
        this.isLoadingCustomerAppointments.set(false);
      }
    });
  }

  closeCustomerAppointmentsModal(): void {
    this.showCustomerAppointmentsModal.set(false);
    this.selectedCustomerForAppointments.set(null);
    this.customerAppointmentsList.set([]);
    this.isLoadingCustomerAppointments.set(false);
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
        this.nuvexNotify.success('Horarios actualizados', 'La jornada semanal del salón se guardó exitosamente.');
      },
      error: (err) => {
        this.isSavingSchedules.set(false);
        this.nuvexNotify.error('Error al guardar', err?.error?.message || 'No fue posible guardar los horarios.');
      }
    });
  }

  loadBlockedSlots(): void {
    this.scheduleService.getBlockedSlots().subscribe({
      next: (res) => this.blockedSlots.set(res),
      error: (err) => console.error('Error al cargar bloqueos:', err)
    });
  }

  openCreateBlockModal(defaultDate?: string, defaultHour?: string): void {
    const startTime = defaultHour ? `${defaultHour}:00` : '13:00';
    const endH = defaultHour ? (parseInt(defaultHour, 10) + 1).toString().padStart(2, '0') : '14';
    const endTime = `${endH}:00`;

    this.blockForm = {
      title: 'Pausa / Bloqueo de Horario',
      blocked_date: defaultDate || this.selectedDateStr() || '',
      start_time: startTime,
      end_time: endTime,
      is_recurring: !defaultDate,
      reason: 'Horario reservado / no disponible'
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
        this.nuvexNotify.success('Horario bloqueado', 'La franja seleccionada ha quedado reservada.');
        this.loadBlockedSlots();
        this.loadAppointmentsForCurrentRange();
      },
      error: (err) => {
        console.error('Error al crear bloqueo:', err);
        this.nuvexNotify.error('Error al bloquear', err?.error?.message || 'No fue posible registrar el bloqueo.');
      }
    });
  }

  async deleteBlockedSlot(id: number, skipConfirm: boolean = false): Promise<void> {
    if (!skipConfirm) {
      const ok = await this.nuvexNotify.confirm({
        title: '¿Eliminar bloqueo de horario?',
        subtitle: 'El turno quedará disponible nuevamente para citas en el calendario',
        variant: 'danger',
        icon: 'lock',
        confirmText: 'Sí, Eliminar Bloqueo',
        cancelText: 'Cancelar'
      });
      if (!ok) return;
    }

    this.scheduleService.deleteBlockedSlot(id).subscribe({
      next: () => {
        this.nuvexNotify.success('Bloqueo eliminado', 'El horario ha sido liberado en el calendario.');
        this.loadBlockedSlots();
        this.loadAppointmentsForCurrentRange();
      },
      error: (err) => {
        this.nuvexNotify.error('Error al eliminar', err?.error?.message || 'Error al eliminar el bloqueo.');
      }
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

  async deleteWaitlistItem(id: number): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Retirar de la lista de espera?',
      subtitle: 'Esta solicitud de turno será retirada del sistema',
      variant: 'warning',
      icon: 'trash',
      confirmText: 'Sí, Retirar',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.waitlistService.deleteWaitlist(id).subscribe({
      next: () => {
        this.nuvexNotify.success('Solicitud retirada', 'La clienta fue retirada de la lista de espera.');
        this.loadWaitlist();
      },
      error: (err) => {
        this.nuvexNotify.error('Error al retirar', err?.error?.message || 'Error al eliminar de lista de espera.');
      }
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
      this.nuvexNotify.warning('PIN Inválido', 'El PIN debe contener exactamente 6 dígitos numéricos (ej: 123456).');
      return;
    }

    this.isRegisteringPin.set(true);
    this.whatsappService.registerPin(pin, {
      meta_phone_number_id: this.whatsAppForm.meta_phone_number_id,
      meta_access_token: this.whatsAppForm.meta_access_token
    }).subscribe({
      next: (res) => {
        this.isRegisteringPin.set(false);
        this.nuvexNotify.success('PIN Registrado', 'El número de WhatsApp fue registrado exitosamente en Meta.');
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.checkWhatsAppStatus();
      },
      error: (err) => {
        this.isRegisteringPin.set(false);
        this.nuvexNotify.error('Error al registrar PIN', err?.error?.message || 'Error al registrar el número con PIN en Meta.');
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
      this.nuvexNotify.warning('Número Requerido', 'Ingresa un número de celular de destino con indicativo.');
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
  // MÉTODOS PARA PLANTILLAS OFICIALES META CLOUD API (1-CLIC & PREVIEW)
  // =========================================================================
  loadTemplatesCatalog(): void {
    this.isLoadingTemplatesCatalog.set(true);
    this.templateActionFeedback.set(null);
    this.whatsappService.getTemplatesCatalog().subscribe({
      next: (res) => {
        this.isLoadingTemplatesCatalog.set(false);
        this.officialTemplates.set(res.templates || []);
        if (res.available_variables) {
          this.availableVariables.set(res.available_variables);
        }
      },
      error: (err) => {
        this.isLoadingTemplatesCatalog.set(false);
        console.error('Error al cargar catálogo de plantillas:', err);
      }
    });
  }

  openTemplatePreview(template: OfficialWhatsAppTemplate): void {
    this.selectedTemplateForPreview.set(template);
  }

  closeTemplatePreview(): void {
    this.selectedTemplateForPreview.set(null);
  }

  openTemplateEdit(template: OfficialWhatsAppTemplate): void {
    this.selectedTemplateForEdit.set(template);
    this.editTemplateBody.set(template.body);
    this.editTemplateSamples.set([...(template.samples || [])]);
    this.templateEditValidationError.set(null);
  }

  closeTemplateEdit(): void {
    this.selectedTemplateForEdit.set(null);
    this.templateEditValidationError.set(null);
  }

  insertVariableIntoBody(varItem: TemplateVariableItem): void {
    const currentBody = this.editTemplateBody();
    const matches = currentBody.match(/\{\{(\d+)\}\}/g) || [];
    const nextIndex = matches.length + 1;
    const token = `{{${nextIndex}}}`;

    const newBody = currentBody ? `${currentBody.trimEnd()} ${token}` : token;
    this.editTemplateBody.set(newBody);

    const currentSamples = [...this.editTemplateSamples()];
    currentSamples[nextIndex - 1] = varItem.sample;
    this.editTemplateSamples.set(currentSamples);
    this.validateEditTemplateBody();
  }

  validateEditTemplateBody(): boolean {
    const body = this.editTemplateBody().trim();
    if (!body) {
      this.templateEditValidationError.set('El cuerpo del mensaje no puede estar vacío.');
      return false;
    }

    if (/^\s*\{\{\d+\}\}/.test(body)) {
      this.templateEditValidationError.set('Meta prohíbe que la plantilla inicie con una variable. Debe iniciar con texto fijo (ej: "Hola {{1}}").');
      return false;
    }

    if (/\{\{\d+\}\}\s*$/.test(body)) {
      this.templateEditValidationError.set('Meta prohíbe que la plantilla termine en una variable (Error #2388299). Debes incluir una frase de cierre.');
      return false;
    }

    this.templateEditValidationError.set(null);
    return true;
  }

  saveTemplateEdit(syncToMeta: boolean = false): void {
    const template = this.selectedTemplateForEdit();
    if (!template) return;

    if (!this.validateEditTemplateBody()) {
      return;
    }

    this.isSavingTemplate.set(true);
    this.templateActionFeedback.set(null);

    this.whatsappService.updateTemplate(template.key, {
      body: this.editTemplateBody().trim(),
      samples: this.editTemplateSamples(),
      sync_to_meta: syncToMeta,
    }).subscribe({
      next: (res) => {
        this.isSavingTemplate.set(false);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || 'Plantilla actualizada exitosamente.',
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        this.closeTemplateEdit();
      },
      error: (err) => {
        this.isSavingTemplate.set(false);
        this.templateEditValidationError.set(err?.error?.message || 'Error al guardar la plantilla.');
      }
    });
  }

  async resetTemplateToOriginal(template: OfficialWhatsAppTemplate): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Restablecer Plantilla Oficial?',
      subtitle: `Se restaurará "${template.display_name}" a su versión estándar`,
      message: 'Las personalizaciones realizadas por el salón para esta plantilla serán reemplazadas por la redacción oficial aprobada.',
      variant: 'warning',
      icon: 'whatsapp',
      confirmText: 'Sí, Restablecer',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isSavingTemplate.set(true);
    this.templateActionFeedback.set(null);

    this.whatsappService.resetTemplate(template.key).subscribe({
      next: (res) => {
        this.isSavingTemplate.set(false);
        this.nuvexNotify.success('Plantilla restablecida', res.message || 'Plantilla restablecida a la versión oficial.');
        this.templateActionFeedback.set({
          success: true,
          message: res.message || 'Plantilla restablecida a la versión oficial.',
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        if (this.selectedTemplateForEdit()?.key === template.key) {
          this.closeTemplateEdit();
        }
      },
      error: (err) => {
        this.isSavingTemplate.set(false);
        this.nuvexNotify.error('Error al restablecer', err?.error?.message || 'Error al restablecer la plantilla.');
        this.templateActionFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al restablecer la plantilla.',
        });
      }
    });
  }

  createSingleMetaTemplate(template: OfficialWhatsAppTemplate): void {
    this.isCreatingMetaTemplate.set(template.key);
    this.templateActionFeedback.set(null);

    this.whatsappService.createTemplate(template.key).subscribe({
      next: (res) => {
        this.isCreatingMetaTemplate.set(null);
        this.nuvexNotify.success('Plantilla procesada', res.message || `Plantilla "${template.display_name}" procesada en Meta.`);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || `Plantilla "${template.display_name}" procesada en Meta.`
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        this.syncWhatsAppTemplates();
      },
      error: (err) => {
        this.isCreatingMetaTemplate.set(null);
        const msg = err?.error?.message || `Error al crear plantilla "${template.name}" en Meta.`;
        this.nuvexNotify.error('Error en Meta', msg);
        this.templateActionFeedback.set({
          success: false,
          message: msg
        });
      }
    });
  }

  async createAllMetaTemplates(): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: 'Registrar Catálogo en Meta',
      subtitle: 'Creación automática masiva en WhatsApp Cloud API',
      message: '¿Deseas enviar las 8 plantillas oficiales a tu cuenta de Meta WhatsApp Cloud API para su registro y aprobación?',
      variant: 'primary',
      icon: 'whatsapp',
      confirmText: 'Crear Todas en Meta',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isCreatingMetaTemplate.set('ALL');
    this.templateActionFeedback.set(null);

    this.whatsappService.createAllTemplates().subscribe({
      next: (res) => {
        this.isCreatingMetaTemplate.set(null);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || 'Proceso de registro masivo de plantillas completado.'
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        this.syncWhatsAppTemplates();
      },
      error: (err) => {
        this.isCreatingMetaTemplate.set(null);
        const msg = err?.error?.message || 'Error al registrar las plantillas en Meta.';
        this.templateActionFeedback.set({
          success: false,
          message: msg
        });
      }
    });
  }

  // =========================================================================
  // MÉTODOS PARA LISTAS DE DIFUSIÓN Y CAMPAÑAS (WHATSAPP)
  // =========================================================================
  loadBroadcastAudience(segment?: string): void {
    const targetSegment = segment || this.broadcastSegment();
    this.isLoadingBroadcastAudience.set(true);
    this.whatsappService.getBroadcastAudience(targetSegment).subscribe({
      next: (res) => {
        this.isLoadingBroadcastAudience.set(false);
        if (res.counts) {
          this.broadcastAudienceCounts.set(res.counts);
        }
        if (res.preview?.sample) {
          this.broadcastAudienceSample.set(res.preview.sample);
        }
      },
      error: (err) => {
        this.isLoadingBroadcastAudience.set(false);
        console.error('Error al cargar audiencia de difusión:', err);
      }
    });
  }

  loadBroadcastCampaigns(): void {
    this.isLoadingBroadcastCampaigns.set(true);
    this.whatsappService.getBroadcastCampaigns().subscribe({
      next: (res) => {
        this.isLoadingBroadcastCampaigns.set(false);
        if (res.campaigns?.data) {
          this.broadcastCampaignsList.set(res.campaigns.data);
        }
      },
      error: (err) => {
        this.isLoadingBroadcastCampaigns.set(false);
        console.error('Error al cargar campañas de difusión:', err);
      }
    });
  }

  onSelectBroadcastSegment(segment: 'all' | 'recurring' | 'inactive_30' | 'new'): void {
    this.broadcastSegment.set(segment);
    this.loadBroadcastAudience(segment);
  }

  onSelectBroadcastTemplate(templateKey: string): void {
    this.broadcastTemplateKey.set(templateKey);
  }

  sendTestBroadcastMessage(): void {
    const phone = this.broadcastTestPhone().trim();
    if (!phone) {
      this.testBroadcastFeedback.set({
        success: false,
        message: 'Ingresa un número de WhatsApp con indicativo para recibir la prueba.'
      });
      return;
    }

    this.isSendingTestBroadcast.set(true);
    this.testBroadcastFeedback.set(null);

    this.whatsappService.sendTestBroadcast({
      phone,
      template_key: this.broadcastTemplateKey(),
      promo_text: this.broadcastPromoText(),
      target_url: this.broadcastTargetUrl(),
      message_body: this.broadcastTemplateKey() === 'custom' ? this.broadcastCustomBody() : undefined
    }).subscribe({
      next: (res) => {
        this.isSendingTestBroadcast.set(false);
        this.testBroadcastFeedback.set({
          success: res.success,
          message: res.message || 'Mensaje de prueba enviado con éxito a tu WhatsApp.'
        });
      },
      error: (err) => {
        this.isSendingTestBroadcast.set(false);
        this.testBroadcastFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al enviar mensaje de prueba por WhatsApp.'
        });
      }
    });
  }

  async launchBroadcastCampaign(): Promise<void> {
    const counts = this.broadcastAudienceCounts();
    const seg = this.broadcastSegment();
    const totalCount = counts[seg] || 0;

    if (totalCount === 0) {
      this.nuvexNotify.warning('Sin Destinatarias', 'El segmento seleccionado no contiene clientas disponibles con número telefónico válido.');
      this.broadcastLaunchFeedback.set({
        success: false,
        message: 'El segmento seleccionado no contiene clientas disponibles con número telefónico válido.'
      });
      return;
    }

    const campaignName = this.broadcastName().trim() || 'Difusión Masiva';
    const segLabel = seg === 'all' ? 'Todas las clientas' : (seg === 'recurring' ? 'Clientas VIP (Frecuentes)' : (seg === 'inactive_30' ? 'Inactivas (+30 días)' : 'Nuevas clientas'));

    const ok = await this.nuvexNotify.confirm({
      title: 'Lanzar Campaña Masiva de Difusión',
      subtitle: `Se enviará vía WhatsApp Cloud API a ${totalCount} clientas`,
      message: `¿Confirmas el lanzamiento de la campaña "${campaignName}"? Los mensajes se personalizarán dinámicamente con el apodo de cada clienta.`,
      variant: 'primary',
      icon: 'whatsapp',
      details: [
        { label: 'Campaña', value: campaignName },
        { label: 'Segmento', value: segLabel },
        { label: 'Destinatarias', value: `${totalCount} contactos` }
      ],
      confirmText: `Sí, Disparar Difusión (${totalCount})`,
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isLaunchingBroadcast.set(true);
    this.broadcastLaunchFeedback.set(null);

    const payload: CreateBroadcastPayload = {
      name: campaignName,
      segment: seg,
      template_key: this.broadcastTemplateKey(),
      promo_text: this.broadcastPromoText(),
      target_url: this.broadcastTargetUrl(),
      message_body: this.broadcastLivePreview()
    };

    this.whatsappService.createAndExecuteBroadcast(payload).subscribe({
      next: (res) => {
        this.isLaunchingBroadcast.set(false);
        this.broadcastLaunchFeedback.set({
          success: res.success,
          message: res.message || 'Campaña de difusión completada.',
          campaign: res.campaign
        });
        this.loadBroadcastCampaigns();
      },
      error: (err) => {
        this.isLaunchingBroadcast.set(false);
        this.broadcastLaunchFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al ejecutar la campaña de difusión.'
        });
      }
    });
  }

  viewCampaignDetail(campaign: BroadcastCampaignItem): void {
    this.selectedCampaignDetail.set(campaign);
    this.whatsappService.getBroadcastCampaignDetails(campaign.id).subscribe({
      next: (res) => {
        if (res.campaign) {
          this.selectedCampaignDetail.set(res.campaign);
        }
      },
      error: (err) => console.error('Error al cargar detalle de campaña:', err)
    });
  }

  closeCampaignDetail(): void {
    this.selectedCampaignDetail.set(null);
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

  async deleteSuperUser(id: number): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Eliminar usuario de la plataforma?',
      subtitle: 'El usuario perderá inmediatamente el acceso al sistema',
      variant: 'danger',
      icon: 'trash',
      confirmText: 'Sí, Eliminar Usuario',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.superAdminService.deleteUser(id).subscribe({
      next: () => {
        this.nuvexNotify.success('Usuario eliminado', 'El usuario ha sido retirado de la plataforma.');
        this.loadSuperAdminData();
      },
      error: (err) => {
        this.nuvexNotify.error('Error al eliminar', err?.error?.message || 'Error al eliminar usuario.');
      }
    });
  }

  // =========================================================================
  // GESTIÓN DE CITAS: APROBAR NEQUI, ESTADOS Y ACCIONES DIRECTAS
  // =========================================================================
  async approveNequi(apt: AdminAppointment): Promise<void> {
    const deposit = apt.financials?.deposit_paid || apt.financials?.deposit_amount || 15000;
    const ok = await this.nuvexNotify.confirm({
      title: 'Confirmar Recepción de Abono',
      subtitle: `Cita #${apt.appointment_number} • ${apt.client.name}`,
      message: '¿Confirmas que el dinero del anticipo fue recibido exitosamente en la cuenta del salón? La cita pasará al estado Confirmada.',
      variant: 'primary',
      icon: 'check',
      details: [
        { label: 'Clienta', value: apt.client.name },
        { label: 'Cita', value: `#${apt.appointment_number}` },
        { label: 'Valor Abono', value: this.formatCurrency(deposit) }
      ],
      confirmText: 'Aprobar Abono',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.appointmentService.approveNequi(apt.id, deposit, 'Comprobante aprobado desde el panel administrativo').subscribe({
      next: () => {
        this.nuvexNotify.success('Abono aprobado', `El anticipo de ${apt.client.name} fue registrado con éxito.`);
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
        this.loadTableAppointments(this.tableCitasPage());
      },
      error: (err) => {
        console.error('Error al aprobar Nequi:', err);
        this.nuvexNotify.error('Error al validar comprobante', err?.error?.message || 'Error al validar comprobante.');
      }
    });
  }

  updateAppointmentStatus(id: number, status: AppointmentStatus): void {
    this.appointmentService.updateStatus(id, status).subscribe({
      next: () => {
        this.loadAppointmentsForCurrentRange();
        this.loadDailySummary();
        this.loadTableAppointments(this.tableCitasPage());
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
    this.editStartTime.set(this.roundTo15Minutes(apt.start_time ? apt.start_time.substring(0, 5) : '08:00'));
    this.editEndTime.set(this.roundTo15Minutes(apt.end_time ? apt.end_time.substring(0, 5) : '09:00'));
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
        const roundedStart = this.roundTo15Minutes(this.editStartTime());
        const [h, m] = roundedStart.split(':').map(n => parseInt(n, 10) || 0);
        let totalMin = h * 60 + m + (svc.duration_minutes || 60);
        totalMin = Math.round(totalMin / 15) * 15;
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
    const rounded = this.roundTo15Minutes(startTime);
    this.editStartTime.set(rounded);
    const svc = this.servicesList().find(s => s.id === Number(this.editServiceId()));
    const dur = svc?.duration_minutes || 60;
    if (rounded && rounded.includes(':')) {
      const [h, m] = rounded.split(':').map(n => parseInt(n, 10) || 0);
      let totalMin = h * 60 + m + dur;
      totalMin = Math.round(totalMin / 15) * 15;
      const endH = Math.floor(totalMin / 60) % 24;
      const endM = totalMin % 60;
      this.editEndTime.set(`${endH.toString().padStart(2, '0')}:${endM.toString().padStart(2, '0')}`);
    }
  }

  onEditStartTimeBlur(): void {
    const rounded = this.roundTo15Minutes(this.editStartTime());
    if (rounded !== this.editStartTime()) {
      this.onEditStartTimeChange(rounded);
    }
  }

  onEditEndTimeChange(endTime: string): void {
    const rounded = this.roundTo15Minutes(endTime);
    this.editEndTime.set(rounded);
  }

  onEditEndTimeBlur(): void {
    const rounded = this.roundTo15Minutes(this.editEndTime());
    if (rounded !== this.editEndTime()) {
      this.editEndTime.set(rounded);
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

    const finalStart = this.roundTo15Minutes(this.editStartTime());
    const finalEnd = this.roundTo15Minutes(this.editEndTime());
    this.editStartTime.set(finalStart);
    this.editEndTime.set(finalEnd);

    const payload: Partial<CreateAppointmentPayload> = {
      client_name: this.editClientName().trim(),
      client_phone: this.editClientPhone().trim(),
      appointment_date: this.editAppointmentDate(),
      start_time: finalStart,
      end_time: finalEnd,
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
        this.loadTableAppointments(this.tableCitasPage());
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
    this.startTime.set(this.roundTo15Minutes(prefillTime || '09:00'));

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

    this.ensureServicesLoaded(true);
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

  roundTo15Minutes(timeStr: string): string {
    if (!timeStr || !timeStr.includes(':')) return timeStr;
    const parts = timeStr.split(':');
    let h = parseInt(parts[0], 10);
    let m = parseInt(parts[1], 10);
    if (isNaN(h) || isNaN(m)) return timeStr;
    m = Math.round(m / 15) * 15;
    if (m >= 60) {
      h = (h + Math.floor(m / 60)) % 24;
      m = m % 60;
    }
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
  }

  onStartTimeManuallyChanged(newStart: string): void {
    const rounded = this.roundTo15Minutes(newStart);
    this.startTime.set(rounded);
    const duration = this.totalDurationMinutes() || 50;
    this.recalculateEndTime(rounded, duration);
  }

  onStartTimeBlur(): void {
    const rounded = this.roundTo15Minutes(this.startTime());
    if (rounded !== this.startTime()) {
      this.startTime.set(rounded);
      const duration = this.totalDurationMinutes() || 50;
      this.recalculateEndTime(rounded, duration);
    }
  }

  onEndTimeManuallyChanged(newEnd: string): void {
    const rounded = this.roundTo15Minutes(newEnd);
    this.endTime.set(rounded);
  }

  onEndTimeBlur(): void {
    const rounded = this.roundTo15Minutes(this.endTime());
    if (rounded !== this.endTime()) {
      this.endTime.set(rounded);
    }
  }

  recalculateEndTime(start: string, durationMinutes: number): void {
    if (!start || !start.includes(':')) return;
    const roundedStart = this.roundTo15Minutes(start);
    const [hStr, mStr] = roundedStart.split(':');
    let totalMinutes = (parseInt(hStr, 10) || 0) * 60 + (parseInt(mStr, 10) || 0) + durationMinutes;

    totalMinutes = Math.round(totalMinutes / 15) * 15;

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

    const finalStart = this.roundTo15Minutes(this.startTime());
    const finalEnd = this.roundTo15Minutes(this.endTime());
    this.startTime.set(finalStart);
    this.endTime.set(finalEnd);

    const payload = {
      service_id: Number(primaryService.id),
      service_ids: serviceIds,
      appointment_date: this.newApptDate(),
      start_time: finalStart,
      end_time: finalEnd,
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
        this.loadTableAppointments(1);
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

  // =========================================================================
  // BLOQUEOS DE HORARIO EN EL CALENDARIO (Almuerzos, Pausas y Bloqueos Puntuales)
  // =========================================================================

  getBlockedSlotsForDate(dateStr: string): BlockedSlot[] {
    return this.blockedSlots().filter(slot => {
      if (slot.is_recurring) return true;
      if (!slot.blocked_date) return false;
      const slotDate = slot.blocked_date.includes('T') ? slot.blocked_date.split('T')[0] : slot.blocked_date;
      return slotDate === dateStr;
    });
  }

  getPositionedBlockedSlotsForDate(dateStr: string): PositionedBlockedSlot[] {
    const slots = this.getBlockedSlotsForDate(dateStr);
    if (!slots.length) return [];

    return slots.map(slot => {
      const [startH, startM] = (slot.start_time || '13:00').split(':').map(n => parseInt(n, 10) || 0);
      const [endH, endM] = (slot.end_time || '14:00').split(':').map(n => parseInt(n, 10) || 0);

      const clampedStartH = Math.max(this.baseHour, startH);
      const startMinutes = (clampedStartH - this.baseHour) * 60 + startM;
      const endMinutes = (endH - this.baseHour) * 60 + endM;
      const duration = Math.max(15, endMinutes - startMinutes);

      const topPx = Math.max(0, (startMinutes / 60) * this.slotHeightPx);
      const heightPx = Math.max(26, (duration / 60) * this.slotHeightPx);

      const startFormatted = `${startH.toString().padStart(2, '0')}:${startM.toString().padStart(2, '0')}`;
      const endFormatted = `${endH.toString().padStart(2, '0')}:${endM.toString().padStart(2, '0')}`;

      return {
        slot,
        topPx,
        heightPx,
        timeRange: `${startFormatted} – ${endFormatted}`
      };
    });
  }

  getBlockedSlotsForDateAndHour(dateStr: string, hourNum: number): BlockedSlot[] {
    const slots = this.getBlockedSlotsForDate(dateStr);
    return slots.filter(slot => {
      const [startH] = (slot.start_time || '00:00').split(':').map(n => parseInt(n, 10) || 0);
      const [endH, endM] = (slot.end_time || '00:00').split(':').map(n => parseInt(n, 10) || 0);

      if (endM > 0) {
        return hourNum >= startH && hourNum <= endH;
      }
      return hourNum >= startH && hourNum < endH;
    });
  }

  async onBlockedSlotClicked(slot: BlockedSlot, event: MouseEvent): Promise<void> {
    event.stopPropagation();
    const typeStr = slot.is_recurring ? 'Recurrente Diario' : `Fecha puntual (${slot.blocked_date})`;
    const range = slot.time_range || `${slot.start_time?.substring(0, 5)} – ${slot.end_time?.substring(0, 5)}`;

    const details = [
      { label: 'Título', value: slot.title },
      { label: 'Horario', value: range },
      { label: 'Tipo', value: typeStr }
    ];
    if (slot.reason) {
      details.push({ label: 'Motivo', value: slot.reason });
    }

    const ok = await this.nuvexNotify.confirm({
      title: 'Horario Bloqueado',
      subtitle: '¿Deseas eliminar este bloqueo para habilitar el horario en el calendario?',
      message: `El intervalo de ${range} volverá a estar disponible para reservas de clientas.`,
      variant: 'danger',
      icon: 'lock',
      details,
      confirmText: 'Sí, Eliminar Bloqueo',
      cancelText: 'Conservar'
    });

    if (ok) {
      this.deleteBlockedSlot(slot.id, true);
    }
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

  getStatusBadgeClass(status: AppointmentStatus | string): string {
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

  getStatusLabel(status: AppointmentStatus | string): string {
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
