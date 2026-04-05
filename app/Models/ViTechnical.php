<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ViTechnical extends Model
{
    protected $table = 'vi_technical';

    protected $fillable = [
        'lot_id',
        'model',
        'lot_qty',
        'lipas_yn',
        'work_type',
        'inspection_times',
        'inspection_spl',
        'defect_code',
        'defect_details',
        'technical_start_at',
        'technical_completed_at',
        'eqp_number',
        'eqp_maker',
        'technical_result',
        'remarks',
        'created_by',
        'updated_by',
        'total_tat',
    ];

    protected $casts = [
        'lot_qty'                => 'integer',
        'inspection_times'       => 'integer',
        'inspection_spl'         => 'integer',
        'total_tat'              => 'integer',
        'technical_start_at'     => 'datetime',
        'technical_completed_at' => 'datetime',
        'defect_details'         => 'array',
    ];
}
