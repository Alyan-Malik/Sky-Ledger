<?php
// app/Services/PriceCalculationService.php

namespace App\Services;

class PriceCalculationService
{
    private float $markupPercentage;
    private float $markupFixed;

    public function __construct()
    {
        $this->markupPercentage = config('duffel.service.markup_percentage', 5);
        $this->markupFixed = config('duffel.service.markup_fixed', 0);
    }

    /**
     * Calculate service charge and total
     */
    public function calculate(float $basePrice): array
    {
        $serviceCharge = round(($basePrice * $this->markupPercentage / 100) + $this->markupFixed, 2);
        $totalPrice = round($basePrice + $serviceCharge, 2);

        return [
            'base_price' => $basePrice,
            'service_charge' => $serviceCharge,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Get markup percentage
     */
    public function getMarkupPercentage(): float
    {
        return $this->markupPercentage;
    }

    /**
     * Format price for display
     */
    public function formatPrice(float $amount, string $currency = 'USD'): string
    {
        return number_format($amount, 2) . ' ' . strtoupper($currency);
    }
}