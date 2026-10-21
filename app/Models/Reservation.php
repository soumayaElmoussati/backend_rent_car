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
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_different_return_location' => 'boolean',
        'is_negotiated_price' => 'boolean',
        'has_commercial_discount' => 'boolean',
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

    // Générer une référence unique automatiquement
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($reservation) {
            if (empty($reservation->reference)) {
                $reservation->reference = 'RES-' . date('Y') . '-' . str_pad(Reservation::count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}