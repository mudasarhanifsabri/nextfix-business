<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void { Schema::table('bookings', function (Blueprint $table): void { $table->timestamp('quotation_sent_at')->nullable(); $table->timestamp('customer_decision_at')->nullable(); $table->text('customer_decision_note')->nullable(); }); DB::table('bookings')->where('workflow_stage', 'awaiting_approval')->update(['workflow_stage' => 'admin_review', 'status' => 'estimate_review']); }
    public function down(): void { Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['quotation_sent_at','customer_decision_at','customer_decision_note'])); }
};
