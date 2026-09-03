<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'maintenance' => 'boolean',
    ];

    protected $fillable = [
        'hr',
        'suc_pres',
        'vpaa',
        'vpaf',
        'dtr_acct',
        'hr_kiosk',
        'hrk_pw',
        'sync_backups',
        'te_rstrct_lvl',
        'records_office_email',
        'job_portal_email',
        'maintenance',
    ];

    public static function maintenanceModeEnabled(): bool
    {
        return (bool) static::query()->value('maintenance');
    }
}
