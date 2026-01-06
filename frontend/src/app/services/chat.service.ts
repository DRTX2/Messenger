import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  unread_messages?: number;
  status?: 'online' | 'offline'; // Backend doesn't support this yet fully but we can mock or extend
}

export interface Message {
  id: number;
  sender_id: number;
  receiver_id: number;
  content: string;
  created_at: string;
  sender?: User;
  receiver?: User;
}

@Injectable({
  providedIn: 'root'
})
export class ChatService {
  private apiUrl = 'http://localhost:8000/api/chat';

  constructor(private http: HttpClient) {}

  getUsers(): Observable<any> {
    return this.http.get(`${this.apiUrl}`);
  }

  getMessages(userId: number): Observable<any> {
    return this.http.get(`${this.apiUrl}/${userId}`);
  }

  sendMessage(userId: number, message: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/${userId}`, { message });
  }

  getUnreadCount(): Observable<any> {
    return this.http.get(`${this.apiUrl}/unread-count`);
  }
}
