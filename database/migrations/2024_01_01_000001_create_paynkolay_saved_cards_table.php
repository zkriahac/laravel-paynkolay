<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paynkolay_saved_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('customer_key')->index();
            $table->string('tran_id');
            $table->text('token');
            $table->string('card_alias');
            $table->string('card_holder_name');
            $table->string('card_number_masked');
            $table->string('card_brand');
            $table->string('expiry_month');
            $table->string('expiry_year');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['user_id', 'tran_id']);
            $table->index(['customer_key', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paynkolay_saved_cards');
    }
};