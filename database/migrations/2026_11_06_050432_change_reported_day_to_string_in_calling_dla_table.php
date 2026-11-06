<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->string('reported_day')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->integer('reported_day')->nullable()->change();
        });
    }
};
