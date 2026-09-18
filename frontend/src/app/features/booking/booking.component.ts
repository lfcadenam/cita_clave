import { Component, OnInit, signal, computed, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ApiService } from '../../core/services/api.service';
import { StorageService } from '../../core/services/storage.service';
import { Service, AvailableSlot, ClientProfile, AppointmentResponse } from '../../core/models/booking.models';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-booking',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  templateUrl: './booking.component.html',
  styleUrls: ['./booking.component.css']
})
export class BookingComponent implements OnInit {
  private api = inject(ApiService);
  private storage = inject(StorageService);
  private fb = inject(FormBuilder);

  // Stepper State: 1: Catálogo, 2: Fecha y Horario, 3: Datos y Abono, 4: Pase VIP
  currentStep = signal<number>(1);
  isLoading = signal<boolean>(false);
  isSubmitting = signal<boolean>(false);
  errorMessage = signal<string | null>(null);

  // Search & Filter
  searchQuery = signal<string>('');
  selectedCategory = signal<string>('ALL');

  // Services State
  services = signal<Service[]>([]);
  selectedService = signal<Service | null>(null);
  detailService = signal<Service | null>(null);

  // Category Filter Tabs
  categories = [
    { key: 'ALL', label: 'Todos' },
    { key: 'PESTANAS_CEJAS', label: 'Pestañas & Cejas' },
    { key: 'FACIAL', label: 'Cuidado Facial' },
    { key: 'LABIOS', label: 'Labios' },
    { key: 'CORPORAL_MASAJES', label: 'Masajes & Spa' },
    { key: 'DEPILACION', label: 'Depilación' }
  ];

  // Slots State
  selectedDate = signal<string>('');
  availableSlots = signal<AvailableSlot[]>([]);
  selectedSlot = signal<AvailableSlot | null>(null);
  loadingSlots = signal<boolean>(false);

  // Client Profile & Payment State
  clientForm!: FormGroup;
  isExistingClient = signal<boolean>(false);
  existingClientName = signal<string>('');
  copiedNequi = signal<boolean>(false);
  paymentMethod = signal<'NEQUI' | 'BOLD'>('NEQUI');
  receiptFile: File | null = null;
  receiptPreview = signal<string | null>(null);
  nequiInfo = signal<{ account_number: string; account_holder: string; instructions: string }>({
    account_number: environment.nequiNumber,
    account_holder: 'Paola Andrea Aguilera Camacho',
    instructions: 'Transfiere el anticipo a Nequi y adjunta el comprobante.'
  });

  // Result & Mobile Summary
  bookingResult = signal<AppointmentResponse['data'] | null>(null);
  summaryVisible = signal<boolean>(false);

  toggleSummary(): void {
    this.summaryVisible.update(v => !v);
  }

  // Computed: Filtered Services
  filteredServices = computed(() => {
    let list = this.services();
    const cat = this.selectedCategory();
    const q = this.searchQuery().toLowerCase().trim();

    if (cat !== 'ALL') {
      list = list.filter(s => {
        const c = (s.category || '').toUpperCase();
        return c.includes(cat) || (s.name || '').toUpperCase().includes(cat);
      });
    }

    if (q) {
      list = list.filter(s => s.name.toLowerCase().includes(q) || (s.description || '').toLowerCase().includes(q));
    }

    return list;
  });

  formattedDateDisplay = computed(() => {
    const dateStr = this.selectedDate();
    if (!dateStr) return '';
    try {
      const [year, month, day] = dateStr.split('-').map(Number);
      const date = new Date(year, month - 1, day);
      return new Intl.DateTimeFormat('es-CO', { 
        weekday: 'long', 
        day: 'numeric', 
        month: 'long',
        year: 'numeric'
      }).format(date);
    } catch {
      return dateStr;
    }
  });

  getCategoryLabel(key: string): string {
    const cat = this.categories.find(c => c.key === key);
    return cat ? cat.label : key;
  }

  ngOnInit(): void {
    this.initForm();
    this.initDefaultDate();
    this.loadServices();
    this.loadNequiInfo();
    this.loadSavedDeviceProfile();
  }

