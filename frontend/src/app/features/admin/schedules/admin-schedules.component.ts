import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, ActivatedRoute } from '@angular/router';
import { WorkingScheduleService, WorkingSchedule, BlockedSlot } from '../../../core/services/working-schedule.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-schedules',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-schedules.component.html',
  styleUrls: ['./admin-schedules.component.css']
})
export class AdminSchedulesComponent implements OnInit {
  private scheduleService = inject(WorkingScheduleService);
  private nuvexNotify = inject(NuvexNotificationService);
  private route = inject(ActivatedRoute);

  readonly schedulesTab = signal<'weekly' | 'blocks'>('weekly');
  readonly weeklySchedules = signal<WorkingSchedule[]>([]);
  readonly blockedSlots = signal<BlockedSlot[]>([]);
  readonly isSavingSchedules = signal<boolean>(false);
  readonly showBlockModal = signal<boolean>(false);

  blockForm = {
    title: '',
    blocked_date: '',
    start_time: '13:00',
    end_time: '14:00',
    is_recurring: true,
    reason: 'Pausa para almuerzo'
  };

  ngOnInit(): void {
    // Check if the current route is 'bloqueos'
    this.route.url.subscribe(segments => {
      const path = segments.map(s => s.path).join('/');
      if (path.includes('bloqueo')) {
        this.schedulesTab.set('blocks');
      } else {
        this.schedulesTab.set('weekly');
      }
    });

    this.loadWeeklySchedules();
    this.loadBlockedSlots();
  }

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
        this.nuvexNotify.error('Error al actualizar', err?.error?.message || 'Error al guardar horarios.');
      }
    });
  }

  loadBlockedSlots(): void {
    this.scheduleService.getBlockedSlots().subscribe({
      next: (res) => this.blockedSlots.set(res),
      error: (err) => console.error('Error al cargar bloqueos:', err)
    });
  }

  openCreateBlockModal(dateStr?: string): void {
    this.blockForm = {
      title: '',
      blocked_date: dateStr || new Date().toISOString().split('T')[0],
      start_time: '13:00',
      end_time: '14:00',
      is_recurring: false,
      reason: ''
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
      },
      error: (err) => {
        this.nuvexNotify.error('Error al bloquear', err?.error?.message || 'No fue posible guardar el bloqueo de horario.');
      }
    });
  }

  async deleteBlockedSlot(id: number): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Liberar esta franja de horario?',
      subtitle: 'El bloqueo será eliminado y el horario quedará habilitado para citas',
      variant: 'warning',
      icon: 'clock',
      confirmText: 'Sí, liberar',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.scheduleService.deleteBlockedSlot(id).subscribe({
      next: () => {
        this.nuvexNotify.success('Bloqueo eliminado', 'El horario ha sido liberado en el calendario.');
        this.loadBlockedSlots();
      },
      error: (err) => {
        this.nuvexNotify.error('Error al eliminar bloqueo', err?.error?.message || 'No se pudo retirar el bloqueo.');
      }
    });
  }
}
