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
        Schema::create('positions_dla', function (Blueprint $table) {
            $table->id();
            $table->integer('id_position');
            $table->string('name', 100);
            $table->integer('id_prefix');
            $table->integer('id_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions_dla');
    }
};
