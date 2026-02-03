<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
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

        // 1. Validation
        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'email'           => ['sometimes', 'email', Rule::unique('users')->ignore($owner->id)],
            'phone_number'    => ['sometimes', 'string', Rule::unique('users')->ignore($owner->id)],
            'national_number' => ['sometimes', 'string', Rule::unique('users')->ignore($owner->id)],
            // التحقق من الصورة (نوعها وحجمها 2 ميجا)
            'profile_image'   => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'fail', 'data' => $validator->errors()], 422);
        }

        // 2. تجهيز البيانات للتحديث
        $data = $request->except(['profile_image']); 

        // 3. التعامل مع الصورة (لو تم رفع صورة جديدة)
        if ($request->hasFile('profile_image')) {
            // أ) مسح الصورة القديمة (عشان نوفر مساحة)
            if ($owner->profile_image) {
                // تأكد إنك عامل import: use Illuminate\Support\Facades\Storage;
                Storage::disk('public')->delete($owner->profile_image);
            }

            // ب) تخزين الصورة الجديدة وحفظ مسارها
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $data['profile_image'] = $path;
        }

        // 4. تنفيذ التحديث
        $owner->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Owner updated successfully',
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
        $driverIds = Driver::where('user_id', $owner->id)->pluck('id');

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

    // جلب الإشعارات (للمالك)
    public function getNotifications(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $user->unreadNotifications->count(),
                'notifications' => $user->notifications // ده بيرجع كل حاجة (مقروء وغير مقروء)
            ]
        ]);
    }

    // تعليم الكل كمقروء (لما يفتح الصفحة)
    public function markRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['status' => 'success']);
    }
}