<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professionals', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });
        Schema::table('bookings', function (Blueprint $table): void {
            $table->json('completed_items')->nullable();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('hold_reason')->nullable();
            $table->text('hold_note')->nullable();
            $table->timestamp('work_started_at')->nullable();
            $table->timestamp('work_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['completed_items', 'progress', 'hold_reason', 'hold_note', 'work_started_at', 'work_completed_at']);
        });
        Schema::table('professionals', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
    }
};
