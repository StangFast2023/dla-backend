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
use Carbon\Carbon;

class Tab4Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 4
        return [
            'part1' =>  $this->Tab4_Part1_Filter(),
            'part2' =>  $this->Tab4_Part2_ShowData(),
        ];
    }

    public function Tab4_Part1_Filter()
    {
        $data = [
            'region'    =>  [],
            'types'     =>  [],
            'positions' =>  []
        ];
        $prov = ProvincesDla::all();
        foreach ($prov as $pr) {
            $main   = $pr->id_main_province;
            $sub    = $pr->id_sub_province;
            $name1  = $pr->main_name_province;
            $name2  = $pr->sub_name_province;

            if (!isset($data['region'][$main])) {
                $data['region'][$main] = [
                    'main'      =>  $main,
                    'main_name' =>  $name1,
                    'sub'       =>  []
                ];
            }
            if (!isset($data['region'][$main]['sub'][$sub])) {
                $data['region'][$main]['sub'][$sub] = [
                    'sub_name'  =>  $name1 . ' ' . $name2
                ];
            }
        }

        $types = TypePositionDla::all();
        foreach ($types as $tp) {
            $id             = $tp->id;
            $name           = $tp->name;
            $type_position  = $tp->type_position;
            if (!isset($data['types'][$id])) {
                $data['types'][$id] = [
                    'id'            =>  $id,
                    'type_name'     =>  $name,
                    'suffixe_name'  =>  $type_position
                ];
            }
        }

        $post = PositionDla::all();
        foreach ($post as $ps) {
            $id             = $ps->id;
            $id_position    = $ps->id_position;
            $name           = $ps->name;
            $id_type        = substr($id_position, 0, 1);
            $id_prefix      = $ps->id_prefix;
            if (!isset($data['positions'][$id_type])) {
                $data['positions'][$id_type] = [
                    'type_name'     =>  $data['types'][$id_type]['type_name'],
                    'data_position' =>  []
                ];
            }
            if (!isset($data['positions'][$id_type]['data_position'][$id_position])) {
                $pref = PrefixsDla::where('id', $id_prefix)->first();
                $data['positions'][$id_type]['data_position'][$id_position] = [
                    'pos_id'        =>  $id,
                    'pos_main_id'   =>  $id_position,
                    'full_pos_name' =>  $pref->name . $name,
                    'type_name'     =>  $data['types'][$id_type]['type_name']
                ];
            }
        }
        return $data;
    }

    public function Tab4_Part2_ShowData()
    {
        $all_pos_array = [];
        $all_Positions = db::table('positions_dla')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->selectRaw('
                positions_dla.id_position                   as  pos_id          ,
                positions_dla.name                          as  pos_name        ,

                prefixes_dla.name                           as  pref_name       ,
                type_positions_dla.type_position            as  suff_name       ,

                SUBSTRING(positions_dla.id_position, 1, 1)  as  pos_type_id     ,
                type_positions_dla.name                     as  pos_type_name
            ')
            ->get()
            ->toArray();
        foreach ($all_Positions as $pos) {
            if (!isset($all_pos_array[$pos->pos_id])) {
                $all_pos_array[$pos->pos_id] = [
                    'pos_id'                =>  $pos->pos_id,
                    'pos_name'              =>  $pos->pref_name . $pos->pos_name . $pos->suff_name,
                    'pos_type_id'           =>  $pos->pos_type_id,
                    'pos_type_name'         =>  $pos->pos_type_name,
                    'status_open'           =>  false,
                    'total_listed'          =>  0,
                    'total_call_round'      =>  0,
                    'total_call'            =>  0,
                    'total_remain'          =>  0,
                    'status_out_of_lits'    =>  false,
                    'data_call_round'       =>  []
                ];
            }
        }

        $array = [];
        $provinces = db::table('provinces_dla')
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
        foreach ($provinces as $prov) {
            if (!isset($array[$prov->pro_main_id])) {
                $array[$prov->pro_main_id] = [
                    'pro_main_id'       =>  $prov->pro_main_id,
                    'pro_main_name'     =>  $prov->pro_main_name,
                    'total_position'    =>  0,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_each_round'  =>  [],
                    'pro_sub'           =>  []
                ];
            }
            if (!isset($array[$prov->pro_main_id]['pro_sub'][$prov->pro_sub_id])) {
                $array[$prov->pro_main_id]['pro_sub'][$prov->pro_sub_id] = [
                    'pro_sub_id'        =>  $prov->pro_sub_id,
                    'pro_sub_name'      =>  $prov->pro_full_name,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_each_round'  =>  [],
                    'data_position'     =>  $all_pos_array
                ];
            }
        }

        $listed_position = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_main_province           as  prov_main_id    ,
                updated_list_dla.id_sub_province            as  prov_sub_id     ,
                positions_dla.id_position                   as  pos_id          ,
                sum( total )                                as  total
            ')
            ->groupBy('prov_main_id', 'prov_sub_id', 'pos_id')
            ->orderBy('pos_id', 'asc')
            ->get()
            ->toArray();
        foreach ($listed_position as $pos) {
            $prov_main_id   =   $pos->prov_main_id;
            $prov_sub_id    =   $pos->prov_sub_id;
            $pos_id         =   $pos->pos_id;
            $total          =   $pos->total;
            if (isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id])) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['status_open']  = (int)$total !== 0;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_listed'] = (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_remain'] = (int)$total;
            }
            $array[$prov_main_id]['total_listed'] += (int)$total;
            $array[$prov_main_id]['total_remain'] += (int)$total;

            $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_listed'] += (int)$total;
            $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] += (int)$total;
        }
        $called_position = db::table('calling_dla')
            ->get()
            ->toArray();
        foreach ($called_position as $pos) {
            $prov_main_id   =   $pos->id_main_province;
            $prov_sub_id    =   $pos->id_sub_province;
            $pos_id         =   $pos->id_position;
            $round          =   $pos->round;
            $total          =   $pos->total;

            $is_cross_region    =   $pos->is_cross_region;
            $crossed_region     =   $pos->crossed_region;
            $crossed_zone       =   $pos->crossed_zone;

            $call_status        =   $pos->call_status;
            $list_status        =   $pos->list_status;
            $current_date       =   Carbon::today();

            if ($call_status === 1) {
                $call_date      =   Carbon::createFromDate($pos->called_year, $pos->called_month, $pos->called_day);
                $status         =   $call_date->greaterThan($current_date) ? 'waiting' : 'completed';
            } else {
                $call_date      =   null;
                $status         =   $list_status === 1 ? 'not-used' : 'exhaustion';
            }


            if (!isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['data_call_round'][$round])) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_call_round'] += 1;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['data_call_round'][$round] = [
                    'round'             =>  $round,
                    'total'             =>  (int)$total,
                    'start'             =>  0,
                    'end'               =>  0,
                    'start_end'         =>  0,
                    'status_call'       =>  $call_status === 1,
                    'status_list'       =>  $list_status === 1,
                    'status_cross'      =>  $is_cross_region === 1,
                    'crossed_region'    =>  $is_cross_region === 1 ? $crossed_region    : null,
                    'crossed_zone'      =>  $is_cross_region === 1 ? $crossed_zone      : null,
                    'date'              =>  $call_date ? $call_date->format('d-m-Y')    : null,
                    'status'            =>  $status,
                ];
            }

            if (!isset($array[$prov_main_id]['total_each_round'][$round])) {
                $array[$prov_main_id]['total_each_round'][$round] = [
                    'round' =>  $round,
                    'total' =>  0
                ];
            }

            if (!isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_each_round'][$round])) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_each_round'][$round] = [
                    'round' =>  $round,
                    'total' =>  0
                ];
            }

            if ($call_status === 1) {
                $array[$prov_main_id]['total_each_round'][$round]['total'] += $total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_each_round'][$round]['total'] += $total;

                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_call'] += $total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_remain'] -= $total;

                $array[$prov_main_id]['total_called'] += (int)$total;
                $array[$prov_main_id]['total_remain'] -= (int)$total;

                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_called'] += (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] -= (int)$total;
            }
        }
        foreach ($array as &$prov) {
            foreach ($prov['pro_sub'] as &$prov_sub) {
                foreach ($prov_sub['data_position'] as &$pos) {
                    $pos['status_out_of_lits'] = ($pos['total_remain'] === 0);
                    $last_end = 0;
                    foreach ($pos['data_call_round'] as &$round) {
                        $total = (int)$round['total'];
                        $start = $last_end + 1;
                        $end = $start + $total - 1;
                        $round['start'] = $start;
                        $round['end'] = $end;
                        if ($total > 0) {
                            $round['start_end'] = $total > 1 ? $start . ' - ' . $end : $start;
                        }
                        $last_end = $end;
                    }
                }
            }
        }
        foreach ($array as &$prov) {
            foreach ($prov['pro_sub'] as &$prov_sub) {
                $prov_sub['data_position'] = array_filter($prov_sub['data_position'], function ($pos) {
                    return $pos['status_open'] === true;
                });
            }
        }
        return $array;
    }
}
