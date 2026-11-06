<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


trait TotalNewListCalculatable
{

    public function getNewTotal()
    {
        $data = [
            'listed_old'    =>  0,
            'listed_new'    =>  0,
            'listed_diff'   =>  0,
            'listed_total'  =>  0,

            'called_old'    =>  0,
            'called_new'    =>  0,
            'called_diff'   =>  0,
            'called_total'  =>  0,

            'remaining'     =>  0,
            'data'          =>  []
        ];

        $positions_data = [];
        $positions_dla = db::table('positions_dla as pos')
            ->leftjoin('prefixes_dla as pre', 'pos.id_prefix', 'pre.id')
            ->leftjoin('type_positions_dla as type', 'pos.id_type', 'type.id')
            ->select(
                'pos.id_position        as  id_position',
                'pos.name               as  name_position',
                'pre.id                 as  id_prefix',
                'pre.name               as  name_prefix',
                'type.id                as  id_type',
                'type.name              as  name_type',
                'type.type_position     as  name_type_position'
            )
            ->get();

        foreach ($positions_dla as $pos) {
            $id_pos = $pos->id_position;
            $name_pre = $pos->name_prefix ?? null;
            $name_pos = $pos->name_type_position ?? null;
            $name_pot = $name_pre . $pos->name_position . $name_pos;
            $id_type = $pos->id_type;
            $name_type = $pos->name_type;

            if (!isset($positions_data[$id_type])) {
                $positions_data[$id_type] = [
                    'listed_old'    =>  0,
                    'listed_new'    =>  0,
                    'listed_diff'   =>  0,
                    'listed_total'  =>  0,

                    'called_old'    =>  0,
                    'called_new'    =>  0,
                    'called_diff'   =>  0,
                    'called_total'  =>  0,

                    'remaining'     =>  0,

                    'name_type_position'  =>  $name_type,
                    'data'          =>  []
                ];
            }

            if (!isset($positions_data[$id_type]['data'][$id_pos])) {
                $positions_data[$id_type]['data'][$id_pos] = [
                    'open_status'       =>  false,
                    'account_status'    =>  false,
                    'listed_old'        =>  0,
                    'listed_new'        =>  0,
                    'listed_diff'       =>  0,
                    'listed_total'      =>  0,

                    'total_round'       =>  0,

                    'called_old'        =>  0,
                    'called_new'        =>  0,
                    'called_diff'       =>  0,
                    'called_total'      =>  0,

                    'remaining'         =>  0,

                    'name_position'     =>  $name_pot,

                    'detail_round'      =>  []
                ];
            }
        }

        $provinces_dla = db::table('provinces_dla')->get();

        foreach ($provinces_dla as $prov) {
            $id_main = $prov->id_main_province;
            $id_sub  = $prov->id_sub_province;
            $name_main = $prov->main_name_province;
            $name_sub = $prov->sub_name_province;

            if (!isset($data['data'][$id_main])) {
                $data['data'][$id_main] = [
                    'name_main'     => $name_main,

                    'listed_old'    =>  0,
                    'listed_new'    =>  0,
                    'listed_diff'   =>  0,
                    'listed_total'  =>  0,

                    'total_round'   =>  0,

                    'called_old'    =>  0,
                    'called_new'    =>  0,
                    'called_diff'   =>  0,
                    'called_total'  =>  0,

                    'remaining'     =>  0,
                    'data'          => []
                ];
            }

            if (!isset($data['data'][$id_main]['data'][$id_sub])) {
                $data['data'][$id_main]['data'][$id_sub] = [
                    'name_sub'      => $name_sub,
                    'full_name'     => $name_main . ' ' . $name_sub,

                    'listed_old'    =>  0,
                    'listed_new'    =>  0,
                    'listed_diff'   =>  0,
                    'listed_total'  =>  0,

                    'total_round'   =>  0,

                    'called_old'    =>  0,
                    'called_new'    =>  0,
                    'called_diff'   =>  0,
                    'called_total'  =>  0,

                    'remaining'     =>  0,
                    'data'          => $positions_data
                ];
            }
        }

        $updated_list_dla = db::table('updated_list_dla as updated')
            ->leftjoin('positions_dla as post', 'updated.id_position', 'post.id_position')
            ->leftjoin('type_positions_dla as type', 'post.id_type', 'type.id')
            ->select('updated.*', 'type.id as id_type')
            ->get();

        foreach ($updated_list_dla as $update) {
            $id_main    = $update->id_main_province;
            $id_sub     = $update->id_sub_province;
            $id_post    = $update->id_position;
            $id_type    = $update->id_type;
            $total      = $update->total;
            $new_total  = $update->new_total;

            if (isset($data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post])) {
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['open_status']  = true;

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['listed_old']   = $total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['listed_new']   = $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['listed_diff']  = $total - $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['listed_total'] = $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['remaining']    = $new_total;

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['listed_old']   += $total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['listed_new']   += $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['listed_diff']  += $total - $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['listed_total'] += $new_total;
                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['remaining']    += $new_total;

                $data['data'][$id_main]['data'][$id_sub]['listed_old']   += $total;
                $data['data'][$id_main]['data'][$id_sub]['listed_new']   += $new_total;
                $data['data'][$id_main]['data'][$id_sub]['listed_diff']  += $total - $new_total;
                $data['data'][$id_main]['data'][$id_sub]['listed_total'] += $new_total;
                $data['data'][$id_main]['data'][$id_sub]['remaining']    += $new_total;

                $data['data'][$id_main]['listed_old']   += $total;
                $data['data'][$id_main]['listed_new']   += $new_total;
                $data['data'][$id_main]['listed_diff']  += $total - $new_total;
                $data['data'][$id_main]['listed_total'] += $new_total;
                $data['data'][$id_main]['remaining']    += $new_total;

                $data['listed_old']   += $total;
                $data['listed_new']   += $new_total;
                $data['listed_diff']  += $total - $new_total;
                $data['listed_total'] += $new_total;
                $data['remaining']    += $new_total;
            }
        }

