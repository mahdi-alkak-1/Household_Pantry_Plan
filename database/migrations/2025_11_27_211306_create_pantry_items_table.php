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
        Schema::create('pantry_items', function (Blueprint $table) {
            $table->id();

             $table->foreignId('household_id')
              ->constrained()
              ->onDelete('cascade');

            $table->foreignId('ingredient_id')
                ->constrained()
                ->onDelete('cascade');      

            $table->integer('quantity')->default(1);
            $table->string('unit')->nullable();          
            $table->date('expiry_date')->nullable();
            $table->string('location')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pantry_items');
    }
};
