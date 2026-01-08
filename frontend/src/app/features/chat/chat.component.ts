import { Component, signal, computed, effect, inject, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router } from '@angular/router';

import { AuthService, ChatService, WebSocketService } from '../../core/services';
import { 
  User, 
  Conversation, 
  UIConversation, 
  UIMessage, 
  Attachment 
} from '../../shared/models';
import { generateUUID } from '../../shared/utils/uuid';

import {
  SidebarComponent,
  ChatHeaderComponent,
  MessageListComponent,
  MessageInputComponent
} from './components';

import { CreateGroupModalComponent } from '../groups/create-group-modal/create-group-modal.component';

@Component({
  selector: 'app-chat',
  standalone: true,
  imports: [
    CommonModule,
    SidebarComponent,
    ChatHeaderComponent,
    MessageListComponent,
    MessageInputComponent,
    CreateGroupModalComponent
  ],
  templateUrl: './chat.component.html',
})
export class ChatComponent {
  private authService = inject(AuthService);
  private chatService = inject(ChatService);
  private webSocketService = inject(WebSocketService);
  private router = inject(Router);

  // State
  conversations = signal<UIConversation[]>([]);
  selectedConversationId = signal<number | null>(null);
  currentMessages = signal<UIMessage[]>([]);
  
  // Search
  searchQuery = signal('');
  isSearching = signal(false);

  // Message Input
  messageText = signal('');
  pendingAttachments = signal<Attachment[]>([]);
  isUploading = signal(false);
  isSending = signal(false);

  // Group Modal
  showGroupModal = signal(false);

  // Typing Indicator
  typingUsers = signal<{ userId: number; userName: string }[]>([]);
  private typingTimeout: any = null;
  private typingDebounceTimeout: any = null;

  // Computed
  currentUser = this.authService.currentUser;
  selectedConversation = computed(() => 
    this.conversations().find(c => c.id === this.selectedConversationId())
  );
  isMobileViewingChat = computed(() => 
    !!this.selectedConversationId() && window.innerWidth < 768
  );
  typingIndicatorText = computed(() => {
    const users = this.typingUsers();
    if (users.length === 0) return null;
    if (users.length === 1) return `${users[0].userName} is typing...`;
    if (users.length === 2) return `${users[0].userName} and ${users[1].userName} are typing...`;
    return 'Several people are typing...';
  });

  constructor() {
    // Initialize WebSocket on component load
    this.webSocketService.connect();

    // Load conversations
    effect(() => {
      if (this.currentUser()) {
        this.loadConversations();
      }
    });

    // Real-time subscription management
    effect((onCleanup) => {
      const conversationId = this.selectedConversationId();
      if (conversationId && conversationId > 0) {
        // Subscribe to messages AND typing events
        this.webSocketService.listenToConversation(
          conversationId, 
          (msg) => this.handleRealTimeMessage(msg),
          (typingData) => this.handleTypingEvent(typingData)
        );
        this.loadMessages(conversationId);
        this.typingUsers.set([]); // Clear typing on conversation change

        onCleanup(() => {
          this.webSocketService.leaveConversation(conversationId);
          this.typingUsers.set([]);
        });
      }
    });
  }

  // ─────────────────────────────────────────────────────────────
  // ACTIONS
  // ─────────────────────────────────────────────────────────────

  loadConversations(): void {
    this.chatService.getConversations().subscribe({
      next: (res) => {
        this.chatService.isLoadingConversations.set(false);
        const mapped = res.data.map((c: Conversation) => this.mapConversation(c));
        this.conversations.set(mapped);
      }
    });
  }

  selectConversation(id: number): void {
    this.selectedConversationId.set(id);
  }

  closeChat(): void {
    this.selectedConversationId.set(null);
  }

  doSearch(): void {
    const query = this.searchQuery();
    if (!query.trim()) {
      this.isSearching.set(false);
      this.loadConversations();
      return;
    }

    this.isSearching.set(true);
    this.chatService.getUsers(query).subscribe({
      next: (res) => {
        const mapped = res.data.map((u: User) => this.mapUserToConversation(u));
        this.conversations.set(mapped);
      }
    });
  }

