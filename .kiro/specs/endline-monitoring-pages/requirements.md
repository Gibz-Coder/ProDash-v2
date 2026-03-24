# Requirements Document

## Introduction

This feature adds two dedicated floor-monitoring pages to the existing endline delay tracking system:

1. **QC Analysis Monitor** — displays lots pending QC analysis (`work_type = 'QC_ANA'`), lets operators start analysis and submit results.
2. **Visual Technical Monitor** — displays lots pending VI technical verification (`work_type = 'VI_TECHL'`), lets operators start verification and submit results.

Both pages write exclusively to the existing `endline_delay` table. No new database tables are introduced. The UI is optimised for factory-floor use: large status indicators, color-coded rows, keyboard shortcuts, live elapsed timers, and auto-refresh every 30 seconds.

---

## Glossary

- **QC_Monitor**: The QC Analysis Monitor page component.
- **VI_Monitor**: The Visual Technical Monitor page component.
- **Monitor_Page**: Either QC_Monitor or VI_Monitor when a rule applies to both.
- **API**: The Laravel JSON API served under `/api/endline-delay/`.
- **Lot**: A single row in the `endline_delay` table representing one production lot.
- **Pending Lot**: A lot whose relevant start timestamp (`qc_ana_start` or `vi_techl_start`) is NULL.
- **In-Progress Lot**: A lot whose relevant start timestamp is set but whose result field (`qc_ana_result` or `vi_techl_result`) is NULL.
- **Completed Lot**: A lot whose relevant result field is not NULL.
- **TAT**: Turn-Around Time in minutes, computed as the difference between the result submission time and the start timestamp.
- **Result_Modal**: The modal dialog used to submit a decision and optional remarks for a lot.
- **Stat_Cards**: The summary cards at the top of each Monitor_Page showing Pending, In Progress, and Completed counts.
- **Elapsed_Timer**: A live, client-side counter showing minutes elapsed since a lot's start timestamp.

---

## Requirements

### Requirement 1: QC Analysis Monitor Page

**User Story:** As a QC operator, I want a dedicated monitor page for QC analysis lots, so that I can see which lots need attention, start analysis, and record results without navigating away.

#### Acceptance Criteria

1. WHEN the QC_Monitor page loads, THE API SHALL return only lots where `work_type = 'QC_ANA'` and `qc_ana_result IS NULL`, ordered by `created_at` ascending.
2. WHEN the QC_Monitor page loads, THE QC_Monitor SHALL display Stat_Cards showing the count of Pending Lots, In-Progress Lots, and Completed Lots for the current shift.
3. WHEN a user clicks the Start button on a Pending Lot, THE API SHALL set `qc_ana_start` to the current server timestamp and return the updated Lot record.
4. IF a Start action is requested on a Lot that already has `qc_ana_start` set, THEN THE API SHALL return HTTP 422 and leave the Lot record unchanged.
5. WHEN a user submits the Result_Modal for a QC lot, THE API SHALL record `qc_ana_result`, compute `qc_ana_tat` as the difference in minutes between `qc_ana_start` and the submission timestamp, and persist `updated_by` as the authenticated user's name.
6. WHEN the QC_Monitor page is open, THE QC_Monitor SHALL automatically refresh the lot list every 30 seconds without requiring user interaction.

---

### Requirement 2: Visual Technical Monitor Page

**User Story:** As a VI technician, I want a dedicated monitor page for VI technical lots, so that I can track pending verifications, start work, and submit results efficiently.

#### Acceptance Criteria

1. WHEN the VI_Monitor page loads, THE API SHALL return only lots where `work_type = 'VI_TECHL'` and `vi_techl_result IS NULL`, ordered by `created_at` ascending.
2. WHEN the VI_Monitor page loads, THE VI_Monitor SHALL display Stat_Cards showing the count of Pending Lots, In-Progress Lots, and Completed Lots for the current shift.
3. WHEN a user clicks the Start button on a Pending Lot, THE API SHALL set `vi_techl_start` to the current server timestamp and return the updated Lot record.
4. IF a Start action is requested on a Lot that already has `vi_techl_start` set, THEN THE API SHALL return HTTP 422 and leave the Lot record unchanged.
5. WHEN a user submits the Result_Modal for a VI lot, THE API SHALL record `vi_techl_result`, compute `vi_techl_tat` as the difference in minutes between `vi_techl_start` and the submission timestamp, and persist `updated_by` as the authenticated user's name.
6. WHEN the VI_Monitor page is open, THE VI_Monitor SHALL automatically refresh the lot list every 30 seconds without requiring user interaction.

