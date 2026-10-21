<?php

// app/Models/Maintenance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'type',
        'scheduled_date',
        'estimated_cost',
        'priority',
        'description',
        'internal_notes',
        'status',
        'completed_date',
        'actual_cost'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    // Relation inverse
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Accesseur pour le libellé du type
    public function getTypeLabelAttribute(): string
    {
        $types = [
            'vidange' => 'Vidange',
            'pneus' => 'Changement de pneus',
            'plaquettes' => 'Freins / Plaquettes',
            'revision_technique' => 'Contrôle technique',
            'filtres' => 'Filtres',
            'courroie' => 'Courroie de distribution',
            'batterie' => 'Batterie',
            'climatisation' => 'Climatisation',
            'amortisseurs' => 'Amortisseurs',
            'liquide_frein' => 'Liquide de frein',
            'liquide_refroidissement' => 'Liquide de refroidissement',
            'essuie_glaces' => 'Essuie-glaces',
            'phares' => 'Phares',
            'autre' => 'Autre',
        ];

        return $types[$this->type] ?? $this->type;
    }
}