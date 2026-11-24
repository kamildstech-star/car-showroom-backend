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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->string('customer_name', 100);
            $table->string('phone', 30);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->string('source', 50)->default('general');
            $table->timestamp('whatsapp_clicked_at')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Inquiry tracking and CRM indexes
            $table->index(['status', 'created_at']);
            $table->index('phone');
            $table->index('car_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
