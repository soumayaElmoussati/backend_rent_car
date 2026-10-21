<?php

// app/Models/Wash.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wash extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'type',
        'completed_date',
        'cost',
        'service_provider',
        'notes',
        'reservation_id'
    ];

    protected $casts = [
        'completed_date' => 'date',
        'cost' => 'decimal:2',
    ];

    // Relation inverse
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    // Accesseur pour le libellé du type de lavage
    public function getTypeLabelAttribute(): string
    {
        $types = [
            'exterior' => 'Extérieur',
            'interior' => 'Intérieur',
            'complete' => 'Complet',
            'deep_clean' => 'Nettoyage profond',
            'detailing' => 'Detailing',
        ];

        return $types[$this->type] ?? $this->type;
    }
}