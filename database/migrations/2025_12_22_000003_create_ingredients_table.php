<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference')->unique()->nullable();
            $table->text('description')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit'); // kg, L, unité
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('quantity_in_stock', 10, 3)->default(0);
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->decimal('optimal_stock', 10, 3)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('storage_location')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(5.5);
            $table->boolean('is_allergenic')->default(false);
            $table->json('allergens')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};