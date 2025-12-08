<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateWip extends Model
{
    protected $table = 'updatewip';

    protected $fillable = [
        'lot_id',
        'model_15',
        'lot_size',
        'lot_qty',
        'stagnant_tat',
        'qty_class',
        'work_type',
        'wip_status',
        'lot_status',
        'hold',
        'auto_yn',
        'lipas_yn',
        'eqp_type',
        'eqp_class',
        'lot_location',
        'lot_code',
        'modified_by',
    ];

    protected $casts = [
        'lot_qty' => 'integer',
        'stagnant_tat' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
