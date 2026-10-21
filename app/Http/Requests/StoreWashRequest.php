<?php

// app/Http/Requests/StoreWashRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'type' => ['required', Rule::in([
                'exterior', 
                'interior', 
                'complete', 
                'deep_clean', 
                'detailing'
            ])],
            'completed_date' => ['required', 'date', 'before_or_equal:today'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'service_provider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'reservation_id' => ['nullable', 'exists:reservations,id'],
        ];
    }
}