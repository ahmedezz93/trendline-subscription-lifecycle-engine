<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('billing_cycle'); // 'monthly', 'yearly'
            $table->string('currency');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['plan_id', 'billing_cycle', 'currency'], 'plan_cycle_currency_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_variants');
    }
};
