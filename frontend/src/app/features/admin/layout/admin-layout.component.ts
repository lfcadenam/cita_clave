import { Component, computed, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { AdminNavigationService } from '../../../core/services/admin-navigation.service';
import { CustomerAdminService } from '../../../core/services/customer-admin.service';
import { ServiceAdminService } from '../../../core/services/service-admin.service';
import { AppointmentAdminService } from '../../../core/services/appointment-admin.service';
import { WaitlistAdminService } from '../../../core/services/waitlist-admin.service';

@Component({
  selector: 'app-admin-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './admin-layout.component.html',
  styleUrls: ['./admin-layout.component.css']
})
export class AdminLayoutComponent implements OnInit {
  private authService = inject(AuthService);
  private router = inject(Router);
  readonly nav = inject(AdminNavigationService);
  private customerService = inject(CustomerAdminService);
  private serviceAdminService = inject(ServiceAdminService);
  private appointmentService = inject(AppointmentAdminService);
  private waitlistService = inject(WaitlistAdminService);

  readonly currentUser = this.authService.currentUser;
  readonly currentTenant = this.authService.currentTenant;
  readonly isSuperAdmin = this.authService.isSuperAdmin;

  readonly userInitials = computed(() => {
    const name = this.currentUser()?.name?.trim() || 'Paola Camacho';
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  });

  readonly headerTitle = computed(() => {
    switch (this.nav.currentSection()) {
      case 'escritorio': return 'DASHBOARD EJECUTIVO';
      case 'calendario': return 'CALENDARIO DE CITAS';
      case 'citas': return 'CITAS & RESERVAS';
      case 'servicios': return 'CATÁLOGO DE SERVICIOS';
      case 'clientes': return 'CLIENTAS (CRM)';
      case 'horarios': return 'HORARIOS & PAUSAS';
      case 'bloqueos': return 'BLOQUEOS DE HORARIO';
      case 'espera': return 'LISTA DE ESPERA';
      case 'whatsapp': return 'WHATSAPP META CLOUD API';
      case 'plantillas': return 'PLANTILLAS WHATSAPP';
      case 'difusion': return 'LISTAS DE DIFUSIÓN & MARKETING';
      case 'superadmin': return 'SUPERADMINISTRADOR NUVEX';
      default: return 'PANEL DE CONTROL';
    }
  });

  ngOnInit(): void {
    if (this.isSuperAdmin()) {
      if (this.router.url === '/admin' || this.router.url === '/admin/calendario') {
        this.router.navigate(['/admin/superadmin']);
      }
    } else {
      this.loadBadges();
    }
  }

  loadBadges(): void {
    this.customerService.getCustomers().subscribe({
      next: (res) => {
        const list = Array.isArray(res) ? res : (res as any)?.data || [];
        this.nav.updateBadges({ customers: list.length });
      },
      error: () => {}
    });

    this.serviceAdminService.getServices().subscribe({
      next: (res) => {
        const list = Array.isArray(res) ? res : (res as any)?.data || [];
        this.nav.updateBadges({ services: list.length });
      },
      error: () => {}
    });

    this.waitlistService.getWaitlist().subscribe({
      next: (list) => {
        const pendingWaitlist = (list || []).filter(w => w.status === 'PENDING').length;
        this.nav.updateBadges({ waitlist: pendingWaitlist });
      },
      error: () => {}
    });

    this.appointmentService.getAppointments({ status: 'PENDING' }).subscribe({
      next: (res) => {
        const count = res?.meta?.total ?? (res?.data?.length || 0);
        this.nav.updateBadges({ pendingAppointments: count });
      },
      error: () => {}
    });
  }

  toggleSidebar(): void {
    this.nav.toggleSidebar();
  }

  closeSidebar(): void {
    this.nav.closeSidebar();
  }

  toggleUserMenu(): void {
    this.nav.toggleUserMenu();
  }

  closeUserMenu(): void {
    this.nav.closeUserMenu();
  }

  openProfile(): void {
    this.nav.openProfile();
  }

  closeProfile(): void {
    this.nav.closeProfile();
  }

  openNewAppointment(): void {
    this.nav.triggerNewAppointment();
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/admin/login']);
  }
}
