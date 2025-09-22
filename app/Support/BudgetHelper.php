<?php

namespace App\Support;

use App\Models\Contract;
use Carbon\Carbon;

class BudgetHelper
{
    public static function getTotaleAllocatoPerAnno(int $anno): float
    {
        $contratti = Contract::all();
        $totale = 0;

        foreach ($contratti as $contratto) {
            $inizio = Carbon::parse($contratto->start_date)->startOfMonth();
            $fine = Carbon::parse($contratto->end_date)->endOfMonth();

            if ($inizio->year > $anno || $fine->year < $anno) {
                continue;
            }

            $durataTotaleMesi = $inizio->startOfMonth()->diffInMonths($fine->endOfMonth());
            if ($durataTotaleMesi === 0)
                continue;

            $quotaMensile = $contratto->amount_total / $durataTotaleMesi;

            $startAnno = Carbon::create($anno, 1, 1)->startOfMonth();
            $endAnno = Carbon::create($anno, 12, 31)->endOfMonth();

            $intersezioneInizio = $inizio->gt($startAnno) ? $inizio : $startAnno;
            $intersezioneFine = $fine->lt($endAnno) ? $fine : $endAnno;

            if ($intersezioneInizio->gt($intersezioneFine)) {
                continue;
            }

            $mesiNellAnno = $intersezioneInizio->startOfMonth()->diffInMonths($intersezioneFine->endOfMonth());

            if ($mesiNellAnno > 0) {
                $totale += $quotaMensile * $mesiNellAnno;
            }
        }

        return $totale;
    }
}