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
        Schema::create('ride_offers', function (Blueprint $table) {
            $table->id();
            $table->string('zip_code'); // PLZ - Required
            $table->string('city'); // Ort - Required
            $table->string('street')->nullable(); // Straße, Hausnummer - Optional
            $table->string('last_name'); // Name - Required
            $table->string('first_name')->nullable(); // Vorname - Optional
            $table->string('email'); // E-Mail - Required
            $table->string('class')->nullable(); // Klasse - Optional
            $table->string('phone')->nullable(); // Handynummer - Optional
            $table->date('valid_from')->nullable(); // Gültigkeitsdatum von - Optional
            $table->date('valid_until')->nullable(); // Gültigkeitsdatum bis - Optional
            $table->text('cost_info')->nullable(); // Kostenbeteiligung - Optional
            $table->text('additional_info')->nullable(); // Sonstiges - Optional
            $table->decimal('latitude', 10, 7)->nullable(); // For map display
            $table->decimal('longitude', 10, 7)->nullable(); // For map display
            $table->string('edit_code')->unique(); // Random code for editing the offer
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_offers');
    }
};
