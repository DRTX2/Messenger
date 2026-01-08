import { User, ConversationParticipant } from './user.model';
import { Message } from './message.model';

export interface Conversation {
  id: number;
  uuid: string;
  is_group: boolean;
  name?: string;
  avatar_url?: string;
  created_by?: number;
  last_message_at: string;
  messages_count?: number;
  participants: ConversationParticipant[];
  latest_message?: Message;
  created_at?: string;
}

// UI-friendly conversation format for sidebar
export interface UIConversation {
  id: number;
  isGroup: boolean;
  participantCount: number;
  user: {
    id: number;
    name: string;
    avatar: string;
    status: 'online' | 'offline' | 'away';
  };
  lastMessage: {
    text: string;
    timestamp: Date;
    isRead: boolean;
    senderId: string;
  };
  unreadCount: number;
}
