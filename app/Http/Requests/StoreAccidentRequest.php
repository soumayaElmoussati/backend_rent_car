<?php

// app/Http/Requests/StoreAccidentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'location' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', Rule::in(['minor', 'moderate', 'severe', 'total_loss'])],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'estimated_repair_cost' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string'],
            'police_report_filed' => ['boolean'],
            'insurance_claim_filed' => ['boolean'],
            'status' => ['sometimes', Rule::in(['reported', 'under_review', 'resolved'])],
        ];
    }
}