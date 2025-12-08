<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleManagementController extends Controller
{
    /**
     * Display a listing of roles with pagination and filters.
     */
    public function index(Request $request)
    {
        $query = Role::query();

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $roles = $query->orderBy('created_at', 'desc')->get();

        // Manually add users count for each role
        foreach ($roles as $role) {
            $role->users_count = \App\Models\User::where('role', $role->slug)->count();
        }

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = Role::create($validated);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role,
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role)
    {
        $role->users_count = \App\Models\User::where('role', $role->slug)->count();
        return response()->json($role);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id), 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['nullable', 'array'],
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role,
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting roles that have users
        $usersCount = \App\Models\User::where('role', $role->slug)->count();
        if ($usersCount > 0) {
            return response()->json([
                'message' => 'Cannot delete role with assigned users',
            ], 403);
        }

        // Prevent deleting system roles
        if (in_array($role->slug, ['admin', 'manager', 'user'])) {
            return response()->json([
                'message' => 'Cannot delete system roles',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