---

### Requirement 3: Live Elapsed Timers

**User Story:** As a floor supervisor, I want to see how long each in-progress lot has been running, so that I can identify bottlenecks in real time.

#### Acceptance Criteria

1. WHILE a Lot is In-Progress, THE Monitor_Page SHALL display an Elapsed_Timer that counts up in minutes from the lot's start timestamp.
2. THE Elapsed_Timer SHALL update its displayed value every 60 seconds on the client side without triggering a full page refresh.
3. IF a Lot transitions from In-Progress to Completed during an auto-refresh, THEN THE Monitor_Page SHALL stop the Elapsed_Timer for that Lot and display the final TAT value instead.

---

### Requirement 4: Result Submission Modal

**User Story:** As an operator, I want a focused modal to submit my analysis decision and remarks, so that I can record results quickly without leaving the monitor view.

#### Acceptance Criteria

1. WHEN a user opens the Result_Modal, THE Result_Modal SHALL display a decision dropdown and a remarks text field.
2. IF a user attempts to submit the Result_Modal without selecting a decision, THEN THE Result_Modal SHALL prevent submission and display a validation error message.
3. WHEN the F2 key is pressed while the Monitor_Page is focused, THE Monitor_Page SHALL open the Result_Modal for the currently selected Lot.
4. WHEN a result is successfully submitted via the Result_Modal, THE Result_Modal SHALL close and THE Monitor_Page SHALL refresh the lot list immediately.
5. WHEN the Result_Modal is open, THE Result_Modal SHALL display the lot ID, model, and lot quantity as read-only context fields.

---

### Requirement 5: Floor-Friendly UI

**User Story:** As a factory-floor operator, I want a UI that is easy to read and interact with from a distance, so that I can use the monitor pages efficiently in a noisy, fast-paced environment.

#### Acceptance Criteria

1. THE Monitor_Page SHALL apply color-coded row backgrounds based on Lot status: a neutral background for Pending Lots, an amber background for In-Progress Lots, and a green background for Completed Lots.
2. THE Monitor_Page SHALL use large, readable status indicators and font sizes suitable for floor-level display screens.
3. THE Monitor_Page SHALL highlight the currently selected row with a distinct visual indicator.
4. WHEN a user presses the F2 key, THE Monitor_Page SHALL treat it as equivalent to clicking the Submit Result button for the selected row.

---

### Requirement 6: API Endpoints

**User Story:** As a frontend developer, I want well-defined API endpoints for the monitor pages, so that I can build reliable data-fetching and action flows.

#### Acceptance Criteria

1. THE API SHALL expose `GET /api/endline-delay/qc-monitor` returning lots filtered to `work_type = 'QC_ANA'` with `qc_ana_result IS NULL`, ordered by `created_at` ascending.
2. THE API SHALL expose `GET /api/endline-delay/vi-monitor` returning lots filtered to `work_type = 'VI_TECHL'` with `vi_techl_result IS NULL`, ordered by `created_at` ascending.
3. THE API SHALL expose `POST /api/endline-delay/{id}/start-qc` that sets `qc_ana_start` to the current timestamp on the identified Lot.
4. THE API SHALL expose `POST /api/endline-delay/{id}/start-vi` that sets `vi_techl_start` to the current timestamp on the identified Lot.
5. THE API SHALL reuse the existing `PUT /api/endline-delay/{id}` endpoint for result submission from both Monitor_Pages.
6. IF a request is made to any monitor endpoint with an `{id}` that does not exist in `endline_delay`, THEN THE API SHALL return HTTP 404 with a descriptive error message.
7. WHEN any monitor API endpoint is called, THE API SHALL require an authenticated session and return HTTP 401 for unauthenticated requests.

---

### Requirement 7: Data Integrity

**User Story:** As a system administrator, I want all monitor actions to write correctly to the existing endline_delay table, so that downstream reporting and the main endline delay view remain accurate.

#### Acceptance Criteria

1. THE API SHALL write all monitor actions exclusively to the `endline_delay` table without creating or modifying any other tables.
2. WHEN a start action is performed, THE API SHALL only update the relevant start timestamp column (`qc_ana_start` or `vi_techl_start`) and leave all other columns unchanged.
3. WHEN a result is submitted, THE API SHALL compute and store the TAT value as an integer number of minutes, rounding down any partial minute.
4. WHEN any write operation is performed, THE API SHALL record the authenticated user's name in the `updated_by` column.
