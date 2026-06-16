<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        return Department::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]));
    }

    public function show(Department $department)
    {
        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $department->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:departments,code,' . $department->id],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ]));

        return $department;
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return response()->noContent();
    }
}
