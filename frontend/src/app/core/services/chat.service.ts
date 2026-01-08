import { Injectable, signal } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { 
  User, 
  Message, 
  Conversation, 
  Attachment 
} from '../../shared/models';

// API Response wrappers
interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
}

interface PaginatedResponse<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

@Injectable({
  providedIn: 'root'
})
export class ChatService {
  private readonly apiUrl = environment.apiUrl;

  // Loading states
  readonly isLoadingConversations = signal(false);
  readonly isLoadingMessages = signal(false);
  readonly isSending = signal(false);

  constructor(private http: HttpClient) {}

  // ──────────────────────────────────────────────────────────────
  // CONVERSATIONS
  // ──────────────────────────────────────────────────────────────

  getConversations(): Observable<ApiResponse<Conversation[]>> {
    this.isLoadingConversations.set(true);
    return this.http.get<ApiResponse<Conversation[]>>(`${this.apiUrl}/conversations`);
  }

  // ──────────────────────────────────────────────────────────────
  // MESSAGES
  // ──────────────────────────────────────────────────────────────

  getMessages(userId: number): Observable<ApiResponse<Message[]>> {
    this.isLoadingMessages.set(true);
    return this.http.get<ApiResponse<Message[]>>(`${this.apiUrl}/chat/${userId}`);
  }

  sendMessage(
    userId: number, 
    message: string, 
    attachmentIds: number[] = []
  ): Observable<ApiResponse<Message>> {
    this.isSending.set(true);
    return this.http.post<ApiResponse<Message>>(`${this.apiUrl}/chat/${userId}`, {
      message,
      attachment_ids: attachmentIds
    });
  }

  deleteMessage(messageId: number): Observable<ApiResponse<void>> {
    return this.http.delete<ApiResponse<void>>(`${this.apiUrl}/chat/messages/${messageId}`);
  }

  toggleFavorite(messageId: number): Observable<ApiResponse<Message>> {
    return this.http.post<ApiResponse<Message>>(
      `${this.apiUrl}/chat/messages/${messageId}/favorite`, 
      {}
    );
  }

  clearConversation(userId: number): Observable<ApiResponse<void>> {
    return this.http.post<ApiResponse<void>>(`${this.apiUrl}/chat/${userId}/clear`, {});
  }

  // ──────────────────────────────────────────────────────────────
  // USERS / DIRECTORY
  // ──────────────────────────────────────────────────────────────

  getUsers(search: string = ''): Observable<PaginatedResponse<User>> {
    let params = new HttpParams();
    if (search) {
      params = params.set('search', search);
    }
    return this.http.get<PaginatedResponse<User>>(`${this.apiUrl}/chat`, { params });
  }

  getUnreadCount(): Observable<ApiResponse<{ count: number }>> {
    return this.http.get<ApiResponse<{ count: number }>>(`${this.apiUrl}/chat/unread-count`);
  }

  // ──────────────────────────────────────────────────────────────
  // ATTACHMENTS
  // ──────────────────────────────────────────────────────────────

  uploadAttachment(file: File): Observable<ApiResponse<Attachment>> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<ApiResponse<Attachment>>(`${this.apiUrl}/attachments`, formData);
  }

  // ──────────────────────────────────────────────────────────────
  // GROUPS
  // ──────────────────────────────────────────────────────────────

  createGroup(
    name: string, 
    participantIds: number[], 
    avatarUrl?: string
  ): Observable<ApiResponse<Conversation>> {
    return this.http.post<ApiResponse<Conversation>>(`${this.apiUrl}/groups`, {
      name,
      participant_ids: participantIds,
      avatar_url: avatarUrl
    });
  }

  updateGroup(
    conversationId: number, 
    name?: string, 
    avatarUrl?: string
  ): Observable<ApiResponse<Conversation>> {
    return this.http.put<ApiResponse<Conversation>>(
      `${this.apiUrl}/groups/${conversationId}`, 
      { name, avatar_url: avatarUrl }
    );
  }

  addGroupParticipants(
    conversationId: number, 
    userIds: number[]
  ): Observable<ApiResponse<Conversation>> {
    return this.http.post<ApiResponse<Conversation>>(
      `${this.apiUrl}/groups/${conversationId}/participants`, 
      { user_ids: userIds }
    );
  }

  removeGroupParticipant(
    conversationId: number, 
    userId: number
  ): Observable<ApiResponse<void>> {
    return this.http.delete<ApiResponse<void>>(
      `${this.apiUrl}/groups/${conversationId}/participants/${userId}`
    );
  }

  leaveGroup(conversationId: number): Observable<ApiResponse<void>> {
    return this.http.delete<ApiResponse<void>>(
      `${this.apiUrl}/groups/${conversationId}/leave`
    );
  }

  // ──────────────────────────────────────────────────────────────
  // TYPING INDICATORS
  // ──────────────────────────────────────────────────────────────

  sendTypingIndicator(conversationId: number, isTyping: boolean = true): Observable<ApiResponse<void>> {
    return this.http.post<ApiResponse<void>>(
      `${this.apiUrl}/conversations/${conversationId}/typing`,
      { is_typing: isTyping }
    );
  }
}
