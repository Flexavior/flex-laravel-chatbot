<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadController extends Controller
{
    public function showUploadForm()
    {
        return view('upload');
    }

    public function handleFileUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:txt,pdf|max:81920',
        ]);

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('uploads', $fileName);

        // Save file details to the database
        UploadedFile::create([
            'file_name' => $fileName,
            'file_path' => $filePath
        ]);

        return redirect()->back()->with('success', 'File uploaded successfully.');
    }
}