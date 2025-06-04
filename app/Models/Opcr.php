<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opcr extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'folder_id', 'pr_number', 'mfo', 'details', 'percent', 'year'];
}
