import { Injectable, signal, inject } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs';
import { AuthService } from './auth.service';

export type AdminSection =
  | 'escritorio' 
  | 'calendario' 
  | 'citas' 
  | 'servicios' 
  | 'clientes' 
  | 'horarios' 
  | 'bloqueos'
  | 'espera' 
  | 'whatsapp' 
  | 'plantillas'
  | 'difusion'
  | 'superadmin';

export interface AdminBadgeCounts {
  pendingAppointments: number;
  customers: number;
  services: number;
  waitlist: number;
}

@Injectable({
  providedIn: 'root'
})
export class AdminNavigationService {
  private router = inject(Router);
  private authService = inject(AuthService);

  readonly sidebarOpen = signal<boolean>(false);
  readonly userMenuOpen = signal<boolean>(false);
  readonly showProfileModal = signal<boolean>(false);
  readonly selectedPeriod = signal<'semana' | 'mes' | 'trimestre' | 'semestre'>('mes');
  readonly openNewAppointmentModalTrigger = signal<number>(0);

  readonly badgeCounts = signal<AdminBadgeCounts>({
    pendingAppointments: 0,
    customers: 71,
    services: 17,
    waitlist: 0
  });

  readonly currentSection = signal<AdminSection>('calendario');

  constructor() {
    this.syncSectionFromUrl(this.router.url);
    this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      this.syncSectionFromUrl(event.urlAfterRedirects || event.url);
    });
  }

  private syncSectionFromUrl(url: string): void {
    const segments = url.split('/').filter(Boolean);
    const lastSegment = segments[segments.length - 1] as AdminSection;
    const validSections: AdminSection[] = [
      'escritorio', 'calendario', 'citas', 'servicios', 'clientes',
      'horarios', 'bloqueos', 'espera', 'whatsapp', 'plantillas', 'difusion', 'superadmin'
    ];

    if (validSections.includes(lastSegment)) {
      this.currentSection.set(lastSegment);
    }
  }

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

  openProfile(): void {
    this.closeUserMenu();
    this.showProfileModal.set(true);
  }

  closeProfile(): void {
    this.showProfileModal.set(false);
  }

  setPeriod(period: 'semana' | 'mes' | 'trimestre' | 'semestre'): void {
    this.selectedPeriod.set(period);
  }

  triggerNewAppointment(): void {
    this.openNewAppointmentModalTrigger.update(n => n + 1);
  }

  updateBadges(counts: Partial<AdminBadgeCounts>): void {
    this.badgeCounts.update(b => ({ ...b, ...counts }));
  }
}
