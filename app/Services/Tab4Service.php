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

class Tab4Service
{

    use DateCalculatable;

    public function getData()
    {
        //---- tab 4
        return [
            'part1' =>  $this->Tab4_Part1_Filter(),
        ];
    }

    public function Tab4_Part1_Filter()
    {
        $data = [
            'region'    =>  [],
            'types'     =>  [],
            'positions' =>  []
        ];
        $prov = ProvincesDla::all();
        foreach ($prov as $pr) {
            $main   = $pr->id_main_province;
            $sub    = $pr->id_sub_province;
            $name1  = $pr->main_name_province;
            $name2  = $pr->sub_name_province;

            if (!isset($data['region'][$main])) {
                $data['region'][$main] = [
                    'main'      =>  $main,
                    'main_name' =>  $name1,
                    'sub'       =>  []
                ];
            }
            if (!isset($data['region'][$main]['sub'][$sub])) {
                $data['region'][$main]['sub'][$sub] = [
                    'sub_name'  =>  $name1 . ' ' . $name2
                ];
            }
        }

        $types = TypePositionDla::all();
        foreach ($types as $tp) {
            $id             = $tp->id;
            $name           = $tp->name;
            $type_position  = $tp->type_position;
            if (!isset($data['types'][$id])) {
                $data['types'][$id] = [
                    'id'            =>  $id,
                    'type_name'     =>  $name,
                    'suffixe_name'  =>  $type_position
                ];
            }
        }

        $post = PositionDla::all();
        foreach ($post as $ps) {
            $id             = $ps->id;
            $id_position    = $ps->id_position;
            $name           = $ps->name;
            $id_type        = substr($id_position, 0, 1);
            $id_prefix      = $ps->id_prefix;
            if (!isset($data['positions'][$id_type])) {
                $data['positions'][$id_type] = [
                    'type_name'     =>  $data['types'][$id_type]['type_name'],
                    'data_position' =>  []
                ];
            }
            if (!isset($data['positions'][$id_type]['data_position'][$id_position])) {
                $pref = PrefixsDla::where('id', $id_prefix)->first();
                $data['positions'][$id_type]['data_position'][$id_position] = [
                    'pos_id'        =>  $id,
                    'pos_main_id'   =>  $id_position,
                    'full_pos_name' =>  $pref->name . $name,
                    'type_name'     =>  $data['types'][$id_type]['type_name']
                ];
            }
        }
        return $data;
    }
}
