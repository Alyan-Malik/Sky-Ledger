<?php
// app/Exceptions/DuffelValidationException.php

namespace App\Exceptions;

class DuffelValidationException extends DuffelException
{
    protected array $errors = [];

    public function __construct(
        string $message,
        array $errors = [],
        ?\Exception $previous = null
    ) {
        parent::__construct(
            message: $message,
            duffelStatusCode: 422,
            context: ['validation_errors' => $errors],
            previous: $previous
        );
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}