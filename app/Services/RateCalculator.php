<?php

namespace App\Services;

use App\Models\RateCard;
use Illuminate\Support\Facades\Cache;

class RateCalculator
{
    public function quote(array $input): array
    {
        $key = 'rate:quote:'.md5(json_encode($input));

        return Cache::remember($key, 300, function () use ($input) {
            $kind = $input['kind'] ?? 'sea';
            $card = RateCard::query()->with(['versions' => fn ($q) => $q->orderByDesc('version')->limit(1)])
                ->where('kind', $kind)
                ->where('active', true)
                ->first();

            $amount = 0;
            $version = $card?->versions->first();
            if ($version) {
                $version->load('items');
                foreach ($version->items as $item) {
                    $dims = $item->dimensions ?? [];
                    $match = true;
                    foreach ($dims as $k => $v) {
                        if (isset($input[$k]) && (string) $input[$k] !== (string) $v) {
                            $match = false;
                            break;
                        }
                    }
                    if ($match) {
                        $amount += (float) $item->amount;
                    }
                }
            }

            return [
                'kind' => $kind,
                'amount' => round($amount, 2),
                'currency' => 'USD',
                'breakdown' => ['base' => $amount],
            ];
        });
    }
}
