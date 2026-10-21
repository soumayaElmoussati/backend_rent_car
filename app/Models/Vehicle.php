<?php

// app/Models/Vehicle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'registration_number',
        'brand',
        'model',
        'year',
        'category',
        'transmission',
        'fuel_type',
        'seats',
        'doors',
        'color',
        'daily_rate',
        'deposit_amount',
        'weekly_rate',
        'monthly_rate',
        'status',
        'is_public',
        'photos',
        'internal_notes',
        'purchase_type',
        'purchase_date',
        'purchase_price'
    ];

    protected $casts = [
        'photos' => 'array', // Convertit automatiquement le JSON en tableau PHP
        'is_public' => 'boolean',
        'purchase_date' => 'date',
    ];

    // Accesseur pour le nom complet du véhicule
    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model} ({$this->registration_number})";
    }
}