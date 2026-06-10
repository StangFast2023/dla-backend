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

    public function getDataTab1()
    {
        return response()->json([
            'status' => 'success',
            'tab1'  =>  app(Tab1Service::class)->getData(),
        ]);
    }
    public function getDataTab2()
    {
        return response()->json([
            'status' => 'success',
            'tab2'  =>  app(Tab2Service::class)->getData(),
        ]);
    }
    public function getDataTab3()
    {
        return response()->json([
            'status' => 'success',
            'tab3'  =>  app(Tab3Service::class)->getData(),
        ]);
    }
    public function getDataTab4()
    {
        return response()->json([
            'status' => 'success',
            'tab4'  =>  app(Tab4Service::class)->getData(),
        ]);
    }
    public function getDataTab5()
    {
        return response()->json([
            'status' => 'success',
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


    public function updateTableForTab4(Request $request, Tab4Service $Tab4Service)
    {

        $positions = $request->input('cleanPositions');
        $array_position = [];
        foreach ($positions as $pos) {
            $part = explode('-', $pos);
            if (count($part) === 3) {
                $array_position[] = (int)$part[2];
            }
        }

        $regions = $request->input('cleanRegions');
        $array_province = [];
        foreach ($regions as $reg) {
            $parts = explode('-', $reg);
            if (count($parts) === 3) {
                $main = (int)$parts[1];
                $subs = (int)$parts[2];
                $array_province[$main]['sub'][] = $subs;
            }
        }

        $showEmpty = $request->input('showEmpty') == '1' || $request->input('showEmpty') === true;
        $data = $Tab4Service->updateTableForTab4($array_position, $array_province, $showEmpty);
        return response()->json($data);
    }

    //--- api data of Tab 5
    /**
     * @param int $regionId
     * @param int $areaId
     * @param int $positionId
     * @param int $sequence
     * @param int $frequency
     */
    public function predictionUserDetail($regionId, $areaId, $positionId, $sequence, $frequency, Tab5Service $tab5Service)
    {
        $data = $tab5Service->predictionUserDetail($regionId, $areaId, $positionId, $sequence, $frequency);
        return response()->json($data);
    }
}
