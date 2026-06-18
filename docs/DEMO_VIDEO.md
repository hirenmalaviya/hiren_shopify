# Demo Video — Recording Script & Storyboard

> ✅ **A recorded demo video already ships with the project: [`bandicam 2026-06-18 12-10-38-108.mp4`](bandicam%202026-06-18%2012-10-38-108.mp4)**
> (1080p, ~1m40s, silent screen capture). It walks through the entire flow — valid upload → live
> processing → completed → dashboard → log viewer (with expanded JSON) → invalid CSV with per-row
> errors → failure notification → dark mode. The script below documents that storyboard and lets
> you re-record a narrated version if you want one.

A scene-by-scene script for recording the demo video required by the submission guidelines.
Target length: **3–5 minutes**. Record at 1080p, app at `http://localhost:8000`.

---

## Before you record (setup)

```bash
# 1. Make sure assets are built and migrations are run
npm run build
php artisan migrate

# 2. (Optional) Start from a clean slate so the demo is tidy
php artisan migrate:fresh        # wipes all data — re-creates tables

# 3. Start the two processes (separate terminals)
php artisan serve                # Terminal A → http://localhost:8000
php artisan queue:work           # Terminal B → processes imports

# 4. Verify Shopify is connected
php artisan shopify:check        # should print Shop, Location, Collection (manual ✓)
```

Have ready:
- The sample CSV: `plan/shopify-products-csv (5) (1) (1).csv` (10 valid products).
- A **bad** CSV to demo validation/failures. Create `bad.csv`:
  ```csv
  Handle,Title,Variant SKU,Variant Price
  broken-1,Broken One,BRK-1,not-a-number
  ,Missing Handle,BRK-2,9.99
  ```
- The Shopify admin open in another tab: `https://admin.shopify.com/store/laravel-import-test`
  (collection **Suprabhat**, id `464337174767`).

---

## Scene-by-scene script

### Scene 1 — Intro (~20s)
- **Show:** the Upload page (`/uploads`).
- **Say:** "This is a Laravel 12 app that imports a Shopify-style product CSV into a Shopify store
  using the GraphQL Admin API, processing everything asynchronously with live status tracking."
- Point out the clean UI, the format guide, and the **Download CSV template** link.

### Scene 2 — Upload a valid CSV (~40s)
- **Do:** drag the sample CSV onto the dropzone (or Browse). Show the file preview (name + size).
- **Show:** the upload progress bar, then the success toast "Upload received — 10 rows queued".
- **Say:** "Client-side validation checks the file type and size; the server re-validates and
  also confirms the required columns exist."
- You land on the **detail page**.

### Scene 3 — Live processing (~40s)
- **Show:** the progress bar climbing, statuses flipping `pending → processing → successful` in
  real time (no manual refresh), and the per-product table with SKU, price, status, and the
  `create`/`update` action tag.
- **Say:** "A queued job per product calls Shopify over GraphQL — creating the product, its
  variant, inventory at a location, the image, and adding it to the target collection."
- Wait until it reaches **Completed — 10 successful**.

### Scene 4 — Confirm in Shopify (~30s)
- **Switch to** the Shopify admin tab → the **Suprabhat** collection.
- **Show:** the 10 imported products are present.
- Back in the app, click **"View in Shopify"** on a product row to show the deep link works.

### Scene 5 — Update / upsert (~30s)
- **Do:** upload the **same** CSV again.
- **Show:** on completion, the **Action** column reads **`update`** for every row.
- **Say:** "Re-importing matches existing products by handle or SKU and updates them — no
  duplicates. That's the bonus 'update existing product' feature."

### Scene 6 — Validation & failure handling (~40s)
- **Do:** upload the **bad.csv**.
- **Show:** the rows marked **Failed** with precise error messages
  ("Variant Price must be a number ≥ 0…", "Handle is required."), and the upload status **Failed**.
- **Show:** the **notification bell** now has an unread badge → open it to show the failure alert.
- **Say:** "Every row is validated independently, errors are captured per-row, and failures raise
  an in-app notification."

### Scene 7 — Retry & delete (~20s)
- **Do:** click **Retry failed** → show the toast and the rows going back to processing.
- **Do:** click **Delete** → show the confirmation dialog → confirm → row removed.

### Scene 8 — Dashboard, logs, dark mode (~40s)
- **Show:** the **Dashboard** — stat cards (total uploads, products imported, failed, success
  rate), the products-by-status **chart**, and the uploads table with search/sort/pagination.
- **Show:** the **Logs** page — filter by level, expand a row to reveal the full JSON context.
- **Do:** toggle **dark mode** to show the polished theme.

### Scene 9 — Wrap-up (~20s)
- **Say:** "Everything is queue-driven, uses GraphQL with throttle handling and retries, is fully
  logged, and is covered by automated tests."
- **Optional:** show `php artisan test` passing (14 passed) in a terminal.

---

## Shot checklist (tick while recording)

- [ ] Upload page + format guide
- [ ] Valid CSV upload + progress + success toast
- [ ] Live status transitions to Completed (10/10)
- [ ] Products visible in the Shopify collection
- [ ] "View in Shopify" deep link
- [ ] Re-upload → `update` action (upsert)
- [ ] Bad CSV → per-row errors + Failed status
- [ ] Notification bell with unread badge
- [ ] Retry failed + Delete (confirm dialog)
- [ ] Dashboard stats + chart + table
- [ ] Logs viewer + expandable context
- [ ] Dark mode toggle
- [ ] (Optional) `php artisan test` green

---

## Saving the video

- Record with any screen recorder (OBS, Loom, ShareX, or Windows Game Bar `Win+G`).
- Export as **MP4 (H.264), 1080p**.
- Suggested filename: `csv-shopify-import-demo.mp4`.
- Place it in this `docs/` folder or attach the link in the GitHub README under "Demo".
- If the file is large, upload to Google Drive/Loom/YouTube (unlisted) and paste the link in the README.
