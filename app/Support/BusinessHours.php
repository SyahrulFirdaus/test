<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Estimasi waktu balasan tim berdasarkan jam kerja (config leads.business_hours).
 * Hari libur nasional tidak diperhitungkan.
 */
class BusinessHours
{
    /** Kalimat estimasi balasan untuk halaman Terima Kasih. */
    public static function replyEstimate(?Carbon $now = null): string
    {
        $config = config('leads.business_hours');
        $now = ($now ?? now())->copy()->setTimezone($config['timezone']);

        [$openHour, $openMinute] = array_map('intval', explode(':', $config['open']));
        [$closeHour, $closeMinute] = array_map('intval', explode(':', $config['close']));
        $close = str_replace(':', '.', $config['close']);
        $open = str_replace(':', '.', $config['open']);

        $isWorkday = fn (Carbon $date) => in_array($date->dayOfWeekIso, $config['days'], true);

        $todayOpen = $now->copy()->setTime($openHour, $openMinute);
        $todayClose = $now->copy()->setTime($closeHour, $closeMinute);

        if ($isWorkday($now) && $now->betweenIncluded($todayOpen, $todayClose->copy()->subMinutes(30))) {
            return "Tim kami akan membalas pada jam kerja hari ini, paling lambat pukul {$close} WIB.";
        }

        if ($isWorkday($now) && $now->lessThan($todayOpen)) {
            return "Tim kami akan membalas pada jam kerja berikutnya: hari ini mulai pukul {$open} WIB.";
        }

        $next = $now->copy()->addDay()->setTime($openHour, $openMinute);

        for ($i = 0; $i < 7 && ! $isWorkday($next); $i++) {
            $next->addDay();
        }

        $date = $next->locale('id')->translatedFormat('l, j F Y');
        $day = $next->toDateString() === $now->copy()->addDay()->toDateString() ? 'besok, '.$date : $date;

        return "Tim kami akan membalas pada jam kerja berikutnya: {$day} mulai pukul {$open} WIB.";
    }
}
