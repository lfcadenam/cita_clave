import { Component, inject, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';
import { NuvexToast, NuvexActiveConfirmDialog } from '../../../core/models/nuvex-notification.models';

@Component({
  selector: 'app-nuvex-notification-container',
  standalone: true,
  imports: [CommonModule],
  template: `
    <!-- ===================================================================== -->
    <!-- 1. CONTENEDOR FLOTANTE DE TOASTS (NUVEX MODERN CURVE UI)              -->
    <!-- ===================================================================== -->
    <aside 
      class="fixed top-5 right-5 z-[10000] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0"
      aria-live="polite"
      aria-label="Notificaciones del sistema"
    >
      @for (toast of notify.toasts(); track toast.id) {
        <div 
          class="pointer-events-auto flex items-start gap-3 p-4 rounded-[20px] bg-white/95 dark:bg-[#1E293B]/95 backdrop-blur-md border border-slate-200/90 dark:border-slate-700/80 shadow-[0_12px_32px_-4px_rgba(0,0,0,0.12),0_4px_16px_-2px_rgba(0,0,0,0.05)] transition-all animate-in slide-in-from-top-3 fade-in duration-200 select-none"
          role="status"
        >
          <!-- Icono sobrio en cápsula circular según tipo -->
          <div 
            class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
            [ngClass]="{
              'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400': toast.type === 'success',
              'bg-rose-50 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400': toast.type === 'error',
              'bg-amber-50 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400': toast.type === 'warning',
              'bg-sky-50 text-sky-600 dark:bg-sky-500/20 dark:text-sky-400': toast.type === 'info',
              'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300': toast.type === 'default'
            }"
          >
            @if (toast.type === 'success') {
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M16.5 6l-8 8.5L4.5 10.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            } @else if (toast.type === 'error') {
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="10" cy="10" r="8"/>
                <path d="M12.5 7.5l-5 5M7.5 7.5l5 5" stroke-linecap="round"/>
              </svg>
            } @else if (toast.type === 'warning') {
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2">
                <path d="M10 3L2 17h16L10 3z" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M10 8v4M10 14.5v.5" stroke-linecap="round"/>
              </svg>
            } @else if (toast.type === 'info') {
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.2">
                <circle cx="10" cy="10" r="8"/>
                <path d="M10 9v5M10 6.5v.5" stroke-linecap="round"/>
              </svg>
            } @else {
              <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="10" cy="10" r="7"/>
              </svg>
            }
          </div>

          <!-- Contenido textual -->
          <div class="flex-1 min-w-0 pr-1">
            @if (toast.title) {
              <h5 class="text-xs font-black text-slate-900 dark:text-white tracking-tight leading-tight mb-0.5">
                {{ toast.title }}
              </h5>
            }
            @if (toast.message) {
              <p class="text-[11.5px] text-slate-600 dark:text-slate-300 font-medium leading-relaxed m-0 break-words">
                {{ toast.message }}
              </p>
            }
          </div>

          <!-- Botón de cerrar discreto -->
          <button 
            type="button" 
            (click)="notify.dismiss(toast.id)"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-full hover:bg-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer shrink-0"
            title="Cerrar notificación"
          >
            <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4l8 8M12 4l-8 8" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      }
    </aside>

    <!-- ===================================================================== -->
    <!-- 2. DIÁLOGO MODAL DE CONFIRMACIÓN (NUVEX MODERN CURVE UI)              -->
    <!-- ===================================================================== -->
    @if (notify.activeConfirm(); as dialog) {
      <div 
        class="fixed inset-0 z-[9999] bg-slate-950/65 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 animate-in fade-in duration-150"
        role="dialog"
        aria-modal="true"
        [attr.aria-labelledby]="'nuvex-confirm-title'"
      >
        <div 
          class="w-full max-w-md bg-white dark:bg-[#1E293B] rounded-[32px] border border-slate-200/90 dark:border-slate-700/80 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.35)] p-6 sm:p-7 flex flex-col gap-4 animate-in zoom-in-95 duration-150"
          (click)="$event.stopPropagation()"
        >
          <!-- Encabezado con Icono Badge y Títulos -->
          <div class="flex items-start gap-4">
            <!-- Icono circular en caja redondeada suave -->
            <div 
              class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-2xs"
              [ngClass]="{
                'bg-rose-50 text-rose-600 border border-rose-200/80 dark:bg-rose-500/20 dark:text-rose-400 dark:border-rose-500/30': dialog.variant === 'danger',
                'bg-amber-50 text-amber-600 border border-amber-200/80 dark:bg-amber-500/20 dark:text-amber-400 dark:border-amber-500/30': dialog.variant === 'warning',
                'bg-emerald-50 text-emerald-600 border border-emerald-200/80 dark:bg-emerald-500/20 dark:text-emerald-400 dark:border-emerald-500/30': dialog.variant === 'primary',
                'bg-sky-50 text-sky-600 border border-sky-200/80 dark:bg-sky-500/20 dark:text-sky-400 dark:border-sky-500/30': dialog.variant === 'info'
              }"
            >
              @if (dialog.icon === 'trash' || dialog.variant === 'danger') {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              } @else if (dialog.icon === 'calendar') {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              } @else if (dialog.icon === 'lock') {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              } @else if (dialog.icon === 'whatsapp') {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
              } @else if (dialog.icon === 'clock') {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="9"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                </svg>
              } @else {
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
              }
            </div>

            <!-- Título y Subtítulo -->
            <div class="min-w-0 flex-1">
              <h3 id="nuvex-confirm-title" class="text-base font-black text-slate-900 dark:text-white tracking-tight leading-snug m-0">
                {{ dialog.title }}
              </h3>
              @if (dialog.subtitle) {
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-1 mb-0 leading-relaxed">
                  {{ dialog.subtitle }}
                </p>
              }
            </div>
          </div>

          <!-- Mensaje Principal descriptivo -->
          @if (dialog.message) {
            <p class="text-xs text-slate-600 dark:text-slate-300 font-normal leading-relaxed m-0">
              {{ dialog.message }}
            </p>
          }

          <!-- Bloque de Detalles Estructurados (si aplican) -->
          @if (dialog.details && dialog.details.length > 0) {
            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 p-3.5 flex flex-col gap-2">
              @for (item of dialog.details; track item.label) {
                <div class="flex items-center justify-between text-xs gap-3">
                  <span class="text-slate-500 dark:text-slate-400 font-medium shrink-0">{{ item.label }}:</span>
                  <span class="font-bold text-slate-800 dark:text-slate-100 text-right truncate">{{ item.value }}</span>
                </div>
              }
            </div>
          }

          <!-- Acciones en Píldora (Cancelar / Confirmar) -->
          <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100 dark:border-slate-800/80">
            <button 
              type="button"
              (click)="notify.respondConfirm(false)"
              class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-600 hover:text-slate-800 dark:text-slate-300 dark:hover:text-white bg-slate-100 hover:bg-slate-200/80 dark:bg-slate-800 dark:hover:bg-slate-700 transition-all cursor-pointer"
            >
              {{ dialog.cancelText }}
            </button>

            <button 
              type="button"
              (click)="notify.respondConfirm(true)"
              class="px-6 py-2.5 rounded-full text-xs font-extrabold text-white transition-all cursor-pointer shadow-md active:scale-[0.98]"
              [ngClass]="{
                'bg-rose-600 hover:bg-rose-700 shadow-rose-600/25': dialog.variant === 'danger',
                'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25': dialog.variant === 'warning',
                'bg-[#00C975] hover:bg-[#00b067] shadow-emerald-600/25': dialog.variant === 'primary',
                'bg-sky-600 hover:bg-sky-700 shadow-sky-600/25': dialog.variant === 'info'
              }"
            >
              {{ dialog.confirmText }}
            </button>
          </div>
        </div>
      </div>
    }
  `
})
export class NuvexNotificationContainerComponent {
  readonly notify = inject(NuvexNotificationService);

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (this.notify.activeConfirm()) {
      this.notify.respondConfirm(false);
    }
  }

  @HostListener('document:keydown.enter')
  onEnter(): void {
    if (this.notify.activeConfirm()) {
      this.notify.respondConfirm(true);
    }
  }
}
