import { Injectable } from '@angular/core';
import { ClientProfile } from '../models/booking.models';

@Injectable({
  providedIn: 'root'
})
export class StorageService {
  private readonly PROFILE_KEY = 'nuvex_paola_client_profile';

  getSavedProfile(): ClientProfile | null {
    try {
      const data = localStorage.getItem(this.PROFILE_KEY);
      return data ? JSON.parse(data) : null;
    } catch (e) {
      console.warn('Could not read client profile from localStorage', e);
      return null;
    }
  }

  saveProfile(profile: Partial<ClientProfile>): void {
    try {
      const existing = this.getSavedProfile() || {} as ClientProfile;
      const updated = { ...existing, ...profile };
      localStorage.setItem(this.PROFILE_KEY, JSON.stringify(updated));
    } catch (e) {
      console.warn('Could not save client profile to localStorage', e);
    }
  }

  clearProfile(): void {
    try {
      localStorage.removeItem(this.PROFILE_KEY);
    } catch (e) {
      console.warn('Could not clear client profile', e);
    }
  }
}
