<?php
// app/Services/Duffel/DuffelService.php

namespace App\Services\Duffel;

use App\Exceptions\DuffelApiException;
use App\Exceptions\DuffelValidationException;
use App\Models\FlightSearch;
use App\Services\Duffel\Transformers\OfferTransformer;
use App\Services\Duffel\Mappers\OfferRequestMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DuffelService
{
    public function __construct(
        private readonly DuffelClient $client,
        private readonly OfferRequestMapper $requestMapper,
        private readonly OfferTransformer $transformer
    ) {}

    /**
     * Search for flight offers
     */
    public function searchOffers(FlightSearch $flightSearch): array
    {
        try {
            // Map flight search to Duffel request
            $requestPayload = $this->requestMapper->map($flightSearch);

            // Check cache for identical searches
            $cacheKey = $this->generateCacheKey($requestPayload);
            
            if ($cachedOffers = $this->getFromCache($cacheKey)) {
                Log::info('Returning cached flight offers', [
                    'flight_search_id' => $flightSearch->id,
                    'offers_count' => count($cachedOffers),
                ]);
                return $cachedOffers;
            }

            // Send request to Duffel - v2 returns offers synchronously
            $rawResponse = $this->client->post(
                'air/offer_requests',
                ['data' => $requestPayload]
            );

            // Get offers from response
            $offers = $rawResponse['data']['offers'] ?? [];
            $offerRequestId = $rawResponse['data']['id'] ?? null;

            Log::info('Duffel search completed', [
                'offer_request_id' => $offerRequestId,
                'offers_count' => count($offers),
            ]);

            // If no offers found
            if (empty($offers)) {
                Log::info('No offers found for search', [
                    'flight_search_id' => $flightSearch->id,
                    'offer_request_id' => $offerRequestId,
                ]);
                
                // Still update search metadata
                $flightSearch->update([
                    'search_metadata' => array_merge(
                        $flightSearch->search_metadata ?? [],
                        [
                            'duffel_offer_request_id' => $offerRequestId,
                            'offers_count' => 0,
                            'search_completed_at' => now()->toISOString(),
                        ]
                    ),
                ]);

                return [];
            }

            // Transform and normalize offers
            $normalizedOffers = $this->transformer->transformCollection($offers);

            // Apply service markup
            $normalizedOffers = $this->applyServiceMarkup($normalizedOffers);

            // Cache the results
            $this->cacheResults($cacheKey, $normalizedOffers);

            // Update flight search with metadata
            $flightSearch->update([
                'search_metadata' => array_merge(
                    $flightSearch->search_metadata ?? [],
                    [
                        'duffel_offer_request_id' => $offerRequestId,
                        'offers_count' => count($normalizedOffers),
                        'search_completed_at' => now()->toISOString(),
                    ]
                ),
            ]);

            return $normalizedOffers;

        } catch (DuffelValidationException $e) {
            Log::warning('Duffel validation error', [
                'flight_search_id' => $flightSearch->id,
                'errors' => $e->getErrors(),
            ]);
            throw $e;
        } catch (DuffelApiException $e) {
            Log::error('Duffel API error during flight search', [
                'flight_search_id' => $flightSearch->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error during flight search', [
                'flight_search_id' => $flightSearch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new DuffelApiException(
                'Failed to search flights. Please try again.',
                [],
                $e
            );
        }
    }

    /**
     * Get offer details by ID
     */
    public function getOffer(string $offerId): array
    {
        try {
            $response = $this->client->get("air/offers/{$offerId}");
            
            $offerData = $response['data'] ?? [];
            
            if (empty($offerData)) {
                throw new DuffelApiException('Offer not found.');
            }
            
            return $this->transformer->transform($offerData);
        } catch (\Exception $e) {
            Log::error('Failed to fetch offer details', [
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
            ]);
            throw new DuffelApiException('Failed to fetch offer details.');
        }
    }

    /**
     * Apply service charge markup to offers
     */
    private function applyServiceMarkup(array $offers): array
    {
        $markupPercentage = config('duffel.service.markup_percentage', 5);
        $markupFixed = config('duffel.service.markup_fixed', 0);

        return array_map(function ($offer) use ($markupPercentage, $markupFixed) {
            $baseAmount = floatval($offer['pricing']['base_amount'] ?? $offer['pricing']['base_fare'] ?? 0);
            $taxAmount = floatval($offer['pricing']['tax_amount'] ?? 0);
            $serviceCharge = round(($baseAmount * $markupPercentage / 100) + $markupFixed, 2);
            $grandTotal = round($baseAmount + $taxAmount + $serviceCharge, 2);
            
            $offer['pricing']['service_charge'] = $serviceCharge;
            $offer['pricing']['grand_total'] = $grandTotal;
            
            // Also add top-level keys for compatibility
            $offer['service_charge'] = $serviceCharge;
            $offer['grand_total'] = $grandTotal;
            
            return $offer;
        }, $offers);
    }

    /**
     * Generate cache key for search
     */
    private function generateCacheKey(array $requestPayload): string
    {
        return config('duffel.cache.prefix') . 'search_' . md5(serialize($requestPayload));
    }

    /**
     * Get results from cache
     */
    private function getFromCache(string $key): ?array
    {
        return Cache::get($key);
    }

    /**
     * Cache search results
     */
    private function cacheResults(string $key, array $offers): void
    {
        $ttl = config('duffel.cache.ttl', 300);
        Cache::put($key, $offers, $ttl);
    }
}