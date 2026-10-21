<?php

// app/Http/Requests/StoreMaintenanceRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
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
                'vidange', 'pneus', 'plaquettes', 'revision_technique', 
                'filtres', 'courroie', 'batterie', 'climatisation', 
                'amortisseurs', 'liquide_frein', 'liquide_refroidissement', 
                'essuie_glaces', 'phares', 'autre'
            ])],
            'scheduled_date' => ['required', 'date', 'after_or_equal:today'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'description' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }
}