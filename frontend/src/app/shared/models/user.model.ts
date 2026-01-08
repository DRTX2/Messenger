// User model
export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  unread_messages?: number;
  is_online?: boolean;
  status?: 'online' | 'offline' | 'away';
  created_at?: string;
}

// For API responses that include pivot data
export interface ConversationParticipant extends User {
  pivot?: {
    is_admin: boolean;
    formatted_last_read_at: string;
  };
}
