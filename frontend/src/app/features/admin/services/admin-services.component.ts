import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ServiceAdminService, AdminServiceItem } from '../../../core/services/service-admin.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-services',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-services.component.html',
  styleUrls: ['./admin-services.component.css']
})
export class AdminServicesComponent implements OnInit {
  private serviceAdminService = inject(ServiceAdminService);
  private nuvexNotify = inject(NuvexNotificationService);

  readonly servicesTable = signal<AdminServiceItem[]>([]);
  readonly servicesPage = signal<number>(1);
  readonly servicesTotalPages = signal<number>(1);
  readonly servicesTotalItems = signal<number>(0);
  readonly servicesPerPage = 10;
  readonly servicesSearch = signal<string>('');
  readonly isLoadingServices = signal<boolean>(false);
  readonly showServiceModal = signal<boolean>(false);
  readonly editingService = signal<AdminServiceItem | null>(null);

  serviceForm = {
    name: '',
    category: 'Manicura y Pedicura',
    description: '',
    duration_minutes: 60,
    base_price: 35000,
    deposit_amount: 10000,
    requires_deposit: true,
    is_active: true,
    is_favorite: false,
    image_url: ''
  };

  serviceImageFile: File | null = null;
  readonly serviceImagePreview = signal<string | null>(null);
  readonly isSavingService = signal<boolean>(false);

  ngOnInit(): void {
    this.loadCatalogServices(1);
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
      is_favorite: false,
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
      is_favorite: svc.is_favorite ?? false,
      image_url: svc.image_url || ''
    };
    this.showServiceModal.set(true);
  }

  toggleFavorite(event: Event, svc: AdminServiceItem): void {
    event.stopPropagation();
    const prev = svc.is_favorite ?? false;
    svc.is_favorite = !prev;

    this.serviceAdminService.toggleFavorite(svc.id).subscribe({
      next: (res) => {
        svc.is_favorite = res.is_favorite;
        if (svc.is_favorite) {
          this.nuvexNotify.success('Tratamiento Destacado', `"${svc.name}" aparecerá ahora de primero en las listas y en el portal.`);
        } else {
          this.nuvexNotify.info('Favorito Desmarcado', `"${svc.name}" volvió a su posición estándar.`);
        }
        // Recargar el catálogo para refrescar el ordenamiento de favoritos al inicio
        this.loadCatalogServices(this.servicesPage());
      },
      error: (err) => {
        svc.is_favorite = prev;
        this.nuvexNotify.error('Error al actualizar', err?.error?.message || 'No fue posible cambiar el estado favorito.');
      }
    });
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
    formData.append('is_favorite', this.serviceForm.is_favorite ? '1' : '0');

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
        this.nuvexNotify.success('Tratamiento guardado', 'Los datos del servicio fueron almacenados correctamente.');
      },
      error: (err) => {
        this.isSavingService.set(false);
        this.nuvexNotify.error('Error al guardar', err?.error?.message || 'Ocurrió un error al guardar el servicio.');
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
      },
      error: (err) => {
        this.nuvexNotify.error('Error al dar de baja', err?.error?.message || 'No fue posible eliminar el servicio.');
      }
    });
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount);
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
}
