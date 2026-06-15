<?php

namespace Laravel\Horizon\Contracts;

interface LastAttemptExceptionRepository
{
    /**
     * Store the exception from a failed job attempt.
     *
     * @param  string  $id
     * @param  string  $exception
     * @return void
     */
    public function store($id, $exception);

    /**
     * Retrieve and remove the stored exception for the given job.
     *
     * @param  string  $id
     * @return string|null
     */
    public function pull($id);

    /**
     * Remove the stored exception for the given job.
     *
     * @param  string  $id
     * @return void
     */
    public function forget($id);
}
