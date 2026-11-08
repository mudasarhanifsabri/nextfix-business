<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('text');
            $table->text('body')->nullable();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'id']);
        });

        Schema::create('booking_message_reads', function (Blueprint $table): void {
            $table->foreignId('booking_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->primary(['booking_message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_message_reads');
        Schema::dropIfExists('booking_messages');
    }
};
