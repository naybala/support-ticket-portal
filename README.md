# Support Ticket Portal

A practical, secure, and maintainable Support Ticket Portal built with **Laravel 13**, **Inertia.js**, and **Vue 3**.

This project provides a robust support ticket system with distinct views for **Clients** and **Support Agents**. Clients can create and track tickets for their organization, while Support Agents can manage, update, assign tickets, and communicate using public comments or private internal notes.

---

## 🛠️ System Architecture

The application uses the **Laravel + Inertia.js (Vue 3) + Tailwind CSS** stack. This architecture delivers a modern SPA-like experience without building or maintaining a decoupled REST API.

### Why This Stack?

- **Laravel + Inertia.js**: No need for a separate API, OAuth tokens, or duplicated validation rules. Controller logic directly dictates what data reaches the Vue component. This is the correct tool for a monolithic team-owned portal.
- **Vue 3 Composition API**: All page logic is extracted into composables (`useAgentTickets.js`, `useTicketShow.js`, etc.), keeping each `.vue` file focused purely on markup. Logic files are independently reusable and testable.
- **Service Layer (`SlaService`)**: SLA calculation is decoupled from both controllers and models into a dedicated service. This makes the business rules easy to locate, test, and extend independently.

### Database Schema

| Table           | Key Columns                                               | Purpose                                                       |
| --------------- | --------------------------------------------------------- | ------------------------------------------------------------- |
| `organizations` | `id`, `name`                                              | Groups client users; tickets are scoped to an org             |
| `users`         | `role` (enum: client\|agent), `organization_id`           | Role drives all authorization decisions                       |
| `tickets`       | `status`, `priority`, `sla_due_at`, `assigned_to_user_id` | Core domain entity with SLA tracking                          |
| `comments`      | `is_internal` (boolean)                                   | Single table for public replies and internal-only agent notes |

---

## 🔐 Authorization Model

Authorization is governed by Laravel **Policies** (`TicketPolicy`) to prevent unauthorized access and data leakage. All checks use `Gate::authorize()` — no raw `abort(403)` calls scattered through controllers.

### Role: `client`

- Can only **view, comment on, and create** tickets belonging to their own organization (`organization_id` must match).
- Cannot see tickets from other organizations — `TicketPolicy::view` enforces this with a hard 403.
- Cannot see agent **internal notes** — the controller actively filters `where('is_internal', false)` before sending data to the Vue page.
- Cannot access any `/agent/*` routes — `TicketPolicy::viewAny` returns `false` for clients.

### Role: `agent`

- Can **view and update** all tickets across all organizations.
- Can post **public replies** (visible to clients) or **internal notes** (agent-only) on any ticket.
- Can change ticket **status**, **priority** (which recalculates the SLA deadline), and **assignee**.

---

## ⏳ SLA Rules

SLA deadlines are calculated by `SlaService` at ticket creation and **recalculated from the moment of the change** if an agent upgrades or downgrades priority.

| Priority   | SLA Deadline | Notes                                        |
| ---------- | ------------ | -------------------------------------------- |
| **High**   | **4 hours**  | For production outages and critical blockers |
| **Normal** | **24 hours** | For standard support requests                |
| **Low**    | **72 hours** | For general inquiries and feature questions  |

### SLA State (computed, not stored)

The `sla_state` is a model accessor appended to every serialized ticket. This avoids storing derived state and the risk of it going stale.

```php
// app/Models/Ticket.php
public function getSlaStateAttribute()
{
    if (now()->greaterThan($this->sla_due_at)) return 'overdue';
    if (now()->diffInHours($this->sla_due_at) <= 2) return 'due_soon';
    return 'on_track';
}
```

The Vue components use this field directly to render color-coded badges (red + pulse animation for overdue, orange for due soon, green for on track).

---

## ✅ Test Coverage

The project ships with **11 feature tests (36+ assertions)** covering the most critical security and business-logic paths.

```bash
./vendor/bin/phpunit
```

