import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { 
  SuperAdminService, 
  PlatformAnalytics, 
  SuperTenant, 
  SuperUser,
  ReminderSettingsData 
} from '../../../core/services/super-admin.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-superadmin',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-superadmin.component.html',
  styleUrls: ['./admin-superadmin.component.css']
})
export class AdminSuperAdminComponent implements OnInit {
  private authService = inject(AuthService);
  private superAdminService = inject(SuperAdminService);
  private nuvexNotify = inject(NuvexNotificationService);
  private route = inject(ActivatedRoute);

  readonly isSuperAdmin = this.authService.isSuperAdmin;
  readonly activeTab = signal<'all' | 'tenants' | 'users' | 'reminders'>('all');

  readonly superAnalytics = signal<PlatformAnalytics | null>(null);
  readonly superTenants = signal<SuperTenant[]>([]);
  readonly superUsers = signal<SuperUser[]>([]);

  // Estado del Programador de Recordatorios
  readonly reminderSettings = signal<ReminderSettingsData | null>(null);
  readonly reminderTime = signal<string>('10:00');
  readonly isReminderEnabled = signal<boolean>(true);
  readonly isSavingReminder = signal<boolean>(false);

  // Disparo manual
  readonly manualDate = signal<string>('');
  readonly manualForce = signal<boolean>(false);
  readonly isExecutingManual = signal<boolean>(false);
  readonly manualOutput = signal<string | null>(null);

  readonly showTenantModal = signal<boolean>(false);
  tenantForm = {
    name: '',
    slug: '',
    domain: '',
    phone: '',
    email: ''
  };

  readonly showUserModal = signal<boolean>(false);
  readonly editingUser = signal<SuperUser | null>(null);
  userForm = {
    name: '',
    email: '',
    password: '',
    role: 'ADMIN',
    tenant_id: null as number | null
  };

  ngOnInit(): void {
    if (this.isSuperAdmin()) {
      this.loadSuperAdminData();
      this.loadReminderSettings();

      // Fecha de mañana por defecto para el disparador manual
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      this.manualDate.set(tomorrow.toISOString().split('T')[0]);

      this.route.queryParamMap.subscribe(params => {
        const tab = params.get('tab');
        if (tab === 'tenants' || tab === 'users' || tab === 'reminders') {
          this.activeTab.set(tab);
          if (tab === 'reminders') {
            this.loadReminderSettings();
          }
        } else {
          this.activeTab.set('all');
        }
      });
    }
  }

