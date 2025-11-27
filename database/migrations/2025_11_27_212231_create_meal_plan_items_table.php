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
        Schema::create('meal_plan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meal_plan_id')->constrained()->onDelete('cascade');

            $table->date('date');
            $table->enum('slot', ['breakfast', 'lunch', 'dinner']);
            $table->foreignId('recipe_id')->nullable()->constrained()->onDelete('set null');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_plan_items');
    }
};
