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

class Tab3Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 3

        return [
            'part8' =>  $this->Tab3_Part8_TableAllTypes(),
        ];
    }

    public function Tab3_Part8_TableAllTypes()
    {
        $array = [];
        $AllType = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_main_province as prov_main_id    ,
                updated_list_dla.id_sub_province  as prov_sub_id    ,
                positions_dla.id_type as pos_type_id,
                type_positions_dla.name as pos_type , 
                sum( total )    as  total
            ')
            ->groupBy('prov_main_id', 'prov_sub_id', 'pos_type_id', 'pos_type')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        foreach ($AllType as $type) {
            if (!isset($array[$type->prov_main_id][$type->prov_sub_id][$type->pos_type_id])) {
                $fullProvName = db::table('provinces_dla')
                    ->where('id_main_province', $type->prov_main_id)
                    ->where('id_sub_province', $type->prov_sub_id)
                    ->first();
                $array[$type->prov_main_id][$type->prov_sub_id][$type->pos_type_id] = [
                    'prov_main_id'      =>  $type->prov_main_id,
                    'prov_sub_id'       =>  $type->prov_sub_id,
                    'prov_main_name'    =>  $fullProvName ? $fullProvName->main_name_province : null,
                    'prov_full_name'    =>  $fullProvName ? $fullProvName->main_name_province . " " . $fullProvName->sub_name_province : null,
                    'prov_sub_name'     =>  $fullProvName ? $fullProvName->sub_name_province : null,
                    'pos_type_id'       =>  $type->pos_type_id,
                    'pos_type'          =>  $type->pos_type,
                    'total_list'        =>  (int)$type->total,
                    'total_call'        =>  0,
                    'total_remain'      =>  (int)$type->total,
                    'status_empty'      =>  false,
                    'status_called'     =>  false,
                    'round_data'        =>  []
                ];
            }
        }
        $array = collect($array)->sortKeys()->toArray();
        foreach ($array as &$subArray) {
            foreach ($subArray as &$main) {
                if (is_array($main)) {
                    ksort($main);
                }
            }
        }
        $typeCallAll = db::table('calling_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'calling_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->where('call_status', 1)
            ->selectRaw('
                calling_dla.id_main_province                as  id_main_province    ,
                calling_dla.id_sub_province                 as  id_sub_province     ,
                calling_dla.round                           as  round               ,
                positions_dla.id_type                       as  pos_type_id         ,
                type_positions_dla.name                     as  pos_type            , 
                sum( total )                                as  total
            ')
            ->groupBy('id_main_province', 'id_sub_province', 'round', 'pos_type_id', 'pos_type')
            ->orderBy('total', 'DESC')
            ->get()
            ->toArray();
        foreach ($typeCallAll as $all) {
            $id_main_province   = $all->id_main_province;
            $id_sub_province    = $all->id_sub_province;
            $pos_type_id        = $all->pos_type_id;
            $total              = $all->total;
            if (isset($array[$id_main_province][$id_sub_province][$pos_type_id])) {
                $array[$id_main_province][$id_sub_province][$pos_type_id]['total_call'] += $total;
                $array[$id_main_province][$id_sub_province][$pos_type_id]['total_remain'] -= $total;
            }

            $round = $all->round;
            if (!isset($array[$id_main_province][$id_sub_province][$pos_type_id]['round_data'][$round])) {
                $array[$id_main_province][$id_sub_province][$pos_type_id]['status_called'] = true;
                $array[$id_main_province][$id_sub_province][$pos_type_id]['round_data'][$round] = [
                    'round'     =>  $round,
                    'total'     =>  0,
                    'called'    =>  false
                ];
            }
            $array[$id_main_province][$id_sub_province][$pos_type_id]['round_data'][$round]['total'] += $total;
            $array[$id_main_province][$id_sub_province][$pos_type_id]['round_data'][$round]['called'] = $total !== 0;
        }
        foreach ($array as $key_prov => $call) {
            foreach ($call as $key_type => $type) {
                $total_remain = $type[$key_type]['total_remain'];
                $type[$key_type]['status_empty'] = $total_remain === 0;
            }
        }
        return $array;
    }
}
