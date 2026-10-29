<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // ── Départ ──
            $table->integer('start_mileage')->nullable()->after('driver_passport_expiry');
            $table->integer('start_fuel_level')->nullable()->after('start_mileage');
            $table->text('start_notes')->nullable()->after('start_fuel_level');
            $table->json('start_damages')->nullable()->after('start_notes');
            $table->dateTime('actual_start_date')->nullable()->after('start_damages');

            // ── Retour ──
            $table->integer('end_mileage')->nullable()->after('actual_start_date');
            $table->integer('end_fuel_level')->nullable()->after('end_mileage');
            $table->text('end_notes')->nullable()->after('end_fuel_level');
            $table->json('end_damages')->nullable()->after('end_notes');
            $table->dateTime('actual_end_date')->nullable()->after('end_damages');

            // ── Dommages retour ──
            $table->boolean('requires_maintenance')->default(false)->after('actual_end_date');
            $table->text('damage_description')->nullable()->after('requires_maintenance');
            $table->decimal('damage_penalty', 10, 2)->nullable()->after('damage_description');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'start_mileage', 'start_fuel_level', 'start_notes', 'start_damages', 'actual_start_date',
                'end_mileage', 'end_fuel_level', 'end_notes', 'end_damages', 'actual_end_date',
                'requires_maintenance', 'damage_description', 'damage_penalty',
            ]);
        });
    }
};