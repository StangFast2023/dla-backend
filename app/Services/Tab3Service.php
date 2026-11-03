<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Traits\DateCalculatable;
use App\Traits\TotalNewListCalculatable;
use Carbon\Carbon;

class Tab3Service
{

    use DateCalculatable;
    use TotalNewListCalculatable;

    public function getData()
    {
        //---- tab 3

        return [
            'part6' =>  $this->Tab3_Part6_PositionSelect(),
            'part7' =>  $this->Tab3_Part7_RegionSelect(),
            'part8' =>  $this->Tab3_Part8_TableAllTypes(),
        ];
    }

    public function Tab3_Part6_PositionSelect()
    {
        $array = [];
        $position = db::table('positions_dla')
            ->join('prefixes_dla', 'positions_dla.id_prefix', '=', 'prefixes_dla.id')
            ->join('type_positions_dla', 'positions_dla.id_type', '=', 'type_positions_dla.id')
            ->select([
                'positions_dla.id_position          as pos_id',
                'positions_dla.name                 as pos_name',
                'prefixes_dla.name                  as pre_name',
                'type_positions_dla.type_position   as suf_name',
                'type_positions_dla.id              as type_id',
                'type_positions_dla.name            as type_name',
            ])
            ->get();
        foreach ($position as $pos) {
            $pos_id     =   (int)$pos->pos_id;
            $pos_name   =   $pos->pos_name;
            $pre_name   =   $pos->pre_name;
            $suf_name   =   $pos->suf_name;
            $type_id    =   (int)$pos->type_id;
            $type_name  =   $pos->type_name;
            if (!isset($array[$pos_id])) {
                $array[$pos_id] = [
                    'pos_id'        =>  $pos_id,
                    'pos_name'      =>  $pos_name,
                    'pre_name'      =>  $pre_name,
                    'suf_name'      =>  $suf_name,
                    'type_id'       =>  $type_id,
                    'type_name'     =>  $type_name,
                ];
            }
        }
        return $array;
    }

    public function Tab3_Part7_RegionSelect()
    {
        $data = [];
        $provinces_dla = db::table('provinces_dla')
            ->select([
                'id_main_province',
                'id_sub_province',
                'main_name_province',
                'sub_name_province'
            ])
            ->get();
        foreach ($provinces_dla as $prov) {
            $id_main_province   =   $prov->id_main_province;
            $id_sub_province    =   $prov->id_sub_province;
            $main_name_province =   $prov->main_name_province;
            $sub_name_province  =   $prov->sub_name_province;
            if (!isset($data[$id_main_province])) {
                $data[$id_main_province] = [
                    'name_main' =>  $main_name_province,
                    'sub'       =>  []
                ];
            }
            if (!isset($data[$id_main_province]['sub'][$id_sub_province])) {
                $data[$id_main_province]['sub'][$id_sub_province] = [
                    'id_sub'    =>  $id_sub_province,
                    'name'      =>  $sub_name_province
                ];
            }
        }
        return $data;
    }

