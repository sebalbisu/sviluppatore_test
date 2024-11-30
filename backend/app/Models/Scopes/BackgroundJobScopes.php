<?php

namespace App\Models\Scopes;

use App\Enums\JobPriority;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Builder;

trait BackgroundJobScopes
{
    public function scopeRunningJobs(Builder $query)
    {
        return $query->where('status', JobStatus::RUNNING);
    }

    public function scopeAvaiableJobs(Builder $query)
    {
        return $query->where('status', JobStatus::PENDING)
            ->where('available_at', '<=', now());
    }

    public function scopeCriticalPriority(Builder $query)
    {
        return $query->where('priority', JobPriority::CRITICAL);
    }

    public function scopeHighPriority(Builder $query)
    {
        return $query->where('priority', JobPriority::HIGH);
    }

    public function scopeMediumPriority(Builder $query)
    {
        return $query->where('priority', JobPriority::MEDIUM);
    }

    public function scopeLowPriority(Builder $query)
    {
        return $query->where('priority', JobPriority::LOW);
    }
}
