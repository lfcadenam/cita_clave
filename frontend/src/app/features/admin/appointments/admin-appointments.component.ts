import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { AppointmentAdminService, CreateAppointmentPayload } from '../../../core/services/appointment-admin.service';
import { ServiceAdminService, AdminServiceItem } from '../../../core/services/service-admin.service';
import { AdminAppointment, AppointmentStatus } from '../../../core/models/admin-appointment.models';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';
import { AdminNavigationService } from '../../../core/services/admin-navigation.service';

@Component({
  selector: 'app-admin-appointments',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-appointments.component.html',
  styleUrls: ['./admin-appointments.component.css']
})
export class AdminAppointmentsComponent implements OnInit {
  private authService = inject(AuthService);
  private appointmentService = inject(AppointmentAdminService);
  private serviceAdminService = inject(ServiceAdminService);
  private nuvexNotify = inject(NuvexNotificationService);
  private nav = inject(AdminNavigationService);

  readonly currentUser = this.authService.currentUser;
  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  // Tabla completa de Citas
  readonly tableAppointments = signal<AdminAppointment[]>([]);
  readonly tableCitasPage = signal<number>(1);
  readonly tableCitasPerPage = signal<number>(10);
  readonly tableCitasTotalPages = signal<number>(1);
  readonly tableCitasTotalItems = signal<number>(0);
  readonly tableCitasStatusFilter = signal<string>('all');
  readonly tableCitasSearch = signal<string>('');
  readonly isLoadingTableCitas = signal<boolean>(false);

  // Detalle & Edición de Cita Seleccionada
  readonly selectedAppointment = signal<AdminAppointment | null>(null);
  readonly isEditingAppointment = signal<boolean>(false);
  readonly editServiceId = signal<number | null>(null);
  readonly editAppointmentDate = signal<string>('');
  readonly editStartTime = signal<string>('');
  readonly editEndTime = signal<string>('');
  readonly editClientName = signal<string>('');
  readonly editClientPhone = signal<string>('');
  readonly editTotalAmount = signal<number>(0);
  readonly editDepositPaid = signal<number>(0);
  readonly editStatus = signal<string>('CONFIRMED');
  readonly editAdminNotes = signal<string>('');
  readonly isSavingAppointmentEdit = signal<boolean>(false);
  readonly editAppointmentError = signal<string | null>(null);

  readonly servicesList = signal<AdminServiceItem[]>([]);

  ngOnInit(): void {
    this.loadTableAppointments(1);
    this.loadServices();
  }

