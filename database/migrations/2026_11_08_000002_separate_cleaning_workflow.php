<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')->where('service_type', 'cleaning')->where('status', '!=', 'completed')->update(['workflow_stage' => 'work']);
        DB::table('bookings')->where('service_type', 'cleaning')->where('status', 'completed')->update(['workflow_stage' => 'complete']);
    }

    public function down(): void
    {
        DB::table('bookings')->where('service_type', 'cleaning')->update(['workflow_stage' => 'inspection']);
    }
};
