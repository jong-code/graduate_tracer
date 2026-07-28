<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use Illuminate\Http\Request;

class AcademicProgramController extends Controller
{
    public function index()
    {
        return view('admin.programs.index', ['programs' => AcademicProgram::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_programs,code'],
            'college' => ['nullable', 'string', 'max:255'],
        ]);

        AcademicProgram::create($validated);

        return back()->with('status', 'Program added.');
    }

    public function update(Request $request, AcademicProgram $academicProgram)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:academic_programs,code,' . $academicProgram->id],
            'college' => ['nullable', 'string', 'max:255'],
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