| Test                                                    | What it verifies                                                  |
| ------------------------------------------------------- | ----------------------------------------------------------------- |
| `test_client_cannot_access_another_organization_ticket` | Policy returns 403 for cross-org ticket access                    |
| `test_client_cannot_see_internal_comments`              | Client endpoint filters out `is_internal = true` comments         |
| `test_agent_can_see_internal_notes_that_clients_cannot` | Agent sees all; client sees zero — same ticket, same DB           |
| `test_high_priority_creates_4h_sla`                     | `SlaService` sets `sla_due_at` to now + 4h on creation            |
| `test_normal_priority_ticket_has_24h_sla`               | `SlaService` sets `sla_due_at` to now + 24h on creation           |
| `test_low_priority_ticket_has_72h_sla`                  | `SlaService` sets `sla_due_at` to now + 72h on creation           |
| `test_agent_priority_change_recalculates_sla`           | Changing priority via PATCH resets `sla_due_at` from current time |
| `test_client_cannot_access_agent_dashboard`             | `viewAny` policy blocks clients from `/agent/tickets`             |
| `test_unauthenticated_user_is_redirected_to_login`      | Auth middleware redirects guests to login                         |
| `test_agent_can_filter_tickets_by_organization`         | `agentIndex` `when()` chains filter correctly by org              |
| `test_agent_can_search_tickets`                         | Full-text search across `title` and `description` works           |

---

## ⏱️ Timebox Decisions

**Time spent: approximately 8 hours**, distributed over three days.

### How I scoped the work

I used the first 30 minutes to plan the domain model and authorization strategy before writing any code. From that point, I applied a strict "security-first" order of priorities:

1. **Authorization correctness** — Data isolation between organizations is non-negotiable; a bug here is a breach.
2. **Clean domain modeling** — `SlaService`, computed `sla_state`, and `TicketPolicy` each own one concern, making the system easy to reason about and test.
3. **SLA implementation** — Core business requirement, fully tested across all 3 priority tiers.
4. **Maintainable frontend** — Vue composables (`useTickets.js`, `useAgentTickets.js`, etc.) separate UI markup from logic so the codebase scales cleanly.
5. **Form request consistency** — `StoreTicketRequest`, `StoreCommentRequest`, `StoreAgentCommentRequest`, and `UpdateTicketRequest` keep validation out of controller methods.
6. **Test coverage** — Written last but scoped to the highest-risk paths: policy enforcement, comment visibility, and SLA calculation.

### Deliberately left out within the timebox

| Feature                               | Reason skipped                                                                                                                   |
| ------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| Real-time WebSocket notifications     | Nice-to-have; adding Reverb/Pusher                                                                                               |
| Email notifications on agent reply    | Requires queue/mail driver setup                                                                                                 |
| File/screenshot attachments           | Storage driver configuration and UI complexity                                                                                   |
| Full audit log / state-change history | Valuable for production; deferred to Next Steps                                                                                  |
| Pagination UI in Vue                  | Backend already paginates with `->paginate(15)`; wiring up Inertia pagination links was deprioritized in favour of test coverage |
| Frozen SLA on ticket close            | SLA state continues computing after resolution; known limitation documented below                                                |

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- Node.js & NPM
- MySQL

### Installation

1. **Clone the repository and install dependencies:**

    ```bash
    composer install
    npm install
    ```

