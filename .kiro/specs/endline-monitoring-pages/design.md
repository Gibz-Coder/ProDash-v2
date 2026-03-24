# Design Document — Endline Monitoring Pages

## Overview

Two new Inertia/React pages added to the existing Laravel application:

- QC Analysis Monitor (`/endline/qc-monitor`) — tracks lots with `work_type = 'QC_ANA'`
- Visual Technical Monitor (`/endline/vi-monitor`) — tracks lots with `work_type = 'VI_TECHL'`

Both pages share the same component structure and write exclusively to the existing `endline_delay` table.

---

## Architecture

### Backend (Laravel)

New methods added to `EndlineDelayController`:

| Method                | Route                                   | Purpose                               |
| --------------------- | --------------------------------------- | ------------------------------------- |
| `qcMonitor`           | `GET /api/endline-delay/qc-monitor`     | Returns pending + in-progress QC lots |
| `viMonitor`           | `GET /api/endline-delay/vi-monitor`     | Returns pending + in-progress VI lots |
| `startQc`             | `POST /api/endline-delay/{id}/start-qc` | Sets `qc_ana_start = now()`           |
| `startVi`             | `POST /api/endline-delay/{id}/start-vi` | Sets `vi_techl_start = now()`         |
| `update` _(existing)_ | `PUT /api/endline-delay/{id}`           | Submits result, computes TAT          |

No new database tables. No migrations required.

### Frontend (React + Inertia)

```
resources/js/pages/endline/
  QcMonitor.tsx
  ViMonitor.tsx
  components/
    MonitorTable.tsx   — shared lot table with color-coded rows
    StatCards.tsx      — Pending / In-Progress / Completed counts
    ResultModal.tsx    — decision dropdown + remarks submission
    ElapsedTimer.tsx   — live minute counter
```

---

## Data Model (existing `endline_delay` table)

| Column            | Type      | Monitor usage                           |
| ----------------- | --------- | --------------------------------------- |
| `id`              | bigint PK | row identifier                          |
| `lot_id`          | string    | display + lookup                        |
| `model`           | string    | display                                 |
| `lot_qty`         | integer   | display                                 |
| `work_type`       | string    | filter: `QC_ANA` or `VI_TECHL`          |
| `qc_ana_start`    | datetime  | set by start-qc; drives elapsed timer   |
| `qc_ana_result`   | string    | set on submission; NULL = not completed |
| `qc_ana_tat`      | integer   | computed minutes on submission          |
| `vi_techl_start`  | datetime  | set by start-vi; drives elapsed timer   |
| `vi_techl_result` | string    | set on submission; NULL = not completed |
| `vi_techl_tat`    | integer   | computed minutes on submission          |
| `updated_by`      | string    | set to auth user name on every write    |

---

## Lot Status Derivation

For QC lots:

- Pending: `qc_ana_start IS NULL`
- In-Progress: `qc_ana_start IS NOT NULL AND qc_ana_result IS NULL`
- Completed: `qc_ana_result IS NOT NULL`

For VI lots:

- Pending: `vi_techl_start IS NULL`
- In-Progress: `vi_techl_start IS NOT NULL AND vi_techl_result IS NULL`
- Completed: `vi_techl_result IS NOT NULL`

---

## TAT Computation

```
TAT (minutes) = floor((submission_timestamp - start_timestamp) / 60)
```

Computed server-side at the moment the PUT endpoint is called. Stored as an integer.

---

## UI Behavior

- Auto-refresh: `setInterval` polling every 30 000 ms; clears on unmount.
- Elapsed timer: `setInterval` every 60 000 ms updating local state; initialised from `(now - start_timestamp)` on mount.
- Row colors: Tailwind — pending: default, in-progress: `bg-amber-100`, completed: `bg-green-100`.
- F2 shortcut: `keydown` listener on the page; opens ResultModal for the selected row.
- Selected row: tracked in local state; clicking a row sets it as selected.

---

## Correctness Properties

_A property is a characteristic or behavior that should hold true across all valid executions of a system — a formal statement about what the system should do._

### Property 1: QC monitor filter correctness

_For any_ dataset of `endline_delay` rows with mixed `work_type` values and mixed `qc_ana_result` values, the `GET /api/endline-delay/qc-monitor` endpoint SHALL return exactly the subset where `work_type = 'QC_ANA'` AND `qc_ana_result IS NULL`, with no rows from any other subset included.

**Validates: Requirements 1.1, 6.1**

### Property 2: VI monitor filter correctness

_For any_ dataset of `endline_delay` rows with mixed `work_type` values and mixed `vi_techl_result` values, the `GET /api/endline-delay/vi-monitor` endpoint SHALL return exactly the subset where `work_type = 'VI_TECHL'` AND `vi_techl_result IS NULL`, with no rows from any other subset included.

**Validates: Requirements 2.1, 6.2**

### Property 3: Stat card counts partition

_For any_ set of lots in the current shift's dataset, the sum of Pending count + In-Progress count + Completed count SHALL equal the total number of lots, with no lot counted in more than one category.

**Validates: Requirements 1.2, 2.2**

### Property 4: Start action sets timestamp and is idempotent-safe

_For any_ Pending Lot, calling the start action SHALL set the relevant start timestamp to a non-null value. Calling the start action a second time on the same Lot SHALL return HTTP 422 and leave the original timestamp unchanged.

**Validates: Requirements 1.3, 1.4, 2.3, 2.4, 6.3, 6.4, 7.1, 7.2**

### Property 5: TAT computation correctness

_For any_ Lot with a known start timestamp, submitting a result at a known submission time SHALL produce a TAT value equal to `floor((submission_time - start_time) / 60)` in minutes, with no rounding up.

**Validates: Requirements 1.5, 2.5, 7.3**

### Property 6: Result submission decision validation

_For any_ attempt to submit the Result_Modal with a null or empty decision field, THE Result_Modal SHALL reject the submission and the API SHALL return a validation error, leaving the Lot record unchanged.

**Validates: Requirements 4.2**

### Property 7: Row color is a pure function of lot status

_For any_ Lot record, the CSS class applied to its table row SHALL be a deterministic function of its derived status (Pending: neutral, In-Progress: amber, Completed: green), independent of any other lot's state.

**Validates: Requirements 5.1**

### Property 8: Start action column isolation

_For any_ Lot, calling `start-qc` SHALL modify only `qc_ana_start` and `updated_by`; all other columns SHALL remain identical to their pre-call values. Calling `start-vi` SHALL modify only `vi_techl_start` and `updated_by`.

**Validates: Requirements 7.2**

### Property 9: Elapsed timer value correctness

_For any_ In-Progress Lot with a known `start_timestamp`, the value displayed by the Elapsed_Timer at any point in time SHALL equal `floor((current_time - start_timestamp) / 60)` minutes.

**Validates: Requirements 3.1, 3.2**
