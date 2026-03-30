<?php

namespace App\Http\Controllers;

use App\Services\EndlineChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EndlineDelayController extends Controller
{
    public function __construct(private readonly EndlineChartService $chartService) {}

    public function chartData(Request $request): JsonResponse
    {
        $filters = $request->only([
            'date', 'shift', 'cutoff', 'work_type', 'lipas_yn',
            'defect_class', 'work_type_filter', 'category', 'status_filter',
        ]);

        $data = $this->chartService->getChartData($filters);

        return response()->json([
            'success' => true,
            'data'    => $data,
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function lotLookup(Request $request): JsonResponse
    {
        $lotId = $request->query('lot_id', '');
        if (!$lotId) {
            return response()->json(null);
        }

        $row = DB::table('mes_data.wip_status as w')
            ->leftJoin('mes_data.monthly_plan as mp', 'mp.chip_model', '=', 'w.model_id')
            ->where('w.lot_no', $lotId)
            ->select(
                'w.lot_no',
                'w.model_id as model_15',
                'w.current_qty as lot_qty',
                DB::raw("CASE
                    WHEN w.warehouse_rework_yn = 'Y' THEN 'WH REWORK'
                    WHEN w.rework_type = 'Normal' THEN 'NORMAL'
                    WHEN w.rework_type = 'Process Rework' THEN 'PROCESS RW'
                    WHEN w.rework_type = 'Outgoing NG' THEN 'OI REWORK'
                    ELSE NULL
                END as work_type"),
                'mp.vi_lipas_yn as lipas_yn',
            )
            ->first();

        return response()->json($row);
    }

    public function defectCodeSearch(Request $request): JsonResponse
    {
        $q = $request->query('q', '');
        $results = DB::table('qc_defect_class')
            ->when($q, fn($query) => $query->where('defect_code', 'like', "%{$q}%")
                ->orWhere('defect_name', 'like', "%{$q}%"))
            ->select('defect_code', 'defect_name', 'defect_class', 'defect_flow')
            ->orderBy('defect_code')
            ->limit(20)
            ->get();

        return response()->json($results);
    }

    private function buildQuery(Request $request)
    {
        $query = DB::table('endline_delay');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('lot_id', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->filled('final_decision')) {
            $query->where('final_decision', $request->final_decision);
        }

        if ($request->filled('date')) {
            $query->whereDate('endline_delay.created_at', $request->date);
        }

        if ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('endline_delay.created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('endline_delay.created_at', '<=', $request->date_to);
            }
        }

        if ($request->filled('shift')) {
            $shift = $request->shift;
            if ($shift === 'DAY') {
                $query->whereTime('endline_delay.created_at', '>=', '07:00:00')
                      ->whereTime('endline_delay.created_at', '<', '19:00:00');
            } elseif ($shift === 'NIGHT') {
                $query->where(function ($q) {
                    $q->whereTime('endline_delay.created_at', '>=', '19:00:00')
                      ->orWhereTime('endline_delay.created_at', '<', '07:00:00');
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
                $query->whereTime('endline_delay.created_at', '>=', $from)
                      ->whereTime('endline_delay.created_at', '<=', $to);
            }
        }

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        return $query;
    }

    public function index(Request $request): JsonResponse
    {
        $records = $this->buildQuery($request)->orderBy('created_at', 'desc')->get();
        return response()->json($records);
    }

    public function qcOkIndex(Request $request): JsonResponse
    {
        $records = $this->buildQuery($request)
            ->leftJoin('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->where(function ($q) {
                // QC OK lots entered directly
                $q->where('endline_delay.qc_result', 'OK')
                  // OR any lot that cleared QC Analysis with Proceed
                  ->orWhere('endline_delay.qc_ana_result', 'Proceed')
                  // OR any lot that cleared VI Technical with Proceed
                  ->orWhere('endline_delay.vi_techl_result', 'Proceed');
            })
            ->orderBy('endline_delay.created_at', 'desc')
            ->select('endline_delay.*', 'qc_defect_class.defect_name')
            ->get();

        $today = now()->toDateString();

        // QC Pending: routed to QC Analysis, awaiting result (only QC OK source lots)
        $qcPending = $records->filter(
            fn($r) => trim($r->qc_result ?? '') === 'OK'
                   && $r->defect_class === 'QC Analysis'
                   && is_null($r->qc_ana_result)
        )->values();

        // Technical Pending: routed to VI Technical, awaiting result
        $techPending = $records->filter(
            fn($r) => $r->defect_class === "Tech'l Verification"
                   && !is_null($r->vi_techl_start)
                   && is_null($r->vi_techl_result)
        )->values();

        // Production Pending: fresh QC OK lots not yet routed, "For Verify", or cleared QC/VI — but not already finalized
        $prodPending = $records->filter(
            fn($r) => $r->output_status !== 'Completed'
                   && $r->output_status !== 'Rework'
                   && (
                       // Fresh QC OK lot — no routing applied yet
                       (trim($r->qc_result ?? '') === 'OK'
                           && is_null($r->qc_ana_result)
                           && is_null($r->vi_techl_result)
                           && $r->defect_class !== 'QC Analysis'
                           && $r->defect_class !== "Tech'l Verification")
                       || $r->final_decision === 'For Verify'
                       || $r->qc_ana_result === 'Proceed'
                       || $r->vi_techl_result === 'Proceed'
                   )
        )->values();

        // Completed: output_status = 'Completed' or 'Rework'
        $completed = $records->filter(
            fn($r) => $r->output_status === 'Completed' || $r->output_status === 'Rework'
        )->values();

        $prevDay = $records->filter(fn($r) =>
            is_null($r->qc_ana_result) && is_null($r->vi_techl_result) &&
            !is_null($r->created_at) &&
            \Carbon\Carbon::parse($r->created_at)->toDateString() < $today
        )->values();

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'total_count'        => $records->count(),
                'total_qty'          => $records->sum('lot_qty'),
                'qc_pending_count'   => $qcPending->count(),
                'qc_pending_qty'     => $qcPending->sum('lot_qty'),
                'tech_pending_count' => $techPending->count(),
                'tech_pending_qty'   => $techPending->sum('lot_qty'),
                'prod_pending_count' => $prodPending->count(),
                'prod_pending_qty'   => $prodPending->sum('lot_qty'),
                'completed_count'    => $completed->count(),
                'completed_qty'      => $completed->sum('lot_qty'),
                'prev_day_count'     => $prevDay->count(),
                'prev_day_qty'       => $prevDay->sum('lot_qty'),
            ],
        ]);
    }

    public function qcAnalysisIndex(Request $request): JsonResponse
    {
        $records = $this->buildQuery($request)
            ->leftJoin('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->where('endline_delay.defect_class', 'QC Analysis')
            ->orderBy('endline_delay.created_at', 'desc')
            ->select('endline_delay.*', 'qc_defect_class.defect_name')
            ->get();

        $prevDay = $this->buildPrevDayQuery($request, 'QC Analysis')->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'prev_day_count' => $prevDay->count(),
                'prev_day_qty'   => $prevDay->sum('lot_qty'),
            ],
        ]);
    }

    public function viTechnicalIndex(Request $request): JsonResponse
    {
        // Show lots that are natively VI Technical OR lots handed off from QC Analysis
        // (qc_ana_result = Rework/DRB Approval sets vi_techl_start)
        $records = $this->buildQuery($request)
            ->leftJoin('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->where(function ($q) {
                $q->where('endline_delay.defect_class', "Tech'l Verification")
                  ->orWhere(function ($q2) {
                      $q2->where('endline_delay.defect_class', 'QC Analysis')
                         ->whereNotNull('endline_delay.vi_techl_start');
                  });
            })
            ->orderBy('endline_delay.created_at', 'desc')
            ->select('endline_delay.*', 'qc_defect_class.defect_name')
            ->get();

        $prevDay = $this->buildPrevDayQuery($request, "Tech'l Verification")->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'prev_day_count' => $prevDay->count(),
                'prev_day_qty'   => $prevDay->sum('lot_qty'),
            ],
        ]);
    }

    private function buildPrevDayQuery(Request $request, string $defectClass)
    {
        $today = now()->toDateString();

        // Include both pending (no prog, no result) and in-progress (prog set, no result)
        $query = DB::table('endline_delay')
            ->whereNull('qc_ana_result')
            ->whereNull('vi_techl_result')
            ->whereDate('created_at', '<', $today);

        // For VI Technical, also include QC Analysis lots handed off via vi_techl_start
        if ($defectClass === "Tech'l Verification") {
            $query->where(function ($q) use ($defectClass) {
                $q->where('defect_class', $defectClass)
                  ->orWhere(function ($q2) {
                      $q2->where('defect_class', 'QC Analysis')
                         ->whereNotNull('vi_techl_start');
                  });
            });
        } else {
            $query->where('defect_class', $defectClass);
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

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        return $query;
    }

    public function submitQcAnalysis(Request $request, int $id): JsonResponse
    {
        $row = DB::table('endline_delay')->find($id);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $validated = $request->validate([
            'result'  => 'required|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $result  = $validated['result'];
        $by      = auth()->user()->emp_name ?? auth()->user()->name ?? 'system';

        // Detect if this lot originated from QC OK (qc_result = 'OK')
        $isFromQcOk = trim($row->qc_result ?? '') === 'OK';

        if ($result === 'Proceed') {
            if ($isFromQcOk) {
                // QC OK lot decided Proceed → keep routing reason, append result
                $baseRemarks = preg_replace('/ - (OK|NG|Rework|Proceed)$/i', '', trim($row->remarks ?? ''));
                DB::table('endline_delay')->where('id', $id)->update([
                    'qc_ana_prog'         => 'Proceed',
                    'qc_ana_result'       => 'Proceed',
                    'qc_ana_completed_at' => now(),
                    'final_decision'      => 'Proceed',
                    'remarks'             => $baseRemarks . ' - OK',
                    'updated_by'          => $by,
                    'updated_at'          => now(),
                ]);
            } else {
                // Non-QC-OK lot → show defect code + Proceed in remarks
                $defectCode = trim($row->qc_defect ?? '');
                DB::table('endline_delay')->where('id', $id)->update([
                    'qc_ana_prog'         => 'Proceed',
                    'qc_ana_result'       => 'Proceed',
                    'qc_ana_completed_at' => now(),
                    'final_decision'      => 'Proceed',
                    'remarks'             => $defectCode ? "{$defectCode} - Proceed" : 'Proceed',
                    'updated_by'          => $by,
                    'updated_at'          => now(),
                ]);
            }
        } elseif ($result === 'Rework') {
            if ($isFromQcOk) {
                // QC OK lot decided Rework → keep routing reason, append result
                $baseRemarks = preg_replace('/ - (OK|NG|Rework|Proceed)$/i', '', trim($row->remarks ?? ''));
                DB::table('endline_delay')->where('id', $id)->update([
                    'qc_ana_result'       => 'Rework',
                    'qc_ana_completed_at' => now(),
                    'final_decision'      => 'Rework',
                    'output_status'       => 'Rework',
                    'remarks'             => $baseRemarks . ' - NG',
                    'updated_by'          => $by,
                    'updated_at'          => now(),
                ]);
            } else {
                // Normal lot Rework → hand off to VI Technical
                DB::table('endline_delay')->where('id', $id)->update([
                    'qc_ana_result'       => 'Rework',
                    'qc_ana_completed_at' => now(),
                    'final_decision'      => 'Technical',
                    'vi_techl_start'      => now(),
                    'remarks'             => $validated['remarks'] ?? $row->remarks,
                    'updated_by'          => $by,
                    'updated_at'          => now(),
                ]);
            }
        } elseif ($result === 'DRB Approval') {
            DB::table('endline_delay')->where('id', $id)->update([
                'qc_ana_result'       => $result,
                'qc_ana_completed_at' => now(),
                'final_decision'      => 'Technical',
                'vi_techl_start'      => now(),
                'remarks'             => $validated['remarks'] ?? $row->remarks,
                'updated_by'          => $by,
                'updated_at'          => now(),
            ]);
        } else {
            // Other items (MOLD, RELI, Dipping, etc.): set as in-progress
            DB::table('endline_delay')->where('id', $id)->update([
                'qc_ana_prog'    => $result,
                'final_decision' => 'In Progress',
                'remarks'        => $validated['remarks'] ?? $row->remarks,
                'updated_by'     => $by,
                'updated_at'     => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => DB::table('endline_delay')->find($id),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function submitViTechnical(Request $request, int $id): JsonResponse
    {
        $row = DB::table('endline_delay')->find($id);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $validated = $request->validate([
            'result'  => 'required|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $result  = $validated['result'];
        $by      = auth()->user()->emp_name ?? auth()->user()->name ?? 'system';

        // Detect if this lot originated from QC OK (qc_result = 'OK')
        $isFromQcOk = trim($row->qc_result ?? '') === 'OK';

        if ($result === 'Proceed') {
            if ($isFromQcOk) {
                // QC OK lot decided Proceed from VI Technical
                $baseRemarks = preg_replace('/ - (OK|NG|Rework|Proceed)$/i', '', trim($row->remarks ?? ''));
                DB::table('endline_delay')->where('id', $id)->update([
                    'vi_techl_prog'         => 'Proceed',
                    'vi_techl_result'       => 'Proceed',
                    'vi_techl_completed_at' => now(),
                    'final_decision'        => 'Proceed',
                    'remarks'               => $baseRemarks . ' - OK',
                    'updated_by'            => $by,
                    'updated_at'            => now(),
                ]);
            } else {
                // Non-QC-OK lot → show defect code + Proceed in remarks
                $defectCode = trim($row->qc_defect ?? '');
                DB::table('endline_delay')->where('id', $id)->update([
                    'vi_techl_prog'         => 'Proceed',
                    'vi_techl_result'       => 'Proceed',
                    'vi_techl_completed_at' => now(),
                    'final_decision'        => 'Proceed',
                    'remarks'               => $defectCode ? "{$defectCode} - Proceed" : 'Proceed',
                    'updated_by'            => $by,
                    'updated_at'            => now(),
                ]);
            }
        } elseif ($result === 'Rework') {
            if ($isFromQcOk) {
                // QC OK lot decided Rework from VI Technical
                $baseRemarks = preg_replace('/ - (OK|NG|Rework|Proceed)$/i', '', trim($row->remarks ?? ''));
                DB::table('endline_delay')->where('id', $id)->update([
                    'vi_techl_result'       => 'Rework',
                    'vi_techl_completed_at' => now(),
                    'final_decision'        => 'Rework',
                    'remarks'               => $baseRemarks . ' - NG',
                    'updated_by'            => $by,
                    'updated_at'            => now(),
                ]);
            } else {
                DB::table('endline_delay')->where('id', $id)->update([
                    'vi_techl_result'       => 'Rework',
                    'vi_techl_completed_at' => now(),
                    'final_decision'        => 'Rework',
                    'remarks'               => $validated['remarks'] ?? $row->remarks,
                    'updated_by'            => $by,
                    'updated_at'            => now(),
                ]);
            }
        } else {
            // DRB Approval / For Decision: in-progress, keep VI open
            DB::table('endline_delay')->where('id', $id)->update([
                'vi_techl_prog'  => $result,
                'final_decision' => 'In Progress',
                'remarks'        => $validated['remarks'] ?? $row->remarks,
                'updated_by'     => $by,
                'updated_at'     => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => DB::table('endline_delay')->find($id),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function startQcAnalysis(int $id): JsonResponse
    {
        $row = DB::table('endline_delay')->find($id);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        if (! empty($row->qc_ana_start)) {
            return response()->json(['success' => false, 'message' => 'Already started.'], 422);
        }

        DB::table('endline_delay')->where('id', $id)->update([
            'qc_ana_start' => now(),
            'updated_by'   => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'updated_at'   => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('endline_delay')->find($id),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function startViTechnical(int $id): JsonResponse
    {
        $row = DB::table('endline_delay')->find($id);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        if (! empty($row->vi_techl_start)) {
            return response()->json(['success' => false, 'message' => 'Already started.'], 422);
        }

        DB::table('endline_delay')->where('id', $id)->update([
            'vi_techl_start' => now(),
            'updated_by'     => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'updated_at'     => now(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => DB::table('endline_delay')->find($id),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function export(Request $request)
    {
        $records = $this->buildQuery($request)->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="endline_delay_' . now()->format('Ymd_His') . '.csv"',
        ];

        $columns = ['lot_id','model','lot_qty','lipas_yn','qc_result','qc_defect','defect_class',
                    'qc_ana_start','qc_ana_prog','qc_ana_result','qc_ana_completed_at',
                    'vi_techl_start','vi_techl_prog','vi_techl_result',
                    'vi_techl_completed_at','final_decision','total_tat','remarks','inspection_times','updated_by','created_at'];

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lot_id'           => 'required|string|max:100',
            'model'            => 'nullable|string|max:100',
            'lot_qty'          => 'nullable|integer',
            'lipas_yn'         => 'nullable|string|max:10',
            'qc_result'        => 'nullable|string|max:50',
            'qc_defect'        => 'nullable|string|max:255',
            'defect_class'     => 'nullable|string|max:100',
            'qc_ana_start'     => 'nullable|date',
            'qc_ana_prog'           => 'nullable|string|max:100',
            'qc_ana_result'         => 'nullable|string|max:100',
            'qc_ana_completed_at'   => 'nullable|date',
            'vi_techl_start'        => 'nullable|date',
            'vi_techl_prog'         => 'nullable|string|max:100',
            'vi_techl_result'       => 'nullable|string|max:100',
            'vi_techl_completed_at' => 'nullable|date',
            'final_decision'   => 'nullable|string|max:100',
            'total_tat'        => 'nullable|integer',
            'work_type'         => 'nullable|string|max:100',
            'remarks'           => 'nullable|string',
            'inspection_times'  => 'nullable|integer|min:1|max:255',
        ]);

        $id = DB::table('endline_delay')->insertGetId(array_merge($validated, [
            'qc_ana_start'   => $validated['defect_class'] === 'QC Analysis'        ? now() : null,
            'vi_techl_start' => $validated['defect_class'] === "Tech'l Verification" ? now() : null,
            'final_decision' => trim($validated['qc_result'] ?? '') === 'OK' ? 'QC OK' : 'Pending',
            'output_status'  => 'Pending',
            'updated_by'     => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Record created successfully.',
            'data'    => DB::table('endline_delay')->find($id),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!DB::table('endline_delay')->find($id)) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $validated = $request->validate([
            'lot_id'           => 'required|string|max:100',
            'model'            => 'nullable|string|max:100',
            'lot_qty'          => 'nullable|integer',
            'lipas_yn'         => 'nullable|string|max:10',
            'qc_result'        => 'nullable|string|max:50',
            'qc_defect'        => 'nullable|string|max:255',
            'defect_class'     => 'nullable|string|max:100',
            'qc_ana_start'     => 'nullable|date',
            'qc_ana_prog'           => 'nullable|string|max:100',
            'qc_ana_result'         => 'nullable|string|max:100',
            'qc_ana_completed_at'   => 'nullable|date',
            'vi_techl_start'        => 'nullable|date',
            'vi_techl_prog'         => 'nullable|string|max:100',
            'vi_techl_result'       => 'nullable|string|max:100',
            'vi_techl_completed_at' => 'nullable|date',
            'final_decision'   => 'nullable|string|max:100',
            'total_tat'        => 'nullable|integer',
            'work_type'         => 'nullable|string|max:100',
            'remarks'           => 'nullable|string',
            'inspection_times'  => 'nullable|integer|min:1|max:255',
        ]);

        DB::table('endline_delay')->where('id', $id)->update(array_merge($validated, [
            'output_status' => 'Pending',
            'updated_by'    => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'updated_at'    => now(),
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Record updated successfully.',
            'data'    => DB::table('endline_delay')->find($id),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        if (!DB::table('endline_delay')->find($id)) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        DB::table('endline_delay')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);
    }

    public function autoUpdateQcOkStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $validated['ids'];
        $by  = auth()->user()->emp_name ?? auth()->user()->name ?? 'system';

        // Only process lots that are still pending (not already finalized)
        $rows = DB::table('endline_delay')
            ->whereIn('id', $ids)
            ->whereNotIn('output_status', ['Completed', 'Rework'])
            ->get(['id', 'lot_id', 'lot_qty']);

        $completedIds = [];

        foreach ($rows as $row) {
            $exists = DB::table('mes_data.wip_status')
                ->where('lot_no', $row->lot_id)
                ->where('current_qty', $row->lot_qty)
                ->exists();

            if (! $exists) {
                $completedIds[] = $row->id;
            }
        }

        if (! empty($completedIds)) {
            DB::table('endline_delay')
                ->whereIn('id', $completedIds)
                ->update([
                    'output_status' => 'Completed',
                    'updated_by'    => $by,
                    'updated_at'    => now(),
                ]);
        }

        return response()->json([
            'success'       => true,
            'checked'       => $rows->count(),
            'completed'     => count($completedIds),
            'completed_ids' => $completedIds,
            'error'         => null,
            'meta'          => null,
        ]);
    }

    public function updateQcOkStatus(Request $request, int $id): JsonResponse
    {
        $row = DB::table('endline_delay')->find($id);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
        }

        $validated = $request->validate([
            'status'  => 'required|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $status  = $validated['status'];
        $remarks = $validated['remarks'] ?? $row->remarks;
        $by      = auth()->user()->emp_name ?? auth()->user()->name ?? 'system';

        // Mark as Completed — sets output_status only
        if ($status === 'Completed') {
            DB::table('endline_delay')->where('id', $id)->update([
                'output_status' => 'Completed',
                'remarks'       => $remarks,
                'updated_by'    => $by,
                'updated_at'    => now(),
            ]);

            return response()->json([
                'success' => true,
                'data'    => DB::table('endline_delay')->find($id),
                'error'   => null,
                'meta'    => null,
            ]);
        }

        // Statuses 1-6: route to QC Analysis, final_decision = Pending
        $toQcAnalysis = [
            'Waiting MOLD',
            'Waiting Reli',
            'Waiting Dipping',
            'Waiting Reflow',
            'Waiting OI Size',
            'For Decision (QC)',
        ];

        // Statuses 7-9: route to VI Technical, final_decision = Pending
        $toViTechnical = [
            'Real NG Scan',
            'Experiment',
            'For Schedule (Yield)',
        ];

        if (in_array($status, $toQcAnalysis, true)) {
            DB::table('endline_delay')->where('id', $id)->update([
                'defect_class'   => 'QC Analysis',
                'qc_defect'      => $status,
                'qc_ana_start'   => now(),
                'qc_ana_prog'    => null,
                'qc_ana_result'  => null,
                'final_decision' => 'Pending',
                'remarks'        => $status,
                'updated_by'     => $by,
                'updated_at'     => now(),
            ]);
        } elseif (in_array($status, $toViTechnical, true)) {
            DB::table('endline_delay')->where('id', $id)->update([
                'defect_class'    => "Tech'l Verification",
                'qc_defect'       => $status,
                'vi_techl_start'  => now(),
                'vi_techl_prog'   => null,
                'vi_techl_result' => null,
                'final_decision'  => 'Pending',
                'remarks'         => $status,
                'updated_by'      => $by,
                'updated_at'      => now(),
            ]);
        } elseif ($status === 'Low Yield (Rework)') {
            DB::table('endline_delay')->where('id', $id)->update([
                'qc_defect'      => $status,
                'final_decision' => 'Recovery',
                'output_status'  => 'Rework',
                'remarks'        => $status,
                'updated_by'     => $by,
                'updated_at'     => now(),
            ]);
        } elseif ($status === 'For Verify (Production)') {
            DB::table('endline_delay')->where('id', $id)->update([
                'qc_defect'      => $status,
                'final_decision' => 'For Verify',
                'remarks'        => $status,
                'updated_by'     => $by,
                'updated_at'     => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data'    => DB::table('endline_delay')->find($id),
            'error'   => null,
            'meta'    => null,
        ]);
    }
}
