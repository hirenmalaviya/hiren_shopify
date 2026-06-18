# Client Requirements — Coverage Document

This document maps **every requirement** from the *Laravel Technical Assessment — CSV to Shopify
Product Import System* brief to its implementation in this project, with the status and the
evidence (files / commands) that prove it is satisfied.

**Legend:** ✅ Done · ⭐ Bonus

---

## 1. Project Setup

| # | Requirement | Status | Implementation / Evidence |
|---|---|---|---|
| 1.1 | Initialize a new Laravel 12 project | ✅ | Laravel **12.62** (`composer.json`, `php artisan --version`) |
| 1.2 | Configure database connections | ✅ | MySQL configured in `.env` (`DB_CONNECTION=mysql`, DB `hiren_shopify`) |
| 1.3 | Set up Shopify API credentials in environment | ✅ | `.env` `SHOPIFY_*` keys → `config/shopify.php` (token, store, API version, collection, location) |
| 1.4 | Install necessary packages | ✅ | Inertia, Vue 3, Vite, PrimeVue, Tailwind v4, `tailwindcss-primeui`, `league/csv` |

---

## 2. File Upload Interface

| # | Requirement | Status | Implementation / Evidence |
|---|---|---|---|
| 2.1 | Form to upload CSV files | ✅ | `resources/js/Pages/Uploads/Index.vue` — drag-&-drop + browse |
| 2.2 | Client-side validation (file type & size) | ✅ | `Index.vue` `validateFile()` — `.csv` only, ≤ 5 MB, rejects empty files |
| 2.3 | Clean, responsive interface | ✅ | PrimeVue + Tailwind, responsive layout, **dark mode** |
| 2.4 | Feedback for successful uploads | ✅ | Upload progress bar + success toast ("Upload received — N rows queued") |

---

## 3. CSV Processing & Shopify Integration

| # | Requirement | Status | Implementation / Evidence |
|---|---|---|---|
| 3.1 | Migration to save product records | ✅ | `database/migrations/*_create_products_table.php` |
| 3.2 | Laravel job to process CSV asynchronously | ✅ | `app/Jobs/ProcessCsvImport.php` + `ImportProductToShopify.php` (queued) |
| 3.3 | Parse CSV data & validate format | ✅ | `app/Services/CsvImportService.php` + `app/Support/CsvProductMapper.php` (header + per-row validation) |
| 3.4 | Map CSV columns to Shopify product fields | ✅ | `CsvProductMapper::HEADER_MAP` + `ShopifyService::productInput()` |
| 3.5 | API integration with Shopify to add products | ✅ | `app/Services/ShopifyService.php` (GraphQL create/update/variant/inventory/image/collection) |
| 3.6 | Add products to the required collection `464337174767` | ✅ | `ShopifyService::addToCollection()`; verified live (`inCollection: true`) |

---

## 4. Dashboard

| # | Requirement | Status | Implementation / Evidence |
|---|---|---|---|
| 4.1 | Display all imports | ✅ | `Pages/Dashboard.vue` — stats, chart, uploads DataTable (search/sort/paginate) |
| 4.2 | Status per product (pending, processing, successful, failed) | ✅ | `Pages/Uploads/Show.vue` — per-product table with status badges (+ `skipped`) |
| 4.3 | Display error messages for failed uploads | ✅ | Detail column shows `error_message`; verified ("Variant Price must be a number…") |
| — | Live status updates | ⭐ | Show page polls every 3s until terminal (auto-refresh toggle) |

---

## 5. Bonus Features

