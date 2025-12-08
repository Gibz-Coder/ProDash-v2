<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for faster insertion
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');
        }
        
        // Truncate the users table
        DB::table('users')->truncate();
        
        // Load all 246 users from the complete data file
        $usersDataFile = __DIR__ . '/users_data_complete.php';
        
        if (file_exists($usersDataFile)) {
            $users = require $usersDataFile;
            $this->command->info("Loading all users from SQL dump (" . count($users) . " users)");
        } else {
            $this->command->error("Complete users data file not found: {$usersDataFile}");
            return;
        }

        // Insert users in batches for better performance
        foreach (array_chunk($users, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }
        
        // Re-enable foreign key checks
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=ON;');
        }
        
        $this->command->info('Visual Dashboard users imported successfully!');
    }
}
