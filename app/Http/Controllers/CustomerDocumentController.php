<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomerDocumentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'document_requirement_id' => 'required|exists:document_requirements,id',
            'file' => 'required|file|max:51200', // 50MB limit
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('customer_documents/' . $validated['customer_id'], 'public');

            if (!$filePath) {
                return redirect()->back()->with('error', 'Failed to save file to storage.');
            }

            CustomerDocument::create([
                'customer_id' => $validated['customer_id'],
                'document_requirement_id' => $validated['document_requirement_id'],
                'file_path' => $filePath,
                'file_name' => $fileName,
                'status' => 'Pending',
            ]);

            return redirect()->back()->with('success', 'Document uploaded successfully.');
        }

        return redirect()->back()->with('error', 'No file was uploaded.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerDocument $customerDocument)
    {
        // Delete the file from storage
        if ($customerDocument->file_path) {
            Storage::disk('public')->delete($customerDocument->file_path);
        }

        // Delete the record from the database
        $customerDocument->delete();

        return redirect()->back()->with('success', 'Document deleted successfully.');
    }
}
