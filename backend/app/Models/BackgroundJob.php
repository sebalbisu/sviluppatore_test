<?php

namespace App\Models;

use App\Enums\JobPriority;
use App\Enums\JobStatus;
use App\Models\Scopes\BackgroundJobScopes;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackgroundJob extends Model
{
    use HasFactory, BackgroundJobScopes;

    protected $table = 'background_jobs';

    protected $fillable = [
        'pid',
        'classname',
        'method',
        'params',
        'status',
        'priority',
        'attempts',
        'started_at',
        'available_at',
        'retries',
        'max_retries',
        'retry_delay',
        'timeout',
    ];

    protected $casts = [
        'params' => 'array',
        'status' => JobStatus::class,
        'priority' => JobPriority::class,
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $dates = [
        'started_at',
        'available_at',
    ];

    public function isAvailable(): bool
    {
        return now()->isAfter($this->available_at);
    }

    public function getDelayAttribute(): int
    {
        return $this->isAvailable() ?
            0 :
            $this->available_at->diffInSeconds(now());
    }

    public function setDelayAttribute(int $seconds)
    {
        $availableAt = $this->attributes['available_at'] ?? Carbon::now();
        if ($availableAt < Carbon::now()) {
            $availableAt = Carbon::now();
        }
        $this->attributes['available_at'] = $availableAt->addSeconds($seconds);
    }
}
