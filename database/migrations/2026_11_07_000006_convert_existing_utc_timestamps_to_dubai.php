<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Existing system-generated values were written while the app used UTC. */
    public function up(): void
    {
        $tables = [
            'users' => ['email_verified_at', 'created_at', 'updated_at'],
            'companies' => ['created_at', 'updated_at'],
            'units' => ['created_at', 'updated_at'],
            // scheduled_at is intentionally excluded: customers entered it as Dubai wall time.
            'bookings' => ['estimate_submitted_at', 'estimate_approved_at', 'quotation_sent_at', 'customer_decision_at', 'work_started_at', 'work_completed_at', 'created_at', 'updated_at'],
            'professionals' => ['created_at', 'updated_at'],
            'invoices' => ['created_at', 'updated_at'],
            'company_documents' => ['created_at', 'updated_at'],
            'payments' => ['paid_at', 'created_at', 'updated_at'],
            'notifications' => ['read_at', 'created_at', 'updated_at'],
            'deployment_runs' => ['started_at', 'finished_at', 'created_at', 'updated_at'],
            'integration_settings' => ['created_at', 'updated_at'],
        ];

        foreach ($tables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
            if ($columns === []) {
                continue;
            }
            DB::table($table)->select(array_merge(['id'], $columns))->orderBy('id')->chunk(200, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];
                    foreach ($columns as $column) {
                        if ($row->{$column} !== null) {
                            $updates[$column] = CarbonImmutable::parse($row->{$column}, 'UTC')->setTimezone('Asia/Dubai')->format('Y-m-d H:i:s');
                        }
                    }
                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        // Historical timezone normalization is intentionally irreversible.
    }
};
