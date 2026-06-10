<?php

namespace App\Services;

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

class Tab5Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 5

        return [
            'part1' =>  $this->Tab5_Part1_FilterData(),
        ];
    }

    public function Tab5_Part1_FilterData()
    {
        $fullMonthly = $this->getAccountTimeline(false);
        $all_pos_array = [];
        $all_Positions = db::table('positions_dla')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
            ->select(db::raw('
                positions_dla.id_position                   as  pos_id          ,
                positions_dla.name                          as  pos_name        ,

                prefixes_dla.name                           as  pref_name       ,
                type_positions_dla.type_position            as  suff_name       ,

                positions_dla.id_type                       as  pos_type_id     ,
                type_positions_dla.name                     as  pos_type_name
            '))
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
            ->select(db::raw("
                provinces_dla.id                    as pro_id           ,
                provinces_dla.id_main_province      as pro_main_id      ,
                provinces_dla.main_name_province    as pro_main_name    ,
                provinces_dla.id_sub_province       as pro_sub_id       ,
                provinces_dla.sub_name_province     as pro_sub_name     ,
                concat( provinces_dla.main_name_province || ' ' || provinces_dla.sub_name_province )  as pro_full_name 
            "))
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
                    'pro_sub_id'            =>  $prov->pro_sub_id,
                    'pro_sub_name'          =>  $prov->pro_full_name,
                    'total_listed'          =>  0,
                    'total_called'          =>  0,
                    'total_remain'          =>  0,
                    'total_each_round'      =>  [],
                    'data_position'         =>  $all_pos_array
                ];
            }
        }
        $listed_position = db::table('updated_list_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', 'positions_dla.id_type')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->select(db::raw('
                updated_list_dla.id_main_province               as  prov_main_id    ,
                updated_list_dla.id_sub_province                as  prov_sub_id     ,
                positions_dla.id_type                           as  pos_type_id     ,
                positions_dla.id_position                       as  pos_id          ,
                sum( total::integer )                           as  total
            '))
            ->groupBy('prov_main_id', 'prov_sub_id', 'pos_type_id', 'pos_id')
            ->orderBy('pos_id', 'asc')
            ->get()
            ->toArray();
        foreach ($listed_position as $pos) {
            $prov_main_id   =   $pos->prov_main_id;
            $prov_sub_id    =   $pos->prov_sub_id;
            $pos_type_id    =   $pos->pos_type_id;
            $pos_id         =   $pos->pos_id;
            $total          =   $pos->total;
            if (isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id])) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['status_open'] = (int)$total !== 0;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_listed'] = (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_remain'] = (int)$total;
            }
            $array[$prov_main_id]['total_listed'] += (int)$total;
            $array[$prov_main_id]['total_remain'] += (int)$total;

            $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_listed'] += (int)$total;
            $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] += (int)$total;
        }

        $max_round = db::table('calling_dla')->max('round');
        $called_position = db::table('calling_dla')
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'calling_dla.id_position')
            ->select(db::raw('calling_dla.* , positions_dla.id_type   as  pos_type_id'))
            ->get()
            ->toArray();
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
            $current_date       =   Carbon::today();

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

            if ($call_status === true) {
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_call'] += $total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_position'][$pos_id]['total_remain'] -= $total;

                $array[$prov_main_id]['total_called'] += (int)$total;
                $array[$prov_main_id]['total_remain'] -= (int)$total;
                $array[$prov_main_id]['total_each_round'][$round]['total'] += $total;

                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_called'] += (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_remain'] -= (int)$total;
                $array[$prov_main_id]['pro_sub'][$prov_sub_id]['total_each_round'][$round]['total'] += $total;
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

    //--- api data of Tab 5
    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     * @param int $sequence
     * @param int $frequency
     */
    public function predictionUserDetail($regionId, $areaId, $positionId, $sequence, $frequency)
    {
        $updateLisedTotal = db::table('updated_list_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->first();
        $data = [
            //---- part 1
            'rank'              =>  (int)$sequence,
            'total_listed'      =>  $updateLisedTotal->total,
            'total_called'      =>  0,
            'total_remain'      =>  $updateLisedTotal->total,
            'total_round'       =>  0,
            'process_bars'      =>  0,
            'avg_call'          =>  0,
            'status_work'       =>  0,
            'remain_before'     =>  0,
            'status_out_list'   =>  false,
            'chart_1_round'     =>  [],
            'chart_2_round'     =>  [],
            'chart_3_region'    =>  [],

            //---- part 2
            'rank_risk'         =>  0,
            'probabilitys'      =>  0,
            'next_round'        =>  0,
        ];



        // total_round , total_called , total_remain
        $calledData = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->get();

        $round = 1;
        foreach ($calledData as $call) {
            $total = $call->total;
            $data['total_round']  += $round;
            $data['total_called'] += ($call->list_status === true ? $total : 0);
            $data['total_remain'] -= ($call->list_status === true ? $total : 0);
        }

        // avg_call
        $avgCalled = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('call_status', 1)
            ->where('list_status', 1)
            ->select(db::raw('avg(total::integer) as avg'))
            ->first();
        $data['avg_call'] = $avgCalled->avg;

        // process_bars , status_work , remain_before , rank_risk , probabilitys , next_round , status_out_list
        $rank           = $data['rank'];
        $total_listed   = $data['total_listed'];
        $total_called   = $data['total_called'];
        $total_round    = $data['total_round'];

        $data['process_bars']   =   ($total_called / $total_listed) * 100;
        $data['status_work']    =    $rank <= $total_called ? 'completed' : 'waiting';

        $data['remain_before']  =   ($rank - $total_called) > 0 ? $rank - $total_called : 0;

        $empty = $total_called - $total_listed;
        $data['status_out_list'] = $empty === 0 ? true : false;

        //  chart_1_round_monthly
        //  chart_2_round_table 
        $data_chart1 = $this->data_part1_chart1($regionId, $areaId, $positionId);
        $data['chart_1_round'] = $data_chart1;

        $data_chart2 = $this->data_part1_chart2($regionId, $areaId, $positionId);
        $data['chart_2_round'] = $data_chart2;

        //  chart_3_region_monthly
        //  chart_4_region_table
        $data_chart3 = $this->data_part1_chart3($positionId);
        $data['chart_3_region'] = $data_chart3;

        //  predictions / rank_risk / probabilitys / next_round
        $data_chart5 = $this->data_part2_chart1($total_called, $total_round, $avgCalled->avg, $sequence, $frequency);
        $data['rank_risk']      = $data_chart5['rank_risk'];
        $data['probabilitys']   = $data_chart5['probabilitys'];
        $data['next_round']     = $data_chart5['next_round'];

        // predictions / probabilitys of exhaustion / projection / total of next round
        $data_chart6 = $this->data_part2_chart2($regionId, $areaId, $positionId, $sequence, $frequency);
        $data['probability_percent']    =   $data_chart6['probability_percent'];
        $data['start_rank_2y']          =   $data_chart6['start_rank_2y'];
        $data['end_rank_2y']            =   $data_chart6['end_rank_2y'];
        $data['next_round_count']       =   $data_chart6['next_round_count'];
        $data['next_round_start']       =   $data_chart6['next_round_start'];
        $data['next_round_end']         =   $data_chart6['next_round_end'];
        $data['heatmap_matrix']         =   $data_chart6['heatmap_matrix'];
        $data['rounds_header']          =   $data_chart6['rounds_header'];

        // probability of crossing region
        $data_chart7 = $this->data_part2_chart3($regionId, $areaId, $positionId, $sequence, $frequency);
        $data['summary']    =   $data_chart7['summary'];
        $data['max_round']  =   $data_chart7['max_round'];
        $data['sim_state']  =   $data_chart7['sim_state'];
        return $data;
    }


    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     */
    public function data_part1_chart1($regionId, $areaId, $positionId)
    {
        $date_chart1 = [];
        $total_listed = db::table('updated_list_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->sum(DB::raw('total::integer'));
        $getAccountTimeline = $this->getAccountTimeline();
        foreach ($getAccountTimeline as $timeline) {
            $date = $timeline['date'];
            if (!isset($date_chart1[$date])) {
                $date_chart1[$date] = [
                    'date_thai_s'       =>  $timeline['name_s'],
                    'date_that_f'       =>  $timeline['name_l'],
                    'round'             =>  0,
                    'total'             =>  0,
                    'total_per_month'   =>  0,
                    'start'             =>  0,
                    'end'               =>  0,
                    'start_end'         =>  0,
                    'call'              =>  false,
                    'list'              =>  false,
                    'work'              =>  false,
                    'is_cross_region'   =>  false,
                    'crossed_region'    =>  null,
                    'crossed_zone'      =>  null,
                ];
            }
        }

        $calledDataChart1 = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->get();

        $last_end = 0;
        $current_date = Carbon::today();
        foreach ($calledDataChart1 as $key => $called) {
            $d = $called->called_day;
            $m = $called->called_month;
            $y = $called->called_year;
            $date = $m . '-' . $y;
            $date_numb   = Carbon::createFromDate($y, $m, $d);

            if (isset($date_chart1[$date])) {

                $work = $date_numb->greaterThan($current_date) ? 'waiting' : 'completed';
                $date_chart1[$date]['work'] = $work;

                $total  = (int)$called->total;
                $calls  = $called->call_status === 1 ? true : false;
                $lists  = $called->list_status === 1 ? true : false;
                $cross  = $called->is_cross_region === 1 ? true : false;
                $region = $called->crossed_region;
                $zone   = $called->crossed_zone;

                $date_chart1[$date]['round'] += ($key + 1);
                $date_chart1[$date]['total'] = $total;
                $date_chart1[$date]['total_per_month'] += $total;
                $date_chart1[$date]['call']  = $calls;
                $date_chart1[$date]['list']  = $lists;
                $date_chart1[$date]['is_cross_region'] = $cross;
                $date_chart1[$date]['crossed_region'] = $cross ? $region : null;
                $date_chart1[$date]['crossed_zone'] = $cross ? $zone : null;

                $start = $last_end + 1;
                $end   = $start + $total - 1;

                $date_chart1[$date]['start']      = $start;
                $date_chart1[$date]['end']        = $end;
                if ($total > 0) {
                    $date_chart1[$date]['start_end'] = $total > 1 ? $start . ' - ' . $end : $start;
                }
                $last_end = ($total > 0) ? $end : $last_end;

                $date_chart1[$date]['proportion'] = $total > 0 ? (($total / $total_listed) * 100) : '-';
            }
        }
        $chart_data = array_values($date_chart1);
        return $chart_data;
    }

    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     */
    public function data_part1_chart2($regionId, $areaId, $positionId)
    {
        //  chart_2_round_table
        $date_chart2 = [];

        $total_listed = db::table('updated_list_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->sum(DB::raw('total::integer'));

        $calledDataChart1 = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->get();

        $current_date = Carbon::today();
        foreach ($calledDataChart1 as $call) {
            $round = $call->round;
            if (!isset($date_chart2[$round])) {
                $d = $call->called_day;
                $m = $call->called_month;
                $y = $call->called_year;
                $date_numb   = Carbon::createFromDate($y, $m, $d);
                $date_chart2[$round] = [
                    'round'             =>  $round,
                    'total'             =>  (bool)$call->list_status ? (int)$call->total : $call->total,
                    'call_status'       =>  (bool)$call->call_status,
                    'list_status'       =>  (bool)$call->list_status,
                    'date'              =>  $d . ' ' . $this->monthYearThai(true, $m, $y),
                    'start'             =>  0,
                    'end'               =>  0,
                    'start_end'         =>  0,
                    'change'            =>  0,
                    'proportion'        =>  (bool)$call->call_status === true ? (($call->total / $total_listed) * 100) : 0,
                    'status'            =>  $date_numb->greaterThan($current_date) ? 'waiting' : 'completed',
                    'is_cross_region'   =>  (bool)$call->is_cross_region,
                    'crossed_region'    =>  $call->crossed_region,
                    'crossed_zone'      =>  $call->crossed_zone,
                ];
            }
        }
        $last_end = 0;
        $prev_total = null;
        foreach ($date_chart2 as $index => &$item) {
            $curr_total  = (int)$item['total'];
            $call_status = (bool)$item['call_status'];
            $start = $last_end + 1;
            $end = $start + $curr_total - 1;
            $item['start'] = $start;
            $item['end'] = $end;

            if ($curr_total > 0) {
                $item['start_end'] = $curr_total > 1 ? $start . ' - ' . $end : $start;
            }
            $last_end = ($curr_total > 0) ? $end : $last_end;

            if ($index === 0) {
                $item['change'] = 'first';
            } else {
                if ($call_status && $curr_total > 0) {
                    if ($prev_total !== null && $prev_total > 0) {
                        $item['change'] = round((($curr_total - $prev_total) / $prev_total) * 100, 2);
                    } else {
                        $item['change'] = 'first';
                    }
                    $prev_total = $curr_total;
                    $item['start_end'] = $curr_total > 1 ? $start . ' - ' . $end : $start;
                } else {
                    $item['change'] = 0;
                }
            }
        }
        return $date_chart2;
    }

    /**
     * @param int $positionId
     */
    public function data_part1_chart3($positionId)
    {
        $getAccountTimeline = $this->getAccountTimeline();
        foreach ($getAccountTimeline as &$get) {
            unset($get['data']);
            $get['round']  =   null;
            $get['total']  =   null;
            $get['calls']  =   false;
            $get['lists']  =   false;
        }
        $getAccountTimeline = collect($getAccountTimeline)->keyBy('date')->toArray();
        $updated_list_dla = db::table('updated_list_dla')
            ->where('id_position', $positionId)
            ->get();

        $chart3 = [];
        $provData = db::table('provinces_dla')
            ->get();

        foreach ($provData as $prov) {
            $main  = $prov->id_main_province;
            $subs  = $prov->id_sub_province;
            if (!isset($chart3[$main])) {
                $chart3[$main] = [
                    'main'              =>  $main,
                    'name'              =>  $prov->main_name_province,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remaining'   =>  0,
                    'processing'        =>  0,
                    'sub_province'      =>  []
                ];
            }
            if (!isset($chart3[$main]['sub_province'][$subs])) {
                $chart3[$main]['sub_province'][$subs] = [
                    'sum'               =>  $subs,
                    'name'              =>  $prov->sub_name_province,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remaining'   =>  0,
                    'total_rounds'      =>  0,
                    'processing'        =>  0,
                    'status_open'       =>  false,
                    'data_monthly'      =>  $getAccountTimeline,
                    'data_rounds'       =>  []
                ];
            }
        }

        foreach ($updated_list_dla as $update) {
            $main  = $update->id_main_province;
            $subs  = $update->id_sub_province;
            $total = $update->total;

            $chart3[$main]['total_listed'] += $total;
            $chart3[$main]['total_remaining'] += $total;

            $chart3[$main]['sub_province'][$subs]['status_open'] = true;
            $chart3[$main]['sub_province'][$subs]['total_listed'] += $total;
            $chart3[$main]['sub_province'][$subs]['total_remaining'] += $total;
        }

        $calling_dla = db::table('calling_dla')
            ->where('id_position', $positionId)
            ->get();
        foreach ($calling_dla as $call) {
            $main  = $call->id_main_province;
            $subs  = $call->id_sub_province;
            $total = $call->total;
            $called = (bool)$call->call_status;
            $listed = (bool)$call->list_status;

            //--- monthly
            if ($called === true && $listed === true) {
                $chart3[$main]['total_called'] += $total;
                $chart3[$main]['total_remaining'] -= $total;

                $chart3[$main]['sub_province'][$subs]['total_rounds'] += 1;
                $chart3[$main]['sub_province'][$subs]['total_called'] += $total;
                $chart3[$main]['sub_province'][$subs]['total_remaining'] -= $total;
            }

            $day    = $call->called_day;
            $month  = $call->called_month;
            $years  = $call->called_year;
            $date   = $month . '-' . $years;
            if (isset($chart3[$main]['sub_province'][$subs]['data_monthly'][$date])) {
                $chart3[$main]['sub_province'][$subs]['data_monthly'][$date]['round'] = $call->round;
                $chart3[$main]['sub_province'][$subs]['data_monthly'][$date]['total'] = $call->total;
                $chart3[$main]['sub_province'][$subs]['data_monthly'][$date]['calls'] = $called;
                $chart3[$main]['sub_province'][$subs]['data_monthly'][$date]['lists'] = $listed;
            }

            //--- roundly
            $round  = $call->round;
            $current_date = Carbon::today();
            $date_numb    = Carbon::createFromDate($years, $month, $day);
            if (!isset($chart3[$main]['sub_province'][$subs]['data_rounds'][$round])) {
                $chart3[$main]['sub_province'][$subs]['data_rounds'][$round] = [
                    'round'             =>  $round,
                    'total'             =>  $total,
                    'called'            =>  $called,
                    'listed'            =>  $listed,
                    'date'              =>  $day . ' ' . $this->monthYearThai(true, $month, $years),
                    'status'            =>  $date_numb->greaterThan($current_date) ? 'waiting' : 'completed',
                    'start'             =>  0,
                    'end'               =>  0,
                    'start_end'         =>  0,
                    'change'            =>  0,
                    'proportion'        =>  0,
                    'is_cross_region'   =>  $call->is_cross_region === 0 ? false : true,
                    'crossed_region'    =>  $call->crossed_region,
                    'crossed_zone'      =>  $call->crossed_zone,
                ];
            }
        }

        foreach ($chart3 as &$ch3) {
            $ch3['processing'] = $ch3['total_listed'] > 0 ? ($ch3['total_called'] / $ch3['total_listed']) * 100 : 0;
            foreach ($ch3['sub_province'] as &$sub) {
                $total_listed = $sub['total_listed'];
                $total_called = $sub['total_called'];
                $sub['processing'] = $total_listed > 0 ? ($total_called / $total_listed) * 100 : 0;
                $last_end = 0;
                $prev_total = null;
                foreach ($sub['data_rounds'] as &$rds) {
                    $total  = (int)($rds['total'] ?? 0);
                    $start  = $last_end + 1;
                    $end    = $start + $total - 1;
                    $rds['start'] = $start;
                    $rds['end']   = $end;

                    //--- proportion
                    $called = $rds['called'];
                    $listed = $rds['listed'];
                    if ($called === true && $listed === true) {
                        $rds['proportion'] = $total_listed > 0 ? ($total / $total_listed) * 100 : 0;
                    }

                    //--- start end
                    if ($total > 0) {
                        $rds['start_end'] = $total > 1 ? $start . ' - ' . $end : $start;
                    }
                    $last_end = ($total > 0) ? $end : $last_end;

                    //--- change
                    if ($total > 0) {
                        if ($prev_total !== null && $prev_total > 0) {
                            $rds['change'] = (($total - $prev_total) / $prev_total) * 100;
                        } else {
                            $rds['change'] = 0;
                        }
                        $prev_total = $total;
                    } else {
                        $rds['change'] = null;
                    }
                }
            }
        }
        return $chart3;
    }

    /**
     * @param int $total_called
     * @param int $total_round
     * @param int $average
     * @param int $sequence
     * @param int $frequency
     */
    public function data_part2_chart1($total_called, $total_round, $average, $sequence, $frequency)
    {
        $data = [];
        $rank = $sequence;
        $getAccountDaysStatus   =   $this->getAccountDaysStatus();
        $days_passed            =   $getAccountDaysStatus['days_passed'];
        $days_remaining         =   $getAccountDaysStatus['days_remaining'];
        $daily_rates            =   $total_called / $days_passed;
        $expected_rotal         =   $total_called + ($daily_rates * $days_remaining);
        $probabilitys           =   min(100, ($expected_rotal / $rank) * 100);
        $data['probabilitys']   =   $probabilitys;

        $today      =   $getAccountDaysStatus['current_date'];
        $final      =   $getAccountDaysStatus['final_date'];
        $interval   =   $today->diff($final);
        $m_remains  =   ($interval->y * 12) + $interval->m;
        $avg_per    =   $total_called / $total_round;
        $round_size =   $avg_per * $frequency;
        $end_of_next_round = $total_called + $round_size;
        if ($end_of_next_round >= $rank) {
            $next_rate = 100;
        } else {
            $next_rate = ($end_of_next_round / $rank) * 100;
        }
        $data['next_round'] = $next_rate;

        if ($rank <= $total_called) {
            $data['rank_risk'] = 0;
        } else {
            $distanc = $rank - $total_called;

            $avg_per_month = ($average > 0) ? $average : 0.1;
            $months_needed = $distanc / $avg_per_month;
            if ($months_needed <= $m_remains) {
                $data['rank_risk'] = round(($months_needed / ($m_remains > 0 ? $m_remains : 1)) * 50, 2);
            } else {
                $data['rank_risk'] = min(100, 50 + (($months_needed - $m_remains) * 2));
            }
        }

        return $data;
    }

    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     * @param int $sequence
     * @param int $frequency
     */
    public function data_part2_chart2($regionId, $areaId, $positionId, $sequence, $frequency)
    {
        $data = [];
        //---- probability of exhaustion
        $total_rank = db::table('updated_list_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->first()
            ->total;
        $last_call_rank = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('call_status', 1)
            ->select(db::raw('sum(total::integer) as total'))
            ->first()
            ->total;
        $avg_call_per_month = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('call_status', 1)
            ->select(db::raw('avg(calling_dla.total::integer) as avg_call'))
            ->first()
            ->avg_call;
        $current_round = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->max('round');
        $DaysStatus     =   $this->getAccountDaysStatus();
        $today          =   $DaysStatus['current_date'];
        $final          =   $DaysStatus['final_date'];
        $interval       =   $today->diff($final);
        $month_remains  =   ($interval->y * 12) + $interval->m;

        $remaining_rank  =  $total_rank - $last_call_rank;
        $potential_calls =  $avg_call_per_month * $month_remains;
        $probability_percent = $remaining_rank > 0 ? min(round(($potential_calls / $remaining_rank) * 100), 100) : 100;
        $data['probability_percent'] = $probability_percent;

        $end_rank_2y   = min($last_call_rank + ($avg_call_per_month * 24), $total_rank);
        $data['start_rank_2y']  = floor($end_rank_2y * 0.9);
        $data['end_rank_2y']    = min($total_rank, ceil($end_rank_2y * 1.2));

        $next_round_count = floor($avg_call_per_month * $frequency);
        $next_round_start = $last_call_rank + 1;
        $next_round_end   = min($last_call_rank + $next_round_count, $total_rank);
        $data['next_round_count']   = $next_round_count;
        $data['next_round_start']   = $next_round_start;
        $data['next_round_end']     = $next_round_end;

        $rounds_chart = [];
        $rank_tracker = $last_call_rank;
        $round_size   = $avg_call_per_month * $frequency;
        $total_rounds = ceil($month_remains / $frequency);
        for ($i = $current_round + 1; $i <= ($current_round + $total_rounds); $i++) {
            if ($rank_tracker >= $sequence) {
                break;
            }
            $start_of_round = $rank_tracker + 1;
            $end_of_round = min($rank_tracker + $round_size, $total_rank);
            $percent = ($end_of_round >= $sequence) ? 100 : round(($end_of_round / $sequence) * 100, 2);
            $rounds_chart[] = [
                'round'   => "รอบที่ $i",
                'start'   => $start_of_round,
                'end'     => $end_of_round,
                'percent' => $percent
            ];
            $rank_tracker = $end_of_round;
            if ($rank_tracker >= $total_rank) break;
        }
        $last_call_rank = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('call_status', 1)
            ->select(db::raw('sum(total::integer) as total'))
            ->first()
            ->total;
        $last_call_date = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('round', $current_round)
            ->first();
        $rank_tracker = max($last_call_rank, $sequence);
        $target_date  = $final;
        $next_date    = Carbon::create($last_call_date->called_year, $last_call_date->called_month, $last_call_date->called_day);
        $total_months = $next_date->diffInMonths($target_date);

        $months_chart = [];
        $rank_tracker = $last_call_rank;
        $monthly_increment = ($frequency > 0) ? ($avg_call_per_month / $frequency) : $avg_call_per_month;
        for ($m = 1; $m <= $total_months; $m++) {
            $rank_tracker += $monthly_increment;
            $end_of_month = min(floor($rank_tracker), $total_rank);
            if ($end_of_month <= $last_call_rank) {
                continue;
            }
            $percent = ($end_of_month >= $sequence) ? 100 : round(($end_of_month / $sequence) * 100, 2);
            $date_label = $today->copy()->addMonths($m)->format('m Y');
            $part_date  = explode(' ', $date_label);
            $months_chart[] = [
                'date_thai_l' => $this->monthYearThai(true, (int)$part_date[0], (int)$part_date[1]),
                'date_thai_s' => $this->monthYearThai(false, (int)$part_date[0], (int)$part_date[1]),
                'rank'        => $end_of_month,
                'percent'     => $percent
            ];
            if ($end_of_month >= $total_rank) break;
        }
        $rounds_header = array_column($rounds_chart, 'round');
        $heatmap_matrix = [];
        foreach ($months_chart as $m_index => $m_data) {
            $row = [];
            foreach ($rounds_chart as $r_index => $r_data) {
                $combined_percent = ($m_data['percent'] * 0.5) + ($r_data['percent'] * 0.5);
                $combined_percent = min(100, round($combined_percent, 2));
                $row[$r_data['round']] = [
                    'percent' => $combined_percent,
                ];
            }
            $heatmap_matrix[] = [
                'date'  => $m_data['date_thai_l'],
                'round' => $row
            ];
        }
        $data['heatmap_matrix'] = $heatmap_matrix;
        $data['rounds_header']  = $rounds_header;
        return $data;
    }

    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     * @param int $sequence
     * @param int $frequency
     */
    //  ตรรกะการยืม (Priority):
    //  เหนือ (1): ยืม -> อีสาน(3) -> กลาง(2) -> ใต้(4)
    //  กลาง (2): ยืม -> ใต้(4) -> อีสาน(3) -> เหนือ(1)
    //  อีสาน (3): ยืม -> เหนือ(1) -> ใต้(4) -> กลาง(2)
    //  ใต้ (4): ยืม -> กลาง(2) -> เหนือ(1) -> อีสาน(3)
    public function data_part2_chart3($regionId, $areaId, $positionId, $sequence, $frequency)
    {
        $data = [];
        $lending_priority = [
            1 => [
                3 => ['priority' => 1, 'target_region_id' => 3],
                2 => ['priority' => 2, 'target_region_id' => 2],
                4 => ['priority' => 3, 'target_region_id' => 4],
            ],
            2 => [
                4 => ['priority' => 1, 'target_region_id' => 4],
                3 => ['priority' => 2, 'target_region_id' => 3],
                1 => ['priority' => 3, 'target_region_id' => 1],
            ],
            3 => [
                1 => ['priority' => 1, 'target_region_id' => 1],
                4 => ['priority' => 2, 'target_region_id' => 4],
                2 => ['priority' => 3, 'target_region_id' => 2],
            ],
            4 => [
                2 => ['priority' => 1, 'target_region_id' => 2],
                1 => ['priority' => 2, 'target_region_id' => 1],
                3 => ['priority' => 3, 'target_region_id' => 3],
            ]
        ];
        $all_provinces = db::table('provinces_dla')
            ->get();
        foreach ($all_provinces as $prov) {
            if (!isset($data[$prov->id_main_province])) {
                $data[$prov->id_main_province] = [
                    'id_main_province'      =>  $prov->id_main_province,
                    'main_name_province'    =>  $prov->main_name_province,
                    'sub_prov'              =>  []
                ];
            }
            if (!isset($data[$prov->id_main_province]['sub_prov'][$prov->id_sub_province])) {
                $data[$prov->id_main_province]['sub_prov'][$prov->id_sub_province] = [
                    'id_sub_province'       =>  $prov->id_sub_province,
                    'sub_name_province'     =>  $prov->sub_name_province,
                    'full_name'             =>  $prov->main_name_province . ' ' . $prov->sub_name_province,
                    'owner_zone'            =>  (int)$prov->id_main_province === (int)$regionId && (int)$prov->id_sub_province === (int)$areaId,
                    'status_open'           =>  false,
                    'total_listed'          =>  0,
                    'total_called'          =>  0,
                    'total_remain'          =>  0,
                    'average_called'        =>  0,
                    'lasted_round'          =>  0,
                    'probability'           =>  0,
                    'exhaustion'            =>  false,
                    'next_lending_region'   =>  $lending_priority[$prov->id_main_province],
                    'data_round'            =>  []
                ];
            }
        }
        $update_listed = db::table('updated_list_dla')
            ->where('id_position', $positionId)
            ->get();
        foreach ($update_listed as $listed) {
            $m_prov = (int)$listed->id_main_province;
            $s_prov = (int)$listed->id_sub_province;
            $totals = (int)$listed->total;
            if (isset($data[$m_prov]['sub_prov'][$s_prov])) {
                $data[$m_prov]['sub_prov'][$s_prov]['status_open'] = true;
                $data[$m_prov]['sub_prov'][$s_prov]['total_listed'] = $totals;
                $data[$m_prov]['sub_prov'][$s_prov]['total_remain'] = $totals;
            }
        }
        $calling = db::table('calling_dla')
            ->where('id_position', $positionId)
            ->get();
        foreach ($calling as $call) {
            $m_prov = (int)$call->id_main_province;
            $s_prov = (int)$call->id_sub_province;
            $call_status = (bool)$call->call_status;
            if (isset($data[$m_prov]['sub_prov'][$s_prov])) {
                $data[$m_prov]['sub_prov'][$s_prov]['lasted_round'] += 1;
                $data[$m_prov]['sub_prov'][$s_prov]['total_called'] += ($call_status === true ? (int)$call->total : 0);
                $data[$m_prov]['sub_prov'][$s_prov]['total_remain'] -= ($call_status === true ? (int)$call->total : 0);
            }
        }

        foreach ($data as $m_id => $dt) {
            foreach ($dt['sub_prov'] as $s_id => $sub) {
                if ($sub['status_open'] === true) {
                    $total_called = $data[$m_id]['sub_prov'][$s_id]['total_called'];
                    $lasted_round = $data[$m_id]['sub_prov'][$s_id]['lasted_round'];
                    $total_remain = $data[$m_id]['sub_prov'][$s_id]['total_remain'];
                    $data[$m_id]['sub_prov'][$s_id]['average_called'] = $total_called > 0 ? floor($total_called / $lasted_round) : 0;
                    $data[$m_id]['sub_prov'][$s_id]['exhaustion'] = $total_remain === 0;
                }
            }
        }

        $sim_state = [];
        foreach ($data as $region) {
            foreach ($region['sub_prov'] as $sub) {
                $m_prov = $region['id_main_province'];
                $s_prov = $sub['id_sub_province'];
                if (!isset($sim_state[$m_prov][$s_prov])) {
                    $sim_state[$m_prov][$s_prov] = [
                        'main_id' => $region['id_main_province'],
                        'sub_id' => $sub['id_sub_province'],
                        'full_name' => $sub['full_name'],
                        'status_open' => $sub['status_open'],
                        'owner_zone' => $sub['owner_zone'],
                        'total_listed' => $sub['total_listed'],
                        'total_called' => $sub['total_called'],
                        'total_remain' => $sub['total_remain'],
                        'avg' => $sub['average_called'],
                        'round' => $sub['lasted_round'],
                        'priority' => $sub['next_lending_region'],
                        'data_round' => []
                    ];
                }
            }
        }
        $getAccountDaysStatus   =   $this->getAccountDaysStatus();
        $today  = $getAccountDaysStatus['current_date'];
        $final  = $getAccountDaysStatus['final_date'];
        $interval = $today->diff($final);
        $remaining_months  = ($interval->y * 12) + $interval->m;
        $remaining_rounds = ceil($remaining_months / $frequency);

        foreach ($sim_state as $main_id => &$sub_regions) {
            foreach ($sub_regions as $sub_id => &$sub) {
                $sub['working_remain'] = $sub['total_remain'];
                $sub['remain_after_2_years'] = max(0, $sub['total_remain'] - ($sub['avg'] * $remaining_rounds));
            }
        }

        for ($r = 1; $r <= $remaining_rounds; $r++) {
            foreach ($sim_state as $main_id => &$sub_regions) {
                foreach ($sub_regions as $sub_id => &$sub) {
                    $round = (int)$sub['round'] + $r;
                    $called_count = 0;
                    $is_borrowed = false;

                    if ($sub['status_open']) {
                        $called_count = min($sub['working_remain'], $sub['avg']);
                        $sub['working_remain'] -= $called_count;
                    } else {
                        foreach ($lending_priority[$main_id] as $p) {
                            $target_region_id = $p['target_region_id'];
                            foreach ($sim_state[$target_region_id] as $t_sub_id => &$target_sub) {
                                if ($target_sub['working_remain'] > 0) {
                                    $take = min($target_sub['working_remain'], $sub['avg']);
                                    $target_sub['working_remain'] -= $take;
                                    $called_count = $take;
                                    $is_borrowed = true;
                                    break 2;
                                }
                            }
                        }
                    }
                    $sim_state[$main_id][$sub_id]['data_round'][$r] = [
                        'round' => $round,
                        'called' => $called_count,
                        'remain' => $sub['working_remain'],
                        'is_borrowed' => $is_borrowed,
                    ];
                }
            }
        }

        foreach ($sim_state as $main_id => &$sub_regions) {
            foreach ($sub_regions as $sub_id => &$sub) {
                $already_called = $sub['total_called'];
                $borrow_count = 0;
                $cumulative_called = 0;
                foreach ($sub['data_round'] as $r => &$data) {
                    $cumulative_called += $data['called'];
                    $total_reached = $already_called + $cumulative_called;
                    if ($sub['owner_zone']) {
                        $reach_probability = ($sequence > 0) ? min(100, ($total_reached / $sequence) * 100) : 100;
                    } else {
                        $reach_probability = 0;
                    }
                    $cross_zone_effect = 0;
                    if (!$sub['owner_zone']) {
                        $used_ratio = 0;
                        if ($sub['total_remain'] > 0) {
                            $used_ratio = (($sub['total_remain'] - $data['remain']) / $sub['total_remain']) * 100;
                        }
                        $cross_zone_effect += $used_ratio;
                        if ($data['is_borrowed']) {
                            $cross_zone_effect += ($data['called'] / max(1, $sequence)) * 100;
                            $borrow_count++;
                        }
                        if (!$sub['status_open']) {
                            $cross_zone_effect += ($data['called'] / max(1, $sequence)) * 100;
                        }
                        if (isset($sub['remain_after_2_years']) && $sub['remain_after_2_years'] <= 0) {
                            $cross_zone_effect += ($data['called'] / max(1, $sequence)) * 100;
                        }
                        $cross_zone_effect = min(100, $cross_zone_effect);
                    }

                    if ($sub['owner_zone']) {
                        $final_probability = $reach_probability;
                    } else {
                        $final_probability = $cross_zone_effect;
                    }
                    $data['reach_probability'] = round($reach_probability, 2);
                    $data['cross_zone_effect'] = round($cross_zone_effect, 2);
                    $data['final_probability'] = round($final_probability, 2);
                }
            }
        }

        foreach ($sim_state as $main_id => &$sub_regions) {
            foreach ($sub_regions as $sub_id => &$sub) {
                if ($sub['owner_zone']) {
                    $future_called = 0;
                    foreach ($sub['data_round'] as $row) {
                        $future_called += $row['called'];
                    }
                    $total_reached = $sub['total_called'] + $future_called;
                    $probability = ($sequence > 0) ? ($total_reached / $sequence) * 100 : 100;
                    $probability = min(100, $probability);
                    $sub['probability'] = round($probability, 2);
                    continue;
                }
                $score = 0;
                if (!$sub['status_open']) {
                    $score += 25;
                }
                if (isset($sub['remain_after_2_years']) && $sub['remain_after_2_years'] <= 0) {
                    $shortage_rounds = abs($sub['remain_after_2_years']) / max(1, $sub['avg']);
                    $score += min(30, $shortage_rounds * 5);
                }
                $borrow_rounds = 0;
                foreach ($sub['data_round'] as $row) {
                    if (!empty($row['is_borrowed'])) {
                        $borrow_rounds++;
                    }
                }
                if ($borrow_rounds > 0) {
                    $score += min(20, ($borrow_rounds / max(1, count($sub['data_round']))));
                }
                $sub['probability'] = round($score, 2);
            }
        }
        $backup_data = json_decode(json_encode($sim_state), true);
        $all_subs = [];
        foreach ($backup_data as $round_data) {
            if (is_array($round_data)) {
                foreach ($round_data as $sub) {
                    if (is_array($sub) && isset($sub['owner_zone'])) {
                        $all_subs[] = array_diff_key($sub, ['sim_state' => '']);
                    }
                }
            }
        }
        $owners = array_filter($all_subs, fn($s) => !empty($s['owner_zone']));
        $owner = reset($owners) ?: null;
        $others = array_filter($all_subs, fn($s) => empty($s['owner_zone']));
        usort($others, fn($a, $b) => $b['probability'] <=> $a['probability']);
        $top_other = reset($others) ?: null;

        $summary = [
            'owner_probability' => $owner['probability'] ?? 0,
            'highest_other_probability' => $top_other ? [
                'status'      => true,
                'region'      => $top_other['full_name'] ?? 'ไม่ระบุ',
                'probability' => (float)($top_other['probability'] ?? 0),
            ] : null
        ];
        $round_counts = array_map(fn($sub) => count($sub['data_round']), array_merge(...$sim_state));
        $data = [
            'summary' =>  $summary,
            'max_round' => !empty($round_counts) ? max($round_counts) : 0,
            'sim_state' =>  $sim_state,
        ];
        return $data;
    }
}
