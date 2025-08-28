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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            // Foreign keys
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade'); // delete review if user is deleted

            $table->foreignId('address_id')
                  ->constrained()
                  ->onDelete('cascade'); // delete review if address is deleted

            // Review details
            $table->string('cafe_shop_name');
            $table->unsignedTinyInteger('rating'); // 1–5 (fits in tinyint)
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
