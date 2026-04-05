<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class QcOk extends Model
{
    protected $table = 'qc_ok';

    protected $fillable = [
        'lot_id',
        'model',
        'lot_qty',
        'lipas_yn',
        'work_type',
        'inspection_times',
        'inspection_spl',
        'inspection_result',
        'analysis_result',
        'technical_result',
        'pending',
        'remarks',
        'output_status',
        'lot_completed_at',
        'created_by',
        'updated_by',
        'total_tat',
    ];

    protected $casts = [
        'lot_qty'          => 'integer',
        'inspection_times' => 'integer',
        'inspection_spl'   => 'integer',
        'total_tat'        => 'integer',
    ];
}
