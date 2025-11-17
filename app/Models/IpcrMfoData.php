<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpcrMfoData extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'ipcr_mfo_id', 
        'dpcr_mfo_data_id', 
        'user_id', 
        'mfo', 
        'target', 
        'measure', 
        'in_support', 
        'report_sup', 
        'alloted',
        'div_account',
        'quality',
        'q_score',
        'efficiency',
        'e_score',
        'timeliness',
        't_score',
        'average',
        'remarks',
        'category',
        'dpcr_by',
        'order',
        'lock'
    ];
}
