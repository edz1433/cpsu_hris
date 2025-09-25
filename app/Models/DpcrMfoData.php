<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DpcrMfoData extends Model
{
    use HasFactory;
    protected $fillable = [
        'opcr_id', 'dpcr_mfo_id', 'opcr_mfo_data_id', 'user_id', 'mfo', 'target', 'measure', 'link_source', 'in_support', 'report_sup', 'alloted', 'div_account', 'quality', 'q_score', 'efficiency', 'e_score', 'timeliness', 't_score', 'a', 'remarks', 'category', 'opcr_by', 'order', 'lock'
    ];
}
