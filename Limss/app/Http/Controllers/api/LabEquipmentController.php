<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\LabInstrument;
use App\Models\LabInstrumentEvent;
use App\Models\LabInstrumentFile;
use App\Models\LabLaboratory;
use Carbon\Carbon;

class LabEquipmentController extends Controller
{
    /** Вычисляемые признаки: просрочка поверки/ТО и блокировка использования. */
    private function decorate($m, $labs)
    {
        $today = Carbon::today();
        $warnDays = (int) config('lims.calibration_warn_days', 30);
        $verDue = $m->VerificationDue ? Carbon::parse($m->VerificationDue) : null;
        $mntDue = $m->NextMaintenance ? Carbon::parse($m->NextMaintenance) : null;
        $m->CalibrationOverdue = $verDue ? $verDue->lt($today) : false;
        $m->MaintenanceOverdue = $mntDue ? $mntDue->lt($today) : false;
        // Заблаговременное напоминание (ТЗ 1.5): поверка/ТО в пределах окна предупреждения.
        $m->DaysToVerification = $verDue ? $today->diffInDays($verDue, false) : null;
        $m->CalibrationDueSoon = $verDue ? (!$verDue->lt($today) && $verDue->lte($today->copy()->addDays($warnDays))) : false;
        $m->MaintenanceDueSoon = $mntDue ? (!$mntDue->lt($today) && $mntDue->lte($today->copy()->addDays($warnDays))) : false;
        // Блокировка: вне эксплуатации / ожидает калибровки / просрочена поверка.
        $m->Blocked = in_array($m->Status, ['out_of_service', 'awaiting_calibration'], true) || $m->CalibrationOverdue;
        $m->LaboratoryName = optional($labs->get($m->LaboratoryID))->Name;
        $m->LaboratoryNameRus = optional($labs->get($m->LaboratoryID))->NameRus;
        return $m;
    }

    public function index(Request $request)
    {
        $q = LabInstrument::query()->orderBy('Name');
        if ($request->filled('status')) {
            $q->where('Status', $request->status);
        }
        if ($request->filled('LaboratoryID')) {
            $q->where('LaboratoryID', (int) $request->LaboratoryID);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $q->where(function ($w) use ($term) {
                $w->where('Name', 'like', $term)->orWhere('NameRus', 'like', $term)
                    ->orWhere('SerialNumber', 'like', $term)->orWhere('InventoryNo', 'like', $term);
            });
        }
        $labs = LabLaboratory::all()->keyBy('id');
        $rows = $q->get()->map(fn ($m) => $this->decorate($m, $labs));
        return response()->json($rows);
    }

    public function show($id)
    {
        $m = LabInstrument::find($id);
        if (!$m) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $labs = LabLaboratory::all()->keyBy('id');
        $this->decorate($m, $labs);
        $m->events = LabInstrumentEvent::where('InstrumentID', $id)->orderByDesc('EventDate')->orderByDesc('id')->get();
        $m->files = LabInstrumentFile::where('InstrumentID', $id)->orderByDesc('id')->get();
        return response()->json($m);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'LaboratoryID' => 'nullable|integer',
            'Code' => 'nullable|string|max:255',
            'Name' => 'required|string|max:255',
            'NameRus' => 'nullable|string|max:255',
            'Model' => 'nullable|string|max:255',
            'SerialNumber' => 'nullable|string|max:255',
            'Location' => 'nullable|string|max:255',
            'Supplier' => 'nullable|string|max:255',
            'InventoryNo' => 'nullable|string|max:255',
            'CommissionDate' => 'nullable|date',
            'Status' => 'nullable|string|max:50',
            'VerificationDate' => 'nullable|date',
            'VerificationDue' => 'nullable|date',
            'NextMaintenance' => 'nullable|date',
            'Comment' => 'nullable|string|max:1000',
        ]);
    }

    public function register(Request $request)
    {
        $data = $this->validateData($request);
        $data['Status'] = $data['Status'] ?? 'working';
        $m = LabInstrument::create($data);
        return response()->json(['status' => 200, 'message' => 'Asbob qo\'shildi', 'unit' => $m]);
    }

    public function update(Request $request, $id)
    {
        $m = LabInstrument::findOrFail($id);
        $m->update($this->validateData($request));
        return response()->json(['status' => 200, 'message' => 'Yangilandi', 'unit' => $m]);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate(['Status' => 'required|string|max:50']);
        $m = LabInstrument::findOrFail($id);
        $m->update(['Status' => $data['Status']]);
        return response()->json(['status' => 200, 'message' => 'Status', 'unit' => $m]);
    }

    /** Добавить событие; калибровка/поверка/ТО обновляют даты прибора. */
    public function addEvent(Request $request, $id)
    {
        $data = $request->validate([
            'EventType' => 'nullable|string|max:50',
            'EventDate' => 'nullable|date',
            'Description' => 'nullable|string|max:1000',
            'PerformedBy' => 'nullable|string|max:255',
            'NextDate' => 'nullable|date',
        ]);
        $m = LabInstrument::findOrFail($id);
        $ev = LabInstrumentEvent::create(array_merge($data, [
            'InstrumentID' => $id,
            'PerformedBy' => $data['PerformedBy'] ?? optional($request->user())->name,
        ]));

        // Автообновление дат прибора по типу события.
        if (in_array($data['EventType'] ?? '', ['calibration', 'verification'], true)) {
            $m->VerificationDate = $data['EventDate'] ?? $m->VerificationDate;
            if (!empty($data['NextDate'])) {
                $m->VerificationDue = $data['NextDate'];
            }
            // Выход из статуса «ожидает калибровки».
            if ($m->Status === 'awaiting_calibration') {
                $m->Status = 'working';
            }
            $m->save();
        } elseif (($data['EventType'] ?? '') === 'maintenance') {
            if (!empty($data['NextDate'])) {
                $m->NextMaintenance = $data['NextDate'];
            }
            if ($m->Status === 'maintenance') {
                $m->Status = 'working';
            }
            $m->save();
        }

        return response()->json(['status' => 200, 'message' => 'Event', 'unit' => $ev]);
    }

    public function uploadFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|max:20480', // до 20 МБ
            'FileType' => 'nullable|string|max:50',
        ]);
        LabInstrument::findOrFail($id);
        $file = $request->file('file');
        $path = $file->store("lab_equipment/{$id}");
        $rec = LabInstrumentFile::create([
            'InstrumentID' => $id,
            'FileType' => $request->input('FileType', 'other'),
            'OriginalName' => $file->getClientOriginalName(),
            'Path' => $path,
            'Size' => $file->getSize(),
            'UploadedBy' => optional($request->user())->name,
        ]);
        return response()->json(['status' => 200, 'message' => 'Uploaded', 'unit' => $rec]);
    }

    public function downloadFile($fileId)
    {
        $f = LabInstrumentFile::findOrFail($fileId);
        if (!Storage::exists($f->Path)) {
            return response()->json(['message' => 'File missing'], 404);
        }
        return Storage::download($f->Path, $f->OriginalName);
    }

    public function deleteFile($fileId)
    {
        $f = LabInstrumentFile::findOrFail($fileId);
        Storage::delete($f->Path);
        $f->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }

    public function destroy($id)
    {
        foreach (LabInstrumentFile::where('InstrumentID', $id)->get() as $f) {
            Storage::delete($f->Path);
        }
        LabInstrumentFile::where('InstrumentID', $id)->delete();
        LabInstrumentEvent::where('InstrumentID', $id)->delete();
        LabInstrument::where('id', $id)->delete();
        return response()->json(['status' => 200, 'message' => 'Deleted']);
    }
}
