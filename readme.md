# Mini Messaging App (Laravel + React; 🚧 Portfolio Project)

---

A real-time messaging app built on Laravel and a modern React frontend. Supports direct messages, channels (public/private), role-based channel membership, and profile management for both users and
channels.

> **Status:** This project is currently a work-in-progress. Core backend architecture is stable, but the frontend is undergoing a complete overhaul. <br>
> Several features (file attachments, blocking) are scheduled for future updates.

## Tech Stack

**Backend**

- PHP 8.2+ / Laravel 12
- Sanctum (SPA + token auth)
- PostgreSQL (production/testing) / SQLite (local dev)
- PHPUnit (feature + unit tests)

**Frontend**

- React 19 + Vite
- React Router v7
- Tailwind CSS
- Axios

## Features

- **Authentication** — registration, login, logout via Laravel Sanctum tokens, with rate-limited auth endpoints.
- **Unified Profile System** — both `User` and `Channel` models share a polymorphic `Profile` (handle, picture). This allows DMs, channels, and future entities (like bots) to use the exact same
  messaging logic.
- **Smart Conversations (DMs)** — automatically created when the first message is sent. Supports per-side hiding (each user can hide the chat without deleting it for the other). The system
  automatically cleans up conversations only when both participants have removed it and no messages remain.
- **Channels** — public/private visibility, open or invite-only joining, role-based membership (`owner`, `admin`, `member`) with pending/approved/blocked/left states.
- **Message lifecycle** — supports per-side hide, a 2-hour edit window, sender-only deletion before receiver reads, and automatic cleanup when hidden by both parties.
- **Authorization** — Laravel Policies enforce access control on messages, channels, profiles, and channel membership at every layer.

## Project Structure

```
.
├── api/        # Laravel backend (REST API)
│   ├── app/
│   │   ├── Http/Controllers/   # Thin controllers, delegate to Services
│   │   ├── Services/           # Business logic (ChannelService, MessageService, ConversationService)
│   │   ├── Policies/           # Authorization rules
│   │   ├── Models/
│   │   └── Enums/              # Backed enums for type-safe constants (roles, statuses, types)
│   └── tests/Feature/          # Feature tests per domain (Auth, Channel, Conversation, Message)
└── client/     # React frontend (Vite)
└── src/
├── components/         # Shared + app-scoped components
├── pages/               # Route-level views
├── layouts/             # PublicLayout shells
└── context/             # Auth + Theme providers
```

## Getting Started

### Backend

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
# Ensure your .env has DB_CONNECTION=pgsql and valid credentials
php artisan migrate
php artisan serve
```

### Frontend

```bash
cd client
npm install
cp .env.example .env   # set VITE_API_URL to your API base URL
npm run dev
```

### Running Tests

```bash
cd api
composer test
```

---

## API Overview

All routes are versioned under `/api/v1`. Key endpoint groups:

| Resource           | Routes                                                                                   |
|--------------------|------------------------------------------------------------------------------------------|
| Auth               | `POST /register`, `POST /login`, `POST /logout`, `GET /me`                               |
| Profiles           | `GET /p/{handle}`, `PUT /p/{id}`                                                         |
| Messages           | `GET /p/{handle}/messages`, `POST /message`, `PUT /message/{id}`, `DELETE /message/{id}` |
| Conversations      | `GET /conversations/my`, `POST /conversations/{id}/hide`                                 |
| Channels           | `POST /channel`, `GET /channel/{id}`, `PUT /channel/{id}`, `DELETE /channel/{id}`        |
| Channel Membership | `POST /channel/{id}/join`, `/invite`, `/kick`, `/block`, `DELETE /leave`                 |

---

## Roadmap / Known TODOs

- [X] **Real-time Broadcasting** — WebSockets (Laravel Reverb) integration is planned.
- [ ] **Frontend Overhaul** — Complete redesign of the UI/UX (currently in progress).
- [ ] **Message Attachments** — Model exists, upload flow not wired.
- [ ] **Profile Pictures** — Cropping with specified location and zoom.
- [ ] **Channel Search** — (`ChannelController::index`, `ProfileController::index` are stubs)
- [ ] **Blocking Users** — Blocked-user message restriction (`MessageController::store` has a TODO for sender-blocked check)

## License

Personal portfolio project — not licensed for redistribution.
