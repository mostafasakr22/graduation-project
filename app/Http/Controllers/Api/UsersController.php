<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Models\Record;
use App\Models\Trip;
use App\Models\Trip_location;
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

        // 2. تجلب المعرفات
        $vehicleIds = Vehicle::where('user_id', $owner->id)->pluck('id');
        $driverIds = Driver::where('owner_id', $owner->id)->pluck('id'); // تأكد هل هو owner_id ولا user_id

        // 3. مسح الحوادث
        if ($vehicleIds->isNotEmpty()) {
            Crash::whereIn('vehicle_id', $vehicleIds)->delete();
        }

        // 4. مسح أماكن الرحلات (مهم جداً قبل مسح الرحلات نفسها)
        $tripIds = Trip::whereIn('vehicle_id', $vehicleIds)->orWhereIn('driver_id', $driverIds)->pluck('id');
        if ($tripIds->isNotEmpty()) {
            // لو عندك موديل اسمه TripLocation امسح منه الأول
             \DB::table('trip_locations')->whereIn('trip_id', $tripIds)->delete();
        }

        // 5. مسح الرحلات
        Trip::whereIn('id', $tripIds)->delete();

        // 6. مسح العربيات والسواقين
        Vehicle::whereIn('id', $vehicleIds)->delete();
        Driver::whereIn('id', $driverIds)->delete();
        
        // 7. مسح الإشعارات (عشان ميعملش تعارض مع جدول الـ notifications)
        $owner->notifications()->delete();

        // 8. مسح المالك نفسه
        $owner->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Owner and all related data deleted successfully'
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