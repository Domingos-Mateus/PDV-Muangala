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
    Schema::create('cash_registers', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->unique(['store_id', 'name']);
    });

    Schema::create('cash_sessions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained();
        $table->foreignId('cash_register_id')->constrained();
        $table->foreignId('user_id')->constrained();
        $table->string('status', 10)->default('open');        // open, closed
        $table->decimal('opening_amount', 12, 2);
        $table->decimal('expected_amount', 12, 2)->nullable();
        $table->decimal('counted_amount', 12, 2)->nullable();
        $table->decimal('difference', 12, 2)->nullable();
        $table->text('notes')->nullable();
        $table->timestamp('opened_at');
        $table->timestamp('closed_at')->nullable();
        $table->timestamps();
        $table->index(['store_id', 'status']);
        $table->index(['user_id', 'status']);
    });

    Schema::create('cash_movements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained();
        $table->foreignId('cash_session_id')->constrained();
        $table->foreignId('user_id')->constrained();
        $table->string('type', 12);                           // supply, withdrawal
        $table->decimal('amount', 12, 2);
        $table->string('reason');
        $table->timestamps();
    });

    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->foreignId('store_id')->constrained();
        $table->foreignId('cash_session_id')->constrained();
        $table->foreignId('user_id')->constrained();           // quem vendeu
        $table->unsignedInteger('seq');                        // sequência por loja
        $table->string('number', 30);                          // ex.: LJ1-000123
        $table->decimal('subtotal', 12, 2);
        $table->decimal('discount', 12, 2)->default(0);
        $table->decimal('total', 12, 2);
        $table->string('status', 12)->default('completed');    // completed, cancelled
        $table->timestamp('cancelled_at')->nullable();
        $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
        $table->string('cancel_reason')->nullable();
        $table->timestamps();
        $table->unique(['store_id', 'seq']);
        $table->index(['store_id', 'created_at']);
        $table->index(['user_id', 'created_at']);
    });

    Schema::create('sale_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
        $table->foreignId('product_id')->constrained();
        $table->string('product_name');                        // fica como estava na hora da venda
        $table->decimal('qty', 12, 3);
        $table->decimal('unit_price', 12, 2);
        $table->decimal('unit_cost', 12, 2);
        $table->decimal('subtotal', 12, 2);
        $table->index('product_id');
    });

    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
        $table->string('method', 20);                          // cash, card, transfer
        $table->decimal('amount', 12, 2);                      // o que fica na venda/caixa
        $table->decimal('tendered', 12, 2)->nullable();        // o que o cliente entregou (dinheiro)
        $table->decimal('change_given', 12, 2)->default(0);    // troco devolvido
        $table->timestamps();
        $table->index('method');
    });
}
/**
     * Reverse the migrations.
     */
public function down(): void
{
    Schema::dropIfExists('payments');
    Schema::dropIfExists('sale_items');
    Schema::dropIfExists('sales');
    Schema::dropIfExists('cash_movements');
    Schema::dropIfExists('cash_sessions');
    Schema::dropIfExists('cash_registers');
}


};
