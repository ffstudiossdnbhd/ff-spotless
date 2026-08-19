<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('task_templates', 'days_of_week')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->json('days_of_week')->nullable()->after('task_session_id');
            });
        }

        DB::table('task_templates')
            ->whereNull('days_of_week')
            ->update([
                'days_of_week' => json_encode([1, 2, 3, 4, 5]),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('task_templates', 'days_of_week')) {
            Schema::table('task_templates', function (Blueprint $table) {
                $table->dropColumn('days_of_week');
            });
        }
    }
};
