<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\Events\JobFailed;
use Laravel\Horizon\LastAttemptException;

class MarkJobAsFailed
{
    /**
     * The job repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\JobRepository
     */
    public $jobs;

    /**
     * The last attempt exception repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\LastAttemptExceptionRepository
     */
    public $lastAttemptExceptions;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\JobRepository  $jobs
     * @param  \Laravel\Horizon\Contracts\LastAttemptExceptionRepository  $lastAttemptExceptions
     * @return void
     */
    public function __construct(JobRepository $jobs, LastAttemptExceptionRepository $lastAttemptExceptions)
    {
        $this->jobs = $jobs;
        $this->lastAttemptExceptions = $lastAttemptExceptions;
    }

    /**
     * Handle the event.
     *
     * @param  \Laravel\Horizon\Events\JobFailed  $event
     * @return void
     */
    public function handle(JobFailed $event)
    {
        $lastAttemptException = null;

        if (LastAttemptException::isSyntheticFailure($event->exception)) {
            $lastAttemptException = $this->lastAttemptExceptions->pull($event->payload->id());
        }

        $this->jobs->failed(
            $event->exception,
            $event->connectionName,
            $event->queue,
            $event->payload,
            $lastAttemptException
        );
    }
}
