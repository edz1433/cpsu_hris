<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dpipop extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'off_id', 'pr_number', 'folder_id', 'mfo', 'percent'];
}
