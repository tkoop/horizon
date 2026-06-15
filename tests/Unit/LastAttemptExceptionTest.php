<?php

namespace Laravel\Horizon\Tests\Unit;

use Exception;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Laravel\Horizon\LastAttemptException;
use Laravel\Horizon\Tests\Fixtures\FakeQueueJob;
use Laravel\Horizon\Tests\UnitTest;

class LastAttemptExceptionTest extends UnitTest
{
    public function test_it_detects_synthetic_failures()
    {
        $this->assertTrue(LastAttemptException::isSyntheticFailure(
            MaxAttemptsExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob'))
        ));

        $this->assertTrue(LastAttemptException::isSyntheticFailure(
            TimeoutExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob'))
        ));
    }

    public function test_it_does_not_detect_regular_exceptions_as_synthetic_failures()
    {
        $this->assertFalse(LastAttemptException::isSyntheticFailure(new Exception('Job Failed')));
    }

    public function test_it_should_capture_regular_exceptions()
    {
        $this->assertTrue(LastAttemptException::shouldCapture(new Exception('Job Failed')));
    }

    public function test_it_should_not_capture_synthetic_exceptions()
    {
        $this->assertFalse(LastAttemptException::shouldCapture(
            MaxAttemptsExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob'))
        ));

        $this->assertFalse(LastAttemptException::shouldCapture(
            TimeoutExceededException::forJob(new FakeQueueJob('App\\Jobs\\ExampleJob'))
        ));
    }
}