2. **Configure environment:**
   Copy `.env.example` to `.env` and set your database credentials (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

3. **Migrate and seed the database:**

    ```bash
    php artisan migrate:fresh --seed
    ```

    _Seeding creates demo logins:_
    - Clients: `client1@gmail.com`, `client2@gmail.com` — password: `password`
    - Agents: `agent1@gmail.com`, `agent2@gmail.com` — password: `password`

4. **Build assets:**

    ```bash
    npm run build
    ```

5. **Start the development server:**

    ```bash
    php artisan serve
    ```

6. **Run the test suite:**
    ```bash
    ./vendor/bin/phpunit
    ```

---

## 🔮 Next Steps — Path to Production

Given more time, these are the natural next phases in priority order:

### Phase 1 — Operational Reliability

- **SLA Breach Alerts**: A scheduled job (`php artisan schedule:run`) checking for tickets nearing `sla_due_at` and automatically escalating priority or notifying the assigned agent via email/Slack.
- **Real-time Notifications**: WebSocket events (via Laravel Reverb or Pusher) to push live updates to the agent dashboard when new tickets are created or comments posted.
- **Queue-backed Jobs**: Move email notifications and audit writes off the request/response cycle using Laravel Queues.

### Phase 2 — Data & Audit

- **Audit Log / Activity Timeline**: A `ticket_activities` table recording every status, priority, and assignee change with timestamp and actor. Displayed as a timeline on the ticket detail page.
- **Frozen SLA on Close**: When a ticket is resolved/closed, freeze (snapshot) the `sla_due_at` so the SLA state doesn't keep changing post-resolution.

### Phase 3 — Scale & Extensibility

- **Pagination UI**: Both listing endpoints already use `->paginate(15)` — the Vue components need updating to render Inertia pagination links for large datasets.
- **Advanced Search**: Integrate Laravel Scout (with Meilisearch or Typesense) to replace the current `LIKE` query with full-text ranked search.
- **Granular Permissions**: Replace the simple `role` enum with `spatie/laravel-permission` for multi-tier roles (e.g., supervisor, L1/L2 agent, admin).
- **File Attachments**: Allow screenshots and documents on tickets/comments using Laravel Media Library backed by S3 or local disk.
- **API Layer**: Expose a versioned JSON API (`/api/v1/tickets`) with Laravel Sanctum for third-party integrations.

---

## ⚠️ Known Limitations & Conscious Shortcuts

These are trade-offs I made deliberately within the timebox, not oversights:

| Limitation                                            | Impact                                                                     | What I'd do with more time                                                                |
| ----------------------------------------------------- | -------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------- |
| **SLA doesn't freeze on ticket close**                | A resolved ticket will eventually show "overdue" if left long enough       | Snapshot `sla_due_at` and `sla_state` at resolution time into a `closed_sla_state` column |
| **Pagination UI not wired up**                        | Agent/client list views fetch page 1 only in practice                      | Add Inertia `<Link>` pagination component; backend is already ready                       |
| **No email/notification on events**                   | Agents aren't alerted to new tickets; clients don't know when agents reply | Add Laravel Mail + queued jobs behind a `TicketCommented` event                           |
| **`LIKE` search instead of full-text**                | Search degrades on large datasets and doesn't rank results                 | Replace with Laravel Scout + Meilisearch                                                  |
| **Single `role` enum (client\|agent)**                | No concept of admin, supervisor, or L2 agent                               | Migrate to `spatie/laravel-permission` for fine-grained roles                             |
| **No CSRF-aware rate limiting on comment submission** | A client could spam comments without throttling                            | Add `ThrottleRequests` middleware to write routes                                         |

---

## 🤔 Parts I'm Not Happy With

**1. `TicketController` is doing double duty.**
Both the client and agent ticket actions live in one controller, growing it to ~200 lines. The cleaner long-term approach is a dedicated `Agent\TicketController` under a separate namespace with its own middleware group, making the routing and authorization intent explicit at a glance.

**2. SLA state is recomputed on every model serialization.**
The `getSlaStateAttribute()` accessor runs on every JSON response. For a high-volume system this should be a scheduled background job that writes `sla_state` to the DB and uses a DB index — both for performance and to enable server-side filtering by SLA state.

**3. The agent `agentIndex` redirect in the client `index` method is a code smell.**
The `if (auth()->user()->role === 'agent') { return redirect()... }` guard inside the client controller action is a side-effect. This should be enforced at the middleware/route level instead, keeping the controller method focused on a single responsibility.

**4. Test database uses SQLite in-memory.**
SQLite doesn't enforce foreign key constraints by default and has subtle SQL dialect differences from MySQL. Ideally the test suite runs against a dedicated MySQL test database to catch real-world constraint and query issues.
