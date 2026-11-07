<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_rate_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('AED');
            $table->unsignedInteger('maintenance_inspection_minor')->nullable();
            $table->json('rates')->nullable();
            $table->json('terms')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('review_requested_at')->nullable();
            $table->timestamps();
        });

        DB::table('companies')->update([
            'inspection_rate_minor' => 0,
            'checkout_cleaning_rate_minor' => 0,
            'regular_cleaning_rate_minor' => 0,
            'deep_cleaning_rate_minor' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('company_rate_cards');
    }
};
