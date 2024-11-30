<?php

namespace App\Services\BackgroundJob;

use App\Enums\JobPriority;
use App\Enums\JobStatus;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\ClassMethodNotFoundException;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;

class BackgroundJobService
{
    public function findOrFail($jobId)
    {
        return BackgroundJob::findOrFail($jobId);
    }

    public function create(
        string $classname,
        string $method,
        array $params = [],
        JobPriority $priority = JobPriority::MEDIUM,
        int $delay = 0,
        int $retryDelay = null,
        int $maxRetries = null,
        int $timeout = null,
    ): BackgroundJob {

        $job = BackgroundJob::create([
            'classname' => $classname,
            'method' => $method,
            'params' => $params,
            'status' => JobStatus::PENDING,
            'priority' => $priority,
            'delay' => $delay,
            'max_retries' => $maxRetries ?? config('backgroundJobs.max_retries'),
            'retry_delay' => $retryDelay ?? config('backgroundJobs.retry_delay'),
            'available_at' => now()->addSeconds($delay),
            'timeout' => $timeout ?? config('backgroundJobs.timeout'),
        ]);

        return $job;
    }

    public function asyncDispatch(BackgroundJob $job)
    {
        try {
            Log::info("Dispatching job {$job->id}.");

            (PHP_OS_FAMILY === 'Windows')
                ? $this->asyncDispatchInWindows($job)
                : $this->asyncDispatchInUnix($job);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch job {$job->id}: " . $e->getMessage());
            $job->delete();
            throw $e;
        }
    }

    protected function asyncDispatchInUnix(BackgroundJob $job)
    {
        $command = "php artisan background-jobs:run-job {$job->id} > /dev/null 2>&1 &";
        exec($command);
    }

    protected function asyncDispatchInWindows(BackgroundJob $job)
    {
        $command = "start /B php artisan background-jobs:run-job {$job->id}";
        exec($command);
    }

    public function run(BackgroundJob $job)
    {
        try {
            Log::info("Starting job {$job->id}: {$job->classname}@{$job->method}.");
            Log::info("Job {$job->id} attempt {$job->retries}/{$job->max_retries}.");

            $response = $this->callMethod($job->classname, $job->method, $job->params);

            Log::info("Job {$job->id} completed.");

            $job->delete();
        } catch (ClassMethodNotFoundException $e) {
            Log::error("Job {$job->id} failed: " . $e->getMessage());
            $job->delete();
            return;
        } catch (\Exception $e) {

            Log::error("Job {$job->id} failed: " . $e->getMessage());

            if (($job->retries + 1) >= $job->max_retries) {
                $job->delete();
                Log::error("Job {$job->id} deleted after exceeding retry limit.");
                return;
            }

            $job->update([
                'pid' => null,
                'status' => JobStatus::PENDING,
                'started_at' => null,
                'retries' => $job->retries + 1,
                'available_at' => now()->addSeconds($job->retry_delay),
            ]);
        }
    }

    protected function callMethod(string $classname, string $method, array $params = []): mixed
    {
        if (!class_exists($classname) || !method_exists($classname, $method)) {
            throw new ClassMethodNotFoundException($classname, $method);
        }

        $obj = app($classname);

        return call_user_func_array([$obj, $method], $params);
    }

    public function kill(BackgroundJob $job)
    {
        try {
            if ($job->pid) {
                $this->killProcess($job->pid);
                Log::info("Killed job {$job->id} with ID {$job->id} PID {$job->pid} after timeout {$job->timeout_at}.");
                $job->delete();
            }
        } catch (\Exception $e) {
            Log::error("Failed to kill job {$job->id}: " . $e->getMessage());
        }
    }

    protected function killProcess($pid)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec("taskkill /PID {$pid} /F");
        } else {
            exec("kill -9 {$pid} &");
        }
    }
}
