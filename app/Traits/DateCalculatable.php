<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait DateCalculatable
{


    public function getAccountTimeline($isFullTimeline = false)
    {
        $start = Carbon::create(2026, 4, 15);
        $timeline = [];
        if ($isFullTimeline) {
            $end = $start->copy()->addYears(2);
        } else {
            $lastRecord = DB::table('calling_dla')
                ->orderBy('called_year', 'desc')
                ->orderBy('called_month', 'desc')
                ->first();
            $end = $lastRecord
                ? Carbon::create($lastRecord->called_year, $lastRecord->called_month, 1)->endOfMonth()
                : Carbon::now()->endOfMonth();
        }
        $current = $start->copy();
        while ($current->lte($end)) {
            $timeline[] = [
                'label'     => $current->translatedFormat('M Y'),
                'month'     => $current->month,
                'year'      => $current->year,
                'date'      => $current->month . '-' . $current->year,
                'name_s'    => $this->monthYearThai(false, $current->month, $current->year),
                'name_l'    => $this->monthYearThai(true, $current->month, $current->year),
                'data'      => []
            ];
            $current->addMonth();
        }
        return $timeline;
    }

    private function formatTimelineItem($date)
    {
        return [
            'label'    => $date->translatedFormat('M Y'),
            'month'    => $date->month,
            'year'     => $date->year,
            'date'     => $date->month . '-' . $date->year,
            'name_s'   => $this->monthYearThai(false, $date->month, $date->year),
            'name_l'   => $this->monthYearThai(true, $date->month, $date->year),
            'data'     => []
        ];
    }

    public function getAccountDaysStatus()
    {
        $startDate = Carbon::create(2026, 4, 15);
        $expiryDate = $startDate->copy()->addYears(2);
        $today = Carbon::now();
        $totalDays = $startDate->diffInDays($expiryDate);
        $daysPassed = $today->greaterThan($startDate) ? $startDate->diffInDays($today) : 0;
        $daysRemaining = $today->lessThan($expiryDate) ? $today->diffInDays($expiryDate) : 0;
        $percentage = ($daysPassed / $totalDays) * 100;
        return [
            'total_days'   => $totalDays,
            'current_date' => $today,
            'final_date'   => $expiryDate,
            'days_passed'  => $daysPassed,
            'days_remaining' => $daysRemaining,
            'percentage' => round($percentage, 2),
            'is_expired' => $today->greaterThanOrEqualTo($expiryDate),
        ];
    }

    /**
     * @param int $month
     * @param int $year
     */
    public function monthYearThai($default = true, $month, $year)
    {
        $month = (int)$month;
        $year  = (int)$year + 543;

        $nameLong  = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $nameShort = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];

        $nameMonth = $default === true ? $nameLong[$month] : $nameShort[$month];
        $nameYear  = $default === true ? $year : substr($year, -2);
        return $nameMonth . ' ' .  $nameYear;
    }
}
