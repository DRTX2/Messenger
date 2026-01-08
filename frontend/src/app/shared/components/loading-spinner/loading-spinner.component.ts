import { Component, Input, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-loading-spinner',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div 
      class="flex items-center justify-center"
      [class]="containerClass">
      <div 
        class="animate-spin rounded-full border-t-2 border-b-2"
        [class]="spinnerClass"
        [style.width.px]="size"
        [style.height.px]="size">
      </div>
      @if (text) {
        <span class="ml-2 text-sm text-zinc-500">{{ text }}</span>
      }
    </div>
  `
})
export class LoadingSpinnerComponent {
  @Input() size = 24;
  @Input() text?: string;
  @Input() color: 'primary' | 'white' | 'muted' = 'primary';
  @Input() containerClass = '';

  get spinnerClass(): string {
    switch (this.color) {
      case 'white':
        return 'border-white';
      case 'muted':
        return 'border-zinc-400';
      default:
        return 'border-indigo-600';
    }
  }
}
