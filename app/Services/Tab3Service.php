<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
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
        return Cache::remember('tab3_part8_table_all_types', 600, function () {
            $provinces = db::table('provinces_dla')
                ->get(['id_main_province', 'id_sub_province', 'main_name_province', 'sub_name_province'])
                ->keyBy(function ($item) {
                    return $item->id_main_province . '_' . $item->id_sub_province;
                });
            $AllType = db::table('updated_list_dla')
                ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
                ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
                ->select(db::raw('updated_list_dla.id_main_province as prov_main_id, updated_list_dla.id_sub_province as prov_sub_id, positions_dla.id_type as pos_type_id, type_positions_dla.name as pos_type, sum(total::integer) as total'))
                ->groupBy('prov_main_id', 'prov_sub_id', 'pos_type_id', 'pos_type')
                ->get();
            $array = [];
            foreach ($AllType as $type) {
                $provKey = $type->prov_main_id . '_' . $type->prov_sub_id;
                $prov = $provinces->get($provKey);

                $array[$type->prov_main_id][$type->prov_sub_id][$type->pos_type_id] = [
                    'prov_main_id'   => $type->prov_main_id,
                    'prov_sub_id'    => $type->prov_sub_id,
                    'prov_main_name' => $prov ? $prov->main_name_province : null,
                    'prov_full_name' => $prov ? $prov->main_name_province . " " . $prov->sub_name_province : null,
                    'prov_sub_name'  => $prov ? $prov->sub_name_province : null,
                    'pos_type_id'    => $type->pos_type_id,
                    'pos_type'       => $type->pos_type,
                    'total_list'     => (int)$type->total,
                    'total_call'     => 0,
                    'total_remain'   => (int)$type->total,
                    'status_empty'   => false,
                    'status_called'  => false,
                    'round_data'     => []
                ];
            }
            $typeCallAll = db::table('calling_dla')
                ->leftjoin('positions_dla', 'positions_dla.id_position', 'calling_dla.id_position')
                ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
                ->where('call_status', 1)
                ->select(db::raw('calling_dla.id_main_province, calling_dla.id_sub_province, calling_dla.round, positions_dla.id_type as pos_type_id, sum(total::integer) as total'))
                ->groupBy('id_main_province', 'id_sub_province', 'round', 'pos_type_id')
                ->get();
            foreach ($typeCallAll as $all) {
                $ref = &$array[$all->id_main_province][$all->id_sub_province][$all->pos_type_id];

                if ($ref) {
                    $ref['total_call'] += $all->total;
                    $ref['total_remain'] -= $all->total;
                    $ref['status_called'] = true;

                    $ref['round_data'][$all->round] = [
                        'round'  => $all->round,
                        'total'  => ($ref['round_data'][$all->round]['total'] ?? 0) + $all->total,
                        'called' => $all->total !== 0
                    ];
                }
            }
            foreach ($array as &$main) {
                foreach ($main as &$sub) {
                    foreach ($sub as &$pos) {
                        $pos['status_empty'] = ($pos['total_remain'] <= 0);
                    }
                }
            }
            return $array;
        });
    }
}
