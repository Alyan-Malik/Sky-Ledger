<?php
// app/Exceptions/DuffelApiException.php

namespace App\Exceptions;

class DuffelApiException extends DuffelException
{
    public function __construct(
        string $message,
        array $context = [],
        ?\Exception $previous = null
    ) {
        parent::__construct(
            message: $message,
            duffelStatusCode: null,
            context: $context,
            previous: $previous
        );
    }
}