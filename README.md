# Glof Finance (Laravel + Filament)

Glof Finance is a web-based finance management system built with **Laravel** and **Filament** (TALL stack). It’s designed for **member-based groups/SACCOs/chamas** to manage savings, collections, loans, payables, and reporting in a structured, auditable way—without relying on spreadsheets.

> Currency: The app is primarily oriented around **Kenyan Shillings (KES)**.

---

## Why this app exists (problem it solves)

Many groups run core financial operations in Excel/Google Sheets. That works early on, but often leads to:
- inconsistent data entry (“M-PESA”, “Mpesa”, “MobileMoney”…),
- broken formulas and manual reconciliation,
- weak accountability (who changed what, and when?),
- repetitive monthly tasks like interest application done manually.

Glof Finance centralizes those workflows in a database-backed system with consistent forms, safer updates, and automation via the Laravel scheduler.

---

## Core modules / features

### Members
- Maintain a list of members (users)
- Member-linked transactions for traceability

### Savings / Contributions
- Track member savings (credits, debits, balances, and net worth)

### Collections (Receivables)
- Record money received from members with structured entry forms
- Payment modes supported (examples): Bank Transfer, Cash, Cheque, Mobile Money (M-PESA/Airtel), Card, Online gateway, etc.

### Loans
- Issue loans to members and track amounts, balances, and due dates
- Loan creation workflow supports consistent related updates (e.g. balance/interest handling)

### Income (including interest)
- Record income entries including interest income for reporting visibility

### Payables
- Record outgoing payments / debits and group expenses
- Supports more complex payout/allocation scenarios

### Reporting (PDF)
- Generate group statements as PDF for meetings, sharing, and record keeping

---

## Scheduler: Monthly interest on debts

The app includes a scheduled task that applies **monthly interest** to outstanding debts.

- Console command: `app:apply-monthly-interest`
- Default behavior: **1% monthly** interest (as implemented in the domain service)
- Schedule: runs on the **1st day of every month at 00:00** (app timezone)

The scheduler includes:
- `withoutOverlapping()` at schedule level
- a cache lock keyed to the current interest period to reduce double-processing risk

### Run it manually
```bash
php artisan app:apply-monthly-interest
```

### Enable in production (Laravel scheduler)
Add this cron entry on your server (runs every minute and triggers scheduled tasks when due):

```bash
* * * * * cd /path/to/glof-finance-app-laravel-filament && php artisan schedule:run >> /dev/null 2>&1
```

To view scheduled tasks:

```bash
php artisan schedule:list
```

---

## Tech stack
- **Laravel** (backend framework)
- **Filament** (admin panel / resources)
- **Livewire** (reactive server-driven UI)
- **Alpine.js + Tailwind CSS** (TALL stack UI layer)
- **Vite** (frontend build pipeline)

---

## Getting started (local development)

### 1) Clone and install dependencies
```bash
git clone https://github.com/ken-calvins-o/glof-finance-app-laravel-filament.git
cd glof-finance-app-laravel-filament

composer install
npm install
```

### 2) Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

Set your database credentials in `.env`.

### 3) Run migrations (and seed if your repo includes seeders)
```bash
php artisan migrate
# optional:
# php artisan db:seed
```

### 4) Build assets and run the app
```bash
npm run dev
php artisan serve
```

---

## Using the admin panel (Filament)

Once the app is running, access the Filament dashboard (commonly):
- `/admin`

Create an admin user (choose one approach that matches your project):
- via registration (if enabled), or
- via tinker / seeder / custom artisan command (if present).

---

## Notes on data integrity

Financial systems are sensitive. This app uses:
- validated form inputs
- database transactions in critical flows
- structured enums for certain fields (e.g. payment modes)

If you plan to deploy to multiple SACCOs or larger datasets, consider adding:
- approvals (maker-checker)
- period closing/locking
- audit logging (who changed what)
- multi-tenancy (tenant isolation)

---

## Roadmap ideas (optional enhancements)
- Member statements (per member, per date range)
- Arrears tracking and notifications
- Import tools (CSV/Excel onboarding)
- Audit logs (activity history per record)
- M-PESA / bank integrations
- Robust idempotency markers for interest runs (persisted “run ledger”)

---

## Contributing
Contributions are welcome. Please open an issue describing the change and the motivation, then submit a PR.

---

## License
Add your chosen license here (MIT/Proprietary/etc.).
