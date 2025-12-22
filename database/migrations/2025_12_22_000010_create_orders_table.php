<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\FulfillmentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default(OrderStatus::PENDING->value);
            $table->string('type')->default(OrderType::IN_STORE->value);
            $table->string('fulfillment_method')->default(FulfillmentMethod::PICKUP->value);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('payment_status')->default(PaymentStatus::UNPAID->value);
            $table->string('payment_method')->default(PaymentMethod::CASH->value)->nullable();
            $table->dateTime('pickup_date')->nullable();
            $table->time('pickup_time')->nullable();
            $table->string('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->string('delivery_postal_code')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->boolean('requires_personalization')->default(false);
            $table->json('personalization_details')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['customer_id', 'status']);
            $table->index(['pickup_date', 'status']);
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};