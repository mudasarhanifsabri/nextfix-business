<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_themes', function (Blueprint $table): void {
            $table->id();
            $table->string('scope')->unique();
            $table->json('configuration');
            $table->json('previous_configuration')->nullable();
            $table->unsignedInteger('revision')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        foreach (['company', 'worker', 'admin'] as $scope) {
            DB::table('app_themes')->insert([
                'scope' => $scope,
                'configuration' => json_encode(['preset' => 'classic', 'appearance' => 'light', 'primary' => '#1267e8', 'secondary' => '#6758d4']),
                'revision' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_themes');
    }
};
