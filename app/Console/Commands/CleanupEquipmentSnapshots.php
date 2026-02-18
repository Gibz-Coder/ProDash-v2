<?php

namespace App\Console\Commands;

use App\Models\EquipmentSnapshot;
use Illuminate\Console\Command;

class CleanupEquipmentSnapshots extends Command
{
    protected $signature = 'equipment:cleanup-snapshots {--days=7 : Number of days to keep}';
    protected $description = 'Clean up old equipment snapshots (default: keep last 7 days)';

    public function handle(): int
    {
        $daysToKeep = (int) $this->option('days');
        $this->info("Cleaning up snapshots older than {$daysToKeep} days...");
        
        try {
            $deletedCount = EquipmentSnapshot::cleanupOldSnapshots($daysToKeep);
            $this->info("Successfully deleted {$deletedCount} old snapshot(s).");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to cleanup snapshots: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