  private initForm(): void {
    this.clientForm = this.fb.group({
      name: ['', [Validators.required, Validators.minLength(3)]],
      phone: ['', [Validators.required, Validators.minLength(10)]],
      email: ['', [Validators.email]],
      notes: ['']
    });

    this.clientForm.get('phone')?.valueChanges.subscribe(val => {
      if (!val) return;
      const clean = val.replace(/\D/g, '');
      if (clean.length === 10) {
        this.lookupClientData(clean);
      }
    });
  }

  private initDefaultDate(): void {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const yyyy = tomorrow.getFullYear();
    const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const dd = String(tomorrow.getDate()).padStart(2, '0');
    this.selectedDate.set(`${yyyy}-${mm}-${dd}`);
  }

  loadServices(): void {
    this.isLoading.set(true);
    this.api.getServices().subscribe({
      next: (data: Service[]) => {
        this.services.set(data);
        this.isLoading.set(false);
      },
      error: () => this.isLoading.set(false)
    });
  }

  loadNequiInfo(): void {
    this.api.getNequiInfo().subscribe((info: { account_number: string; account_holder: string; instructions: string }) => {
      this.nequiInfo.set(info);
    });
  }

  loadSavedDeviceProfile(): void {
    const profile = this.storage.getSavedProfile();
    if (profile && profile.phone) {
      this.clientForm.patchValue({
        name: profile.name || '',
        phone: this.formatPhone(profile.phone),
        email: profile.email || '',
        notes: profile.notes || ''
      }, { emitEvent: false });
    }
  }

  lookupClientData(phone: string): void {
    this.api.lookupClient(phone).subscribe((profile: ClientProfile | null) => {
      if (profile && profile.name) {
        this.isExistingClient.set(true);
        this.existingClientName.set(profile.name);
        this.clientForm.patchValue({
          name: profile.name,
          email: profile.email || this.clientForm.get('email')?.value || ''
        }, { emitEvent: false });
      } else {
        this.isExistingClient.set(false);
      }
    });
  }

  selectService(svc: Service): void {
    this.selectedService.set(svc);
    this.selectedSlot.set(null);
    this.currentStep.set(2);
    this.loadSlotsForCurrentSelection();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  openServiceDetail(svc: Service, event?: MouseEvent): void {
    if (event) {
      event.stopPropagation();
    }
    this.detailService.set(svc);
  }

  closeServiceDetail(): void {
    this.detailService.set(null);
  }

  confirmServiceFromDetail(svc: Service): void {
    this.selectService(svc);
    this.closeServiceDetail();
  }

  onDateChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.selectedDate.set(input.value);
    this.loadSlotsForCurrentSelection();
  }

  loadSlotsForCurrentSelection(): void {
    const svc = this.selectedService();
    const date = this.selectedDate();
    if (!svc || !date) return;

    this.loadingSlots.set(true);
    this.availableSlots.set([]);
    this.selectedSlot.set(null);

    this.api.getAvailableSlots(svc.id, date).subscribe({
      next: (slots: AvailableSlot[]) => {
        if (slots && slots.length > 0) {
          const singleSlot = slots[0];
          this.availableSlots.set([singleSlot]);
          this.selectedSlot.set(singleSlot);
        } else {
          this.availableSlots.set([]);
          this.selectedSlot.set(null);
        }
        this.loadingSlots.set(false);
      },
      error: () => {
        this.loadingSlots.set(false);
        this.availableSlots.set([]);
      }
    });
  }

  selectSlot(slot: AvailableSlot): void {
    this.selectedSlot.set(slot);
  }

  formatPhoneInput(event: Event): void {
    const input = event.target as HTMLInputElement;
    const clean = input.value.replace(/\D/g, '').substring(0, 10);
    input.value = this.formatPhone(clean);
    this.clientForm.get('phone')?.setValue(clean, { emitEvent: true });
  }

  formatPhone(phone: string): string {
    const clean = phone.replace(/\D/g, '');
    if (clean.length <= 3) return clean;
    if (clean.length <= 6) return `${clean.substring(0, 3)} ${clean.substring(3)}`;
    return `${clean.substring(0, 3)} ${clean.substring(3, 6)} ${clean.substring(6, 10)}`;
  }

  copyNequiNumber(): void {
    const num = this.nequiInfo().account_number.replace(/\s/g, '');
    navigator.clipboard.writeText(num).then(() => {
      this.copiedNequi.set(true);
      setTimeout(() => this.copiedNequi.set(false), 2500);
    });
  }

  onReceiptSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
      const file = input.files[0];
      if (file.size > 10 * 1024 * 1024) {
        alert('El comprobante no debe superar 10 MB.');
        return;
      }
      this.receiptFile = file;
      const reader = new FileReader();
      reader.onload = () => {
        this.receiptPreview.set(reader.result as string);
      };
      reader.readAsDataURL(file);
    }
  }

  removeReceipt(): void {
    this.receiptFile = null;
    this.receiptPreview.set(null);
  }

  goToStep(step: number): void {
    if (step > 1 && !this.selectedService()) {
      alert('Por favor selecciona un servicio.');
      return;
    }
    if (step > 2 && (!this.selectedDate() || !this.selectedSlot())) {
      alert('Por favor selecciona fecha y horario disponible.');
      return;
    }
    if (step === 3 && this.selectedService()) {
      const formVal = this.clientForm.value;
      if (formVal.phone) {
        this.storage.saveProfile({
          name: formVal.name,
          phone: formVal.phone,
          email: formVal.email
        });
      }
    }

    this.currentStep.set(step);
    window.scrollTo({ top: 0, behavior: 'smooth' });

    if (step === 2 && (!this.availableSlots().length || !this.selectedSlot())) {
      this.loadSlotsForCurrentSelection();
    }
  }

  submitBooking(): void {
    const svc = this.selectedService();
    const slot = this.selectedSlot();
    const date = this.selectedDate();
    const formVal = this.clientForm.value;

    if (!svc || !slot || !date) {
      alert('Por favor verifica el servicio y horario.');
      return;
    }

    if (this.clientForm.invalid) {
      this.clientForm.markAllAsTouched();
      alert('Por favor completa tu nombre y número de WhatsApp.');
      return;
    }

    if (this.paymentMethod() === 'NEQUI' && !this.receiptFile) {
      alert('Por favor adjunta el comprobante de transferencia Nequi para apartar tu cupo.');
      return;
    }

    this.isSubmitting.set(true);
    this.errorMessage.set(null);

    this.api.createBooking({
      service_id: svc.id,
      booking_date: date,
      start_time: slot.start_time,
      client_name: formVal.name,
      client_phone: formVal.phone,
      client_email: formVal.email,
      client_notes: formVal.notes || '',
      payment_method: this.paymentMethod(),
      receipt_file: this.receiptFile || undefined
    }).subscribe({
      next: (res: AppointmentResponse) => {
        this.isSubmitting.set(false);
        if (res && res.data) {
          this.bookingResult.set(res.data);
          this.currentStep.set(4);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          this.errorMessage.set(res?.message || 'Ocurrió un error al procesar tu cita.');
        }
      },
      error: (err: any) => {
        this.isSubmitting.set(false);
        let msg = 'Error al procesar la reserva. Verifica los datos e intenta de nuevo.';
        if (err.error) {
          if (err.error.message) msg = err.error.message;
          if (err.error.errors && typeof err.error.errors === 'object') {
            const errorDetails = Object.values(err.error.errors).flat().join(' ');
            if (errorDetails) msg += ` (${errorDetails})`;
          }
        }
        this.errorMessage.set(msg);
      }
    });
  }

  getWhatsAppShareUrl(): string {
    const res = this.bookingResult();
    if (!res) return '';
    const phone = environment.paolaWhatsApp;
    const msg = `Hola Paola, acabo de reservar mi cita #${res.appointment_number} para ${res.service_name} el día ${res.booking_date} a las ${res.start_time}. Mi nombre es ${res.client_name}.`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
  }

  resetBooking(): void {
    this.bookingResult.set(null);
    this.receiptFile = null;
    this.receiptPreview.set(null);
    this.selectedService.set(null);
    this.selectedSlot.set(null);
    this.currentStep.set(1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  getServiceImage(svc: Service): string {
    if (svc.image_url) return svc.image_url;
    const c = (svc.category || '').toUpperCase();
    if (c.includes('PESTANAS') || c.includes('CEJAS')) return 'https://images.unsplash.com/photo-1583001809873-a128495da465?w=800&q=80';
    if (c.includes('FACIAL')) return 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?w=800&q=80';
    if (c.includes('LABIOS')) return 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=800&q=80';
    if (c.includes('MASAJE')) return 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&q=80';
    return 'https://images.unsplash.com/photo-1560750588-73207b1ef5b8?w=800&q=80';
  }
}
