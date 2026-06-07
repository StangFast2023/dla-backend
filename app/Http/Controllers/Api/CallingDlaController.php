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
        ini_set('max_execution_time', 300);
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


    public function updateTableForTab4(Request $request, Tab4Service $Tab4Service)
    {
        $data = $Tab4Service->updateTableForTab4($request);
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
