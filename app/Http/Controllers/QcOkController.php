<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QcOk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class QcOkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $query = QcOk::query();

        // Date filter
        if ($request->filled('date') && ! $request->filled('search')) {
            $query->whereDate('created_at', $request->date);
        }

        // Search
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('lot_id', 'like', "%{$s}%")
                ->orWhere('model', 'like', "%{$s}%"));
        }

        // Shift
        if ($request->filled('shift')) {
            if ($request->shift === 'DAY') {
                $query->whereTime('created_at', '>=', '07:00:00')
                      ->whereTime('created_at', '<', '19:00:00');
            } elseif ($request->shift === 'NIGHT') {
                $query->where(fn ($q) => $q->whereTime('created_at', '>=', '19:00:00')
                    ->orWhereTime('created_at', '<', '07:00:00'));
            }
        }

        // Cutoff
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

        if ($request->filled('work_type')) {
            $query->where('work_type', $request->work_type);
        }

        if ($request->filled('lipas_yn')) {
            $query->where('lipas_yn', $request->lipas_yn);
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        // Summary buckets
        $pending   = $records->filter(fn ($r) => ! $r->output_status || $r->output_status === 'Pending');
        $completed = $records->filter(fn ($r) => $r->output_status === 'Completed');
        $rework    = $records->filter(fn ($r) => $r->output_status === 'Rework');

        // Prev-day pending (unresolved lots from before today)
        $prevDay = QcOk::whereDate('created_at', '<', $today)
            ->where(fn ($q) => $q->whereNull('output_status')
                ->orWhere('output_status', 'Pending'))
            ->when($request->filled('work_type'), fn ($q) => $q->where('work_type', $request->work_type))
            ->when($request->filled('lipas_yn'), fn ($q) => $q->where('lipas_yn', $request->lipas_yn))
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
            'error'   => null,
            'meta'    => [
                'total_count'     => $records->count(),
                'total_qty'       => (int) $records->sum('lot_qty'),
                'pending_count'   => $pending->count(),
                'pending_qty'     => (int) $pending->sum('lot_qty'),
                'completed_count' => $completed->count(),
                'completed_qty'   => (int) $completed->sum('lot_qty'),
                'rework_count'    => $rework->count(),
                'rework_qty'      => (int) $rework->sum('lot_qty'),
                'prev_day_count'  => $prevDay->count(),
                'prev_day_qty'    => (int) $prevDay->sum('lot_qty'),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = QcOk::findOrFail($id);

        $validated = $request->validate([
            'output_status' => 'required|string|max:100',
            'remarks'       => 'nullable|string|max:500',
        ]);

        $by = auth()->user()->emp_name ?? auth()->user()->name ?? 'system';

        $record->update([
            'output_status'    => $validated['output_status'],
            'remarks'          => $validated['remarks'] ?? $record->remarks,
            'updated_by'       => $by,
            'lot_completed_at' => in_array($validated['output_status'], ['Completed', 'Rework'], true)
                ? now()
                : $record->lot_completed_at,
        ]);

        return response()->json(['success' => true, 'data' => $record->fresh(), 'error' => null, 'meta' => null]);
    }
}
