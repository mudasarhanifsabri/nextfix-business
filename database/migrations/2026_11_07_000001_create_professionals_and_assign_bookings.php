<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professionals', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('role');
            $table->string('email')->nullable()->unique();
            $table->string('phone');
            $table->string('status')->default('available');
            $table->json('skills')->nullable();
            $table->json('working_days')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('professional_id')->nullable()->after('unit_id')->constrained('professionals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropConstrainedForeignId('professional_id'));
        Schema::dropIfExists('professionals');
    }
};
