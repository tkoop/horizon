<?php

namespace Laravel\Horizon\Listeners;

use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;
use Laravel\Horizon\Events\JobDeleted;

class ForgetLastAttemptException
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
     * @param  \Laravel\Horizon\Events\JobDeleted  $event
     * @return void
     */
    public function handle(JobDeleted $event)
    {
        if (! $event->job->hasFailed()) {
            $this->exceptions->forget($event->payload->id());
        }
    }
}
