<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ---------------------------------------------------------------------------
// Property 10: API response shape invariant
//
// For any valid combination of query parameters, the response must always
// contain the required envelope keys and chart sub-keys.
// ---------------------------------------------------------------------------

function propertyTestUser(): User
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

/**
 * Cartesian combinations of optional filter values used to drive the property test.
 *
 * @return list<array<string, string>>
 */
function chartParamCombinations(): array
{
    $defectClasses    = ['QC Analysis', "Tech'l Verification"];
    $workTypeFilters  = ['', 'Mainlot', 'R-rework', 'L-rework'];
    $categories       = ['', 'QC Analysis', 'Technical'];

    $combinations = [];

    foreach ($defectClasses as $dc) {
        foreach ($workTypeFilters as $wt) {
            foreach ($categories as $cat) {
                $params = ['defect_class' => $dc];
                if ($wt !== '') {
                    $params['work_type_filter'] = $wt;
                }
                if ($cat !== '') {
                    $params['category'] = $cat;
                }
                $combinations[] = [$params];
            }
        }
    }

    return $combinations;
}

// Property 10 — run once per parameter combination
test('Property 10: chart-data response shape is invariant across all valid parameter combinations', function (
    array $params
): void {
    $user = propertyTestUser();

    $query    = http_build_query($params);
    $response = $this->actingAs($user)->getJson("/api/endline-delay/chart-data?{$query}");

    $response->assertOk();

    // Top-level envelope keys must always be present
    $response->assertJsonStructure(['success', 'data', 'error', 'meta']);
    expect($response->json('success'))->toBeTrue();

    // data sub-keys must always be present
    $response->assertJsonStructure([
        'data' => [
            'pie'    => ['labels', 'series'],
            'bar'    => ['categories', 'series'],
            'column' => ['categories', 'series'],
        ],
    ]);

    // pie.labels is always the three bucket names
    expect($response->json('data.pie.labels'))->toBe(['Mainlot', 'R-rework', 'L-rework']);

    // pie.series is always length 3 with non-negative integers
    $pieSeries = $response->json('data.pie.series');
    expect($pieSeries)->toHaveCount(3);
    foreach ($pieSeries as $val) {
        expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
    }

    // bar.categories is always the six size codes
    expect($response->json('data.bar.categories'))->toBe(['03', '05', '10', '21', '31', '32']);

    // Every bar series data array has length 6
    foreach ($response->json('data.bar.series') as $series) {
        expect($series['data'])->toHaveCount(6);
        foreach ($series['data'] as $val) {
            expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }

    // Every column series data array length equals count(column.categories)
    $colCategories = $response->json('data.column.categories');
    foreach ($response->json('data.column.series') as $series) {
        expect($series['data'])->toHaveCount(count($colCategories));
        foreach ($series['data'] as $val) {
            expect($val)->toBeInt()->toBeGreaterThanOrEqual(0);
        }
    }
})->with(chartParamCombinations());
