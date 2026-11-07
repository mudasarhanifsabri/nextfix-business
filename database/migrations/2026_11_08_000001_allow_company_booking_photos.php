<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('job_photos', function (Blueprint $table): void {
            $table->dropForeign(['professional_id']);
            $table->foreignId('professional_id')->nullable()->change();
            $table->foreign('professional_id')->references('id')->on('professionals')->nullOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->after('professional_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_photos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('uploaded_by_user_id');
            $table->dropForeign(['professional_id']);
            $table->foreignId('professional_id')->nullable(false)->change();
            $table->foreign('professional_id')->references('id')->on('professionals')->cascadeOnDelete();
        });
    }
};
