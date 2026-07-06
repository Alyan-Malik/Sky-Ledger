<?php
// app/Services/Duffel/DuffelClient.php

namespace App\Services\Duffel;

use App\Exceptions\DuffelApiException;
use App\Exceptions\DuffelRateLimitException;
use App\Exceptions\DuffelValidationException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuffelClient
{
    private string $baseUrl;
    private string $apiToken;
    private string $apiVersion;
    private array $defaultHeaders;
    private int $timeout;
    private int $connectTimeout;

    public function __construct()
    {
        $this->baseUrl = config('duffel.api.url');
        $this->apiToken = config('duffel.api.token');
        $this->apiVersion = config('duffel.api.version', 'v2');
        $this->timeout = config('duffel.http.timeout', 30);
        $this->connectTimeout = config('duffel.http.connect_timeout', 10);

        $this->defaultHeaders = [
            'Authorization' => "Bearer {$this->apiToken}",
            'Duffel-Version' => $this->apiVersion,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Send GET request to Duffel API
     */
    public function get(string $endpoint, array $params = []): array
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Send POST request to Duffel API
     */
    public function post(string $endpoint, array $data = []): array
    {
        return $this->request('POST', $endpoint, [], $data);
    }

    /**
     * Make HTTP request with error handling and retries
     */
    private function request(string $method, string $endpoint, array $params = [], array $data = []): array
    {
        $url = "{$this->baseUrl}/{$endpoint}";
        
        $this->logRequest($method, $url, $params, $data);

        try {
            $httpClient = Http::withHeaders($this->defaultHeaders)
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout);

            // Don't use retry for POST requests that create resources
            if ($method === 'GET') {
                $httpClient->retry(
                    config('duffel.http.retry.times', 3),
                    config('duffel.http.retry.sleep', 1000),
                    function ($exception, $request) {
                        return $this->shouldRetry($exception, $request);
                    }
                );
            }

            $response = $method === 'GET' 
                ? $httpClient->get($url, $params)
                : $httpClient->post($url, $data);

            $this->logResponse($response);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Log response structure for debugging
                if (config('duffel.logging.log_requests', false)) {
                    Log::debug('Duffel API Response Structure', [
                        'status' => $response->status(),
                        'has_data_key' => isset($responseData['data']),
                        'data_keys' => isset($responseData['data']) ? array_keys($responseData['data']) : [],
                    ]);
                }
                
                return $responseData;
            }

            $this->handleErrorResponse($response);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Duffel API connection error', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new DuffelApiException(
                'Unable to connect to flight service. Please try again.',
                ['url' => $url],
                $e
            );
        } catch (\Exception $e) {
            if (!$e instanceof DuffelApiException) {
                Log::error('Duffel API unexpected error', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw new DuffelApiException(
                    'An unexpected error occurred. Please try again.',
                    ['url' => $url],
                    $e
                );
            }
            throw $e;
        }

        throw new DuffelApiException('Flight service request failed.');
    }

    // ... rest of the methods remain the same
    private function handleErrorResponse(?Response $response): void
    {
        if (!$response) {
            throw new DuffelApiException('No response received from flight service.');
        }

        $statusCode = $response->status();
        $responseData = $response->json() ?? [];
        $errorMessage = $responseData['errors'][0]['message'] ?? $responseData['errors'][0]['title'] ?? 'Unknown error';

        switch ($statusCode) {
            case 401:
                Log::critical('Duffel API authentication failed', ['response' => $responseData]);
                throw new DuffelApiException('Flight service authentication failed.');

            case 403:
                Log::warning('Duffel API access forbidden', ['response' => $responseData]);
                throw new DuffelApiException('Access to flight service is restricted.');

            case 422:
                $errors = $responseData['errors'] ?? [];
                throw new DuffelValidationException($errorMessage, $errors);

            case 429:
                $retryAfter = $response->header('Retry-After');
                throw new DuffelRateLimitException(
                    'Rate limit exceeded. Please wait before trying again.',
                    $retryAfter ? (int) $retryAfter : null
                );

            case 500:
            case 502:
            case 503:
            case 504:
                Log::error('Duffel API server error', [
                    'status' => $statusCode,
                    'response' => $responseData,
                ]);
                throw new DuffelApiException(
                    'Flight service is temporarily unavailable. Please try again later.'
                );

            default:
                Log::error('Duffel API unexpected status', [
                    'status' => $statusCode,
                    'response' => $responseData,
                ]);
                throw new DuffelApiException(
                    "Flight service returned an unexpected error (HTTP {$statusCode})."
                );
        }
    }

    private function shouldRetry(\Exception $exception, $request): bool
    {
        if ($exception instanceof \Illuminate\Http\Client\RequestException) {
            return in_array($exception->response?->status(), config('duffel.http.retry.when', []));
        }
        
        return $exception instanceof \Illuminate\Http\Client\ConnectionException;
    }

    private function logRequest(string $method, string $url, array $params, array $data): void
    {
        if (config('duffel.logging.log_requests', false)) {
            Log::channel(config('duffel.logging.channel'))
                ->debug('Duffel API Request', [
                    'method' => $method,
                    'url' => $url,
                    'params' => $params,
                    'data' => array_merge($data, ['sensitive' => 'redacted']),
                ]);
        }
    }

    private function logResponse(Response $response): void
    {
        if (config('duffel.logging.log_responses', false)) {
            Log::channel(config('duffel.logging.channel'))
                ->debug('Duffel API Response', [
                    'status' => $response->status(),
                    'headers' => $response->headers(),
                ]);
        }
    }
}