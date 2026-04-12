<?php

namespace App\Http\Controllers;

use App\Services\GhnService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function provinces()
    {
        return response()->json((new GhnService())->getProvinces());
    }

    public function districts(Request $request)
    {
        $request->validate(['province_id' => 'required|integer']);
        return response()->json((new GhnService())->getDistricts($request->province_id));
    }

    public function wards(Request $request)
    {
        $request->validate(['district_id' => 'required|integer']);
        return response()->json((new GhnService())->getWards($request->district_id));
    }

    public function calculateFee(Request $request)
    {
        $request->validate([
            'to_district_id' => 'required|integer',
            'to_ward_code'   => 'required|string',
            'weight'         => 'nullable|integer',
            'total'          => 'nullable|numeric',
        ]);

        $fee = (new GhnService())->calculateFee(
            toDistrictId:   $request->to_district_id,
            toWardCode:     $request->to_ward_code,
            weight:         $request->weight ?? 500,
            insuranceValue: (int) $request->total,
        );

        return response()->json(['shipping_fee' => $fee ?? 30000]);
    }
}