<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('trn')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('company_manager')->after('password');
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('building');
            $table->string('unit_number');
            $table->unsignedTinyInteger('bedrooms')->default(1);
            $table->unsignedInteger('size_sqft')->nullable();
            $table->text('address');
            $table->text('access_notes')->nullable();
            $table->text('parking_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'building', 'unit_number']);
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->string('service_type');
            $table->string('service_name');
            $table->dateTime('scheduled_at');
            $table->string('status')->default('draft');
            $table->string('payment_status')->default('pending');
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->char('currency', 3)->default('AED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('units');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('company_id'));
        Schema::dropIfExists('companies');
    }
};
