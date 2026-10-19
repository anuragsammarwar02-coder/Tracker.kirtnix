<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('telegram_events')) {
            return;
        }

        try {
            DB::statement('PRAGMA busy_timeout = 30000;');
        } catch (\Throwable $e) {}

        $reclassifyEventIds = [];

        // 1. Specific known panel/fake accounts from recent bulk additions
        $fakeNames = [
            'Moiga Else',
            'Steffie Brittney',
            'Aeriel Cookie',
            'Antonia Terrijo',
            'Mariya Sharline',
            'Ulla Barbee',
            'Esta Dorri',
            'Nicole Dinnie',
            'Shayna',
            'Lourdes',
            'Oeuch',
            'Phqbrian',
            'Eumge',
            'Mccandl',
            'Corsa',
            'Merrittl',
            'Radclyf',
            'Ozieh',
            'Denbo19',
            'Nessabec',
            'Jackson',
            'Berger',
            'Hychvafu',
        ];

        foreach ($fakeNames as $name) {
            $parts = explode(' ', trim($name));
            $fn = $parts[0] ?? $name;
            $ln = $parts[1] ?? null;

            $ids = DB::table('telegram_events')
                ->where(function ($q) {
                    $q->where('id', '>=', 470000)
                      ->orWhere('created_at', '>=', now()->subDays(1));
                })
                ->where(function ($q) use ($name, $fn, $ln) {
                    $q->where('first_name', $name)
                      ->orWhere('first_name', 'like', "%{$name}%")
                      ->orWhere(function ($sub) use ($fn, $ln) {
                          $sub->where('first_name', $fn);
                          if ($ln) {
                              $sub->where('last_name', $ln);
                          }
                      });
                    if ($ln) {
                        $q->orWhere(function ($sub2) use ($fn, $ln) {
                            $sub2->where('first_name', 'like', "%{$fn}%")
                                 ->where('last_name', 'like', "%{$ln}%");
                        });
                    }
                })
                ->pluck('id')
                ->toArray();

            foreach ($ids as $id) {
                $reclassifyEventIds[] = $id;
            }
        }

        // 2. Reclassify any duplicate telegram_events claiming the SAME cta_click_id from recent events
        $events = DB::table('telegram_events')
            ->where(function ($q) {
                $q->where('id', '>=', 470000)
                  ->orWhere('created_at', '>=', now()->subDays(1));
            })
            ->whereNotNull('cta_click_id')
            ->orderBy('id', 'asc')
            ->get(['id', 'cta_click_id']);

        $seenClicks = [];
        foreach ($events as $ev) {
            if (isset($seenClicks[$ev->cta_click_id])) {
                $reclassifyEventIds[] = $ev->id;
            } else {
                $seenClicks[$ev->cta_click_id] = $ev->id;
            }
        }

        // 3. Reclassify any duplicate telegram_events sharing the SAME invite_link in the same channel from recent events
        $eventsWithLink = DB::table('telegram_events')
            ->where(function ($q) {
                $q->where('id', '>=', 470000)
                  ->orWhere('created_at', '>=', now()->subDays(1));
            })
            ->whereNotNull('invite_link')
            ->where('invite_link', '!=', '')
            ->where('source', 'ads')
            ->orderBy('id', 'asc')
            ->get(['id', 'telegram_channel_id', 'invite_link']);

        $seenLinks = [];
        foreach ($eventsWithLink as $ev) {
            $key = $ev->telegram_channel_id . '_' . $ev->invite_link;
            if (isset($seenLinks[$key])) {
                $reclassifyEventIds[] = $ev->id;
            } else {
                $seenLinks[$key] = $ev->id;
            }
        }

        // Perform batch updates once
        if (!empty($reclassifyEventIds)) {
            $uniqueIds = array_values(array_unique($reclassifyEventIds));

            DB::table('telegram_events')
                ->whereIn('id', $uniqueIds)
                ->update([
                    'source' => 'direct',
                    'campaign_id' => null,
                    'cta_click_id' => null,
                ]);

            DB::table('conversions')
                ->whereIn('telegram_event_id', $uniqueIds)
                ->update([
                    'source' => 'direct',
                    'campaign_id' => null,
                    'status' => 'unverified',
                ]);
        }
    }

    public function down(): void
    {
    }
};
