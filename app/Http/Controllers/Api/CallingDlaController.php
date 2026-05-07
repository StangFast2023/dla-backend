<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\CallingDla;
use App\Models\UpdateListDla;
use App\Models\ProvincesDla;
use App\Models\PrefixsDla;
use App\Models\PositionDla;
use App\Models\TypePositionDla;

class CallingDlaController extends Controller
{
    public function getStats()
    {
        $updated_list   = UpdateListDla::all();
        $calling        = CallingDla::all();
        $provinces      = ProvincesDla::all();
        $prefixes       = PrefixsDla::all();
        $positions      = PositionDla::all();
        $type_positions = TypePositionDla::all();

        //---- tab 1
        $Tab1_Part1_Static  = $this->Tab1_Part1_Static();
        $Tab1_Part2_Monthly = $this->Tab1_Part2_Monthly();

        return response()->json([
            'status' => 'success',
            'content' => [
                'calling'           => $calling,
                'updated_list'      => $updated_list,
                'provinces'         => $provinces,
                'prefixes'          => $prefixes,
                'positions'         => $positions,
                'type_positions'    => $type_positions
            ],
            'tab1'  =>  [
                'part1' =>  $Tab1_Part1_Static,
                'part2' =>  $Tab1_Part2_Monthly,
            ]
        ]);
    }

    public function Tab1_Part1_Static()
    {
        $CurRound   = CallingDla::max('round');
        $MaxRound   = 25;
        $TotalList  = UpdateListDla::sum('total');
        $TotalCall  = CallingDla::where('call_status', 1)->sum('total');
        $array = [
            'CurRound'      =>  (int)$CurRound,
            'MaxRound'      =>  (int)$MaxRound,
            'TotalList'     =>  (int)$TotalList,
            'TotalCall'     =>  (int)$TotalCall,
        ];
        return $array;
    }

    public function Tab1_Part2_Monthly()
    {
        $Monthly = CallingDla::all()->where('call_status', 1);
        $array = [];
        foreach ($Monthly as $month) {

            $cur_m = $month->called_month;
            $cur_y = $month->called_year;
            $curmy = $cur_m . '-' . $cur_y;
            if (!isset($array[$curmy])) {
                $array[$curmy] = [
                    'date'              =>  $curmy,
                    'month'             =>  $cur_m,
                    'years'             =>  $cur_y,
                    'name_s'            =>  $this->monthYearThai(false, $cur_m, $cur_y),
                    'name_l'            =>  $this->monthYearThai(true, $cur_m, $cur_y),
                    'total_per_month'   =>  0,
                    'data'              =>  []
                ];
            }

            $cur_r = $month->round;
            $cur_t = $month->total;
            if (!isset($array[$curmy]['data'][$cur_r])) {
                $array[$curmy]['data'][$cur_r] = [
                    'round' =>  $cur_r,
                    'total' =>  0
                ];
            }

            if (isset($array[$curmy]['data'][$cur_r])) {
                $array[$curmy]['total_per_month'] += $cur_t;
                $array[$curmy]['data'][$cur_r]['total'] += $cur_t;
            }
        }
        return $array;
    }

    public function monthYearThai($default, $month, $year)
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
