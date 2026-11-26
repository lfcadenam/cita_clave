export type NuvexNotificationType = 'success' | 'error' | 'warning' | 'info' | 'default';

export type NuvexConfirmVariant = 'primary' | 'danger' | 'warning' | 'info';

export type NuvexConfirmIcon = 'trash' | 'calendar' | 'lock' | 'check' | 'alert' | 'whatsapp' | 'clock';

export interface NuvexToast {
  id: string;
  type: NuvexNotificationType;
  title?: string;
  message: string;
  duration?: number;
  dismissible?: boolean;
}

export interface NuvexConfirmDetail {
  label: string;
  value: string;
}

export interface NuvexConfirmOptions {
  title: string;
  subtitle?: string;
  message?: string;
  variant?: NuvexConfirmVariant;
  icon?: NuvexConfirmIcon;
  details?: NuvexConfirmDetail[];
  confirmText?: string;
  cancelText?: string;
}

export interface NuvexActiveConfirmDialog extends NuvexConfirmOptions {
  id: string;
  resolve: (result: boolean) => void;
}
