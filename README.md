# Nexus Messenger - Enterprise Edition

An advanced real-time messaging application built with **Angular 19** and **Laravel 12** with native WebSocket support.

![Dashboard](docs/imgs/Screenshot_20260106_001740.png)

## 🚀 Description

Nexus is a professional full-stack messaging platform designed for high-performance team communication. It features a stunning, premium UI with real-time synchronization, secure authentication, and a scalable microservices-ready architecture.

## ✨ Key Features

-   **⚡ Real-time Communication**: Native WebSocket integration using **Laravel Reverb** for instant message delivery and typing indicators.
-   **📁 Enterprise File Sharing**: Support for multiple file attachments with a sleek glassmorphism design and image previews.
-   **👥 Group Messaging**: Create and manage group conversations seamlessly.
-   **🔐 Robust Authentication**: JWT-based security with automatic token management and protected routing.
-   **⭐ Message Experience**:
    *   **Favorite Messages**: Star important content for quick reference.
    *   **Auto-scroll**: Intelligent scrolling that keeps you at the latest message.
    *   **Custom Scrollbars**: Minimalist, non-intrusive design for a premium feel.
    *   **Message Actions**: Delete messages or clear entire chats with one click.
-   **🎨 Premium UI/UX**: Built with Tailwind CSS v4, featuring a modern dark mode, smooth transitions, and a mobile-first responsive layout.

## 🏗️ Architecture

### Frontend
-   **Framework**: Angular 19+ (Signals-based reactive state).
-   **Styling**: Tailwind CSS v4 + Vanilla CSS for custom animations.
-   **Communication**: RxJS for asynchronous flows and WebSocketService for real-time events.
-   **Features**: Modular architecture by features (Chat, Auth, Shared).

### Backend
-   **Framework**: Laravel 12.
-   **Real-time Server**: Laravel Reverb (WebSockets).
-   **Database**: PostgreSQL.
-   **Auth**: JWT (JSON Web Tokens) with standard API protection.
-   **DX Tools**: Custom Artisan commands for full-stack development.

## 🛠️ Installation

### Prerequisites
-   Docker & Docker Compose
-   Or manually: PHP 8.2+, Node.js 20+, PostgreSQL.

### Using Docker (Recommended)

1.  **Clone & Start:**
    ```bash
    git clone https://github.com/yourusername/messenger.git
    cd messenger
    docker-compose up -d --build
    ```

2.  **Setup Backend:**
    ```bash
    docker-compose exec backend composer install
    docker-compose exec backend php artisan migrate
    docker-compose exec backend php artisan jwt:secret
    ```

### Manual Installation (Development)

#### 1. Backend Setup
```bash
cd backend
composer install
cp .env.example .env # Configure your DB
php artisan key:generate
php artisan migrate
```

#### 2. Frontend Setup
```bash
cd frontend
npm install
```

#### 3. Run Everything
We've made development easier with a single command that starts both the API and WebSockets:
```bash
cd backend
php artisan serve:all
```
Then, in another terminal:
```bash
cd frontend
npm start
```

## 📸 Screenshots

| Empty Chat State | Active Chat View |
|-----------|-------------|
| ![Chat](docs/imgs/Screenshot_20260106_001740.png) | ![Mobile](docs/imgs/Screenshot_20260106_001800.png) |

## 🚀 Recent Improvements (Jan 2026)
-   ✅ **Transition to WebSockets**: Migrated from polling to **Laravel Reverb**.
-   ✅ **Enterprise Logic**: Added `Attachments` and `Conversations` models for complex data structures.
-   ✅ **UI Polishing**: Implemented custom scrollbars, auto-scroll logic, and fixed navigation artifacts.
-   ✅ **DX Commands**: Added `php artisan serve:all` to orchestrate multi-server development.

## 🛠️ Development Tools

-   **Frontend-only**: `docker-compose -f docker-compose.frontend.yml up`
-   **Backend-only**: `docker-compose -f docker-compose.backend.yml up`
