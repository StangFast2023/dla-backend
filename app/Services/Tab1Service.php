<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\CallingDla;
use App\Traits\DateCalculatable;
use App\Traits\TotalNewListCalculatable;

class Tab1Service
{

    use DateCalculatable;
    use TotalNewListCalculatable;

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
        return Cache::remember('tab1_part1_stats', 600, function () {

            $getNewTotal = $this->getNewTotal();
            $CurRound   = CallingDla::max('round');
            $MaxRound   = 25;
            $AVGCall = CallingDla::where('call_status', 1)
                ->select(DB::raw("concat(called_month, '/', called_year) as monthly, sum(total::integer) as total_per_month"))
                ->groupBy('monthly')
                ->get()
                ->avg('total_per_month');

            return [
                'CurRound'      => (int)$CurRound,
                'MaxRound'      => (int)$MaxRound,
                'OldTotalList'  => (int)$getNewTotal['listed_old'],
                'NewTotalList'  => (int)$getNewTotal['listed_new'],
                'DiffTotalList' => (int)$getNewTotal['listed_diff'],
                'TotalCall'     => (int)$getNewTotal['called_total'],
                'AvgCall'       => (int)$AVGCall,
            ];
        });
    }

    public function Tab1_Part2_Monthly()
    {
        return Cache::remember('monthly_stats_tab1', 600, function () {
            $rawStats = DB::table('calling_dla')
                ->select('called_month', 'called_year', 'round')
                ->selectRaw('SUM(total::integer) as total_sum')
                ->where('call_status', 1)
                ->groupBy('called_year', 'called_month', 'round')
                ->get();
            $fullMonthly = $this->getAccountTimeline();
            $array = [];
            foreach ($fullMonthly as $full) {
                $key = $full['month'] . '-' . $full['year'];
                $array[$key] = [
                    'month' => $full['month'],
                    'year' => $full['year'],
                    'label_eng' => $full['label'],
                    'label_th_f' => $this->monthYearThai(true, $full['month'], $full['year']),
                    'label_th_s' => $this->monthYearThai(false, $full['month'], $full['year']),
                    'call_status' => false,
                    'total_per_month' => 0,
                    'total_round' => 0,
                    'data' => []
                ];
            }
            foreach ($rawStats as $row) {
                $key = $row->called_month . '-' . $row->called_year;
                if (isset($array[$key])) {
                    $array[$key]['call_status'] = true;
                    $array[$key]['total_per_month'] += $row->total_sum;
                    $array[$key]['total_round'] += 1;
                    $array[$key]['data'][$row->round] = [
                        'round' => $row->round,
                        'total' => $row->total_sum
                    ];
                }
            }
            return $array;
        });
    }

    public function Tab1_Part3_Cumulative()
    {
        return Cache::remember('tab1_part3_cumulative', 600, function () {
            $monthlySums = DB::table('calling_dla')
                ->select('called_month', 'called_year')
                ->selectRaw('SUM(total::integer) as sum_total')
                ->where('call_status', 1)
                ->groupBy('called_year', 'called_month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->called_month . '-' . $item->called_year;
                });
            $fullMonthly = $this->getAccountTimeline();
            $array = [];
            foreach ($fullMonthly as $full) {
                $key = $full['month'] . '-' . $full['year'];
                $array[$key] = [
                    'month'           => $full['month'],
                    'year'            => $full['year'],
                    'label_eng'       => $full['label'],
                    'label_th_f'      => $this->monthYearThai(true, $full['month'], $full['year']),
                    'label_th_s'      => $this->monthYearThai(false, $full['month'], $full['year']),
                    'call_status'     => isset($monthlySums[$key]),
                    'total_per_month' => isset($monthlySums[$key]) ? (int)$monthlySums[$key]->sum_total : 0,
                    'total_round'     => 0,
                    'data'            => []
                ];
            }

            return $array;
        });
    }

    public function Tab1_Part4_OtherStatic()
    {
        return Cache::remember('tab1_part4_stats', 600, function () {
            $CurRound = CallingDla::max('round') ?? 0;
            $MaxRound = 25;
            $CallProcess = $MaxRound > 0 ? round((($CurRound / $MaxRound) * 100), 2) : 0;
            $MostTotalCall = DB::table('calling_dla')
                ->select('called_month', 'called_year')
                ->selectRaw('SUM(total::integer) as total')
                ->where('call_status', true)
                ->groupBy('called_month', 'called_year')
                ->orderByDesc('total')
                ->first();
            $MostRoundCall = DB::table('calling_dla')
                ->select('round')
                ->selectRaw('SUM(total::integer) as total')
                ->where('call_status', true)
                ->groupBy('round')
                ->orderByDesc('total')
                ->first();
            return [
                'CurRound' => ['name' => 'ความคืบหน้าในการเรียกใช้บัญชี', 'value' => $CallProcess],
                'MostTotalCall_1' => ['name' => 'เดือนที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $MostTotalCall ? $this->monthYearThai(true, $MostTotalCall->called_month, $MostTotalCall->called_year) : '-'],
                'MostTotalCall_2' => ['name' => 'ทั้งหมด', 'value' => $MostTotalCall ? $MostTotalCall->total : 0],
                'MostRoundCall_1' => ['name' => 'รอบที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $MostRoundCall ? $MostRoundCall->round : '-'],
                'MostRoundCall_2' => ['name' => 'ทั้งหมด', 'value' => $MostRoundCall ? $MostRoundCall->total : 0],
            ];
        });
    }

    public function Tab1_Part5_PercentRound()
    {
        return Cache::remember('tab1_part5_percent_round', 600, function () {
            $results = DB::table('calling_dla')
                ->select('round')
                ->selectRaw('SUM(total::integer) as total')
                ->where('total', '!=', '-')
                ->groupBy('round')
                ->orderBy('round', 'ASC')
                ->get();
            $array = [];
            foreach ($results as $row) {
                $array[$row->round] = [
                    'round' => $row->round,
                    'total' => (int)$row->total
                ];
            }

            return $array;
        });
    }

    public function Tab1_part6_TableRoundCall()
    {
        return Cache::remember('tab1_part6_table', 600, function () {

            $callStats = DB::table('calling_dla')
                ->select('id_main_province', 'id_sub_province', 'round', 'list_new_status')
                ->selectRaw('SUM(total::integer) as total_called ')
                ->where('call_status', true)
                ->orderBy('id_main_province', 'asc')
                ->orderBy('id_sub_province', 'asc')
                ->orderBy('round', 'asc')
                ->groupBy('id_main_province', 'id_sub_province', 'round', 'list_new_status')
                ->get()
                ->groupBy(['id_main_province', 'id_sub_province'])
                ->toArray();
            $getNewTotal = $this->getNewTotal();
            $array = [];
            foreach ($getNewTotal['data'] as $key1 => $region) {
                if (!isset($array[$key1])) {
                    $array[$key1] = [
                        'name'  =>  $region['name_main'],
                        'data'  =>  []
                    ];
                }
                foreach ($region['data'] as $key2 => $zone) {
                    if (!isset($array[$key1]['data'][$key2])) {
                        $array[$key1]['data'][$key2] = [
                            'full'              =>  $key1 . $key2,
                            'name'              =>  $zone['full_name'],
                            'total_listed_new'  =>  $zone['listed_new'],
                            'total_listed'      =>  $zone['listed_old'],
                            'total_called'      =>  $zone['called_total'],
                            'total_remain'      =>  $zone['remaining'],
                            'total_round'       =>  $zone['total_round'],
                            'data-round'        =>  $callStats[$key1][$key2],
                        ];
                    }
                }
            }
            return $array;
        });
    }
}
