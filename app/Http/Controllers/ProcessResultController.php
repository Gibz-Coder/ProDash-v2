<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProcessResultController extends Controller
{
    public function update(Request $request)
    {
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
            $batchSize = 100;
            $isFirstLine = true;
            
            DB::beginTransaction();
            try {
                $lines = explode("\n", $rawData);
                
                foreach ($lines as $i => $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '') continue;
                    
                    $values = explode("\t", $trimmed);
                    
                    if ($isFirstLine || stripos($trimmed, 'lot_id') !== false) {
                        $isFirstLine = false;
                        continue;
                    }
                    
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
                        'amount' => $this->parseNumber($values[4] ?? null),
                        'auto_yn' => $values[5] ?? null,
                        'work_type' => $values[6] ?? null,
                        'eqp_line' => $values[7] ?? null,
                        'derive' => $values[8] ?? null,
                        'week_num' => $this->parseInt($values[9] ?? null),
                        'Date' => $this->parseDate($values[10] ?? null),
                        'lipas_yn' => $values[11] ?? null,
                        'cut_off' => $values[12] ?? null,
                        'stagnant_tat' => $this->parseNumber($values[13] ?? null),
                        'worktype_2' => $values[14] ?? null,
                        'updated_by' => $updatedBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    if (count($batch) >= $batchSize) {
                        DB::table('process_result')->insert($batch);
                        $totalInserted += count($batch);
                        $batch = [];
                        unset($lines[$i]);
                    }
                }
                
                if (!empty($batch)) {
                    DB::table('process_result')->insert($batch);
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

            Log::info('Process Result data added', [
                'rows_inserted' => $totalInserted,
                'rows_skipped' => $skippedRows,
                'updated_by' => $updatedBy
            ]);

            return back()->with('success', 'Process Result data added successfully!');

        } catch (\Exception $e) {
            Log::error('Process Result update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['raw_data' => 'Failed to update data: ' . $e->getMessage()]);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'lot_id', 'model_15', 'lot_qty', 'amount', 'auto_yn', 'work_type', 
            'eqp_line', 'derive', 'week_num', 'Date', 'lipas_yn', 'cut_off', 
            'stagnant_tat', 'worktype_2'
        ];

        $sampleData = [
            ['AL5SP1W', 'CL03A225MQ3OSNB', '182,646', '350,132', 'Y', 'Normal', 'LINE-01', 'N', '45', '11/1/2025', 'Y', 'Cut1', '126.57', 'Normal'],
            ['AL5SP1Y', 'CL03A225MQ3OSNB', '236,579', '453,522', 'Y', 'Normal', 'LINE-02', 'N', '45', '11/1/2025', 'Y', 'Cut1', '122.61', 'Normal'],
        ];

        $content = implode("\t", $headers) . "\n";
        foreach ($sampleData as $row) {
            $content .= implode("\t", $row) . "\n";
        }

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="process_result_template.txt"');
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
