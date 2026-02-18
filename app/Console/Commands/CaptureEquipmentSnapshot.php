<?php

namespace App\Console\Commands;

use App\Models\EquipmentSnapshot;
use Illuminate\Console\Command;

class CaptureEquipmentSnapshot extends Command
{
    protected $signature = 'equipment:capture-snapshot';
    protected $description = 'Capture current equipment snapshot for trend tracking (every 30 minutes)';

    public function handle(): int
    {
        $this->info('Capturing equipment snapshot...');
        
        try {
            EquipmentSnapshot::captureSnapshot();
            $this->info('Snapshot captured successfully at ' . now()->format('Y-m-d H:i:s'));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to capture snapshot: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
