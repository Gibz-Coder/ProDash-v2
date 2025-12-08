<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Full system access with all permissions',
                'permissions' => [
                    'Employees Read',
                    'Employees Create',
                    'Employees Update',
                    'Employees Delete',
                    'Employees Manage',
                    'Roles Manage',
                    'Reports View',
                    'Reports Generate',
                    'Settings Manage',
                    'Audit View',
                    'Departments Manage',
                    'Positions Manage'
                ]
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Management access with employee and department management permissions',
                'permissions' => [
                    'Employees Read',
                    'Employees Create',
                    'Employees Update',
                    'Employees Manage',
                    'Roles Manage',
                    'Reports View',
                    'Reports Generate',
                    'Departments Manage',
                    'Positions Manage'
                ]
            ],
            [
                'name' => 'Moderator',
                'slug' => 'moderator',
                'description' => 'Moderation access with employee management and reporting permissions',
                'permissions' => [
                    'Employees Read',
                    'Employees Update',
                    'Employees Manage',
                    'Reports View',
                    'Audit View'
                ]
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Basic employee access with read-only permissions',
                'permissions' => [
                    'Employees Read'
                ]
            ],
            [
                'name' => 'Super User',
                'slug' => 'super-user',
                'description' => 'Advanced user access with extended permissions',
                'permissions' => [
                    'Employees Create',
                    'Employees Update',
                    'Employees Read',
                    'Employees Delete',
                    'Employees Manage',
                    'Reports View',
                    'Audit View',
                    'Departments Manage',
                    'Reports Generate',
                    'Positions Manage'
                ]
            ]
        ];

        foreach ($roles as $role) {
            \App\Models\Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
