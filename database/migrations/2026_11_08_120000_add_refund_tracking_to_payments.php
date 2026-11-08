<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->unsignedBigInteger('refund_amount_minor')->default(0)->after('amount_minor');
            $table->text('refund_reason')->nullable()->after('notes');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_reason');
            $table->timestamp('refunded_at')->nullable()->after('refund_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn(['refund_amount_minor', 'refund_reason', 'refund_requested_at', 'refunded_at']));
    }
};
