import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { 
  WhatsAppAdminService, 
  OfficialWhatsAppTemplate, 
  TemplateVariableItem 
} from '../../../core/services/whatsapp-admin.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-whatsapp-templates',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-whatsapp-templates.component.html',
  styleUrls: ['./admin-whatsapp-templates.component.css']
})
export class AdminWhatsAppTemplatesComponent implements OnInit {
  private whatsappService = inject(WhatsAppAdminService);
  private nuvexNotify = inject(NuvexNotificationService);

  // Catálogo Oficial de Plantillas y Previsualización
  readonly officialTemplates = signal<OfficialWhatsAppTemplate[]>([]);
  readonly isLoadingTemplatesCatalog = signal<boolean>(false);
  readonly isCreatingMetaTemplate = signal<string | null>(null);
  readonly selectedTemplateForPreview = signal<OfficialWhatsAppTemplate | null>(null);
  readonly templateActionFeedback = signal<{ success: boolean; message: string } | null>(null);

  // Variables Disponibles y Edición de Plantillas
  readonly availableVariables = signal<TemplateVariableItem[]>([]);
  readonly selectedTemplateForEdit = signal<OfficialWhatsAppTemplate | null>(null);
  readonly editTemplateBody = signal<string>('');
  readonly editTemplateSamples = signal<string[]>([]);
  readonly isSavingTemplate = signal<boolean>(false);
  readonly templateEditValidationError = signal<string | null>(null);

  readonly editTemplateLivePreview = computed(() => {
    let preview = this.editTemplateBody();
    const samples = this.editTemplateSamples();
    samples.forEach((sample, idx) => {
      preview = preview.replaceAll(`{{${idx + 1}}}`, sample || `[Variable ${idx + 1}]`);
    });
    return preview;
  });

  readonly templatesSummary = computed(() => {
    const list = this.officialTemplates();
    const approved = list.filter(t => t.meta_status === 'APPROVED').length;
    const pending = list.filter(t => t.meta_status === 'PENDING').length;
    const notCreated = list.filter(t => !t.exists_in_meta || t.meta_status === 'NOT_REGISTERED').length;
    const rejected = list.filter(t => t.meta_status === 'REJECTED').length;
    return {
      total: list.length,
      approved,
      pending,
      notCreated,
      rejected
    };
  });

  readonly templatesFilter = signal<'ALL' | 'APPROVED' | 'PENDING'>('ALL');

  readonly filteredOfficialTemplates = computed(() => {
    const list = this.officialTemplates();
    const f = this.templatesFilter();
    if (f === 'APPROVED') {
      return list.filter(t => t.meta_status === 'APPROVED');
    }
    if (f === 'PENDING') {
      return list.filter(t => t.meta_status !== 'APPROVED');
    }
    return list;
  });

  ngOnInit(): void {
    this.loadTemplatesCatalog();
  }

  loadTemplatesCatalog(): void {
    this.isLoadingTemplatesCatalog.set(true);
    this.templateActionFeedback.set(null);
    this.whatsappService.getTemplatesCatalog().subscribe({
      next: (res) => {
        this.isLoadingTemplatesCatalog.set(false);
        this.officialTemplates.set(res.templates || []);
        if (res.available_variables) {
          this.availableVariables.set(res.available_variables);
        }
      },
      error: (err) => {
        this.isLoadingTemplatesCatalog.set(false);
        console.error('Error al cargar catálogo de plantillas:', err);
      }
    });
  }

  openTemplatePreview(template: OfficialWhatsAppTemplate): void {
    this.selectedTemplateForPreview.set(template);
  }

  closeTemplatePreview(): void {
    this.selectedTemplateForPreview.set(null);
  }

  openTemplateEdit(template: OfficialWhatsAppTemplate): void {
    this.selectedTemplateForEdit.set(template);
    this.editTemplateBody.set(template.body);
    this.editTemplateSamples.set([...(template.samples || [])]);
    this.templateEditValidationError.set(null);
  }

  closeTemplateEdit(): void {
    this.selectedTemplateForEdit.set(null);
    this.templateEditValidationError.set(null);
  }

  insertVariableIntoBody(varItem: TemplateVariableItem): void {
    const currentBody = this.editTemplateBody();
    const matches = currentBody.match(/\{\{(\d+)\}\}/g) || [];
    const nextIndex = matches.length + 1;
    const token = `{{${nextIndex}}}`;

    const newBody = currentBody ? `${currentBody.trimEnd()} ${token}` : token;
    this.editTemplateBody.set(newBody);

    const currentSamples = [...this.editTemplateSamples()];
    currentSamples[nextIndex - 1] = varItem.sample;
    this.editTemplateSamples.set(currentSamples);
    this.validateEditTemplateBody();
  }

