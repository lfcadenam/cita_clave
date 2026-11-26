import { Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';
import { NuvexNotificationContainerComponent } from './shared/components/nuvex-notification-container/nuvex-notification-container.component';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, NuvexNotificationContainerComponent],
  template: `
    <router-outlet></router-outlet>
    <app-nuvex-notification-container />
  `
})
export class AppComponent {
  title = 'Paola Aguilera • Belleza y Estética';
}
