<?php

namespace Tests\Unit\BackgroundService;

use App\Enums\JobStatus;
use App\Services\BackgroundJob\BackgroundJobService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;
use Tests\Unit\BackgroundService\Data\ClassA;

class RunTest extends TestCase
{
    use RefreshDatabase;

    protected BackgroundJobService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BackgroundJobService();
        Log::shouldReceive('debug');
    }

    protected function expectClassMethodToBeCalled(
        string $classname,
        string $method,
        array $params
    ): MockObject {
        $mock = $this->createMock($classname);

        $mock->expects($this->once())
            ->method($method)
            ->with(...$params);

        $this->app->instance($classname, $mock);

        return $mock;
    }


    public function testRunOk()
    {
        $data = [
            'classname' => ClassA::class,
            'method' => 'method3',
            'params' => [1, 'data'],
        ];

        $job = $this->service->create(...$data);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(["Starting job {$job->id}: {$job->classname}@{$job->method}"]);

        $this->expectClassMethodToBeCalled($data['classname'], $data['method'], $data['params']);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(["Job {$job->id} completed."]);

        $this->service->run($job);

        $job->refresh();

        $this->assertFalse($job->exists);
    }

    static public function classMethodNotExistsProvider()
    {
        return [
            'classNotExists' => [
                'classname' => 'NotExistsClass',
                'method' => 'method1',
            ],
            'methodNotExists' => [
                'classname' => ClassA::class,
                'method' => 'notExistsMethod'
            ],
        ];
    }

    /**
     * @dataProvider classMethodNotExistsProvider
     */
    public function testClassMethodNotExists(string $classname, string $method)
    {
        $data = [
            'classname' => $classname,
            'method' => $method,
        ];
        $job = $this->service->create(...$data);

        Log::shouldReceive('info');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(["Job {$job->id} failed: " . "$classname::$method not found"]);

        $this->service->run($job);

        $job->refresh();

        $this->assertFalse($job->exists);
    }

    public function testWhenFailsIncrRetries()
    {
        $maxRetries = 4;
        $initRetries = rand(0, $maxRetries - 2);
        $job = $this->service->create(
            classname: ClassA::class,
            method: 'method5ThrowException',
            maxRetries: $maxRetries,
        );
        $job->retries = $initRetries;
        $job->save();

        Log::shouldReceive('info');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($input) => str_contains($input, "Job {$job->id} failed"));

        $this->service->run($job);

        $job->refresh();

        $this->assertEquals($initRetries + 1, $job->retries);
        $this->assertTrue($job->exists);
        $this->assertNull($job->started_at);
        $this->assertTrue($job->available_at->isFuture());
        $this->assertEquals(JobStatus::PENDING, $job->status);
    }

    public function testWhenFailsAndRetriesIsMaxDeleteJob()
    {
        $maxRetries = 4;
        $initRetries = 3;
        $job = $this->service->create(
            classname: ClassA::class,
            method: 'method5ThrowException',
            maxRetries: $maxRetries,
        );
        $job->retries = $initRetries;
        $job->save();

        Log::shouldReceive('info');

        Log::shouldReceive('error')
            ->once()
            ->withArgs(fn($input) => str_contains($input, "Job {$job->id} failed"));

        Log::shouldReceive('error')
            ->once()
            ->withArgs(["Job {$job->id} deleted after exceeding retry limit."]);

        $this->service->run($job);

        $job->refresh();

        $this->assertFalse($job->exists);
    }
}
