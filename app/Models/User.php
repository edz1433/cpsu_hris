<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'gender',
        'campus_id',
        'username',
        'verification_code',
        'password',
        'role',
        'access',
        'emp_ID',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * Pages a user can be granted, keyed by name: [position in `access`, label].
     * `access` is a comma list of 0/1 flags in this order; never reorder it.
     */
    public const PAGE_ACCESS = [
        'employees' => [0, 'Employees'],
        'offices' => [1, 'Offices'],
        'payslip' => [2, 'Payslip'],
        'events' => [3, 'Events'],
        'dtr' => [4, 'DTR'],
        'spms' => [5, 'SPMS'],
        'settings' => [6, 'Settings'],
        'leave' => [7, 'Leave'],
        'kiosk' => [8, 'Kiosk'],
        'contracts' => [9, 'Contracts'],
    ];

    public function hasRole($role)
    {
        return $this->role === $role;
    }

    /**
     * Whether this account may open a page. Administrators can open everything;
     * everyone else only gets the pages ticked for them in User Management.
     */
    public function canAccessPage(string $page): bool
    {
        if ($this->role === 'Administrator') {
            return true;
        }

        $index = self::PAGE_ACCESS[$page][0] ?? null;

        // System settings (incl. maintenance mode, which blocks every login) stay
        // with Administrators. Many HR accounts have this flag set from before it
        // did anything, so honouring it would quietly hand them the switch.
        if ($index === null || $page === 'settings') {
            return false;
        }

        $flags = array_map('trim', explode(',', (string) $this->access));

        // Saved before Contracts was a permission: keep the role rule it had then
        // until the account is saved again in User Management.
        if (!array_key_exists($index, $flags)) {
            return $page === 'contracts' && $this->role === 'HR Administrator';
        }

        return $flags[$index] === '1';
    }
}
