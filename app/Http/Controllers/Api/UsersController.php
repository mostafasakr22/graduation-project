<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Record;
use App\Models\Trip;
use App\Models\Crash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{

    // 2. Show One Owner 
    public function show($id)
    {
        $owner = User::where('role', 'owner')
                     ->withCount(['vehicles', 'drivers'])
                     ->find($id);

        if (!$owner) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Owner not found']], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['owner' => $owner]
        ]);
    }

    // 3. Update Owner
    public function update(Request $request, $id)
    {
        $owner = User::where('role', 'owner')->find($id);

        if (!$owner) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Owner not found']], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'email'           => ['sometimes', 'email', Rule::unique('users')->ignore($owner->id)],
            'phone_number'    => ['sometimes', 'string', Rule::unique('users')->ignore($owner->id)],
            'national_number' => ['sometimes', 'string', Rule::unique('users')->ignore($owner->id)],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        $owner->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Updated successfully',
            'data' => ['owner' => $owner]
        ]);
    }

    // 4. Delete Owner
    public function delete($id)
      {
        // 1. البحث عن المالك
        $owner = User::where('role', 'owner')->find($id);

        if (!$owner) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Owner not found']], 404);
        }

        // 2. تجهيز القوائم (IDs) للمتعلقات
        // بنجيب أرقام العربيات والسواقين عشان نعرف نمسح الحاجات المرتبطة بيهم الأول
        $vehicleIds = Vehicle::where('user_id', $owner->id)->pluck('id');
        $driverIds  = Driver::where('user_id', $owner->id)->pluck('id');

        // 3. مسح الحوادث (Crashes)
        // لأن الحادثة مرتبطة بالعربية، فلازم تتمسح قبل العربية
        if ($vehicleIds->count() > 0) {
            Crash::whereIn('vehicle_id', $vehicleIds)->delete();
        }

        // 4. مسح الرحلات (Trips)
        // لأن الرحلة مرتبطة بالعربية والسواق
        if ($vehicleIds->count() > 0 || $driverIds->count() > 0) {
            Trip::whereIn('vehicle_id', $vehicleIds)
                            ->orWhereIn('driver_id', $driverIds)
                            ->delete();
        }

        // 5. مسح السجلات (Records)
        if ($vehicleIds->count() > 0) {
            Record::whereIn('vehicle_id', $vehicleIds)->delete();
        }

        // 6. مسح العربيات والسواقين 
        Vehicle::whereIn('id', $vehicleIds)->delete();
        Driver::whereIn('id', $driverIds)->delete();

        // 7. مسح المالك
        $owner->delete();

        return response()->json([
            'status' => 'success',
            'data' => null,
            'message' => 'Owner deleted successfully'
        ]);
    }
}