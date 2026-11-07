<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->string('rate_unit_type')->default('1_bhk')->after('unit_number');
        });

        DB::table('units')->orderBy('id')->each(function ($unit): void {
            $type = match (true) {
                (int) $unit->bedrooms <= 0 => 'studio',
                (int) $unit->bedrooms >= 5 => '5_bhk',
                default => ((int) $unit->bedrooms).'_bhk',
            };
            DB::table('units')->where('id', $unit->id)->update(['rate_unit_type' => $type]);
        });
    }

    public function down(): void
    {
        Schema::table('units', fn (Blueprint $table) => $table->dropColumn('rate_unit_type'));
    }
};
