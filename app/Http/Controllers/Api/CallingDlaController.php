<?php

namespace App\Http\Controllers\Api;

use App\Services\Tab1Service;
use App\Services\Tab2Service;
use App\Services\Tab3Service;
use App\Services\Tab4Service;
use App\Services\Tab5Service;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\CallingDla;
use App\Models\UpdateListDla;
use App\Models\ProvincesDla;
use App\Models\PrefixsDla;
use App\Models\PositionDla;
use App\Models\TypePositionDla;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallingDlaController extends Controller
{
    public function getStats()
    {

        return response()->json([
            'status' => 'success',
            'tab1'  =>  app(Tab1Service::class)->getData(),
            'tab2'  =>  app(Tab2Service::class)->getData(),
            'tab3'  =>  app(Tab3Service::class)->getData(),
            'tab4'  =>  app(Tab4Service::class)->getData(),
            'tab5'  =>  app(Tab5Service::class)->getData(),
        ]);
    }


    //--- api data of Tab 2
    /**
     * @param int $id
     */
    public function getPositionDetailByZone($id, Tab2Service $tab2Service)
    {
        $data = $tab2Service->getPositionDetail($id);
        return response()->json($data);
    }


    public function updateTableForTab4(Request $request, Tab4Service $tab4Service)
    {
        $regions    =   $request->input('cleanRegions');
        $positions  =   $request->input('cleanPositions');

        $array_position = [];
        foreach ($positions as $pos) {
            $part = explode('-', $pos);
            if (count($part) === 3) {
                $array_position[] = $part[2];
            }
        }
        $all_pos_array = [];
        $all_Positions = db::table('positions_dla')
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->whereIn('positions_dla.id_position', $array_position)
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

        $array_province = [];
        foreach ($regions as $reg) {
            $parts = explode('-', $reg);
            if (count($parts) === 3) {
                $main = $parts[1];
                $subs = $parts[2];
                $array_province[$main]['sub'][] = $subs;
            }
        }
        $array = [];
        foreach ($array_province as $key => $arr_pro) {
            $main_provinces = db::table('provinces_dla')
                ->where('provinces_dla.id_main_province', $key)
                ->selectRaw('
                    provinces_dla.id                    as pro_id           ,
                    provinces_dla.id_main_province      as pro_main_id      ,
                    provinces_dla.main_name_province    as pro_main_name
                ')
                ->orderBy('pro_id', 'ASC')
                ->first();

            if (!isset($array[$main_provinces->pro_main_id])) {
                $array[$main_provinces->pro_main_id] = [
                    'pro_main_id'       =>  $main_provinces->pro_main_id,
                    'pro_main_name'     =>  $main_provinces->pro_main_name,
                    'total_position'    =>  0,
                    'total_listed'      =>  0,
                    'total_called'      =>  0,
                    'total_remain'      =>  0,
                    'total_each_round'  =>  [],
                    'pro_sub'           =>  []
                ];
            }
            $sub_provinces = db::table('provinces_dla')
                ->where('provinces_dla.id_main_province', $key)
                ->whereIn('provinces_dla.id_sub_province', $arr_pro['sub'])
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

            foreach ($sub_provinces as $prov) {
                if (!isset($array[$prov->pro_main_id]['pro_sub'][$prov->pro_sub_id])) {
                    $array[$prov->pro_main_id]['pro_sub'][$prov->pro_sub_id] = [
                        'pro_sub_id'            =>  $prov->pro_sub_id,
                        'pro_sub_name'          =>  $prov->pro_full_name,
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
            ->leftjoin('positions_dla', 'positions_dla.id_position', 'updated_list_dla.id_position')
            ->leftjoin('type_positions_dla', 'type_positions_dla.id', DB::raw('SUBSTRING(positions_dla.id_position, 1, 1)'))
            ->leftjoin('prefixes_dla', 'prefixes_dla.id', 'positions_dla.id_prefix')
            ->selectRaw('
                updated_list_dla.id_main_province               as  prov_main_id    ,
                updated_list_dla.id_sub_province                as  prov_sub_id     ,
                SUBSTRING(updated_list_dla.id_position, 1, 1)   as  pos_type_id     ,
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
        $called_position = db::table('calling_dla')
            ->selectRaw('calling_dla.* , SUBSTRING(calling_dla.id_position, 1, 1)   as  pos_type_id')
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

            if (isset($array[$prov_main_id]['pro_sub'][$prov_sub_id]['data_type_position'][$pos_type_id]['data_position'][$pos_id])) {

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
        }


        $showEmpty = $request->input('showEmpty') == '1' || $request->input('showEmpty') === true;
        if ($showEmpty === false) {
            foreach ($array as &$prov) {
                foreach ($prov['pro_sub'] as &$prov_sub) {
                    foreach ($prov_sub['data_type_position'] as &$data_type) {
                        $data_type['data_position'] = array_filter($data_type['data_position'], function ($pos) {
                            return $pos['status_open'] === true;
                        });
                    }
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
        return response()->json($data);
    }
}
