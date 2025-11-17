<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpcrMfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'ipcr_id', 'dpcr_id', 'mfo', 'percent', 'functions', 'count'
    ];
}
