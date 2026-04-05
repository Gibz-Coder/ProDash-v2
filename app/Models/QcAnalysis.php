<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QcAnalysis extends Model
{
    protected $table = 'qc_analysis';

    protected $fillable = [
        'lot_id', 'model', 'lot_qty', 'lipas_yn', 'work_type',
        'inspection_times', 'inspection_spl', 'defect_code',
        'analysis_start_at',
        'mold_image_before', 'mold_image_after', 'mold_result',
        'reli_image_before', 'reli_image_after', 'reli_result',
        'dipping_image_before', 'dipping_image_after', 'dipping_result',
        'reflow_image_before', 'reflow_image_after', 'reflow_result',
        'measure_image_before', 'measure_image_after', 'measure_result',
        'analysis_completed_at', 'analysis_result',
        'remarks', 'created_by', 'updated_by', 'total_tat',
    ];

    protected $casts = [
        'lot_qty'               => 'integer',
        'inspection_times'      => 'integer',
        'inspection_spl'        => 'integer',
        'total_tat'             => 'integer',
        'analysis_start_at'     => 'datetime',
        'analysis_completed_at' => 'datetime',
        'mold_image_before'     => 'array',
        'mold_image_after'      => 'array',
        'reli_image_before'     => 'array',
        'reli_image_after'      => 'array',
        'dipping_image_before'  => 'array',
        'dipping_image_after'   => 'array',
        'reflow_image_before'   => 'array',
        'reflow_image_after'    => 'array',
        'measure_image_before'  => 'array',
        'measure_image_after'   => 'array',
    ];
}