  loadMessages(conversationId: number): void {
    if (conversationId < 0) {
      this.currentMessages.set([]);
      return;
    }

    const conv = this.conversations().find(c => c.id === conversationId);
    if (!conv) return;

    this.chatService.getMessages(conv.user.id).subscribe({
      next: (res) => {
        this.chatService.isLoadingMessages.set(false);
        if (res.success) {
          const msgs = res.data.map((m: any) => this.mapMessage(m));
          this.currentMessages.set(msgs.reverse());
        }
      }
    });
  }

  onSendMessage(): void {
    const text = this.messageText();
    const attachments = this.pendingAttachments();
    const convId = this.selectedConversationId();

    if ((!text.trim() && attachments.length === 0) || !convId) return;

    const conv = this.conversations().find(c => c.id === convId);
    if (!conv) return;

    this.isSending.set(true);
    const attachmentIds = attachments.map(a => a.id);
    const requestId = generateUUID();

    this.chatService.sendMessage(conv.user.id, text, attachmentIds, requestId).subscribe({
      next: (res) => {
        this.isSending.set(false);
        this.messageText.set('');
        this.pendingAttachments.set([]);

        if (res.success && res.data) {
          const mapped = this.mapMessage(res.data);
          // Check if message already exists (idempotency check in UI too for strictness)
          if (!this.currentMessages().find(m => m.id === mapped.id)) {
            this.currentMessages.update(msgs => [...msgs, mapped]);
          }
        }

        if (convId < 0) {
          this.loadConversations();
        }
      },
      error: () => this.isSending.set(false)
    });
  }

  onFileSelected(file: File): void {
    this.isUploading.set(true);
    this.chatService.uploadAttachment(file).subscribe({
      next: (res) => {
        this.isUploading.set(false);
        if (res.success) {
          this.pendingAttachments.update(prev => [...prev, res.data]);
        }
      },
      error: () => this.isUploading.set(false)
    });
  }

  onRemoveAttachment(id: number): void {
    this.pendingAttachments.update(prev => prev.filter(a => a.id !== id));
  }

  onToggleFavorite(msg: UIMessage): void {
    this.chatService.toggleFavorite(msg.id).subscribe({
      next: () => {
        this.currentMessages.update(msgs => 
          msgs.map(m => m.id === msg.id ? { ...m, isFavorite: !m.isFavorite } : m)
        );
      }
    });
  }

  onDeleteMessage(messageId: number): void {
    if (confirm('Delete this message?')) {
      this.chatService.deleteMessage(messageId).subscribe({
        next: () => {
          this.currentMessages.update(msgs => msgs.filter(m => m.id !== messageId));
        }
      });
    }
  }

  onClearChat(): void {
    const convId = this.selectedConversationId();
    if (!convId || convId < 0) return;

    const conv = this.conversations().find(c => c.id === convId);
    if (!conv) return;

    if (confirm('Are you sure you want to clear this conversation?')) {
      this.chatService.clearConversation(conv.user.id).subscribe({
        next: () => {
          this.currentMessages.set([]);
          this.loadConversations();
        }
      });
    }
  }

  onLeaveGroup(): void {
    const convId = this.selectedConversationId();
    if (!convId) return;

    if (confirm('Are you sure you want to leave this group?')) {
      this.chatService.leaveGroup(convId).subscribe({
        next: () => {
          this.loadConversations();
          this.closeChat();
        }
      });
    }
  }

  onLogout(): void {
    this.webSocketService.disconnect();
    this.authService.logout();
  }

  onGroupCreated(): void {
    this.showGroupModal.set(false);
    this.loadConversations();
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (this.showGroupModal()) {
      this.showGroupModal.set(false);
    } else if (this.selectedConversationId()) {
      this.closeChat();
    }
  }

  // ─────────────────────────────────────────────────────────────
  // REAL-TIME
  // ─────────────────────────────────────────────────────────────

  private handleRealTimeMessage(msg: any): void {
    const current = this.currentMessages();
    if (current.find(m => m.id === msg.id)) return;

    const mapped = this.mapMessage(msg);
    this.currentMessages.update(msgs => [...msgs, mapped]);
    this.loadConversations();

    // Clear typing indicator for the sender when they send a message
    this.typingUsers.update(users => 
      users.filter(u => u.userId !== msg.sender_id)
    );
  }

