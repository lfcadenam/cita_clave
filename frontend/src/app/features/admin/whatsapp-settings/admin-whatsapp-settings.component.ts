import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { 
  WhatsAppAdminService, 
  WhatsAppSettings, 
  WhatsAppAccountStatus, 
  WhatsAppTemplate, 
  WhatsAppTestResponse 
} from '../../../core/services/whatsapp-admin.service';
import { AuthService } from '../../../core/services/auth.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-whatsapp-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-whatsapp-settings.component.html',
  styleUrls: ['./admin-whatsapp-settings.component.css']
})
export class AdminWhatsAppSettingsComponent implements OnInit {
  private whatsappService = inject(WhatsAppAdminService);
  private authService = inject(AuthService);
  private nuvexNotify = inject(NuvexNotificationService);

  readonly salonTitle = computed(() => this.authService.currentTenant()?.name || 'Paola Aguilera');
  readonly whatsAppSettings = signal<WhatsAppSettings | null>(null);
  readonly whatsAppAccountStatus = signal<WhatsAppAccountStatus | null>(null);
  readonly isCheckingWhatsAppStatus = signal<boolean>(false);
  readonly isSavingWhatsApp = signal<boolean>(false);
  readonly isRegisteringPin = signal<boolean>(false);
  readonly metaPinCode = signal<string>('123456');
  readonly isSyncingTemplates = signal<boolean>(false);
  readonly wabaTemplates = signal<WhatsAppTemplate[]>([]);
  readonly hasSyncedTemplates = signal<boolean>(false);
  readonly whatsAppTestPhone = signal<string>('');
  readonly whatsAppTestMode = signal<'template' | 'text'>('template');
  readonly whatsAppTestTemplateName = signal<string>('reserva_confirmada');
  readonly whatsAppTestTemplateLang = signal<string>('es_CO');
  readonly whatsAppTestCustomMessage = signal<string>('');
  readonly whatsAppTestResult = signal<WhatsAppTestResponse | null>(null);
  readonly isSendingWhatsAppTest = signal<boolean>(false);

  whatsAppForm = {
    whatsapp_driver: 'meta',
    whatsapp_number: '',
    meta_phone_number_id: '',
    meta_waba_id: '',
    meta_access_token: '',
    meta_verify_token: 'nuvex_meta_verify_2026'
  };

  ngOnInit(): void {
    this.loadWhatsAppSettings();
  }

  loadWhatsAppSettings(): void {
    this.whatsappService.getSettings().subscribe({
      next: (res) => {
        this.whatsAppSettings.set(res);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.whatsAppForm = {
          whatsapp_driver: res.whatsapp_driver || 'meta',
          whatsapp_number: res.whatsapp_number || '',
          meta_phone_number_id: res.meta_phone_number_id || '',
          meta_waba_id: res.meta_waba_id || '',
          meta_access_token: res.meta_access_token || '',
          meta_verify_token: res.meta_verify_token || 'nuvex_meta_verify_2026'
        };
        if (!this.whatsAppTestPhone().trim()) {
          const defaultPhone = (res.whatsapp_number || '').replace(/\D/g, '');
          this.whatsAppTestPhone.set(defaultPhone || '3106080402');
        }
      },
      error: (err) => console.error('Error al cargar WhatsApp settings:', err)
    });
  }

  checkWhatsAppStatus(): void {
    this.isCheckingWhatsAppStatus.set(true);
    this.whatsappService.checkStatus().subscribe({
      next: (status) => {
        this.whatsAppAccountStatus.set(status);
        this.isCheckingWhatsAppStatus.set(false);
      },
      error: (err) => {
        this.isCheckingWhatsAppStatus.set(false);
        this.whatsAppAccountStatus.set({
          connected: false,
          state: 'unreachable',
          message: err?.error?.message || 'Error al conectar con la API de WhatsApp.'
        });
      }
    });
  }

