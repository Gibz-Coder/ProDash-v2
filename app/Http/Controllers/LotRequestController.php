<?php

namespace App\Http\Controllers;

use App\Models\LotRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class LotRequestController extends Controller
{
    /**
     * Display the lot request dashboard
     */
    public function index(): Response
    {
        return Inertia::render('dashboards/main/lot-request');
    }

    /**
     * Get all lot requests with optional filters
     */
    public function getData(Request $request): JsonResponse
    {
        $query = LotRequest::query();

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'ALL') {
            $query->where('status', $request->status);
        }

        // Apply date range filter — but always include PENDING requests regardless of date
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $query->where(function ($q) use ($request) {
                // Match records within the date range
                $q->where(function ($dateQ) use ($request) {
                    if ($request->filled('date_from')) {
                        $dateQ->whereDate('requested', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $dateQ->whereDate('requested', '<=', $request->date_to);
                    }
                });

                // OR always include any PENDING request (regardless of date)
                // so old unresolved requests are never hidden
                $q->orWhere('status', 'PENDING');
            });
        }

        // Apply line filter
        if ($request->filled('line')) {
            $query->where('line', $request->line);
        }

        // Apply area filter
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Apply LIPAS filter
        if ($request->filled('lipas') && $request->lipas !== 'ALL') {
            $query->where('lipas', $request->lipas);
        }

        // Get the data
        $lotRequests = $query->orderBy('requested', 'desc')->get();

        // Calculate response time for each request
        $lotRequests->each(function ($request) {
            if ($request->requested && $request->completed) {
                $request->response_time = $request->calculateResponseTime();
            } elseif ($request->requested && $request->status === 'PENDING') {
                // Calculate elapsed time for pending requests
                $diff = $request->requested->diff(now());
                $parts = [];
                if ($diff->d > 0) $parts[] = $diff->d . 'd';
                if ($diff->h > 0) $parts[] = $diff->h . 'h';
                if ($diff->i > 0) $parts[] = $diff->i . 'm';
                $request->response_time = !empty($parts) ? implode(' ', $parts) : '0m';
            }
        });

        return response()->json([
            'data' => $lotRequests,
            'count' => $lotRequests->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get statistics for the dashboard
     */
    public function getStats(Request $request): JsonResponse
    {
        $query = LotRequest::query();

        // Apply date range if provided
        if ($request->filled('date_from')) {
            $query->whereDate('requested', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested', '<=', $request->date_to);
        }

        $total = $query->count();
        $pending = (clone $query)->where('status', 'PENDING')->count();
        $completed = (clone $query)->where('status', 'COMPLETED')->count();
        $rejected = (clone $query)->where('status', 'REJECTED')->count();
        
        $completionRate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

        // Calculate average response time for completed requests
        $completedRequests = (clone $query)
            ->where('status', 'COMPLETED')
            ->whereNotNull('requested')
            ->whereNotNull('completed')
            ->get();

        $avgResponseTime = 0;
        if ($completedRequests->count() > 0) {
            $totalHours = $completedRequests->reduce(function ($sum, $req) {
                $hours = $req->requested->diffInHours($req->completed);
                return $sum + $hours;
            }, 0);
            $avgResponseTime = round($totalHours / $completedRequests->count(), 1);
        }

        return response()->json([
            'total' => $total,
            'pending' => $pending,
            'completed' => $completed,
            'rejected' => $rejected,
            'completionRate' => $completionRate,
            'avgResponseTime' => $avgResponseTime,
        ]);
    }

    /**
     * Get requests grouped by size
     */
    public function getBySize(Request $request): JsonResponse
    {
        $query = LotRequest::query();

        // Apply date range if provided
        if ($request->filled('date_from')) {
            $query->whereDate('requested', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested', '<=', $request->date_to);
        }

        $requests = $query->get();

        // Define the size categories based on 3rd and 4th digit of model
        $sizeCategories = ['03', '05', '10', '21', '31', '32'];
        
        // Initialize result array
        $sizeGroups = [];
        
        foreach ($sizeCategories as $sizeCode) {
            // Filter requests where model's 3rd and 4th characters match the size code
            $filteredRequests = $requests->filter(function ($req) use ($sizeCode) {
                // Only completed requests use 'model' column, pending and rejected use 'request_model'
                $modelToCheck = $req->status === 'COMPLETED' ? $req->model : $req->request_model;
                
                // If no model available, skip
                if (!$modelToCheck) {
                    return false;
                }
                
                // Extract 3rd and 4th characters (index 2 and 3)
                if (strlen($modelToCheck) >= 4) {
                    $extractedSize = substr($modelToCheck, 2, 2);
                    return $extractedSize === $sizeCode;
                }
                return false;
            });
            
            $pending = $filteredRequests->where('status', 'PENDING')->count();
            $completed = $filteredRequests->where('status', 'COMPLETED')->count();
            $rejected = $filteredRequests->where('status', 'REJECTED')->count();
            
            $sizeGroups[] = [
                'size' => $sizeCode,
                'pending' => $pending,
                'completed' => $completed,
                'rejected' => $rejected,
                'total' => $filteredRequests->count(),
            ];
        }

        return response()->json([
            'data' => $sizeGroups,
        ]);
    }

    /**
     * Get requests grouped by production line
     */
    public function getByLine(Request $request): JsonResponse
    {
        $query = LotRequest::query();

        // Apply date range if provided
        if ($request->filled('date_from')) {
            $query->whereDate('requested', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested', '<=', $request->date_to);
        }

        $requests = $query->get();

        // Define fixed line categories A through K
        $lineCategories = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
        
        // Initialize result array
        $lineGroups = [];
        
        foreach ($lineCategories as $lineCode) {
            // Filter requests where line matches the line code
            $filteredRequests = $requests->filter(function ($req) use ($lineCode) {
                // Extract the line letter (assuming format like "Line A", "A", etc.)
                $line = $req->line;
                // Check if line contains the letter (case insensitive)
                return stripos($line, $lineCode) !== false;
            });
            
            $pending = $filteredRequests->where('status', 'PENDING')->count();
            $completed = $filteredRequests->where('status', 'COMPLETED')->count();
            $rejected = $filteredRequests->where('status', 'REJECTED')->count();
            
            $lineGroups[] = [
                'line' => $lineCode,
                'pending' => $pending,
                'completed' => $completed,
                'rejected' => $rejected,
                'total' => $filteredRequests->count(),
            ];
        }

        return response()->json([
            'data' => $lineGroups,
        ]);
    }

    /**
     * Accept a lot request
     */
    public function accept(Request $request, $id): JsonResponse
    {
        try {
            $lotRequest = LotRequest::findOrFail($id);

            if ($lotRequest->status === 'COMPLETED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already completed',
                ], 400);
            }

            $lotRequest->update([
                'status' => 'COMPLETED',
                'completed' => now(),
                'response_by' => auth()->user()->name,
            ]);

            // Calculate and save response time
            $lotRequest->response_time = $lotRequest->calculateResponseTime();
            $lotRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Request accepted successfully',
                'data' => $lotRequest,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error accepting lot request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a lot to a request
     */
    public function assignLot(Request $request, $id): JsonResponse
    {
        try {
            $lotRequest = LotRequest::findOrFail($id);

            if ($lotRequest->status === 'COMPLETED') {
                return response()->json([
                    'success' => false,
                    'message' => 'Request is already completed',
                ], 400);
            }

            $validated = $request->validate([
                'lot_no' => 'required|string',
                'model' => 'required|string',
                'quantity' => 'required|integer',
                'lipas' => 'required|string|in:Y,N',
                'lot_tat' => 'nullable|string',
                'lot_location' => 'nullable|string',
                'response_by' => 'required|string',
            ]);

            $lotRequest->update([
                'lot_no' => $validated['lot_no'],
                'model' => $validated['model'],
                'quantity' => $validated['quantity'],
                'lipas' => $validated['lipas'],
                'lot_tat' => $validated['lot_tat'] ?? null,
                'lot_location' => $validated['lot_location'] ?? null,
                'status' => 'COMPLETED',
                'completed' => now(),
                'response_by' => $validated['response_by'],
            ]);

            // Calculate and save response time
            $lotRequest->response_time = $lotRequest->calculateResponseTime();
            $lotRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Lot assigned successfully',
                'data' => $lotRequest,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error assigning lot to request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign lot',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a lot request
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $lotRequest = LotRequest::findOrFail($id);

            $validated = $request->validate([
                'remarks' => 'nullable|string',
            ]);

            $lotRequest->update([
                'status' => 'REJECTED',
                'completed' => now(),
                'response_by' => auth()->user()->name,
                'remarks' => $validated['remarks'] ?? $lotRequest->remarks,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Request rejected successfully',
                'data' => $lotRequest,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error rejecting lot request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if there's a pending request for a machine
     */
    public function checkPending(Request $request): JsonResponse
    {
        $mcNo = $request->query('mc_no');
        
        if (!$mcNo) {
            return response()->json([
                'has_pending' => false,
                'message' => 'Machine number is required',
            ], 400);
        }

        $pendingRequest = LotRequest::where('mc_no', $mcNo)
            ->where('status', 'PENDING')
            ->orderBy('requested', 'desc')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'has_pending' => true,
                'request' => $pendingRequest,
            ]);
        }

        return response()->json([
            'has_pending' => false,
        ]);
    }

    /**
     * Create a new lot request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mc_no' => 'required|string',
            'line' => 'required|string',
            'area' => 'required|string',
            'requestor' => 'required|string',
            'request_model' => 'nullable|string',
            'lot_no' => 'nullable|string',
            'model' => 'nullable|string',
            'quantity' => 'nullable|integer',
            'lipas' => 'nullable|string|in:Y,N',
            'lot_tat' => 'nullable|string',
            'lot_location' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        try {
            $lotRequest = LotRequest::create([
                'request_no' => LotRequest::generateRequestNo(),
                'mc_no' => $validated['mc_no'],
                'line' => $validated['line'],
                'area' => $validated['area'],
                'requestor' => $validated['requestor'],
                'request_model' => $validated['request_model'] ?? null,
                'lot_no' => $validated['lot_no'] ?? null,
                'model' => $validated['model'] ?? null,
                'quantity' => $validated['quantity'] ?? null,
                'lipas' => $validated['lipas'],
                'lot_tat' => $validated['lot_tat'] ?? null,
                'lot_location' => $validated['lot_location'] ?? null,
                'requested' => now(),
                'status' => 'PENDING',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lot request created successfully',
                'data' => $lotRequest,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating lot request: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lot request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
