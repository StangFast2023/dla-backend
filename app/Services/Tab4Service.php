<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CallingDla;
use App\Models\UpdateListDla;
use App\Models\ProvincesDla;
use App\Models\PrefixsDla;
use App\Models\PositionDla;
use App\Models\TypePositionDla;
use App\Traits\DateCalculatable;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Tab4Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 4
        return [
            'part1' =>  $this->Tab4_Part1_Filter()
        ];
    }

    public function Tab4_Part1_Filter()
    {
        return Cache::remember('tab4_filter_data', 600, function () {
            $prov = ProvincesDla::all();
            $region = [];
            foreach ($prov as $pr) {
                $region[$pr->id_main_province]['main'] = $pr->id_main_province;
                $region[$pr->id_main_province]['main_name'] = $pr->main_name_province;
                $region[$pr->id_main_province]['sub'][$pr->id_sub_province] = [
                    'sub_name' => $pr->main_name_province . ' ' . $pr->sub_name_province
                ];
            }
            $types = TypePositionDla::all()->keyBy('id');
            $post = PositionDla::with('prefix')->get();
            $positions = [];
            foreach ($post as $ps) {
                $id_type = substr($ps->id_position, 0, 1);
                if (!isset($positions[$id_type])) {
                    $positions[$id_type] = [
                        'type_name' => $types[$id_type]->name ?? 'Unknown',
                        'data_position' => []
                    ];
                }
                $prefName = $ps->prefix ? $ps->prefix->name : '';
                $positions[$id_type]['data_position'][$ps->id_position] = [
                    'pos_id'        => $ps->id,
                    'pos_main_id'   => $ps->id_position,
                    'full_pos_name' => $prefName . $ps->name,
                    'type_name'     => $types[$id_type]->name ?? 'Unknown'
                ];
            }
            return [
                'region'    => $region,
                'types'     => $types->toArray(),
                'positions' => $positions
            ];
        });
    }

    public function updateTableForTab4(array $array_position, array $array_province, bool $showEmpty)
    {
        $all_Positions = db::table('positions_dla')
            ->join('prefixes_dla', 'prefixes_dla.id', '=', 'positions_dla.id_prefix')
            ->join('type_positions_dla', 'type_positions_dla.id', '=', 'positions_dla.id_type')
            ->whereIn('positions_dla.id_position', $array_position)
            ->select([
                'positions_dla.id_position as pos_id',
                'positions_dla.name as pos_name',
                'prefixes_dla.name as pref_name',
                'type_positions_dla.type_position as suff_name',
                'type_positions_dla.id as pos_type_id',
                'type_positions_dla.name as pos_type_name'
            ])
            ->get();
        $all_pos_array = [];
        foreach ($all_Positions as $pos) {
            if (!isset($all_pos_array[$pos->pos_type_id])) {
                $all_pos_array[$pos->pos_type_id] = [
                    'pos_type_id'       =>  $pos->pos_type_id,
                    'pos_type_name'     =>  $pos->pos_type_name,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_each_round'  =>  [],
                    'data_position'     =>  []
                ];
            }
            if (!isset($all_pos_array[$pos->pos_type_id]['data_position'][$pos->pos_id])) {
                $all_pos_array[$pos->pos_type_id]['data_position'][$pos->pos_id] = [
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
        $provinceData = DB::table('provinces_dla')
            ->get()
            ->groupBy('id_main_province');
        Log::info($provinceData);
        foreach ($array_province as $key => $arr_pro) {
            $main_provinces = $provinceData[$key]->first() ?? null;
            if (!$main_provinces) {
                continue;
            }
            if (!isset($array[$main_provinces->id_main_province])) {
                $array[$main_provinces->id_main_province] = [
                    'pro_main_id'       =>  $main_provinces->id_main_province,
                    'pro_main_name'     =>  $main_provinces->main_name_province,
                    'total_position'    =>  0,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_each_round'  =>  [],
                    'pro_sub'           =>  []
                ];
            }
            $sub_provinces = $provinceData[$key]->whereIn('id_sub_province', $arr_pro['sub']);
            foreach ($sub_provinces as $prov) {
                if (!isset($array[$prov->id_main_province]['pro_sub'][$prov->id_sub_province])) {
                    $array[$prov->id_main_province]['pro_sub'][$prov->id_sub_province] = [
                        'pro_sub_id'            =>  $prov->id_sub_province,
                        'pro_sub_name'          =>  $prov->sub_name_province,
                        'total_listed'          =>  0,
                        'total_called'          =>  0,
                        'total_remain'          =>  0,
                        'total_each_round'      =>  [],
                        'data_type_position'    =>  $all_pos_array
                    ];
                }
            }
        }
        $listed_position = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', '=', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', '=', 'positions_dla.id_type')
            ->selectRaw("
                updated_list_dla.id_main_province as prov_main_id,
                updated_list_dla.id_sub_province as prov_sub_id,
                type_positions_dla.id as pos_type_id,
                positions_dla.id_position as pos_id,
                SUM(total::integer) as total
            ")
            ->whereIn('updated_list_dla.id_position', $array_position)
            ->whereIn('updated_list_dla.id_main_province', array_keys($array_province))
            ->groupBy('prov_main_id', 'prov_sub_id', 'pos_type_id', 'pos_id')
            ->orderBy('pos_id', 'asc')
            ->get();

        foreach ($listed_position as $pos) {
            $prov_main_id   =   $pos->prov_main_id;
            $prov_sub_id    =   $pos->prov_sub_id;
            $pos_type_id    =   $pos->pos_type_id;
            $pos_id         =   $pos->pos_id;
            $total          =   $pos->total;
            if (isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id])) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['status_open'] = (int)$total !== 0;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['total_listed'] = (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['total_remain'] = (int)$total;

                $array[$prov_main_id]['total_listed'] += (int)$total;
                $array[$prov_main_id]['total_remain'] += (int)$total;

                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_listed'] += (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] += (int)$total;

                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_listed'] += (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_remain'] += (int)$total;
            }
        }
        $current_date    =  Carbon::today();
        $called_position = db::table('calling_dla')
            ->join('positions_dla', 'positions_dla.id_position', '=', 'calling_dla.id_position')
            ->select([
                'calling_dla.*',
                'positions_dla.id_type as pos_type_id'
            ])
            ->whereIn('calling_dla.id_position', $array_position)
            ->get();
        foreach ($called_position as $pos) {

            $prov_main_id   =   $pos->id_main_province;
            $prov_sub_id    =   $pos->id_sub_province;

            $pos_type_id    =   $pos->pos_type_id;
            $pos_id         =   $pos->id_position;
            $round          =   $pos->round;
            $total          =   $pos->total;

            $is_cross_region    =   $pos->is_cross_region;
            $crossed_region     =   $pos->crossed_region;
            $crossed_zone       =   $pos->crossed_zone;

            $call_status        =   $pos->call_status;
            $list_status        =   $pos->list_status;

            if (isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id])) {

                if ($call_status === true) {
                    $call_date      =   Carbon::createFromDate($pos->called_year, $pos->called_month, $pos->called_day);
                    $status         =   $call_date->greaterThan($current_date) ? 'waiting' : 'completed';
                } else {
                    $call_date      =   null;
                    $status         =   $list_status === true ? 'not-used' : 'exhaustion';
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

                if (!isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_each_round'][$round])) {
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_each_round'][$round] = [
                        'round' =>  $round,
                        'total' =>  0
                    ];
                }

                if (!isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['data_call_round'][$round])) {
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['total_call_round'] += 1;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['data_call_round'][$round] = [
                        'round'             =>  $round,
                        'total'             =>  (int)$total,
                        'start'             =>  0,
                        'end'               =>  0,
                        'start_end_2'       =>  0,
                        'percent_change'    =>  null,
                        'status_call'       =>  $call_status,
                        'status_list'       =>  $list_status,
                        'status_cross'      =>  $is_cross_region,
                        'crossed_region'    =>  $is_cross_region === true ? $crossed_region    : null,
                        'crossed_zone'      =>  $is_cross_region === true ? $crossed_zone      : null,
                        'date'              =>  $call_date ? $call_date->format('d-m-Y')    : null,
                        'status'            =>  $status,
                    ];
                }

                if ($call_status === true) {
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['total_call'] += $total;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id]['total_remain'] -= $total;

                    $array[$prov_main_id]['total_called'] += (int)$total;
                    $array[$prov_main_id]['total_remain'] -= (int)$total;
                    $array[$prov_main_id]['total_each_round'][$round]['total'] += $total;

                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_called'] += (int)$total;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] -= (int)$total;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_each_round'][$round]['total'] += $total;

                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_called'] += (int)$total;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_remain'] -= (int)$total;
                    $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['total_each_round'][$round]['total'] += $total;
                }
            }
        }
        foreach ($array as &$prov) {
            foreach ($prov['pro_sub'] as &$prov_sub) {
                foreach ($prov_sub['data_type_position'] as &$data_type) {
                    foreach ($data_type['data_position'] as &$pos) {
                        $pos['status_out_of_lits'] = ($pos['total_remain'] === 0);
                        $last_end = 0;
                        $prev_total = null;
                        foreach ($pos['data_call_round'] as &$round) {
                            $total = (int)($round['total'] ?? 0);
                            $start = $last_end + 1;
                            $end = $start + $total - 1;
                            $round['start'] = $start;
                            $round['end'] = $end;

                            if ($total > 0) {
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
                        }
                    }
                }
            }
        }
        foreach ($array as &$prov) {
            foreach ($prov['pro_sub'] as &$prov_sub) {
                foreach ($prov_sub['data_type_position'] as &$data_type) {
                    $data_type['data_position'] = array_filter($data_type['data_position'], function ($pos) use ($showEmpty) {
                        $isPassEmpty = ($showEmpty === false) ? ($pos['status_open'] === true) : true;
                        return $isPassEmpty;
                    });
                    $data_type['data_position'] = array_values($data_type['data_position']);
                }
            }
        }
        foreach ($array as &$fil) {
            foreach ($fil['pro_sub'] as $subId => &$sub) {
                $sub['data_type_position'] = array_filter($sub['data_type_position'], function ($type) {
                    return !empty($type['data_position']);
                });
            }
            $fil['pro_sub'] = array_filter($fil['pro_sub'], function ($sub) {
                return !empty($sub['data_type_position']);
            });
        }
        unset($fil, $sub);
        $array = array_filter($array, function ($fil) {
            return !empty($fil['pro_sub']);
        });
        $max_round = db::table('calling_dla')->max('round');
        $data = ['tab4' => ['part2' => ['round' => $max_round, 'data' => $array]]];
        return $data;
    }
}