| # | Requirement | Status | Implementation / Evidence |
|---|---|---|---|
| 5.1 | Logging across the application | ⭐✅ | Dedicated `shopify_import` log channel (`config/logging.php`) + `import_logs` table |
| 5.2 | Log all import events | ⭐✅ | `ImportProductToShopify::log()` writes every event |
| 5.3 | Log viewer in the dashboard | ⭐✅ | `Pages/Logs/Index.vue` (`/logs`) — filter by level, search, expandable context |
| 5.4 | Error notification system | ⭐✅ | `ImportFailedNotification` (database channel) → in-app bell with unread badge |
| 5.5 | Use GraphQL instead of REST | ⭐✅ | Entire `ShopifyService` uses Admin **GraphQL** (`2024-10`) |
| 5.6 | GraphQL queries & mutations for import | ⭐✅ | `productCreate`, `productUpdate`, `productVariantsBulkUpdate`, `inventorySetQuantities`, `productCreateMedia`, `collectionAddProducts` |
| 5.7 | Error handling for GraphQL responses | ⭐✅ | `ShopifyService::request()` handles transport / `errors` / `userErrors` + throttle backoff |
| 5.8 | Update product if it already exists | ⭐✅ | `ShopifyService::upsertProduct()` (match by handle/SKU); verified — `action = update` |

---

## Technical Requirements

### Database (migrations)
| Requirement | Status | Evidence |
|---|---|---|
| Upload records | ✅ | `uploads` table |
| Product import status | ✅ | `products` table (`status`, `action`, `shopify_product_id`, `error_message`) |
| Error logs | ✅ | `import_logs` table |

### Models & relationships
| Requirement | Status | Evidence |
|---|---|---|
| Uploads | ✅ | `app/Models/Upload.php` — `hasMany(Product)`, `hasMany(ImportLog)` |
| Products | ✅ | `app/Models/Product.php` — `belongsTo(Upload)`, `hasMany(ImportLog)` |
| Import Records | ✅ | `app/Models/ImportLog.php` — `belongsTo(Upload/Product)` |

### Jobs & Queues
| Requirement | Status | Evidence |
|---|---|---|
| Laravel queue for background processing | ✅ | `QUEUE_CONNECTION=database`, `jobs`/`failed_jobs` tables |
| Jobs for handling CSV imports | ✅ | `ProcessCsvImport`, `ImportProductToShopify` (tries=3, backoff, failed() hooks) |

### Frontend
| Requirement | Status | Evidence |
|---|---|---|
| Blade or modern JS framework (React/Vue) | ✅ | **Vue 3 + Inertia.js** + PrimeVue + Tailwind |

---

## Submission Guidelines

| # | Requirement | Status | Notes |
|---|---|---|---|
| S.1 | Push code to a GitHub repository | ⏳ | Ready to push (`.env` git-ignored, `.env.example` provided). To be done by the client. |
| S.2 | README with setup, overview, assumptions, testing | ✅ | `README.md` |
| S.3 | Include video of the working application | ✅ (script) | See `docs/DEMO_VIDEO.md` — full recording script/storyboard. A screenshot-by-screenshot walkthrough is in `docs/STEP_BY_STEP_GUIDE.md`. |

---

## Verification summary

*Last full verification: 2026-06-18.*

- **Automated tests:** `php artisan test` → **14 passed, 61 assertions** (CSV parsing/validation, upload handling, ShopifyService with `Http::fake`, import pipeline, retry, failure notifications).
- **Live Shopify sandbox:** sample CSV (10 products) imported into collection `464337174767`; all 10 **successful**, upsert/update verified (`action = update`, no duplicates). `shopify:check` → *Suprabhat — manual collection ✓*, location `gid://shopify/Location/81950769391`.
- **Browser walkthrough (real user, captured):** upload (valid + invalid), client-side validation, live processing → completed, dashboard stats/chart, log viewer + expandable context, failure handling (3/3 failed with per-row errors), notification bell, dark mode — all verified and screenshotted in **`docs/STEP_BY_STEP_GUIDE.md`**.
- **Diagnostics:** `php artisan shopify:check` confirms token, location, and manual collection.

> Every core requirement and all three bonus features are implemented and verified. The only
> outstanding item is the GitHub push + video recording, which are client-side submission steps
> (the video script is provided in `docs/DEMO_VIDEO.md`).
