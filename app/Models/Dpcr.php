<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dpcr extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'opcr_id', 'folder_id', 'pr_number', 'op_pr_number', 'mfo', 'percent', 'year'
    ];
}
