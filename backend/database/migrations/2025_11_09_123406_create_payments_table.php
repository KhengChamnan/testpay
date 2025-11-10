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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('tran_id')->unique();
            $table->string('payment_status')->default('pending'); // pending, paid, failed, expired
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('payment_option')->nullable(); // abapay_khqr, abapay_deeplink, etc.
            $table->text('qr_string')->nullable();
            $table->text('deeplink')->nullable();
            $table->text('callback_data')->nullable(); // Store full callback response
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
