<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocuFolder extends Model
{
    use HasFactory;

    protected $fillable = ['folder_name', 'connected_folder', 'folder_category', 'folder_path', 'office_access'];
}
