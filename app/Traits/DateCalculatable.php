<?php

namespace App\Traits;

use Carbon\Carbon;

trait DateCalculatable
{


    public function getAccountTimeline()
    {
        $start = Carbon::create(2026, 4, 15);
        $end = $start->copy()->addYears(2);

        $timeline = [];
        while ($start->lte($end)) {
            $timeline[] = [
                'label'     =>  $start->translatedFormat('M Y'),
                'month'     =>  $start->month,
                'year'      =>  $start->year,
                'date'      =>  $start->month . '-' . $start->year,
                'name_s'    =>  $this->monthYearThai(false, $start->month, $start->year),
                'name_l'    =>  $this->monthYearThai(true, $start->month, $start->year),
                'data'      =>  []
            ];
            $start->addMonth();
        }
        return $timeline;
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
            'total_days' => $totalDays,
            'days_passed' => $daysPassed,
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
