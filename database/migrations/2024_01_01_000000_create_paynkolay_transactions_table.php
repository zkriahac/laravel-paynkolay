<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynkolay_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->unique();
            $table->string('client_ref_code')->index();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('TRY');
            $table->string('status');
            $table->string('transaction_type');
            $table->integer('installment')->default(1);
            $table->boolean('use_3d')->default(true);
            $table->string('card_holder_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_brand')->nullable();
            $table->string('customer_key')->nullable()->index();
            $table->string('ip_address')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->json('callback_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynkolay_transactions');
    }
};