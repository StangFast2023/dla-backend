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
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->unsignedInteger('start_rank')
                ->nullable()
                ->comment('ลำดับเริ่มต้นของการเรียก');

            $table->unsignedInteger('end_rank')
                ->nullable()
                ->comment('ลำดับสิ้นสุดของการเรียก');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->dropColumn(['start_rank', 'end_rank']);
        });
    }
};
