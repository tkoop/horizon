<?php

namespace Laravel\Horizon;

use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Throwable;

class LastAttemptException
{
    /**
     * Determine if the exception should be captured for later reference.
     *
     * @param  \Throwable  $exception
     * @return bool
     */
    public static function shouldCapture(Throwable $exception)
    {
        return ! static::isSyntheticFailure($exception);
    }

    /**
     * Determine if the exception represents a synthetic queue failure.
     *
     * @param  \Throwable  $exception
     * @return bool
     */
    public static function isSyntheticFailure(Throwable $exception)
    {
        return $exception instanceof MaxAttemptsExceededException
            || $exception instanceof TimeoutExceededException;
    }
}
