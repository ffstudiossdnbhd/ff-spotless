<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('task_sessions')->where('name', '4:00 PM - 6:00 PM')->exists()) {
            return;
        }

        DB::table('task_sessions')
            ->where('name', '4:00 PM - 6:00PM')
            ->update(['name' => '4:00 PM - 6:00 PM']);
    }

    public function down(): void
    {
        // Do not restore a known formatting typo when rolling back.
    }
};
