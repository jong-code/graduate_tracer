<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index()
    {
        return view('admin.school-years.index', ['schoolYears' => SchoolYear::orderByDesc('label')->get()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:20'],
        ]);

        SchoolYear::create($validated);

        return back()->with('status', 'School year added.');
    }

    public function setCurrent(SchoolYear $schoolYear)
    {
        SchoolYear::query()->update(['is_current' => false]);
        $schoolYear->update(['is_current' => true]);

        return back()->with('status', "{$schoolYear->label} set as the current school year.");
    }

    public function destroy(SchoolYear $schoolYear)
    {
        $schoolYear->delete();

        return back()->with('status', 'School year removed.');
    }
}
