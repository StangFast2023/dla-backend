<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Traits\DateCalculatable;
use App\Traits\TotalNewListCalculatable;

class Tab2Service
{

    use DateCalculatable;
    use TotalNewListCalculatable;

    public function getData()
    {
        $getNewTotal = $this->getNewTotal();
        //---- tab 2
        return [
            'part1' =>  $this->Tab2_Part1_TypePos(),
            'part2' =>  $this->Tab2_part2_TypeMonthly(),
            'part3' =>  $this->Tab2_part3_TypeRoundly(),
            'part4' =>  $this->Tab2_Part4_TypePosTop10(),
            'part5' =>  $this->Tab2_Part5_Empty(),
            'part6' =>  $this->Tab2_Part6_TypeCallAll(),
            'part7' =>  $this->Tab2_Part7_TypeRemainAll(),
            'part8' =>  $this->Tab2_Part8_TableAllTypes(),
        ];
    }


    public function Tab2_Part1_TypePos()
    {
        return Cache::remember('tab2_part1_stats', 600, function () {
            $TypePos = DB::table('updated_list_dla as ul')
                ->join('positions_dla as pos', 'pos.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as type', 'type.id', '=', 'pos.id_type')
                ->join('prefixes_dla as pre', 'pre.id', '=', 'pos.id_prefix')
                ->selectRaw("
                    pos.id_type as pos_type_id,
                    type.name as pos_type,
                    ul.id_position as id_pos,
                    concat(pre.name, pos.name, type.type_position) as pos_name,
                    SUM(ul.total) as total,
                    SUM(ul.new_total) as new_total
                ")
                ->groupBy('pos_type_id', 'pos_type', 'id_pos', 'pos_name')
                ->get();
            $array = ['t' => ['total_count' => 0, 'total_person_old' => 0, 'total_person_new' => 0, 'diff' => 0]];
            foreach ($TypePos as $pos) {
                $tid = $pos->pos_type_id;
                $pid = $pos->id_pos;

                if (!isset($array[$tid])) {
                    $array[$tid] = [
                        'pos_type_id' => $tid,
                        'type_name' => $pos->pos_type,
                        'total_count' => 0,
                        'total_person_old' => 0,
                        'total_person_new' => 0,
                        'diff' => 0,
                        'data' => []
                    ];
                }
                $array[$tid]['data'][$pid] = ['id_pos' => $pid, 'pos_name' => $pos->pos_name, 'total' => (int)$pos->total, 'new_total' => (int)$pos->new_total, 'diff' => (int)($pos->total - $pos->new_total)];
                $array[$tid]['total_count']++;
                $array[$tid]['total_person_old'] += $pos->total;
                $array[$tid]['total_person_new'] += $pos->new_total;
                $array[$tid]['diff'] += ($pos->total - $pos->new_total);

                $array['t']['total_count']++;
                $array['t']['total_person_old'] += $pos->total;
                $array['t']['total_person_new'] += $pos->new_total;
                $array['t']['diff'] += ($pos->total - $pos->new_total);
            }
            $totalCount = $array['t']['total_count'] ?: 1;
            $totalPerson = $array['t']['total_person_old'] ?: 1;
            $totalPersonNew = $array['t']['total_person_new'] ?: 1;
            foreach ($array as $key => &$item) {
                if ($key !== 't') {
                    $item['total_count_in_percent'] = ($item['total_count'] / $totalCount) * 100;
                    $item['total_person_in_percent'] = ($item['total_person_old'] / $totalPerson) * 100;
                    $item['total_person_new_in_percent'] = ($item['total_person_new'] / $totalPersonNew) * 100;
                }
            }
            return $array;
        });
    }

    public function Tab2_part2_TypeMonthly()
    {
        return Cache::remember('tab2_part2_monthly', 600, function () {
            $rawCalls = DB::table('calling_dla as c')
                ->join('positions_dla as p', 'p.id_position', '=', 'c.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->where('c.call_status', 1)
                ->selectRaw("
                c.called_month, c.called_year,
                p.id_type as pos_type_id,
                t.name as pos_type,
                SUM(c.total::integer) as total
            ")
                ->groupBy('called_year', 'called_month', 'pos_type_id', 'pos_type')
                ->get();
            $fullMonthly = collect($this->getAccountTimeline())->keyBy('date');
            $result = $fullMonthly->map(function ($item) {
                $item['total_per_month'] = 0;
                $item['data'] = [];
                return $item;
            });
            foreach ($rawCalls as $call) {
                $key = $call->called_month . '-' . $call->called_year;
                if ($result->has($key)) {
                    $item = $result[$key];
                    $typeId = $call->pos_type_id;

                    if (!isset($item['data'][$typeId])) {
                        $item['data'][$typeId] = ['type' => $typeId, 'name' => $call->pos_type, 'total' => 0];
                    }

                    $item['data'][$typeId]['total'] += $call->total;
                    $item['total_per_month'] += $call->total;
                    $result[$key] = $item;
                }
            }
            return $result->filter(fn($i) => $i['total_per_month'] > 0)->toArray();
        });
    }

    public function Tab2_part3_TypeRoundly()
    {
        return Cache::remember('tab2_part3_roundly', 600, function () {
            $CallingRoundly = DB::table('calling_dla as c')
                ->join('positions_dla as p', 'p.id_position', '=', 'c.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->where('c.call_status', 1)
                ->selectRaw('
                c.round as roundly,
                p.id_type as pos_type_id,
                t.name as pos_type,
                SUM(c.total::integer) as total
            ')
                ->groupBy('c.round', 'p.id_type', 't.name')
                ->orderBy('c.round', 'ASC')
                ->get();
            $array = [];
            foreach ($CallingRoundly as $call) {
                $round = $call->roundly;
                $typeId = $call->pos_type_id;
                if (!isset($array[$round])) {
                    $array[$round] = [
                        'total_per_round' => 0,
                        'data' => []
                    ];
                }
                $array[$round]['data'][$typeId] = [
                    'round' => $round,
                    'type'  => $typeId,
                    'name'  => $call->pos_type,
                    'total' => (int)$call->total
                ];
                $array[$round]['total_per_round'] += $call->total;
            }
            return $array;
        });
    }

    public function Tab2_Part4_TypePosTop10()
    {
        return Cache::remember('tab2_part4_top10', 600, function () {

            return DB::table('updated_list_dla as ul')
                ->join('positions_dla as p', 'p.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
                ->selectRaw("
                    ul.id_position as id_pos,
                    COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name,
                    p.id_type as pos_type_id,
                    t.name as pos_type, 
                    SUM(ul.total::integer) as total
                ")
                ->groupBy('id_pos', 'pos_name', 'pos_type_id', 'pos_type')
                ->orderByDesc('total')
                // ->limit(10)
                ->get()
                ->toArray();
        });
    }

    public function Tab2_Part5_Empty()
    {
        return Cache::remember('tab2_part5_empty', 600, function () {
            return DB::table('updated_list_dla as ul')
                ->join('calling_dla as c', function ($join) {
                    $join->on('c.id_main_province', '=', 'ul.id_main_province')
                        ->on('c.id_sub_province', '=', 'ul.id_sub_province')
                        ->on('c.id_position', '=', 'ul.id_position')
                        ->where('c.call_status', 1)
                        ->where('c.round', 1);
                })
                ->join('positions_dla as p', 'p.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
                ->leftJoin('provinces_dla as prov', function ($join) {
                    $join->on('prov.id_main_province', '=', 'ul.id_main_province')
                        ->on('prov.id_sub_province', '=', 'ul.id_sub_province');
                })
                ->selectRaw("
                    ul.id_position as id_pos,
                    COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name,
                    p.id_type as pos_type_id,
                    t.name as pos_type,
                    ul.new_total::integer as total_list,
                    c.total::integer as total_call,
                    ul.id_main_province as prov_main_id,
                    ul.id_sub_province as prov_sub_id,
                    c.total::integer > ul.new_total::integer as over_status,
                    prov.main_name_province || ' ' || prov.sub_name_province as prov_full_name
                ")
                ->whereRaw('ul.new_total::integer - c.total::integer = 0')
                ->orderByDesc('total_list')
                ->get()
                ->toArray();
        });
    }

    public function Tab2_Part6_TypeCallAll()
    {
        return Cache::remember('tab2_part6_call_all', 600, function () {
            return DB::table('calling_dla as c')
                ->join('positions_dla as p', 'p.id_position', '=', 'c.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
                ->where('c.call_status', 1)
                ->selectRaw("
                c.id_position as id_pos,
                p.id_type as pos_type_id,
                t.name as pos_type, 
                COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name,
                SUM(c.total::integer) as total_call
            ")
                ->groupBy('id_pos', 'pos_name', 'pos_type_id', 'pos_type')
                ->orderByDesc('total_call')
                // ->limit(10)
                ->get()
                ->map(function ($call) {
                    return [
                        'id_pos'      => $call->id_pos,
                        'pos_type_id' => $call->pos_type_id,
                        'pos_name'    => $call->pos_name,
                        'pos_type'    => $call->pos_type,
                        'total_call'  => (int)$call->total_call
                    ];
                })
                ->toArray();
        });
    }

    public function Tab2_Part7_TypeRemainAll()
    {
        return Cache::remember('tab2_part7_remain_all', 600, function () {
            $calling = DB::table('calling_dla')
                ->select(
                    'id_position',
                    DB::raw('SUM(total::integer) as total_call')
                )
                ->where('list_status', 1)
                ->where('call_status', 1)
                ->groupBy('id_position');
            return DB::table('updated_list_dla as ul')
                ->join('positions_dla as p', 'p.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
                ->leftJoinSub($calling, 'c', function ($join) {
                    $join->on('c.id_position', '=', 'ul.id_position');
                })
                ->selectRaw("
                    ul.id_position as id_pos,
                    COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name,
                    p.id_type as pos_type_id,
                    t.name as pos_type,
                    SUM(ul.new_total::integer) as total_list,
                    COALESCE(c.total_call, 0) as total_call,
                    SUM(ul.new_total::integer) - COALESCE(c.total_call, 0) as total_remain
                ")
                ->groupBy(
                    'ul.id_position',
                    'pos_name',
                    'p.id_type',
                    't.name',
                    'c.total_call'
                )

                ->orderByDesc('total_remain')
                ->get()

                ->map(function ($item) {
                    return [
                        'id_pos'       => $item->id_pos,
                        'pos_name'     => $item->pos_name,
                        'pos_type_id'  => $item->pos_type_id,
                        'pos_type'     => $item->pos_type,
                        'total_list'   => (int) $item->total_list,
                        'total_call'   => (int) $item->total_call,
                        'total_remain' => (int) $item->total_remain,
                    ];
                })
                ->toArray();
        });
    }

    public function Tab2_Part8_TableAllTypes()
    {
        return Cache::remember('tab2_part8_all_types', 600, function () {
            $listed = DB::table('updated_list_dla as ul')
                ->join('positions_dla as p', 'p.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->join('provinces_dla as prov', function ($join) {
                    $join->on('prov.id_main_province', '=', 'ul.id_main_province')
                        ->on('prov.id_sub_province', '=', 'ul.id_sub_province');
                })
                ->selectRaw("
                    ul.id_main_province as prov_main_id,
                    prov.main_name_province,
                    p.id_type as pos_type_id,
                    t.name as pos_type,
                    SUM(ul.total::integer) as total,
                    SUM(ul.new_total::integer) as new_total
                ")
                ->groupBy('prov_main_id', 'prov.main_name_province', 'pos_type_id', 'pos_type')
                ->get();
            $called = DB::table('calling_dla as c')
                ->join('positions_dla as p', 'p.id_position', '=', 'c.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->where('call_status', 1)
                ->selectRaw("
                    c.id_main_province, c.round,
                    p.id_type as pos_type_id,
                    SUM(c.total::integer) as total
                ")
                ->groupBy('id_main_province', 'round', 'pos_type_id')
                ->get();
            $array = [];
            foreach ($listed as $item) {
                $array[$item->prov_main_id][$item->pos_type_id] = [
                    'prov_main_id'   => $item->prov_main_id,
                    'prov_main_name' => $item->main_name_province,
                    'pos_type_id'    => $item->pos_type_id,
                    'pos_type'       => $item->pos_type,
                    'total_list'     => (int)$item->total,
                    'total_list_n'   => (int)$item->new_total,
                    'total_diff'     => (int)($item->total - $item->new_total),
                    'total_call'     => 0,
                    'total_remain'   => (int)$item->new_total,
                    'status_empty'   => false,
                    'status_called'  => false,
                    'round_data'     => []
                ];
            }
            foreach ($called as $call) {
                $ref = &$array[$call->id_main_province][$call->pos_type_id];
                if ($ref) {
                    $ref['total_call'] += $call->total;
                    $ref['total_remain'] -= $call->total;
                    $ref['status_called'] = true;
                    $ref['round_data'][$call->round] = [
                        'round'  => $call->round,
                        'total'  => (int)$call->total,
                        'called' => true
                    ];
                }
            }
            foreach ($array as &$provinces) {
                foreach ($provinces as &$type) {
                    $type['status_empty'] = ($type['total_remain'] <= 0);
                }
            }
            return $array;
        });
    }

    /**
     * @param int $id
     */
    public function getPositionDetail($id)
    {
        $getNewTotal = $this->getNewTotal();
        $result = Str::substr($id, 0, 1);
        $position = $getNewTotal['data'][1]['data'][1]['data'][$result]['data'][$id]['name_position'] ?? 'ไม่พบข้อมูล';
        $array = [
            'id' => $id,
            'name' => $position,
            'data' => [],
            'total' => ['listed' => 0, 'listed_new' => 0, 'called' => 0, 'remain' => 0, 'diff' => 0]
        ];
        foreach ($getNewTotal['data'] as $k1 => $region) {
            foreach ($region['data'] as $k2 => $zone) {
                $data = $zone['data'][$result]['data'][$id];
                if (isset($data)) {
                    $array['data'][$k1][$k2] = [
                        'pro_main_id'    => $k1,
                        'pro_sub_id'     => $k2,
                        'pro_main_name'  => $region['name_main'],
                        'pro_sub_name'   => $zone['name_sub'],
                        'pro_full_name'  => $zone['full_name'],
                        'total_listed'   => $data['listed_old'],
                        'total_listed_n' => $data['listed_new'],
                        'total_diff'     => $data['listed_diff'],
                        'total_called'   => $data['called_new'],
                        'total_remain'   => $data['remaining'],
                        'total_process'  => ($data['listed_new'] > 0) ? ($data['called_new'] / $data['listed_new']) * 100 : 0,
                        'total_round'    => $data['total_round'],
                        'status_listed'  => $data['listed_new'] !== 0,
                        'status_calling' => $data['called_new'] !== 0,
                    ];
                }
                $array['total']['listed'] += $data['listed_old'];
                $array['total']['listed_new'] += $data['listed_new'];
                $array['total']['called'] += $data['called_new'];
                $array['total']['diff'] += $data['listed_diff'];
            }
        }
        $array['total']['remain'] = $array['total']['listed_new'] - $array['total']['called'];
        return $array;
    }
}
