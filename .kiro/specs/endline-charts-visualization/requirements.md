# Requirements Document

## Introduction

This feature wires the three-chart section (pie, horizontal stacked bar, stacked column) in the QC Analysis and VI Technical monitoring pages to real data from the `endline_delay` table. Charts currently render with static placeholder data; this feature replaces that with live aggregated data fetched from a new backend API endpoint (`GET /api/endline-delay/chart-data`), reacting to the same date/shift/cutoff/work_type/lipas_yn filters already present on each page. A shared Vue composable (`useEndlineCharts`) owns all chart lifecycle and data-fetching logic, keeping both pages DRY.

---

## Glossary

- **EndlineChartService**: Laravel service class responsible for aggregating `endline_delay` rows into the three chart datasets.
- **Composable**: The `useEndlineCharts` Vue 3 composable that owns ApexCharts instances, fetches data, and updates charts reactively.
- **API_Endpoint**: The `GET /api/endline-delay/chart-data` route handled by `EndlineDelayController`.
- **ChartDataDto**: The structured response object containing `pie`, `bar`, and `column` datasets.
- **Work_Type_Bucket**: One of three logical groupings — `Mainlot`, `R-rework`, or `L-rework` — derived from the raw `work_type` column value.
- **Lot_Size**: A two-character size code (`03`, `05`, `10`, `21`, `31`, `32`) derived from `SUBSTRING(model, 9, 2)`.
- **Page_Filters**: The set of filter values active on the page: `date`, `shift`, `cutoff`, `work_type`, `lipas_yn`.
- **Column_Toggles**: The two independent toggle groups on the column chart — work-type (Mainlot/R-rework/L-rework) and category (Technical/QC Analysis).
- **Defect_Class_Scope**: The page-level `defect_class` value that scopes all chart data — `"QC Analysis"` for the QC Analysis page and `"Tech'l Verfication"` for the VI Technical page.

---

## Requirements

### Requirement 1: Chart Data API Endpoint

**User Story:** As a dashboard user, I want the charts to display real aggregated data from the endline_delay table, so that I can monitor actual QC and VI technical delay trends.

#### Acceptance Criteria

1. WHEN a request is made to `GET /api/endline-delay/chart-data`, THE API_Endpoint SHALL return a JSON response with the envelope `{ success, data: { pie, bar, column }, error, meta }`.
2. WHEN valid query parameters are provided, THE API_Endpoint SHALL return `success: true` and populate all three chart datasets.
3. WHEN an unauthenticated request is made to `GET /api/endline-delay/chart-data`, THE API_Endpoint SHALL return a 401 Unauthorized response.
4. WHEN a request is made by a user without the `Manage Endline` permission, THE API_Endpoint SHALL return a 403 Forbidden response.
5. THE API_Endpoint SHALL accept the following optional query parameters: `date` (YYYY-MM-DD), `shift` (DAY|NIGHT), `cutoff` (range string), `work_type` (NORMAL|PROCESS RW|WH REWORK|OI REWORK), `lipas_yn` (Y|N), `defect_class` (page scope), `work_type_filter` (Mainlot|R-rework|L-rework), and `category` (Technical|QC Analysis).
6. THE API_Endpoint SHALL delegate aggregation logic to the EndlineChartService.

---

### Requirement 2: Work-Type Bucketing

**User Story:** As a dashboard user, I want delay counts grouped into Mainlot, R-rework, and L-rework categories, so that I can distinguish between production lot types at a glance.

#### Acceptance Criteria

1. WHEN aggregating rows, THE EndlineChartService SHALL map `work_type = 'NORMAL'` to the Mainlot bucket.
2. WHEN aggregating rows, THE EndlineChartService SHALL map `work_type = 'PROCESS RW'` to the R-rework bucket.
3. WHEN aggregating rows, THE EndlineChartService SHALL map `work_type = 'WH REWORK'` to the R-rework bucket.
4. WHEN aggregating rows, THE EndlineChartService SHALL map `work_type = 'OI REWORK'` to the L-rework bucket.
5. WHEN a row has a null or unrecognized `work_type` value, THE EndlineChartService SHALL assign that row to the Mainlot bucket.
6. FOR ALL collections of `endline_delay` rows, THE EndlineChartService SHALL assign each row to exactly one Work_Type_Bucket such that the sum of all bucket counts equals the total row count.

