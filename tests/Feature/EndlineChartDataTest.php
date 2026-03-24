<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------------------
// Helpers (scoped to this file; userWithEndlinePermission is already defined
// in EndlineDelayMonitorTest.php but Pest loads helpers per-file, so we
// reuse the same Role slug to avoid duplicate-key errors via firstOrCreate)
// ---------------------------------------------------------------------------

/**
 * Insert a minimal endline_delay row and return its id.
 *
 * @param array<string, mixed> $overrides
 */
function insertChartRow(array $overrides = []): int
{
    return DB::table('endline_delay')->insertGetId(array_merge([
        'lot_id'     => 'LOT-' . uniqid(),
        'model'      => 'ABCDEFGH0300001', // SUBSTRING(model,9,2) = '03'
        'lot_qty'    => 10,
        'work_type'  => 'NORMAL',
        'defect_class' => 'QC Analysis',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

/**
 * Insert a qc_defect_class row and return its defect_code.
 *
 * @param array<string, mixed> $overrides
 */
function insertDefectClass(array $overrides = []): string
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

function chartUser(): User
{
    $role = Role::firstOrCreate(
        ['slug' => 'admin'],
        [
            'name'        => 'Admin',
            'description' => 'Administrator',
            'permissions' => ['Manage Endline'],
        ]
    );

    return User::factory()->withoutTwoFactor()->create(['role' => $role->slug]);
}

// ===========================================================================
// GET /api/endline-delay/chart-data
// ===========================================================================

test('chart-data returns correct envelope structure', function (): void {
    $user = chartUser();
    insertChartRow();

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'pie'    => ['labels', 'series'],
                'bar'    => ['categories', 'series'],
                'column' => ['categories', 'series'],
            ],
            'error',
            'meta',
        ])
        ->assertJsonPath('success', true)
        ->assertJsonPath('error', null);
});

test('chart-data pie labels are always the three bucket names', function (): void {
    $user = chartUser();
    insertChartRow(['work_type' => 'NORMAL']);
    insertChartRow(['work_type' => 'PROCESS RW']);
    insertChartRow(['work_type' => 'OI REWORK']);

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk()
        ->assertJsonPath('data.pie.labels', ['Mainlot', 'R-rework', 'L-rework']);
});

test('chart-data pie series sums to total matching row count', function (): void {
    $user = chartUser();
    insertChartRow(['work_type' => 'NORMAL',     'defect_class' => 'QC Analysis']);
    insertChartRow(['work_type' => 'PROCESS RW', 'defect_class' => 'QC Analysis']);
    insertChartRow(['work_type' => 'WH REWORK',  'defect_class' => 'QC Analysis']);
    insertChartRow(['work_type' => 'OI REWORK',  'defect_class' => 'QC Analysis']);
    // Row with different defect_class — must NOT be counted
    insertChartRow(['work_type' => 'NORMAL', 'defect_class' => "Tech'l Verification"]);

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk();

    $series = $response->json('data.pie.series');
    expect(array_sum($series))->toBe(4);
});

test('chart-data returns zeros when no rows match filters', function (): void {
    $user = chartUser();

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis&date=1990-01-01');

    $response->assertOk()
        ->assertJsonPath('data.pie.series', [0, 0, 0]);

    $barSeries = $response->json('data.bar.series');
    foreach ($barSeries as $s) {
        expect(array_sum($s['data']))->toBe(0);
    }
});

test('chart-data bar categories are always the six size codes', function (): void {
    $user = chartUser();

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk()
        ->assertJsonPath('data.bar.categories', ['03', '05', '10', '21', '31', '32']);
});

test('chart-data bar series data arrays have length 6', function (): void {
    $user = chartUser();
    insertChartRow();

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk();

    foreach ($response->json('data.bar.series') as $series) {
        expect($series['data'])->toHaveCount(6);
    }
});

test('chart-data date filter scopes results', function (): void {
    $user = chartUser();
    insertChartRow(['created_at' => '2024-01-15 10:00:00', 'updated_at' => '2024-01-15 10:00:00']);
    insertChartRow(['created_at' => '2024-01-16 10:00:00', 'updated_at' => '2024-01-16 10:00:00']);

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis&date=2024-01-15');

    $response->assertOk();

    $series = $response->json('data.pie.series');
    expect(array_sum($series))->toBe(1);
});

test('chart-data work_type_filter scopes column chart rows', function (): void {
    $user     = chartUser();
    $defCode  = insertDefectClass(['defect_code' => 'QC01', 'defect_flow' => 'QC Analysis']);

    insertChartRow(['work_type' => 'NORMAL',     'qc_defect' => $defCode]);
    insertChartRow(['work_type' => 'OI REWORK',  'qc_defect' => $defCode]);

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis&work_type_filter=Mainlot&category=QC+Analysis');

    $response->assertOk();

    // Only the NORMAL (Mainlot) row should appear in column series totals
    $total = 0;
    foreach ($response->json('data.column.series') as $s) {
        $total += array_sum($s['data']);
    }
    expect($total)->toBe(1);
});

test('chart-data unauthenticated request returns 401', function (): void {
    $this->getJson('/api/endline-delay/chart-data')
        ->assertUnauthorized();
});

test('chart-data user without Manage Endline permission returns 403', function (): void {
    $user = User::factory()->withoutTwoFactor()->create(['role' => 'viewer']);

    $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data')
        ->assertForbidden();
});

test('chart-data rows with unrecognized size are excluded from bar but counted in pie', function (): void {
    $user = chartUser();
    // model where SUBSTRING(model,9,2) = 'XX' — not in size whitelist
    insertChartRow(['model' => 'ABCDEFGHXX00001']);

    $response = $this->actingAs($user)
        ->getJson('/api/endline-delay/chart-data?defect_class=QC+Analysis');

    $response->assertOk();

    // Pie should count the row
    $pieSeries = $response->json('data.pie.series');
    expect(array_sum($pieSeries))->toBe(1);

    // Bar should have all zeros
    foreach ($response->json('data.bar.series') as $s) {
        expect(array_sum($s['data']))->toBe(0);
    }
});
