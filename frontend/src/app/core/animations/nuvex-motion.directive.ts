import { Directive, ElementRef, HostListener, Input, OnInit } from '@angular/core';
import { animateEntrance, animateTap } from './motion.animations';

export type NuvexMotionVariant = 'entrance' | 'tap' | 'card' | 'none';

@Directive({
  selector: '[nuvexMotion]',
  standalone: true,
})
export class NuvexMotionDirective implements OnInit {
  @Input('nuvexMotion') variant: NuvexMotionVariant = 'entrance';
  @Input() motionDelay: number = 0;

  constructor(private el: ElementRef<HTMLElement>) {}

  ngOnInit(): void {
    if (this.variant === 'entrance' || this.variant === 'card') {
      animateEntrance(this.el.nativeElement, this.motionDelay);
    }
  }

  @HostListener('click')
  onClick(): void {
    if (this.variant === 'tap' || this.variant === 'card') {
      animateTap(this.el.nativeElement);
    }
  }
}
