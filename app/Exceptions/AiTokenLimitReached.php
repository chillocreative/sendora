<?php

namespace App\Exceptions;

use RuntimeException;

class AiTokenLimitReached extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("You've used all AI tokens for this month. Upgrade your plan or buy a token pack to keep using AI features.");
    }
}