    /**
     * @param int $id
     */
    public function updateTablePart6ForTab3($id)
    {
        $data_new = $this->getNewTotal();
        $array = [];
        foreach ($data_new['data'] as $key_r => $region) {
            foreach ($region['data'] as $key_z => $zones) {
                foreach ($zones['data'] as $key_t => $types) {
                    foreach ($types['data'] as $key_p => $position) {
                        $id_position = (int)$id;
                        $full_key_province = (int)($key_r . $key_z);
                        if ($key_p === $id_position) {
                            if (!isset($array[$full_key_province])) {
                                $array[$full_key_province] = [
                                    'full_key_province' => $full_key_province,
                                    'full_name_province' => $zones['full_name'],
                                    'status_open' => $position['open_status'],
                                    'total_round' => $position['total_round'],
                                    'total_listed' => $position['listed_old'],
                                    'total_listed_new' => $position['listed_new'],
                                    'total_diff' => $position['listed_diff'],
                                    'total_called' => $position['called_total'],
                                    'total_remain' => $position['remaining'],
                                    'stauts_of_exhaustion' => $position['account_status'],
                                    'data_round' => []
                                ];
                            }
                            $last_end = 0;
                            $prev_total = null;
                            foreach ($position['detail_round'] as $key_round => $rounds) {
                                $total = (int)($rounds['total'] ?? 0);
                                $ranks = $rounds['list_new_status'] ? collect($rounds['rank'] ?? [])->map(fn($item) => (array) $item)->all() : null;
                                if (!isset($array[$full_key_province]['data_round'][$key_round])) {
                                    $array[$full_key_province]['data_round'][$key_round] = [
                                        'round' => $key_round,
                                        'total' => $rounds['total'],
                                        'start' => 0,
                                        'end' => 0,
                                        'start_end' => null,
                                        'list_new_status' => $rounds['list_new_status'],
                                        'call_status' => $rounds['call_status'],
                                        'list_status' => $rounds['list_status'],
                                        'is_cross_region' => $rounds['is_cross_region'],
                                        'crossed_region' => $rounds['crossed_region'],
                                        'crossed_zone' => $rounds['crossed_zone'],
                                        'call_date' => $rounds['thai_short'],
                                        'status_work' => Carbon::parse($rounds['datetime'])->startOfDay()->lte(now()->startOfDay()) ? 'completed' : 'waiting',
                                        'percent_change' => 0,
                                        'proportion_used' => ($rounds['call_status'] && $position['account_status'] && $position['listed_new'] > 0) ? ($rounds['total'] / $position['listed_new']) * 100 : 0,
                                    ];
                                }
                                if ($rounds['list_new_status'] === false && $rounds['call_status'] === true) {
                                    $start = $last_end + 1;
                                    $end = $start + $total - 1;
                                    if ($total > 0) {
                                        $array[$full_key_province]['data_round'][$key_round]['start'] = $start;
                                        $array[$full_key_province]['data_round'][$key_round]['end'] = $end;
                                        $array[$full_key_province]['data_round'][$key_round]['start_end'][] = $total > 1 ? $start . '-' . $end : $start;
                                    }
                                    $last_end = ($total > 0) ? $end : $last_end;
                                } elseif ($rounds['list_new_status'] === true && $rounds['call_status'] === true) {
                                    foreach ($ranks as $rank) {
                                        $st_n = $rank['start'];
                                        $ed_n = $rank['end'];
                                        $diff = $st_n - $ed_n;
                                        $new_rank = $diff === 0 ? $st_n : $st_n . '-' . $ed_n;
                                        $array[$full_key_province]['data_round'][$key_round]['start_end'][] = $new_rank;
                                    }
                                }
                                if ($total > 0) {
                                    if ($prev_total !== null && $prev_total > 0) {
                                        $array[$full_key_province]['data_round'][$key_round]['percent_change'] = (($total - $prev_total) / $prev_total) * 100;
                                    } else {
                                        $array[$full_key_province]['data_round'][$key_round]['percent_change'] = 0;
                                    }
                                    $prev_total = $total;
                                } else {
                                    $array[$full_key_province]['data_round'][$key_round]['percent_change'] = null;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $array;
    }

    public function Tab3_Part8_TableAllTypes()
    {
        return Cache::remember('tab3_part8_table_all_types', 600, function () {

            $data_new = $this->getNewTotal();
            $array = [];
            foreach ($data_new['data'] as $key_r => $region) {
                foreach ($region['data'] as $key_z => $zones) {
                    foreach ($zones['data'] as $key_t => $types) {
                        foreach ($types['data'] as $posts) {
                            if (!isset($array[$key_r][$key_z][$key_t])) {
                                $array[$key_r][$key_z][$key_t] = [
                                    'prov_main_id' => $key_r,
                                    'prov_sub_id' => $key_z,
                                    'prov_main_name' => $region['name_main'],
                                    'prov_full_name' => $zones['full_name'],
                                    'prov_sub_name' => $zones['name_sub'],
                                    'pos_type_id' => $key_t,
                                    'pos_type' => $types['name_type_position'],
                                    'total_list' => $types['listed_old'],
                                    'total_list_new' => $types['listed_new'],
                                    'total_diff' => $types['listed_diff'],
                                    'total_call' => $types['called_total'],
                                    'total_remain' => $types['remaining'],
                                    'status_empty' => $types['remaining'] === 0,
                                    'status_called' => $types['called_total'] !== 0,
                                    'round_data' => [],
                                ];
                            }
                            foreach ($posts['detail_round'] as $key_round => $rounds) {
                                if (!isset($array[$key_r][$key_z][$key_t]['round_data'][$key_round])) {
                                    $array[$key_r][$key_z][$key_t]['round_data'][$key_round] = [
                                        'round' => $key_round,
                                        'total' => 0,
                                        'called' => false,
                                        'list_new_status' => false,
                                    ];
                                }
                                $array[$key_r][$key_z][$key_t]['round_data'][$key_round]['total'] += $rounds['total'];
                                $array[$key_r][$key_z][$key_t]['round_data'][$key_round]['list_new_status'] = $rounds['list_new_status'];
                            }
                        }
                    }
                }
            }
            foreach ($array as $ky => $region) {
                foreach ($region as $kz => $zones) {
                    foreach ($zones as $kt => $types) {
                        foreach ($types['round_data'] as $kr => $round) {
                            $array[$ky][$kz][$kt]['round_data'][$kr]['called'] = $round['total'] !== 0;
                        }
                    }
                }
            }
            return $array;
        });
    }
}
