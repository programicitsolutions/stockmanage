# Stock Ledger

Single-business stock management. Present stock is calculated from a transaction ledger and is **never** typed in by a user.

This repository is **Phase 1 only**: Laravel foundation, authentication, roles, and database architecture. Product screens, stock entry, Excel import, and reports are not built yet.

This is not a SaaS product. There is one organisation, two roles, and no tenancy.

## Stock principle

```
Opening stock
+ Stock IN
− Stock OUT
± Approved stock adjustments
= Present stock
```

`products.opening_stock` is the setup figure for a product. Live available stock is **not** that column. Live stock is `Product::presentStock()`, which delegates to `App\Services\StockCalculator`. Do not copy that logic into controllers.

Ledger rows cannot be updated or deleted. Corrections become new `ADJUSTMENT_IN` / `ADJUSTMENT_OUT` rows (approval UI is a later phase; the `stock_adjustments` table is already in place).

## Stack

- PHP 8.3+, Laravel 13
- MySQL 8
- Livewire 3 + Volt, Alpine.js (bundled with Livewire)
- Tailwind CSS
- Laravel Breeze (Livewire) for login / password reset
- PWA extras: `public/manifest.webmanifest` and `public/sw.js`

## Local setup

1. PHP 8.3 with `bcmath`, `mbstring`, `xml`, `curl`, `zip`, `intl`, and `mysql` extensions
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
npm install
npm run dev
```

5. In another terminal:

```bash
php artisan serve --host=127.0.0.1 --port=43123
```

Run tests with `php artisan test`. Tests use in-memory SQLite.

## Sample users (local only)

Password for every seeded account: `password`

| Role | Email |
| --- | --- |
| Partner | `partner1@stock.local` … `partner5@stock.local` |
| Accountant | `accountant@stock.local` |

Public registration is disabled. Add staff later with a seeder or artisan tinker until an admin screen exists.

## Roles (Phase 1)

Simple `roles` table and `users.role_id`. No permission matrix yet.

- **Partner** — view dashboard (later: live stock, movements, sales/purchases, profit, audit, adjustment approval)
- **Accountant** — view dashboard (later: enter stock in/out, purchases, sales, adjustment requests, reports)

Both roles can sign in and open the Phase 1 dashboard placeholder.

## Database

| Table | Purpose |
| --- | --- |
| `users`, `roles` | Staff and the two roles |
| `categories` | Product categories |
| `products` | SKU, name, unit, opening/minimum stock, default prices. **No present_stock column.** Unique `sku`. |
| `suppliers`, `customers` | Parties for purchases and sales |
| `stock_transactions` | Immutable ledger (`OPENING`, `STOCK_IN`, `STOCK_OUT`, `ADJUSTMENT_IN`, `ADJUSTMENT_OUT`) |
| `stock_transaction_costs` | Optional cost lines (purchase, transport, loading/unloading, other) so landing price can be added without redesigning the ledger |
| `stock_adjustments` | Adjustment requests and later approval / apply-to-ledger |
| `activity_logs` | Who entered each ledger row, when, type, product, qty, price, reference, notes |

Money uses `DECIMAL(15,2)`. Quantities use `DECIMAL(15,3)`. Do not use floats for money.

Profit is not stored on products. Later: profit per unit = selling price − landing price.

Excel / Google Sheets import is **not** in this phase. Do not invent spreadsheet data here.

## Important classes

- `App\Services\StockCalculator` — present stock and posting new ledger rows (including negative-stock guard)
- `App\Models\Product::presentStock()` — the only model API for live stock
- `App\Models\StockTransaction` — throws if updated or deleted
- `App\Observers\StockTransactionObserver` — writes `activity_logs`
- `App\Http\Middleware\EnsureUserHasRole` — alias `role:partner,accountant`

Negative stock is blocked unless `STOCK_ALLOW_NEGATIVE=true` (default false).

## What is intentionally not built

- Stock in/out screens
- Product / supplier / customer CRUD UI
- Reports and exports
- Adjustment approval workflow UI
- Excel mapping/import
- Multi-tenant / SaaS features

## Remaining environment notes

- Set a real `APP_KEY` via `php artisan key:generate`
- Point `.env` at MySQL for running the app
- Keep `php artisan test` on SQLite memory (already configured in `phpunit.xml`)
- Change seeded passwords before any real deployment
