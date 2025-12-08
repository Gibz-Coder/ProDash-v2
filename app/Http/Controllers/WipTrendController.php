<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class WipTrendController extends Controller
{
    public function update(Request $request)
    {
        // Increase execution time and memory for large imports
        set_time_limit(300);
        ini_set('memory_limit', '512M');
        
        $request->validate([
            'raw_data' => 'required|string',
        ]);

        try {
            $rawData = $request->input('raw_data');
            $updatedBy = Auth::user()->name ?? 'System';
            $now = now();
            
            $totalInserted = 0;
            $skippedRows = 0;
            $batch = [];
            $batchSize = 100; // Smaller batch size to reduce memory
            $isFirstLine = true;
            
            DB::beginTransaction();
            try {
                // Process line by line to minimize memory usage
                $lines = explode("\n", $rawData);
                
                foreach ($lines as $i => $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '') continue;
                    
                    $values = explode("\t", $trimmed);
                    
                    // Skip header
                    if ($isFirstLine || stripos($trimmed, 'lot_id') !== false) {
                        $isFirstLine = false;
                        continue;
                    }
                    
                    // Skip rows without enough columns or empty lot_id
                    if (count($values) < 10 || empty($values[0])) {
                        $skippedRows++;
                        continue;
                    }
                    
                    $values = array_map('trim', $values);
                    
                    $batch[] = [
                        'lot_id' => $values[0] ?? null,
                        'model_15' => $values[1] ?? null,
                        'lot_size' => $values[2] ?? null,
                        'lot_qty' => $this->parseNumber($values[3] ?? null),
                        'auto_yn' => $values[4] ?? null,
                        'work_type' => $values[5] ?? null,
                        'hold' => $values[6] ?? null,
                        'derive' => $values[7] ?? null,
                        'wip_breakdown' => $values[8] ?? null,
                        'week_num' => $this->parseInt($values[9] ?? null),
                        'Date' => $this->parseDate($values[10] ?? null),
                        'lipas_yn' => $values[11] ?? null,
                        'amount' => $this->parseNumber($values[12] ?? null),
                        'wip_status' => $values[13] ?? null,
                        'stagnant_tat' => $this->parseNumber($values[14] ?? null),
                        'worktype_2' => $values[15] ?? null,
                        'updated_by' => $updatedBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    // Insert batch when it reaches the batch size
                    if (count($batch) >= $batchSize) {
                        DB::table('wip_trend')->insert($batch);
                        $totalInserted += count($batch);
                        $batch = []; // Clear batch to free memory
                        unset($lines[$i]); // Free memory for processed line
                    }
                }
                
                // Insert remaining batch
                if (!empty($batch)) {
                    DB::table('wip_trend')->insert($batch);
                    $totalInserted += count($batch);
                }
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            if ($totalInserted === 0) {
                return back()->withErrors(['raw_data' => 'No valid data rows found.']);
            }

            Log::info('WIP Trend data added', [
                'rows_inserted' => $totalInserted,
                'rows_skipped' => $skippedRows,
                'updated_by' => $updatedBy
            ]);

            return back()->with('success', 'WIP Trend data added successfully!');

        } catch (\Exception $e) {
            Log::error('WIP Trend update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['raw_data' => 'Failed to update data: ' . $e->getMessage()]);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'lot_id', 'model_15', 'lot_qty', 'auto_yn', 'work_type', 
            'hold', 'derive', 'wip_breakdown', 'week_num', 'Date', 
            'lipas_yn', 'amount', 'wip_status', 'stagnant_tat', 'worktype_2'
        ];

        $sampleData = [
            ['AL5SP1W', 'CL03A225MQ3OSNB', '182,646', 'Y', 'Normal', 'Hold', 'N', 'MC WIP', '45', '11/1/2025', 'Y', '350,132', 'Receiving', '126.57', 'Normal'],
            ['AL5SP1Y', 'CL03A225MQ3OSNB', '236,579', 'Y', 'Normal', 'Hold', 'N', 'MC WIP', '45', '11/1/2025', 'Y', '453,522', 'Receiving', '122.61', 'Normal'],
        ];

        $content = implode("\t", $headers) . "\n";
        foreach ($sampleData as $row) {
            $content .= implode("\t", $row) . "\n";
        }

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="wip_trend_template.txt"');
    }

    private function parseNumber($value)
    {
        if (empty($value)) return null;
        return (float) str_replace(',', '', $value);
    }

    private function parseInt($value)
    {
        if (empty($value)) return null;
        return (int) str_replace(',', '', $value);
    }

    private function parseDate($value)
    {
        if (empty($value)) return null;
        
        try {
            $date = \DateTime::createFromFormat('n/j/Y', $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
