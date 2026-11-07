<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('bookings', function (Blueprint $table): void {
        $table->string('workflow_stage')->default('inspection')->after('progress');
        $table->string('inspection_condition')->nullable(); $table->json('inspection_issues')->nullable(); $table->text('inspection_report')->nullable();
        $table->decimal('labour_hours', 6, 2)->nullable(); $table->unsignedInteger('labour_amount_minor')->default(0); $table->unsignedInteger('parts_amount_minor')->default(0);
        $table->text('parts_required')->nullable(); $table->text('estimate_note')->nullable(); $table->timestamp('estimate_submitted_at')->nullable(); $table->timestamp('estimate_approved_at')->nullable();
    }); }
    public function down(): void { Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['workflow_stage','inspection_condition','inspection_issues','inspection_report','labour_hours','labour_amount_minor','parts_amount_minor','parts_required','estimate_note','estimate_submitted_at','estimate_approved_at'])); }
};
