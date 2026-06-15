<?php

namespace Laravel\Horizon\Tests\Feature\Listeners;

use Exception;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\MaxAttemptsExceededException;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\Listeners\StoreLastAttemptException;
use Laravel\Horizon\Tests\IntegrationTest;
use Laravel\Horizon\Tests\Fixtures\FakeQueueJob;
use Mockery as m;

class StoreLastAttemptExceptionTest extends IntegrationTest
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function test_it_stores_the_exception_for_redis_jobs()
    {
        $repository = m::mock(LastAttemptExceptionRepository::class);

        $repository->shouldReceive('store')->once()->with('job-uuid', m::on(function ($value) {
            return str_contains($value, 'Job Failed');
        }));

        $listener = new StoreLastAttemptException($repository);

        $listener->handle(new JobExceptionOccurred(
            'redis',
            m::mock(RedisJob::class, function ($mock) {
                $mock->shouldReceive('uuid')->andReturn('job-uuid');
            }),
            new Exception('Job Failed')
        ));
    }

    public function test_it_does_not_store_synthetic_exceptions()
    {
        $repository = m::mock(LastAttemptExceptionRepository::class);

        $repository->shouldNotReceive('store');

        $listener = new StoreLastAttemptException($repository);

        $listener->handle(new JobExceptionOccurred(
            'redis',
            m::mock(RedisJob::class, function ($mock) {
                $mock->shouldReceive('uuid')->andReturn('job-uuid');
            }),
            MaxAttemptsExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob'))
        ));
    }
}
