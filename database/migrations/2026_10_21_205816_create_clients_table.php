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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
             $table->enum('type', ['particulier', 'entreprise'])->default('particulier');
             $table->string('email')->unique()->nullable();
             $table->string('telephone')->nullable();
             $table->string('adresse')->nullable();
             $table->string('ville')->nullable();
             $table->text('notes_internes')->nullable();
             $table->string('nom_complet')->nullable();
             $table->string('cin')->nullable(); 
             $table->string('permis_conduire')->nullable(); 
             $table->string('nom_entreprise')->nullable();
             $table->string('identifiant_fiscal')->nullable(); 
             $table->string('nom_contact')->nullable(); 
             $table->boolean('est_blackliste')->default(false);
             $table->timestamps();
             $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
