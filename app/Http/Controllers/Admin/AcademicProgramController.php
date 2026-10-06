<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Department;
use Illuminate\Http\Request;

class AcademicProgramController extends Controller
{
    public function index()
    {
        return view('admin.programs.index', [
            'programs' => AcademicProgram::with('department')->orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:department,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_programs,code'],
        ]);

        AcademicProgram::create($validated);

        return back()->with('status', 'Program added.');
    }

    public function update(Request $request, AcademicProgram $academicProgram)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:department,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_programs,code,' . $academicProgram->id],
            'is_active' => ['required', 'boolean'],
        ]);

        $academicProgram->update($validated);

        return back()->with('status', 'Program updated.');
    }

    public function destroy(AcademicProgram $academicProgram)
    {
        $academicProgram->delete();

        return back()->with('status', 'Program removed.');
    }
}
