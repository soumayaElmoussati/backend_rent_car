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
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            
            // Informations de maintenance
            $table->string('type'); // vidange, pneus, plaquettes, revision_technique, etc.
            $table->date('scheduled_date'); // Date prévue
            $table->decimal('estimated_cost', 10, 2)->nullable(); // Coût estimé
            $table->string('priority')->default('medium'); // low, medium, high, critical
            
            // Détails
            $table->text('description')->nullable(); // Description des travaux
            $table->text('internal_notes')->nullable(); // Notes internes
            
            // Statut de l'intervention
            // pending (en attente), in_progress (en cours), completed (terminé), cancelled (annulé)
            $table->string('status')->default('pending');
            
            // Suivi réel (rempli à la fin de l'intervention)
            $table->date('completed_date')->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->timestamps();
             $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
