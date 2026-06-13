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

class Tab2Service
{

    use DateCalculatable;

    public function getData()
    {
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
        return Cache::remember('tab2_part1_stats', 300, function () {
            $TypePos = DB::table('updated_list_dla as ul')
                ->join('positions_dla as pos', 'pos.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as type', 'type.id', '=', 'pos.id_type')
                ->join('prefixes_dla as pre', 'pre.id', '=', 'pos.id_prefix')
                ->selectRaw("
                pos.id_type as pos_type_id,
                type.name as pos_type,
                ul.id_position as id_pos,
                concat(pre.name, pos.name, type.type_position) as pos_name,
                SUM(ul.total::integer) as total
            ")
                ->groupBy('pos_type_id', 'pos_type', 'id_pos', 'pos_name')
                ->get();
            $array = ['t' => ['total_count' => 0, 'total_person' => 0]];
            foreach ($TypePos as $pos) {
                $tid = $pos->pos_type_id;
                $pid = $pos->id_pos;

                if (!isset($array[$tid])) {
                    $array[$tid] = [
                        'pos_type_id' => $tid,
                        'type_name' => $pos->pos_type,
                        'total_count' => 0,
                        'total_person' => 0,
                        'data' => []
                    ];
                }
                $array[$tid]['data'][$pid] = ['id_pos' => $pid, 'pos_name' => $pos->pos_name, 'data' => (int)$pos->total];
                $array[$tid]['total_count']++;
                $array[$tid]['total_person'] += $pos->total;

                $array['t']['total_count']++;
                $array['t']['total_person'] += $pos->total;
            }
            $totalCount = $array['t']['total_count'] ?: 1;
            $totalPerson = $array['t']['total_person'] ?: 1;
            foreach ($array as $key => &$item) {
                if ($key !== 't') {
                    $item['total_count_in_percent'] = ($item['total_count'] / $totalCount) * 100;
                    $item['total_person_in_percent'] = ($item['total_person'] / $totalPerson) * 100;
                }
            }
            return $array;
        });
    }

    public function Tab2_part2_TypeMonthly()
    {
        return Cache::remember('tab2_part2_monthly', 300, function () {
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
        return Cache::remember('tab2_part3_roundly', 300, function () {
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
        return Cache::remember('tab2_part5_empty', 300, function () {
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
                    ul.total::integer as total_list,
                    c.total::integer as total_call,
                    ul.id_main_province as prov_main_id,
                    ul.id_sub_province as prov_sub_id,
                    prov.main_name_province || ' ' || prov.sub_name_province as prov_full_name
                ")
                ->whereRaw('ul.total::integer - c.total::integer = 0')
                ->orderByDesc('total_list')
                // ->limit(10)
                ->get()
                ->toArray();
        });
    }

    public function Tab2_Part6_TypeCallAll()
    {
        return Cache::remember('tab2_part6_call_all', 300, function () {
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
        return Cache::remember('tab2_part7_remain_all', 300, function () {
            return DB::table('updated_list_dla as ul')
                ->join('positions_dla as p', 'p.id_position', '=', 'ul.id_position')
                ->join('type_positions_dla as t', 't.id', '=', 'p.id_type')
                ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
                ->leftJoin('calling_dla as c', function ($join) {
                    $join->on('c.id_position', '=', 'ul.id_position')
                        ->where('c.call_status', 1);
                })
                ->selectRaw("
                ul.id_position as id_pos,
                COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name,
                p.id_type as pos_type_id,
                t.name as pos_type,
                SUM(ul.total::integer) as total_list,
                COALESCE(SUM(c.total::integer), 0) as total_call,
                SUM(ul.total::integer) - COALESCE(SUM(c.total::integer), 0) as total_remain
            ")
                ->groupBy('ul.id_position', 'pos_name', 'p.id_type', 't.name')
                ->orderByDesc('total_remain')
                // ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'id_pos'       => $item->id_pos,
                        'pos_name'     => $item->pos_name,
                        'pos_type_id'  => $item->pos_type_id,
                        'pos_type'     => $item->pos_type,
                        'total_list'   => (int)$item->total_list,
                        'total_call'   => (int)$item->total_call,
                        'total_remain' => (int)$item->total_remain,
                    ];
                })
                ->toArray();
        });
    }

    public function Tab2_Part8_TableAllTypes()
    {
        return Cache::remember('tab2_part8_all_types', 300, function () {
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
                    SUM(ul.total::integer) as total
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
                    'total_call'     => 0,
                    'total_remain'   => (int)$item->total,
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
        $position = DB::table('positions_dla as p')
            ->leftJoin('type_positions_dla as t', 't.id', '=', 'p.id_type')
            ->leftJoin('prefixes_dla as pre', 'pre.id', '=', 'p.id_prefix')
            ->where('p.id_position', $id)
            ->selectRaw("COALESCE(pre.name, '') || p.name || COALESCE(t.type_position, '') as pos_name")
            ->first();
        $provinces = DB::table('provinces_dla')->get();
        $updates = DB::table('updated_list_dla')
            ->where('id_position', $id)
            ->get()
            ->keyBy(fn($item) => $item->id_main_province . '-' . $item->id_sub_province);
        $calls = DB::table('calling_dla')
            ->where('call_status', 1)
            ->where('id_position', $id)
            ->selectRaw("id_main_province, id_sub_province, MAX(round) as round, SUM(total::integer) as total")
            ->groupBy('id_main_province', 'id_sub_province')
            ->get()
            ->keyBy(fn($item) => $item->id_main_province . '-' . $item->id_sub_province);
        $array = [
            'id' => $id,
            'name' => $position->pos_name ?? 'ไม่พบข้อมูล',
            'data' => [],
            'total' => ['listed' => 0, 'called' => 0, 'remain' => 0]
        ];
        foreach ($provinces as $prov) {
            $key = $prov->id_main_province . '-' . $prov->id_sub_province;
            $listed = $updates->get($key);
            $calling = $calls->get($key);
            $listedTotal = (int)($listed->total ?? 0);
            $calledTotal = (int)($calling->total ?? 0);
            $array['data'][$prov->id_main_province][$prov->id_sub_province] = [
                'pro_main_id'    => $prov->id_main_province,
                'pro_sub_id'     => $prov->id_sub_province,
                'pro_main_name'  => $prov->main_name_province,
                'pro_sub_name'   => $prov->sub_name_province,
                'pro_full_name'  => "{$prov->main_name_province} {$prov->sub_name_province}",
                'total_listed'   => $listedTotal,
                'total_called'   => $calledTotal,
                'total_remain'   => $listedTotal - $calledTotal,
                'total_process'  => ($listedTotal > 0) ? ($calledTotal / $listedTotal) * 100 : 0,
                'total_round'    => (int)($calling->round ?? 0),
                'status_listed'  => !is_null($listed),
                'status_calling' => !is_null($calling),
            ];
            $array['total']['listed'] += $listedTotal;
            $array['total']['called'] += $calledTotal;
        }
        $array['total']['remain'] = $array['total']['listed'] - $array['total']['called'];
        return $array;
    }
}
