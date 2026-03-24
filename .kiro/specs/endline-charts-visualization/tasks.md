# Implementation Plan: Endline Charts Visualization

## Overview

Wire the three-chart section (pie, horizontal stacked bar, stacked column) in the QC Analysis and VI Technical monitoring pages to real data from the `endline_delay` table. The work proceeds in four phases: database migration, backend service + endpoint, shared frontend composable, and page wiring.

## Tasks

- [x]   1. Add composite index migration for endline_delay
    - Create a new migration file in `database/migrations/` that adds a composite index on `endline_delay (defect_class, created_at)`
    - Include a `down()` method that drops the index
    - _Requirements: 10.2_

- [x]   2. Create EndlineChartService with aggregation logic
    - Create `app/Services/EndlineChartService.php` as a `final` class with `declare(strict_types=1)`
    - Implement `private function buildBaseQuery(array $filters): \Illuminate\Database\Query\Builder` that reuses the same date/shift/cutoff/work_type/lipas filter logic as `EndlineDelayController::buildQuery()`, scoped to `defect_class`
    - Implement `public function getChartData(array $filters): array` that returns the `ChartDataDto` shape `{ pie, bar, column }`
    - Implement `private function aggregateByWorkTypeBucket(Collection $rows): array` — maps NORMAL→Mainlot, PROCESS RW/WH REWORK→R-rework, OI REWORK→L-rework, null/other→Mainlot; returns `{ labels: [...], series: [...] }`
    - Implement `private function pivotToStackedSeries(Collection $rows, array $xCategories, array $seriesNames): array` — fills zeros for missing combinations; every series `data` array length equals `count($xCategories)`
    - Pie query: `SELECT work_type, COUNT(*) as cnt FROM endline_delay WHERE ... GROUP BY work_type`
    - Bar query: `SELECT SUBSTRING(model,9,2) as size, work_type, COUNT(*) as cnt ... WHERE SUBSTRING(model,9,2) IN ('03','05','10','21','31','32') GROUP BY size, work_type`
    - Column query: join `qc_defect_class` on `qc_defect = defect_code`; apply `work_type_filter` bucket and `category` filter; x-axis from `qc_defect_class` ordered by `defect_code`
    - _Requirements: 1.6, 2.1–2.6, 3.1–3.4, 4.1–4.7, 5.1–5.9, 10.1_

    - [x] 2.1 Write unit tests for aggregateByWorkTypeBucket
        - Test all four `work_type` values map to correct buckets
        - Test null and unrecognized values fall back to Mainlot
        - Test empty collection returns `[0, 0, 0]`
        - _Requirements: 2.1–2.5, 3.4_

    - [x] 2.2 Write property test for work-type bucket partition (Property 1)
        - **Property 1: Work-type bucket partition**
        - For any collection of rows, `sum(pie.series)` must equal total row count
        - **Validates: Requirements 2.6, 3.3**

    - [x] 2.3 Write property test for pie series length invariant (Property 2)
        - **Property 2: Pie series length invariant**
        - `pie.series` is always length 3 with non-negative integers; `pie.labels` is always `["Mainlot","R-rework","L-rework"]`
        - **Validates: Requirements 3.1, 3.2**

    - [x] 2.4 Write unit tests for pivotToStackedSeries
        - Test zero-fill for missing size/bucket combinations
        - Test `data` array length always equals `count($xCategories)`
        - Test rows with unrecognized size codes are excluded from bar/column output
        - _Requirements: 4.6, 4.7, 5.7, 5.8_

    - [x] 2.5 Write property test for bar series data alignment (Property 3)
        - **Property 3: Bar series data alignment**
        - Every object in `bar.series` has `data` length equal to `count(bar.categories)` (always 6); missing combinations are 0
        - **Validates: Requirements 4.6, 4.7**

    - [x] 2.6 Write property test for column series data alignment (Property 4)
        - **Property 4: Column series data alignment**
        - Every object in `column.series` has `data` length equal to `count(column.categories)`; missing combinations are 0
        - **Validates: Requirements 5.7, 5.8**

    - [x] 2.7 Write property test for size whitelist exclusion (Property 5)
        - **Property 5: Size whitelist exclusion**
        - Rows where `SUBSTRING(model,9,2)` is not in `('03','05','10','21','31','32')` are absent from `bar.series` and `column.series` but still counted in `pie.series`
        - **Validates: Requirements 4.2, 4.3, 9.5**

    - [x] 2.8 Write property test for column work-type filter scoping (Property 6)
        - **Property 6: Column chart work-type filter scoping**
        - All rows in `column.series` belong to the Work_Type_Bucket matching `work_type_filter`
        - **Validates: Requirements 5.4, 7.3**

    - [x] 2.9 Write property test for column category filter scoping (Property 7)
        - **Property 7: Column chart category filter scoping**
        - All rows in `column.series` have `defect_class` matching the active `category`; `column.categories` derived only from `qc_defect_class` rows where `defect_flow` matches
        - **Validates: Requirements 5.2, 5.5, 7.4**

