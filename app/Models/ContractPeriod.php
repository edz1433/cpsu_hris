<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractPeriod extends Model
{
    use SoftDeletes;

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';

    protected $connection = 'mysql';
    protected $table = 'contract_periods';

    protected $fillable = [
        'contract_type', 'title', 'start_date', 'end_date', 'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function contracts()
    {
        return $this->hasMany(EmployeeContract::class, 'contract_period_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function typeConfig(): array
    {
        return config('contracts.types.' . $this->contract_type, []);
    }

    public function typeLabel(): string
    {
        return $this->typeConfig()['label'] ?? $this->contract_type;
    }

    public static function suggestTitle(string $type, $start, $end): string
    {
        $label = config('contracts.types.' . $type . '.label', 'Contract');

        return $label . ' Contract: ' . \Carbon\Carbon::parse($start)->format('F j, Y') . ' – ' . \Carbon\Carbon::parse($end)->format('F j, Y');
    }

    /** Open periods of the same type whose dates overlap the given range. */
    public static function overlapping(string $type, $start, $end, ?int $exceptId = null)
    {
        return static::where('contract_type', $type)
            ->where('status', self::STATUS_OPEN)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->get();
    }
}
