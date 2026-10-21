<?php
// app/Http/Requests/StoreVehicleRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Identification
            'registration_number' => ['required', 'string', 'max:50', 'unique:vehicles,registration_number'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],

            // Caractéristiques
            'category' => ['required', Rule::in(['economique', 'citadine', 'suv', 'luxe'])],
            'transmission' => ['required', Rule::in(['manuelle', 'automatique'])],
            'fuel_type' => ['nullable', Rule::in(['essence', 'diesel', 'hybride', 'electrique'])],
            'seats' => ['required', 'integer', 'min:1', 'max:20'],
            'doors' => ['required', 'integer', 'min:2', 'max:6'],
            'color' => ['nullable', 'string', 'max:50'],

            // Tarification
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'weekly_rate' => ['nullable', 'numeric', 'min:0'],
            'monthly_rate' => ['nullable', 'numeric', 'min:0'],

            // Statut
            'status' => ['required', Rule::in(['available', 'rented', 'maintenance', 'archived'])],
            'is_public' => ['boolean'],

            // Médias (Photos)
            'photos' => ['nullable', 'array', 'max:8'], // Maximum 8 images
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'], // Max 5MB par image

            // Notes
            'internal_notes' => ['nullable', 'string'],

            // Achat et Financement
            'purchase_type' => ['nullable', Rule::in(['full_payment', 'bank_loan'])],
            'purchase_date' => ['nullable', 'date'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}