        $calling_dla = db::table('calling_dla as call')
            ->leftjoin('positions_dla as post', 'call.id_position', 'post.id_position')
            ->leftjoin('type_positions_dla as type', 'post.id_type', 'type.id')
            ->select('call.*', 'type.id as id_type')
            ->get();

        foreach ($calling_dla as $call) {
            $id_main    = $call->id_main_province;
            $id_sub     = $call->id_sub_province;
            $id_post    = $call->id_position;
            $id_type    = $call->id_type;

            $round      = (int)$call->round;
            $total      = (int)$call->total;

            $call_status  = $call->call_status;
            $list_status  = $call->list_status;

            $called_day    = $call->called_day;
            $called_month  = $call->called_month;
            $called_year   = $call->called_year;

            $reported_day    = $call->reported_day;
            $reported_month  = $call->reported_month;
            $reported_year   = $call->reported_year;

            $is_cross_region    = $call->is_cross_region;
            $crossed_region     = $call->crossed_region;
            $crossed_zone       = $call->crossed_zone;

            $list_new_status    = $call->list_new_status;
            $rank               = $call->rank;

            if (!isset($data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['detail_round'][$round])) {
                $report_date = Carbon::create(
                    $reported_year,
                    $reported_month
                )->locale('th');

                $date = Carbon::create(
                    $called_year,
                    $called_month,
                    $called_day
                )->locale('th');

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['detail_round'][$round] = [
                    'round'             =>  $round,
                    'total'             =>  $total,
                    'call_status'       =>  $call_status,
                    'list_status'       =>  $list_status,

                    'report_thai_full'  =>  $reported_day . ' ' . $report_date->copy()->addYears(543)->translatedFormat('F Y'),
                    'report_thai_short' =>  $reported_day . ' ' . $report_date->copy()->addYears(543)->translatedFormat('M Y'),

                    'datetime'          =>  $date->format('Y-m-d H:i:s'),
                    'thai_full'         =>  $date->copy()->addYears(543)->translatedFormat('j F Y'),
                    'thai_short'        =>  $date->copy()->addYears(543)->translatedFormat('j M Y'),
                    'work_status'       =>  $date->lessThanOrEqualTo(Carbon::today()),


                    'is_cross_region'   =>  $is_cross_region,
                    'crossed_region'    =>  $crossed_region,
                    'crossed_zone'      =>  $crossed_zone,
                    'list_new_status'   =>  $list_new_status,
                    'rank'              =>  $call_status ? json_decode($rank) : null
                ];

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['total_round'] += 1;

                $name_called = $list_new_status ? 'called_new' : 'called_old';
                if ($list_status) {
                    if (!$list_new_status) {
                        $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post][$name_called] += $total;
                        $data['data'][$id_main]['data'][$id_sub]['data'][$id_type][$name_called] += $total;
                        $data['data'][$id_main]['data'][$id_sub][$name_called] += $total;
                        $data['data'][$id_main][$name_called] += $total;
                        $data[$name_called] += $total;
                    } else {
                        $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post][$name_called] += $total;
                        $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['called_total'] += $total;
                        $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['remaining'] -= $total;
                    }
                }

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['called_diff'] =
                    $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['called_old'] -
                    $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['called_new'];

                $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['called_diff'] =
                    $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['called_old'] -
                    $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['called_new'];

                $data['data'][$id_main]['data'][$id_sub]['called_diff'] =
                    $data['data'][$id_main]['data'][$id_sub]['called_old'] -
                    $data['data'][$id_main]['data'][$id_sub]['called_new'];

                $data['data'][$id_main]['called_diff'] =
                    $data['data'][$id_main]['called_old'] -
                    $data['data'][$id_main]['called_new'];

