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
            $data = $this->dataForThreeTables();
            usort($data, function ($a, $b) {
                return $b['total_list'] <=> $a['total_list'];
            });
            $data = array_filter($data, function ($item) {
                return $item['total_list'] != 0;
            });
            return $data;
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
            $data = $this->dataForThreeTables();
            usort($data, function ($a, $b) {
                return $b['total_call'] <=> $a['total_call'];
            });
            $data = array_filter($data, function ($item) {
                return $item['total_call'] != 0;
            });
            return $data;
        });
    }

    public function Tab2_Part7_TypeRemainAll()
    {
        return Cache::remember('tab2_part7_remain_all', 600, function () {
            $data = $this->dataForThreeTables();
            usort($data, function ($a, $b) {
                return $b['total_remain'] <=> $a['total_remain'];
            });
            $data = array_filter($data, function ($item) {
                return $item['total_remain'] != 0;
            });
            return $data;
        });
    }

    public function Tab2_Part8_TableAllTypes()
    {
        return Cache::remember('tab2_part8_all_types', 600, function () {

            $getNewTotal = $this->getNewTotal();
            $array = [];
            foreach ($getNewTotal['data'] as $key_r => $regoin) {
                foreach ($regoin['data'] as $zone) {
                    foreach ($zone['data'] as $key_t => $type) {
                        foreach ($type['data']  as $post) {
                            if (!isset($array[$key_r][$key_t])) {
                                $array[$key_r][$key_t] = [
                                    'prov_main_id' => $key_r,
                                    'prov_main_name' => $regoin['name_main'],
                                    'pos_type_id' => $key_t,
                                    'pos_type' => $type['name_type_position'],
                                    'total_list' => 0,
                                    'total_list_n' => 0,
                                    'total_diff' => 0,
                                    'total_call' => 0,
                                    'total_remain' => 0,
                                    'status_empty' => false,
                                    'status_called' => false,
                                    'account_status' => false,
                                    'list_new_status' => false,
                                    'round_data' => []
                                ];
                            }
                            $array[$key_r][$key_t]['total_list'] += $post['listed_old'];
                            $array[$key_r][$key_t]['total_list_n'] += $post['listed_new'];
                            $array[$key_r][$key_t]['total_diff'] += $post['listed_diff'];
                            $array[$key_r][$key_t]['total_call'] += $post['called_total'];
                            $array[$key_r][$key_t]['total_remain'] += $post['listed_new'] - $post['called_total'];
                            foreach ($post['detail_round'] as $key_round => $round) {
                                if (!isset($array[$key_r][$key_t]['round_data'][$key_round])) {
                                    $array[$key_r][$key_t]['round_data'][$key_round] = [
                                        'round' => $key_round,
                                        'total' => 0,
                                        'called' => false,
                                    ];
                                }
                                $array[$key_r][$key_t]['round_data'][$key_round]['total'] += ($round['call_status'] ? $round['total'] : 0);
                                $array[$key_r][$key_t]['round_data'][$key_round]['list_new_status'] = $round['list_new_status'];
                            }
                        }
                    }
                }
            }
            foreach ($array as $regoin => $data) {
                foreach ($data as $zones => $post) {
                    $array[$regoin][$zones]['status_empty'] = $post['total_list_n'] === 0;
                    $array[$regoin][$zones]['status_called'] = $post['total_call'] !== 0;
                    $array[$regoin][$zones]['account_status'] = $post['total_remain'] === 0;
                    foreach ($post['round_data'] as $round => $r_data) {
                        $array[$regoin][$zones]['round_data'][$round]['called'] = $r_data['total'] !== 0;
                    }
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
                    $account_status = $data['account_status'];
                    $array['data'][$k1][$k2] = [
                        'pro_main_id'    => $k1,
                        'pro_sub_id'     => $k2,
                        'pro_main_name'  => $region['name_main'],
                        'pro_sub_name'   => $zone['name_sub'],
                        'pro_full_name'  => $zone['full_name'],
                        'total_listed'   => $data['listed_old'],
                        'total_listed_n' => $data['listed_new'],
                        'total_diff'     => $data['listed_diff'],
                        'total_called'   => $account_status ? $data['listed_new'] : $data['called_new'],
                        'total_remain'   => $account_status ? 0 : $data['remaining'],
                        'total_process'  => 0,
                        'total_round'    => $data['total_round'],
                        'status_listed'  => $data['listed_new'] !== 0,
                        'status_calling' => $data['called_new'] !== 0,
                        'account_status' => $account_status,
                    ];
                }
                $array['total']['listed'] += $data['listed_old'];
                $array['total']['listed_new'] += $data['listed_new'];
                $array['total']['called'] +=  $array['data'][$k1][$k2]['total_called'];
                $array['total']['diff'] += $data['listed_diff'];
                $array['total']['remain'] += $data['listed_new'];
                $array['total']['remain'] -= $account_status ? $data['listed_new'] : $data['called_new'];
                $array['data'][$k1][$k2]['total_process'] = $array['data'][$k1][$k2]['total_listed_n'] > 0 ? (($array['data'][$k1][$k2]['total_called'] / $array['data'][$k1][$k2]['total_listed_n']) * 100) : 0;
            }
        }
        return $array;
    }

    private function dataForThreeTables()
    {
        $getNewTotal = $this->getNewTotal();
        $array = [];
        foreach ($getNewTotal['data'] as $region) {
            foreach ($region['data'] as $zone) {
                foreach ($zone['data'] as $id_type => $type) {
                    foreach ($type['data'] as $id_post => $post) {
                        $total_list = $post['listed_new'];
                        $total_call = $post['called_total'];
                        if (!isset($array[$id_post])) {
                            $array[$id_post] = [
                                'id_pos' => $id_post,
                                'pos_name' => $post['name_position'],
                                'pos_type_id' => $id_type,
                                'pos_type' => $type['name_type_position'],
                                'total_list' => 0,
                                'total_call' => 0,
                                'total_remain' => 0,
                            ];
                        }
                        $array[$id_post]['total_list'] += $total_list;
                        $array[$id_post]['total_call'] += $total_call;
                    }
                }
            }
        }
        foreach ($array as &$data) {
            $data['total_remain'] = $data['total_list'] - $data['total_call'];
        }
        return $array;
    }
}
