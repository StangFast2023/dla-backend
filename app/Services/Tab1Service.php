<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\CallingDla;
use App\Models\UpdateListDla;
use App\Models\ProvincesDla;
use App\Models\PrefixsDla;
use App\Models\PositionDla;
use App\Models\TypePositionDla;
use App\Traits\DateCalculatable;

class Tab1Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 1
        $Tab1_Part1_Static          =   $this->Tab1_Part1_Static();
        $getAccountDaysStatus       =   $this->getAccountDaysStatus();
        $Tab1_Part1_Static          =   array_merge($Tab1_Part1_Static, $getAccountDaysStatus);

        return [
            'part1' => $Tab1_Part1_Static,
            'part2' => $this->Tab1_Part2_Monthly(),
            'part3' => $this->Tab1_Part3_Cumulative(),
            'part4' => $this->Tab1_Part4_OtherStatic(),
            'part5' => $this->Tab1_Part5_PercentRound(),
            'part6' => $this->Tab1_Part6_TableRoundCall(),
        ];
    }

    public function Tab1_Part1_Static()
    {
        $CurRound   = CallingDla::max('round');
        $MaxRound   = 25;
        $TotalList  = UpdateListDla::sum(DB::raw('total::integer'));
        $TotalCall  = CallingDla::where('call_status', 1)->sum(DB::raw('total::integer'));
        $AVGCall = CallingDla::where('call_status', 1)
            ->select(DB::raw("
                concat(called_month || '/' || called_year) as monthly, 
                sum(total::integer) as total_per_month
            "))
            ->groupBy('monthly')
            ->get()
            ->avg('total_per_month');
        $array = [
            'CurRound'      =>  (int)$CurRound,
            'MaxRound'      =>  (int)$MaxRound,
            'TotalList'     =>  (int)$TotalList,
            'TotalCall'     =>  (int)$TotalCall,
            'AvgCall'       =>  (int)$AVGCall,
        ];
        return $array;
    }

    public function Tab1_Part2_Monthly()
    {
        $fullMonthly = $this->getAccountTimeline();

        $MonthlyRaw = CallingDla::where('call_status', 1)->get();
        $minMonth = $MonthlyRaw->min(fn($m) => $m->called_year * 12 + $m->called_month);
        $maxMonth = $MonthlyRaw->max(fn($m) => $m->called_year * 12 + $m->called_month);

        $array = [];
        foreach ($fullMonthly as $full) {
            $currentMonthValue = $full['year'] * 12 + $full['month'];
            if ($currentMonthValue >= $minMonth && $currentMonthValue <= $maxMonth) {
                $key_month_year = $full['month'] . '-' . $full['year'];
                $array[$key_month_year] = [
                    'month'           => $full['month'],
                    'year'            => $full['year'],
                    'label_eng'       => $full['label'],
                    'label_th_f'      => $this->monthYearThai(true, $full['month'], $full['year']),
                    'label_th_s'      => $this->monthYearThai(false, $full['month'], $full['year']),
                    'call_status'     => false,
                    'total_per_month' => 0,
                    'total_round'     => 0,
                    'data'            => []
                ];
            }
        }

        foreach ($MonthlyRaw as $month) {
            $key_month_year = $month->called_month . '-' . $month->called_year;
            if (isset($array[$key_month_year])) {
                $round = $month->round;
                $total = $month->total;
                if (!isset($array[$key_month_year]['data'][$round])) {
                    $array[$key_month_year]['total_round'] += 1;
                    $array[$key_month_year]['data'][$round] = ['round' => $round, 'total' => 0];
                }
                $array[$key_month_year]['call_status'] = true;
                $array[$key_month_year]['total_per_month'] += $total;
                $array[$key_month_year]['data'][$round]['total'] += $total;
            }
        }
        return $array;
    }

    public function Tab1_Part3_Cumulative()
    {
        $fullMonthly = $this->getAccountTimeline();
        $array = [];
        $Monthly    = CallingDla::all()->where('call_status', 1);
        $minMonth = $Monthly->min(fn($m) => $m->called_year * 12 + $m->called_month);
        $maxMonth = $Monthly->max(fn($m) => $m->called_year * 12 + $m->called_month);
        foreach ($fullMonthly as $full) {
            $currentMonthValue = $full['year'] * 12 + $full['month'];
            if ($currentMonthValue >= $minMonth && $currentMonthValue <= $maxMonth) {
                $key_month_year = $full['month'] . '-' . $full['year'];
                $array[$key_month_year] = [
                    'month'           => $full['month'],
                    'year'            => $full['year'],
                    'label_eng'       => $full['label'],
                    'label_th_f'      => $this->monthYearThai(true, $full['month'], $full['year']),
                    'label_th_s'      => $this->monthYearThai(false, $full['month'], $full['year']),
                    'call_status'     => false,
                    'total_per_month' => 0,
                    'total_round'     => 0,
                    'data'            => []
                ];
            }
        }

        foreach ($Monthly as $month) {
            $called_month   = $month->called_month;
            $called_year    = $month->called_year;
            $full_month_year = $called_month . '-' . $called_year;
            if (isset($array[$full_month_year])) {
                $array[$full_month_year]['total_per_month'] += $month->total;
                $array[$full_month_year]['call_status'] = true;
            }
        }
        return $array;
    }

    public function Tab1_Part4_OtherStatic()
    {
        $CurRound    = CallingDla::max('round');
        $MaxRound    = 25;
        $CallProcess = round((($CurRound / $MaxRound) * 100), 2);

        $MostTotalCall = CallingDla::where('call_status', 1)
            ->select(db::raw("
            called_month , called_year ,
                sum(total::integer ) as total
            "))
            ->groupBy('called_month', 'called_year')
            ->orderBy('total', 'DESC')
            ->first();

        $MostRoundCall = CallingDla::where('call_status', 1)
            ->select(db::raw('
                round as round    ,
                sum(total::integer) as total
            '))
            ->groupBy('round')
            ->orderBy('total', 'DESC')
            ->first();

        $array = [
            'CurRound'          =>  ['name' => 'ความคืบหน้าในการเรียกใช้บัญชี', 'value' => $CallProcess],
            'MostTotalCall_1'   =>  ['name' => 'เดือนที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $this->monthYearThai(true, $MostTotalCall->called_month, $MostTotalCall->called_year)],
            'MostTotalCall_2'   =>  ['name' => 'ทั้งหมด', 'value' => $MostTotalCall->total],
            'MostRoundCall_1'   =>  ['name' => 'รอบที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $MostRoundCall->round],
            'MostRoundCall_2'   =>  ['name' => 'ทั้งหมด', 'value' => $MostRoundCall->total],
        ];
        return $array;
    }

    public function Tab1_Part5_PercentRound()
    {
        $AllRound   = CallingDla::all()->where('total', '!=', '-');
        $array = [];
        foreach ($AllRound as $all) {
            if (!isset($array[$all->round])) {
                $array[$all->round] = [
                    'round' =>  $all->round,
                    'total' =>  0
                ];
            }
            $array[$all->round]['total'] += $all->total;
        }
        return $array;
    }


    public function Tab1_part6_TableRoundCall()
    {
        $array = [];
        $Province = ProvincesDla::all();
        foreach ($Province as $prov) {
            $main   = $prov->id_main_province;
            $sub    = $prov->id_sub_province;
            if (!isset($array[$main])) {
                $array[$main] = [
                    'name'  =>  $prov->main_name_province,
                    'data'  =>  []
                ];
            }
            if (!isset($array[$main]['data'][$sub])) {
                $array[$main]['data'][$sub] = [
                    'full'  =>  $main . $sub,
                    'name'  =>  $prov->main_name_province . ' ' . $prov->sub_name_province,
                    'total_listed'  =>  0,
                    'total_called'  =>  0,
                    'total_remain'  =>  0,
                    'total_round'   =>  0,
                    'data-round'    =>  []
                ];
            }
        }

        $UpdateList = UpdateListDla::all();
        foreach ($UpdateList as $updt) {
            $main   = $updt->id_main_province;
            $sub    = $updt->id_sub_province;
            $total  = $updt->total;
            if (isset($array[$main]['data'][$sub])) {
                $array[$main]['data'][$sub]['total_listed'] += $total;
                $array[$main]['data'][$sub]['total_remain'] += $total;
            }
        }

        $Calling = CallingDla::all();
        foreach ($Calling as $call) {
            if ($call->call_status == true) {
                $main   = $call->id_main_province;
                $sub    = $call->id_sub_province;
                $total  = $call->total;
                if (isset($array[$main]['data'][$sub])) {
                    $array[$main]['data'][$sub]['total_called'] += $total;
                }
                $round  = $call->round;
                if (!isset($array[$main]['data'][$sub]['data-round'][$round])) {
                    $array[$main]['data'][$sub]['total_round'] += 1;
                    $array[$main]['data'][$sub]['data-round'][$round] = [
                        'round' =>  $round,
                        'total' =>  0
                    ];
                }
                $array[$main]['data'][$sub]['data-round'][$round]['total'] += $total;
                $array[$main]['data'][$sub]['total_remain'] -= $total;
            }
        }
        return $array;
    }
}
