# Support Ticket Portal

A practical, secure, and maintainable Support Ticket Portal built with **Laravel 12**, **Inertia.js**, and **Vue 3**.

This project provides a robust support ticket system with distinct views for **Clients** and **Support Agents**. Clients can create and track tickets for their organization, while Support Agents can manage, update, assign tickets, and communicate using public comments or private internal notes.

---

## 🛠️ System Architecture

The application uses the **Laravel + Inertia.js (Vue 3) + Tailwind CSS** stack. This architecture delivers a modern SPA-like experience without building or maintaining a decoupled REST API.

### Why This Stack?
- **Laravel + Inertia.js**: No need for a separate API, OAuth tokens, or duplicated validation rules. Controller logic directly dictates what data reaches the Vue component. This is the correct tool for a monolithic team-owned portal.
- **Vue 3 Composition API**: All page logic is extracted into composables (`useAgentTickets.js`, `useTicketShow.js`, etc.), keeping each `.vue` file focused purely on markup. Logic files are independently reusable and testable.
- **Service Layer (`SlaService`)**: SLA calculation is decoupled from both controllers and models into a dedicated service. This makes the business rules easy to locate, test, and extend independently.

### Database Schema

| Table | Key Columns | Purpose |
|---|---|---|
| `organizations` | `id`, `name` | Groups client users; tickets are scoped to an org |
| `users` | `role` (enum: client\|agent), `organization_id` | Role drives all authorization decisions |
| `tickets` | `status`, `priority`, `sla_due_at`, `assigned_to_user_id` | Core domain entity with SLA tracking |
| `comments` | `is_internal` (boolean) | Single table for public replies and internal-only agent notes |

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

| Priority | SLA Deadline | Notes |
|---|---|---|
| **High** | **4 hours** | For production outages and critical blockers |
| **Normal** | **24 hours** | For standard support requests |
| **Low** | **72 hours** | For general inquiries and feature questions |

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

| Test | What it verifies |
|---|---|
| `test_client_cannot_access_another_organization_ticket` | Policy returns 403 for cross-org ticket access |
| `test_client_cannot_see_internal_comments` | Client endpoint filters out `is_internal = true` comments |
| `test_agent_can_see_internal_notes_that_clients_cannot` | Agent sees all; client sees zero — same ticket, same DB |
| `test_high_priority_creates_4h_sla` | `SlaService` sets `sla_due_at` to now + 4h on creation |
| `test_normal_priority_ticket_has_24h_sla` | `SlaService` sets `sla_due_at` to now + 24h on creation |
| `test_low_priority_ticket_has_72h_sla` | `SlaService` sets `sla_due_at` to now + 72h on creation |
| `test_agent_priority_change_recalculates_sla` | Changing priority via PATCH resets `sla_due_at` from current time |
| `test_client_cannot_access_agent_dashboard` | `viewAny` policy blocks clients from `/agent/tickets` |
| `test_unauthenticated_user_is_redirected_to_login` | Auth middleware redirects guests to login |
| `test_agent_can_filter_tickets_by_organization` | `agentIndex` `when()` chains filter correctly by org |
| `test_agent_can_search_tickets` | Full-text search across `title` and `description` works |

---

## ⏱️ Timebox Decisions

> Within the available timebox, I deliberately prioritized:
> 1. **Authorization correctness** — Data isolation between organizations is non-negotiable.
> 2. **Clean domain modeling** — `SlaService`, computed `sla_state`, and `TicketPolicy` each own one concern.
> 3. **SLA implementation** — Core business requirement, fully tested across all 3 priority tiers.
> 4. **Maintainable frontend** — Vue composables separate UI from logic so the codebase scales cleanly.
> 5. **Form request consistency** — `StoreTicketRequest`, `StoreCommentRequest`, `StoreAgentCommentRequest`, and `UpdateTicketRequest` keep validation out of controller methods.
>
> **Intentionally deprioritized** (out of scope for this timebox):
> - Real-time WebSocket notifications on ticket assignment or SLA breach
> - File/screenshot attachments on tickets and comments
> - Full audit log / state-change history trail
> - Email notifications to clients when agents reply

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
   *Seeding creates demo logins:*
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
- **Pagination (already added)**: Both listing endpoints use `->paginate(15)` — the Vue components would need updating to render Inertia pagination links.
- **Advanced Search**: Integrate Laravel Scout (with Meilisearch or Typesense) to replace the current `LIKE` query with full-text ranked search.
- **Granular Permissions**: Replace the simple `role` enum with `spatie/laravel-permission` for multi-tier roles (e.g., supervisor, L1/L2 agent, admin).
- **File Attachments**: Allow screenshots and documents on tickets/comments using Laravel Media Library backed by S3 or local disk.
- **API Layer**: Expose a versioned JSON API (`/api/v1/tickets`) with Laravel Sanctum for third-party integrations.
