# CSV → Shopify Product Import System

A Laravel 12 application that lets a user upload a Shopify-style product CSV, validates and
stores it, then imports each product into a Shopify store **asynchronously** via the **Admin
GraphQL API** — tracking per-product status on a world-class dashboard with live progress,
comprehensive logging, an in-app log viewer, and failure notifications.

> Built for the Laravel Technical Assessment. Implements all core requirements **and** the three
> bonus features (GraphQL integration, update-existing/upsert, logging + log viewer + notifications).

---

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Frontend | Vue 3 + Inertia.js + Vite |
| UI | PrimeVue (Aura theme) + Tailwind CSS v4, dark mode |
| Database | MySQL |
| Queue | Laravel `database` queue driver |
| Shopify | Admin **GraphQL** API (`2024-10`) |

---

## Features

**Core**
- Drag-and-drop CSV upload with client- **and** server-side validation (type, size, required headers, per-row rules).
- Asynchronous import via queued jobs; per-product status (`pending → processing → successful / failed`), plus `skipped` for in-file duplicates.
- Shopify product create with variant (price, compare-at, SKU, weight, inventory policy), inventory quantity at a location, product image, and **add-to-collection**.
- Dashboard listing all imports with stats, a chart, search/filter/sort/pagination, live-polling detail view, and per-product errors.

**Bonus**
- **GraphQL** for the entire Shopify integration, with transport/`errors`/`userErrors` handling and **rate-limit/throttle** backoff.
- **Update existing products (upsert)** — re-importing matches by handle/SKU and updates instead of duplicating; each row records whether it was a `create` or `update`.
- **Logging** — every import event is written to a dedicated `shopify_import` log channel **and** the `import_logs` table, surfaced in an in-app **log viewer**. Failures raise an in-app **database notification** (bell in the nav).
- **Retry & delete** uploads, dark-mode toggle, CSV template download.

---

## Documentation

- **[docs/bandicam 2026-06-18 12-10-38-108.mp4](docs/bandicam%202026-06-18%2012-10-38-108.mp4)** — the **demo video**: a real screen recording of the full flow (upload → live import → dashboard → logs → failure handling → notifications → dark mode), 1080p, ~1m40s.
- **[docs/CSV-to-Shopify-Import-Guide.docx](docs/CSV-to-Shopify-Import-Guide.docx)** — the client-facing **Word document**: full step-by-step flow with embedded screenshots and descriptions. *(Regenerate with `python docs/build_docx.py`.)*
- **[docs/STEP_BY_STEP_GUIDE.md](docs/STEP_BY_STEP_GUIDE.md)** — the same visual walkthrough in Markdown (renders on GitHub).
- **[docs/CLIENT_REQUIREMENTS.md](docs/CLIENT_REQUIREMENTS.md)** — every brief requirement mapped to its implementation, with status and evidence.
- **[docs/DEMO_VIDEO.md](docs/DEMO_VIDEO.md)** — scene-by-scene recording script/storyboard for the demo video.
- **[docs/hiren_shopify.sql](docs/hiren_shopify.sql)** — a phpMyAdmin **database dump** (schema + sample data). Import it instead of running `php artisan migrate` to start from a populated database.

---

## Requirements

- PHP 8.2+
- Composer 2.x
- Node.js 18+ and npm
- MySQL 5.7+ / MariaDB (e.g. via XAMPP)

---

## Setup

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set your **database** and **Shopify** credentials:

```dotenv
DB_DATABASE=hiren_shopify
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

SHOPIFY_STORE_URL=your-store.myshopify.com
SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
SHOPIFY_API_VERSION=2024-10
SHOPIFY_COLLECTION_ID=464337174767
# Optional — leave blank to auto-detect the default location:
SHOPIFY_LOCATION_ID=
```

```bash
# 3. Create the database (MySQL) then migrate
#    e.g.  mysql -u root -e "CREATE DATABASE hiren_shopify"
php artisan migrate

#    --- OR --- import the ready-made dump instead of migrating
#    (schema + sample data, via XAMPP/phpMyAdmin or the CLI):
#    mysql -u root hiren_shopify < docs/hiren_shopify.sql

# 4. Verify the Shopify connection (token, location, collection)
php artisan shopify:check

# 5. Build the frontend
npm run build      # production
# or: npm run dev  # hot-reloading during development
```

### Run it

You need **two** processes — the web server and the queue worker:

```bash
# Terminal A — web server
php artisan serve

# Terminal B — queue worker (processes imports)
php artisan queue:work
```

Open <http://localhost:8000> and upload a CSV. (A sample lives in `plan/`.)

> In production, run the worker under a supervisor (e.g. `supervisor`/`systemd`) so it restarts automatically.

---

## How it works

