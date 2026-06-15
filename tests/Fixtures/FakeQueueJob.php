<?php

namespace Laravel\Horizon\Tests\Fixtures;

class FakeQueueJob
{
    public function __construct(public $name)
    {
    }

    public function resolveName()
    {
        return $this->name;
    }
}
