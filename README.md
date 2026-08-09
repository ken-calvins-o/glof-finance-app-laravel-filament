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

## How the app is organised

The interface is grouped around what a treasurer actually does in a week, not
around balance-sheet categories:

| Section | What lives there |
| --- | --- |
| **Overview** | Where the group stands, what came in this month, who owes money, and buttons to start the day's work. |
| **Money in** | **Collections** — payments received from members. **Group income** — joining fees, loan interest and anything else the group earns. |
| **Money out** | **Payments** — group expenses charged to members, either the same amount each or set per member. |
| **Loans** | **Loans issued** and **Money owed** — every loan and every outstanding balance, with repayments recorded in a single modal. |
| **Members** | The member register, and a full statement for each person. |
| **Reports** | The **group statement** (every member against every fund) and the **savings ledger** audit trail. |
| **Setup** | **Funds** — the pots money is collected into. |

### Roles

The `role` on each member decides what they see:

- **Treasurer / Admin** — records money in and out, issues loans, manages members, sees everything.
- **Member** — sees only their own savings, contributions, loans and debts. They
  cannot reach the member register, the funds, group income or the group
  statement, and they are not offered any "record" buttons.

### Design principles

The interface follows a few rules consistently, and they are worth knowing
before changing it:

1. **Plain language over accounting jargon.** Screens say "Collections", "Money
   owed" and "Funds" rather than "Receivables", "Debts" and "Accounts".
2. **Ask each question once.** The accounting period is chosen once per batch,
   not once per member; "how was this paid" is one dropdown, not a dropdown plus
   a yes/no toggle that means something adjacent.
3. **Show the consequence before committing.** Every form that moves money ends
   in a running total or a plain-English summary — what the member receives,
   what they repay, what the group is about to spend.
4. **Validate, do not warn.** A repayment larger than the balance is refused by
   a validation rule, not flagged by a notification that still lets the form
   submit.
5. **One way to write money.** All amounts go through `App\Support\Money::kes()`
   and are right-aligned in tabular figures, so columns line up and the same
   figure never looks different on two screens.
6. **Badges must be actionable.** Sidebar badges show money collected this
   month or the number of members behind on payments — never a row count.

Amounts are formatted through `App\Support\Money`; shared table columns live in
`App\Filament\Tables\Columns\MoneyColumn`; and the sidebar's structure is
declared in one place, `App\Filament\Navigation`.

## Scheduler: Monthly interest on debts

The app includes a scheduled task that applies **monthly interest** to outstanding debts.

- Console command: `app:apply-monthly-interest`
- Default behavior: **1% monthly** interest (as implemented in the domain service)
- Schedule: runs on the **1st day of every month at 00:00** in `APP_TIMEZONE`,
  which `.env.example` sets to `Africa/Nairobi` for new installations. Existing
  installations that recorded their timestamps under UTC should leave the value
  alone — changing it after the fact shifts how those stored times read back.

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

The panel uses a custom Filament theme, which is the entry point
`resources/css/app.css` (registered via `->viteTheme()` in
`AppPanelProvider`). Because it is a custom theme rather than Filament's
compiled default, **Tailwind must be able to scan every view that renders
markup** — including plugin views. Those paths are listed explicitly in
`tailwind.config.js`; adding a Filament plugin means adding its
`resources/**/*.blade.php` path there, or its styles will be purged away.

Rebuild after any change to the theme or to Blade/PHP that introduces new
utility classes:

```bash
npm run build
```

---

## Getting started with Laravel Herd

Herd is the quickest way to run this project locally — it provides PHP, nginx
and the `.test` domain with nothing to configure.

### 1) Put the project where Herd can see it

Clone into a directory Herd parks (`~/Herd` by default), then give the site a
short domain:

```bash
cd ~/Herd
git clone https://github.com/ken-calvins-o/glof-finance-app-laravel-filament.git
cd glof-finance-app-laravel-filament

herd link glof
```

The site is now served at **http://glof.test**.

`herd link <name>` is what buys the short domain. A parked folder is served
under its own name, which for this repository would be the unwieldy
`glof-finance-app-laravel-filament.test`; linking overrides that without
renaming the directory or the repository. (Renaming the folder to `glof` and
leaving it parked works just as well, if you would rather.)

Any domain is fine — just keep `APP_URL` in `.env` in step with it. That is the
only place the hostname is configured: `vite.config.js` reads it from there, so
the dev server follows automatically.

The app needs **PHP 8.2 or newer**. Herd's default is fine; to pin it,
`herd use php@8.3` inside the project directory.

### 2) Create the database

Herd Pro ships MySQL — start it under **Services**, then create the database:

```bash
herd mysql -e "CREATE DATABASE glof_finance_app"
```

The credentials in `.env.example` (`127.0.0.1:3306`, user `root`, no password)
already match Herd's MySQL, so there is nothing to change.

**On Herd's free tier**, there is no database server. Use SQLite instead — open
`.env.example` and follow the note above the database block, then:

```bash
touch database/database.sqlite
```

### 3) Install and set up

```bash
composer install
composer setup
```

`composer setup` writes `.env`, generates the app key, runs the migrations and
seeders, links storage, and builds the frontend assets.

Then open **http://glof.test** and sign in with the seeded treasurer account
below.

### Working on the frontend

The built assets from `composer setup` are enough to use the app. While
changing styles or Blade views, run the Vite dev server for hot reloading:

```bash
npm run dev
```

If you have secured the site (`herd secure`), it is served over https, and a
dev server on plain http would have its assets blocked by the browser — the
panel would load completely unstyled. `vite.config.js` handles this by reusing
Herd's own certificate for the host in `APP_URL`; no flags needed.

Remember that this project uses a **custom Filament theme**, so any change to
`resources/css/app.css`, or any new utility class in a Blade or PHP file,
requires a rebuild (`npm run build`) before it shows up in production assets.

### The scheduler and the queue

Monthly interest is applied by a scheduled command (see below). Herd Pro can run
the scheduler for you — enable it for this site under **Services** — otherwise
trigger it by hand while developing:

```bash
php artisan schedule:run
```

The queue is configured to use the database. Run a worker when you need one:

```bash
php artisan queue:listen --tries=1
```

Or run the worker and Vite together:

```bash
composer herd
```

### Health check

Herd shows the site as up once `http://glof.test/up` returns 200, which is a
quick way to confirm PHP and the database are reachable.

---

## Getting started without Herd

```bash
composer install
composer setup
php artisan serve
```

Set your database credentials in `.env` before running `composer setup`, and
change `APP_URL` to `http://127.0.0.1:8000`.

---

## Using the app

The panel is served from the site root (`/`), not `/admin`.

Running `php artisan db:seed` creates the member roll along with a treasurer
account you can sign in with:

- **Email:** `admin@glof.co.ke`
- **Password:** `password`

Change that password before putting the app anywhere real. Members seeded
alongside it have no password and cannot sign in until one is set on their
profile; give someone the **Member** access level and they will only ever see
their own money.

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
