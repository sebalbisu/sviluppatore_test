<?php

namespace App\Console\Commands\BackgroundJob;

use Illuminate\Console\Command;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\BackgroundJobService;
use App\Services\ClassMethodNotFoundException;
use Illuminate\Support\Facades\Log;

class RunJob extends Command
{
    protected $signature = 'background-jobs:run-job {jobId} {--identifier=sys_identifier}';
    protected $description = 'Run a specific background job';

    protected $service;

    public function __construct(BackgroundJobService $service)
    {
        parent::__construct();
        $this->service = $service;
        Log::setDefaultDriver('job_manager_stack');
    }

    public function handle()
    {
        $jobId = $this->argument('jobId');
        $job = BackgroundJob::find($jobId);

        if (!$job) {
            $this->error("Job not found: $jobId");
            return;
        }

        $pid = getmypid();
        $updated = $job->update([
            'started_at' => now(),
            'status' => 'running',
            'pid' => $pid,
        ]);
        if (!$updated) {
            Log::info("Job {$job->id} was already dispatched.");
            return;
        }

        $this->service->run($job);
    }
}
