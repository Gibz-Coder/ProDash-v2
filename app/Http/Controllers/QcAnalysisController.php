<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QcAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class QcAnalysisController extends Controller
{
    public function findByLot(Request $request): JsonResponse
    {
        $lotId = strtoupper(trim($request->input('lot_id', '')));
        if (!$lotId) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Lot ID required', 'meta' => null], 422);
        }

        $record = QcAnalysis::where('lot_id', $lotId)->latest()->first();
        if (!$record) {
            return response()->json(['success' => false, 'data' => null, 'error' => 'Lot not found in QC Analysis queue', 'meta' => null], 404);
        }

        // Only allow updating lots that are still pending or in-progress
        $completedStatuses = ['Proceed', 'Rework', 'Proceed - w/ OTHER', 'Derive / Scrap (RL)', 'DRB'];
        if ($record->analysis_result && in_array($record->analysis_result, $completedStatuses, true)) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'error'   => "Lot {$lotId} is already completed with decision: {$record->analysis_result}",
                'meta'    => null,
            ], 422);
        }

        // Also fetch qc_inspection details for this lot
        $inspection = \Illuminate\Support\Facades\DB::table('qc_inspection')
            ->where('lot_id', $lotId)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data'    => array_merge($record->toArray(), [
                'inspection_result' => $inspection?->inspection_result,
                'mainlot_result'    => $inspection?->mainlot_result,
                'rr_result'         => $inspection?->rr_result,
                'ly_result'         => $inspection?->ly_result,
                'inspection_times'  => $inspection?->inspection_times,
                'inspection_spl'    => $inspection?->inspection_spl,
            ]),
            'error' => null,
            'meta'  => null,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = QcAnalysis::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('lot_id', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%")
                  ->orWhere('defect_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        if ($request->filled('shift')) {
            $shift = $request->shift;
            if ($shift === 'DAY') {
                $query->whereTime('created_at', '>=', '07:00:00')
                      ->whereTime('created_at', '<', '19:00:00');
            } elseif ($shift === 'NIGHT') {
                $query->where(function ($q) {
                    $q->whereTime('created_at', '>=', '19:00:00')
                      ->orWhereTime('created_at', '<', '07:00:00');
                });
            }
        }

        if ($request->filled('cutoff')) {
            $ranges = [
                '00:00~03:59' => ['00:00:00', '03:59:59'],
                '04:00~06:59' => ['04:00:00', '06:59:59'],
                '07:00~11:59' => ['07:00:00', '11:59:59'],
                '12:00~15:59' => ['12:00:00', '15:59:59'],
                '16:00~18:59' => ['16:00:00', '18:59:59'],
                '19:00~23:59' => ['19:00:00', '23:59:59'],
            ];
            if (isset($ranges[$request->cutoff])) {
                [$from, $to] = $ranges[$request->cutoff];
                $query->whereTime('created_at', '>=', $from)
                      ->whereTime('created_at', '<=', $to);
            }
        }

        $records = $query->orderByDesc('created_at')->get();

        $today = now()->toDateString();

        // Prev-day: separate query ignoring the date filter — incomplete lots from before today
        $prevDayQuery = QcAnalysis::where(function ($q) {
                $q->whereNull('analysis_result')
                  ->orWhere('analysis_result', 'In Progress');
            })
            ->whereDate('created_at', '<', $today);

        if ($request->filled('work_type')) {
            $prevDayQuery->where('work_type', $request->work_type);
        }
        if ($request->filled('lipas_yn')) {
            $prevDayQuery->where('lipas_yn', $request->lipas_yn);
        }

        $prevDayCount = $prevDayQuery->count();
        $prevDayQty   = (int) $prevDayQuery->sum('lot_qty');

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'prev_day_count' => $prevDayCount,
                'prev_day_qty'   => $prevDayQty,
            ],
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $date      = $request->input('date');
        $workType  = $request->input('work_type');
        $lipas     = $request->input('lipas_yn');
        $bucket    = $request->input('work_type_filter'); // All|Mainlot|R-rework|L-rework

        // Base query joining qc_analysis with qc_inspection to get bin results
        $base = DB::table('qc_analysis as qa')
            ->leftJoin('qc_inspection as qi', 'qi.lot_id', '=', 'qa.lot_id');

        if ($date) {
            $base->whereDate('qa.created_at', $date);
        }
        if ($workType) {
            $base->where('qa.work_type', $workType);
        }
        if ($lipas) {
            $base->where('qa.lipas_yn', $lipas);
        }

        $shift = $request->input('shift');
        if ($shift === 'DAY') {
            $base->whereTime('qa.created_at', '>=', '07:00:00')
                 ->whereTime('qa.created_at', '<', '19:00:00');
        } elseif ($shift === 'NIGHT') {
            $base->where(function ($q) {
                $q->whereTime('qa.created_at', '>=', '19:00:00')
                  ->orWhereTime('qa.created_at', '<', '07:00:00');
            });
        }

        $cutoff = $request->input('cutoff');
        $cutoffRanges = [
            '00:00~03:59' => ['00:00:00', '03:59:59'],
            '04:00~06:59' => ['04:00:00', '06:59:59'],
            '07:00~11:59' => ['07:00:00', '11:59:59'],
            '12:00~15:59' => ['12:00:00', '15:59:59'],
            '16:00~18:59' => ['16:00:00', '18:59:59'],
            '19:00~23:59' => ['19:00:00', '23:59:59'],
        ];
        if ($cutoff && isset($cutoffRanges[$cutoff])) {
            [$from, $to] = $cutoffRanges[$cutoff];
            $base->whereTime('qa.created_at', '>=', $from)
                 ->whereTime('qa.created_at', '<=', $to);
        }

        // Apply bucket filter using qc_inspection bin columns
        if ($bucket && $bucket !== 'All') {
            match ($bucket) {
                'Mainlot'  => $base->where('qi.mainlot_result', 'NG'),
                'RL-Rework' => $base->where('qi.mainlot_result', 'OK')
                                    ->where(function ($q) {
                                        $q->where('qi.rr_result', 'NG')
                                          ->orWhere('qi.ly_result', 'NG');
                                    }),
                default    => null,
            };
        }

        // --- Pie: count by status (Pending / In Progress / Completed) ---
        $pieRows = (clone $base)
            ->select('qa.analysis_result', DB::raw('COUNT(*) as cnt'))
            ->groupBy('qa.analysis_result')
            ->get();

        $pieTotals = ['Pending' => 0, 'In Progress' => 0, 'Completed' => 0];
        foreach ($pieRows as $row) {
            if ($row->analysis_result === 'In Progress') {
                $pieTotals['In Progress'] += (int) $row->cnt;
            } elseif ($row->analysis_result && $row->analysis_result !== 'For Decision') {
                $pieTotals['Completed'] += (int) $row->cnt;
            } else {
                $pieTotals['Pending'] += (int) $row->cnt;
            }
        }

        $statusNames = ['Pending', 'In Progress', 'Completed'];

        $sizeCodes   = ['03', '05', '10', '21', '31', '32'];

        // --- Bar: per size, stacked by status ---
        $barRows = (clone $base)
            ->select(
                DB::raw("SUBSTRING(qa.model, 3, 2) as size"),
                'qa.analysis_result',
                DB::raw('COUNT(*) as cnt'),
            )
            ->whereRaw("SUBSTRING(qa.model, 3, 2) IN ('03','05','10','21','31','32')")
            ->groupBy(DB::raw("SUBSTRING(qa.model, 3, 2)"), 'qa.analysis_result')
            ->get();

        $barLookup = [];
        foreach ($barRows as $row) {
            if ($row->analysis_result === 'In Progress') {
                $status = 'In Progress';
            } elseif ($row->analysis_result && $row->analysis_result !== 'For Decision') {
                $status = 'Completed';
            } else {
                $status = 'Pending';
            }
            $barLookup[$status][$row->size] = ($barLookup[$status][$row->size] ?? 0) + (int) $row->cnt;
        }
        $barSeries = [];
        foreach ($statusNames as $name) {
            $data = [];
            foreach ($sizeCodes as $s) {
                $data[] = $barLookup[$name][$s] ?? 0;
            }
            $barSeries[] = ['name' => $name, 'data' => $data];
        }

        // --- Column: per defect code (QC Analysis class only), all codes per lot, stacked by size ---
        // Fetch all rows with their defect_code strings and model size
        $allRows = (clone $base)
            ->select(
                DB::raw("SUBSTRING(qa.model, 3, 2) as size"),
                'qa.defect_code',
            )
            ->whereRaw("SUBSTRING(qa.model, 3, 2) IN ('03','05','10','21','31','32')")
            ->whereNotNull('qa.defect_code')
            ->get();

        // Get all QC Analysis defect codes from the lookup table
        $qcAnalysisCodes = DB::table('qc_defect_class')
            ->where('defect_class', 'QC Analysis')
            ->pluck('defect_code')
            ->map(fn($c) => strtoupper(trim($c)))
            ->flip()
            ->all(); // keyed by code for O(1) lookup

        // Split comma-separated codes and count each QC Analysis code per size
        $colLookup = [];
        foreach ($allRows as $row) {
            $codes = array_map('trim', explode(',', strtoupper((string) $row->defect_code)));
            foreach ($codes as $code) {
                if ($code === '' || !isset($qcAnalysisCodes[$code])) {
                    continue;
                }
                $colLookup[$row->size][$code] = ($colLookup[$row->size][$code] ?? 0) + 1;
            }
        }

        // Collect all unique defect codes that appeared
        $defectCodes = collect($colLookup)
            ->flatMap(fn($codes) => array_keys($codes))
            ->unique()->sort()->values()->all();

        if (empty($defectCodes)) {
            $colData = ['categories' => [], 'series' => []];
        } else {
            $colSeries = [];
            foreach ($sizeCodes as $size) {
                $data = [];
                foreach ($defectCodes as $code) {
                    $data[] = $colLookup[$size][$code] ?? 0;
                }
                $colSeries[] = ['name' => $size, 'data' => $data];
            }
            $colData = ['categories' => $defectCodes, 'series' => $colSeries];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'pie'    => ['labels' => $statusNames, 'series' => array_values($pieTotals)],
                'bar'    => ['categories' => $sizeCodes, 'series' => $barSeries],
                'column' => $colData,
            ],
            'error' => null,
            'meta'  => null,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = QcAnalysis::findOrFail($id);

        $validated = $request->validate([
            'mold_result'           => 'nullable|string|max:100',
            'mold_image_before'     => 'nullable|array',
            'mold_image_after'      => 'nullable|array',
            'reli_result'           => 'nullable|string|max:100',
            'reli_image_before'     => 'nullable|array',
            'reli_image_after'      => 'nullable|array',
            'dipping_result'        => 'nullable|string|max:100',
            'dipping_image_before'  => 'nullable|array',
            'dipping_image_after'   => 'nullable|array',
            'reflow_result'         => 'nullable|string|max:100',
            'reflow_image_before'   => 'nullable|array',
            'reflow_image_after'    => 'nullable|array',
            'measure_result'        => 'nullable|string|max:100',
            'measure_image_before'  => 'nullable|array',
            'measure_image_after'   => 'nullable|array',
            'analysis_result'       => 'nullable|string|max:100',
            'analysis_completed_at' => 'nullable|date',
            'remarks'               => 'nullable|string',
            'total_tat'             => 'nullable|integer',
        ]);

        $user = auth()->user();
        $validated['updated_by'] = $user->emp_name ?? $user->name ?? 'system';

        // If no explicit final decision is set, derive analysis_result from step results
        if (empty($validated['analysis_result'])) {
            $stepResults = [
                $validated['mold_result']    ?? $record->mold_result,
                $validated['reli_result']    ?? $record->reli_result,
                $validated['dipping_result'] ?? $record->dipping_result,
                $validated['reflow_result']  ?? $record->reflow_result,
                $validated['measure_result'] ?? $record->measure_result,
            ];
            $filled = array_filter($stepResults, fn($v) => !is_null($v) && $v !== '');
            if (count($filled) > 0 && in_array('In Progress', $filled, true)) {
                $validated['analysis_result'] = 'In Progress';
            }
        }

        // Sync qc_inspection when analysis_result is In Progress
        if (($validated['analysis_result'] ?? null) === 'In Progress') {
            \App\Models\QcInspection::where('lot_id', $record->lot_id)
                ->latest()
                ->first()
                ?->update([
                    'analysis_result' => 'In Progress',
                    'final_decision'  => 'In Progress',
                    'updated_by'      => $validated['updated_by'],
                ]);
        }

        // Auto-stamp completed_at when a real final result is set for the first time
        $finalDecisions = ['Proceed', 'Rework', 'Proceed - w/ OTHER', 'DRB', 'Derive / Scrap (RL)'];
        if (!empty($validated['analysis_result'])
            && in_array($validated['analysis_result'], $finalDecisions, true)
            && empty($record->analysis_completed_at)
        ) {
            $validated['analysis_completed_at'] = now();
            if ($record->analysis_start_at) {
                $validated['total_tat'] = (int) $record->analysis_start_at->diffInMinutes(now());
            }
        }

        $record->update($validated);

        // Route based on final decision
        $decision = $validated['analysis_result'] ?? null;

        if (in_array($decision, ['Proceed', 'Derive / Scrap (RL)'], true)) {
            $inspection = \App\Models\QcInspection::where('lot_id', $record->lot_id)->latest()->first();
            if ($inspection) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($inspection, $record, $validated, $decision) {
                    $inspection->update([
                        'analysis_completed_at' => now(),
                        'analysis_result'       => $decision,
                        'production_start_at'   => now(),
                        'final_decision'        => $decision,
                        'output_status'         => 'pending',
                        'updated_by'            => $validated['updated_by'],
                    ]);

                    \App\Models\QcOk::updateOrCreate(
                        ['lot_id' => $record->lot_id],
                        [
                            'model'             => $record->model,
                            'lot_qty'           => $record->lot_qty,
                            'lipas_yn'          => $record->lipas_yn,
                            'work_type'         => $record->work_type,
                            'inspection_times'  => $record->inspection_times,
                            'inspection_spl'    => $record->inspection_spl,
                            'inspection_result' => $inspection->inspection_result,
                            'analysis_result'   => $decision,
                            'technical_result'  => $inspection->technical_result,
                            'pending'           => 'production',
                            'output_status'     => 'pending',
                            'remarks'           => $record->remarks,
                            'created_by'        => $inspection->created_by,
                            'updated_by'        => $validated['updated_by'],
                        ]
                    );
                });
            }
        } elseif (in_array($decision, ['Rework', 'Proceed - w/ OTHER', 'DRB'], true)) {
            $inspection = \App\Models\QcInspection::where('lot_id', $record->lot_id)->latest()->first();
            if ($inspection) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($inspection, $record, $validated, $decision) {
                    $inspection->update([
                        'analysis_completed_at' => now(),
                        'analysis_result'       => $decision,
                        'final_decision'        => $decision,
                        'technical_start_at'    => now(),
                        'output_status'         => 'pending',
                        'updated_by'            => $validated['updated_by'],
                    ]);

                    \App\Models\ViTechnical::updateOrCreate(
                        ['lot_id' => $record->lot_id],
                        [
                            'model'            => $record->model,
                            'lot_qty'          => $record->lot_qty,
                            'lipas_yn'         => $record->lipas_yn,
                            'work_type'        => $record->work_type,
                            'inspection_times' => $record->inspection_times,
                            'inspection_spl'   => $record->inspection_spl,
                            'defect_code'      => $record->defect_code,
                            'technical_start_at' => now(),
                            'remarks'          => $record->remarks,
                            'created_by'       => $inspection->created_by,
                            'updated_by'       => $validated['updated_by'],
                        ]
                    );
                });
            }
        } elseif ($decision === 'For Decision') {
            // Flag as pending — no routing, just update qc_inspection final_decision
            \App\Models\QcInspection::where('lot_id', $record->lot_id)
                ->latest()
                ->first()
                ?->update([
                    'final_decision'  => 'Pending',
                    'analysis_result' => 'For Decision',
                    'updated_by'      => $validated['updated_by'],
                ]);
        }

        return response()->json([
            'success' => true,
            'data'    => $record->fresh(),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    private function classifyBucket(?string $mainlot, ?string $rr, ?string $ly): string
    {
        if ($mainlot === 'NG') return 'Mainlot';
        if ($mainlot === 'OK' && $rr === 'NG') return 'R-rework';
        if ($mainlot === 'OK' && $ly === 'NG') return 'L-rework';
        return 'Mainlot';
    }

    public function export(Request $request)
    {
        $query = QcAnalysis::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }
        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        $records = $query->orderByDesc('created_at')->get();

        $columns = [
            'lot_id','model','lot_qty','lipas_yn','work_type',
            'inspection_times','inspection_spl','defect_code',
            'analysis_start_at','analysis_completed_at','analysis_result',
            'mold_result','reli_result','dipping_result','reflow_result','measure_result',
            'total_tat','remarks','created_by','updated_by','created_at',
        ];

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="qc_analysis_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($records, $columns) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_map('strtoupper', $columns));
            foreach ($records as $row) {
                fputcsv($handle, array_map(fn($col) => $row->$col ?? '', $columns));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
