import { Component, signal, computed, effect, inject, HostListener } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterOutlet } from '@angular/router';
import { AuthService } from './services/auth.service';
import { ChatService, User, Message } from './services/chat.service';

interface Conversation {
  id: string; // user id as string for tracking
  user: {
      id: string;
      name: string;
      avatar: string;
      status: 'online' | 'offline' | 'away';
      lastSeen?: string;
  };
  lastMessage: {
      text: string;
      timestamp: Date;
      isRead: boolean;
      senderId: string;
  };
  unreadCount: number;
}


@Component({
  selector: 'app-root',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterOutlet],
  templateUrl: './app.html',
  styleUrl: './app.css'
})
export class App {
  authService = inject(AuthService);
  chatService = inject(ChatService);

  // Login State
  email = signal('');
  password = signal('');
  loginError = signal('');
  isRegistering = signal(false);
  name = signal(''); // For registration

  currentUser = this.authService.currentUser;
  
  // Chat State
  users = signal<any[]>([]);
  conversations = computed(() => {
    // Transform backend users to conversation format
    return this.users().map(u => ({
      id: u.id.toString(),
      user: {
        id: u.id.toString(),
        name: u.name,
        avatar: u.avatar || `https://ui-avatars.com/api/?name=${u.name}&background=random`,
        status: 'offline', // Default for now
        lastSeen: ''
      },
      lastMessage: {
        text: u.unread_messages > 0 ? `${u.unread_messages} unread messages` : 'Start a conversation',
        timestamp: new Date(u.created_at), // Placeholder
        isRead: u.unread_messages === 0,
        senderId: 'unknown'
      },
      unreadCount: u.unread_messages || 0
    }));
  });

  selectedConversationId = signal<string | null>(null);
  currentMessages = signal<any[]>([]);
  messageInput = signal('');

  selectedConversation = computed(() => 
    this.conversations().find(c => c.id === this.selectedConversationId())
  );

  constructor() {
    // Reload users when user logs in
    effect(() => {
      if (this.currentUser()) {
        this.loadUsers();
      }
    });

    // Auto-refresh messages every 5 seconds if a chat is open
    // In a real app, use WebSockets (Pusher/Laravel Echo)
    effect((onCleanup) => {
        const conversationId = this.selectedConversationId();
        if (conversationId) {
            const interval = setInterval(() => {
                this.loadMessages(conversationId);
            }, 3000);
            onCleanup(() => clearInterval(interval));
        }
    });
  }

  doLogin() {
    this.loginError.set('');
    this.authService.login({ email: this.email(), password: this.password() })
      .subscribe({
        next: () => {
          this.loadUsers();
        },
        error: (err) => {
          this.loginError.set('Invalid credentials');
          console.error(err);
        }
      });
  }

  doRegister() {
    this.loginError.set('');
    this.authService.register({ 
        name: this.name(),
        email: this.email(), 
        password: this.password(),
        password_confirmation: this.password() 
    }).subscribe({
        next: () => {
            // Auto login after register
            this.doLogin();
        },
        error: (err) => {
             this.loginError.set(err.error.message || 'Registration failed');
        }
    });
  }

  logout() {
    this.authService.logout();
    this.selectedConversationId.set(null);
  }

  loadUsers() {
    this.chatService.getUsers().subscribe({
      next: (res: any) => {
        if (res.success) {
          this.users.set(res.data);
        }
      }
    });
  }

  selectConversation(id: string) {
    this.selectedConversationId.set(id);
    this.loadMessages(id);
  }

  @HostListener('document:keydown.escape')
  onKeydownHandler() {
    if (this.selectedConversationId()) {
      this.closeChat();
    }
  }

  loadMessages(userId: string) {
      this.chatService.getMessages(+userId).subscribe({
          next: (res: any) => {
              if (res.success) {
                  // Transform messages to UI format
                  const msgs = res.data.map((m: any) => ({
                      id: m.id,
                      senderId: m.sender_id === this.currentUser().id ? 'me' : m.sender_id.toString(),
                      text: m.content,
                      timestamp: m.created_at,
                      isRead: !!m.read_at,
                      isFavorite: !!m.is_favorite
                  }));
                  this.currentMessages.set(msgs);
              }
          }
      });
  }

  sendMessage() {
    const text = this.messageInput();
    const conversationId = this.selectedConversationId();
    
    if (!text.trim() || !conversationId) return;

    this.chatService.sendMessage(+conversationId, text).subscribe({
        next: (res: any) => {
            if (res.success) {
                this.messageInput.set('');
                this.loadMessages(conversationId); // Refresh to see new message
                this.loadUsers(); // Refresh sidebar to update order or snippets
            }
        }
    });
  }
  
  clearChat() {
      const conversationId = this.selectedConversationId();
      if (!conversationId) return;

      if (confirm('Are you sure you want to clear this conversation? This cannot be undone.')) {
          this.chatService.clearConversation(+conversationId).subscribe({
              next: () => {
                  this.currentMessages.set([]);
                  this.loadUsers();
              }
          });
      }
  }

  deleteMessage(messageId: number) {
      if (confirm('Delete this message?')) {
          this.chatService.deleteMessage(messageId).subscribe({
              next: () => {
                  this.currentMessages.update(msgs => msgs.filter(m => m.id !== messageId));
              }
          });
      }
  }

  toggleFavorite(message: any) {
      this.chatService.toggleFavorite(message.id).subscribe({
          next: () => {
              this.currentMessages.update(msgs => msgs.map(m => {
                  if (m.id === message.id) {
                      return { ...m, isFavorite: !m.isFavorite };
                  }
                  return m;
              }));
          }
      });
  }

  closeChat() {
    this.selectedConversationId.set(null);
  }
}
