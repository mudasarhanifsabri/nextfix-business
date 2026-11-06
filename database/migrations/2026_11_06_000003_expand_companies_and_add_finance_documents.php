<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('trade_license_number')->nullable()->after('legal_name');
            $table->string('industry')->nullable()->after('trn');
            $table->string('email')->nullable()->after('contact_name');
            $table->string('billing_email')->nullable()->after('phone');
            $table->string('city')->nullable()->after('billing_address');
            $table->string('country')->default('United Arab Emirates')->after('city');
            $table->unsignedSmallInteger('payment_terms')->default(30)->after('country');
        });
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->char('currency', 3)->default('AED');
            $table->timestamps();
        });
        Schema::create('company_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->string('file_path')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_documents');
        Schema::dropIfExists('invoices');
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['legal_name', 'trade_license_number', 'industry', 'email', 'billing_email', 'city', 'country', 'payment_terms']));
    }
};
