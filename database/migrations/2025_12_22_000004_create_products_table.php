<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\DifficultyLevel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique()->nullable();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_price', 10, 2);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2);
            $table->decimal('margin_percentage', 5, 2)->nullable();
            $table->decimal('vat_rate', 5, 2)->default(5.5); // Taux réduit alimentaire standard
            $table->integer('preparation_time')->nullable();
            $table->integer('cooking_time')->nullable();
            $table->integer('shelf_life')->nullable(); // Durée de conservation en jours/heures
            $table->string('storage_type')->nullable();
            $table->json('allergens')->nullable();
            $table->json('nutritional_info')->nullable();
            $table->integer('portions')->default(1);
            $table->string('difficulty_level')->nullable()->default(DifficultyLevel::EASY->value);
            $table->boolean('is_seasonal')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_available')->default(true);
            $table->boolean('requires_advance_order')->default(false);
            $table->integer('advance_order_days')->nullable();
            $table->integer('daily_production_limit')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category_id', 'is_available']);
            $table->index('sku');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};