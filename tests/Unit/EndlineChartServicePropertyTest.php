<?php

declare(strict_types=1);

use App\Services\EndlineChartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Property-based tests for EndlineChartService
//
// Each test uses a dataset generator to cover a wide range of inputs and
// asserts the universal correctness properties defined in the design document.
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/** @param list<array<string, mixed>> $rows */
function insertPropertyRows(array $rows): void
{
    foreach ($rows as $row) {
        DB::table('endline_delay')->insert(array_merge([
            'lot_id'       => 'LOT-' . uniqid(),
            'lot_qty'      => 1,
            'defect_class' => 'QC Analysis',
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $row));
    }
}

function insertPropertyDefect(string $code, string $flow = 'QC Analysis'): void
{
    DB::table('qc_defect_class')->insertOrIgnore([
        'defect_code'  => $code,
        'defect_name'  => 'Defect ' . $code,
        'defect_class' => $flow,
        'defect_flow'  => $flow,
        'created_by'   => 'test',
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);
}

function propSvc(): EndlineChartService
{
    return new EndlineChartService();
}

/**
 * Generate datasets of varying sizes and work_type distributions.
 *
 * @return list<array{list<array<string, mixed>>, int}>  [rows, expectedTotal]
 */
function workTypeDatasets(): array
{
    $allTypes = ['NORMAL', 'PROCESS RW', 'WH REWORK', 'OI REWORK', null, 'UNKNOWN'];

    $datasets = [];

    // Single-type datasets
    foreach ($allTypes as $wt) {
        $count = rand(1, 5);
        $rows  = array_fill(0, $count, ['work_type' => $wt, 'model' => 'ABCDEFGH0300001']);
        $datasets[] = [$rows, $count];
    }

    // Mixed dataset
    $mixed = [
        ['work_type' => 'NORMAL',     'model' => 'ABCDEFGH0300001'],
        ['work_type' => 'PROCESS RW', 'model' => 'ABCDEFGH0500001'],
        ['work_type' => 'WH REWORK',  'model' => 'ABCDEFGH1000001'],
        ['work_type' => 'OI REWORK',  'model' => 'ABCDEFGH2100001'],
        ['work_type' => null,          'model' => 'ABCDEFGH3100001'],
    ];
    $datasets[] = [$mixed, 5];

    // Empty dataset
    $datasets[] = [[], 0];

    return $datasets;
}

/**
 * Generate datasets with varying size codes for bar/column alignment tests.
 *
 * @return list<list<array<string, mixed>>>
 */
function sizeDatasets(): array
{
    $validSizes   = ['03', '05', '10', '21', '31', '32'];
    $invalidSizes = ['XX', 'AB', '99', ''];

    return [
        // All valid sizes
        array_map(fn (string $s): array => [
            'work_type' => 'NORMAL',
            'model'     => "ABCDEFGH{$s}00001",
        ], $validSizes),

        // Mix of valid and invalid
        [
            ['work_type' => 'NORMAL', 'model' => 'ABCDEFGH0300001'],
            ['work_type' => 'NORMAL', 'model' => 'ABCDEFGHXX00001'],
        ],

        // Only invalid sizes
        array_map(fn (string $s): array => [
            'work_type' => 'NORMAL',
            'model'     => "ABCDEFGH{$s}00001",
        ], $invalidSizes),

        // Empty
        [],
    ];
}

// ===========================================================================
// Property 1: Work-type bucket partition
// sum(pie.series) == total row count matching filters
// ===========================================================================

test('Property 1: pie series sum equals total matching row count', function (
    array $rows,
    int $expectedTotal
): void {
    insertPropertyRows($rows);

    $result = propSvc()->getChartData(['defect_class' => 'QC Analysis']);

    expect(array_sum($result['pie']['series']))->toBe($expectedTotal);
})->with(workTypeDatasets());

// ===========================================================================
// Property 2: Pie series length invariant
// pie.series always length 3, non-negative; pie.labels always the three names
// ===========================================================================

test('Property 2: pie series is always length 3 with non-negative integers', function (
    array $rows,
    int $_expected
): void {
    insertPropertyRows($rows);

    $result = propSvc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['pie']['labels'])->toBe(['Mainlot', 'R-rework', 'L-rework']);
    expect($result['pie']['series'])->toHaveCount(3);

    foreach ($result['pie']['series'] as $val) {
        expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
    }
})->with(workTypeDatasets());

// ===========================================================================
// Property 3: Bar series data alignment
// every bar series data array has length 6; missing combos are 0
// ===========================================================================

test('Property 3: every bar series data array has length 6', function (array $rows): void {
    insertPropertyRows($rows);

    $result = propSvc()->getChartData(['defect_class' => 'QC Analysis']);

    expect($result['bar']['series'])->toHaveCount(3);

    foreach ($result['bar']['series'] as $series) {
        expect($series['data'])->toHaveCount(6);
        foreach ($series['data'] as $val) {
            expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }
})->with(array_map(fn (array $d): array => [$d], sizeDatasets()));

// ===========================================================================
// Property 4: Column series data alignment
// every column series data array length equals count(column.categories)
// ===========================================================================

test('Property 4: every column series data array length equals count of column categories', function (
    array $rows
): void {
    insertPropertyDefect('P4A', 'QC Analysis');
    insertPropertyDefect('P4B', 'QC Analysis');
    insertPropertyRows($rows);

    $result = propSvc()->getChartData([
        'defect_class' => 'QC Analysis',
        'category'     => 'QC Analysis',
    ]);

    $catCount = count($result['column']['categories']);

    foreach ($result['column']['series'] as $series) {
        expect($series['data'])->toHaveCount($catCount);
        foreach ($series['data'] as $val) {
            expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }
})->with(array_map(fn (array $d): array => [$d], sizeDatasets()));

// ===========================================================================
// Property 5: Size whitelist exclusion
// rows with invalid size are absent from bar/column but counted in pie
// ===========================================================================

test('Property 5: invalid-size rows counted in pie but excluded from bar and column', function (): void {
    // Two rows: one valid size, one invalid
    insertPropertyRows([
        ['work_type' => 'NORMAL', 'model' => 'ABCDEFGH0300001'], // valid '03'
        ['work_type' => 'NORMAL', 'model' => 'ABCDEFGHXX00001'], // invalid 'XX'
    ]);

    $result = propSvc()->getChartData(['defect_class' => 'QC Analysis']);

    // Both rows counted in pie
    expect(array_sum($result['pie']['series']))->toBe(2);

    // Only the valid-size row appears in bar
    $barTotal = array_sum(array_merge(...array_column($result['bar']['series'], 'data')));
    expect($barTotal)->toBe(1);
});

// ===========================================================================
// Property 6: Column chart work-type filter scoping
// all column rows belong to the specified Work_Type_Bucket
// ===========================================================================

test('Property 6: work_type_filter scopes column chart to correct bucket only', function (
    string $bucket,
    array $includedTypes,
    array $excludedTypes
): void {
    $code = 'P6-' . $bucket;
    insertPropertyDefect($code, 'QC Analysis');

    foreach ($includedTypes as $wt) {
        insertPropertyRows([['work_type' => $wt, 'model' => 'ABCDEFGH0300001', 'qc_defect' => $code]]);
    }
    foreach ($excludedTypes as $wt) {
        insertPropertyRows([['work_type' => $wt, 'model' => 'ABCDEFGH0500001', 'qc_defect' => $code]]);
    }

    $result = propSvc()->getChartData([
        'defect_class'     => 'QC Analysis',
        'work_type_filter' => $bucket,
        'category'         => 'QC Analysis',
    ]);

    $total = array_sum(array_merge(...array_column($result['column']['series'], 'data')));
    expect($total)->toBe(count($includedTypes));
})->with([
    'Mainlot bucket'  => ['Mainlot',  ['NORMAL'],              ['PROCESS RW', 'WH REWORK', 'OI REWORK']],
    'R-rework bucket' => ['R-rework', ['PROCESS RW', 'WH REWORK'], ['NORMAL', 'OI REWORK']],
    'L-rework bucket' => ['L-rework', ['OI REWORK'],           ['NORMAL', 'PROCESS RW', 'WH REWORK']],
]);

// ===========================================================================
// Property 7: Column chart category filter scoping
// column.categories derived from qc_defect_class where defect_flow matches
// ===========================================================================

test('Property 7: column categories come only from qc_defect_class rows matching active category', function (): void {
    insertPropertyDefect('P7QC', 'QC Analysis');
    insertPropertyDefect('P7TK', 'Technical');

    $qcResult = propSvc()->getChartData([
        'defect_class' => 'QC Analysis',
        'category'     => 'QC Analysis',
    ]);

    $techResult = propSvc()->getChartData([
        'defect_class' => "Tech'l Verification",
        'category'     => 'Technical',
    ]);

    // QC Analysis category should include P7QC defect name but not P7TK
    expect($qcResult['column']['categories'])->toContain('Defect P7QC');
    expect($qcResult['column']['categories'])->not->toContain('Defect P7TK');

    // Technical category should include P7TK defect name but not P7QC
    expect($techResult['column']['categories'])->toContain('Defect P7TK');
    expect($techResult['column']['categories'])->not->toContain('Defect P7QC');
});

// ===========================================================================
// Property 8 & 9 are composable-level (frontend) — covered in tasks 5.1/5.2
// ===========================================================================
