import { Injectable, signal } from '@angular/core';
import {
  NuvexToast,
  NuvexNotificationType,
  NuvexConfirmOptions,
  NuvexActiveConfirmDialog
} from '../models/nuvex-notification.models';

@Injectable({
  providedIn: 'root'
})
export class NuvexNotificationService {
  readonly toasts = signal<NuvexToast[]>([]);
  readonly activeConfirm = signal<NuvexActiveConfirmDialog | null>(null);

  /**
   * Muestra un Toast / Notificación flotante de acuerdo al sistema de diseño Nuvex Modern Curve UI.
   */
  show(type: NuvexNotificationType, title: string, message?: string, duration: number = 4000): void {
    const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
    const newToast: NuvexToast = {
      id,
      type,
      title,
      message: message || '',
      duration,
      dismissible: true
    };

    this.toasts.update(list => [...list, newToast]);

    if (duration > 0) {
      setTimeout(() => {
        this.dismiss(id);
      }, duration);
    }
  }

  success(title: string, message?: string, duration: number = 4000): void {
    this.show('success', title, message, duration);
  }

  error(title: string, message?: string, duration: number = 5500): void {
    this.show('error', title, message, duration);
  }

  warning(title: string, message?: string, duration: number = 4500): void {
    this.show('warning', title, message, duration);
  }

  info(title: string, message?: string, duration: number = 4000): void {
    this.show('info', title, message, duration);
  }

  dismiss(id: string): void {
    this.toasts.update(list => list.filter(t => t.id !== id));
  }

  clearAllToasts(): void {
    this.toasts.set([]);
  }

  /**
   * Abre un diálogo modal de confirmación con la estética Nuvex Modern Curve UI.
   * Devuelve una Promise<boolean> que se resuelve en `true` al aceptar o `false` al cancelar.
   */
  confirm(options: NuvexConfirmOptions): Promise<boolean> {
    return new Promise<boolean>((resolve) => {
      const id = 'confirm_' + Date.now();
      this.activeConfirm.set({
        ...options,
        id,
        confirmText: options.confirmText || 'Aceptar',
        cancelText: options.cancelText || 'Cancelar',
        variant: options.variant || 'primary',
        resolve: (result: boolean) => {
          this.activeConfirm.set(null);
          resolve(result);
        }
      });
    });
  }

  /**
   * Cierra el diálogo de confirmación actual con el resultado especificado.
   */
  respondConfirm(result: boolean): void {
    const current = this.activeConfirm();
    if (current) {
      current.resolve(result);
    }
  }
}
