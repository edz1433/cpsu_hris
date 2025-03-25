<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opcr extends Model
{
    use HasFactory;

    protected $fillable = ['folder_id', 'off_id', 'pr_number', 'mfo', 'percent', 'cat'];
}
