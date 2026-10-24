<?php

// app/Models/Reservation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'vehicle_id',
        'client_id',
        'start_date',
        'end_date',
        'pickup_location',
        'is_different_return_location',
        'return_location',
        'calculated_rate',
        'is_negotiated_price',
        'negotiated_price',
        'has_commercial_discount',
        'discount_amount',
        'subtotal',
        'total_amount',
        'deposit_amount',
        'source',
        'internal_notes',
        'client_notes',
        'status',
        'deposit_received', 'paid_amount', 'payment_method', 'generate_documents_now',
        'driver_birth_date', 'driver_nationality', 'driver_address', 'driver_city', 'driver_postal_code',
        'driver_cin', 'driver_cin_expiry', 'driver_license_number', 'driver_license_issue_date', 'driver_license_expiry',
        'driver_passport_number', 'driver_passport_expiry',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_different_return_location' => 'boolean',
        'is_negotiated_price' => 'boolean',
        'has_commercial_discount' => 'boolean',
        'generate_documents_now' => 'boolean',
        'driver_birth_date' => 'date',
        'driver_cin_expiry' => 'date',
        'driver_license_issue_date' => 'date',
        'driver_license_expiry' => 'date',
        'driver_passport_expiry' => 'date',
    ];

    // Relations
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

  protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->reference)) {
                // On génère une référence unique basée sur l'année et le prochain ID
                // ou on utilise un timestamp pour être certain de l'unicité
                $lastReservation = self::withTrashed()->latest('id')->first();
                $nextId = $lastReservation ? $lastReservation->id + 1 : 1;
                $reservation->reference = 'RES-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}