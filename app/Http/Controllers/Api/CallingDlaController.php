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
        $Tab1_Part1_Static      = $this->Tab1_Part1_Static();
        $Tab1_Part2_Monthly     = $this->Tab1_Part2_Monthly();
        $Tab1_Part3_Cumulative  = $this->Tab1_Part3_Cumulative();
        $Tab1_Part4_OtherStatic = $this->Tab1_Part4_OtherStatic();
        $Tab1_Part5_Type        = $this->Tab1_Part5_Type();
        $Tab1_Part6_Empty       = $this->Tab1_Part6_Empty();
        $Tab1_Part7_TypePos     = $this->Tab1_Part7_TypePos();



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
                'part3' =>  $Tab1_Part3_Cumulative,
                'part4' =>  $Tab1_Part4_OtherStatic,
                'part5' =>  $Tab1_Part5_Type,
                'part6' =>  $Tab1_Part6_Empty,
                'part7' =>  $Tab1_Part7_TypePos,
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

    public function Tab1_Part3_Cumulative()
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
                ];
            }

            if (isset($array[$curmy])) {
                $array[$curmy]['total_per_month'] += $month->total;
            }
        }
        return $array;
    }

    public function Tab1_Part4_OtherStatic()
    {
        $CurRound    = CallingDla::max('round');
        $MaxRound    = 25;
        $CallProcess = round((($CurRound / $MaxRound) * 100), 2);

        $MostTotalCall = CallingDla::selectRaw('
            concat( called_month , "-" , called_year ) as month_year    ,
            sum( total ) as total
        ')
            ->groupBy('month_year')
            ->orderBy('total', 'DESC')
            ->first();

        $MostRoundCall = CallingDla::selectRaw('
            round as round    ,
            sum( total ) as total
        ')
            ->groupBy('round')
            ->orderBy('total', 'DESC')
            ->first();

        $array = [
            'CurRound'          =>  ['name' => 'ความคืบหน้าในการเรียกใช้บัญชี', 'value' => $CallProcess],
            'MostTotalCall_1'   =>  ['name' => 'เดือนที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $this->monthYearThai(true, explode('-', $MostTotalCall->month_year)[0], explode('-', $MostTotalCall->month_year)[1])],
            'MostTotalCall_2'   =>  ['name' => 'ทั้งหมด', 'value' => $MostTotalCall->total],
            'MostRoundCall_1'   =>  ['name' => 'รอบที่มีการเรียกรายงานตัวมากที่สุด', 'value' => $MostRoundCall->round],
            'MostRoundCall_2'   =>  ['name' => 'ทั้งหมด', 'value' => $MostRoundCall->total],
        ];
        return $array;
    }

    public function Tab1_Part5_Type()
    {
        $top10Pos        = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_position as id_pos ,
                concat( prefixes_dla.name , positions_dla.name , type_positions_dla.type_position ) as pos_name ,
                sum( total )    as  total
            ')
            ->groupBy('id_pos', 'pos_name')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        return $top10Pos;
    }

    public function Tab1_Part6_Empty()
    {
        $array = [];
        $fastEmpty = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_main_province as prov_main_id    ,
                updated_list_dla.id_sub_province  as prov_sub_id    ,
                updated_list_dla.id_position as id_pos ,
                concat( prefixes_dla.name , positions_dla.name , type_positions_dla.type_position ) as pos_name ,
                SUBSTRING(positions_dla.id_position, 1, 1) as pos_type_id,
                type_positions_dla.name as pos_type , 
                sum( total )    as  total
            ')
            ->groupBy('prov_main_id', 'prov_sub_id', 'id_pos', 'pos_name', 'pos_type_id', 'pos_type')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        foreach ($fastEmpty as $fast) {
            if (!isset($array[$fast->prov_main_id][$fast->prov_sub_id][$fast->id_pos])) {
                $fullProvName = db::table('provinces_dla')
                    ->where('id_main_province', $fast->prov_main_id)
                    ->where('id_sub_province', $fast->prov_sub_id)
                    ->first();
                $array[$fast->prov_main_id][$fast->prov_sub_id][$fast->id_pos] = [
                    'id_pos'            =>  $fast->id_pos,
                    'pos_name'          =>  $fast->pos_name,
                    'pos_type_id'       =>  $fast->pos_type_id,
                    'pos_type'          =>  $fast->pos_type,
                    'total_list'        =>  (int)$fast->total,
                    'total_call'        =>  0,
                    'status_empty'      =>  false,
                    'prov_main_id'      =>  $fast->prov_main_id,
                    'prov_sub_id'       =>  $fast->prov_sub_id,
                    'prov_full_name'    =>  $fullProvName ? $fullProvName->main_name_province . " " . $fullProvName->sub_name_province : null
                ];
            }
        }
        $allCalling = db::table('calling_dla')
            ->where('call_status', 1)
            ->where('round', 1)
            ->get();
        foreach ($allCalling as $all) {
            if (isset($array[$all->id_main_province][$all->id_sub_province][$all->id_position])) {
                $array[$all->id_main_province][$all->id_sub_province][$all->id_position]['total_call'] = (int)$all->total;
                $array[$all->id_main_province][$all->id_sub_province][$all->id_position]['status_empty'] = $array[$all->id_main_province][$all->id_sub_province][$all->id_position]['total_list'] - $all->total === 0;
            }
        }
        foreach ($array as $zoneKey => $provinces) {
            foreach ($provinces as $proKey => $positions) {
                $array[$zoneKey][$proKey] = array_filter($positions, function ($item) {
                    return $item['status_empty'] !== false;
                });
            }
        }
        $allPositions = collect($array)
            ->collapse()
            ->collapse()
            ->filter(function ($item) {
                return $item['status_empty'] === true;
            })
            ->sortByDesc('total_list')
            ->values()
            ->all();
        return $allPositions;
    }

    public function Tab1_Part7_TypePos()
    {
        $array1 = [];
        $array2 = [];
        $TypePos = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_main_province as prov_main_id    ,
                updated_list_dla.id_sub_province  as prov_sub_id    ,
                updated_list_dla.id_position as id_pos ,
                concat( prefixes_dla.name , positions_dla.name , type_positions_dla.type_position ) as pos_name ,
                SUBSTRING(positions_dla.id_position, 1, 1) as pos_type_id,
                type_positions_dla.name as pos_type , 
                sum( total )    as  total
            ')
            ->groupBy('prov_main_id', 'prov_sub_id', 'id_pos', 'pos_name', 'pos_type_id', 'pos_type')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        foreach ($TypePos as $pos) {
            $pr_m_id        = $pos->prov_main_id;
            $pr_s_id        = $pos->prov_sub_id;
            $pos_id         = $pos->id_pos;
            $pos_type_id    = $pos->pos_type_id;
            if (!isset($array1[$pr_m_id][$pr_s_id][$pos_id])) {
                $array1[$pr_m_id][$pr_s_id][$pos_id] = [
                    'id_pos'        =>  $pos->id_pos,
                    'pos_name'      =>  $pos->pos_name,
                    'pos_type_id'   =>  $pos->pos_type_id,
                    'pos_type'      =>  $pos->pos_type,
                    'total'         =>  $pos->total,
                ];
            }
            if (!isset($array2[$pos_type_id])) {
                $array2[$pos_type_id] = [
                    'type_name'         =>  $pos->pos_type,
                    'total_count'       =>  0,
                    'total_person'      =>  0,
                    'data'              => []
                ];
            }
            if (!isset($array2[$pos_type_id]['data'][$pos_id])) {
                $array2[$pos_type_id]['total_count'] += 1;
                $array2[$pos_type_id]['data'][$pos_id] = [
                    'pos_type_id'   =>  $pos_type_id,
                    'id_pos'        =>  $pos_id,
                    'pos_name'      =>  $pos->pos_name,
                    'data'          =>  $pos->total
                ];
            }
            $array2[$pos_type_id]['total_person'] += $pos->total;
        }
        return $array2;
    }


















































    public function getPositionDetailByZone($id)
    {
        $array = [];
        $Position = db::table('positions_dla')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                concat( prefixes_dla.name , positions_dla.name , type_positions_dla.type_position ) as pos_name
            ')
            ->where('positions_dla.id_position', $id)
            ->first();
        $array = [
            'id'    =>  $id,
            'name'  =>  $Position->pos_name,
            'data'  =>  []
        ];

        $Provinces = db::table('provinces_dla')
            ->selectRaw('
                provinces_dla.id                    as pro_id           ,
                provinces_dla.id_main_province      as pro_main_id      ,
                provinces_dla.main_name_province    as pro_main_name    ,
                provinces_dla.id_sub_province       as pro_sub_id       ,
                provinces_dla.sub_name_province     as pro_sub_name     ,
                concat( provinces_dla.main_name_province , " " , provinces_dla.sub_name_province )  as pro_full_name 
            ')
            ->orderBy('pro_id', 'ASC')
            ->get();

        foreach ($Provinces as $prov) {
            if (!isset($array['data'][$prov->pro_main_id])) {
                $array['data'][$prov->pro_main_id] = [
                    'pro_main_id'       =>  $prov->pro_main_id,
                    'pro_main_name'     =>  $prov->pro_main_name
                ];
            }
            if (!isset($array['data'][$prov->pro_main_id][$prov->pro_sub_id])) {
                $array['data'][$prov->pro_main_id][$prov->pro_sub_id] = [
                    'pro_main_id'       =>  $prov->pro_main_id,
                    'pro_sub_id'        =>  $prov->pro_sub_id,
                    'pro_main_name'     =>  $prov->pro_main_name,
                    'pro_sub_name'      =>  $prov->pro_sub_name,
                    'pro_full_name'     =>  $prov->pro_full_name,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_process'     =>  0,
                    'total_round'       =>  0,
                    'status_listed'     =>  false,
                    'status_calling'    =>  false,
                ];
            }
        }

        $Update = db::table('updated_list_dla')
            ->where('id_position', $id)
            ->get();
        foreach ($Update as $update) {
            if (isset($array['data'][$update->id_main_province][$update->id_sub_province])) {
                $array['data'][$update->id_main_province][$update->id_sub_province]['total_listed'] = $update->total;
                $array['data'][$update->id_main_province][$update->id_sub_province]['status_listed'] = true;
            }
        }

        $allCalling = db::table('calling_dla')
            ->where('call_status', 1)
            ->where('id_position', $id)
            ->selectRaw('
                id_main_province        as pro_main_id  ,
                id_sub_province         as pro_sub_id   ,
                max( round )            as round ,
                sum( total )            as total                      
            ')
            ->groupBy('pro_main_id', 'pro_sub_id')
            ->get();
        foreach ($allCalling as $calling) {
            if (isset($array['data'][$calling->pro_main_id][$calling->pro_sub_id])) {
                $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['total_called']     = $calling->total;
                $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['total_round']      = $calling->round;
                $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['status_calling']   = true;

                $percentage = 0;
                $totalListed = $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['total_listed'];
                $statusl = $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['status_listed'];
                if ($statusl === true) {
                    $percentage = (($calling->total / $totalListed) * 100);
                }
                $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['total_process'] = $percentage;
                $array['data'][$calling->pro_main_id][$calling->pro_sub_id]['total_remain'] = $totalListed - $calling->total;
            }
        }
        return response()->json($array);
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
