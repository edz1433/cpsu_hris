<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeEvaluate extends Model
{
    protected $fillable = [
        'ete_id',
        'application_id',
        'jid',
        'evaluator_id',
        'position',
        'education_score',
        'training_score',
        'experience_score',
        'experience_year_ratings',
        'total_score',
        'remarks',
    ];

    protected $casts = [
        'experience_year_ratings' => 'array',
    ];

    public function eteEvaluation()
    {
        return $this->belongsTo(EteEvaluation::class, 'ete_id');
    }

    public function application()
    {
        return $this->belongsTo(Application::class, 'application_id');
    }

    public function job()
    {
        return $this->belongsTo(JobHiring::class, 'jid');
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }
}