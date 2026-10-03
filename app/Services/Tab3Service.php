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
use Carbon\Carbon;

class Tab3Service
{

    use DateCalculatable;

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
        $array = [];
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
            $full_key_province  =   $id_main_province . $id_sub_province;

            $main_name_province = $prov->main_name_province;
            $sub_name_province  = $prov->sub_name_province;
            $full_name_province = $main_name_province . ' ' . $sub_name_province;

            if (!isset($array[$full_key_province])) {
                $array[$full_key_province] = [
                    'full_key_province'     =>  (int)$full_key_province,
                    'full_name_province'    =>  $full_name_province,
                    'status_open'           =>  false,
                    'total_round'           =>  0,
                    'total_listed'          =>  0,
                    'total_listed_new'      =>  0,
                    'total_diff'            =>  0,
                    'total_called'          =>  0,
                    'total_remain'          =>  0,
                    'stauts_of_exhaustion'  =>  false,
                    'data_round'            =>  []
                ];
            }
        }

        $updated_list_dla = db::table('updated_list_dla')
            ->where('updated_list_dla.id_position', '=', $id)
            ->select([
                'id_main_province',
                'id_sub_province',
                'total',
                'new_total'
            ])
            ->get();
        foreach ($updated_list_dla as $updated) {
            $total      =   $updated->total;
            $new_total  =   $updated->new_total;

            $id_main_province   =   $updated->id_main_province;
            $id_sub_province    =   $updated->id_sub_province;
            $full_key_province  =   $id_main_province . $id_sub_province;
            if (isset($array[$full_key_province])) {
                $array[$full_key_province]['status_open'] = true;
                $array[$full_key_province]['total_listed'] = $total;
                $array[$full_key_province]['total_listed_new'] = $new_total;
                $array[$full_key_province]['total_diff'] = $total - $new_total;
                $array[$full_key_province]['total_remain'] = $new_total;
            }
        }

        $calling_dla = db::table('calling_dla')
            ->where('calling_dla.id_position', '=', $id)
            ->select([
                'id_main_province',
                'id_sub_province',
                'round',
                'total',
                'call_status',
                'list_status',
                'called_day',
                'called_month',
                'called_year',
                'is_cross_region',
                'crossed_region',
                'crossed_zone'
            ])
            ->get();

        $current_date    =  Carbon::today();
        foreach ($calling_dla as $called) {
            $id_main_province   =   $called->id_main_province;
            $id_sub_province    =   $called->id_sub_province;
            $full_key_province  =   $id_main_province . $id_sub_province;

            $round    =   (int)$called->round;
            $total    =   (int)$called->total;

            $call_status    =   (bool)$called->call_status;
            $list_status    =   (bool)$called->list_status;

            $called_day     =   (int)$called->called_day;
            $called_month   =   (int)$called->called_month;
            $called_year    =   (int)$called->called_year;

            $is_cross_region    =   (bool)$called->is_cross_region;
            $crossed_region     =   $called->crossed_region;
            $crossed_zone       =   $called->crossed_zone;

            if ($call_status === true) {
                $call_date      =   Carbon::createFromDate($called_year, $called_month, $called_day);
                $status         =   $call_date->greaterThan($current_date) ? 'waiting' : 'completed';
            } else {
                $call_date      =   null;
                $status         =   $list_status === true ? 'not-used' : 'exhaustion';
            }
            $call_date_thai = $call_date ? (int)$call_date->format('d') . ' ' . $this->monthYearThai(true, $call_date->format('m'), $call_date->format('Y'))  : null;
            if (isset($array[$full_key_province]) && $call_status === true) {
                $array[$full_key_province]['status_open'] = true;
                $array[$full_key_province]['total_called'] += $total;
                $array[$full_key_province]['total_remain'] -= $total;
            }

            if (!isset($array[$full_key_province]['data_round'][$round])) {
                $array[$full_key_province]['total_round'] += 1;
                $array[$full_key_province]['data_round'][$round] = [
                    'round' =>  $round,
                    'total' =>  $total,

                    'start'         =>  0,
                    'end'           =>  0,
                    'start_end'     =>  0,

                    'call_status'   =>  $call_status,
                    'list_status'   =>  $list_status,

                    'called_day'    =>  $call_status === true ? $called_day     : null,
                    'called_month'  =>  $call_status === true ? $called_month   : null,
                    'called_year'   =>  $call_status === true ? $called_year    : null,

                    'is_cross_region'   =>  $is_cross_region === true,
                    'crossed_region'    =>  $is_cross_region === true ? $crossed_region     : null,
                    'crossed_zone'      =>  $is_cross_region === true ? $crossed_zone       : null,

                    'call_date'         =>  $call_date_thai,
                    'status_work'       =>  $status,
                    'percent_change'    =>  0,
                    'proportion_used'   =>  0,
                ];
            }
        }
        foreach ($array as &$arr) {
            $last_end = 0;
            $prev_total = null;
            $total_listed = $arr['total_listed'];
            foreach ($arr['data_round'] as &$round) {
                $total = (int)($round['total'] ?? 0);
                $start = $last_end + 1;
                $end = $start + $total - 1;

                if ($total > 0) {
                    $round['start'] = $start;
                    $round['end'] = $end;
                    $round['start_end'] = $total > 1 ? $start . ' - ' . $end : $start;
                }

                $last_end = ($total > 0) ? $end : $last_end;

                if ($total > 0) {
                    if ($prev_total !== null && $prev_total > 0) {
                        $round['percent_change'] = (($total - $prev_total) / $prev_total) * 100;
                    } else {
                        $round['percent_change'] = 0;
                    }
                    $prev_total = $total;
                } else {
                    $round['percent_change'] = null;
                }


                if ($total > 0) {
                    $round['proportion_used'] = ($total / $total_listed) * 100;
                }
            }
        }
        return $array;
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
                ->select(db::raw('updated_list_dla.id_main_province as prov_main_id, updated_list_dla.id_sub_province as prov_sub_id, positions_dla.id_type as pos_type_id, type_positions_dla.name as pos_type, sum(total::integer) as total, sum(new_total::integer) as new_total'))
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
                    'total_list_new' => (int)$type->new_total,
                    'total_diff'     => (int)($type->total - $type->new_total),
                    'total_call'     => 0,
                    'total_remain'   => (int)$type->new_total,
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
