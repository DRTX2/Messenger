import { User } from './user.model';

export interface Attachment {
  id: number;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  url: string;
}

export interface Message {
  id: number;
  sender_id: number;
  receiver_id?: number;
  conversation_id?: number;
  content: string;
  created_at: string;
  read_at?: string;
  sender?: User;
  receiver?: User;
  is_favorite?: boolean;
  attachments?: Attachment[];
}

// UI-friendly message format
export interface UIMessage {
  id: number;
  senderId: string; // 'me' or actual ID
  text: string;
  timestamp: string;
  isRead: boolean;
  isFavorite: boolean;
  attachments: Attachment[];
}
