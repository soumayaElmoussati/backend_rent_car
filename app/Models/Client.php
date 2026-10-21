<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'email',
        'telephone',
        'adresse',
        'ville',
        'notes_internes',
        // Particulier
        'nom_complet',
        'cin',
        'permis_conduire',
        // Entreprise
        'nom_entreprise',
        'identifiant_fiscal',
        'nom_contact',
        'est_blackliste'
    ];

    protected $casts = [
        'est_blackliste' => 'boolean',
    ];

    // Accesseur pour avoir le nom à afficher peu importe le type
    public function getNomAffichageAttribute()
    {
        if ($this->type === 'entreprise') {
            return $this->nom_entreprise . ' (' . $this->nom_contact . ')';
        }
        return $this->nom_complet;
    }
}