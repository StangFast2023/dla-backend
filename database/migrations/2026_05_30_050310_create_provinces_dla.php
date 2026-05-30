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
        Schema::create('provinces_dla', function (Blueprint $table) {
            $table->id();
            $table->integer('id_main_province');
            $table->integer('id_sub_province');
            $table->string('main_name_province', 100);
            $table->string('sub_name_province', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces_dla');
    }
};
