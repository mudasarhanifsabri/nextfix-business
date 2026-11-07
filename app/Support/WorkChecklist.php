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

        return $items + [
            'bathrooms' => 'Bathroom(s)',
            'kitchen' => 'Kitchen',
            'living' => 'Living area',
            'linen' => 'Linen & towels',
            'supplies' => 'Guest supplies',
            'floors' => 'Floors & balcony',
            'guest_ready' => 'Final guest-ready check',
        ];
    }
}
