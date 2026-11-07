<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class NomodPaymentLinks {
    public function ready(): bool { return config('services.nomod.mode') !== 'disabled' && filled(config('services.nomod.api_key')); }
    public function create(string $title, array $items, string $note, string $successUrl, string $failureUrl): ?string {
        if (! $this->ready()) return null;
        $response = Http::timeout(20)->withHeaders(['X-API-KEY' => config('services.nomod.api_key')])->post(rtrim(config('services.nomod.base_url'), '/').'/v1/links', ['currency'=>'AED','items'=>$items,'title'=>$title,'note'=>$note,'success_url'=>$successUrl,'failure_url'=>$failureUrl,'allow_tip'=>false,'shipping_address_required'=>false,'allow_service_fee'=>false,'payment_expiry_limit'=>1]);
        if (! $response->successful() || ! filled($response->json('url'))) throw new RuntimeException('Nomod could not create the payment link.');
        return $response->json('url');
    }
}
