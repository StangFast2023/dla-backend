<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ProvincesDla;
use App\Models\PositionDla;
use App\Models\TypePositionDla;
use App\Traits\DateCalculatable;
use App\Traits\TotalNewListCalculatable;
use Carbon\Carbon;

class Tab4Service
{

    use DateCalculatable;
    use TotalNewListCalculatable;

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
            // $data_new = $this->getNewTotal();
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
        $data_new = $this->getNewTotal();
        $array_new = [];
        foreach ($data_new['data'] as $key_r => $region) {
            foreach ($region['data'] as $key_z => $zones) {
                foreach ($zones['data'] as $key_t => $types) {
                    foreach ($types['data'] as $key_p => $position) {
                        if (array_key_exists($key_r, $array_province)) {
                            if (!isset($array_new[$key_r])) {
                                $array_new[$key_r] = [
                                    'pro_main_id'       =>  $key_r,
                                    'pro_main_name'     =>  $region['name_main'],
                                    'total_position'    =>  0,
                                    'total_listed'      =>  0,
                                    'total_listed_n'    =>  0,
                                    'total_diff'        =>  0,
                                    'total_called'      =>  0,
                                    'total_remain'      =>  0,
                                    'total_each_round'  =>  [],
                                    'pro_sub'           =>  []
                                ];
                            }
                            if (in_array($key_z, $array_province[$key_r]['sub'], true)) {
                                if (!isset($array_new[$key_r]['pro_sub'][$key_z])) {
                                    $array_new[$key_r]['pro_sub'][$key_z] = [
                                        'pro_sub_id'            =>  $key_z,
                                        'pro_sub_name'          =>  $zones['name_sub'],
                                        'total_listed'          =>  0,
                                        'total_listed_n'        =>  0,
                                        'total_diff'            =>  0,
                                        'total_called'          =>  0,
                                        'total_remain'          =>  0,
                                        'total_each_round'      =>  [],
                                        'data_type_position'    =>  []
                                    ];
                                }
                                if (!isset($array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t])) {
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t] = [
                                        'pos_type_id'           =>  $key_t,
                                        'pos_type_name'         =>  $types['name_type_position'],
                                        'has_data'              =>  false,
                                        'total_listed'          =>  0,
                                        'total_listed_n'        =>  0,
                                        'total_diff'            =>  0,
                                        'total_called'          =>  0,
                                        'total_remain'          =>  0,
                                        'total_each_round'      =>  [],
                                        'data_position'         =>  []
                                    ];
                                }
                                if (in_array($key_p, $array_position)) {

                                    //--- region
                                    $array_new[$key_r]['total_position']  += $position['open_status'] ? 1 : 0;
                                    $array_new[$key_r]['total_listed']    += $position['listed_old'];
                                    $array_new[$key_r]['total_listed_n']  += $position['listed_new'];
                                    $array_new[$key_r]['total_diff']      += $position['listed_diff'];
                                    $array_new[$key_r]['total_called']    += $position['called_total'];
                                    $array_new[$key_r]['total_remain']    += $position['remaining'];

                                    //--- zones
                                    $array_new[$key_r]['pro_sub'][$key_z]['total_listed']   += $position['listed_old'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['total_listed_n'] += $position['listed_new'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['total_diff']     += $position['listed_diff'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['total_called']   += $position['called_total'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['total_remain']   += $position['remaining'];

                                    //--- data_type_position
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_listed']   += $position['listed_old'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_listed_n'] += $position['listed_new'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_diff']     += $position['listed_diff'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_called']   += $position['called_total'];
                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_remain']   += $position['remaining'];

                                    $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['has_data'] = true;

                                    if (!isset($array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p])) {
                                        $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p] = [
                                            'pos_id'                =>  $key_p,
                                            'pos_name'              =>  $position['name_position'],
                                            'pos_type_id'           =>  $key_t,
                                            'pos_type_name'         =>  $types['name_type_position'],
                                            'status_open'           =>  $position['open_status'],
                                            'total_listed'          =>  $position['listed_old'],
                                            'total_listed_n'        =>  $position['listed_new'],
                                            'total_diff'            =>  $position['listed_diff'],
                                            'total_call_round'      =>  $position['total_round'],
                                            'total_call'            =>  $position['called_total'],
                                            'total_remain'          =>  $position['remaining'],
                                            'status_out_of_lits'    =>  $position['account_status'],
                                            'data_call_round'       =>  []
                                        ];
                                    }

                                    $last_end = 0;
                                    $prev_total = null;
                                    foreach ($position['detail_round'] as $key_rd => $round) {
                                        $total = (int)($round['total'] ?? 0);

                                        //--- region
                                        if (!isset($array_new[$key_r]['total_each_round'][$key_rd])) {
                                            $array_new[$key_r]['total_each_round'][$key_rd] = [
                                                'round' => $key_rd,
                                                'total' => 0
                                            ];
                                        }
                                        $array_new[$key_r]['total_each_round'][$key_rd]['total'] += $total;

                                        //--- zones
                                        if (!isset($array_new[$key_r]['pro_sub'][$key_z]['total_each_round'][$key_rd])) {
                                            $array_new[$key_r]['pro_sub'][$key_z]['total_each_round'][$key_rd] = [
                                                'round' => $key_rd,
                                                'total' => 0
                                            ];
                                        }
                                        $array_new[$key_r]['pro_sub'][$key_z]['total_each_round'][$key_rd]['total'] += $total;

                                        //--- data_type_position
                                        if (!isset($array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_each_round'][$key_rd])) {
                                            $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_each_round'][$key_rd] = [
                                                'round' => $key_rd,
                                                'total' => 0
                                            ];
                                        }
                                        $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['total_each_round'][$key_rd]['total'] += $total;

                                        $ranks = $round['list_new_status'] ? collect($round['rank'] ?? [])->map(fn($item) => (array) $item)->all() : null;
                                        if (!isset($array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd])) {
                                            $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd] = [
                                                'round'             =>  $key_rd,
                                                'total'             =>  $round['total'],
                                                'start'             =>  0,
                                                'end'               =>  0,
                                                'percent_change'    =>  0,
                                                'status_call'       =>  $round['call_status'],
                                                'status_list'       =>  $round['list_status'],
                                                'status_cross'      =>  $round['is_cross_region'],
                                                'crossed_region'    =>  $round['crossed_region'],
                                                'crossed_zone'      =>  $round['crossed_zone'],
                                                'date'              =>  $round['thai_short'],
                                                'status'            =>  Carbon::parse($round['datetime'])->startOfDay()->lte(now()->startOfDay()) ? 'completed' : 'waiting',
                                                'list_new_status'   =>  $round['list_new_status'],
                                                'start_end'         =>  null
                                            ];
                                        }
                                        if ($round['list_new_status'] === false && $round['call_status'] === true) {
                                            $start = $last_end + 1;
                                            $end = $start + $total - 1;
                                            if ($total > 0) {
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['start'] = $start;
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['end'] = $end;
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['start_end'][] = $total > 1 ? $start . '-' . $end : $start;
                                            }
                                            $last_end = ($total > 0) ? $end : $last_end;
                                        } elseif ($round['list_new_status'] === true && $round['call_status'] === true) {
                                            foreach ($ranks as $rank) {
                                                $st_n = $rank['start'];
                                                $ed_n = $rank['end'];
                                                $diff = $st_n - $ed_n;
                                                $new_rank = $diff === 0 ? $st_n : $st_n . '-' . $ed_n;
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['start_end'][] = $new_rank;
                                            }
                                        }
                                        if ($total > 0) {
                                            if ($prev_total !== null && $prev_total > 0) {
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['percent_change'] = (($total - $prev_total) / $prev_total) * 100;
                                            } else {
                                                $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['percent_change'] = 0;
                                            }
                                            $prev_total = $total;
                                        } else {
                                            $array_new[$key_r]['pro_sub'][$key_z]['data_type_position'][$key_t]['data_position'][$key_p]['data_call_round'][$key_rd]['percent_change'] = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach ($array_new as &$region) {
            foreach ($region['pro_sub'] as &$sub) {
                foreach ($sub['data_type_position'] as $key_t => &$type) {
                    if (!$type['has_data']) {
                        unset($sub['data_type_position'][$key_t]);
                    }
                    foreach ($type['data_position'] as $key_p => $position) {
                        if (!$showEmpty) {
                            if (!$type['data_position'][$key_p]['status_open']) {
                                unset($type['data_position'][$key_p]);
                            }
                        }
                    }
                }
            }
        }
        unset($region, $sub, $type);
        $max_round = db::table('calling_dla')->max('round');
        $data = ['tab4' => ['part2' => ['round' => $max_round, 'data' => $array_new]]];
        return $data;
    }
}
