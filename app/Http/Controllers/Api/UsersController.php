<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $owner = User::where('role', 'owner')->find($id);

        if (!$owner) {
            return response()->json(['status' => 'fail', 'data' => ['message' => 'Owner not found']], 404);
        }

        $owner->delete();

        return response()->json(['status' => 'success', 'data' => null]);
    }
}