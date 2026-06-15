<?php

namespace Laravel\Horizon\Tests\Feature;

use Exception;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Tests\Fixtures\FakeQueueJob;
use Laravel\Horizon\Tests\IntegrationTest;

class LastAttemptExceptionTest extends IntegrationTest
{
    public function test_it_stores_and_pulls_last_attempt_exceptions()
    {
        $repository = $this->app->make(LastAttemptExceptionRepository::class);

        $repository->store('job-1', "Exception: Job Failed\nStack trace");

        $this->assertSame("Exception: Job Failed\nStack trace", $repository->pull('job-1'));
        $this->assertNull($repository->pull('job-1'));
    }

    public function test_it_forgets_last_attempt_exceptions()
    {
        $repository = $this->app->make(LastAttemptExceptionRepository::class);

        $repository->store('job-1', 'Exception trace');

        $repository->forget('job-1');

        $this->assertNull($repository->pull('job-1'));
    }

    public function test_it_stores_last_attempt_exception_on_failed_jobs()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['uuid' => 'job-1', 'displayName' => 'foo']));

        $repository->failed(
            MaxAttemptsExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob')),
            'redis',
            'default',
            $payload,
            "Exception: Job Failed\nStack trace"
        );

        $job = $repository->getJobs(['job-1'])[0];

        $this->assertStringContainsString('attempted too many times', $job->exception);
        $this->assertSame("Exception: Job Failed\nStack trace", $job->last_attempt_exception);
    }

    public function test_it_does_not_store_last_attempt_exception_when_not_provided()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['uuid' => 'job-1', 'displayName' => 'foo']));

        $repository->failed(new Exception('Job Failed'), 'redis', 'default', $payload);

        $job = $repository->getJobs(['job-1'])[0];

        $this->assertSame('Job Failed', $job->exception);
        $this->assertNull($job->last_attempt_exception);
    }

    public function test_it_stores_last_attempt_exception_for_timeout_failures()
    {
        $repository = $this->app->make(JobRepository::class);
        $payload = new JobPayload(json_encode(['uuid' => 'job-1', 'displayName' => 'foo']));

        $repository->failed(
            TimeoutExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob')),
            'redis',
            'default',
            $payload,
            "Exception: Connection refused\nStack trace"
        );

        $job = $repository->getJobs(['job-1'])[0];

        $this->assertStringContainsString('timed out', $job->exception);
        $this->assertSame("Exception: Connection refused\nStack trace", $job->last_attempt_exception);
    }
}
