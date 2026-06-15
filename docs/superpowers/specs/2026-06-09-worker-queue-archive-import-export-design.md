# Route Archive / Import / Export Through the Cron Worker — Design

**Date:** 2026-06-09
**Status:** Approved (design phase)

## Problem

The web request for **Archive All / Archive By Filter** loops over every matching
student synchronously and times out the PHP session on large batches. Student
**Import** (bulk xlsx/csv insert) and **Export** (build xlsx/csv) run the same
synchronous, blocking way and carry the same timeout risk.

PDF **Generate** already solved this: it enqueues into a file-backed JSON queue
(`writable/pdf_queue/{queue,processing,finished}.json`) drained by the
`php spark run:json-pdf-queue` worker, and the browser polls for progress.

Goal: route Archive, Import, and Export through that **same single worker**, so
each returns instantly with a job to poll instead of blocking the request.

## Scope

In scope:

- Voucher/student **Archive All** + **Archive By Filter** (admin + user controllers).
- Student **Import** (`VoucherImport::import`).
- Student **Export** (`VoucherImport::export`).

Out of scope (small datasets, don't time out): school import/export, signatory
archive, single-row student archive, restore flows. Generate is already async and
its existing routes/runner are left untouched.

## Decisions (from brainstorming)

- **UX:** queue + poll progress (mirror the Generate flow), not fire-and-forget.
- **Worker:** ONE unified worker. The existing `run:json-pdf-queue` process drains
  every job type. No second process, no second cron.
- **Import errors:** fully async — validate + insert in the worker; the specific
  reject message surfaces in the job result the UI polls (no synchronous reject).
- **Architecture:** generalize the existing JSON queue into a typed job queue
  (Approach A), reusing all current plumbing (flock locking, parent+chunk model,
  snapshot/progress, status/download routes, sweep/TTL cleanup, poll loop).

## Architecture overview

```
Controller (enqueue)  →  writable/pdf_queue/*.json  →  run:json-pdf-queue worker
      │                                                        │
      │                                              JobRunner::processOne()
      │                                                        │ dispatch by type
      ▼                                          ┌─────────────┼───────────────┐
 returns {queued,                            pdf │        archive │   import / export
  job_id, status_url}                 JsonPdfRunner   ArchiveRunner  Import/ExportRunner
      │
      ▼
 Browser polls jobs/status/{id} → progress → done (download / count / summary)
```

## Section 1 — Data model

Keep the three JSON files and the `JsonPdfQueue` class (rename of concept only;
class name kept for back-compat). Generalize the job record:

```
{
  "job_id":        int,
  "parent_job_id": int|null,
  "chunk_index":   int|null,
  "total_chunks":  int,                                  // 0 for single jobs
  "type":          "pdf"|"archive"|"import"|"export",    // NEW
  "voucher_ids":   [int,...],     // chunk id list (pdf/archive); [] for import/export
  "payload":       { ... },       // NEW, type-specific (reason / file_path / format / filters)
  "progress":      { "total": int, "done": int },        // NEW, optional (import)
  "result":        { ... },       // NEW, optional (archived count / imported count / skipped)
  "created_by":    int,
  "created_at":    "Y-m-d H:i:s",
  "status":        "pending|processing|finalizing|done|failed",
  "file_path":     string|null,
  "completed_at":  "Y-m-d H:i:s"|null,
  "error_message": string|null
}
```

`type` defaults to `"pdf"` when absent so any in-flight legacy records keep working.

Two enqueue shapes:

- **Chunked** (`pdf`, `archive`): parent + N chunk records, identical to today's
  model. Existing `enqueueJob()` becomes a thin wrapper over a new
  `enqueueChunked(string $type, array $ids, int $userId, int $chunkSize, array $payload = [])`.
- **Single** (`import`, `export`): one parent record, `total_chunks = 0`, no chunk
  records. New `enqueueSingle(string $type, array $payload, int $userId)`.

`snapshot()` is extended:

- Chunked → percentage from chunk counts (unchanged behavior).
- Single → status plus `progress` and/or `result` read off the parent record.

## Section 2 — Worker + dispatch

The worker command keeps the name `run:json-pdf-queue` (cron / start scripts
reference it). An optional alias `run:job-queue` may point at the same command —
this is a rename for clarity, NOT a second worker. The drain loop calls a new
facade `JobRunner::processOne()`:

1. `JobQueue::claimNext()` atomically claims the next unit:
   - a pending **chunk** (for chunked types `pdf`/`archive`), OR
   - a pending **single parent** (`total_chunks = 0`, types `import`/`export`),
     using the same fair, least-served-parent scheduling already in
     `JsonPdfRunner::claimNextChunk()`, generalized to also pick claimable single
     parents.
2. Dispatch by `type` via a runner registry:
   - `pdf` → `JsonPdfRunner` (unchanged)
   - `archive` → `ArchiveRunner`
   - `import` → `ImportRunner`
   - `export` → `ExportRunner`
3. For chunked types, after a chunk completes, `tryFinalizeParent()` runs:
   - `pdf`: assemble final PDF/ZIP (today's logic).
   - `archive`: total the archived counts, write one bulk audit row, mark parent done.

`JsonPdfRunner` is left as-is for `pdf`. New runners are siblings implementing the
same `process(array $unit): bool` contract.

## Section 3 — Per-type runner behavior

### ArchiveRunner (chunked)

Each chunk archives its id subset using the exact logic of
`Admin\Voucher::archiveStudentsByIds()`, scoped to the chunk's ids:

1. Copy each row into `student_archive` (with `archive_reason` from `payload.reason`,
   `archived_by` from `created_by`, `archived_at` = now).
2. Null `audit_log.student_id` (and `voucher_id` if the column exists) for the chunk ids.
3. `DELETE FROM students WHERE student_id IN (chunk ids)`.

Parent finalize: sum the per-chunk archived counts into `result.archived`, write one
`ARCHIVE_STUDENT` bulk audit row, mark parent `done`. No output file.

Partial-failure: if a chunk fails, that chunk → `failed`, parent → `failed` with the
chunk error in `error_message`; already-archived chunks remain archived (no
cross-chunk rollback — same effective behavior as the current loop, but now reported).

### ImportRunner (single)

1. Read `payload.file_path` from `writable/imports/`.
2. Parse (PhpSpreadsheet for xlsx/xls, `fgetcsv` for csv) — same parsing as
   `VoucherImport::import()`.
3. Run the full validate-then-insert logic: header validation, duplicate
   voucher-no / duplicate-name pre-checks, per-row field validation, school
   resolution, inserts.
4. On any validation reject → parent `failed`, the specific message stored in
   `error_message` (e.g. "Import rejected on row 12: …").
5. On success → parent `done`, `result.imported` (+ skipped if any).
6. Update `progress.{total,done}` as rows insert so the UI shows coarse movement.
7. Delete the uploaded file when finished (success or fail).

### ExportRunner (single)

1. Resolve rows from `payload.ids` (selected) or full listing when empty
   (same as today's `export()`).
2. Build xlsx or csv per `payload.format` using the existing PhpSpreadsheet writer
   body.
3. Write the file to `writable/pdfs/students_export_<timestamp>.{xlsx|csv}`, set
   `file_path`, mark `done`.
   Status→done exposes a download URL.

## Section 4 — Endpoints + routes

Add one generic status/download controller (`App\Controllers\JobController`) so we
don't fork per feature:

- `GET jobs/status/(:num)` → `JobController::status`
  Generic snapshot JSON: `{ status, progress_percent, result, download_url, error_message }`.
  Owner/admin guard, same rule as `jsonPdfStatus`.
- `GET jobs/download/(:num)` → `JobController::download`
  Streams `file_path` for `done` export jobs. Reuses the pdf download guards
  (owner/admin, file exists, correct content-type).

Routed under both `admin/` and `user/` groups (and a shared top-level entry where
the current import/export routes live), mirroring the existing admin/user split.

Changed endpoints (admin + user mirror each other):

| Route                        | Before                   | After                                                                                                                                        |
| ---------------------------- | ------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| `vouchers/archive-all`       | sync loop, returns count | resolve ids → `enqueueChunked('archive', ids, user, CHUNK_SIZE, {reason})`, return `{queued, job_id, status_url}`                            |
| `vouchers/archive-by-filter` | sync loop                | same as above                                                                                                                                |
| `import_data`                | sync parse+insert        | move upload to `writable/imports/<jobid>_<name>`, `enqueueSingle('import', {file_path, original_name}, user)`, return `{queued, status_url}` |
| `vouchers/export`            | direct file download     | `enqueueSingle('export', {ids                                                                                                                | filters, format}, user)`, return `{queued, status_url}` |

`vouchers/archive` (selected, small) and Generate's `json-pdf-*` routes stay
unchanged. The `vouchers/count-matching` preview endpoint stays as-is (used by the
confirm modal).

## Section 5 — Frontend polling (`public/assets/js/custom/modal_instance.js`)

Generalize the existing Generate poll loop into one helper:

```
pollJob(statusUrl, { onProgress, onDone, onError })
```

It polls `jobs/status/{id}` on an interval, calls `onProgress(percent)` and
resolves on `done`/`failed`. Per trigger:

- **Archive All / By Filter:** show progress → on done toast "N archived" and
  refresh the DataTable.
- **Import:** show progress → on done toast "N imported"; on fail show
  `error_message` (the reject reason) in the existing error UI.
- **Export:** show progress → on done auto-trigger `download_url` (same hidden-link
  trick Generate uses for its download).

The Archive All count-preview confirm modal is unchanged; only the submit handler
swaps from synchronous POST to enqueue + `pollJob`.

## Section 6 — Files, cleanup, security

- New directory `writable/imports/` for uploads (worker-readable across requests;
  PHP temp files vanish after the request).
- Validate file extension + size **at upload time**, before enqueue (cheap), and
  reject bad files synchronously — only valid uploads become jobs.
- `sweepStaleFinished()` extended: in addition to `writable/pdfs/` outputs, unlink
  import uploads (`writable/imports/`) and export outputs referenced by finished
  jobs past the TTL. Same 10-minute TTL.
- Permission: `jobs/status` and `jobs/download` enforce `created_by == current user`
  or admin role, mirroring `jsonPdfStatus` / `jsonPdfDownload`.
- Archive is destructive and chunked: a mid-batch chunk failure marks the parent
  `failed` and surfaces the partial archived count; already-archived chunks are not
  rolled back (matches current partial-failure behavior, now visible to the user).

## Files touched (anticipated)

New:

- `app/Libraries/ArchiveRunner.php`
- `app/Libraries/ImportRunner.php`
- `app/Libraries/ExportRunner.php`
- `app/Libraries/JobRunner.php` (dispatch facade + generic claim)
- `app/Controllers/JobController.php` (generic status/download)

Modified:

- `app/Libraries/JsonPdfQueue.php` — `enqueueChunked`, `enqueueSingle`, generalized
  `claimNext`, extended `snapshot`.
- `app/Commands/ProcessJsonPdfQueue.php` — drain via `JobRunner::processOne()`;
  extend sweep to imports/exports; optional `run:job-queue` alias.
- `app/Controllers/Admin/Voucher.php` — `archiveAll`, `archiveByFilter` enqueue.
- `app/Controllers/User/Voucher.php` — mirror.
- `app/Controllers/VoucherImport.php` — `import`, `export` enqueue.
- `app/Config/Routes.php` — `jobs/status`, `jobs/download`; adjust changed routes.
- `public/assets/js/custom/modal_instance.js` — `pollJob` helper + per-trigger wiring.

## Testing

- Archive All over a large filter set: returns instantly; worker drains in chunks;
  DataTable reflects removal; one bulk audit row written.
- Import valid file: job → done, correct imported count, upload deleted.
- Import invalid file (dup voucher, bad date, header mismatch): job → failed, exact
  reject message shown.
- Export selected + export all, xlsx + csv: job → done, file downloads, content correct.
- Generate still works unchanged (regression): pdf jobs drain alongside the new types.
- One worker only: confirm all four types drain from a single `run:json-pdf-queue`.

```

```
