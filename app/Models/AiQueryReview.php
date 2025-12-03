<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQueryReview extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'user_question', 'generated_sql',
        'risk_level', 'status', 'reviewer_id', 'reviewed_at',
        'execution_result', 'error_message', 'meta',
    ];

    protected $casts = [
        'execution_result' => 'array',
        'meta' => 'array',
        'reviewed_at' => 'datetime',
    ];
}