---

### Requirement 3: Pie Chart Aggregation

**User Story:** As a dashboard user, I want a pie chart summarizing delay counts by work-type bucket, so that I can see the proportional breakdown at a glance.

#### Acceptance Criteria

1. THE EndlineChartService SHALL return `pie.labels` as exactly `["Mainlot", "R-rework", "L-rework"]` regardless of the data.
2. THE EndlineChartService SHALL return `pie.series` as an array of three non-negative integers corresponding to the lot counts for each Work_Type_Bucket.
3. FOR ALL valid filter combinations, THE EndlineChartService SHALL return `pie.series` values that sum to the total count of `endline_delay` rows matching those filters and the active Defect_Class_Scope.
4. WHEN no rows match the current filters, THE EndlineChartService SHALL return `pie.series` as `[0, 0, 0]`.

---

### Requirement 4: Bar Chart Aggregation (Per-Size Breakdown)

**User Story:** As a dashboard user, I want a horizontal stacked bar chart showing delay counts per lot size and work-type bucket, so that I can identify which lot sizes are most affected.

#### Acceptance Criteria

1. THE EndlineChartService SHALL derive Lot_Size for each row using `SUBSTRING(model, 9, 2)`.
2. THE EndlineChartService SHALL only include rows in the bar chart where Lot_Size is one of `('03', '05', '10', '21', '31', '32')`.
3. WHEN a row has a null or short `model` value such that `SUBSTRING(model, 9, 2)` does not produce a recognized Lot_Size, THE EndlineChartService SHALL exclude that row from the bar chart.
4. THE EndlineChartService SHALL return `bar.categories` as the array `["03", "05", "10", "21", "31", "32"]`.
5. THE EndlineChartService SHALL return `bar.series` as an array of three objects with `name` values `"Mainlot"`, `"R-rework"`, and `"L-rework"`.
6. FOR ALL series objects in `bar.series`, THE EndlineChartService SHALL return a `data` array whose length equals `count(bar.categories)`.
7. WHEN a Work_Type_Bucket has no rows for a given Lot_Size, THE EndlineChartService SHALL use `0` for that position in the series data array.

---

### Requirement 5: Column Chart Aggregation (Per-Defect Breakdown)

**User Story:** As a dashboard user, I want a stacked column chart showing delay counts per defect name and lot size, so that I can drill into which defect types are causing the most delays.

#### Acceptance Criteria

1. THE EndlineChartService SHALL derive column chart x-axis categories from `qc_defect_class.defect_name` values, ordered by `defect_code` ascending.
2. WHEN the `category` parameter is provided, THE EndlineChartService SHALL filter `qc_defect_class` rows by `defect_flow` matching the active category to determine x-axis categories.
3. THE EndlineChartService SHALL join `endline_delay` to `qc_defect_class` on `endline_delay.qc_defect = qc_defect_class.defect_code` to resolve defect names.
4. WHEN the `work_type_filter` parameter is set, THE EndlineChartService SHALL scope column chart rows to those whose `work_type` maps to the specified Work_Type_Bucket.
5. WHEN the `category` parameter is set, THE EndlineChartService SHALL scope column chart rows to those where `endline_delay.defect_class` matches the category value.
6. THE EndlineChartService SHALL return `column.series` as an array of objects keyed by Lot_Size, each with a `data` array aligned to `column.categories`.
7. FOR ALL series objects in `column.series`, THE EndlineChartService SHALL return a `data` array whose length equals `count(column.categories)`.
8. WHEN a Lot_Size has no rows for a given defect name, THE EndlineChartService SHALL use `0` for that position in the series data array.
9. WHEN no rows match the column chart filters, THE EndlineChartService SHALL return `column.series` with zero-filled data arrays.

---

### Requirement 6: Shared useEndlineCharts Composable

**User Story:** As a developer, I want a single shared composable to manage chart lifecycle and data fetching for both pages, so that the QC Analysis and VI Technical pages remain DRY and consistent.

#### Acceptance Criteria

