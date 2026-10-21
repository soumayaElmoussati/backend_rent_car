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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            
            // Relations
            $table->foreignId('vehicle_id')->constrained()->onDelete('restrict'); // Ne pas supprimer un véhicule loué
            $table->foreignId('client_id')->constrained()->onDelete('restrict');
            
            // Dates et Lieux
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->string('pickup_location');
            $table->boolean('is_different_return_location')->default(false);
            $table->string('return_location')->nullable();
            
            // Tarification
            $table->decimal('calculated_rate', 10, 2); // Tarif calculé par le système (avant modif)
            $table->boolean('is_negotiated_price')->default(false);
            $table->decimal('negotiated_price', 10, 2)->nullable();
            $table->boolean('has_commercial_discount')->default(false);
            $table->decimal('discount_amount', 10, 2)->nullable();
            
            $table->decimal('subtotal', 10, 2); // Sous-total
            $table->decimal('total_amount', 10, 2); // Montant total final
            $table->decimal('deposit_amount', 10, 2)->default(0); // Caution
            
            // Détails
            $table->string('source')->default('En personne'); // En personne, Téléphone, Site web, etc.
            $table->text('internal_notes')->nullable(); // Notes internes (non visibles client)
            $table->text('client_notes')->nullable(); // Notes pour le client (visibles contrat)
            
            // Statut de la réservation
            // pending, confirmed, ongoing, completed, cancelled
            $table->string('status')->default('confirmed'); 
            $table->timestamps();
             $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
