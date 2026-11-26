import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { 
  CustomerAdminService, 
  AdminCustomer, 
  CustomerAppointmentHistoryItem 
} from '../../../core/services/customer-admin.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-customers',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-customers.component.html',
  styleUrls: ['./admin-customers.component.css']
})
export class AdminCustomersComponent implements OnInit {
  private authService = inject(AuthService);
  private customerService = inject(CustomerAdminService);
  private nuvexNotify = inject(NuvexNotificationService);

  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  readonly crmCustomers = signal<AdminCustomer[]>([]);
  readonly crmPage = signal<number>(1);
  readonly crmTotalPages = signal<number>(1);
  readonly crmTotalItems = signal<number>(0);
  readonly crmPerPage = 10;
  readonly isLoadingCustomers = signal<boolean>(false);
  readonly crmSearch = signal<string>('');
  readonly showCustomerModal = signal<boolean>(false);
  readonly editingCustomer = signal<AdminCustomer | null>(null);
  readonly isSavingCustomer = signal<boolean>(false);
  readonly customerFormError = signal<string | null>(null);

  // Modal para Eliminar / Desactivar clienta
  readonly showDeleteCustomerModal = signal<boolean>(false);
  readonly customerToDelete = signal<AdminCustomer | null>(null);
  readonly isDeletingCustomer = signal<boolean>(false);

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
  readonly showCustomerAppointmentsModal = signal<boolean>(false);
  readonly selectedCustomerForAppointments = signal<AdminCustomer | null>(null);
  readonly customerAppointmentsList = signal<CustomerAppointmentHistoryItem[]>([]);
  readonly isLoadingCustomerAppointments = signal<boolean>(false);

  ngOnInit(): void {
    this.loadCrmCustomers(1);
  }

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
        next: (updatedCust) => {
          this.isSavingCustomer.set(false);
          const name = updatedCust?.name || this.customerForm.name || 'Clienta';
          this.closeCustomerModal();
          this.nuvexNotify.success('Clienta actualizada', `Los datos de "${name}" se guardaron de forma correcta.`);
          this.loadCrmCustomers(this.crmPage());
        },
        error: (err) => {
          this.isSavingCustomer.set(false);
          let msg = err.error?.message;
          if (!msg) {
            if (err.status === 502 || err.status === 503 || err.status === 504 || (typeof err.error === 'string' && err.error.includes('<html'))) {
              msg = 'El servidor se estaba reiniciando. Por favor presiona "Guardar" de nuevo.';
            } else {
              msg = 'Error al actualizar los datos de la clienta.';
            }
          }
          this.customerFormError.set(msg);
          this.nuvexNotify.error('Error al guardar', msg);
          console.error('Error al actualizar clienta:', err);
        }
      });
    } else {
      this.customerService.createCustomer(payload).subscribe({
        next: (newCust) => {
          this.isSavingCustomer.set(false);
          const name = newCust?.name || this.customerForm.name || 'Clienta';
          this.closeCustomerModal();
          this.nuvexNotify.success('Clienta registrada', `"${name}" se guardó de forma correcta.`);
          this.loadCrmCustomers(1);
        },
        error: (err) => {
          this.isSavingCustomer.set(false);
          let msg = err.error?.message;
          if (!msg) {
            if (err.status === 502 || err.status === 503 || err.status === 504 || (typeof err.error === 'string' && err.error.includes('<html'))) {
              msg = 'El servidor se estaba reiniciando. Por favor presiona "Guardar" de nuevo.';
            } else {
              msg = 'Error al registrar la nueva clienta.';
            }
          }
          this.customerFormError.set(msg);
          this.nuvexNotify.error('Error al registrar', msg);
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
}