1. THE Composable SHALL accept a `chartIdPrefix` option (`"qc"` or `"vi"`) to target the correct DOM element IDs for chart initialization.
2. THE Composable SHALL accept a `defaultCategory` option (`"QC Analysis"` or `"Technical"`) to set the page-level Defect_Class_Scope.
3. THE Composable SHALL accept a `getParams` function option that returns the current page-level filter values.
4. WHEN the composable is initialized, THE Composable SHALL initialize three ApexCharts instances targeting `{chartIdPrefix}-pie-chart`, `{chartIdPrefix}-bar-chart`, and `{chartIdPrefix}-column-chart` DOM elements.
5. WHEN `fetchChartData` is called, THE Composable SHALL merge page-level filters from `getParams()`, the Defect_Class_Scope, `activeWorkType`, and `activeCategory` into the request parameters.
6. WHEN chart data is received, THE Composable SHALL update charts by calling `updateSeries()` and `updateOptions()` on existing instances without destroying and re-initializing them.
7. WHEN the component unmounts, THE Composable SHALL call `destroy()` on all three ApexCharts instances.
8. THE Composable SHALL expose `activeWorkType` (ref), `activeCategory` (ref), `setWorkType`, `setCategory`, `fetchChartData`, `initCharts`, and `destroyCharts`.

---

### Requirement 7: Column Chart Toggle Interactions

**User Story:** As a dashboard user, I want to toggle the column chart between work-type buckets and categories, so that I can explore delay data from different dimensions without reloading the page.

#### Acceptance Criteria

1. WHEN a user clicks a work-type toggle button (Mainlot, R-rework, or L-rework), THE Composable SHALL update `activeWorkType` to the selected value and immediately re-fetch chart data.
2. WHEN a user clicks a category toggle button (Technical or QC Analysis), THE Composable SHALL update `activeCategory` to the selected value and immediately re-fetch chart data.
3. WHEN `setWorkType` is called with a valid value, THE Composable SHALL pass `work_type_filter` equal to the new `activeWorkType` value in the next fetch request.
4. WHEN `setCategory` is called with a valid value, THE Composable SHALL pass `category` equal to the new `activeCategory` value in the next fetch request.
5. WHILE a fetch is in progress and a new toggle is activated, THE Composable SHALL cancel the in-flight request using AbortController before initiating the new fetch.

---

### Requirement 8: Page Filter Reactivity

**User Story:** As a dashboard user, I want the charts to update automatically when I change any page filter, so that the chart data always reflects my current filter selection.

#### Acceptance Criteria

1. WHEN any Page_Filter value changes on the QC Analysis page, THE Composable SHALL re-fetch chart data with the updated filter values.
2. WHEN any Page_Filter value changes on the VI Technical page, THE Composable SHALL re-fetch chart data with the updated filter values.
3. WHEN the page mounts, THE Composable SHALL fetch chart data using the initial Page_Filter values.
4. WHILE a fetch is in progress and a new filter change occurs, THE Composable SHALL cancel the in-flight request using AbortController before initiating the new fetch.

---

### Requirement 9: Empty and Error States

**User Story:** As a dashboard user, I want the charts to handle missing data and network errors gracefully, so that the page remains usable even when data is unavailable.

#### Acceptance Criteria

1. WHEN no `endline_delay` rows match the current filters, THE Composable SHALL render all three charts with zero-value series.
2. WHEN no rows match the current filters, THE Composable SHALL display the ApexCharts `noData` label with the text `"No data for selected filters"`.
3. WHEN a network error occurs during a chart data fetch, THE Composable SHALL retain the last valid chart data in all three charts.
4. IF a network error occurs, THEN THE Composable SHALL emit a `console.warn` message describing the failure.
5. WHEN a row has a null or short `model` value, THE EndlineChartService SHALL still count that row in the pie chart while excluding it from bar and column charts.

---

### Requirement 10: Performance and Index

**User Story:** As a system administrator, I want chart queries to execute efficiently, so that filter changes do not cause noticeable latency on the dashboard.

#### Acceptance Criteria

1. THE EndlineChartService SHALL aggregate all three chart datasets using grouped SQL queries with no N+1 query patterns.
2. THE database SHALL have a composite index on `endline_delay (defect_class, created_at)` to support the primary filter pattern.
