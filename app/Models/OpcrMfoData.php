<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcrMfoData extends Model
{
    use HasFactory;
    protected $fillable = [
        'opcr_mfo_id', 'mfo', 'target', 'measure', 'link_source', 'in_support', 'report_sup', 'alloted', 'div_account', 'quality', 'q_score', 'efficiency', 'e_score', 'timeliness', 't_score', 'a', 'remarks', 'category', 'opcr_by', 'order', 'user_id'
    ];
}
