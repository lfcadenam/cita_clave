import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { WaitlistAdminService } from '../../../core/services/waitlist-admin.service';
import { WaitlistItem } from '../../../core/models/admin-waitlist.models';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-waitlist',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-waitlist.component.html',
  styleUrls: ['./admin-waitlist.component.css']
})
export class AdminWaitlistComponent implements OnInit {
  private authService = inject(AuthService);
  private waitlistService = inject(WaitlistAdminService);
  private nuvexNotify = inject(NuvexNotificationService);

  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  readonly waitlist = signal<WaitlistItem[]>([]);
  readonly isLoadingWaitlist = signal<boolean>(false);

  ngOnInit(): void {
    this.loadWaitlist();
  }

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
      confirmText: 'Sí, retirar',
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

  openWhatsAppChat(phone: string, clientName: string, date?: string, time?: string, service?: string): void {
    const cleanPhone = phone.replace(/\D/g, '');
    const fullPhone = cleanPhone.startsWith('57') ? cleanPhone : `57${cleanPhone}`;
    let msg = `¡Hola ${clientName}! Te saludamos desde ${this.salonTitle()}.`;
    if (date && time && service) {
      msg += ` Te escribimos respecto a tu solicitud en lista de espera para ${service} el día ${date} en el horario ${time}. ¡Tenemos un turno disponible para ti!`;
    }
    const url = `https://wa.me/${fullPhone}?text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
  }
}
