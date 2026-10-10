# Mini Messaging App

**Laravel 12 + React 19 · 🚧 Portfolio Project**

A real-time messaging app: direct messages, public/private groups and broadcast channels, role- and status-based membership, and a unified profile system shared by users and channels. Built to demonstrate backend engineering practices, not a toy CRUD demo.

> **Status:** The backend is feature-complete, covered by feature tests, and Docker-containerized. The frontend is mid-build (auth flow and the live inbox work; the chat view and real-time UI are next). CI is not set up yet.

---

## Tech Stack

**Backend**
- PHP 8.4+ / Laravel 12
- Laravel Sanctum (token auth)
- PostgreSQL 16 / Redis 7
- Laravel Reverb (WebSocket broadcasting)
- PHPUnit (feature tests; event classes tested in isolation)
- Intervention Image v4 (profile picture processing)

**Frontend**
- React 19 + Vite
- React Router v7
- TanStack React Query (server state)
- Tailwind CSS
- Laravel Echo + Pusher JS (Reverb client, not wired yet)

**Infra**
- Docker Compose: `app`, `queue`, `reverb`, `postgres`, `redis`, each with health checks

---

## Features

- **Authentication**: registration, login, logout via Sanctum tokens; rate-limited auth endpoints; forgot/reset password flow.
- **Unified profile system**: `User` and `Channel` share a polymorphic `Profile` (handle, pictures), so DMs, groups and channels all run through the same messaging logic.
- **Conversations (DMs)**: created on the first message. Each side can hide a conversation independently; it is removed only when both sides have hidden it.
- **Groups and channels**: `type` is `group` (members can post) or `channel` (only owner/admins post), with `public`/`private` visibility and a `join_mode` of `open`, `approval_needed` or `closed`.
- **Membership lifecycle**: roles (`owner`, `admin`, `member`) and statuses (`approved`, `pending`, `invited`, `left`, `blocked`). Rejoining reuses the existing membership row, and blocked users can never rejoin.
- **Messages**: per-side hide, a 2-hour edit window, sender-only revoke before the receiver reads it, read receipts, and per-chat unread counts where replying marks the conversation as read.
- **Inbox**: one paginated feed of conversations and channels sorted by last activity, with unread counts, built from a single `UNION ALL` query.
- **Real-time events**: `message.sent`, `message.read` and `user.typing` over private Reverb channels scoped per conversation/channel. Read receipts are skipped for broadcast channels.
- **Authorization**: Laravel Policies and model-level rules, with the acting profile passed explicitly (no hidden request state in models or policies).

### Who can read and write

Rules for groups, pinned by a data-provider test (`tests/Feature/Channel/MemberActionTest.php`):

| Your membership | Public group | Private group |
|---|---|---|
| No row, `left`, `pending` | read and post | no access |
| `approved`, `invited` | read and post | read and post |
| `blocked` | no access | no access |

- Public groups are open to everyone except blocked users. Joining is only needed for live updates and the inbox.
- Live updates (WebSocket subscription) and typing indicators require an active membership (`approved` or `invited`).
- In broadcast channels, only the owner and admins can post.

---

## Engineering Notes

- **Thin controllers, services for business logic**: `MessageService`, `ChannelService`, `ConversationService`.
- **Enum-cast model attributes** (`type`, `visibility`, `status`, `role`, `messageable_type`) instead of string comparisons.
- **Hermetic tests**: broadcast events are faked in tests that send messages, so the suite does not need Reverb running.
- **Event classes are tested directly** (channel names, payload shape) and **query-count tests** guard against redundant queries in hot paths such as typing and message broadcasts.
- **Lazy loading is prevented** (`Model::preventLazyLoading()`), so N+1 queries fail loudly in development and tests.
- **Mass assignment is guarded**: untrusted request fields cannot override server-owned fields when creating channels.

---

## Project Structure

