# Support Ticket Portal

A practical, secure, and maintainable Support Ticket Portal built with **Laravel 11**, **Inertia.js**, and **Vue 3**.

This project provides a robust support ticket system with distinct views for **Clients** and **Support Agents**. Clients can create and track tickets for their organization, while Support Agents can manage, update, assign tickets, and communicate using public comments or private internal notes.

---

## 🛠️ System Architecture

The application uses the **Laravel + Inertia.js (Vue 3) + Tailwind CSS** stack. This architecture delivers a modern single-page application (SPA) experience without the unnecessary overhead of building and maintaining a decoupled, stateless REST API.

### Core Architectural Decisions
- **Inertia.js**: Avoids API over-engineering (no OAuth setup, client-side routing synchronization, or duplicate validation rules). Controller logic directly dictates state.
- **Service Layer Pattern**: SLA calculation is decoupled from controllers and models into a dedicated `SlaService`. This makes the business rules easily testable and reusable.
- **Database Schema**: 
  - `organizations`: Simple container for grouping clients and scoping ticket access.
  - `users`: Augmented with a `role` (enum: client, agent) and a nullable `organization_id` foreign key.
  - `tickets`: Tracks association, assignee, state, priority, and computed SLA expiration.
  - `comments`: Handles public and private conversation threads with a simple boolean `is_internal` marker.

---

## 🔐 Authorization & Security

Authorization is governed strictly via Laravel **Policies** to prevent unauthorized access and data leakage:

- **TicketPolicy**:
  - **Client Users**: Scoped strictly to their own organization (`$user->organization_id === $ticket->organization_id`). They cannot view or modify tickets outside their company.
  - **Agents**: Granted global permission (`$user->role === 'agent'`) to view and update tickets across all organizations.
- **Comment Isolation**:
  - Clients can only see public comments (`is_internal = false`).
  - The controller actively filters out internal notes from the comments collection before sending the payload to the client-facing Vue pages.
  - Clients are restricted by policy and validation from submitting comments marked as `is_internal`.

---

## ⏳ SLA Calculation & State Management

### SLA Rules
- **High** Priority ➡️ **4 hours** resolution time
- **Normal** Priority ➡️ **24 hours** resolution time
- **Low** Priority ➡️ **72 hours** resolution time

### Accessor-Driven State
SLA state is computed dynamically rather than being stored in the database, avoiding state synchronization bugs:
- **`overdue`**: The current time has surpassed the `sla_due_at` timestamp.
- **`due_soon`**: The current time is within **2 hours** of the `sla_due_at` timestamp.
- **`on_track`**: The ticket is within its SLA window and not close to expiration.

```php
public function getSlaStateAttribute()
{
    if (now()->greaterThan($this->sla_due_at)) {
        return 'overdue';
    }

    if (now()->diffInHours($this->sla_due_at) <= 2) {
        return 'due_soon';
    }

    return 'on_track';
}
```

---

## ⏱️ Timebox Decisions & Omissions

To complete the portal within a practical timebox while emphasizing clean architecture and security, the following tradeoffs were made:

### What was Prioritized:
1. **Security & Data Isolation**: Rigorous policy tests verify that clients cannot cross-access organization boundaries or read internal agent notes.
2. **Dynamic UI/UX**: Designed a clean, modern interface using custom badges, state-colored labels, filters, and forms.
3. **Database Seeding**: A `DemoSeeder` generates realistic, ready-to-test scenarios (open/in-progress/resolved/overdue tickets) for rapid evaluation.

### What was Intentionally Omitted:
1. **Advanced Notifications**: Real-time email or Slack notifications on assignment or SLA breach were omitted to keep dependencies minimal.
2. **File Attachments**: Ticket and comment attachments were left out of the scope to avoid configuring object storage providers in this phase.
3. **Auditing/Activity Logs**: A full history of state changes (e.g., ticket assignee history) was omitted in favor of simple status updates.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
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
   Copy `.env.example` to `.env` and configure your database settings (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

3. **Migrate and seed the database:**
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Note: Seeding creates client logins (`client1@gmail.com`, `client2@gmail.com`) and agent logins (`agent1@gmail.com`, `agent2@gmail.com`) with the password `password`.*

4. **Build assets:**
   ```bash
   npm run build
   ```

5. **Start the development server:**
   ```bash
   php artisan serve
   ```

6. **Run tests:**
   ```bash
   ./vendor/bin/phpunit
   ```

---

## 🔮 Next Steps & Evolving to Production

### 1. What to Build Next (given more time)
- **Real-time Notifications**: Trigger notifications (via mail/SMS/Slack) to agents on ticket assignment and to clients when agents post public comments.
- **SLA Breach Alerts**: Set up a background worker checking for tickets approaching their SLA breach window (e.g., within 30 minutes) and send warnings or escalate priorities.
- **Audit Logs / History**: Log every status, priority, or assignee change to a separate ledger table to construct an audit timeline.

### 2. Known Shortcuts & Limitations
- **SLA Resets**: Currently, changing the priority recalculates the SLA due date based on the *current* time. In production, this should factor in elapsed business hours. Also, resolving/closing a ticket does not stop/freeze the SLA timer.
- **Pagination**: The ticket queries currently load all matching records. A production implementation would enforce cursor/offset pagination (`->paginate()`) to protect against performance degradation under load.

### 3. Areas for Improvement
- **Robust Role/Permissions**: The role system is modeled as a simple DB enum (`client`, `agent`). For a production system with granular permissions (e.g., supervisors vs level 1 support vs system admin), integrating a package like `spatie/laravel-permission` is recommended.
- **File Uploads**: Adding document/screenshot attachments to tickets and comments using Laravel Media Library or S3 integration.

