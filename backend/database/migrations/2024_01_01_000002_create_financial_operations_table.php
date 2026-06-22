<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('status')->default('completed');
            $table->string('idempotency_key')->unique();
            $table->foreignId('initiated_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('reverses_id')->nullable()->constrained('financial_operations')->nullOnDelete();
            $table->foreignId('reversed_by_id')->nullable()->constrained('financial_operations')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['initiated_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_operations');
    }
};