  validateEditTemplateBody(): boolean {
    const body = this.editTemplateBody().trim();
    if (!body) {
      this.templateEditValidationError.set('El cuerpo del mensaje no puede estar vacío.');
      return false;
    }

    if (/^\s*\{\{\d+\}\}/.test(body)) {
      this.templateEditValidationError.set('Meta prohíbe que la plantilla inicie con una variable. Debe iniciar con texto fijo (ej: "Hola {{1}}").');
      return false;
    }

    if (/\{\{\d+\}\}\s*$/.test(body)) {
      this.templateEditValidationError.set('Meta prohíbe que la plantilla termine en una variable (Error #2388299). Debes incluir una frase de cierre.');
      return false;
    }

    this.templateEditValidationError.set(null);
    return true;
  }

  saveTemplateEdit(syncToMeta: boolean = false): void {
    const template = this.selectedTemplateForEdit();
    if (!template) return;

    if (!this.validateEditTemplateBody()) {
      return;
    }

    this.isSavingTemplate.set(true);
    this.templateActionFeedback.set(null);

    this.whatsappService.updateTemplate(template.key, {
      body: this.editTemplateBody().trim(),
      samples: this.editTemplateSamples(),
      sync_to_meta: syncToMeta,
    }).subscribe({
      next: (res) => {
        this.isSavingTemplate.set(false);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || 'Plantilla actualizada exitosamente.',
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        this.closeTemplateEdit();
      },
      error: (err) => {
        this.isSavingTemplate.set(false);
        this.templateEditValidationError.set(err?.error?.message || 'Error al guardar la plantilla.');
      }
    });
  }

  async resetTemplateToOriginal(template: OfficialWhatsAppTemplate): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: '¿Restablecer Plantilla Oficial?',
      subtitle: `Se restaurará "${template.display_name}" a su versión estándar`,
      message: 'Las personalizaciones realizadas por el salón para esta plantilla serán reemplazadas por la redacción oficial aprobada.',
      variant: 'warning',
      icon: 'whatsapp',
      confirmText: 'Sí, Restablecer',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isSavingTemplate.set(true);
    this.templateActionFeedback.set(null);

    this.whatsappService.resetTemplate(template.key).subscribe({
      next: (res) => {
        this.isSavingTemplate.set(false);
        this.nuvexNotify.success('Plantilla restablecida', res.message || 'Plantilla restablecida a la versión oficial.');
        this.templateActionFeedback.set({
          success: true,
          message: res.message || 'Plantilla restablecida a la versión oficial.',
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
        if (this.selectedTemplateForEdit()?.key === template.key) {
          this.closeTemplateEdit();
        }
      },
      error: (err) => {
        this.isSavingTemplate.set(false);
        this.nuvexNotify.error('Error al restablecer', err?.error?.message || 'Error al restablecer la plantilla.');
        this.templateActionFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al restablecer la plantilla.',
        });
      }
    });
  }

  createSingleMetaTemplate(template: OfficialWhatsAppTemplate): void {
    this.isCreatingMetaTemplate.set(template.key);
    this.templateActionFeedback.set(null);

    this.whatsappService.createTemplate(template.key).subscribe({
      next: (res) => {
        this.isCreatingMetaTemplate.set(null);
        this.nuvexNotify.success('Plantilla procesada', res.message || `Plantilla "${template.display_name}" procesada en Meta.`);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || `Plantilla "${template.display_name}" procesada en Meta.`
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
      },
      error: (err) => {
        this.isCreatingMetaTemplate.set(null);
        const msg = err?.error?.message || `Error al crear plantilla "${template.name}" en Meta.`;
        this.nuvexNotify.error('Error en Meta', msg);
        this.templateActionFeedback.set({
          success: false,
          message: msg
        });
      }
    });
  }

  async createAllMetaTemplates(): Promise<void> {
    const ok = await this.nuvexNotify.confirm({
      title: 'Registrar Catálogo en Meta',
      subtitle: 'Creación automática masiva en WhatsApp Cloud API',
      message: '¿Deseas enviar las 8 plantillas oficiales a tu cuenta de Meta WhatsApp Cloud API para su registro y aprobación?',
      variant: 'primary',
      icon: 'whatsapp',
      confirmText: 'Crear Todas en Meta',
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isCreatingMetaTemplate.set('ALL');
    this.templateActionFeedback.set(null);

    this.whatsappService.createAllTemplates().subscribe({
      next: (res) => {
        this.isCreatingMetaTemplate.set(null);
        this.templateActionFeedback.set({
          success: res.success,
          message: res.message || 'Proceso de registro masivo de plantillas completado.'
        });
        if (res.catalog) {
          this.officialTemplates.set(res.catalog);
        } else {
          this.loadTemplatesCatalog();
        }
      },
      error: (err) => {
        this.isCreatingMetaTemplate.set(null);
        const msg = err?.error?.message || 'Error al registrar las plantillas en Meta.';
        this.templateActionFeedback.set({
          success: false,
          message: msg
        });
      }
    });
  }
}
