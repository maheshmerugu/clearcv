<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'original_path',
        'optimized_path',
        'extracted_text',
        'optimized_text',
        'job_description',
        'ats_score',
        'findings',
    ];

    protected function casts(): array
    {
        return [
            'findings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
