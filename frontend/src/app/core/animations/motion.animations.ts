import { animate, spring } from 'motion';

/**
 * Configuraciones de física de resortes (Spring Physics) oficiales de Nuvex.
 */
export const NUVEX_SPRING_CONFIG = {
  smooth: spring({ stiffness: 320, damping: 25, mass: 0.9 }),
  snappy: spring({ stiffness: 400, damping: 28, mass: 0.8 }),
  gentle: spring({ stiffness: 200, damping: 22, mass: 1.0 }),
};

/**
 * Animación de entrada para modales, tarjetas o contenedores Nuvex Modern Curve UI.
 */
export function animateEntrance(element: HTMLElement, delay: number = 0) {
  if (!element) return;
  return animate(
    element,
    {
      opacity: [0, 1],
      transform: ['translateY(12px) scale(0.97)', 'translateY(0) scale(1)'],
    },
    {
      easing: NUVEX_SPRING_CONFIG.smooth,
      delay,
    }
  );
}

/**
 * Animación de feedback táctil al hacer tap o clic en tarjetas interactivas o botones.
 */
export function animateTap(element: HTMLElement) {
  if (!element) return;
  return animate(
    element,
    {
      transform: ['scale(1)', 'scale(0.96)', 'scale(1)'],
    },
    {
      duration: 0.18,
      easing: 'ease-out',
    }
  );
}

/**
 * Animación escalonada para listas (servicios, horarios disponibles, etc.)
 */
export function animateStaggerItems(container: HTMLElement, itemSelector: string, staggerDelay: number = 0.04) {
  if (!container) return;
  const items = container.querySelectorAll<HTMLElement>(itemSelector);
  if (!items.length) return;

  items.forEach((item, index) => {
    animate(
      item,
      {
        opacity: [0, 1],
        transform: ['translateY(8px)', 'translateY(0)'],
      },
      {
        easing: NUVEX_SPRING_CONFIG.smooth,
        delay: index * staggerDelay,
      }
    );
  });
}
