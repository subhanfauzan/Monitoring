<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dapot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DapotApiController extends Controller
{
    public function searchBySiteId(Request $request): JsonResponse {
        $siteID = $request->input('site_id');
        $dapots = Dapot::where('site_id', 'LIKE', "%{$siteID}%")->get();

        $response = [
            'success' => true,
            'payload' => $dapots
        ];

        return response()->json($response);
    }

    public function findBySiteID(Request $request): JsonResponse {
        $siteID = $request->input('site_id');
        $dapot = Dapot::where('site_id', $siteID)->first();

        $response = [
            'success' => true,
            'payload' => $dapot
        ];

        return response()->json($response);
    }
}
