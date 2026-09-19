# Mini Messaging App

**Laravel 12 + React 19 · 🚧 Portfolio Project**

A real-time messaging app: direct messages, public/private channels, role-based membership, and a unified profile system shared between users and channels. Built to demonstrate backend engineering practices — not a toy CRUD demo.

> **Status:** Backend is feature-complete and Docker-containerized. Frontend is mid-rewrite (auth flow + conversation list are live; chat view and real-time UI are next).

---

## Tech Stack

**Backend**
- PHP 8.4+ / Laravel 12
- Laravel Sanctum (token auth)
- PostgreSQL 16 (all environments, via Docker) / Redis 7
- Laravel Reverb (WebSocket broadcasting)
- PHPUnit (feature + unit tests)
- Intervention Image v4 (profile picture processing)

**Frontend**
- React 19 + Vite
- React Router v7
- TanStack React Query (server state / data fetching)
- Tailwind CSS
- Laravel Echo + Pusher JS (Reverb client)

**Infra**
- Docker Compose — `app`, `queue`, `reverb`, `postgres`, `redis`, each with health checks

---

## Features

- **Authentication** — registration, login, logout via Sanctum tokens, with rate-limited auth endpoints and forgot/reset password flow.
- **Unified Profile System** — `User` and `Channel` share a polymorphic `Profile` (handle, picture), so DMs, channels, and future entities (bots, etc.) run through the exact same messaging logic.
- **Smart Conversations (DMs)** — auto-created on first message. Each side can hide a conversation independently; it's only cleaned up once *both* participants have removed it and no messages remain.
- **Channels** — public/private visibility, open or invite-only joining, role-based membership (`owner`, `admin`, `member`) with pending/approved/blocked/left states.
- **Message lifecycle** — per-side hide, a 2-hour edit window, sender-only deletion before the receiver reads it, automatic cleanup when hidden by both parties.
- **Real-time events** — Laravel Reverb broadcasts `message.sent`, `user.typing`, and `message.read` over private channels scoped per conversation/channel.
- **Authorization** — Laravel Policies enforce access control on messages, channels, profiles, and channel membership at every layer.
- **Frontend (in progress)** — auth pages, app shell/layouts, and a live sidebar rendering real conversations with React Query + skeleton loading states.

---

## Project Structure

```
.
├── docker-compose.yml           # app, queue, reverb, postgres, redis
├── api/                         # Laravel backend (REST API)
│   ├── app/
│   │   ├── Http/Controllers/    # Thin controllers, delegate to Services
│   │   ├── Services/            # Business logic (ChannelService, MessageService, ConversationService)
│   │   ├── Policies/            # Authorization rules
│   │   ├── Models/
│   │   └── Enums/               # Backed enums for type-safe constants (roles, statuses, types)
│   └── tests/Feature/           # Feature tests per domain (Auth, Channel, Conversation, Message)
└── client/                      # React frontend (Vite)
    └── src/
        ├── api/                 # Axios client + endpoint wrappers
        ├── components/          # ui/, layouts/, skeletons/, icons/
        ├── context/             # Auth + Theme providers
        ├── hooks/               # React Query hooks (e.g. useConversations)
        ├── pages/               # Route-level views (auth/, Home)
        ├── routes/              # Centralized ROUTES path definitions
        └── utils/
```

---

## Getting Started

### Option A — Docker (recommended)

```bash
cp .env.example .env
cp api/.env.example api/.env
docker compose up --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

- API: `http://localhost:${APP_PORT:-8000}`
- Reverb (WebSocket): `${REVERB_PORT:-8080}`

Frontend is not containerized yet — run it separately (see Option B, Frontend step).

### Option B — Manual

**Backend**
```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
# .env: set DB_CONNECTION=pgsql and point at a running Postgres instance
php artisan migrate
php artisan serve
```

**Frontend**
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
# or, against the Docker container:
docker compose exec app php artisan test
```

---

## API Overview

All routes are versioned under `/api/v1`.

| Resource           | Routes                                                                                   |
|--------------------|------------------------------------------------------------------------------------------|
| Auth               | `POST /register`, `POST /login`, `POST /logout`, `GET /me`                               |
| Profiles           | `GET /p/{handle}`, `PUT /p/{id}`                                                         |
| Messages           | `GET /p/{handle}/messages`, `POST /message`, `PUT /message/{id}`, `DELETE /message/{id}` |
| Conversations      | `POST /conversations/{id}/hide`                                                          |
| Channels           | `POST /channel`, `GET /channel/{id}`, `PUT /channel/{id}`, `DELETE /channel/{id}`        |
| Channel Membership | `POST /channel/{id}/join`, `/invite`, `/kick`, `/block`, `DELETE /leave`                 |
| Inbox              | `GET /chats/my` — unified conversations + channels feed, sorted by last activity         |

---

## Roadmap / Known TODOs

- [x] Real-time broadcasting (Laravel Reverb — `message.sent`, `user.typing`, `message.read`)
- [x] Docker Compose infra with health-checked services
- [ ] CI pipeline (GitHub Actions) — build the API image and run PHPUnit on push/PR to `main`
- [ ] Frontend chat view + real-time UI (Echo wired to Reverb)
- [ ] Message attachments — model exists, upload flow not wired
- [ ] Profile picture cropping (position + zoom)
- [ ] Channel search (`ChannelController::index`, `ProfileController::index` are stubs)
- [ ] Blocking users — sender-blocked check (`MessageController::store` has a TODO)

## License

Personal portfolio project — not licensed for redistribution.
