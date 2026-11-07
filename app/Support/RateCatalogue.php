<?php

namespace App\Support;

class RateCatalogue
{
    public static function unitTypes(): array
    {
        return ['studio' => 'Studio', '1_bhk' => '1 BHK', '2_bhk' => '2 BHK', '3_bhk' => '3 BHK', '3_bhk_maids' => '3 BHK + maid’s room', '4_bhk' => '4 BHK', '5_bhk' => '5 BHK'];
    }

    public static function cleaningTypes(): array
    {
        return [
            'touch_up' => 'Touch-up cleaning',
            'normal_materials' => 'Normal cleaning + materials',
            'normal_linen' => 'Normal cleaning + linen, towels & amenities',
            'client_in_stay' => 'Client in-stay + linen, towels & amenities',
            'deep_materials' => 'Deep cleaning + materials',
            'deep_linen' => 'Deep cleaning + linen, towels & amenities',
            'intensive_deep' => 'Intensive deep cleaning + linen, towels & amenities',
        ];
    }

    public static function sections(): array
    {
        return [
            'additional_linen' => ['title' => 'Additional linen', 'unit' => 'per item', 'items' => [
                'pillow_case' => 'Pillow case', 'bed_sheet' => 'Bed sheet', 'duvet_cover' => 'Duvet cover', 'bath_mat' => 'Bath mat', 'face_towel' => 'Face towel', 'hand_towel' => 'Hand towel', 'bath_towel' => 'Bath towel', 'pool_towel' => 'Pool towel',
            ]],
            'basic_maintenance' => ['title' => 'Common basic maintenance', 'unit' => 'per service', 'items' => [
                'lavatory_kitchen' => 'Lavatory clogged — kitchen', 'lavatory_bathroom' => 'Lavatory clogged — bathroom', 'wc_clogged' => 'WC clogged', 'false_ceiling_lights' => 'Change false-ceiling lights (materials excluded)', 'ceiling_lights' => 'Install ceiling lights', 'door_handle' => 'Install door knob/handle (materials excluded)', 'diffuser_cleaning' => 'Clean diffusers (per set)', 'ac_service' => 'AC service (depends on AC type)',
            ]],
            'carpet' => ['title' => 'Carpet cleaning', 'unit' => 'by dimensions', 'items' => ['0_8x3' => '0.8 × 3 m', '1_5x2_3' => '1.5 × 2.3 m', '2x3' => '2 × 3 m', '3x4' => '3 × 4 m', '3x5' => '3 × 5 m']],
            'mattress' => ['title' => 'Mattress cleaning', 'unit' => 'complete package', 'items' => ['single' => 'Single (0.9 × 2 m)', 'double' => 'Double (1.4 × 2 m)', 'queen' => 'Queen (1.6 × 2 m)', 'king' => 'King (1.8 × 2 m)', 'super_king' => 'Super king (2 × 2 m)']],
            'sofa_bed' => ['title' => 'Sofa bed + cushions', 'unit' => 'by seats', 'items' => ['2_seater' => '2-seater', '3_seater' => '3-seater', '4_seater' => '4-seater']],
            'sofa' => ['title' => 'Sofa/armchair + cushions', 'unit' => 'complete package', 'items' => ['1_seater' => '1-seater', '2_seater' => '2-seater', '3_seater' => '3-seater', '4_seater' => '4-seater', '5_seater' => '5-seater']],
        ];
    }

    public static function codes(): array
    {
        $codes = [];
        foreach (self::unitTypes() as $unit => $label) {
            foreach (self::cleaningTypes() as $service => $serviceLabel) {
                $codes[] = "cleaning.{$unit}.{$service}";
            }
        }
        foreach (self::sections() as $section => $data) {
            foreach ($data['items'] as $item => $label) {
                $codes[] = "extras.{$section}.{$item}";
            }
        }

        return $codes;
    }

    public static function bookingCode(string $serviceName, int $bedrooms): string
    {
        $unit = match (true) { $bedrooms <= 0 => 'studio', $bedrooms >= 5 => '5_bhk', default => $bedrooms.'_bhk' };
        $service = match ($serviceName) {
            'Deep cleaning' => 'deep_materials',
            'Regular cleaning' => 'normal_materials',
            default => 'normal_linen',
        };

        return "cleaning.{$unit}.{$service}";
    }
}
