<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\CustomerAssignment;
use App\Models\RouteStop;
use App\Models\SalesRoute;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Section 2 relasi: Arrival/Check-In -> Visit -> Check-Out -> Next Customer
class VisitController extends Controller
{
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();

        $assignment = CustomerAssignment::where('user_id', $user->id)
            ->where('customer_id', $request->input('customer_id'))
            ->whereDate('assigned_date', now()->toDateString())
            ->first();

        // Section 8/multi-cabang: pastikan customer memang milik assignment Sales ini hari ini.
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ini tidak ada dalam assignment kamu hari ini.',
            ], 403);
        }

        $visit = Visit::create([
            'user_id' => $user->id,
            'customer_id' => $request->input('customer_id'),
            'customer_assignment_id' => $assignment->id,
            'check_in_latitude' => $request->input('latitude'),
            'check_in_longitude' => $request->input('longitude'),
            'check_in_at' => now(),
            'status' => 'in_progress',
        ]);

        $assignment->update(['status' => 'visited']);

        // Section 90-an: sinkronkan status di route_stops juga, supaya RouteMapActivity
        // tahu stop mana yang sudah dikunjungi (dipakai untuk panduan "stop berikutnya").
        $todayRoute = SalesRoute::where('user_id', $user->id)
            ->whereDate('route_date', now()->toDateString())
            ->first();
        if ($todayRoute) {
            RouteStop::where('sales_route_id', $todayRoute->id)
                ->where('customer_id', $request->input('customer_id'))
                ->update(['status' => 'arrived']);
        }

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }

    public function checkOut(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visit_id' => 'required|exists:visits,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $user = $request->user();

        $visit = Visit::where('id', $request->input('visit_id'))
            ->where('user_id', $user->id)
            ->first();

        if (!$visit) {
            return response()->json(['success' => false, 'message' => 'Visit tidak ditemukan.'], 404);
        }

        $visit->update([
            'check_out_latitude' => $request->input('latitude'),
            'check_out_longitude' => $request->input('longitude'),
            'check_out_at' => now(),
            'status' => 'completed',
        ]);

        return response()->json(['success' => true, 'visit_id' => $visit->id]);
    }
}
