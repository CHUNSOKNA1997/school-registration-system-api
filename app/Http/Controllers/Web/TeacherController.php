<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $query = Teacher::query();

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        $teachers = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Teachers/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        DB::beginTransaction();

        try {
            Teacher::create($validated);
            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', 'Teacher created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        $teacher = Teacher::withCount(['subjects'])->findOrFail($id);

        return Inertia::render('Teachers/Show', [
            'teacher' => TeacherResource::make($teacher)->resolve(),
        ]);
    }

    public function edit($id)
    {
        $teacher = Teacher::findOrFail($id);

        return Inertia::render('Teachers/Edit', [
            'teacher' => $teacher,
        ]);
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $validated = $this->validateData($request, $teacher->id);

        DB::beginTransaction();

        try {
            $teacher->update($validated);
            DB::commit();

            return redirect()->route('teachers.show', $teacher->id)
                ->with('success', 'Teacher updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        DB::beginTransaction();

        try {
            $teacher->delete();
            DB::commit();

            return redirect()->route('teachers.index')
                ->with('success', 'Teacher deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function validateData(Request $request, ?int $teacherId = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'khmer_name' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'nationality' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => [
                'nullable',
                'email',
                Rule::unique('teachers', 'email')->ignore($teacherId),
            ],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            'current_address' => ['nullable', 'string'],
            'permanent_address' => ['nullable', 'string'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
            'employment_type' => ['required', 'string', 'in:full_time,part_time,contract'],
            'photo' => ['nullable', 'string'],
            'documents' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
