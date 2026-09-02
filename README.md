# Stock Ledger

Single-business stock management. Present stock is calculated from a transaction ledger and is **never** typed in by a user.

```
Opening stock
+ Stock IN
− Stock OUT
± Approved stock adjustments
= Present stock
```

`products.opening_stock` is the setup figure. Live available stock is `Product::presentStock()` → `App\Services\StockCalculator`. Do not copy that logic into controllers, dashboards, or reports.

Ledger rows cannot be updated or deleted. Corrections become new `ADJUSTMENT_IN` / `ADJUSTMENT_OUT` rows after a manager or admin approves a request. The requester cannot approve their own adjustment.

Excel import is **not** part of the current workflow. Products are added with **Products → Add product**.

## Stack

- PHP 8.3+, Laravel 13
- MySQL 8
- Livewire 3 + Volt, Alpine.js
- Tailwind CSS
- Laravel Breeze (Livewire) for login / password reset
- PWA extras: `public/manifest.webmanifest` and `public/sw.js`

## Local setup

1. PHP 8.3 with `mbstring`, `xml`, `curl`, `zip`, `intl`, and `mysql` extensions. `php8.3-bcmath` is recommended; if it is missing the app uses a built-in decimal polyfill so the dashboard still loads.
2. Composer, Node.js 22+, MySQL 8
3. Create a database:

```sql
CREATE DATABASE stock_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Install and boot:

```bash
composer install
cp .env.example .env
php artisan key:generate
# set DB_* in .env to your MySQL credentials
php artisan migrate --seed
```

Do **not** run `migrate:fresh` on a database that already has live stock.

Compiled CSS/JS lives in `public/build/` (committed so `php artisan serve` works without Node). If you change Tailwind/JS, rebuild with `npm install && npm run build`.

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Tests: `php artisan test` (in-memory SQLite).

## Sample users (local only)

Password for every seeded account: `password`

| Role | Email | Typical use |
| --- | --- | --- |
| Partner (manager) | `partner1@stock.local` … `partner5@stock.local` | View stock, approve adjustments, reports |
| Accountant | `accountant@stock.local` | Add products, stock in/out, request adjustments |
| Admin | `admin@stock.local` | Users, products, stock, adjustments, reports, audit |

Public registration is disabled. Admins add staff at **Users**.

The first login opens a welcome popup and a short walkthrough. Replay it from **Replay walkthrough** at the bottom of the sidebar.

`php artisan db:seed` also loads sample **main** products (finished tins, bottles, cartons) and **inner** products (lids, liners, plugs, inserts). Re-seeding does not double opening stock.

## Accountant workflow

Login → Dashboard → Stock in / Stock out → add lines (SKU or name) → Review → Confirm → print slip.

Stock quantity is never edited on the product form. Opening stock is posted once at product create. Later corrections use Stock in, Stock out, or a physical-count / count sheet.

The **stock assistant** is a page at `/assistant` (sidebar **Assistant**, or **Ask assistant**). It answers stock in/out/adjustments questions and looks up a SKU against the live ledger. It is not an AI chat — replies come from `App\Services\StockAssistant`.

## Roles (enforced on the server)

- **Admin** — full access including users
- **Partner** — view catalog/stock/reports/audit; approve or reject adjustments they did not request
- **Accountant** — view stock; add/edit products and catalog; stock in/out; request adjustments; own entry history. Cannot approve adjustments, manage users, or delete ledger rows

## Daily partner summary

`php artisan stock:daily-summary` emails opted-in users (partners and admin are seeded with this on). Schedule it at 19:00 with the Laravel scheduler:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Mail defaults to the `log` driver unless you configure SMTP.

## Important classes

- `App\Services\StockCalculator` — present stock, posting, and product ledger summaries
- `App\Models\Product::presentStock()` — the only model API for live stock
- `App\Models\StockTransaction` — throws if updated or deleted
- `App\Observers\StockTransactionObserver` — audit log with before/after stock
- `App\Services\ProductCatalog` — create product and post opening stock
- `App\Services\StockAdjustmentService` — request / approve / reject (no self-approval)
- `App\Services\StockInsights` — dashboard KPIs from the ledger

Negative stock is blocked unless `STOCK_ALLOW_NEGATIVE=true` (default false).

## Remaining environment notes

- Set a real `APP_KEY` via `php artisan key:generate`
- Point `.env` at MySQL for running the app
- Change seeded passwords before any real deployment
