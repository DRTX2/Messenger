import { Injectable, signal } from '@angular/core';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root'
})
export class WebSocketService {
  private echo: any;
  
  readonly isConnected = signal(false);
  readonly connectionError = signal<string | null>(null);

  constructor() {
    // Defer initialization to avoid blocking app startup
  }

  /**
   * Initialize WebSocket connection
   * Should be called after user is authenticated
   */
  async connect(): Promise<void> {
    if (this.echo) {
      return; // Already connected
    }

    try {
      const Echo = (await import('laravel-echo')).default;
      const Pusher = (await import('pusher-js')).default;

      // Expose Pusher globally (required by Laravel Echo)
      (window as any).Pusher = Pusher;

      this.echo = new Echo({
        broadcaster: 'reverb',
        key: environment.reverbAppKey,
        wsHost: environment.wsHost,
        wsPort: environment.wsPort,
        wssPort: environment.wsPort,
        forceTLS: environment.production,
        disableStats: true,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${environment.apiUrl}/broadcasting/auth`,
        auth: {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
            Accept: 'application/json'
          }
        }
      });

      this.isConnected.set(true);
      this.connectionError.set(null);
      console.log('[WebSocket] Connected successfully');

    } catch (error) {
      this.connectionError.set('Failed to connect to WebSocket server');
      console.error('[WebSocket] Connection error:', error);
    }
  }

  /**
   * Disconnect WebSocket
   * Should be called on logout
   */
  disconnect(): void {
    if (this.echo) {
      this.echo.disconnect();
      this.echo = null;
      this.isConnected.set(false);
      console.log('[WebSocket] Disconnected');
    }
  }

  /**
   * Subscribe to a private conversation channel
   */
  listenToConversation(
    conversationId: number, 
    onMessage: (message: any) => void,
    onTyping?: (data: { user_id: number; user_name: string; is_typing: boolean }) => void
  ): void {
    if (!this.echo) {
      console.warn('[WebSocket] Not connected. Call connect() first.');
      return;
    }

    const channel = this.echo.private(`conversation.${conversationId}`);
    
    channel.listen('MessageSent', (event: any) => {
      console.log('[WebSocket] Message received:', event);
      onMessage(event.message);
    });

    if (onTyping) {
      channel.listen('.UserTyping', (event: any) => {
        console.log('[WebSocket] Typing indicator:', event);
        onTyping(event);
      });
    }
  }

  /**
   * Unsubscribe from a conversation channel
   */
  leaveConversation(conversationId: number): void {
    if (!this.echo) return;
    this.echo.leave(`conversation.${conversationId}`);
  }

  /**
   * Subscribe to user's private channel for notifications
   */
  listenToUserChannel(userId: number, callback: (event: any) => void): void {
    if (!this.echo) return;

    this.echo.private(`App.Models.User.${userId}`)
      .notification((notification: any) => {
        console.log('[WebSocket] Notification:', notification);
        callback(notification);
      });
  }
}
