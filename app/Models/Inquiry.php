<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Enquiry\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Inquiry extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'inquirable_id',
        'inquirable_type',
        'status',
        'remarks',
    ];

    // cast status to enum
    protected $casts = [
        'type' => 'string',
        'status' => Status::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inquirable(): MorphTo
    {
        return $this->morphTo();
    }
}
