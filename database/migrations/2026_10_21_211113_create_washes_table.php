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
        Schema::create('washes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            
            // Détails du lavage
            $table->string('type'); // exterior, interior, complete, deep_clean, detailing
            $table->date('completed_date'); // Date d'exécution
            $table->decimal('cost', 10, 2)->nullable(); // Coût (MAD)
            $table->string('service_provider')->nullable(); // Effectué par (personne/entreprise)
            $table->text('notes')->nullable(); // Notes additionnelles
            
            // Suivi (optionnel, pour savoir si c'est lié à une réservation)
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('washes');
    }
};
