<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'core_mfo1',
        'core_mfo2',
        'core_mfo3',
        'core_sum',
        'strategic_mfo4',
        'strategic_mfo5',
        'strat_sum',
        'support_mfo4',
        'support_mfo5',
        'support_sum',
    ];
}
