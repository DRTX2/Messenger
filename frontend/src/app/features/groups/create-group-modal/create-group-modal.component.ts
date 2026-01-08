import { Component, signal, inject, Output, EventEmitter, ChangeDetectionStrategy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ChatService } from '../../../core/services';
import { User } from '../../../shared/models';

@Component({
  selector: 'app-create-group-modal',
  standalone: true,
  imports: [CommonModule, FormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './create-group-modal.component.html',
})
export class CreateGroupModalComponent {
  private chatService = inject(ChatService);

  @Output() close = new EventEmitter<void>();
  @Output() groupCreated = new EventEmitter<void>();

  // State
  groupName = signal('');
  selectedUsers = signal<User[]>([]);
  availableUsers = signal<User[]>([]);
  isLoading = signal(false);
  isCreating = signal(false);

  constructor() {
    this.loadUsers();
  }

  private loadUsers(): void {
    this.isLoading.set(true);
    this.chatService.getUsers().subscribe({
      next: (res) => {
        this.availableUsers.set(res.data || []);
        this.isLoading.set(false);
      },
      error: () => this.isLoading.set(false)
    });
  }

  toggleUser(user: User): void {
    const current = this.selectedUsers();
    const exists = current.find(u => u.id === user.id);
    
    if (exists) {
      this.selectedUsers.set(current.filter(u => u.id !== user.id));
    } else {
      this.selectedUsers.set([...current, user]);
    }
  }

  isSelected(userId: number): boolean {
    return this.selectedUsers().some(u => u.id === userId);
  }

  get canCreate(): boolean {
    return this.groupName().trim().length > 0 && this.selectedUsers().length > 0;
  }

  onCreate(): void {
    if (!this.canCreate) return;

    this.isCreating.set(true);
    const userIds = this.selectedUsers().map(u => u.id);

    this.chatService.createGroup(this.groupName(), userIds).subscribe({
      next: () => {
        this.isCreating.set(false);
        this.groupCreated.emit();
      },
      error: () => this.isCreating.set(false)
    });
  }
}
