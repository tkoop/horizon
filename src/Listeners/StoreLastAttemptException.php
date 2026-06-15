<?php

namespace Laravel\Horizon\Listeners;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Jobs\RedisJob;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\LastAttemptException;

class StoreLastAttemptException
{
    /**
     * The last attempt exception repository implementation.
     *
     * @var \Laravel\Horizon\Contracts\LastAttemptExceptionRepository
     */
    public $exceptions;

    /**
     * Create a new listener instance.
     *
     * @param  \Laravel\Horizon\Contracts\LastAttemptExceptionRepository  $exceptions
     * @return void
     */
    public function __construct(LastAttemptExceptionRepository $exceptions)
    {
        $this->exceptions = $exceptions;
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Queue\Events\JobExceptionOccurred  $event
     * @return void
     */
    public function handle(JobExceptionOccurred $event)
    {
        if (! $event->job instanceof RedisJob) {
            return;
        }

        if (! LastAttemptException::shouldCapture($event->exception)) {
            return;
        }

        if (! $id = $event->job->uuid()) {
            return;
        }

        $this->exceptions->store($id, (string) $event->exception);
    }
}
