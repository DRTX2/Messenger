import { Component, Input, Output, EventEmitter, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AvatarComponent } from '../../../../shared/components';
import { UIConversation } from '../../../../shared/models';

@Component({
  selector: 'app-chat-sidebar',
  standalone: true,
  imports: [CommonModule, FormsModule, AvatarComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './sidebar.component.html',
})
export class SidebarComponent {
  @Input() conversations: UIConversation[] = [];
  @Input() selectedId: number | null = null;
  @Input() currentUserName = 'User';
  @Input() searchQuery = '';
  @Input() isSearching = false;

  @Output() conversationSelected = new EventEmitter<number>();
  @Output() searchChanged = new EventEmitter<string>();
  @Output() searchSubmitted = new EventEmitter<void>();
  @Output() createGroupClicked = new EventEmitter<void>();
  @Output() logoutClicked = new EventEmitter<void>();
}
