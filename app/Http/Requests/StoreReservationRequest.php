<?php

// app/Http/Requests/StoreReservationRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Étape 1 : Véhicule & Client
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'client_id' => ['required', 'exists:clients,id'],

            // Étape 2 : Dates & Lieux
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'is_different_return_location' => ['boolean'],
            'return_location' => [
                'required_if:is_different_return_location,true',
                'nullable',
                'string',
                'max:255'
            ],

            // Étape 3 : Tarification
            'calculated_rate' => ['required', 'numeric', 'min:0'],
            'is_negotiated_price' => ['boolean'],
            'negotiated_price' => [
                'required_if:is_negotiated_price,true',
                'nullable',
                'numeric',
                'min:0'
            ],
            'has_commercial_discount' => ['boolean'],
            'discount_amount' => [
                'required_if:has_commercial_discount,true',
                'nullable',
                'numeric',
                'min:0'
            ],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],

            // Étape 4 : Détails
            'source' => ['required', 'string', 'max:100'],
            'internal_notes' => ['nullable', 'string'],
            'client_notes' => ['nullable', 'string'],
        ];
    }

    // Validation personnalisée pour vérifier la disponibilité du véhicule
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $vehicleId = $this->input('vehicle_id');
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($vehicleId && $startDate && $endDate) {
                $isOverlapping = \App\Models\Reservation::where('vehicle_id', $vehicleId)
                    ->where('status', '!=', 'cancelled')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                              ->orWhereBetween('end_date', [$startDate, $endDate])
                              ->orWhere(function ($q) use ($startDate, $endDate) {
                                  $q->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                              });
                    })->exists();

                if ($isOverlapping) {
                    $validator->errors()->add('vehicle_id', 'Ce véhicule est déjà réservé pour ces dates.');
                }
            }
        });
    }
}