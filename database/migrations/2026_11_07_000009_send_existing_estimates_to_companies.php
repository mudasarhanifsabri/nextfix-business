<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')->where('workflow_stage', 'admin_review')->update([
            'workflow_stage' => 'awaiting_customer_approval',
            'status' => 'awaiting_customer_approval',
            'quotation_sent_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Customer decisions must never be rolled back automatically.
    }
};
