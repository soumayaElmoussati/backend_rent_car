<?php

// app/Models/Accident.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Accident extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'incident_date',
        'location',
        'severity',
        'driver_name',
        'estimated_repair_cost',
        'description',
        'police_report_filed',
        'insurance_claim_filed',
        'status',
        'actual_repair_cost',
        'insurance_payout',
        'internal_notes'
    ];

    protected $casts = [
        'incident_date' => 'date',
        'estimated_repair_cost' => 'decimal:2',
        'actual_repair_cost' => 'decimal:2',
        'insurance_payout' => 'decimal:2',
        'police_report_filed' => 'boolean',
        'insurance_claim_filed' => 'boolean',
    ];

    // Relation inverse
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Accesseur pour le libellé de la sévérité
    public function getSeverityLabelAttribute(): string
    {
        $labels = [
            'minor' => 'Mineur',
            'moderate' => 'Modéré',
            'severe' => 'Sévère',
            'total_loss' => 'Perte totale',
        ];

        return $labels[$this->severity] ?? $this->severity;
    }

    // Accesseur pour le libellé du statut
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'reported' => 'Signalé',
            'under_review' => 'En révision',
            'resolved' => 'Résolu',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}