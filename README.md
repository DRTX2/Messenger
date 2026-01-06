# Messenger Project

An advanced real-time messaging application built with Angular and Laravel (PostgreSQL).

![Dashboard](docs/imgs/Screenshot_20260106_001740.png)

## Description

This project is a full-stack messaging platform that enables users to communicate in real-time. It features a modern, responsive UI inspired by top-tier messaging apps, complete with authentication, secure API communication, and dynamic interactions.

## Features

-   **Authentication**: Secure Login and Registration using JWT (JSON Web Tokens).
-   **Conversation List**: View all available users and recent message snippets.
-   **Real-time Chat**: Send and receive messages instantly (polling mechanism currently, expandable to WebSockets).
-   **Message Management**:
    -   Delete specific messages.
    -   Clear entire conversations.
    -   Mark messages as "Favorite" (starred).
-   **User Presence**: Simulated Online/Offline status.
-   **Responsive Design**: Mobile-first approach using Tailwind CSS.
-   **Keyboard Shortcuts**: Press `ESC` to close the active chat.

## Architecture

The project follows a decoupled architecture separating the client and server logic:

### Frontend
-   **Framework**: Angular 19+ (Signals for state management).
-   **Styling**: Tailwind CSS v4.
-   **HTTP Client**: Angular `HttpClient` with Interceptors for JWT handling.
-   **Structure**:
    -   `AuthService`: Manages user sessions.
    -   `ChatService`: API interaction for messages and users.

### Backend
-   **Framework**: Laravel 11.
-   **Database**: PostgreSQL.
-   **Authentication**: `laravel/sanctum` and `php-open-source-saver/jwt-auth`.
-   **API**: RESTful endpoints for Chat and Auth.

## Installation

### Prerequisites
-   Docker & Docker Compose
-   Or manually: PHP 8.2+, Node.js 20+, PostgreSQL.

### Using Docker (Recommended)

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/yourusername/messenger.git
    cd messenger
    ```

2.  **Start the application:**
    ```bash
    docker-compose up -d --build
    ```
    This will start the Database, Backend (on port 8000), and Frontend (on port 4200).

3.  **Setup Backend:**
    Access the backend container to run migrations:
    ```bash
    docker-compose exec backend composer install
    docker-compose exec backend php artisan migrate
    docker-compose exec backend php artisan jwt:secret
    ```

4.  **Access the App:**
    Open `http://localhost:4200` in your browser.

### Manual Installation

#### Backend
1.  Navigate to `backend/`.
2.  Copy `.env.example` to `.env` and configure your Database.
3.  Run `composer install`.
4.  Run `php artisan key:generate`.
5.  Run `php artisan migrate`.
6.  Start server: `php artisan serve`.

#### Frontend
1.  Navigate to `frontend/`.
2.  Run `pnpm install`.
3.  Start dev server: `pnpm start`.

## Screenshots

| Chat View | Mobile View |
|-----------|-------------|
| ![Chat](docs/imgs/Screenshot_20260106_001740.png) | ![Mobile](docs/imgs/Screenshot_20260106_001800.png) |

## Development

-   **Frontend-only**: `docker-compose -f docker-compose.frontend.yml up`
-   **Backend-only**: `docker-compose -f docker-compose.backend.yml up`
