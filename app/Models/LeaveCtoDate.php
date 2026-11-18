<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One date (and the hours used on it) of a CTO application.
class LeaveCtoDate extends Model
{
    protected $fillable = ['leave_application_id', 'cto_date', 'hours'];

    protected $casts = [
        'cto_date' => 'date',
        'hours' => 'float',
    ];

    public function leaveApplication()
    {
        return $this->belongsTo(LeaveApplication::class);
    }
}
