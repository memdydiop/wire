<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PromotionType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('type');
            $table->decimal('value', 10, 2);
            $table->decimal('minimum_purchase', 10, 2)->nullable();
            $table->integer('max_uses')->nullable();
            $table->integer('max_uses_per_customer')->nullable();
            $table->integer('current_uses')->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->json('applicable_products')->nullable();
            $table->json('applicable_categories')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('combinable')->default(false);
            $table->timestamps();
            
            $table->index(['code', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};