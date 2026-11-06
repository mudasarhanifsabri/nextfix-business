<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique()->after('number');
            $table->json('line_items')->nullable()->after('status');
            $table->unsignedBigInteger('subtotal_minor')->default(0)->after('line_items');
            $table->unsignedBigInteger('tax_minor')->default(0)->after('subtotal_minor');
            $table->text('notes')->nullable()->after('currency');
        });
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('method');
            $table->string('status')->default('completed');
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3)->default('AED');
            $table->timestamp('paid_at');
            $table->string('gateway_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('payments');
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn(['public_token', 'line_items', 'subtotal_minor', 'tax_minor', 'notes']));
    }
};
