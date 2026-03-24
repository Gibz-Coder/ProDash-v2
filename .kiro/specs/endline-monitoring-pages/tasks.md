# Implementation Plan: Endline Monitoring Pages

## Overview

Add two dedicated floor-monitoring pages (QC Analysis and VI Technical) backed by new API
endpoints on the existing `endline_delay` table. The implementation follows the existing
`EndlineDelayController` / `routes/web.php` / Vue + Inertia patterns already in the project.

---

## Tasks

- [x]   1. Add backend API endpoints for monitor pages
    - [x] 1.1 Add `qcAnalysisIndex()` and `viTechnicalIndex()` methods to `EndlineDelayController`
        - `qcAnalysisIndex()`: reuse `buildQuery()`, add `->where('work_type', 'QC_ANA')->whereNull('qc_ana_result')`, order by `created_at` asc
        - `viTechnicalIndex()`: same pattern with `work_type = 'VI_TECHL'` and `whereNull('vi_techl_result')`
        - Both methods must require auth and return the standard JSON envelope (`success`, `data`, `error`, `meta`)
        - Add `declare(strict_types=1)` and typed return types (`JsonResponse`) per coding-style rules
        - _Requirements: 1.1, 2.1, 6.1, 6.2, 6.7_

    - [x] 1.2 Add `startQcAnalysis(int $id)` and `startViTechnical(int $id)` methods to `EndlineDelayController`
        - Look up the row; return 404 if not found (_Requirements: 6.6_)
        - Guard against double-start: if the relevant start column is already set, return HTTP 422 with `"Already started"` message (_Requirements: 1.4, 2.4_)
        - On success: update only the relevant start column (`qc_ana_start` or `vi_techl_start`) to `now()` and `updated_by` to the authenticated user's name; leave all other columns unchanged (_Requirements: 1.3, 2.3, 7.2, 7.4_)
        - Return the updated row in the standard envelope
        - _Requirements: 1.3, 1.4, 2.3, 2.4, 6.3, 6.4, 7.2, 7.4_

    - [x] 1.3 Write Pest feature tests for the four new controller methods
        - Test `qcAnalysisIndex` returns only `work_type = 'QC_ANA'` rows with null `qc_ana_result`, ordered ascending
        - Test `viTechnicalIndex` returns only `work_type = 'VI_TECHL'` rows with null `vi_techl_result`, ordered ascending
        - Test `startQcAnalysis` stamps `qc_ana_start` and records `updated_by`; assert 422 on double-start
        - Test `startViTechnical` stamps `vi_techl_start` and records `updated_by`; assert 422 on double-start
        - Test unauthenticated requests return 401 (_Requirements: 6.7_)
        - Use `RefreshDatabase`, Pest syntax, and `actingAs($user)` per `laravel-tdd.md`
        - _Requirements: 1.1–1.4, 2.1–2.4, 6.1–6.4, 6.6, 6.7, 7.2, 7.4_

- [x]   2. Register new routes in `routes/web.php`
    - Add Inertia page routes inside the existing `permission:Manage Endline` middleware group:
        - `GET /qc-analysis` → `Inertia::render('dashboards/main/qc-analysis')`, named `qc_analysis`
        - `GET /vi-technical` → `Inertia::render('dashboards/main/vi-technical')`, named `vi_technical`
    - Add API routes inside the existing `permission:Manage Endline` middleware group:
        - `GET  api/endline-delay/qc-monitor` → `EndlineDelayController@qcAnalysisIndex`
        - `GET  api/endline-delay/vi-monitor` → `EndlineDelayController@viTechnicalIndex`
        - `POST api/endline-delay/{id}/start-qc` → `EndlineDelayController@startQcAnalysis`
        - `POST api/endline-delay/{id}/start-vi` → `EndlineDelayController@startViTechnical`
    - Note: `PUT api/endline-delay/{id}` already exists and is reused for result submission
    - _Requirements: 6.1–6.5, 6.7_

- [x]   3. Add route helpers and sidebar navigation links
    - [x] 3.1 Add `qc_analysis` and `vi_technical` route helper functions to `resources/js/routes/index.ts`
        - Follow the exact same pattern as `endline_delay` (export const, `.definition`, `.url`, `.get`, `.form`)
        - `qc_analysis.definition.url = '/qc-analysis'`
        - `vi_technical.definition.url = '/vi-technical'`
        - _Requirements: (enables frontend routing)_

    - [x] 3.2 Add navigation entries in `resources/js/components/AppSidebar.vue`
        - Import `qc_analysis` and `vi_technical` from `@/routes`
        - Add two entries to `allNavItems` after the existing `ENDLINE` entry:
            - `{ title: 'QC MONITOR', href: qc_analysis(), icon: '🔬' }`
            - `{ title: 'VI MONITOR', href: vi_technical(), icon: '🔍' }`
        - Apply the same role-based visibility logic already used for other items (hidden for `'user'` role; visible for `'qc part'` alongside ENDLINE)
        - _Requirements: (floor-friendly navigation)_

