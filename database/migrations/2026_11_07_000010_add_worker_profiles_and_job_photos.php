<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('professionals', fn (Blueprint $table) => $table->string('profile_photo_path')->nullable()->after('notes'));
        Schema::create('job_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 80)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('category')->default('inspection');
            $table->timestamp('captured_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_photos');
        Schema::table('professionals', fn (Blueprint $table) => $table->dropColumn('profile_photo_path'));
    }
};
