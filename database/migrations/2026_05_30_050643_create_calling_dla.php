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
        Schema::create('calling_dla', function (Blueprint $table) {
            $table->id();
            $table->integer('id_main_province');
            $table->integer('id_sub_province');
            $table->integer('id_position');
            $table->integer('round');
            $table->string('total', 50);
            $table->boolean('call_status')->default(false);
            $table->boolean('list_status')->default(false);
            $table->integer('called_day');
            $table->integer('called_month');
            $table->integer('called_year');
            $table->boolean('is_cross_region')->default(false);
            $table->integer('crossed_region')->nullable();
            $table->integer('crossed_zone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calling_dla');
    }
};
