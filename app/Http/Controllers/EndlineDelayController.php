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
            'defect_class', 'work_type_filter', 'category',
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

        $row = DB::table('updatewip')
            ->where('lot_id', $lotId)
            ->select('lot_id', 'model_15', 'lot_qty', 'work_type', 'lipas_yn')
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

    public function qcAnalysisIndex(Request $request): JsonResponse
    {
        $records = $this->buildQuery($request)
            ->leftJoin('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->where('endline_delay.defect_class', 'QC Analysis')
            ->orderBy('endline_delay.created_at', 'desc')
            ->select('endline_delay.*', 'qc_defect_class.defect_name')
            ->get();

        return response()->json(['success' => true, 'data' => $records, 'error' => null, 'meta' => null]);
    }

    public function viTechnicalIndex(Request $request): JsonResponse
    {
        $records = $this->buildQuery($request)
            ->leftJoin('qc_defect_class', 'endline_delay.qc_defect', '=', 'qc_defect_class.defect_code')
            ->where('endline_delay.defect_class', "Tech'l Verification")
            ->orderBy('endline_delay.created_at', 'desc')
            ->select('endline_delay.*', 'qc_defect_class.defect_name')
            ->get();

        return response()->json(['success' => true, 'data' => $records, 'error' => null, 'meta' => null]);
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
                    'qc_ana_start','qc_ana_result','qc_ana_tat','vi_techl_start','vi_techl_result',
                    'vi_techl_tat','final_decision','total_tat','remarks','inspection_times','updated_by','created_at'];

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
            'qc_ana_result'    => 'nullable|string|max:100',
            'qc_ana_tat'       => 'nullable|integer',
            'vi_techl_start'   => 'nullable|date',
            'vi_techl_result'  => 'nullable|string|max:100',
            'vi_techl_tat'     => 'nullable|integer',
            'final_decision'   => 'nullable|string|max:100',
            'total_tat'        => 'nullable|integer',
            'work_type'         => 'nullable|string|max:100',
            'remarks'           => 'nullable|string',
            'inspection_times'  => 'nullable|integer|min:1|max:255',
        ]);

        $id = DB::table('endline_delay')->insertGetId(array_merge($validated, [
            'updated_by' => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'created_at' => now(),
            'updated_at' => now(),
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
            'qc_ana_result'    => 'nullable|string|max:100',
            'qc_ana_tat'       => 'nullable|integer',
            'vi_techl_start'   => 'nullable|date',
            'vi_techl_result'  => 'nullable|string|max:100',
            'vi_techl_tat'     => 'nullable|integer',
            'final_decision'   => 'nullable|string|max:100',
            'total_tat'        => 'nullable|integer',
            'work_type'         => 'nullable|string|max:100',
            'remarks'           => 'nullable|string',
            'inspection_times'  => 'nullable|integer|min:1|max:255',
        ]);

        DB::table('endline_delay')->where('id', $id)->update(array_merge($validated, [
            'updated_by' => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'updated_at' => now(),
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
}
