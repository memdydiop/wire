<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProductionStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number')->unique();
            $table->date('production_date');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipe_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('planned_quantity');
            $table->integer('produced_quantity')->default(0);
            $table->integer('defective_quantity')->default(0);
            $table->string('status')->default(ProductionStatus::PLANNED->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('production_cost', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('quality_checks')->nullable();
            $table->timestamps();
            
            $table->index(['production_date', 'status']);
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};