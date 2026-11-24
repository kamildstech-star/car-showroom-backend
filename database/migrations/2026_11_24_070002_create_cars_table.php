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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->string('model', 100);
            $table->string('variant', 100)->nullable();
            $table->string('slug', 191)->unique();
            $table->unsignedSmallInteger('year');
            $table->decimal('price', 14, 2);
            $table->unsignedInteger('mileage')->nullable();
            $table->unsignedInteger('engine_capacity')->nullable();
            $table->string('transmission', 20);
            $table->string('fuel_type', 20);
            $table->string('exterior_color', 50)->nullable();
            $table->string('registration_city', 100)->nullable();
            $table->string('assembly', 20)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('available');
            $table->string('publication_status', 20)->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Listing and filter performance indexes
            $table->index(['brand_id', 'status']);
            $table->index(['publication_status', 'status', 'published_at']);
            $table->index(['is_featured', 'publication_status', 'published_at']);
            $table->index('price');
            $table->index('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
