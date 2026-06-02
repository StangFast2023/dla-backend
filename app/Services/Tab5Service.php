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
            ->selectRaw('
                positions_dla.id_position                   as  pos_id          ,
                positions_dla.name                          as  pos_name        ,

                prefixes_dla.name                           as  pref_name       ,
                type_positions_dla.type_position            as  suff_name       ,

                positions_dla.id_type                       as  pos_type_id     ,
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
            ->selectRaw('
                updated_list_dla.id_main_province               as  prov_main_id    ,
                updated_list_dla.id_sub_province                as  prov_sub_id     ,
                positions_dla.id_type                           as  pos_type_id     ,
                positions_dla.id_position                       as  pos_id          ,
                sum( total )                                    as  total
            ')
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
            ->selectRaw('calling_dla.* , positions_dla.id_type   as  pos_type_id')
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

            if ($call_status === 1) {
                $call_date      =   Carbon::createFromDate($pos->called_year, $pos->called_month, $pos->called_day);
                $status         =   $call_date->greaterThan($current_date) ? 'waiting' : 'completed';
            } else {
                $call_date      =   null;
                $status         =   $list_status === 1 ? 'not-used' : 'exhaustion';
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

            if ($call_status === 1) {
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
     */
    public function predictionUserDetail($regionId, $areaId, $positionId, $sequence)
    {

        // total_listed , total_remain
        $updateLisedTotal = db::table('updated_list_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->first();

        $data = [
            'rank'              =>  (int)$sequence,
            'total_listed'      =>  $updateLisedTotal->total,
            'total_called'      =>  0,
            'total_remain'      =>  $updateLisedTotal->total,
            'total_round'       =>  0,
            'process_bars'      =>  0,
            'avg_call'          =>  0,
            'status_work'       =>  0,
            'remain_before'     =>  0,
            'rank_risk'         =>  0,
            'probabilitys'      =>  0,
            'next_round'        =>  0,
            'status_out_list'   =>  false,
            'chart_1_round'     =>  [],
            'chart_2_region'    =>  [],
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
            $data['total_called'] += ($call->list_status === 1 ? $total : 0);
            $data['total_remain'] -= ($call->list_status === 1 ? $total : 0);
        }

        //-- avg_call
        $avgCalled = db::table('calling_dla')
            ->where('id_main_province', $regionId)
            ->where('id_sub_province', $areaId)
            ->where('id_position', $positionId)
            ->where('call_status', 1)
            ->where('list_status', 1)
            ->selectRaw('avg(total) as avg')
            ->first();
        $data['avg_call'] = $avgCalled->avg;

        //-- process_bars , status_work , remain_before , rank_risk , probabilitys , next_round , status_out_list
        $rank           = $data['rank'];
        $total_listed   = $data['total_listed'];
        $total_called   = $data['total_called'];
        $total_remain   = $data['total_remain'];
        $total_round    = $data['total_round'];

        $data['process_bars']   =   ($total_called / $total_listed) * 100;
        $data['status_work']    =    $rank <= $total_called ? 'completed' : 'waiting';

        $status_work    = $data['status_work'];

        $data['remain_before']  =   ($rank - $total_called) > 0 ? $rank - $total_called : 0;
        if ($rank <= $total_called) {
            $data['rank_risk'] = 0;
        } else {
            $safe_total_remain = max(0, $total_remain);
            if ($safe_total_remain <= 0) {
                $data['rank_risk'] = 100;
            } else {
                $data['rank_risk'] = min(100, (($rank - $total_called) / $safe_total_remain) * 100);
            }
        }

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
        $r_remains  =   ceil($m_remains / 1);
        $avg_per    =   $total_called / $total_round;
        $distanc    =   $rank - $total_called;
        $total_capacity = $avg_per * $r_remains;
        if ($distanc <= 0) {
            $next_rate = 100;
        } elseif ($distanc <= $avg_per) {
            $next_rate = 100;
        } else {
            $next_rate = max(0, 100 - (($distanc / $total_capacity) * 100));
        }
        $data['next_round'] = $next_rate;

        $empty = $total_called - $total_listed;
        $data['status_out_list'] = $empty === 0 ? true : false;

        //  chart_1_round_monthly
        //  chart_2_round_table 
        $date_chart1_2 = [];
        $getAccountTimeline = $this->getAccountTimeline();
        foreach ($getAccountTimeline as $timeline) {
            $date = $timeline['date'];
            if (!isset($date_chart1_2[$date])) {
                $date_chart1_2[$date] = [
                    'date_thai_s'       =>  null,
                    'date_that_f'       =>  null,
                    'round'             =>  0,
                    'total'             =>  0,
                    'start'             =>  0,
                    'end'               =>  0,
                    'start_end'         =>  0,
                    'call'              =>  false,
                    'list'              =>  false,
                    'work'              =>  false,
                    'change'            =>  0,
                    'proportion'        =>  0,
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
            if (isset($date_chart1_2[$date])) {
                $date_numb   = Carbon::createFromDate($y, $m, $d);
                $date_thai_s = $this->monthYearThai(false, $m, $y);
                $date_that_f = $d . ' ' . $this->monthYearThai(true, $m, $y);
                $date_chart1_2[$date]['date_thai_s'] = $date_thai_s;
                $date_chart1_2[$date]['date_that_f'] = $date_that_f;

                $work = $date_numb->greaterThan($current_date) ? 'waiting' : 'completed';
                $date_chart1_2[$date]['work'] = $work;

                $total  = (int)$called->total;
                $calls  = $called->call_status === 1 ? true : false;
                $lists  = $called->list_status === 1 ? true : false;
                $cross  = $called->is_cross_region === 1 ? true : false;
                $region = $called->crossed_region;
                $zone   = $called->crossed_zone;

                $date_chart1_2[$date]['round'] += ($key + 1);
                $date_chart1_2[$date]['total'] = $total;
                $date_chart1_2[$date]['call']  = $calls;
                $date_chart1_2[$date]['list']  = $lists;
                $date_chart1_2[$date]['is_cross_region'] = $cross;
                $date_chart1_2[$date]['crossed_region'] = $cross ? $region : null;
                $date_chart1_2[$date]['crossed_zone'] = $cross ? $zone : null;

                $start = $last_end + 1;
                $end   = $start + $total - 1;
                $date_chart1_2[$date]['start']      = $start;
                $date_chart1_2[$date]['end']        = $end;
                $date_chart1_2[$date]['start_end']  = $total > 1 ? $start . ' - ' . $end : $start;

                $date_chart1_2[$date]['proportion'] = $total > 0 ? (($total / $total_listed) * 100) : '-';
            }
        }

        $chart_data = array_values($date_chart1_2);
        $previous_total = null;
        foreach ($chart_data as $index => &$item) {
            if ($index > 0 && $previous_total !== null && $previous_total > 0) {
                $current_total = $item['total'];
                $growth = (($current_total - $previous_total) / $previous_total) * 100;
                $item['change'] = round($growth, 2);
            } else {
                $item['change'] = 'first';
            }
            $previous_total = $item['total'];
        }
        $data['chart_1_round'] = $chart_data;
        // dd($data);
        //  chart_3_region_monthly
        //  chart_4_region_table
        return $data;
    }
}
