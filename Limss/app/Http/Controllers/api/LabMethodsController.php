<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabMethod;

class LabMethodsController extends Controller
{
    public function handle(Request $request, $id = null)
    {
        if ($id !== null && $request->isMethod('get')) {
            return $this->getRow($id);
        }

        switch ($request->method()) {
            case 'GET':
                return $this->index();
            case 'POST':
                return $this->create($request);
            case 'PUT':
                return $this->update($request, $id);
            case 'DELETE':
                return $this->delete($request, $id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }

    private function index()
    {
        return response()->json(LabMethod::all());
    }

    private function getRow($id)
    {
        return response()->json(LabMethod::with('analytes')->find($id));
    }

    private function create(Request $request)
    {
        $data = $request->validate([
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'StandardDoc' => 'nullable|string|max:255',
            'LOD' => 'nullable|numeric',
            'LOQ' => 'nullable|numeric',
            'Uncertainty' => 'nullable|numeric',
            'DecimalPlaces' => 'nullable|integer|min:0|max:10',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $row = LabMethod::create($data);

        return response()->json([
            'status' => 200,
            'message' => "Ma'lumot muvafaqiyatli qo'shildi",
            'unit' => $row,
        ]);
    }

    private function update(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:lab_methods,id',
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'StandardDoc' => 'nullable|string|max:255',
            'LOD' => 'nullable|numeric',
            'LOQ' => 'nullable|numeric',
            'Uncertainty' => 'nullable|numeric',
            'DecimalPlaces' => 'nullable|integer|min:0|max:10',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $row = LabMethod::find($request->id);
        $row->update($data);

        return response()->json([
            'status' => 200,
            'message' => "Ma'lumot muvafaqiyatli yangilandi",
            'unit' => $row,
        ]);
    }

    public function delete(Request $request, $id)
    {
        try {
            LabMethod::findOrFail($id)->delete();
            return response()->json(['status' => 200, 'message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
