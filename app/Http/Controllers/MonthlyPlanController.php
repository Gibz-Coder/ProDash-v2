<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MonthlyPlanController extends Controller
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
                    
                    if ($isFirstLine || stripos($trimmed, 'model_15') !== false) {
                        $isFirstLine = false;
                        continue;
                    }
                    
                    if (count($values) < 8 || empty($values[0])) {
                        $skippedRows++;
                        continue;
                    }
                    
                    $values = array_map('trim', $values);
                    
                    $batch[] = [
                        'model_15' => $values[0] ?? null,
                        'lot_size' => $values[1] ?? null,
                        'orig_lipas' => $values[2] ?? null,
                        'vi_lipas' => $values[3] ?? null,
                        'volpas_plan' => $this->parseNumber($values[4] ?? null),
                        'lipas_plan' => $this->parseNumber($values[5] ?? null),
                        'vi_short' => $this->parseNumber($values[6] ?? null),
                        'oi_short' => $this->parseNumber($values[7] ?? null),
                        'week_num' => $this->parseInt($values[8] ?? null),
                        'Date' => $this->parseDate($values[9] ?? null),
                        'updated_by' => $updatedBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    
                    if (count($batch) >= $batchSize) {
                        DB::table('monthly_plan')->insert($batch);
                        $totalInserted += count($batch);
                        $batch = [];
                        unset($lines[$i]);
                    }
                }
                
                if (!empty($batch)) {
                    DB::table('monthly_plan')->insert($batch);
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

            Log::info('Monthly Plan data added', [
                'rows_inserted' => $totalInserted,
                'rows_skipped' => $skippedRows,
                'updated_by' => $updatedBy
            ]);

            return back()->with('success', 'Monthly Plan data added successfully!');

        } catch (\Exception $e) {
            Log::error('Monthly Plan update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['raw_data' => 'Failed to update data: ' . $e->getMessage()]);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'model_15', 'lot_size', 'orig_lipas', 'vi_lipas', 'volpas_plan', 
            'lipas_plan', 'vi_short', 'oi_short', 'week_num', 'Date'
        ];

        $sampleData = [
            ['CL03A225MQ3OSNB', '0603', '10000', '8000', '9000', '9500', '1000', '500', '45', '11/1/2025'],
            ['CL03A225MQ3OSNB', '0805', '15000', '12000', '13000', '14000', '2000', '1000', '45', '11/1/2025'],
        ];

        $content = implode("\t", $headers) . "\n";
        foreach ($sampleData as $row) {
            $content .= implode("\t", $row) . "\n";
        }

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="monthly_plan_template.txt"');
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