  setActiveTab(tab: 'all' | 'tenants' | 'users' | 'reminders'): void {
    this.activeTab.set(tab);
    if (tab === 'reminders') {
      this.loadReminderSettings();
    }
  }

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
        this.nuvexNotify.success('Salón registrado', 'El nuevo salón inquilino ha sido configurado en Nuvex.');
      },
      error: (err) => {
        this.nuvexNotify.error('Error', err?.error?.message || 'No fue posible crear el salón.');
      }
    });
  }

  toggleTenantStatus(tenant: SuperTenant): void {
    this.superAdminService.updateTenant(tenant.id, { is_active: !tenant.is_active }).subscribe({
      next: () => this.loadSuperAdminData(),
      error: (err) => console.error('Error al alternar estado tenant:', err)
    });
  }

  openCreateUserModal(): void {
    this.editingUser.set(null);
    this.userForm = {
      name: '',
      email: '',
      password: '',
      role: 'ADMIN',
      tenant_id: this.superTenants().length > 0 ? this.superTenants()[0].id : null
    };
    this.showUserModal.set(true);
  }

  openEditUserModal(user: SuperUser): void {
    this.editingUser.set(user);
    this.userForm = {
      name: user.name,
      email: user.email,
      password: '',
      role: user.role,
      tenant_id: user.tenant_id ?? null
    };
    this.showUserModal.set(true);
  }

  closeUserModal(): void {
    this.showUserModal.set(false);
    this.editingUser.set(null);
  }

  saveSuperUser(): void {
    if (!this.userForm.name.trim() || !this.userForm.email.trim()) {
      this.nuvexNotify.warning('Campos incompletos', 'Nombre y correo electrónico son requeridos.');
      return;
    }

    const currentEditing = this.editingUser();

    if (currentEditing) {
      const payload: { name: string; email: string; role: string; tenant_id: number | null; password?: string } = {
        name: this.userForm.name.trim(),
        email: this.userForm.email.trim(),
        role: this.userForm.role,
        tenant_id: this.userForm.tenant_id
      };

      if (this.userForm.password.trim()) {
        if (this.userForm.password.trim().length < 8) {
          this.nuvexNotify.warning('Contraseña muy corta', 'La contraseña debe contener mínimo 8 caracteres.');
          return;
        }
        payload.password = this.userForm.password.trim();
      }

      this.superAdminService.updateUser(currentEditing.id, payload).subscribe({
        next: () => {
          this.closeUserModal();
          this.loadSuperAdminData();
          this.nuvexNotify.success('Usuario actualizado', 'Los datos del usuario han sido actualizados exitosamente.');
        },
        error: (err) => {
          this.nuvexNotify.error('Error al actualizar', err?.error?.message || 'No fue posible actualizar los datos del usuario.');
        }
      });
    } else {
      if (!this.userForm.password.trim()) {
        this.nuvexNotify.warning('Contraseña requerida', 'Por favor ingresa una contraseña temporal para el nuevo usuario.');
        return;
      }

      if (this.userForm.password.trim().length < 8) {
        this.nuvexNotify.warning('Contraseña muy corta', 'La contraseña debe contener mínimo 8 caracteres.');
        return;
      }

      this.superAdminService.createUser(this.userForm).subscribe({
        next: () => {
          this.closeUserModal();
          this.loadSuperAdminData();
          this.nuvexNotify.success('Usuario creado', 'El usuario ha sido registrado en la plataforma.');
        },
        error: (err) => {
          this.nuvexNotify.error('Error', err?.error?.message || 'No fue posible registrar al usuario.');
        }
      });
    }
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

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(amount);
  }

  // =========================================================================
  // GESTIÓN DE HORARIOS Y AUTOMATIZACIONES DE RECORDATORIOS
  // =========================================================================

  loadReminderSettings(): void {
    this.superAdminService.getReminderSettings().subscribe({
      next: (res) => {
        this.reminderSettings.set(res);
        this.reminderTime.set(res.reminder_time || '10:00');
        this.isReminderEnabled.set(res.is_enabled);
      },
      error: (err) => console.error('Error al cargar configuración de recordatorios:', err)
    });
  }

  setTimePreset(time: string): void {
    this.reminderTime.set(time);
  }

  saveReminderSettings(): void {
    const time = this.reminderTime();
    if (!time || !/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(time)) {
      this.nuvexNotify.warning('Formato inválido', 'Debes ingresar una hora en formato HH:mm (ej. 10:00 o 08:30).');
      return;
    }

    this.isSavingReminder.set(true);
    this.superAdminService.updateReminderSettings({
      reminder_time: time,
      is_enabled: this.isReminderEnabled()
    }).subscribe({
      next: (res) => {
        this.isSavingReminder.set(false);
        this.nuvexNotify.success('Horario Actualizado', res.message || `El proceso se ejecutará diariamente a las ${time} (Hora Colombia).`);
        this.loadReminderSettings();
      },
      error: (err) => {
        this.isSavingReminder.set(false);
        this.nuvexNotify.error('Error al guardar', err?.error?.message || 'No fue posible guardar la configuración.');
      }
    });
  }

  triggerManualExecution(): void {
    const date = this.manualDate();
    if (!date) {
      this.nuvexNotify.warning('Fecha requerida', 'Por favor selecciona la fecha de las citas a procesar.');
      return;
    }

    this.isExecutingManual.set(true);
    this.manualOutput.set(null);

    this.superAdminService.triggerManualReminders({
      date,
      force: this.manualForce()
    }).subscribe({
      next: (res) => {
        this.isExecutingManual.set(false);
        this.manualOutput.set(res.output || 'Proceso finalizado correctamente.');
        this.nuvexNotify.success('Recordatorios Ejecutados', res.message || 'Ejecución manual completada con éxito.');
        this.loadReminderSettings();
      },
      error: (err) => {
        this.isExecutingManual.set(false);
        this.manualOutput.set('Error: ' + (err?.error?.message || err.message));
        this.nuvexNotify.error('Error al ejecutar', err?.error?.message || 'Ocurrió un error en la ejecución.');
      }
    });
  }
}
