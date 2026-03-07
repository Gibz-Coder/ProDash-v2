<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LotRequest extends Model
{
    protected $fillable = [
        'request_no',
        'mc_no',
        'line',
        'area',
        'requestor',
        'request_model',
        'lot_no',
        'model',
        'quantity',
        'lipas',
        'lot_tat',
        'lot_location',
        'requested',
        'completed',
        'response_time',
        'response_by',
        'status',
        'remarks',
    ];

    protected $casts = [
        'requested' => 'datetime',
        'completed' => 'datetime',
        'quantity' => 'integer',
    ];

    /**
     * Generate unique request number in format YYMMDDhhmmss
     */
    public static function generateRequestNo(): string
    {
        return now()->format('ymdHis');
    }

    /**
     * Calculate response time between requested and completed
     */
    public function calculateResponseTime(): ?string
    {
        if (!$this->requested || !$this->completed) {
            return null;
        }

        $diff = $this->requested->diff($this->completed);
        
        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d . 'd';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . 'h';
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i . 'm';
        }
        
        return !empty($parts) ? implode(' ', $parts) : '0m';
    }
}
