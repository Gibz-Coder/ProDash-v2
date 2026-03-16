<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QcDefectClassController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('qc_defect_class');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('defect_code', 'like', "%{$search}%")
                  ->orWhere('defect_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('defect_flow')) {
            $query->where('defect_flow', $request->defect_flow);
        }

        $defects = $query->orderBy('defect_code')->get();

        return response()->json($defects);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'defect_code'  => 'required|string|max:50|unique:qc_defect_class,defect_code',
            'defect_name'  => 'required|string|max:255',
            'defect_class' => 'nullable|string|max:255',
            'defect_flow'  => 'required|string|max:100',
            'remarks'      => 'nullable|string',
        ]);

        $id = DB::table('qc_defect_class')->insertGetId([
            'defect_code'  => strtoupper(trim($validated['defect_code'])),
            'defect_name'  => $validated['defect_name'],
            'defect_class' => $validated['defect_class'] ?? '',
            'defect_flow'  => $validated['defect_flow'],
            'created_by'   => auth()->user()->emp_name ?? auth()->user()->name ?? 'system',
            'remarks'      => $validated['remarks'] ?? null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $defect = DB::table('qc_defect_class')->find($id);

        return response()->json(['success' => true, 'message' => 'Defect created successfully.', 'data' => $defect], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $defect = DB::table('qc_defect_class')->find($id);
        if (!$defect) {
            return response()->json(['success' => false, 'message' => 'Defect not found.'], 404);
        }

        $validated = $request->validate([
            'defect_code'  => "required|string|max:50|unique:qc_defect_class,defect_code,{$id}",
            'defect_name'  => 'required|string|max:255',
            'defect_class' => 'nullable|string|max:255',
            'defect_flow'  => 'required|string|max:100',
            'remarks'      => 'nullable|string',
        ]);

        DB::table('qc_defect_class')->where('id', $id)->update([
            'defect_code'  => strtoupper(trim($validated['defect_code'])),
            'defect_name'  => $validated['defect_name'],
            'defect_class' => $validated['defect_class'] ?? '',
            'defect_flow'  => $validated['defect_flow'],
            'remarks'      => $validated['remarks'] ?? null,
            'updated_at'   => now(),
        ]);

        $updated = DB::table('qc_defect_class')->find($id);

        return response()->json(['success' => true, 'message' => 'Defect updated successfully.', 'data' => $updated]);
    }

    public function destroy(int $id): JsonResponse
    {
        $defect = DB::table('qc_defect_class')->find($id);
        if (!$defect) {
            return response()->json(['success' => false, 'message' => 'Defect not found.'], 404);
        }

        DB::table('qc_defect_class')->where('id', $id)->delete();

        return response()->json(['success' => true, 'message' => 'Defect deleted successfully.']);
    }
}
