<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->integer('reported_day')->nullable();
            $table->integer('reported_month')->nullable();
            $table->integer('reported_year')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->dropColumn([
                'reported_day',
                'reported_month',
                'reported_year',
            ]);
        });
    }
};
