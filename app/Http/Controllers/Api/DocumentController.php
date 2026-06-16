<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index()
    {
        return Document::with('category', 'department', 'user')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_category_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]);

        $document = Document::create($data + ['user_id' => $request->user()?->id]);

        ActivityLog::create([
            'user_id' => $request->user()?->id,
            'action' => 'Criou documento',
            'entity' => 'Document',
            'entity_id' => $document->id,
            'metadata' => ['title' => $document->title],
        ]);

        return $document;
    }

    public function show(Document $document)
    {
        return $document->load('category', 'department', 'user');
    }

    public function update(Request $request, Document $document)
    {
        $document->update($request->validate([
            'document_category_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'required', 'string', 'max:50'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ]));

        return $document->load('category', 'department', 'user');
    }

    public function destroy(Document $document)
    {
        $document->delete();

        return response()->noContent();
    }
}
