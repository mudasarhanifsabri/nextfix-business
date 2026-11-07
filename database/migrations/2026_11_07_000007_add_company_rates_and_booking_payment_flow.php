<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table): void { $table->unsignedInteger('inspection_rate_minor')->default(0); $table->unsignedInteger('checkout_cleaning_rate_minor')->default(0); $table->unsignedInteger('regular_cleaning_rate_minor')->default(0); $table->unsignedInteger('deep_cleaning_rate_minor')->default(0); });
        Schema::table('bookings', function (Blueprint $table): void { $table->unsignedInteger('inspection_fee_minor')->default(0); $table->timestamp('inspection_fee_paid_at')->nullable(); $table->string('inspection_payment_url', 1000)->nullable(); $table->foreignId('quotation_invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); $table->string('quotation_payment_url', 1000)->nullable(); });
        // Existing inspected maintenance jobs predate fee tracking; preserve their completed inspection as paid credit.
        DB::table('bookings')->where('service_type', 'maintenance')->whereNotIn('workflow_stage', ['inspection'])->orderBy('id')->each(function ($booking): void { $rate = DB::table('companies')->where('id', $booking->company_id)->value('inspection_rate_minor') ?? 15000; DB::table('bookings')->where('id', $booking->id)->update(['inspection_fee_minor'=>$rate,'inspection_fee_paid_at'=>$booking->estimate_submitted_at ?? $booking->created_at]); });
        DB::table('bookings')->where('workflow_stage', 'awaiting_customer_approval')->orderBy('id')->each(function ($booking): void { foreach (DB::table('users')->where('company_id', $booking->company_id)->get(['id']) as $user) { DB::table('notifications')->insert(['id'=>(string) Str::uuid(),'type'=>'App\\Notifications\\AdminAlert','notifiable_type'=>'App\\Models\\User','notifiable_id'=>$user->id,'data'=>json_encode(['title'=>'Quotation ready for approval','message'=>$booking->reference.' is ready. Review the inspection, fee credit and remaining amount.','url'=>'/bookings','level'=>'warning']),'read_at'=>null,'created_at'=>now(),'updated_at'=>now()]); } });
    }
    public function down(): void { Schema::table('bookings', function (Blueprint $table): void { $table->dropConstrainedForeignId('quotation_invoice_id'); $table->dropColumn(['inspection_fee_minor','inspection_fee_paid_at','inspection_payment_url','quotation_payment_url']); }); Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['inspection_rate_minor','checkout_cleaning_rate_minor','regular_cleaning_rate_minor','deep_cleaning_rate_minor'])); }
};
