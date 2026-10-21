<?php

// app/Services/ReservationPricingService.php

namespace App\Services;

use App\Models\Vehicle;
use Carbon\Carbon;

class ReservationPricingService
{
    /**
     * Calcule le tarif de base selon la durée et le véhicule.
     */
    public function calculateRate(Vehicle $vehicle, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $days = $start->diffInDays($end);
        if ($days == 0) $days = 1; // Minimum 1 jour facturé

        $subtotal = 0;

        // Logique de tarification dégressive
        if ($days >= 30 && $vehicle->monthly_rate) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            $subtotal = ($months * $vehicle->monthly_rate) + ($remainingDays * $vehicle->daily_rate);
        } elseif ($days >= 7 && $vehicle->weekly_rate) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;
            $subtotal = ($weeks * $vehicle->weekly_rate) + ($remainingDays * $vehicle->daily_rate);
        } else {
            $subtotal = $days * $vehicle->daily_rate;
        }

        return [
            'days' => $days,
            'subtotal' => $subtotal,
            'deposit_amount' => $vehicle->deposit_amount,
        ];
    }
}