- [x]   4. Create shared composables
    - [x] 4.1 Create `resources/js/composables/useElapsedTimer.ts`
        - Accept a `Ref<string | null>` start timestamp; return a reactive `elapsedMinutes: ComputedRef<number>`
        - Use `setInterval` (60 s) to tick a reactive `now` ref; clean up with `onUnmounted`
        - Return `0` when start is null (pending lots)
        - _Requirements: 3.1, 3.2_

    - [x] 4.2 Write unit tests for `useElapsedTimer`
        - Test that elapsed minutes increase correctly as `now` advances
        - Test that elapsed is 0 when start timestamp is null
        - _Requirements: 3.1, 3.2_

    - [x] 4.3 Create `resources/js/composables/useMonitorPage.ts`
        - Accept `apiUrl: string` (e.g. `/api/endline-delay/qc-monitor`) and filter params
        - Expose: `records`, `loading`, `fetchRecords()`, `summaryStats` (pending / in-progress / completed counts)
        - Auto-refresh every 30 s via `setInterval`; clear on `onUnmounted`
        - `summaryStats` derives counts from `records` reactively (no extra API call)
        - _Requirements: 1.2, 1.6, 2.2, 2.6_

    - [x] 4.4 Write unit tests for `useMonitorPage` summary stats computation
        - Test pending count = rows where start is null
        - Test in-progress count = rows where start is set and result is null
        - Test completed count = rows where result is set
        - _Requirements: 1.2, 2.2_

- [x]   5. Create shared Result Submission Modal
    - Create `resources/js/pages/dashboards/subs/monitor-result-modal.vue`
    - Props: `lot` (the selected record object), `mode: 'qc' | 'vi'` (determines which fields to submit)
    - Display read-only context: `lot_id`, `model`, `lot_qty` (_Requirements: 4.5_)
    - Decision dropdown: `Proceed`, `Rework`, `Low Yield` (_Requirements: 4.1_)
    - Remarks textarea (optional)
    - Client-side validation: block submit if decision is empty; show inline error (_Requirements: 4.2_)
    - On submit: call `PUT /api/endline-delay/{id}` with the appropriate result field (`qc_ana_result` or `vi_techl_result`) plus `final_decision` and `remarks`
    - On success: emit `submitted` event so the parent page can close modal and refresh (_Requirements: 4.4_)
    - F2 key handling is owned by the parent page (see task 6 / 7); modal itself does not need to listen for F2
    - _Requirements: 4.1, 4.2, 4.4, 4.5, 6.5, 7.3, 7.4_

- [x]   6. Create QC Analysis monitor page
    - Create `resources/js/pages/dashboards/main/qc-analysis.vue`
    - Use `useMonitorPage('/api/endline-delay/qc-monitor', filters)` for data fetching and auto-refresh
    - Stat cards at top: Pending / In Progress / Completed counts from `summaryStats` (_Requirements: 1.2_)
    - Filter bar: date, shift, cutoff, worktype, lipas — identical to `endline-delay.vue` (_Requirements: 5.2_)
    - Table columns: Lot No, Model, Qty, LIPAS, Date Time, Status, Elapsed, Actions
    - Color-coded rows: gray/neutral = pending, amber = in-progress, green = completed (_Requirements: 5.1_)
    - Highlight selected row with a distinct ring/border (_Requirements: 5.3_)
    - "Start" button on pending rows → calls `POST /api/endline-delay/{id}/start-qc`; refreshes list on success (_Requirements: 1.3_)
    - "Submit Result" button on in-progress rows → opens `monitor-result-modal` (_Requirements: 4.1_)
    - Live elapsed timer on in-progress rows using `useElapsedTimer` (_Requirements: 3.1, 3.2_)
    - When a row transitions to completed after refresh, elapsed timer stops and TAT value is shown instead (_Requirements: 3.3_)
    - F2 key: opens `monitor-result-modal` for the currently selected in-progress row (_Requirements: 4.3, 5.4_)
    - _Requirements: 1.1–1.6, 3.1–3.3, 4.1–4.5, 5.1–5.4_

- [x]   7. Create VI Technical monitor page
    - Create `resources/js/pages/dashboards/main/vi-technical.vue`
    - Identical structure to `qc-analysis.vue` but targets VI Technical lots
    - Use `useMonitorPage('/api/endline-delay/vi-monitor', filters)` for data fetching
    - "Start" button calls `POST /api/endline-delay/{id}/start-vi` (_Requirements: 2.3_)
    - Modal `mode` prop set to `'vi'` so it writes `vi_techl_result` on submit (_Requirements: 2.5_)
    - Elapsed timer uses `vi_techl_start`; completed rows show `vi_techl_tat` (_Requirements: 3.1–3.3_)
    - All filter, color-coding, F2, and auto-refresh behaviour identical to QC Analysis page
    - _Requirements: 2.1–2.6, 3.1–3.3, 4.1–4.5, 5.1–5.4_

- [x]   8. Checkpoint — Ensure all tests pass
    - Run `php artisan test` and verify all Pest feature tests for the new endpoints pass
    - Smoke-test both monitor pages in the browser: load, start a lot, submit a result, confirm TAT is stored
    - Ensure all tests pass; ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- The existing `PUT /api/endline-delay/{id}` endpoint handles result submission; no new endpoint needed
- TAT computation (integer minutes, rounded down) is handled server-side in the existing `update()` method — the frontend sends `qc_ana_result` / `vi_techl_result` and the controller derives TAT from the stored start timestamp
- Route helpers in `resources/js/routes/index.ts` are auto-generated in this project; add them manually following the existing pattern
- All PHP files must include `declare(strict_types=1)` and typed return types per `coding-style.md`
