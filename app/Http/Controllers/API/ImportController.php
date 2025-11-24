<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Jobs\ProcessServiceRequestImport;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $file = $request->file('file');

        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('imports', $filename);

        // Dispatch the queued job
        ProcessServiceRequestImport::dispatch($path, $request->user()->id);

        return response()->json(['message' => 'Import started']);
    }

    public function summary(Request $request)
    {
        // Use the newly renamed relationship method: importLogs()
        $lastImport = $request->user()->importLogs()->latest()->first();

        if (!$lastImport) {
            return response()->json(['summary' => null]);
        }
        
        // Use the correct database column name: summary_json
        return response()->json(['summary' => $lastImport->summary_json]);
    }
}
