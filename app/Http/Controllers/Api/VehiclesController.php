<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiclesController extends Controller
{
    // عرض كل العربيات الخاصة بالمستخدم اللي داخل
    public function index()
    {
        $vehicles = Vehicle::where('user_id', Auth::id())->get();
        return response()->json($vehicles);
    }

    // إضافة عربية جديدة
    public function store(Request $request)
    {
        $data = $request->validate([
            'plate_number' => 'required|string|unique:vehicles',
            'model' => 'required|string',
            'year' => 'nullable|integer'
        ]);

        $vehicle = Vehicle::create([
            'user_id' => Auth::id(),
            'plate_number' => $data['plate_number'],
            'model' => $data['model'],
            'year' => $data['year'] ?? null,
        ]);

        return response()->json($vehicle, 201);
    }

    // عرض عربية معينة
    public function show($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($vehicle);
    }

    // تحديث عربية
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);

        $data = $request->validate([
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'model' => 'sometimes|string',
            'year' => 'sometimes|integer',
        ]);

        $vehicle->update($data);

        return response()->json($vehicle);
    }

    // حذف عربية
    public function destroy($id)
    {
        $vehicle = Vehicle::where('user_id', Auth::id())->findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }
}
