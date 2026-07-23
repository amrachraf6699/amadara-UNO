<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidSimulationResult extends RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Gemini returned an invalid league simulation.');
    }
}
