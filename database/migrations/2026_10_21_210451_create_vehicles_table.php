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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique(); // Immatriculation
            $table->string('brand'); // Marque
            $table->string('model'); // Modèle
            $table->integer('year'); // Année
            $table->string('category')->default('economique'); 
            $table->string('transmission')->default('manuelle'); 
            $table->string('fuel_type')->nullable();
            $table->integer('seats')->default(5); // Places
            $table->integer('doors')->default(5); // Portes
            $table->string('color')->nullable();
            $table->decimal('daily_rate', 10, 2)->default(0); // Tarif journalier
            $table->decimal('deposit_amount', 10, 2)->default(0); // Dépôt
            $table->decimal('weekly_rate', 10, 2)->nullable(); // Tarif hebdo
            $table->decimal('monthly_rate', 10, 2)->nullable(); // Tarif mensuel
            
            // Statut et Visibilité
            $table->string('status')->default('available'); // available, rented, maintenance, archived
            $table->boolean('is_public')->default(false); // Visible sur le portail public
            
            // Médias et Notes
            $table->json('photos')->nullable(); // Stockage des chemins d'images (max 8)
            $table->text('internal_notes')->nullable(); // Notes internes
            
            // Achat et Financement
            $table->string('purchase_type')->nullable(); // full_payment, bank_loan
            $table->date('purchase_date')->nullable(); // Date d'achat
            $table->decimal('purchase_price', 10, 2)->nullable(); // Prix d'achat
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
