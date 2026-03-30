# Endline Monitoring System — Feature & Business Logic Report

**Project:** VICIS ProDash V2
**Module:** Endline Delay Monitoring
**Report Framework:** DMAIC (Define · Measure · Analyze · Improve · Control)
**Date:** 2026-03-30
**Version:** 2.0

---

## Table of Contents

1. [Define](#1-define)
2. [Measure](#2-measure)
3. [Analyze](#3-analyze)
4. [Improve](#4-improve)
5. [Control](#5-control)
6. [Lot Flow Diagrams](#6-lot-flow-diagrams)
7. [API Reference](#7-api-reference)
8. [Database Schema](#8-database-schema)

---

## 1. Define

### 1.1 Problem Statement

Endline lots that fail QC inspection require multi-step disposition — QC Analysis, VI Technical verification, or direct production release. Without a centralized tracking system, lot status visibility was fragmented across manual logs, causing delays in disposition decisions, missed lots, and inability to measure turnaround time (TAT) per stage.

### 1.2 Scope

The Endline Monitoring System covers the full lifecycle of a lot from the moment it is flagged at endline inspection through final disposition (production release, rework, or completion). It spans four monitoring pages:

| Page          | Audience               | Purpose                                                |
| ------------- | ---------------------- | ------------------------------------------------------ |
| Endline Delay | QC Inspector / Admin   | Data entry — log lots with QC result and defect codes  |
| QC Analysis   | QC Inspector / QC Part | Monitor and decide lots routed to QC Analysis          |
| VI Technical  | QC Inspector / QC Part | Monitor and decide lots routed to VI Technical         |
| QC OK         | QC Inspector / QC Part | Monitor QC OK lots and track production release status |

### 1.3 Business Objectives

- Provide real-time visibility of lot disposition status across all endline stages
- Eliminate manual tracking and reduce missed lots
- Enable TAT measurement per stage (QC Analysis, VI Technical)
- Support data-driven decisions via summary cards, charts, and filters
- Automate output status updates via MES WIP cross-reference

### 1.4 Stakeholders

| Role         | Access Level                                                       |
| ------------ | ------------------------------------------------------------------ |
| Admin        | Full access — entry, edit, delete, all pages                       |
| Manager      | Full access — entry, edit, delete, all pages                       |
| Moderator    | Full access — entry, edit, delete, all pages                       |
| QC Inspector | Entry, edit, all monitoring pages                                  |
| QC Part      | Read + status update — QC Analysis, VI Technical, QC OK pages only |

---

## 2. Measure

### 2.1 Key Data Points Captured Per Lot

| Field                   | Description                                                     |
| ----------------------- | --------------------------------------------------------------- |
| `lot_id`                | Unique lot identifier (max 7 chars, uppercase)                  |
| `model`                 | Chip model (auto-filled from MES lookup)                        |
| `lot_qty`               | Lot quantity in pcs (auto-filled from MES)                      |
| `lipas_yn`              | LIPAS flag Y/N (auto-filled from MES monthly plan)              |
| `work_type`             | NORMAL / PROCESS RW / WH REWORK / OI REWORK                     |
| `qc_result`             | QC inspection result: OK, Main, RR, LY, or combination          |
| `qc_defect`             | Defect code(s) — comma-separated, autocomplete from master list |
| `defect_class`          | Routing class: `QC Analysis` or `Tech'l Verification`           |
| `inspection_times`      | Number of inspection cycles                                     |
| `qc_ana_start`          | Timestamp when QC Analysis started                              |
| `qc_ana_prog`           | In-progress status (MOLD, RELI, Dipping, etc.)                  |
| `qc_ana_result`         | Final QC Analysis result: Proceed, Rework, DRB Approval         |
| `qc_ana_completed_at`   | Timestamp when QC Analysis was completed                        |
| `vi_techl_start`        | Timestamp when VI Technical started                             |
| `vi_techl_prog`         | In-progress status (DRB Approval, For Decision, etc.)           |
| `vi_techl_result`       | Final VI Technical result: Proceed, Rework                      |
| `vi_techl_completed_at` | Timestamp when VI Technical was completed                       |
| `final_decision`        | Current disposition state (see §3.2)                            |
| `output_status`         | Production output state: Pending, Rework, Completed             |
| `remarks`               | Auto-generated or manual notes                                  |
| `updated_by`            | Last user to update the record                                  |

### 2.2 QC Result Classification

| QC Result                     | Meaning                  | Routing                                         |
| ----------------------------- | ------------------------ | ----------------------------------------------- |
| `OK`                          | Lot passed QC inspection | QC OK page — awaits production release decision |
| `Main`                        | Main lot defect          | Follows defect code routing                     |
| `RR`                          | Re-rework defect         | Follows defect code routing                     |
| `LY`                          | Low yield defect         | Follows defect code routing                     |
| Combination (e.g. `Main, RR`) | Multiple defect types    | Follows defect code routing                     |

### 2.3 Summary Card Metrics (QC OK Page)

| Card                   | Metric Definition                                                                                                                                   |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Total**              | Sum of `lot_qty` for all records matching current filters                                                                                           |
| **QC Pending**         | QC OK source lots with `defect_class = 'QC Analysis'` and no `qc_ana_result`                                                                        |
| **Technical Pending**  | Lots with `defect_class = "Tech'l Verification"`, `vi_techl_start` set, no `vi_techl_result`                                                        |
| **Production Pending** | Fresh QC OK lots (no routing) + `final_decision = 'For Verify'` + `qc_ana_result = 'Proceed'` + `vi_techl_result = 'Proceed'` — excluding finalized |
| **Completed**          | Lots where `output_status IN ('Completed', 'Rework')`                                                                                               |
| **Prev Days Pending**  | Lots from prior dates with no `qc_ana_result` and no `vi_techl_result`                                                                              |

---

## 3. Analyze

### 3.1 Root Cause of Tracking Gaps (Pre-System)

| Gap                           | Impact                                              |
| ----------------------------- | --------------------------------------------------- |
| No centralized lot status log | Lots lost between QC and VI Technical handoffs      |
| Manual TAT tracking           | Inaccurate TAT data, no trend visibility            |
| No MES cross-reference        | Completed lots remained in tracking as "pending"    |
| No role-based visibility      | All users saw all data regardless of responsibility |

### 3.2 Final Decision State Machine

The `final_decision` column drives lot routing and page visibility:

| Value         | Meaning                                     | Visible On                      |
| ------------- | ------------------------------------------- | ------------------------------- |
| `Pending`     | Awaiting action                             | QC Analysis / VI Technical page |
| `QC OK`       | Entered with QC result = OK                 | QC OK page                      |
| `In Progress` | Intermediate step (MOLD, RELI, DRB, etc.)   | QC Analysis / VI Technical page |
| `Technical`   | Handed off from QC Analysis to VI Technical | VI Technical page               |
| `Proceed`     | Cleared by QC Analysis or VI Technical      | QC OK page (Production Pending) |
| `For Verify`  | Manually set to "For Verify (Production)"   | QC OK page (Production Pending) |
| `Recovery`    | Low Yield (Rework) — no re-routing          | QC OK page (Completed)          |
| `Rework`      | Confirmed rework decision                   | QC OK page (Completed)          |

### 3.3 Output Status State Machine

| Value       | Set When                                                                                    | Effect                                                     |
| ----------- | ------------------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| `Pending`   | On entry (store/update)                                                                     | Lot is active and actionable                               |
| `Rework`    | QC Analysis or VI Technical decides Rework on a QC OK lot; or "Low Yield (Rework)" selected | Lot is finalized as rework — Update Status button disabled |
| `Completed` | "Completed" selected in Update Status modal; or Auto Update finds lot absent from MES WIP   | Lot is finalized as output — Update Status button disabled |

---

## 4. Improve

### 4.1 Lot Flow Diagrams

#### Flow A — QC OK Direct Entry

```
┌─────────────────────────────────────────────────────────────────┐
│  ENDLINE DELAY ENTRY MODAL                                      │
│  QC Result = OK  |  output_status = Pending                     │
│  final_decision = QC OK                                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  QC OK PAGE — Production Pending card                           │
│  Output Status: Pending                                         │
└────────────────────────┬────────────────────────────────────────┘
                         │
          ┌──────────────┼──────────────────────┐
          │              │                      │
          ▼              ▼                      ▼
  ┌──────────────┐ ┌──────────────┐   ┌──────────────────────┐
  │ Route to     │ │ Route to     │   │ No Routing           │
  │ QC Analysis  │ │ VI Technical │   │                      │
  │              │ │              │   │ Low Yield (Rework)   │
  │ Waiting MOLD │ │ Real NG Scan │   │ → output_status=Rework│
  │ Waiting Reli │ │ Experiment   │   │                      │
  │ Waiting Dip  │ │ For Schedule │   │ For Verify (Prod)    │
  │ etc.         │ │ (Yield)      │   │ → final_decision=    │
  └──────┬───────┘ └──────┬───────┘   │   For Verify         │
         │                │           │                      │
         ▼                ▼           │ Completed            │
  ┌──────────────┐ ┌──────────────┐   │ → output_status=     │
  │ QC ANALYSIS  │ │ VI TECHNICAL │   │   Completed          │
  │ PAGE         │ │ PAGE         │   └──────────────────────┘
  └──────┬───────┘ └──────┬───────┘
         │                │
    ┌────┴────┐       ┌────┴────┐
    │         │       │         │
    ▼         ▼       ▼         ▼
 Proceed   Rework  Proceed   Rework
    │         │       │         │
    ▼         ▼       ▼         ▼
 remarks:  remarks: remarks:  remarks:
 "X - OK"  "X - NG" "X - OK"  "X - NG"
    │         │       │         │
    ▼         ▼       ▼         ▼
 QC OK     QC OK   QC OK     QC OK
 Prod      Completed Prod    Completed
 Pending   (Rework) Pending  (Rework)
```

---

#### Flow B — Non-QC-OK Lot (Main / RR / LY) via QC Analysis

```
┌─────────────────────────────────────────────────────────────────┐
│  ENDLINE DELAY ENTRY MODAL                                      │
│  QC Result = Main / RR / LY (any combination)                  │
│  Defect Code = QC Analysis type                                 │
│  defect_class = 'QC Analysis'                                   │
│  output_status = Pending  |  final_decision = Pending           │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │  QC ANALYSIS PAGE   │
              │  Pending card       │
              └──────────┬──────────┘
                         │
              ┌──────────┼──────────────────┐
              │          │                  │
              ▼          ▼                  ▼
           Proceed     Rework          DRB Approval /
              │          │             In Progress
              ▼          ▼                  │
        qc_ana_result  qc_ana_result        ▼
        = Proceed      = Rework        vi_techl_start
        final_decision = Technical          │
        = Proceed      vi_techl_start       ▼
              │        = now()        VI TECHNICAL
              ▼             │         PAGE
        QC OK PAGE          ▼
        Production     VI TECHNICAL
        Pending        PAGE
        remarks:            │
        "DEFECT - Proceed"  ├── Proceed → QC OK Page
                            │   Production Pending
                            │   remarks: "DEFECT - Proceed"
                            │
                            └── Rework → final_decision=Rework
```

---

#### Flow C — Non-QC-OK Lot via VI Technical (Direct)

```
┌─────────────────────────────────────────────────────────────────┐
│  ENDLINE DELAY ENTRY MODAL                                      │
│  QC Result = Main / RR / LY                                     │
│  Defect Code = Tech'l Verification type                         │
│  defect_class = "Tech'l Verification"                           │
│  output_status = Pending  |  final_decision = Pending           │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              ┌─────────────────────┐
              │  VI TECHNICAL PAGE  │
              │  Pending card       │
              └──────────┬──────────┘
                         │
              ┌──────────┴──────────┐
              │                     │
              ▼                     ▼
           Proceed               Rework
              │                     │
              ▼                     ▼
        vi_techl_result        vi_techl_result
        = Proceed              = Rework
        final_decision         final_decision
        = Proceed              = Rework
              │
              ▼
        QC OK PAGE
        Production Pending
        remarks: "DEFECT - Proceed"
```

---

#### Flow D — Auto Update (MES WIP Cross-Reference)

```
┌─────────────────────────────────────────────────────────────────┐
│  QC OK PAGE — Auto Update Button                                │
│  Scope: all pending lots in current filtered view               │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              For each lot where
              output_status NOT IN ('Completed', 'Rework')
                         │
              ┌──────────┴──────────┐
              │                     │
              ▼                     ▼
     lot_no + current_qty      NOT FOUND in
     FOUND in                  mes_data.wip_status
     mes_data.wip_status            │
              │                     ▼
              ▼              output_status = 'Completed'
         No change           (lot has been output or
         (still in WIP)       is no longer in visual WIP)
```

---

### 4.2 QC OK Update Status — Routing Options

| Option                  | Group                 | Effect                                                                                   |
| ----------------------- | --------------------- | ---------------------------------------------------------------------------------------- |
| Waiting MOLD            | Route to QC Analysis  | `defect_class='QC Analysis'`, `qc_ana_start=now()`, `final_decision='Pending'`           |
| Waiting Reli            | Route to QC Analysis  | Same as above                                                                            |
| Waiting Dipping         | Route to QC Analysis  | Same as above                                                                            |
| Waiting Reflow          | Route to QC Analysis  | Same as above                                                                            |
| Waiting OI Size         | Route to QC Analysis  | Same as above                                                                            |
| For Decision (QC)       | Route to QC Analysis  | Same as above                                                                            |
| Real NG Scan            | Route to VI Technical | `defect_class="Tech'l Verification"`, `vi_techl_start=now()`, `final_decision='Pending'` |
| Experiment              | Route to VI Technical | Same as above                                                                            |
| For Schedule (Yield)    | Route to VI Technical | Same as above                                                                            |
| Low Yield (Rework)      | No Routing            | `final_decision='Recovery'`, `output_status='Rework'`                                    |
| For Verify (Production) | No Routing            | `final_decision='For Verify'`                                                            |
| Completed               | No Routing            | `output_status='Completed'`                                                              |

### 4.3 Remarks Auto-Generation Rules

| Scenario                           | Remarks Format                                         |
| ---------------------------------- | ------------------------------------------------------ |
| QC OK lot routed via Update Status | `"{routing_option}"` (e.g. `"Waiting Reli"`)           |
| QC OK lot — QC Analysis Proceed    | `"{routing_option} - OK"` (e.g. `"Waiting Reli - OK"`) |
| QC OK lot — QC Analysis Rework     | `"{routing_option} - NG"` (e.g. `"Waiting MOLD - NG"`) |
| QC OK lot — VI Technical Proceed   | `"{routing_option} - OK"`                              |
| QC OK lot — VI Technical Rework    | `"{routing_option} - NG"`                              |
| Non-QC-OK lot — QC/VI Proceed      | `"{defect_code} - Proceed"` (e.g. `"EER - Proceed"`)   |
| Low Yield (Rework)                 | `"Low Yield (Rework)"`                                 |
| For Verify (Production)            | `"For Verify (Production)"`                            |

Remarks color coding in QC OK table:

- Contains `ok` or `proceed` → **green**
- Contains `ng` or `rework` → **red**
- Otherwise → default foreground

---

## 5. Control

### 5.1 Permission Matrix

| Permission                   | Admin | Manager | Moderator | QC Inspector | QC Part |
| ---------------------------- | :---: | :-----: | :-------: | :----------: | :-----: |
| View Endline Delay           |   ✓   |    ✓    |     ✓     |      ✓       |    —    |
| Add / Edit Endline Entry     |   ✓   |    ✓    |     ✓     |      ✓       |    —    |
| Delete Endline Entry         |   ✓   |    ✓    |     ✓     |      —       |    —    |
| View QC Analysis             |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| Submit QC Analysis Decision  |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| View VI Technical            |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| Submit VI Technical Decision |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| View QC OK                   |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| Update QC OK Status          |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |
| Auto Update (MES sync)       |   ✓   |    ✓    |     ✓     |      ✓       |    ✓    |

### 5.2 Data Integrity Controls

- `lot_id` is required and uppercased on entry; max 7 characters
- `qc_result` must be selected before saving (validated client-side)
- `output_status` is always set to `'Pending'` on store and edit — only explicit actions can finalize it
- Finalized lots (`output_status = 'Completed'` or `'Rework'`) have the Update Status button disabled
- Remarks suffix is stripped before re-appending on re-decision (prevents double-appending)
- Auto Update only processes non-finalized lots — finalized lots are never overwritten

### 5.3 Auto-Refresh Configuration

All monitoring pages support configurable auto-refresh:

| Setting  | Options                              |
| -------- | ------------------------------------ |
| Toggle   | On / Off (persisted in localStorage) |
| Interval | 15s / 30s / 1m / 2m / 5m             |

### 5.4 Filter Controls (All Monitoring Pages)

| Filter   | Options                                                                                 |
| -------- | --------------------------------------------------------------------------------------- |
| Date     | Date picker (defaults to today, Asia/Manila timezone)                                   |
| Shift    | ALL / DAY (07:00–18:59) / NIGHT (19:00–06:59)                                           |
| Cutoff   | ALL / 00:00~03:59 / 04:00~06:59 / 07:00~11:59 / 12:00~15:59 / 16:00~18:59 / 19:00~23:59 |
| Worktype | ALL / NORMAL / PROCESS RW / WH REWORK / OI REWORK                                       |
| LIPAS    | ALL / Y / N                                                                             |
| Unit     | pcs / Kpcs / Mpcs                                                                       |

Search drops the date filter and searches across all dates (350ms debounce).

### 5.5 QC OK Page — Card Filter Behavior

Clicking a summary card filters the raw data table to show only matching lots. Clicking the same card again (or clicking Total) resets to show all. Active card is highlighted with a colored ring.

| Card               | Filter Logic                                                                                                                                |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Total              | Show all (clear filter)                                                                                                                     |
| QC Pending         | `qc_result='OK'` AND `defect_class='QC Analysis'` AND `qc_ana_result IS NULL`                                                               |
| Technical Pending  | `defect_class="Tech'l Verification"` AND `vi_techl_start IS NOT NULL` AND `vi_techl_result IS NULL`                                         |
| Production Pending | Fresh QC OK (no routing) OR `final_decision='For Verify'` OR `qc_ana_result='Proceed'` OR `vi_techl_result='Proceed'` — excluding finalized |
| Completed          | `output_status IN ('Completed', 'Rework')`                                                                                                  |
| Prev Days Pending  | `created_at < today` AND `qc_ana_result IS NULL` AND `vi_techl_result IS NULL`                                                              |

---

## 6. Lot Flow Diagrams

### 6.1 Complete System Flow (All Paths)

```
                    ┌──────────────────────────────┐
                    │     ENDLINE DELAY ENTRY       │
                    │  (Multi-row modal, MES lookup) │
                    └──────────────┬───────────────┘
                                   │
                    ┌──────────────┴───────────────┐
                    │         QC RESULT?            │
                    └──────────────┬───────────────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
              ▼                    ▼                    ▼
           qc_result            qc_result           qc_result
           = OK                 = Main/RR/LY        = Main/RR/LY
              │                 defect_class        defect_class
              │                 = QC Analysis       = Tech'l Verif.
              │                    │                    │
              ▼                    ▼                    ▼
         QC OK PAGE          QC ANALYSIS           VI TECHNICAL
         (Prod Pending)       PAGE                  PAGE
              │                    │                    │
              │           ┌────────┴────────┐  ┌───────┴───────┐
              │           │                 │  │               │
              │        Proceed           Rework/           Proceed
              │           │             DRB Appr.              │
              │           ▼                 │              ▼
              │      QC OK PAGE             │         QC OK PAGE
              │      (Prod Pending)         ▼         (Prod Pending)
              │      remarks:          VI TECHNICAL
              │      "CODE - Proceed"  PAGE
              │                            │
              │                   ┌────────┴────────┐
              │                   │                 │
              │                Proceed           Rework
              │                   │                 │
              │                   ▼                 ▼
              │              QC OK PAGE        final_decision
              │              (Prod Pending)    = Rework
              │              remarks:
              │              "CODE - Proceed"
              │
              ├─── Update Status Modal ──────────────────────────┐
              │                                                   │
              │    Route to QC Analysis ──► QC ANALYSIS PAGE     │
              │    Route to VI Technical ──► VI TECHNICAL PAGE    │
              │    Low Yield (Rework) ──► output_status=Rework    │
              │    For Verify (Prod) ──► final_decision=For Verify│
              │    Completed ──► output_status=Completed          │
              │                                                   │
              └───────────────────────────────────────────────────┘
              │
              ├─── Auto Update Button ────────────────────────────┐
              │                                                   │
              │    For each pending lot:                          │
              │    Check mes_data.wip_status                      │
              │    NOT FOUND → output_status = Completed          │
              │                                                   │
              └───────────────────────────────────────────────────┘
```

### 6.2 Output Status Lifecycle

```
  ┌─────────┐
  │ PENDING │  ◄── Set on every store() and update()
  └────┬────┘
       │
       ├──────────────────────────────────────────────────────┐
       │                                                      │
       │  QC Analysis Rework (QC OK lot)                      │
       │  VI Technical Rework (QC OK lot)                     │
       │  Low Yield (Rework) selected                         │
       ▼                                                      │
  ┌─────────┐                                                 │
  │  REWORK │  (final — Update Status button disabled)        │
  └─────────┘                                                 │
                                                              │
       │  "Completed" selected in Update Status modal         │
       │  Auto Update: lot NOT found in mes_data.wip_status   │
       ▼                                                      │
  ┌───────────┐                                               │
  │ COMPLETED │  (final — Update Status button disabled)      │
  └───────────┘◄─────────────────────────────────────────────┘
```

---

## 7. API Reference

| Method | Endpoint                                      | Controller Method      | Description                                       |
| ------ | --------------------------------------------- | ---------------------- | ------------------------------------------------- |
| GET    | `/api/endline-delay`                          | `index`                | All endline records (with filters)                |
| POST   | `/api/endline-delay`                          | `store`                | Create new endline entry                          |
| PUT    | `/api/endline-delay/{id}`                     | `update`               | Edit existing entry                               |
| DELETE | `/api/endline-delay/{id}`                     | `destroy`              | Delete entry (requires Delete Endline permission) |
| GET    | `/api/endline-delay/qc-monitor`               | `qcAnalysisIndex`      | QC Analysis monitoring records                    |
| GET    | `/api/endline-delay/vi-monitor`               | `viTechnicalIndex`     | VI Technical monitoring records                   |
| GET    | `/api/endline-delay/qc-ok-monitor`            | `qcOkIndex`            | QC OK monitoring records + meta counts            |
| GET    | `/api/endline-delay/chart-data`               | `chartData`            | Chart data (pie / bar / column)                   |
| GET    | `/api/endline-delay/lot-lookup`               | `lotLookup`            | MES WIP lot lookup                                |
| GET    | `/api/endline-delay/defect-codes`             | `defectCodeSearch`     | Defect code autocomplete                          |
| GET    | `/api/endline-delay/export`                   | `export`               | CSV export                                        |
| POST   | `/api/endline-delay/{id}/start-qc`            | `startQcAnalysis`      | Stamp QC Analysis start time                      |
| POST   | `/api/endline-delay/{id}/start-vi`            | `startViTechnical`     | Stamp VI Technical start time                     |
| POST   | `/api/endline-delay/{id}/submit-qc`           | `submitQcAnalysis`     | Submit QC Analysis decision                       |
| POST   | `/api/endline-delay/{id}/submit-vi`           | `submitViTechnical`    | Submit VI Technical decision                      |
| POST   | `/api/endline-delay/{id}/update-qc-ok-status` | `updateQcOkStatus`     | Update QC OK lot status/routing                   |
| POST   | `/api/endline-delay/auto-update-qc-ok`        | `autoUpdateQcOkStatus` | Bulk MES WIP cross-reference update               |

---

## 8. Database Schema

### Table: `endline_delay`

| Column                  | Type        | Nullable | Description                       |
| ----------------------- | ----------- | -------- | --------------------------------- |
| `id`                    | bigint (PK) | No       | Auto-increment primary key        |
| `lot_id`                | varchar     | No       | Lot number                        |
| `model`                 | varchar     | Yes      | Chip model                        |
| `lot_qty`               | integer     | Yes      | Lot quantity (pcs)                |
| `lipas_yn`              | varchar     | Yes      | LIPAS flag (Y/N)                  |
| `qc_result`             | varchar     | Yes      | QC inspection result              |
| `qc_defect`             | varchar     | Yes      | Defect code(s), comma-separated   |
| `defect_class`          | varchar     | Yes      | Routing class                     |
| `qc_ana_start`          | datetime    | Yes      | QC Analysis start timestamp       |
| `qc_ana_prog`           | varchar     | Yes      | QC Analysis in-progress status    |
| `qc_ana_result`         | varchar     | Yes      | QC Analysis final result          |
| `qc_ana_completed_at`   | datetime    | Yes      | QC Analysis completion timestamp  |
| `vi_techl_start`        | datetime    | Yes      | VI Technical start timestamp      |
| `vi_techl_prog`         | varchar     | Yes      | VI Technical in-progress status   |
| `vi_techl_result`       | varchar     | Yes      | VI Technical final result         |
| `vi_techl_completed_at` | datetime    | Yes      | VI Technical completion timestamp |
| `final_decision`        | varchar     | Yes      | Current disposition state         |
| `output_status`         | varchar     | Yes      | Production output state           |
| `total_tat`             | integer     | Yes      | Total turnaround time (minutes)   |
| `remarks`               | text        | Yes      | Notes / auto-generated status     |
| `inspection_times`      | integer     | Yes      | Number of inspection cycles       |
| `work_type`             | varchar     | Yes      | Work type classification          |
| `updated_by`            | varchar     | Yes      | Last updated by (user name)       |
| `created_at`            | timestamp   | Yes      | Record creation timestamp         |
| `updated_at`            | timestamp   | Yes      | Record last update timestamp      |

### MES Cross-Reference: `mes_data.wip_status`

| Column                | Used For                              |
| --------------------- | ------------------------------------- |
| `lot_no`              | Match against `endline_delay.lot_id`  |
| `current_qty`         | Match against `endline_delay.lot_qty` |
| `model_id`            | Auto-fill model on lot lookup         |
| `rework_type`         | Derive work_type on lot lookup        |
| `warehouse_rework_yn` | Derive WH REWORK work_type            |

---

_Report generated from VICIS ProDash V2 codebase — 2026-03-30_
