<?php

namespace App\Http\Controllers;

use App\Enums\JobPriority;
use App\Enums\JobStatus;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\BackgroundJobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\Unit\BackgroundService\Data\ClassA;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $jobs = BackgroundJob::orderBy('id', 'asc')->get();

        if ($request->has('only-table')) {
            return view('jobs.background_jobs_table', compact('jobs'));
        }

        return view('jobs.index', compact('jobs'));
    }

    public function kill($id, Request $request, BackgroundJobService $service)
    {
        Log::setDefaultDriver('job_manager_stack');
        $job = $service->findOrFail($id);
        $service->kill($job);
        return redirect()->route('backgroundJobs.index');
    }

    public function delete($id, Request $request, BackgroundJobService $service)
    {
        $job = $service->findOrFail($id);
        $job->delete();
        return redirect()->route('backgroundJobs.index');
    }

    public function addTestJobs()
    {
        for ($i = 0; $i < 10; $i++) {
            $priority = collect(JobPriority::cases())->random();
            runBackgroundJob(
                ClassA::class,
                collect(['method4Sleep', 'method6SleepThrowException'])->random(),
                [$sleep = 5],
                priority: $priority,
                retryDelay: rand(20, 30),
                maxRetries: rand(1, 5),
                timeout: rand(30, 60),
                delay: rand(0, 10),
            );
        }
        return redirect()->route('backgroundJobs.index');
    }
}
