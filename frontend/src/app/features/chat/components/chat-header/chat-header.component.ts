import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AvatarComponent } from '../../../../shared/components';
import { UIConversation } from '../../../../shared/models';

@Component({
  selector: 'app-chat-header',
  standalone: true,
  imports: [CommonModule, AvatarComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md flex-shrink-0 z-20 sticky top-0">
      <div class="flex items-center gap-3">
        <!-- Back Button (Mobile) -->
        <button 
          (click)="backClicked.emit()" 
          class="md:hidden p-2 -ml-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
          </svg>
        </button>
        
        <app-avatar
          [src]="conversation?.user?.avatar || null"
          [name]="conversation?.user?.name || 'Unknown'"
          [status]="conversation?.isGroup ? null : conversation?.user?.status || 'offline'"
          [isGroup]="conversation?.isGroup || false"
          [size]="40">
        </app-avatar>

        <div class="flex flex-col cursor-pointer">
          <h2 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 leading-tight flex items-center gap-1.5">
            {{ conversation?.user?.name || 'Select a conversation' }}
          </h2>
          @if (conversation?.isGroup) {
            <span class="text-xs text-zinc-500">{{ conversation?.participantCount }} members</span>
          } @else if (conversation?.user?.status === 'online') {
            <span class="text-xs text-emerald-500 font-medium">Online</span>
          } @else {
            <span class="text-xs text-zinc-500">Offline</span>
          }
        </div>
      </div>

      <div class="flex items-center gap-1 md:gap-2">
        <!-- Clear Chat -->
        <button 
          (click)="clearClicked.emit()" 
          class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-red-600 transition-colors" 
          title="Clear Chat">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
          </svg>
        </button>

        <!-- Leave Group (for groups) -->
        @if (conversation?.isGroup) {
          <button 
            (click)="leaveGroupClicked.emit()" 
            class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-orange-600 transition-colors" 
            title="Leave Group">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
            </svg>
          </button>
        }

        <!-- More options -->
        <button class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-400 hover:text-indigo-600 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" />
          </svg>
        </button>
      </div>
    </header>
  `
})
export class ChatHeaderComponent {
  @Input() conversation: UIConversation | null = null;
  
  @Output() backClicked = new EventEmitter<void>();
  @Output() clearClicked = new EventEmitter<void>();
  @Output() leaveGroupClicked = new EventEmitter<void>();
}
