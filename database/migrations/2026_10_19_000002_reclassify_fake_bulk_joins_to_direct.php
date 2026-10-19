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

            $events = DB::table('telegram_events')
                ->where('event_time', '>=', now()->subDays(2))
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
                ->get();

            foreach ($events as $ev) {
                DB::table('telegram_events')
                    ->where('id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'cta_click_id' => null,
                    ]);

                DB::table('conversions')
                    ->where('telegram_event_id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'status' => 'unverified',
                    ]);
            }
        }

        // 2. Reclassify any duplicate telegram_events claiming the SAME cta_click_id from recent events
        $events = DB::table('telegram_events')
            ->where('event_time', '>=', now()->subDays(2))
            ->whereNotNull('cta_click_id')
            ->orderBy('id', 'asc')
            ->get();

        $seenClicks = [];
        foreach ($events as $ev) {
            if (isset($seenClicks[$ev->cta_click_id])) {
                DB::table('telegram_events')
                    ->where('id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'cta_click_id' => null,
                    ]);

                DB::table('conversions')
                    ->where('telegram_event_id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'status' => 'unverified',
                    ]);
            } else {
                $seenClicks[$ev->cta_click_id] = $ev->id;
            }
        }

        // 3. Reclassify any duplicate telegram_events sharing the SAME invite_link in the same channel from recent events
        $eventsWithLink = DB::table('telegram_events')
            ->where('event_time', '>=', now()->subDays(2))
            ->whereNotNull('invite_link')
            ->where('invite_link', '!=', '')
            ->where('source', 'ads')
            ->orderBy('id', 'asc')
            ->get();

        $seenLinks = [];
        foreach ($eventsWithLink as $ev) {
            $key = $ev->telegram_channel_id . '_' . $ev->invite_link;
            if (isset($seenLinks[$key])) {
                DB::table('telegram_events')
                    ->where('id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'cta_click_id' => null,
                    ]);

                DB::table('conversions')
                    ->where('telegram_event_id', $ev->id)
                    ->update([
                        'source' => 'direct',
                        'campaign_id' => null,
                        'status' => 'unverified',
                    ]);
            } else {
                $seenLinks[$key] = $ev->id;
            }
        }
    }

    public function down(): void
    {
    }
};
