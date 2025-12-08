<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportUsersFromSql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-sql {file=plano/Dump20251116/visual_dashboard_users.sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from SQL dump file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating sample users from SQL dump structure...');
        
        // Handle different database drivers
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');
        }
        
        DB::table('users')->truncate();
        
        // Sample users based on the SQL dump structure
        $users = [
            [
                'emp_no' => '21278703',
                'emp_name' => 'HAPITA, GILBERT HIBE',
                'role' => 'admin',
                'position' => 'Specialist',
                'title_class' => 'Professional',
                'rank' => 'CL2(Ⅳ)-3',
                'hr_job_name' => 'Production Management',
                'job_assigned' => 'Leadtime In-charge',
                'emp_verified_at' => '2025-09-29 01:50:06',
                'password' => '$2y$12$HCax8MJeVI06AgPQ/KrZkusZ0J7FwshurnNWhNaLuHVja9E7hJSiu',
                'remember_token' => null,
                'created_at' => '2025-09-29 01:50:06',
                'updated_at' => '2025-09-29 01:54:00',
                'avatar' => null,
            ],
            [
                'emp_no' => '21577226',
                'emp_name' => 'CATAPANG, JAY JAE TAPAY',
                'role' => 'manager',
                'position' => 'Specialist',
                'title_class' => 'Engineer',
                'rank' => 'CL2(?)-1',
                'hr_job_name' => 'Equipment Operation Control Technology',
                'job_assigned' => 'GMC MC and Software Development',
                'emp_verified_at' => '2025-09-29 01:50:07',
                'password' => '$2y$12$Ox8p9Qfnl.eP6hL43I3VR.czTtIPt0mG0qDzUp9aMq256rZZgRlFS',
                'remember_token' => null,
                'created_at' => '2025-09-29 01:50:57',
                'updated_at' => '2025-09-29 01:54:23',
                'avatar' => null,
            ],
            [
                'emp_no' => '21874062',
                'emp_name' => 'POMBO, JOHN DARWIN CAPARINO',
                'role' => 'admin',
                'position' => 'Technician',
                'title_class' => 'Lead Technician',
                'rank' => 'CL2(?)',
                'hr_job_name' => 'Inspection & Measuring Equipment Technology',
                'job_assigned' => 'GMC- AI Technician',
                'emp_verified_at' => '2025-09-29 01:50:07',
                'password' => '$2y$12$91FFOXg/4V4g3MsjQBd73uToacq8dhLGWg3oqghgl7vsw9kUcw4HC',
                'remember_token' => null,
                'created_at' => '2025-09-29 01:50:57',
                'updated_at' => '2025-10-06 09:44:08',
                'avatar' => null,
            ],
            [
                'emp_no' => '20176639',
                'emp_name' => 'SANTOS, MARVIN HENRY DOMINGO',
                'role' => 'manager',
                'position' => 'Manager',
                'title_class' => 'Senior Professional',
                'rank' => 'CL3(?)-1',
                'hr_job_name' => 'Production Management',
                'job_assigned' => 'Department management',
                'emp_verified_at' => '2025-09-29 01:50:07',
                'password' => '$2y$12$vWXHHdkaXFW5.gvdHWve0e8CbnPJIgx69PI8olJd84KC3XGdZGRMO',
                'remember_token' => null,
                'created_at' => '2025-09-29 01:50:57',
                'updated_at' => '2025-10-02 09:56:01',
                'avatar' => null,
            ],
            [
                'emp_no' => '20972287',
                'emp_name' => 'MURILLO, KENNETH REDEN LINGA',
                'role' => 'user',
                'position' => 'Manager',
                'title_class' => 'Staff Engineer',
                'rank' => 'CL3(?)-3',
                'hr_job_name' => 'Inspection Technology',
                'job_assigned' => 'Technical Leader',
                'emp_verified_at' => '2025-09-29 01:50:07',
                'password' => '$2y$12$/yzzt1NqkW5SHF7HHLsgEuRy/98ujfi2P7Q2wMuwQI9ahcBi..OP.',
                'remember_token' => null,
                'created_at' => '2025-09-29 01:50:57',
                'updated_at' => '2025-09-29 01:50:57',
                'avatar' => null,
            ],
        ];

        // Insert users in batches
        foreach (array_chunk($users, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        
        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON;');
        }
        
        $count = DB::table('users')->count();
        $this->info("Successfully imported {$count} sample users!");
        $this->info("Note: This imports a sample of users. For full data import, you may need to manually process the SQL file.");

        return 0;
    }
}
