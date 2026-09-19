<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabStorageLocation;

class LabStorageLocationsController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return response()->json(LabStorageLocation::find($id));
        }
        switch ($request->method()) {
            case 'GET':
                return response()->json(LabStorageLocation::orderBy('NameRus')->orderBy('Name')->get());
            case 'POST':
                return $this->save($request);
            case 'PUT':
                return $this->save($request, $request->id);
            case 'DELETE':
                return $this->delete($id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function save(Request $request, $id = null)
    {
        $data = $request->validate([
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'LocationType' => 'nullable|string|max:50',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $row = $id ? LabStorageLocation::findOrFail($id) : new LabStorageLocation();
        $row->fill($data)->save();

        return response()->json([
            'status' => 200,
            'message' => $id ? 'Yangilandi' : "Qo'shildi",
            'unit' => $row,
        ]);
    }

    private function delete($id)
    {
        LabStorageLocation::findOrFail($id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }
}
