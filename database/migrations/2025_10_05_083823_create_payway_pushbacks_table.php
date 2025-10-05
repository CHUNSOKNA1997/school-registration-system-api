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
        Schema::create('payway_pushbacks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('tran_id'); // PayWay transaction ID
            $table->string('apv')->nullable(); // Approval code
            $table->integer('status'); // PayWay status code (0 = success, others = error)
            $table->string('status_message')->nullable();
            $table->text('return_params')->nullable(); // Base64 encoded return params
            $table->json('data'); // Full webhook payload from PayWay

            $table->timestamps();

            // Indexes
            $table->index('uuid');
            $table->index('tran_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payway_pushbacks');
    }
};