```
Upload form (Vue/Inertia)
  └─ POST /uploads ─ UploadController@store
        ├─ validate file (StoreUploadRequest: mimes/size + required headers)
        ├─ store file in storage/app/private/uploads
        ├─ CsvImportService: parse + per-row validation + dup detection
        │     → Upload row + Product rows (pending / failed / skipped)
        └─ dispatch ProcessCsvImport ──► database queue

Worker (php artisan queue:work)
  └─ ProcessCsvImport (per upload)
        └─ dispatches ImportProductToShopify per pending product
              ├─ status=processing
              ├─ ShopifyService->upsertProduct()  (GraphQL)
              │     ├─ find existing by handle/SKU
              │     ├─ productCreate | productUpdate
              │     ├─ productVariantsBulkUpdate (price/sku/weight/policy)
              │     ├─ inventorySetQuantities (at location)
              │     ├─ productCreateMedia (image)
              │     └─ collectionAddProducts (target collection)
              ├─ success → successful (+ shopify ids, create/update action)
              └─ failure → retry (3×, backoff); on final failure → failed + error + ImportLog + notification
        └─ last finished product → Upload marked completed / completed_with_errors / failed

Dashboard (Vue/Inertia) — stats, chart, uploads table, live-polling detail, log viewer, notifications.
```

### Key files
- `app/Services/ShopifyService.php` — GraphQL client + import flow.
- `app/Services/CsvImportService.php` — CSV → DB parsing (shared by web + CLI).
- `app/Support/CsvProductMapper.php` — header→field map + per-row validation rules.
- `app/Jobs/{ProcessCsvImport,ImportProductToShopify}.php` — queued import.
- `app/Models/{Upload,Product,ImportLog}.php` + `app/Enums/*` — data model.
- `resources/js/Pages/{Dashboard,Uploads/Index,Uploads/Show,Logs/Index}.vue` — UI.

---

## CSV format

Required columns: `Handle`, `Title`, `Variant SKU`, `Variant Price`.
Optional: `Body HTML`, `Vendor`, `Product Type`, `Tags`, `Published`, `Variant Compare At Price`,
`Variant Requires Shipping`, `Variant Taxable`, `Variant Inventory Tracker`, `Variant Inventory Qty`,
`Variant Inventory Policy`, `Variant Fulfillment Service`, `Variant Weight`, `Variant Weight Unit`,
`Image Src`, `Image Position`, `Image Alt Text`.

Download a ready-made template from the upload page or at `/csv-template`.

---

## CLI tools

```bash
php artisan shopify:check                 # verify token, location, target collection
php artisan import:csv path/to/file.csv   # import a CSV from the filesystem (then run the worker)
```

---

## Testing

```bash
php artisan test
```

The suite (PHPUnit, SQLite in-memory) covers CSV parsing & validation, upload handling, the
`ShopifyService` (with `Http::fake` — no live calls), the full import pipeline, retry, and
failure notifications.

---

## Assumptions & design decisions

- **No authentication** — the brief doesn't require it, so the app is a single-tenant tool. Failure
  notifications are delivered to a single auto-provisioned "system" account via Laravel's database
  notification channel.
- **One variant per CSV row** — the Shopify CSV format is one row per variant; each row maps to a
  product with a single default variant.
- **Upsert key** — existing products are matched by **handle**, falling back to **variant SKU**.
- **Collection** — products are added with `collectionAddProducts`, which requires a **manual**
  (custom) collection. `php artisan shopify:check` reports whether the configured collection qualifies.
- **Inventory** — quantity is set at the store's default location (override with `SHOPIFY_LOCATION_ID`).
- **Completion tracking** — instead of `Bus::batch`, the last per-product job to finish finalizes the
  upload. This behaves identically on the `sync` (test) and async (production) drivers.
- **Image on create only** — to avoid duplicate media when re-importing; product fields are still updated.
- **Throttling** — GraphQL `THROTTLED` and HTTP 429 responses are retried with cost-aware backoff.

---

## Troubleshooting

- **Imports stay "pending"** → the queue worker isn't running. Start `php artisan queue:work`.
- **`shopify:check` fails** → check `SHOPIFY_STORE_URL` (no protocol) and `SHOPIFY_ACCESS_TOKEN`.
- **Products import but aren't in the collection** → the collection must be a *manual* collection.
- **Config changes not picked up** → run `php artisan config:clear`.

---

## Submission checklist

- [ ] Push to GitHub (`.env` is git-ignored; `.env.example` documents all keys).
- [x] Demo video recorded → `docs/bandicam 2026-06-18 12-10-38-108.mp4`.

### Demo video script
1. Show the upload page; drag in the sample CSV (client validation, progress).
2. Land on the detail page; watch statuses go `pending → processing → successful` live.
3. Open the Shopify admin and show the products in the target collection.
4. Re-upload the same CSV → show products **updated** (action = update, no duplicates).
5. Trigger a failure (e.g. a bad row) → show the `failed` status, error message, the **log viewer**, and the **notification bell**.
6. Show the dashboard stats + chart, dark-mode toggle, and retry/delete actions.
