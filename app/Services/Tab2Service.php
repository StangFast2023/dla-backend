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

class Tab2Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 2
        return [
            'part1' =>  $this->Tab2_Part1_TypePos(),
            'part2' =>  $this->Tab2_part2_TypeMonthly(),
            'part3' =>  $this->Tab2_part3_TypeRoundly(),
            'part4' =>  $this->Tab2_Part4_TypePosTop10(),
            'part5' =>  $this->Tab2_Part5_Empty(),
        ];
    }


    public function Tab2_Part1_TypePos()
    {
        $array = [
            't' =>  [
                'total_count'   =>  0,
                'total_person'   =>  0,
            ]
        ];
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
            $pos_id         = $pos->id_pos;
            $pos_type_id    = $pos->pos_type_id;
            if (!isset($array[$pos_type_id])) {
                $array[$pos_type_id] = [
                    'pos_type_id'                 =>  $pos_type_id,
                    'type_name'                 =>  $pos->pos_type,
                    'total_count'               =>  0,
                    'total_count_in_percent'    =>  0,
                    'total_person'              =>  0,
                    'total_person_in_percent'   =>  0,
                    'data'                      => []
                ];
            }
            if (!isset($array[$pos_type_id]['data'][$pos_id])) {
                $array['t']['total_count']  += 1;
                $array[$pos_type_id]['total_count'] += 1;
                $array[$pos_type_id]['data'][$pos_id] = [
                    'pos_type_id'   =>  $pos_type_id,
                    'id_pos'        =>  $pos_id,
                    'pos_name'      =>  $pos->pos_name,
                    'data'          =>  $pos->total
                ];
            }
            $array['t']['total_person'] += $pos->total;
            $array[$pos_type_id]['total_person'] += $pos->total;
        }
        $array = collect($array)->sortKeys()->toArray();
        foreach ($array as $key => $arr) {
            $total_count  = $array['t']['total_count'];
            $total_person = $array['t']['total_person'];
            if ($key !== 't') {
                $total_count_div = $array[$key]['total_count'];
                $array[$key]['total_count_in_percent'] = (($total_count_div / $total_count) * 100);

                $total_person_div = $array[$key]['total_person'];
                $array[$key]['total_person_in_percent'] = (($total_person_div / $total_person) * 100);
            }
        }
        return $array;
    }

    public function Tab2_part2_TypeMonthly()
    {
        $fullMonthly = $this->getAccountTimeline();
        $fullMonthly = collect($fullMonthly)->keyBy('date')->toArray();
        foreach ($fullMonthly as $key => $monthly) {
            $new_array = ['total_per_month' => 0];
            $fullMonthly[$key] = array_merge($fullMonthly[$key], $new_array);
        }
        $CallingMonthly = db::table('calling_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'calling_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->where('calling_dla.call_status', 1)
            ->selectRaw('
                concat( calling_dla.called_month , "-" , calling_dla.called_year ) as monthly ,
                SUBSTRING(positions_dla.id_position, 1, 1)  as pos_type_id ,
                type_positions_dla.name     as  pos_type , 
                sum( calling_dla.total )    as  total
            ')
            ->groupBy('monthly', 'pos_type_id', 'pos_type')
            ->get();
        foreach ($CallingMonthly as $call) {
            $key = $call->monthly;
            $type = $call->pos_type_id;
            if (!isset($fullMonthly[$key]['data'][$type])) {
                $fullMonthly[$key]['data'][$type] = [
                    'type'  =>  $type,
                    'name'  =>  $call->pos_type,
                    'total' =>  $call->total
                ];
                $fullMonthly[$key]['total_per_month'] += $call->total;
            }
        }
        return $fullMonthly;
    }

    public function Tab2_part3_TypeRoundly()
    {

        $CallingMonthly = db::table('calling_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'calling_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->where('calling_dla.call_status', 1)
            ->selectRaw('
                calling_dla.round as roundly ,
                SUBSTRING(positions_dla.id_position, 1, 1)  as pos_type_id ,
                type_positions_dla.name     as  pos_type , 
                sum( calling_dla.total )    as  total
            ')
            ->groupBy('roundly', 'pos_type_id', 'pos_type')
            ->get();
        $array = [];
        foreach ($CallingMonthly as $call) {
            $roundly = $call->roundly;
            if (!isset($array[$roundly]['data'][$call->pos_type_id])) {
                $array[$roundly]['data'][$call->pos_type_id] = [
                    'round' =>  $roundly,
                    'type'  =>  $call->pos_type_id,
                    'name'  =>  $call->pos_type,
                    'total' =>  $call->total
                ];
            }
            if (!isset($array[$roundly]['total_per_round'])) {
                $array[$roundly]['total_per_round'] = 0;
            }
            $array[$roundly]['total_per_round'] += $call->total;
        }
        return $array;
    }

    public function Tab2_Part4_TypePosTop10()
    {
        $top10Pos        = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_position as id_pos ,
                concat( prefixes_dla.name , positions_dla.name , type_positions_dla.type_position ) as pos_name ,
                SUBSTRING(positions_dla.id_position, 1, 1) as pos_type_id,
                type_positions_dla.name as pos_type , 
                sum( total )    as  total
            ')
            ->groupBy('id_pos', 'pos_name', 'pos_type_id', 'pos_type')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        return $top10Pos;
    }

    public function Tab2_Part5_Empty()
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

    /**
     * @param int $id
     */
    public function getPositionDetail($id)
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
        return $array;
    }
}
