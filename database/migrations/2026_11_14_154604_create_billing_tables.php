<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 40)->nullable();
            $table->string('nif', 30)->nullable();
            $table->string('block', 40)->nullable();
            $table->string('unit', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['store_id', 'name']);
            $table->index(['store_id', 'phone']);
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('default_price', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['store_id', 'name']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('service_id')->constrained();
            $table->decimal('price', 12, 2);
            $table->string('frequency', 10);
            $table->unsignedSmallInteger('interval_count')->default(1);
            $table->date('start_date');
            $table->date('next_charge_date');
            $table->date('end_date')->nullable();
            $table->string('status', 10)->default('active');
            $table->timestamps();
            $table->index(['store_id', 'status', 'next_charge_date']);
        });

        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->string('status', 10)->default('pending');
            $table->timestamps();
            $table->unique(['subscription_id', 'period_start']);
            $table->index(['store_id', 'status', 'due_date']);
            $table->index(['customer_id', 'status']);
        });

        Schema::create('billing_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained();
            $table->foreignId('cash_session_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->unsignedInteger('seq');
            $table->string('number', 30);
            $table->decimal('total', 12, 2);
            $table->string('status', 12)->default('completed');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'seq']);
            $table->index(['store_id', 'created_at']);
        });

        Schema::create('billing_receipt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_receipt_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('tendered', 12, 2)->nullable();
            $table->decimal('change_given', 12, 2)->default(0);
            $table->timestamps();
            $table->index('method');
        });

        Schema::create('charge_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->index('charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_allocations');
        Schema::dropIfExists('billing_receipt_payments');
        Schema::dropIfExists('billing_receipts');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('services');
        Schema::dropIfExists('customers');
    }
};