                $data['called_diff'] =
                    $data['called_old'] -
                    $data['called_new'];
            }

            $data['data'][$id_main]['data'][$id_sub]['total_round'] = $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['total_round'];
            $data['data'][$id_main]['total_round'] = $data['data'][$id_main]['data'][$id_sub]['data'][$id_type]['data'][$id_post]['total_round'];
        }

        $total_fields = [
            'listed_old',
            'listed_new',
            'listed_diff',
            'listed_total',
            'called_old',
            'called_new',
            'called_diff',
            'called_total',
            'remaining',
        ];

        foreach ($total_fields as $field) {
            $data[$field] = 0;
        }

        foreach ($data['data'] as $key1 => $region) {
            foreach ($total_fields as $field) {
                $data['data'][$key1][$field] = 0;
            }
            foreach ($region['data'] as $key2 => $zone) {
                foreach ($total_fields as $field) {
                    $data['data'][$key1]['data'][$key2][$field] = 0;
                }
                foreach ($zone['data'] as $key3 => $type) {
                    foreach ($total_fields as $field) {
                        $data['data'][$key1]['data'][$key2]['data'][$key3][$field] = 0;
                    }
                    foreach ($type['data'] as $key4 => $position) {
                        $position['called_diff'] = $position['called_old'] - $position['called_new'];
                        $data['data'][$key1]['data'][$key2]['data'][$key3]['data'][$key4]['called_diff'] = $position['called_diff'];
                        foreach ($total_fields as $field) {
                            $data['data'][$key1]['data'][$key2]['data'][$key3][$field] += $position[$field] ?? 0;
                        }
                    }
                    $data['data'][$key1]['data'][$key2]['data'][$key3]['called_diff'] = $data['data'][$key1]['data'][$key2]['data'][$key3]['called_old'] - $data['data'][$key1]['data'][$key2]['data'][$key3]['called_new'];
                    foreach ($total_fields as $field) {
                        $data['data'][$key1]['data'][$key2][$field] += $data['data'][$key1]['data'][$key2]['data'][$key3][$field];
                    }
                }
                $data['data'][$key1]['data'][$key2]['called_diff'] = $data['data'][$key1]['data'][$key2]['called_old'] - $data['data'][$key1]['data'][$key2]['called_new'];
                foreach ($total_fields as $field) {
                    $data['data'][$key1][$field] += $data['data'][$key1]['data'][$key2][$field];
                }
            }
            $data['data'][$key1]['called_diff'] = $data['data'][$key1]['called_old'] - $data['data'][$key1]['called_new'];
            foreach ($total_fields as $field) {
                $data[$field] += $data['data'][$key1][$field];
            }
        }

        $data['called_diff'] = $data['called_old'] - $data['called_new'];

        foreach ($data['data'] as $key1 => $region) {
            foreach ($region['data'] as $key2 => $zone) {
                foreach ($zone['data'] as $key3 => $type) {
                    foreach ($type['data'] as $key4 => $position) {
                        $remaining = $position['remaining'];
                        $listed_old_exhausted = $position['listed_old'] > 0 && $position['called_old'] >= $position['listed_old'];
                        $has_new_list = $position['listed_new'] > 0;
                        $new_list_not_called = $position['called_new'] == 0;
                        $account_status = $remaining <= 0 || ($listed_old_exhausted && $has_new_list && $new_list_not_called);
                        $data['data'][$key1]['data'][$key2]['data'][$key3]['data'][$key4]['account_status'] = $account_status;
                    }
                }
            }
        }

        foreach ($data['data'] as $key1 => $region) {
            foreach ($region['data'] as $key2 => $zone) {
                foreach ($zone['data'] as $key3 => $type) {
                    foreach ($type['data'] as $key4 => $position) {
                        if (
                            $position['account_status'] === true &&
                            $position['called_new'] == 0 &&
                            $position['listed_old'] > 0 &&
                            $position['called_old'] >= $position['listed_old'] &&
                            $position['listed_new'] > 0
                        ) {
                            $adjusted_total = $position['listed_new'];
                            $data['data'][$key1]['data'][$key2]['data'][$key3]['data'][$key4]['called_total'] += $adjusted_total;
                            $data['data'][$key1]['data'][$key2]['data'][$key3]['called_total'] += $adjusted_total;
                            $data['data'][$key1]['data'][$key2]['called_total'] += $adjusted_total;
                            $data['data'][$key1]['called_total'] += $adjusted_total;
                            $data['called_total'] += $adjusted_total;

                            $data['data'][$key1]['data'][$key2]['data'][$key3]['data'][$key4]['remaining'] -= $adjusted_total;
                            $data['data'][$key1]['data'][$key2]['data'][$key3]['remaining'] -= $adjusted_total;
                            $data['data'][$key1]['data'][$key2]['remaining'] -= $adjusted_total;
                            $data['data'][$key1]['remaining'] -= $adjusted_total;
                            $data['remaining'] -= $adjusted_total;
                        }
                    }
                }
            }
        }
        return $data;
    }
}
