<?php

namespace Tests\Unit;

use App\Enums\JobPriority;
use App\Enums\JobStatus;
use App\Models\BackgroundJob;
use App\Services\BackgroundJob\BackgroundJobService;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Unit\BackgroundService\Data\ClassA;

class CreateTest extends TestCase
{
    use RefreshDatabase;
    protected BackgroundJobService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BackgroundJobService();
    }

    public function testDefaultsOk()
    {
        $data = [
            'classname' => ClassA::class,
            'method' => 'method1',
        ];
        $job = $this->service->create(...$data);

        $this->assertInstanceOf(BackgroundJob::class, $job);
        $this->assertIsArray($job->params);
        $this->assertEquals([], $job->params);
        $this->assertEquals(JobStatus::PENDING, $job->status);
        $this->assertEquals(JobPriority::MEDIUM, $job->priority);
        $this->assertEquals($job->max_retries, 1);
        $this->assertEquals(0, $job->delay);
        $this->assertLessThan(now(), $job->available_at);
    }

    public function testDefaultIsAvailableAndPending()
    {
        $data = [
            'classname' => ClassA::class,
            'method' => 'method1',
        ];
        $job = $this->service->create(...$data);

        $this->assertTrue($job->isAvailable());
        $this->assertEquals(JobStatus::PENDING, $job->status);
    }

    public function testClassMethodOk()
    {
        $data = [
            'classname' => ClassA::class,
            'method' => 'method1',
        ];
        $job = $this->service->create(...$data);

        $this->assertInstanceOf(BackgroundJob::class, $job);
        $this->assertEquals($data['classname'], $job->classname);
        $this->assertEquals($data['method'], $job->method);
    }

    public function testParamsOk()
    {
        $data = [
            'classname' => ClassA::class,
            'method' => 'method1',
            'params' => [1, 'data'],
        ];
        $job = $this->service->create(...$data);

        $this->assertIsArray($job->params);
        $this->assertEquals($data['params'], $job->params);
    }


    public function testDelayOk()
    {
        $delay = 10;
        $data = [
            'classname' => ClassA::class,
            'method' => 'method1',
            'delay' => $delay,
        ];
        $job = $this->service->create(...$data);

        $this->assertEquals($job->created_at->addSeconds($delay), $job->available_at);
    }
}
