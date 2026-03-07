<?php

namespace App\Console\Commands;

use App\Models\UpdateWip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportMesWipFromFile extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:mes-wip 
                            {--folder= : The folder to monitor for Excel files}
                            {--archive : Archive processed files instead of deleting}';

    /**
     * The console command description.
     */
    protected $description = 'Import WIP data from Excel files in monitored folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $monitorFolder = $this->option('folder') ?? storage_path('app/mes_imports');
        $archiveFolder = storage_path('app/mes_imports/archive');
        $shouldArchive = $this->option('archive');

        // Ensure folders exist
        if (!File::exists($monitorFolder)) {
            File::makeDirectory($monitorFolder, 0755, true);
            $this->info("Created monitor folder: {$monitorFolder}");
        }

        if ($shouldArchive && !File::exists($archiveFolder)) {
            File::makeDirectory($archiveFolder, 0755, true);
        }

        // Find Excel files
        $files = File::glob($monitorFolder . '/*.{xlsx,xls,csv}', GLOB_BRACE);

        if (empty($files)) {
            $this->info('No files found to process.');
            return 0;
        }

        $this->info('Found ' . count($files) . ' file(s) to process.');

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $this->info("Processing: {$fileName}");

            try {
                $recordCount = $this->processFile($filePath);
                
                $this->info("✓ Successfully imported {$recordCount} records from {$fileName}");
                Log::info("MES WIP Import Success", [
                    'file' => $fileName,
                    'records' => $recordCount,
                ]);

                // Archive or delete the file
                if ($shouldArchive) {
                    $archivePath = $archiveFolder . '/' . date('Ymd_His') . '_' . $fileName;
                    File::move($filePath, $archivePath);
                    $this->info("Archived to: {$archivePath}");
                } else {
                    File::delete($filePath);
                    $this->info("Deleted processed file.");
                }

            } catch (\Exception $e) {
                $this->error("✗ Failed to process {$fileName}: " . $e->getMessage());
                Log::error("MES WIP Import Failed", [
                    'file' => $fileName,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Move failed file to error folder
                $errorFolder = storage_path('app/mes_imports/errors');
                if (!File::exists($errorFolder)) {
                    File::makeDirectory($errorFolder, 0755, true);
                }
                $errorPath = $errorFolder . '/' . date('Ymd_His') . '_ERROR_' . $fileName;
                File::move($filePath, $errorPath);
                $this->warn("Moved to error folder: {$errorPath}");
            }
        }

        return 0;
    }

    /**
     * Process a single Excel file and import to database.
     */
    private function processFile(string $filePath): int
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            return $this->processCsvFile($filePath);
        } else {
            return $this->processExcelFile($filePath);
        }
    }

    /**
     * Process Excel file using PhpSpreadsheet.
     */
    private function processExcelFile(string $filePath): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();

        if (empty($rows)) {
            throw new \Exception('Excel file is empty');
        }

        return $this->importData($rows);
    }

    /**
     * Process CSV file.
     */
    private function processCsvFile(string $filePath): int
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            throw new \Exception('CSV file is empty');
        }

        return $this->importData($rows);
    }

    /**
     * Import data array to database.
     */
    private function importData(array $rows): int
    {
        // Extract header
        $header = array_shift($rows);
        
        // Normalize header keys
        $header = array_map(function ($h) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h));
        }, $header);

        // Clear existing data
        DB::table('updatewip')->delete();
        DB::statement('ALTER TABLE updatewip AUTO_INCREMENT = 1');

        DB::beginTransaction();

        $count = 0;
        foreach ($rows as $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Map row to associative array
            $data = [];
            foreach ($header as $idx => $key) {
                $val = $row[$idx] ?? null;
                
                // Clean up values
                if (is_string($val)) {
                    $val = trim($val);
                    if ($val === '' || $val === 'NULL') {
                        $val = null;
                    }
                }
                
                $data[$key] = $val;
            }

            // Build payload
            $payload = [
                'lot_id'         => $data['lot_id'] ?? null,
                'model_15'       => $data['model_15'] ?? null,
                'lot_size'       => $data['lot_size'] ?? null,
                'lot_qty'        => isset($data['lot_qty']) ? (int)str_replace(',', '', trim((string)$data['lot_qty'])) : null,
                'stagnant_tat'   => isset($data['stagnant_tat']) ? trim((string)$data['stagnant_tat']) : null,
                'qty_class'      => $data['qty_class'] ?? null,
                'work_type'      => $data['work_type'] ?? null,
                'wip_status'     => $data['wip_status'] ?? null,
                'lot_status'     => $data['lot_status'] ?? null,
                'hold'           => $data['hold'] ?? null,
                'auto_yn'        => $data['auto_yn'] ?? null,
                'lipas_yn'       => $data['lipas_yn'] ?? null,
                'eqp_type'       => $data['eqp_type'] ?? null,
                'eqp_class'      => $data['eqp_class'] ?? null,
                'lot_location'   => $data['lot_location'] ?? null,
                'lot_code'       => $data['lot_code'] ?? null,
                'modified_by'    => 'MES_AUTO_IMPORT',
            ];

            // Skip if no lot_id
            if (empty($payload['lot_id'])) {
                continue;
            }

            UpdateWip::create($payload);
            $count++;
        }

        DB::commit();

        return $count;
    }
}
