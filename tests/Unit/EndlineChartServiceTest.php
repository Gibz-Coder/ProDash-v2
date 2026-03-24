<?php

declare(strict_types=1);

use App\Services\EndlineChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Unit tests drive EndlineChartService through its public getChartData() interface
// with controlled DB rows inserted directly, keeping tests fast and focused.

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * @param array<string, mixed> $overrides
 */
function svcRow(array $overrides = []): void
{
    DB::table('endline_delay')->insert(array_merge([
        'lot_id'       => 'LOT-' . uniqid(),
        'model'        => 'ABCDEFGH0300001', // SUBSTRING(model,9,2) = '03'
        'lot_qty'      => 1,
        'work_type'    => 'NORMAL',
        'defect_class' => 'QC Analysis',
        'created_at'   => now(),
        'updated_at'   => now(),
    ], $overrides));
}

/**
 * @param array<string, mixed> $overrides
 */
function svcDefect(array $overrides = []): string
{
    $code = $overrides['defect_code'] ?? 'D' . uniqid();
    DB::table('qc_defect_class')->insertOrIgnore(array_merge([
        'defect_code'  => $code,
        'defect_name'  => 'Defect ' . $code,
        'defect_class' => 'QC Analysis',
        'defect_flow'  => 'QC Analysis',
        'created_by'   => 'test',
        'created_at'   => now(),
        'updated_at'   => now(),
    ], $overrides));

    return $code;
}

function svc(): EndlineChartService
{
    return new EndlineChartService();
}

// ===========================================================================
// aggregateByWorkTypeBucket (via pie chart)
// ===========================================================================

test('NORMAL maps to Mainlot bucket', function (): void {
    svcRow(['work_type' => 'NORMAL', 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'])->toBe([1, 0, 0]);
});

test('PROCESS RW maps to R-rework bucket', function (): void {
    svcRow(['work_type' => 'PROCESS RW', 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'])->toBe([0, 1, 0]);
});

test('WH REWORK maps to R-rework bucket', function (): void {
    svcRow(['work_type' => 'WH REWORK', 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'])->toBe([0, 1, 0]);
});

test('OI REWORK maps to L-rework bucket', function (): void {
    svcRow(['work_type' => 'OI REWORK', 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'])->toBe([0, 0, 1]);
});

test('null work_type falls back to Mainlot bucket', function (): void {
    svcRow(['work_type' => null, 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'][0])->toBe(1);
});

test('unrecognized work_type falls back to Mainlot bucket', function (): void {
    svcRow(['work_type' => 'UNKNOWN_TYPE', 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'][0])->toBe(1);
});

test('empty dataset returns pie series [0, 0, 0]', function (): void {
    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['series'])->toBe([0, 0, 0]);
});

test('pie labels are always the three bucket names regardless of data', function (): void {
    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['labels'])->toBe(['Mainlot', 'R-rework', 'L-rework']);
});

// ===========================================================================
// pivotToStackedSeries (via bar chart)
// ===========================================================================

test('bar series data arrays always have length 6', function (): void {
    svcRow(['work_type' => 'NORMAL', 'model' => 'ABCDEFGH0300001']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    foreach ($result['bar']['series'] as $series) {
        expect($series['data'])->toHaveCount(6);
    }
});

test('bar fills zero for missing size/bucket combinations', function (): void {
    // Only one row with size '03' and Mainlot — all other positions should be 0
    svcRow(['work_type' => 'NORMAL', 'model' => 'ABCDEFGH0300001']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    $mainlot = collect($result['bar']['series'])->firstWhere('name', 'Mainlot');
    expect($mainlot['data'][0])->toBe(1);   // '03' position
    expect($mainlot['data'][1])->toBe(0);   // '05' position
    expect($mainlot['data'][2])->toBe(0);   // '10' position

    $rrework = collect($result['bar']['series'])->firstWhere('name', 'R-rework');
    expect(array_sum($rrework['data']))->toBe(0);
});

test('bar excludes rows with unrecognized size codes', function (): void {
    // SUBSTRING(model,9,2) = 'XX' — not in whitelist
    svcRow(['model' => 'ABCDEFGHXX00001']);

    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    foreach ($result['bar']['series'] as $series) {
        expect(array_sum($series['data']))->toBe(0);
    }
});

test('bar categories are always the six size codes', function (): void {
    $result = svc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['bar']['categories'])->toBe(['03', '05', '10', '21', '31', '32']);
});

// ===========================================================================
// Column chart filtering
// ===========================================================================

test('work_type_filter Mainlot scopes column chart to NORMAL rows only', function (): void {
    $code = svcDefect(['defect_code' => 'QC01', 'defect_flow' => 'QC Analysis']);
    svcRow(['work_type' => 'NORMAL',    'qc_defect' => $code, 'defect_class' => 'QC Analysis']);
    svcRow(['work_type' => 'OI REWORK', 'qc_defect' => $code, 'defect_class' => 'QC Analysis']);

    $result = svc()->getChartData([
        'defect_class'     => 'QC Analysis',
        'work_type_filter' => 'Mainlot',
        'category'         => 'QC Analysis',
    ]);

    $total = array_sum(array_merge(...array_column($result['column']['series'], 'data')));
    expect($total)->toBe(1);
});

test('column series data arrays length equals count of column categories', function (): void {
    svcDefect(['defect_code' => 'QC02', 'defect_flow' => 'QC Analysis']);
    svcDefect(['defect_code' => 'QC03', 'defect_flow' => 'QC Analysis']);

    $result = svc()->getChartData([
        'defect_class' => 'QC Analysis',
        'category'     => 'QC Analysis',
    ]);

    $catCount = count($result['column']['categories']);
    foreach ($result['column']['series'] as $series) {
        expect($series['data'])->toHaveCount($catCount);
    }
});

test('column returns empty series when no qc_defect_class rows match category', function (): void {
    $result = svc()->getChartData([
        'defect_class' => 'QC Analysis',
        'category'     => 'Technical',
    ]);

    expect($result['column']['categories'])->toBe([]);
    expect($result['column']['series'])->toBe([]);
});
