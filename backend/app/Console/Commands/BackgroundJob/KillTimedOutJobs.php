<?php

namespace App\Console\Commands\BackgroundJob;

use Illuminate\Console\Command;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\BackgroundJobService;
use Illuminate\Support\Facades\Log;

class KillTimedOutJobs extends Command
{
    protected $signature = 'background-jobs:kill-timeouts';
    protected $description = 'Kill timed-out background jobs';

    protected $service;

    public function __construct(BackgroundJobService $service)
    {
        parent::__construct();
        $this->service = $service;
        Log::setDefaultDriver('job_manager_stack');
    }

    public function handle()
    {
        BackgroundJob::where('status', 'running')
            ->whereNotNull('timeout')
            ->whereRaw('started_at + (timeout * interval \'1 second\') <= now()')
            ->each(function ($job) {
                $this->service->kill($job);
            });
    }
}