  private handleTypingEvent(data: { user_id: number; user_name: string; is_typing: boolean }): void {
    const currentUserId = this.currentUser()?.id;
    if (data.user_id === currentUserId) return; // Ignore own typing events

    if (data.is_typing) {
      // Add user to typing list if not already there
      this.typingUsers.update(users => {
        if (users.find(u => u.userId === data.user_id)) return users;
        return [...users, { userId: data.user_id, userName: data.user_name }];
      });

      // Auto-remove after 3 seconds of inactivity
      clearTimeout(this.typingTimeout);
      this.typingTimeout = setTimeout(() => {
        this.typingUsers.update(users => 
          users.filter(u => u.userId !== data.user_id)
        );
      }, 3000);
    } else {
      // Remove user from typing list
      this.typingUsers.update(users => 
        users.filter(u => u.userId !== data.user_id)
      );
    }
  }

  /**
   * Called when user is typing in the input
   */
  onTyping(): void {
    const convId = this.selectedConversationId();
    if (!convId || convId < 0) return;

    // Debounce: only send typing event every 1 second
    if (this.typingDebounceTimeout) return;

    this.chatService.sendTypingIndicator(convId, true).subscribe();

    this.typingDebounceTimeout = setTimeout(() => {
      this.typingDebounceTimeout = null;
    }, 1000);
  }

  /**
   * Called when user stops typing (blur or send)
   */
  onStopTyping(): void {
    const convId = this.selectedConversationId();
    if (!convId || convId < 0) return;

    clearTimeout(this.typingDebounceTimeout);
    this.typingDebounceTimeout = null;

    this.chatService.sendTypingIndicator(convId, false).subscribe();
  }

  // ─────────────────────────────────────────────────────────────
  // MAPPERS
  // ─────────────────────────────────────────────────────────────

  private mapConversation(c: Conversation): UIConversation {
    const currentUserId = this.currentUser()?.id;
    const otherUser = c.participants.find(p => p.id !== currentUserId) || c.participants[0];
    
    const isGroup = c.is_group;
    const displayName = isGroup ? c.name : (otherUser?.name || 'Unknown');
    const displayAvatar = isGroup
      ? (c.avatar_url || `https://ui-avatars.com/api/?name=${c.name?.charAt(0) || 'G'}&background=6366f1&color=fff`)
      : (otherUser?.avatar || `https://ui-avatars.com/api/?name=${otherUser?.name || 'U'}&background=random`);

    return {
      id: c.id,
      isGroup,
      participantCount: c.participants.length,
      user: {
        id: otherUser?.id || 0,
        name: displayName || 'Unknown',
        avatar: displayAvatar,
        status: otherUser?.is_online ? 'online' : 'offline'
      },
      lastMessage: {
        text: c.latest_message?.content || 'No messages yet',
        timestamp: c.latest_message ? new Date(c.latest_message.created_at) : new Date(c.last_message_at),
        isRead: true,
        senderId: c.latest_message?.sender_id === currentUserId ? 'me' : 'other'
      },
      unreadCount: 0
    };
  }

  private mapUserToConversation(u: User): UIConversation {
    return {
      id: -u.id, // Negative ID for new conversations
      isGroup: false,
      participantCount: 2,
      user: {
        id: u.id,
        name: u.name,
        avatar: `https://ui-avatars.com/api/?name=${u.name}&background=random`,
        status: u.is_online ? 'online' : 'offline'
      },
      lastMessage: {
        text: 'Tap to start chatting',
        timestamp: new Date(),
        isRead: true,
        senderId: 'system'
      },
      unreadCount: 0
    };
  }

  private mapMessage(m: any): UIMessage {
    const currentUserId = this.currentUser()?.id;
    return {
      id: m.id,
      senderId: m.sender_id === currentUserId ? 'me' : m.sender_id.toString(),
      text: m.content,
      timestamp: m.created_at,
      isRead: !!m.read_at,
      isFavorite: !!m.is_favorite,
      attachments: m.attachments || []
    };
  }
}