- [x]   3. Add chart-data endpoint to EndlineDelayController
    - Inject `EndlineChartService` via constructor
    - Add `public function chartData(Request $request): JsonResponse` method
    - Collect all query params (`date`, `shift`, `cutoff`, `work_type`, `lipas_yn`, `defect_class`, `work_type_filter`, `category`) and pass as array to `EndlineChartService::getChartData()`
    - Return standard envelope `{ success, data: ChartDataDto, error: null, meta: null }`
    - Register route `GET api/endline-delay/chart-data` inside the existing `auth + permission:Manage Endline` middleware group in `routes/web.php`
    - _Requirements: 1.1–1.6_

    - [x] 3.1 Write Pest feature tests for chart-data endpoint
        - Test response structure matches `{ success, data: { pie, bar, column } }` — _Requirements: 1.1, 1.2, 10.10_
        - Test unauthenticated request returns 401 — _Requirements: 1.3_
        - Test user without `Manage Endline` permission returns 403 — _Requirements: 1.4_
        - Test `date` filter scopes results correctly — _Requirements: 1.5_
        - Test `work_type_filter` scopes column chart rows — _Requirements: 5.4_
        - Test empty result set returns zero-filled series — _Requirements: 3.4, 4.7, 5.9_

    - [x] 3.2 Write property test for API response shape invariant (Property 10)
        - **Property 10: API response shape invariant**
        - For any valid query parameter combination, response always contains `success`, `data.pie.labels`, `data.pie.series`, `data.bar.categories`, `data.bar.series`, `data.column.categories`, `data.column.series`
        - **Validates: Requirements 1.1, 1.2**

- [x]   4. Checkpoint — ensure all backend tests pass
    - Run `php artisan test --filter EndlineChart` and confirm all tests green; ask the user if questions arise.

- [x]   5. Create useEndlineCharts composable
    - Create `resources/js/composables/useEndlineCharts.ts`
    - Accept options `{ chartIdPrefix, defaultCategory, getParams }` matching the `UseEndlineChartsOptions` interface in the design
    - Initialize three ApexCharts instances in `initCharts()` targeting `{chartIdPrefix}-pie-chart`, `{chartIdPrefix}-bar-chart`, `{chartIdPrefix}-column-chart` — reuse the same chart option shapes already in `qc-analysis.vue` and `vi-technical.vue`
    - Implement `fetchChartData()` using `axios.get('/api/endline-delay/chart-data', { params, signal })` with an `AbortController`; cancel any in-flight request before starting a new one
    - On success, call `chart.updateSeries()` and `chart.updateOptions()` — no destroy/re-init
    - On network error, retain last valid data and emit `console.warn`
    - When no data, pass `noData: { text: 'No data for selected filters' }` in chart options
    - Expose `activeWorkType`, `activeCategory`, `setWorkType`, `setCategory`, `fetchChartData`, `initCharts`, `destroyCharts`
    - Call `destroyCharts()` in `onBeforeUnmount`
    - _Requirements: 6.1–6.8, 7.1–7.5, 8.1–8.4, 9.1–9.4_

    - [x] 5.1 Write property test for toggle re-fetch triggers (Property 8)
        - **Property 8: Toggle re-fetch triggers**
        - Calling `setWorkType` or `setCategory` with a new value updates the reactive ref and initiates a new fetch with the updated parameter
        - **Validates: Requirements 7.1, 7.2**

    - [x] 5.2 Write property test for AbortController race-condition prevention (Property 9)
        - **Property 9: AbortController race-condition prevention**
        - Only the most recently initiated fetch is applied to charts; all prior in-flight requests are cancelled
        - **Validates: Requirements 7.5, 8.4**

- [x]   6. Wire qc-analysis.vue to useEndlineCharts
    - Import `useEndlineCharts` from `@/composables/useEndlineCharts`
    - Remove the local `qcPieChart`, `qcBarChart`, `qcColumnChart` variables, the local `initCharts()` function, and the local `activeWorkType`, `activeCategory`, `setWorkType`, `setCategory` declarations
    - Call `useEndlineCharts({ chartIdPrefix: 'qc', defaultCategory: 'QC Analysis', getParams })` where `getParams` returns `{ date: filterDate, shift: filterShift, cutoff: filterCutoff, work_type: filterWorktype, lipas_yn: filterLipas }`
    - In `onMounted`, call `initCharts()` then `fetchChartData()` (replacing the old `initCharts()` call)
    - Add a `watch` on all page filter refs that calls `fetchChartData()` on change
    - _Requirements: 6.1–6.3, 8.1, 8.3_

- [x]   7. Wire vi-technical.vue to useEndlineCharts
    - Import `useEndlineCharts` from `@/composables/useEndlineCharts`
    - Remove the local `viPieChart`, `viBarChart`, `viColumnChart` variables, the local `initCharts()` function, and the local `activeWorkType`, `activeCategory`, `setWorkType`, `setCategory` declarations
    - Call `useEndlineCharts({ chartIdPrefix: 'vi', defaultCategory: 'Technical', getParams })` where `getParams` returns the same filter shape as task 6
    - In `onMounted`, call `initCharts()` then `fetchChartData()`
    - Add a `watch` on all page filter refs that calls `fetchChartData()` on change
    - _Requirements: 6.1–6.3, 8.2, 8.3_

- [x]   8. Final checkpoint — ensure all tests pass
    - Run `php artisan test` and confirm full suite is green; ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Property tests (2.2–2.9, 3.2, 5.1–5.2) use Pest with custom data generators against the service layer directly
- The `buildBaseQuery` in `EndlineChartService` should extract and reuse the filter logic from `EndlineDelayController::buildQuery()` to avoid duplication
- The `defect_class` value for VI Technical is `"Tech'l Verfication"` (note the typo — match the existing DB value exactly)
- Chart option shapes (colors, responsive breakpoints, etc.) should be copied from the existing `initCharts()` implementations in each page to preserve visual consistency
