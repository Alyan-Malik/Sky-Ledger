<?php
// app/Exceptions/DuffelException.php

namespace App\Exceptions;

use Exception;

class DuffelException extends Exception
{
    protected array $context = [];
    protected ?int $duffelStatusCode = null;

    public function __construct(
        string $message = 'Duffel API error occurred',
        int $code = 0,
        ?int $duffelStatusCode = null,
        array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->duffelStatusCode = $duffelStatusCode;
        $this->context = $context;
    }

    public function getDuffelStatusCode(): ?int
    {
        return $this->duffelStatusCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function render(): array
    {
        return [
            'success' => false,
            'message' => $this->getUserFriendlyMessage(),
            'code' => $this->duffelStatusCode ?? $this->getCode(),
        ];
    }

    protected function getUserFriendlyMessage(): string
    {
        return match ($this->duffelStatusCode) {
            401 => 'Unable to authenticate with flight service. Please try again later.',
            403 => 'Access to flight service is currently restricted.',
            422 => 'Invalid search parameters. Please check your inputs.',
            429 => 'Too many requests. Please wait a moment and try again.',
            500, 502, 503, 504 => 'Flight service is temporarily unavailable. Please try again in a few minutes.',
            default => $this->message ?: 'An unexpected error occurred while searching for flights.',
        };
    }
}