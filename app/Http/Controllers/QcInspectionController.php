<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\QcInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QcInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = QcInspection::query();

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

        return response()->json([
            'success' => true,
            'data'    => $query->orderByDesc('created_at')->get(),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lot_id'            => 'required|string|max:20',
            'model'             => 'nullable|string|max:100',
            'lot_qty'           => 'nullable|integer|min:0',
            'lipas_yn'          => 'nullable|string|max:1',
            'work_type'         => 'nullable|string|max:50',
            'inspection_times'  => 'required|integer|min:1|max:99',
            'inspection_spl'    => 'required|integer|min:0',
            'inspected_bin'     => 'nullable|string|max:255',
            'inspection_result' => 'nullable|string|max:100',
            'mainlot_result'    => 'nullable|string|max:20',
            'rr_result'         => 'nullable|string|max:20',
            'ly_result'         => 'nullable|string|max:20',
            'defect_code'       => 'nullable|string|max:255',
            'defect_flow'       => 'nullable|string|max:100',
            'final_decision'    => 'nullable|string|max:50',
            'remarks'           => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['created_by'] = $user->emp_name ?? $user->name ?? 'system';
        $validated['updated_by'] = $validated['created_by'];

        if (($validated['inspection_result'] ?? null) === 'OK') {
            $validated['production_start_at'] = now();

            // Mirror to qc_ok table
            \Illuminate\Support\Facades\DB::table('qc_ok')->insert([
                'lot_id'            => $validated['lot_id'],
                'model'             => $validated['model'] ?? null,
                'lot_qty'           => $validated['lot_qty'] ?? null,
                'lipas_yn'          => $validated['lipas_yn'] ?? null,
                'work_type'         => $validated['work_type'] ?? null,
                'inspection_times'  => $validated['inspection_times'] ?? null,
                'inspection_spl'    => $validated['inspection_spl'] ?? null,
                'inspection_result' => 'OK',
                'pending'           => 'production',
                'remarks'           => $validated['remarks'] ?? null,
                'created_by'        => $validated['created_by'],
                'updated_by'        => $validated['updated_by'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // QC Analysis flow → populate qc_analysis + stamp analysis_start_at
        if (($validated['defect_flow'] ?? null) === 'QC Analysis') {
            $validated['analysis_start_at'] = now();

            \Illuminate\Support\Facades\DB::table('qc_analysis')->insert([
                'lot_id'            => $validated['lot_id'],
                'model'             => $validated['model'] ?? null,
                'lot_qty'           => $validated['lot_qty'] ?? null,
                'lipas_yn'          => $validated['lipas_yn'] ?? null,
                'work_type'         => $validated['work_type'] ?? null,
                'inspection_times'  => $validated['inspection_times'] ?? null,
                'inspection_spl'    => $validated['inspection_spl'] ?? null,
                'defect_code'       => $validated['defect_code'] ?? null,
                'analysis_start_at' => now(),
                'remarks'           => $validated['remarks'] ?? null,
                'created_by'        => $validated['created_by'],
                'updated_by'        => $validated['updated_by'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Tech'l Verification flow → populate vi_technical + stamp technical_start_at
        if (($validated['defect_flow'] ?? null) === "Tech'l Verification") {
            $validated['technical_start_at'] = now();

            \Illuminate\Support\Facades\DB::table('vi_technical')->insert([
                'lot_id'             => $validated['lot_id'],
                'model'              => $validated['model'] ?? null,
                'lot_qty'            => $validated['lot_qty'] ?? null,
                'lipas_yn'           => $validated['lipas_yn'] ?? null,
                'work_type'          => $validated['work_type'] ?? null,
                'inspection_times'   => $validated['inspection_times'] ?? null,
                'inspection_spl'     => $validated['inspection_spl'] ?? null,
                'defect_code'        => $validated['defect_code'] ?? null,
                'technical_start_at' => now(),
                'remarks'            => $validated['remarks'] ?? null,
                'created_by'         => $validated['created_by'],
                'updated_by'         => $validated['updated_by'],
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        $record = QcInspection::create($validated);

        return response()->json([
            'success' => true,
            'data'    => $record,
            'error'   => null,
            'meta'    => null,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = QcInspection::findOrFail($id);

        $validated = $request->validate([
            'lot_id'            => 'sometimes|required|string|max:20',
            'model'             => 'nullable|string|max:100',
            'lot_qty'           => 'nullable|integer|min:0',
            'lipas_yn'          => 'nullable|string|max:1',
            'work_type'         => 'nullable|string|max:50',
            'inspection_times'  => 'sometimes|required|integer|min:1|max:99',
            'inspection_spl'    => 'required|integer|min:0',
            'inspected_bin'     => 'nullable|string|max:255',
            'inspection_result' => 'nullable|string|max:100',
            'mainlot_result'    => 'nullable|string|max:20',
            'rr_result'         => 'nullable|string|max:20',
            'ly_result'         => 'nullable|string|max:20',
            'defect_code'       => 'nullable|string|max:255',
            'defect_flow'       => 'nullable|string|max:100',
            'final_decision'    => 'nullable|string|max:50',
            'remarks'           => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['updated_by'] = $user->emp_name ?? $user->name ?? 'system';

        $record->update($validated);

        return response()->json([
            'success' => true,
            'data'    => $record->fresh(),
            'error'   => null,
            'meta'    => null,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        QcInspection::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'data'    => null,
            'error'   => null,
            'meta'    => null,
        ]);
    }
}
