import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { 
  WhatsAppAdminService, 
  BroadcastAudienceCounts, 
  BroadcastAudienceSample, 
  BroadcastCampaignItem, 
  CreateBroadcastPayload 
} from '../../../core/services/whatsapp-admin.service';
import { NuvexNotificationService } from '../../../core/services/nuvex-notification.service';

@Component({
  selector: 'app-admin-whatsapp-broadcast',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './admin-whatsapp-broadcast.component.html',
  styleUrls: ['./admin-whatsapp-broadcast.component.css']
})
export class AdminWhatsAppBroadcastComponent implements OnInit {
  private authService = inject(AuthService);
  private whatsappService = inject(WhatsAppAdminService);
  private nuvexNotify = inject(NuvexNotificationService);

  readonly currentUser = this.authService.currentUser;
  readonly currentTenant = this.authService.currentTenant;
  readonly salonTitle = computed(() => this.currentTenant()?.name || 'Paola Aguilera');

  // Segmentación y Audiencia
  readonly broadcastSegment = signal<'all' | 'recurring' | 'inactive_30' | 'new'>('all');
  readonly broadcastAudienceCounts = signal<BroadcastAudienceCounts>({ all: 0, recurring: 0, inactive_30: 0, new: 0 });
  readonly broadcastAudienceSample = signal<BroadcastAudienceSample[]>([]);
  readonly isLoadingBroadcastAudience = signal<boolean>(false);

  // Formulario de Campaña
  readonly broadcastName = signal<string>('Promo Especial ' + new Date().toLocaleDateString('es-CO', { month: 'short', day: 'numeric' }));
  readonly broadcastTemplateKey = signal<string>('promocion_salon');
  readonly broadcastPromoText = signal<string>('20% de descuento en todos los servicios de Manicura y Pestañas este fin de semana');
  readonly broadcastTargetUrl = signal<string>('https://labellenailsbog.com');
  readonly broadcastCustomBody = signal<string>('Hola {{1}}, en {{2}} tenemos una promoción especial para ti: {{3}}.\n\nReserva tu cita en el siguiente enlace: {{4}}\n\n¡Esperamos verte pronto!');

  // Envío de Prueba
  readonly broadcastTestPhone = signal<string>('');
  readonly isSendingTestBroadcast = signal<boolean>(false);
  readonly testBroadcastFeedback = signal<{ success: boolean; message: string } | null>(null);

  // Lanzamiento de Campaña
  readonly isLaunchingBroadcast = signal<boolean>(false);
  readonly broadcastLaunchFeedback = signal<{ success: boolean; message: string; campaign?: BroadcastCampaignItem } | null>(null);

  // Historial de Campañas
  readonly broadcastCampaignsList = signal<BroadcastCampaignItem[]>([]);
  readonly isLoadingBroadcastCampaigns = signal<boolean>(false);
  readonly selectedCampaignDetail = signal<BroadcastCampaignItem | null>(null);

  // Previsualización en Vivo de WhatsApp
  readonly broadcastLivePreview = computed(() => {
    const tKey = this.broadcastTemplateKey();
    const salonName = this.salonTitle() || 'La Belle Nails';
    const promo = this.broadcastPromoText() || 'Promoción especial';
    const link = this.broadcastTargetUrl() || 'https://labellenailsbog.com';
    const sampleNickname = 'Cami';

    if (tKey === 'promocion_salon') {
      return `Hola ${sampleNickname}, en ${salonName} tenemos una promoción especial para ti: ${promo}.\n\nReserva tu cita en el siguiente enlace: ${link}\n\n¡Esperamos verte pronto!`;
    } else if (tKey === 'reactivacion_clientas') {
      return `Hola ${sampleNickname}, te extrañamos en ${salonName}. Hace días que no te consentimos y queremos invitarte a renovar tu look.\n\nAgenda tu próxima cita aquí: ${link}\n\n¡Te esperamos con una atención especial!`;
    } else {
      let body = this.broadcastCustomBody();
      body = body.replaceAll('{{1}}', sampleNickname).replaceAll('{{apodo}}', sampleNickname);
      body = body.replaceAll('{{2}}', salonName).replaceAll('{{salon}}', salonName);
      body = body.replaceAll('{{3}}', promo).replaceAll('{{mensaje_promo}}', promo);
      body = body.replaceAll('{{4}}', link).replaceAll('{{link_portal}}', link);
      return body;
    }
  });

  ngOnInit(): void {
    this.loadBroadcastAudience();
    this.loadBroadcastCampaigns();
  }

  loadBroadcastAudience(segment?: string): void {
    const targetSegment = segment || this.broadcastSegment();
    this.isLoadingBroadcastAudience.set(true);
    this.whatsappService.getBroadcastAudience(targetSegment).subscribe({
      next: (res) => {
        this.isLoadingBroadcastAudience.set(false);
        if (res.counts) {
          this.broadcastAudienceCounts.set(res.counts);
        }
        if (res.preview?.sample) {
          this.broadcastAudienceSample.set(res.preview.sample);
        }
      },
      error: (err) => {
        this.isLoadingBroadcastAudience.set(false);
        console.error('Error al cargar audiencia de difusión:', err);
      }
    });
  }

