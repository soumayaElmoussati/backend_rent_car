<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            
            // Détails de l'accident
            $table->date('incident_date'); // Date de l'accident
            $table->string('location')->nullable(); // Lieu
            $table->string('severity')->default('moderate'); // minor, moderate, severe, total_loss
            $table->string('driver_name')->nullable(); // Conducteur
            $table->decimal('estimated_repair_cost', 10, 2)->nullable(); // Coût estimé
            $table->text('description'); // Description détaillée
            
            // Documents et assurances
            $table->boolean('police_report_filed')->default(false); // Rapport de police établi
            $table->boolean('insurance_claim_filed')->default(false); // Déclaration d'assurance
            
            // Statut du dossier
            // reported (Signalé), under_review (En révision), resolved (Résolu)
            $table->string('status')->default('reported');
            
            // Suivi financier réel
            $table->decimal('actual_repair_cost', 10, 2)->nullable();
            $table->decimal('insurance_payout', 10, 2)->nullable();
            
            // Notes internes
            $table->text('internal_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accidents');
    }
};
