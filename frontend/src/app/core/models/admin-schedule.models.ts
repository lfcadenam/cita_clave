export interface WorkingSchedule {
  id: number;
  day_of_week: number;
  day_name: string;
  is_working_day: boolean;
  open_time: string;
  close_time: string;
  slot_interval_minutes: number;
}

export interface BlockedSlot {
  id: number;
  title: string;
  blocked_date: string | null;
  start_time: string;
  end_time: string;
  time_range: string;
  is_recurring: boolean;
  is_specific?: boolean;
  reason?: string | null;
}
