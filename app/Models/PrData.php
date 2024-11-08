<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrData extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_id', 'mfo', 'target', 'in_support', 'report_sup', 'alloted', 'div_account', 'q', 'e', 't', 'qrate', 'erate', 'trate', 'a', 'remarks', 'attachment'
    ];
}
