
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->boolean('list_new_status')->default(false);
            $table->jsonb('rank')->nullable();

            $table->dropColumn([
                'start_rank',
                'end_rank',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('calling_dla', function (Blueprint $table) {
            $table->dropColumn([
                'list_new_status',
                'rank',
            ]);

            $table->unsignedInteger('start_rank')->nullable();
            $table->unsignedInteger('end_rank')->nullable();
        });
    }
};
