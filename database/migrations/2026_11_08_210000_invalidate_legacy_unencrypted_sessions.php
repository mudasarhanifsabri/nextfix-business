<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy plaintext sessions before production session encryption is enforced.
     */
    public function up(): void
    {
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->delete();
        }
    }

    public function down(): void
    {
        // Invalidated authentication sessions cannot and should not be restored.
    }
};