  loadBroadcastCampaigns(): void {
    this.isLoadingBroadcastCampaigns.set(true);
    this.whatsappService.getBroadcastCampaigns().subscribe({
      next: (res) => {
        this.isLoadingBroadcastCampaigns.set(false);
        if (res.campaigns?.data) {
          this.broadcastCampaignsList.set(res.campaigns.data);
        }
      },
      error: (err) => {
        this.isLoadingBroadcastCampaigns.set(false);
        console.error('Error al cargar historial de campañas:', err);
      }
    });
  }

  onSelectBroadcastSegment(segment: 'all' | 'recurring' | 'inactive_30' | 'new'): void {
    this.broadcastSegment.set(segment);
    this.loadBroadcastAudience(segment);
  }

  onSelectBroadcastTemplate(templateKey: string): void {
    this.broadcastTemplateKey.set(templateKey);
  }

  sendTestBroadcastMessage(): void {
    const phone = this.broadcastTestPhone().trim();
    if (!phone) {
      this.testBroadcastFeedback.set({
        success: false,
        message: 'Ingresa un número de WhatsApp con indicativo para recibir la prueba.'
      });
      return;
    }

    this.isSendingTestBroadcast.set(true);
    this.testBroadcastFeedback.set(null);

    this.whatsappService.sendTestBroadcast({
      phone,
      template_key: this.broadcastTemplateKey(),
      promo_text: this.broadcastPromoText(),
      target_url: this.broadcastTargetUrl(),
      message_body: this.broadcastTemplateKey() === 'custom' ? this.broadcastCustomBody() : undefined
    }).subscribe({
      next: (res) => {
        this.isSendingTestBroadcast.set(false);
        this.testBroadcastFeedback.set({
          success: res.success,
          message: res.message || 'Mensaje de prueba enviado con éxito a tu WhatsApp.'
        });
      },
      error: (err) => {
        this.isSendingTestBroadcast.set(false);
        this.testBroadcastFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al enviar mensaje de prueba por WhatsApp.'
        });
      }
    });
  }

  async launchBroadcastCampaign(): Promise<void> {
    const counts = this.broadcastAudienceCounts();
    const seg = this.broadcastSegment();
    const totalCount = counts[seg] || 0;

    if (totalCount === 0) {
      this.nuvexNotify.warning('Sin Destinatarias', 'El segmento seleccionado no contiene clientas disponibles con número telefónico válido.');
      this.broadcastLaunchFeedback.set({
        success: false,
        message: 'El segmento seleccionado no contiene clientas disponibles con número telefónico válido.'
      });
      return;
    }

    const campaignName = this.broadcastName().trim() || 'Difusión Masiva';
    const segLabel = seg === 'all' ? 'Todas las clientas' : (seg === 'recurring' ? 'Clientas VIP (Frecuentes)' : (seg === 'inactive_30' ? 'Inactivas (+30 días)' : 'Nuevas clientas'));

    const ok = await this.nuvexNotify.confirm({
      title: 'Lanzar Campaña Masiva de Difusión',
      subtitle: `Se enviará vía WhatsApp Cloud API a ${totalCount} clientas`,
      message: `¿Confirmas el lanzamiento de la campaña "${campaignName}"? Los mensajes se personalizarán dinámicamente con el apodo de cada clienta.`,
      variant: 'primary',
      icon: 'whatsapp',
      details: [
        { label: 'Campaña', value: campaignName },
        { label: 'Segmento', value: segLabel },
        { label: 'Destinatarias', value: `${totalCount} contactos` }
      ],
      confirmText: `Sí, Disparar Difusión (${totalCount})`,
      cancelText: 'Cancelar'
    });
    if (!ok) return;

    this.isLaunchingBroadcast.set(true);
    this.broadcastLaunchFeedback.set(null);

    const payload: CreateBroadcastPayload = {
      name: campaignName,
      segment: seg,
      template_key: this.broadcastTemplateKey(),
      promo_text: this.broadcastPromoText(),
      target_url: this.broadcastTargetUrl(),
      message_body: this.broadcastLivePreview()
    };

    this.whatsappService.createAndExecuteBroadcast(payload).subscribe({
      next: (res) => {
        this.isLaunchingBroadcast.set(false);
        this.broadcastLaunchFeedback.set({
          success: res.success,
          message: res.message || 'Campaña de difusión completada.',
          campaign: res.campaign
        });
        this.loadBroadcastCampaigns();
      },
      error: (err) => {
        this.isLaunchingBroadcast.set(false);
        this.broadcastLaunchFeedback.set({
          success: false,
          message: err?.error?.message || 'Error al ejecutar la campaña de difusión.'
        });
      }
    });
  }

  viewCampaignDetail(campaign: BroadcastCampaignItem): void {
    this.selectedCampaignDetail.set(campaign);
    this.whatsappService.getBroadcastCampaignDetails(campaign.id).subscribe({
      next: (res) => {
        if (res.campaign) {
          this.selectedCampaignDetail.set(res.campaign);
        }
      },
      error: (err) => console.error('Error al cargar detalle de campaña:', err)
    });
  }

  closeCampaignDetail(): void {
    this.selectedCampaignDetail.set(null);
  }
}
