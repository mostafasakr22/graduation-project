<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecordsController extends Controller
{
    public function index()
    {
        $records = Record::whereHas('vehicle', function($q){
            $q->where('user_id', Auth::id());
        })->latest()->get();

        return response()->json($records);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'speed' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'acceleration_x' => 'nullable|numeric',
            'acceleration_y' => 'nullable|numeric',
            'acceleration_z' => 'nullable|numeric',
            'recorded_at' => 'nullable|date',
        ]);

        $record = Record::create($data);
        return response()->json($record, 201);
    }
}
