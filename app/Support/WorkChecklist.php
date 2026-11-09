<?php

namespace App\Support;

use App\Models\Booking;

final class WorkChecklist
{
    public static function for(Booking $booking): array
    {
        if ($booking->service_type !== 'cleaning') {
            return config('workflows.maintenance');
        }

        $items = ['access' => 'Enter unit'];
        if ((int) $booking->unit->bedrooms === 0) {
            $items['studio_room'] = 'Studio sleeping area';
        } else {
            foreach (range(1, max(1, (int) $booking->unit->bedrooms)) as $room) {
                $items['bedroom_'.$room] = 'Bedroom '.$room;
            }
        }
        if ($booking->unit->rate_unit_type === '3_bhk_maids') {
            $items['maid_room'] = 'Maid’s room';
        }

        $serviceCode = $booking->service_code;
        if (! $serviceCode) {
            $fullCode = RateCatalogue::bookingCode($booking->service_name, $booking->unit->rate_unit_type);
            $serviceCode = $fullCode ? str($fullCode)->afterLast('.')->toString() : null;
        }

        $items += [
            'bathrooms' => 'Bathroom(s)',
            'kitchen' => 'Kitchen',
            'living' => 'Living area',
            'floors' => 'Floors & balcony',
        ];

        if (in_array($serviceCode, ['normal_linen', 'client_in_stay', 'deep_linen', 'intensive_deep'], true)) {
            $items['linen'] = 'Linen & towels';
            $items['supplies'] = 'Guest supplies';
        }

        foreach ($booking->booking_items ?? [] as $item) {
            if (($item['kind'] ?? null) !== 'extra') {
                continue;
            }
            $key = 'extra_'.substr(md5((string) ($item['code'] ?? $item['label'] ?? 'item')), 0, 10);
            $items[$key] = ($item['label'] ?? 'Add-on').' × '.max(1, (int) ($item['quantity'] ?? 1));
        }

        $items['guest_ready'] = 'Final guest-ready check';

        return $items;
    }
}
