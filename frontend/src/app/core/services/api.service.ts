import { Injectable, inject } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map, catchError, of } from 'rxjs';
import { environment } from '../../../environments/environment';
import { 
  Service, 
  AvailableSlot, 
  ClientProfile, 
  AppointmentBookingRequest, 
  AppointmentResponse,
  NequiInfoResponse 
} from '../models/booking.models';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private http = inject(HttpClient);
  private baseUrl = environment.apiUrl;

  getServices(): Observable<Service[]> {
    return this.http.get<{ success?: boolean; data?: Service[] } | Service[]>(`${this.baseUrl}/services`).pipe(
      map(res => {
        let list: Service[] = [];
        if (Array.isArray(res)) list = res;
        else if (res && (res as any).data && Array.isArray((res as any).data)) list = (res as any).data;
        
        return list.map(s => ({
          ...s,
          price: Number(s.price || s.base_price || 0),
          deposit_amount: Number(s.deposit_amount || 0)
        }));
      }),
      catchError(err => {
        console.error('Error fetching services:', err);
        return of([]);
      })
    );
  }

  getAvailableSlots(serviceId: number, date: string): Observable<AvailableSlot[]> {
    const params = new HttpParams()
      .set('service_id', serviceId.toString())
      .set('date', date);

    return this.http.get<{ success?: boolean; data?: { slots?: AvailableSlot[] } | AvailableSlot[] } | AvailableSlot[]>(
      `${this.baseUrl}/availability/slots`, { params }
    ).pipe(
      map(res => {
        let slots: AvailableSlot[] = [];
        if (Array.isArray(res)) {
          slots = res;
        } else if (res && (res as any).data) {
          const d = (res as any).data;
          if (Array.isArray(d)) slots = d;
          else if (d.slots && Array.isArray(d.slots)) slots = d.slots;
        }
        return slots;
      }),
      catchError(err => {
        console.error('Error fetching availability slots:', err);
        return of([]);
      })
    );
  }

  lookupClient(phone: string): Observable<ClientProfile | null> {
    const cleanPhone = phone.replace(/\D/g, '');
    if (cleanPhone.length < 7) return of(null);

    const params = new HttpParams().set('phone', cleanPhone);
    return this.http.get<{ success?: boolean; data?: ClientProfile } | ClientProfile>(
      `${this.baseUrl}/clients/lookup`, { params }
    ).pipe(
      map(res => {
        if (res && (res as any).data) return (res as any).data;
        if (res && (res as any).name) return res as ClientProfile;
        return null;
      }),
      catchError(() => of(null))
    );
  }

  getNequiInfo(): Observable<NequiInfoResponse['data']> {
    return this.http.get<NequiInfoResponse>(`${this.baseUrl}/payments/nequi-info`).pipe(
      map(res => res?.data || {
        account_number: environment.nequiNumber,
        account_holder: 'Paola Andrea Aguilera Camacho',
        instructions: 'Transfiere el anticipo a Nequi y adjunta el comprobante.'
      }),
      catchError(() => of({
        account_number: environment.nequiNumber,
        account_holder: 'Paola Andrea Aguilera Camacho',
        instructions: 'Transfiere el anticipo a Nequi y adjunta el comprobante.'
      }))
    );
  }

  createBooking(request: AppointmentBookingRequest): Observable<AppointmentResponse> {
    const formData = new FormData();
    formData.append('service_id', request.service_id.toString());
    formData.append('date', request.booking_date);
    formData.append('booking_date', request.booking_date);
    const cleanStartTime = (request.start_time || '').substring(0, 5);
    formData.append('start_time', cleanStartTime);
    formData.append('client_name', request.client_name);
    formData.append('client_phone', request.client_phone.replace(/\D/g, ''));
    if (request.client_email) formData.append('client_email', request.client_email);
    if (request.client_notes) formData.append('client_notes', request.client_notes);
    
    const paymentMethod = request.payment_method === 'NEQUI' ? 'NEQUI_TRANSFER' : (request.payment_method === 'BOLD' ? 'BOLD_ONLINE' : request.payment_method);
    formData.append('payment_method', paymentMethod);
    
    if (request.receipt_file) {
      formData.append('receipt', request.receipt_file);
    }

    return this.http.post<AppointmentResponse>(`${this.baseUrl}/appointments/book`, formData);
  }
}