  saveWhatsAppSettings(): void {
    this.isSavingWhatsApp.set(true);
    const payload: Partial<WhatsAppSettings> = {
      whatsapp_driver: this.whatsAppForm.whatsapp_driver,
      whatsapp_number: this.whatsAppForm.whatsapp_number,
      meta_phone_number_id: this.whatsAppForm.meta_phone_number_id,
      meta_waba_id: this.whatsAppForm.meta_waba_id,
      meta_access_token: this.whatsAppForm.meta_access_token.trim(),
      meta_verify_token: this.whatsAppForm.meta_verify_token
    };

    this.whatsappService.updateSettings(payload).subscribe({
      next: (res) => {
        this.whatsAppSettings.set(res.data);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.isSavingWhatsApp.set(false);
        this.nuvexNotify.success('Configuración Guardada', 'La configuración de WhatsApp Meta Cloud API ha sido actualizada.');
      },
      error: (err) => {
        this.isSavingWhatsApp.set(false);
        this.nuvexNotify.error('Error al Guardar', err?.error?.message || 'No se pudo guardar la configuración de WhatsApp.');
        console.error('Error al guardar configuración WhatsApp:', err);
      }
    });
  }

  registerMetaPin(): void {
    const pin = this.metaPinCode().trim();
    if (!/^\d{6}$/.test(pin)) {
      this.nuvexNotify.warning('PIN Inválido', 'El PIN debe contener exactamente 6 dígitos numéricos (ej: 123456).');
      return;
    }

    this.isRegisteringPin.set(true);
    this.whatsappService.registerPin(pin, {
      meta_phone_number_id: this.whatsAppForm.meta_phone_number_id,
      meta_access_token: this.whatsAppForm.meta_access_token
    }).subscribe({
      next: (res) => {
        this.isRegisteringPin.set(false);
        this.nuvexNotify.success('PIN Registrado', 'El número de WhatsApp fue registrado exitosamente en Meta.');
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
        this.checkWhatsAppStatus();
      },
      error: (err) => {
        this.isRegisteringPin.set(false);
        this.nuvexNotify.error('Error al registrar PIN', err?.error?.message || 'Error al registrar el número con PIN en Meta.');
      }
    });
  }

  syncWhatsAppTemplates(): void {
    this.isSyncingTemplates.set(true);
    this.whatsappService.syncTemplates().subscribe({
      next: (res) => {
        this.isSyncingTemplates.set(false);
        this.wabaTemplates.set(res.templates || []);
        this.hasSyncedTemplates.set(true);
      },
      error: (err) => {
        this.isSyncingTemplates.set(false);
        this.wabaTemplates.set([]);
        console.error('Error al sincronizar plantillas de Meta:', err);
      }
    });
  }

  selectWhatsAppTemplate(name: string, lang: string): void {
    this.whatsAppTestTemplateName.set(name);
    this.whatsAppTestTemplateLang.set(lang);
  }

  sendWhatsAppTest(): void {
    const phone = this.whatsAppTestPhone().trim();
    if (!phone) {
      this.nuvexNotify.warning('Número Requerido', 'Ingresa un número de celular de destino con indicativo.');
      return;
    }

    this.isSendingWhatsAppTest.set(true);
    this.whatsAppTestResult.set(null);

    const payload = {
      phone,
      test_mode: this.whatsAppTestMode(),
      template_name: this.whatsAppTestTemplateName(),
      template_lang: this.whatsAppTestTemplateLang(),
      message: this.whatsAppTestCustomMessage().trim()
    };

    this.whatsappService.sendTest(payload).subscribe({
      next: (res) => {
        this.isSendingWhatsAppTest.set(false);
        this.whatsAppTestResult.set(res);
        if (res.account_status) {
          this.whatsAppAccountStatus.set(res.account_status);
        }
      },
      error: (err) => {
        this.isSendingWhatsAppTest.set(false);
        const errData = err?.error || {};
        this.whatsAppTestResult.set({
          success: false,
          message: errData.message || 'Error al enviar mensaje de prueba.',
          error_code: errData.error_code,
          has_pin_error: errData.has_pin_error || (errData.message && errData.message.includes('133010')),
          account_status: errData.account_status
        });
        if (errData.account_status) {
          this.whatsAppAccountStatus.set(errData.account_status);
        }
      }
    });
  }
}
