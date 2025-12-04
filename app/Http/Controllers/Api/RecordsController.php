<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Record;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    // Show All Records
    public function index()
    {
        $records = Record::with('vehicle')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No records found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $records
        ], 200);
    }

    // Add Record
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'speed' => 'required|numeric|min:0',
            'engine_temp' => 'required|numeric|min:-50|max:200',
            'fuel_level' => 'required|numeric|min:0|max:100',
            'location' => 'nullable|string|max:255',
            'acceleration_x' => 'nullable|numeric|max:255',
            'acceleration_y' => 'nullable|numeric|max:255',
            'acceleration_z' => 'nullable|numeric|max:255',
        ]);

        $validated['recorded_at'] = now();

        $record = Record::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Record added successfully',
            'data' => $record
        ], 201);
    }

    // Delete Record
    public function delete($id)
    {
        $record = Record::find($id);

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found'
            ], 404);
        }

        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully'
        ], 200);
    }
}
