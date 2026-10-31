import { Component, OnInit, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';

export interface PetAppointment {
  id: number;
  time: string;
  time24: string;
  petName: string;
  species: string;
  breed: string;
  ownerName: string;
  ownerPhone?: string;
  service: string;
  status: 'confirmado' | 'pendiente' | 'cancelado';
  date: string; // YYYY-MM-DD
  doctor: string;
  notes?: string;
}

export interface WeekDay {
  date: Date;
  dateStr: string;
  dayName: string;
  dayNumber: number;
  isToday: boolean;
  isSelected: boolean;
}

@Component({
  selector: 'app-appointments-calendar',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './appointments-calendar.component.html',
  styleUrls: ['./appointments-calendar.component.css']
})
export class AppointmentsCalendarComponent implements OnInit {
  // Navigation & Clinic Profile
  clinicName = signal<string>('Clínica De Valledupar');
  systemBrand = signal<string>('VetSystemSaas');
  doctorProfile = signal<{ name: string; role: string; avatar: string }>({
    name: 'Dr. Breyner Ustariz',
    role: 'VETERINARIO SENIOR',
    avatar: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?q=80&w=250&auto=format&fit=crop'
  });

  // Active View Switcher: 'lista' | 'calendario' | 'hibrido'
  activeView = signal<'lista' | 'calendario' | 'hibrido'>('hibrido');

  // Active Sidebar Nav
  activeNav = signal<string>('citas');

  // Reference Date for Current Week (defaulting to current date or sample Nov 2023)
  currentWeekStart = signal<Date>(this.getMondayOfDate(new Date()));

  // Stats signals
  stats = signal({
    confirmadas: 12,
    pendientes: 4,
    canceladas: 2,
    totalHoy: 18
  });

  // Spotlight Reminder
  spotlightReminder = signal({
    title: 'Recordatorio',
    highlight: 'Cirugía programada para Max a las 14:00',
    pet: 'Max',
    species: 'Perro (Golden Retriever)',
    doctor: 'Dr. Breyner Ustariz',
    owner: 'Valentina Restrepo',
    phone: '3105559876',
    details: 'Profilaxis y extracción molar programada en Quirófano 1. Ayuno confirmado.'
  });

  // Master Appointments List
  appointments = signal<PetAppointment[]>([
    {
      id: 1,
      time: '09:00 AM',
      time24: '09:00',
      petName: 'Luna',
      species: 'Gato',
      breed: 'Siamés',
      ownerName: 'Carlos Ruiz',
      ownerPhone: '3001234567',
      service: 'Vacunación',
      status: 'confirmado',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Vacuna Triple Felina anual.'
    },
    {
      id: 2,
      time: '10:30 AM',
      time24: '10:30',
      petName: 'Toby',
      species: 'Perro',
      breed: 'Beagle',
      ownerName: 'Ana López',
      ownerPhone: '3129876543',
      service: 'Consulta General',
      status: 'pendiente',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Control digestivo y peso.'
    },
    {
      id: 3,
      time: '11:45 AM',
      time24: '11:45',
      petName: 'Coco',
      species: 'Loro',
      breed: 'Amazona',
      ownerName: 'Marcos Gil',
      ownerPhone: '3187654321',
      service: 'Desparasitación',
      status: 'confirmado',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Desparasitación periódica para ave exótica.'
    },
    {
      id: 4,
      time: '01:15 PM',
      time24: '13:15',
      petName: 'Rex',
      species: 'Perro',
      breed: 'Pastor Alemán',
      ownerName: 'Julia Sanz',
      ownerPhone: '3206549871',
      service: 'Limpieza Dental',
      status: 'cancelado',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Cancelado por el propietario por viaje.'
    },
    {
      id: 5,
      time: '02:00 PM',
      time24: '14:00',
      petName: 'Max',
      species: 'Perro',
      breed: 'Golden Retriever',
      ownerName: 'Valentina Restrepo',
      ownerPhone: '3105559876',
      service: 'Cirugía Dental',
      status: 'confirmado',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Quirófano 1 preparado.'
    },
    {
      id: 6,
      time: '04:30 PM',
      time24: '16:30',
      petName: 'Milo',
      species: 'Gato',
      breed: 'Persa',
      ownerName: 'Diana Torres',
      ownerPhone: '3114443322',
      service: 'Control Post-Operatorio',
      status: 'confirmado',
      date: this.formatDate(new Date()),
      doctor: 'Dr. Breyner Ustariz',
      notes: 'Revisión de puntos de sutura.'
    }
  ]);

  // Selected Day & Filter states
  filterStatus = signal<string>('todos');
  searchQuery = signal<string>('');
  selectedAppointment = signal<PetAppointment | null>(null);
  showFilterModal = signal<boolean>(false);
  showReminderModal = signal<boolean>(false);

  // Pagination for Daily Table
  currentPage = signal<number>(1);
  pageSize = signal<number>(4);

  // Computed Week Days
  weekDays = computed<WeekDay[]>(() => {
    const monday = this.currentWeekStart();
    const days: WeekDay[] = [];
    const todayStr = this.formatDate(new Date());

    const names = ['LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB', 'DOM'];

    for (let i = 0; i < 7; i++) {
      const d = new Date(monday);
      d.setDate(monday.getDate() + i);
      const dStr = this.formatDate(d);

      days.push({
        date: d,
        dateStr: dStr,
        dayName: names[i],
        dayNumber: d.getDate(),
        isToday: dStr === todayStr,
        isSelected: false
      });
    }

    return days;
  });

  // Week Title Range (e.g. "Noviembre 20-26, 2026")
  weekRangeText = computed<string>(() => {
    const monday = this.currentWeekStart();
    const sunday = new Date(monday);
    sunday.setDate(monday.getDate() + 6);

    const monthNames = [
      'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
      'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const monthStart = monthNames[monday.getMonth()];
    const year = monday.getFullYear();

    if (monday.getMonth() === sunday.getMonth()) {
      return `${monthStart} ${monday.getDate()}-${sunday.getDate()}, ${year}`;
    } else {
      const monthEnd = monthNames[sunday.getMonth()];
      return `${monthStart} ${monday.getDate()} - ${monthEnd} ${sunday.getDate()}, ${year}`;
    }
  });

  // Filtered Today's appointments for table
  paginatedTodayAppointments = computed<PetAppointment[]>(() => {
    let list = this.appointments();
    const query = this.searchQuery().toLowerCase().trim();
    const status = this.filterStatus();

    if (status !== 'todos') {
      list = list.filter(a => a.status === status);
    }

    if (query) {
      list = list.filter(a =>
        a.petName.toLowerCase().includes(query) ||
        a.ownerName.toLowerCase().includes(query) ||
        a.service.toLowerCase().includes(query) ||
        a.breed.toLowerCase().includes(query)
      );
    }

    const start = (this.currentPage() - 1) * this.pageSize();
    return list.slice(start, start + this.pageSize());
  });

  totalFilteredCount = computed<number>(() => {
    return this.appointments().length;
  });

  ngOnInit(): void {
    // Populate additional appointments across the current week for full realism
    this.seedWeekAppointments();
  }

  // --- Week & Calendar Helpers ---
  private getMondayOfDate(d: Date): Date {
    const date = new Date(d);
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1); // adjust when day is sunday
    date.setDate(diff);
    date.setHours(0, 0, 0, 0);
    return date;
  }

  private formatDate(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  prevWeek(): void {
    const current = new Date(this.currentWeekStart());
    current.setDate(current.getDate() - 7);
    this.currentWeekStart.set(current);
  }

  nextWeek(): void {
    const current = new Date(this.currentWeekStart());
    current.setDate(current.getDate() + 7);
    this.currentWeekStart.set(current);
  }

  goToToday(): void {
    this.currentWeekStart.set(this.getMondayOfDate(new Date()));
  }

  // Get appointments for a specific day column
  getAppointmentsForDay(dateStr: string): PetAppointment[] {
    return this.appointments().filter(a => a.date === dateStr);
  }

  // Pagination
  prevPage(): void {
    if (this.currentPage() > 1) {
      this.currentPage.update(p => p - 1);
    }
  }

  nextPage(): void {
    const maxPage = Math.ceil(this.totalFilteredCount() / this.pageSize());
    if (this.currentPage() < maxPage) {
      this.currentPage.update(p => p + 1);
    }
  }

  // View Switching
  setView(view: 'lista' | 'calendario' | 'hibrido'): void {
    this.activeView.set(view);
  }

  // Modal actions
  openCitaDetalle(cita: PetAppointment): void {
    this.selectedAppointment.set(cita);
  }

  closeCitaModal(): void {
    this.selectedAppointment.set(null);
  }

  openReminderModal(): void {
    this.showReminderModal.set(true);
  }

  closeReminderModal(): void {
    this.showReminderModal.set(false);
  }

  toggleFilterModal(): void {
    this.showFilterModal.update(v => !v);
  }

  // Status Styling helpers
  getStatusBadgeClass(status: string): string {
    switch (status) {
      case 'confirmado':
        return 'bg-[#DCFCE7] text-[#15803D] border border-green-200/50';
      case 'pendiente':
        return 'bg-[#FEF3C7] text-[#B45309] border border-amber-200/50';
      case 'cancelado':
        return 'bg-[#FFE4E6] text-[#BE123C] border border-rose-200/50';
      default:
        return 'bg-slate-100 text-slate-600 border border-slate-200';
    }
  }

  getStatusDotClass(status: string): string {
    switch (status) {
      case 'confirmado':
        return 'bg-[#10B981]';
      case 'pendiente':
        return 'bg-[#F59E0B]';
      case 'cancelado':
        return 'bg-[#EF4444]';
      default:
        return 'bg-slate-400';
    }
  }

  getCalendarCardClass(status: string): string {
    switch (status) {
      case 'confirmado':
        return 'bg-[#ECFDF5] border-[#00C975] text-[#065F46] hover:bg-[#D1FAE5]';
      case 'pendiente':
        return 'bg-[#FFFBEB] border-[#F59E0B] text-[#92400E] hover:bg-[#FEF3C7]';
      case 'cancelado':
        return 'bg-[#FFF1F2] border-[#F43F5E] text-[#9F1239] opacity-60';
      default:
        return 'bg-slate-50 border-slate-300 text-slate-700';
    }
  }

  private seedWeekAppointments(): void {
    const monday = this.currentWeekStart();
    const day2 = new Date(monday); day2.setDate(monday.getDate() + 1); // Martes
    const day4 = new Date(monday); day4.setDate(monday.getDate() + 3); // Jueves
    const day5 = new Date(monday); day5.setDate(monday.getDate() + 4); // Viernes

    const additional: PetAppointment[] = [
      {
        id: 7,
        time: '10:30 AM',
        time24: '10:30',
        petName: 'Toby',
        species: 'Perro',
        breed: 'Beagle',
        ownerName: 'Ana López',
        service: 'Consulta',
        status: 'pendiente',
        date: this.formatDate(day2),
        doctor: 'Dr. Breyner Ustariz'
      },
      {
        id: 8,
        time: '11:45 AM',
        time24: '11:45',
        petName: 'Coco',
        species: 'Loro',
        breed: 'Amazona',
        ownerName: 'Marcos Gil',
        service: 'Desparasita',
        status: 'confirmado',
        date: this.formatDate(day4),
        doctor: 'Dr. Breyner Ustariz'
      },
      {
        id: 9,
        time: '03:00 PM',
        time24: '15:00',
        petName: 'Sasha',
        species: 'Gato',
        breed: 'Angora',
        ownerName: 'Felipe Mendoza',
        service: 'Vacuna',
        status: 'confirmado',
        date: this.formatDate(day5),
        doctor: 'Dr. Breyner Ustariz'
      }
    ];

    // Merge without duplicates
    const current = this.appointments();
    const existingIds = new Set(current.map(c => c.id));
    const merged = [...current];

    additional.forEach(a => {
      if (!existingIds.has(a.id)) {
        merged.push(a);
      }
    });

    this.appointments.set(merged);
  }
}