  loadServices(): void {
    this.serviceAdminService.getServices(true).subscribe({
      next: (svcs) => this.servicesList.set(svcs),
      error: (err) => console.warn('Error al precargar servicios:', err)
    });
  }

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
          this.tableCitasTotalItems.set(res.meta.total || (res.data?.length ?? 0));
        } else {
          this.tableCitasPage.set(page);
          this.tableCitasTotalPages.set(1);
          this.tableCitasTotalItems.set(res.data?.length ?? 0);
        }
        this.isLoadingTableCitas.set(false);
      },
      error: (err) => {
        this.isLoadingTableCitas.set(false);
        console.error('Error al cargar tabla de citas:', err);
      }
    });
  }

  onTableSearchChange(term: string): void {
    this.tableCitasSearch.set(term);
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

  openDetailModal(apt: AdminAppointment): void {
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
      status: this.editStatus() as AppointmentStatus,
      admin_notes: this.editAdminNotes()
    };

    this.appointmentService.updateAppointment(apt.id, payload).subscribe({
      next: (updatedApt) => {
        this.isSavingAppointmentEdit.set(false);
        this.isEditingAppointment.set(false);
        this.selectedAppointment.set(updatedApt);
        this.loadTableAppointments(this.tableCitasPage());
        this.nuvexNotify.success('Cita actualizada', 'Los cambios en la cita fueron guardados exitosamente.');
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

  async approveNequi(apt: AdminAppointment): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Aprobar comprobante Nequi?',
      subtitle: `Comprobante de abono para la cita #${apt.appointment_number}`,
      message: `¿Confirmas que recibiste el abono de ${this.formatCurrency(apt.financials?.deposit_paid || 0)} en tu cuenta de Nequi? La cita pasará a estado CONFIRMADA y se notificará a la clienta.`,
      variant: 'primary',
      icon: 'check',
      confirmText: 'Sí, Aprobar Abono',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.appointmentService.approveNequi(apt.id).subscribe({
      next: (updated: AdminAppointment) => {
        this.nuvexNotify.success('Abono confirmado', 'El comprobante Nequi fue aprobado con éxito.');
        this.selectedAppointment.set(updated);
        this.loadTableAppointments(this.tableCitasPage());
      },
      error: (err: any) => {
        console.error('Error al aprobar Nequi:', err);
        this.nuvexNotify.error('Error al validar comprobante', err?.error?.message || 'Error al validar comprobante.');
      }
    });
  }

  updateStatus(apt: AdminAppointment, newStatus: string): void {
    this.appointmentService.updateStatus(apt.id, newStatus as AppointmentStatus).subscribe({
      next: (updated: AdminAppointment) => {
        this.selectedAppointment.set(updated);
        this.loadTableAppointments(this.tableCitasPage());
        this.nuvexNotify.success('Estado actualizado', `La cita #${apt.appointment_number} ahora está en estado ${newStatus}.`);
      },
      error: (err: any) => {
        console.error('Error al actualizar estado:', err);
        this.nuvexNotify.error('Error al cambiar estado', err?.error?.message || 'No fue posible actualizar el estado.');
      }
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

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount);
  }

  readonly Math = Math;

  updateAppointmentStatus(target: AdminAppointment | number, newStatus: string): void {
    const id = typeof target === 'number' ? target : target.id;
    const num = typeof target === 'number' ? `#${id}` : `#${target.appointment_number}`;
    this.appointmentService.updateStatus(id, newStatus as AppointmentStatus).subscribe({
      next: (updated: AdminAppointment) => {
        this.selectedAppointment.set(updated);
        this.loadTableAppointments(this.tableCitasPage());
        this.nuvexNotify.success('Estado actualizado', `La cita ${num} ahora está en estado ${newStatus}.`);
      },
      error: (err: any) => {
        console.error('Error al actualizar estado:', err);
        this.nuvexNotify.error('Error al cambiar estado', err?.error?.message || 'No fue posible actualizar el estado.');
      }
    });
  }

  getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'CONFIRMED':
        return 'bg-emerald-50 text-emerald-700 border border-emerald-200/80';
      case 'PENDING_VERIFICATION':
        return 'bg-amber-50 text-amber-700 border border-amber-200/80';
      case 'COMPLETED':
        return 'bg-purple-50 text-purple-700 border border-purple-200/80';
      case 'CANCELLED':
        return 'bg-slate-100 text-slate-600 border border-slate-200';
      default:
        return 'bg-slate-100 text-slate-700 border border-slate-200';
    }
  }

  getStatusLabel(status: string): string {
    switch (status) {
      case 'CONFIRMED': return 'Confirmada';
      case 'PENDING_VERIFICATION': return 'Por Verificar';
      case 'COMPLETED': return 'Completada';
      case 'CANCELLED': return 'Cancelada';
      default: return status || 'Agendada';
    }
  }

  getMin(a: number, b: number): number {
    return Math.min(a, b);
  }

  getPageNumbers(current: number, total: number): number[] {
    const pages: number[] = [];
    const maxVisible = 5;
    let start = Math.max(1, current - Math.floor(maxVisible / 2));
    let end = Math.min(total, start + maxVisible - 1);
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }

  openNewAppointmentModal(): void {
    this.nav.triggerNewAppointment();
  }
}
