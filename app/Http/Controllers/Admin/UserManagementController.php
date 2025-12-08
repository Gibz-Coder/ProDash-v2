<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Get all valid role slugs from the roles table.
     */
    private function getValidRoles(): array
    {
        return Role::pluck('slug')->toArray();
    }
    /**
     * Display a listing of users with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('emp_name', 'like', "%{$search}%")
                  ->orWhere('emp_no', 'like', "%{$search}%");
            });
        }

        // Role filter (case-insensitive)
        if ($request->has('role') && $request->role) {
            $query->whereRaw('LOWER(role) = ?', [strtolower($request->role)]);
        }

        $users = $query->orderBy('created_at', 'desc')
                       ->paginate(15);

        // Normalize role to lowercase in the response
        $users->getCollection()->transform(function ($user) {
            $user->role = strtolower($user->role);
            return $user;
        });

        // Get statistics for all users (not filtered)
        $stats = [
            'total' => User::count(),
        ];

        // Get all roles from the roles table
        $allRoles = Role::pluck('slug');

        // Count users for each role (including 0 counts) - case-insensitive
        foreach ($allRoles as $roleSlug) {
            $count = User::whereRaw('LOWER(role) = ?', [strtolower($roleSlug)])->count();
            
            // Convert role slug to plural form for stats key
            $statsKey = $roleSlug === 'user' ? 'regular_users' : $roleSlug . 's';
            // Handle special cases like "super-user" -> "super_users"
            $statsKey = str_replace('-', '_', $statsKey);
            
            $stats[$statsKey] = $count;
        }

        // Add statistics to the response
        $response = $users->toArray();
        $response['stats'] = $stats;

        return response()->json($response);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'emp_name' => ['required', 'string', 'max:255'],
            'emp_no' => ['required', 'string', 'max:255', 'unique:users'],
            'position' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in($this->getValidRoles())],
        ]);

        // Validate admin role restriction
        if ($validated['role'] === 'admin' && $validated['emp_no'] !== '21278703') {
            return response()->json([
                'message' => 'Only employee ID 21278703 can be assigned the admin role.',
                'errors' => [
                    'role' => ['Only employee ID 21278703 can be assigned the admin role.']
                ]
            ], 422);
        }

        $user = User::create([
            'emp_name' => $validated['emp_name'],
            'emp_no' => $validated['emp_no'],
            'position' => $validated['position'],
            'password' => Hash::make($validated['password']),
            'role' => strtoupper($validated['role']), // Store as uppercase to match existing data
            'emp_verified_at' => now(),
        ]);

        // Return with lowercase role for frontend consistency
        $user->role = strtolower($user->role);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->role = strtolower($user->role);
        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'emp_name' => ['required', 'string', 'max:255'],
            'emp_no' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'position' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in($this->getValidRoles())],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Validate admin role restriction
        if ($validated['role'] === 'admin' && $validated['emp_no'] !== '21278703') {
            return response()->json([
                'message' => 'Only employee ID 21278703 can be assigned the admin role.',
                'errors' => [
                    'role' => ['Only employee ID 21278703 can be assigned the admin role.']
                ]
            ], 422);
        }

        // Prevent removing admin role from the designated admin (case-insensitive check)
        if ($user->emp_no === '21278703' && strtolower($validated['role']) !== 'admin') {
            return response()->json([
                'message' => 'Cannot change the role of the system administrator (21278703).',
                'errors' => [
                    'role' => ['Cannot change the role of the system administrator (21278703).']
                ]
            ], 422);
        }

        $user->emp_name = $validated['emp_name'];
        $user->emp_no = $validated['emp_no'];
        $user->position = $validated['position'];
        $user->role = strtoupper($validated['role']); // Store as uppercase to match existing data

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Normalize role for response
        $user->role = strtolower($user->role);

        // If the user is updating their own role, refresh the session
        if ($user->id === auth()->id()) {
            // Force logout to clear cached permissions
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Your role has been updated. Please log in again.',
                'user' => $user,
                'logout' => true, // Signal to frontend to redirect to login
            ]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Prevent deleting the system administrator
        if ($user->emp_no === '21278703') {
            return response()->json([
                'message' => 'Cannot delete the system administrator (21278703)',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Toggle user account status (active/inactive).
     */
    public function toggleStatus(User $user)
    {
        // Prevent toggling status of the system administrator
        if ($user->emp_no === '21278703') {
            return response()->json([
                'message' => 'Cannot change the status of the system administrator (21278703)',
            ], 403);
        }

        // Toggle the emp_verified_at field
        if ($user->emp_verified_at) {
            $user->emp_verified_at = null;
            $message = 'User account deactivated successfully';
        } else {
            $user->emp_verified_at = now();
            $message = 'User account activated successfully';
        }

        $user->save();

        // Normalize role for response
        $user->role = strtolower($user->role);

        return response()->json([
            'message' => $message,
            'user' => $user,
        ]);
    }
}
