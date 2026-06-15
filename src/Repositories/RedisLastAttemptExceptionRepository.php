<?php

namespace Laravel\Horizon\Repositories;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Laravel\Horizon\Contracts\LastAttemptExceptionRepository;

class RedisLastAttemptExceptionRepository implements LastAttemptExceptionRepository
{
    /**
     * The Redis connection instance.
     *
     * @var \Illuminate\Contracts\Redis\Factory
     */
    public $redis;

    /**
     * The number of minutes until stored exceptions should expire.
     *
     * @var int
     */
    public $expires;

    /**
     * Create a new repository instance.
     *
     * @param  \Illuminate\Contracts\Redis\Factory  $redis
     * @return void
     */
    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;

        $this->expires = (int) config('horizon.trim.recent', 60);
    }

    /**
     * Store the exception from a failed job attempt.
     *
     * @param  string  $id
     * @param  string  $exception
     * @return void
     */
    public function store($id, $exception)
    {
        $this->connection()->setex(
            $this->key($id),
            $this->expires * 60,
            $exception
        );
    }

    /**
     * Retrieve and remove the stored exception for the given job.
     *
     * @param  string  $id
     * @return string|null
     */
    public function pull($id)
    {
        $key = $this->key($id);

        $exception = $this->connection()->get($key);

        if ($exception) {
            $this->connection()->del($key);
        }

        return $exception ?: null;
    }

    /**
     * Remove the stored exception for the given job.
     *
     * @param  string  $id
     * @return void
     */
    public function forget($id)
    {
        $this->connection()->del($this->key($id));
    }

    /**
     * Get the Redis key for the given job ID.
     *
     * @param  string  $id
     * @return string
     */
    protected function key($id)
    {
        return 'last_attempt_exception:'.$id;
    }

    /**
     * Get the Redis connection instance.
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    protected function connection()
    {
        return $this->redis->connection('horizon');
    }
}
