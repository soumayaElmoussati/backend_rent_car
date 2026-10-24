<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // --- Paiement ---
            $table->decimal('deposit_received', 10, 2)->default(0)->after('deposit_amount');
            $table->decimal('paid_amount', 10, 2)->default(0)->after('deposit_received');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->boolean('generate_documents_now')->default(false)->after('payment_method');

            // --- Informations Contrat & Déclaration ---
            $table->date('driver_birth_date')->nullable();
            $table->string('driver_nationality')->nullable();
            $table->string('driver_address')->nullable();
            $table->string('driver_city')->nullable();
            $table->string('driver_postal_code')->nullable();
            
            $table->string('driver_cin')->nullable();
            $table->date('driver_cin_expiry')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_issue_date')->nullable();
            $table->date('driver_license_expiry')->nullable();
            
            $table->string('driver_passport_number')->nullable();
            $table->date('driver_passport_expiry')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'deposit_received', 'paid_amount', 'payment_method', 'generate_documents_now',
                'driver_birth_date', 'driver_nationality', 'driver_address', 'driver_city', 'driver_postal_code',
                'driver_cin', 'driver_cin_expiry', 'driver_license_number', 'driver_license_issue_date', 'driver_license_expiry',
                'driver_passport_number', 'driver_passport_expiry'
            ]);
        });
    }
};
