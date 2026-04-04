<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QcInspection extends Model
{
    protected $table = 'qc_inspection';

    protected $fillable = [
        'lot_id',
        'model',
        'lot_qty',
        'lipas_yn',
        'work_type',
        'inspection_times',
        'inspection_spl',
        'inspected_bin',
        'inspection_result',
        'mainlot_result',
        'rr_result',
        'ly_result',
        'defect_code',
        'defect_flow',
        'analysis_start_at',
        'analysis_completed_at',
        'analysis_result',
        'technical_start_at',
        'technical_completd_at',
        'technical_result',
        'production_start_at',
        'production_completed_at',
        'production_result',
        'final_decision',
        'remarks',
        'output_status',
        'lot_completed_at',
        'created_by',
        'updated_by',
        'total_tat',
    ];

    protected $casts = [
        'lot_qty'               => 'integer',
        'inspection_times'      => 'integer',
        'inspection_spl'        => 'integer',
        'total_tat'             => 'integer',
        'analysis_start_at'     => 'datetime',
        'analysis_completed_at' => 'datetime',
        'technical_start_at'    => 'datetime',
        'technical_completd_at' => 'datetime',
        'production_start_at'      => 'datetime',
        'production_completed_at' => 'datetime',
        'lot_completed_at'      => 'datetime',
    ];
}
