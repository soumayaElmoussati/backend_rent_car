<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // À modifier si vous avez une gestion de permissions
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['particulier', 'entreprise'])],
            'email' => ['nullable', 'email', 'unique:clients,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:100'],
            'notes_internes' => ['nullable', 'string'],

            // Validation conditionnelle pour Particulier
            'nom_complet' => [
                'required_if:type,particulier',
                'nullable',
                'string',
                'max:255'
            ],
            'cin' => [
                'required_if:type,particulier',
                'nullable',
                'string',
                'max:50'
            ],
            'permis_conduire' => [
                'nullable', // Souvent demandé mais pas toujours obligatoire à la création
                'string',
                'max:50'
            ],

            // Validation conditionnelle pour Entreprise
            'nom_entreprise' => [
                'required_if:type,entreprise',
                'nullable',
                'string',
                'max:255'
            ],
            'identifiant_fiscal' => [
                'required_if:type,entreprise',
                'nullable',
                'string',
                'max:50'
            ],
            'nom_contact' => [
                'required_if:type,entreprise',
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }
}