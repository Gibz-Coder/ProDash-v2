<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class CleanupRolePermissions extends Command
{
    protected $signature = 'roles:cleanup-permissions';
    protected $description = 'Clean up old unused permissions from all roles and keep only valid ones';

    // Valid permissions for ProDash V2
    private array $validPermissions = [
        'Employees Manage',
        'Roles Manage',
        'Settings Manage',
        'Data Entry Access',
        'MC Allocation Edit',
        'MC Allocation Delete',
        'Endtime Manage',
        'Endtime Delete',
    ];

    public function handle()
    {
        $this->info('Starting permission cleanup...');
        
        $roles = Role::all();
        
        foreach ($roles as $role) {
            $oldPermissions = $role->permissions ?? [];
            
            // Filter to keep only valid permissions
            $newPermissions = array_values(array_filter($oldPermissions, function ($permission) {
                return in_array($permission, $this->validPermissions);
            }));
            
            // Update the role
            $role->permissions = $newPermissions;
            $role->save();
            
            $removed = array_diff($oldPermissions, $newPermissions);
            
            $this->line("Role: {$role->name}");
            $this->line("  - Old permissions: " . count($oldPermissions));
            $this->line("  - New permissions: " . count($newPermissions));
            
            if (count($removed) > 0) {
                $this->line("  - Removed: " . implode(', ', $removed));
            }
            $this->newLine();
        }
        
        $this->info('Permission cleanup completed!');
        
        return Command::SUCCESS;
    }
}
