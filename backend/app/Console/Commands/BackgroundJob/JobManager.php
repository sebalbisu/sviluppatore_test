<?php

namespace App\Console\Commands\BackgroundJob;

use App\Enums\JobStatus;
use Illuminate\Console\Command;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\BackgroundJobService;
use Illuminate\Support\Facades\Log;

class JobManager extends Command
{
    protected $signature = 'background-jobs:job-manager {--max-jobs=1}';
    protected $description = 'Manage and dispatch background jobs';

    protected $service;

    public function __construct(BackgroundJobService $service)
    {
        parent::__construct();
        $this->service = $service;
        Log::setDefaultDriver('job_manager_stack');
    }

    public function handle()
    {
        $maxJobs = (int)$this->option('max-jobs');
        $this->info("Job Manager started with a max of {$maxJobs} jobs at a time.");

        while (true) {
            $this->callKillTimedOutJobsCommand();
            $this->dispatchJobs($maxJobs);
            sleep(1);
        }
    }

    protected function callKillTimedOutJobsCommand()
    {
        try {
            $this->call('background-jobs:kill-timeouts');
        } catch (\Exception $e) {
            Log::error('Failed to execute KillTimedOutJobs command: ' . $e->getMessage());
        }
    }

    protected function dispatchJobs($maxJobs)
    {
        $runningCount = BackgroundJob::where('status', 'running')->count();

        if ($runningCount >= $maxJobs) {
            $this->info("Maximum running jobs limit reached ({$maxJobs}).");
            return;
        }

        $jobs = BackgroundJob::where('status', JobStatus::PENDING)
            ->orderByRaw("CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END asc")
            ->orderBy('available_at', 'asc')
            ->orderBy('updated_at', 'asc')
            ->where('available_at', '<=', now())
            ->limit($maxJobs - $runningCount)
            ->get();

        foreach ($jobs as $job) {
            try {
                $this->service->asyncDispatch($job);
            } catch (\Exception $e) {
                Log::error("Failed to dispatch job {$job->id}: " . $e->getMessage());
            }
        }
    }
}
