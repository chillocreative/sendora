<?php

namespace App\Exceptions;

use RuntimeException;

class ReminderLimitReached extends RuntimeException
{
    public function __construct(public readonly int $limit)
    {
        parent::__construct("You've reached your monthly reminder limit ({$limit}). Please upgrade your plan.");
    }
}
