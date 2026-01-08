import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-avatar',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="relative" [style.width.px]="size" [style.height.px]="size">
      <img 
        [src]="src || fallbackUrl" 
        [alt]="alt"
        class="w-full h-full rounded-full object-cover bg-zinc-200 dark:bg-zinc-800"
        (error)="onImageError($event)"
      >
      
      <!-- Status indicator -->
      @if (status === 'online') {
        <span 
          class="absolute bottom-0 right-0 bg-emerald-500 border-2 border-white dark:border-zinc-900 rounded-full"
          [style.width.px]="statusSize"
          [style.height.px]="statusSize">
        </span>
      } @else if (status === 'away') {
        <span 
          class="absolute bottom-0 right-0 bg-amber-500 border-2 border-white dark:border-zinc-900 rounded-full"
          [style.width.px]="statusSize"
          [style.height.px]="statusSize">
        </span>
      }

      <!-- Group badge -->
      @if (isGroup && participantCount) {
        <span 
          class="absolute -top-1 -right-1 bg-indigo-600 border-2 border-white dark:border-zinc-900 rounded-full flex items-center justify-center text-white text-[10px] font-bold"
          [style.width.px]="badgeSize"
          [style.height.px]="badgeSize">
          {{ participantCount }}
        </span>
      }
    </div>
  `
})
export class AvatarComponent {
  @Input() src: string | null = null;
  @Input() alt = 'Avatar';
  @Input() name = 'User';
  @Input() size = 48;
  @Input() status: 'online' | 'offline' | 'away' | null = null;
  @Input() isGroup = false;
  @Input() participantCount?: number;

  get fallbackUrl(): string {
    const initial = this.name?.charAt(0) || 'U';
    const bg = this.isGroup ? '6366f1' : 'random';
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(initial)}&background=${bg}&color=fff`;
  }

  get statusSize(): number {
    return Math.max(8, this.size * 0.25);
  }

  get badgeSize(): number {
    return Math.max(16, this.size * 0.4);
  }

  onImageError(event: any): void {
    event.target.src = this.fallbackUrl;
  }
}