```
.
├── docker-compose.yml           # app, queue, reverb, postgres, redis
├── api/                         # Laravel backend (REST API)
│   ├── app/
│   │   ├── Http/Controllers/    # Thin controllers
│   │   ├── Services/            # ChannelService, MessageService, ConversationService
│   │   ├── Policies/            # Authorization rules
│   │   ├── Events/              # MessageSent, MessageRead, UserTyping
│   │   ├── Models/
│   │   └── Enums/               # Backed enums (roles, statuses, types, join modes)
│   └── tests/
│       ├── Concerns/            # Shared fixtures (channels, conversations)
│       └── Feature/             # Auth, Channel/, Chat, Conversation, Events/, Message, Profile, ...
└── client/                      # React frontend (Vite)
    └── src/
        ├── api/                 # Axios client + endpoint wrappers
        ├── components/          # ui/, layouts/, skeletons/, icons/
        ├── context/             # Auth + Theme providers
        ├── hooks/               # React Query hooks
        ├── pages/               # Route-level views
        ├── routes/              # Centralized route definitions
        └── utils/
```

---

## Getting Started

### Option A: Docker (recommended)

```bash
cp .env.example .env
cp api/.env.example api/.env
docker compose up --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

- API: `http://localhost:${APP_PORT:-8000}`
- Reverb (WebSocket): `${REVERB_PORT:-8080}`

The frontend is not containerized yet; run it separately (see below).

### Option B: Manual

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

The test suite needs PostgreSQL (`chat_test` database) but not Reverb.

---

## API Overview

All routes are versioned under `/api/v1`.

| Resource | Routes |
|---|---|
| Auth | `POST /register`, `POST /login`, `POST /logout`, `GET /me`, `POST /forgot-password`, `POST /reset-password` |
| Profiles | `GET /p/{handle}`, `PUT /p/{id}`, `POST /p/{handle}/pictures`, `DELETE /p/pictures/{uuid}` |
| Messages | `GET /p/{handle}/messages`, `POST /message`, `PUT /message/{id}`, `DELETE /message/{id}` (hide), `DELETE /message/{id}/revoke`, `POST /message/{id}/read` |
| Conversations | `POST /conversations/{id}/hide` |
| Channels | `POST /channel`, `GET /channel/{id}`, `PUT /channel/{id}`, `DELETE /channel/{id}` |
| Channel membership | `GET /channel/{id}/members`, `POST /channel/{id}/join`, `DELETE /channel/{id}/leave`, `POST /channel/{id}/block` |
| Inbox | `GET /chats/my`: unified conversations + channels, sorted by last activity |
| Real-time | `POST /user-typing`, plus Reverb private channels `conversation.{id}` and `channel.{id}` |

---

## Roadmap

**Done**
- [x] Real-time broadcasting (Reverb) with tested event classes
- [x] Docker Compose infra with health-checked services
- [x] Access rules per membership status, pinned by a matrix test
- [x] Unread counts, read receipts, inbox ordering by activity

**Next**
- [ ] CI pipeline (GitHub Actions): build the API image and run PHPUnit on push/PR to `main`
- [ ] Frontend chat view + real-time UI (Echo wired to Reverb)

**Backlog**
- [ ] **Invitations**: a separate `channel_invitations` table (inviter, invitee, status: pending/accepted/declined/expired); a membership row is created only on accept. The current `invite` endpoint is a disabled stub.
- [ ] **Reports**: a generic `reports` table (reporter, subject, reason) for invite spam and later messages/users; repeated reports can revoke a manager's right to invite.
- [ ] **Secret join links** for private channels/groups (token, expiry, max uses, revocation, rate-limited)
- [ ] **Contacts**: its own feature (mutual vs one-way); invitations by handle do not depend on it
- [ ] Message attachments: model exists, upload flow not wired
- [ ] Profile picture cropping (position + zoom)
- [ ] Channel and profile search (`ChannelController::index`, `ProfileController::index` are stubs)
- [ ] Block users in DMs (sender-blocked check in `MessageController::store`)
- [ ] Admin panel (app-level roles, moderation)

## License

Personal portfolio project, not licensed for redistribution.