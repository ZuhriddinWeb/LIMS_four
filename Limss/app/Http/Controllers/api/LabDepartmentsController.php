<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LabDepartment;

class LabDepartmentsController extends Controller
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
        return response()->json(LabDepartment::all());
    }

    private function getRow($id)
    {
        return response()->json(LabDepartment::find($id));
    }

    private function create(Request $request)
    {
        $data = $request->validate([
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $row = LabDepartment::create($data);

        return response()->json([
            'status' => 200,
            'message' => "Ma'lumot muvafaqiyatli qo'shildi",
            'unit' => $row,
        ]);
    }

    private function update(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:lab_departments,id',
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'ShortName' => 'nullable|string|max:255',
            'ShortNameRus' => 'nullable|string|max:255',
            'Comment' => 'nullable|string|max:1000',
        ]);

        $row = LabDepartment::find($request->id);
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
            LabDepartment::findOrFail($id)->delete();
            return response()->json(['status' => 200, 'message' => 'Deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}
