<?php

namespace App\Http\Controllers;

use App\Models\UpdateWip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateWipController extends Controller
{
    /**
     * Process the pasted Excel data and update the updatewip table.
     */
    public function store(Request $request)
    {
        $request->validate([
            'raw_data' => 'required|string',
        ]);

        try {
            // Clear the table first and reset auto-increment
            DB::table('updatewip')->delete();
            DB::statement('ALTER TABLE updatewip AUTO_INCREMENT = 1');
            
            DB::beginTransaction();
            
            $rawData = $request->input('raw_data');
            
            // Normalize line endings and split into lines
            $rawData = str_replace(["\r\n", "\r"], "\n", $rawData);
            $lines = explode("\n", $rawData);
            $lines = array_filter($lines, function($line) {
                return trim($line) !== '';
            });
            
            if (empty($lines)) {
                throw new \Exception('No data found in the pasted content.');
            }
            
            // Parse header from first line
            $headerLine = array_shift($lines);
            $header = str_getcsv($headerLine, "\t"); // Using tab delimiter for Excel paste
            
            // Normalize header keys
            $header = array_map(function ($h) {
                return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h));
            }, $header);
            
            $count = 0;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }
                
                // Parse data from line using tab delimiter
                $row = str_getcsv($line, "\t");
                
                // Map row to associative array by header
                $data = [];
                foreach ($header as $idx => $key) {
                    $val = $row[$idx] ?? null;
                    // Clean up values
                    if (is_string($val)) {
                        $val = trim($val);
                        // Remove surrounding quotes if present
                        if (strlen($val) >= 2 && $val[0] === '"' && $val[-1] === '"') {
                            $val = substr($val, 1, -1);
                        }
                    }
                    $data[$key] = $val === '' ? null : $val;
                }
                
                // Build payload matching our updatewip table columns
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
                    'modified_by'    => Auth::user()->name ?? Auth::user()->email,
                ];
                
                // Skip if no lot_id
                if (empty($payload['lot_id'])) {
                    continue;
                }
                
                UpdateWip::create($payload);
                $count++;
            }
            
            DB::commit();
            
            return back()->with('success', "Successfully updated WIP data with {$count} records.");
                
        } catch (\Exception $e) {
            DB::rollback();
            
            return back()
                ->withErrors(['raw_data' => 'Error processing data: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Download the updatewip template Excel file.
     */
    public function downloadTemplate()
    {
        $filePath = public_path('storage/documents/updatewip.xlsx');
        
        if (!file_exists($filePath)) {
            abort(404, 'Template file not found.');
        }
        
        return response()->download($filePath, 'updatewip_template.xlsx');
    }
}
