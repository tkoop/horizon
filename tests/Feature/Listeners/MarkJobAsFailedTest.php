<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Exception;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\JobPayload;
use Laravel\Horizon\Listeners\MarkJobAsFailed;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Tests\Fixtures\FakeQueueJob;
use Mockery as m;

class MarkJobAsFailedTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_it_attaches_the_last_attempt_exception_for_synthetic_failures()
    {
        $jobs = m::mock(JobRepository::class);
        $exceptions = m::mock(LastAttemptExceptionRepository::class);

        $exceptions->shouldReceive('pull')->once()->with('job-uuid')->andReturn('Previous failure');

        $jobs->shouldReceive('failed')->once()->with(
            m::type(MaxAttemptsExceededException::class),
            'redis',
            'default',
            m::type(JobPayload::class),
            'Previous failure'
        );

        $listener = new MarkJobAsFailed($jobs, $exceptions);

        $event = new JobFailed(
            MaxAttemptsExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob')),
            new FailedJob(),
            json_encode(['uuid' => 'job-uuid', 'displayName' => 'ExampleJob'])
        );

        $event->connection('redis')->queue('default');

        $listener->handle($event);
    }

    public function test_it_does_not_pull_last_attempt_exception_for_regular_failures()
    {
        $jobs = m::mock(JobRepository::class);
        $exceptions = m::mock(LastAttemptExceptionRepository::class);

        $exceptions->shouldNotReceive('pull');

        $jobs->shouldReceive('failed')->once()->with(
            m::type(Exception::class),
            'redis',
            'default',
            m::type(JobPayload::class),
            null
        );

        $listener = new MarkJobAsFailed($jobs, $exceptions);

        $event = new JobFailed(
            new Exception('Job Failed'),
            new FailedJob(),
            json_encode(['uuid' => 'job-uuid', 'displayName' => 'ExampleJob'])
        );

        $event->connection('redis')->queue('default');

        $listener->handle($event);
    }

    public function test_it_attaches_the_last_attempt_exception_for_timeout_failures()
    {
        $jobs = m::mock(JobRepository::class);
        $exceptions = m::mock(LastAttemptExceptionRepository::class);

        $exceptions->shouldReceive('pull')->once()->with('job-uuid')->andReturn('Previous failure');

        $jobs->shouldReceive('failed')->once()->with(
            m::type(TimeoutExceededException::class),
            'redis',
            'default',
            m::type(JobPayload::class),
            'Previous failure'
        );

        $listener = new MarkJobAsFailed($jobs, $exceptions);

        $event = new JobFailed(
            TimeoutExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob')),
            new FailedJob(),
            json_encode(['uuid' => 'job-uuid', 'displayName' => 'ExampleJob'])
        );

        $event->connection('redis')->queue('default');

        $listener->handle($event);
    }
}

class FailedJob extends \Illuminate\Queue\Jobs\Job
{
    public function getJobId()
    {
        return 'job-uuid';
    }

    public function getRawBody()
    {
        return '';
    }
}
