<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $query = Classroom::withCount('students');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%")
                    ->orWhere('room_number', 'like', "%{$search}%")
                    ->orWhere('academic_year', 'like', "%{$search}%");
            });
        }

        $classrooms = $query->orderBy('grade_level')->orderBy('section')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Classes/Index', [
            'classrooms' => $classrooms,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Classes/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        DB::beginTransaction();

        try {
            Classroom::create($validated);
            DB::commit();

            return redirect()->route('classes.index')
                ->with('success', 'Class created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(Classroom $classroom)
    {
        $classroom->load(['students' => function ($query) {
            $query->select('id', 'class_id', 'student_code', 'first_name', 'last_name', 'status');
        }]);
        $classroom->loadCount('students');

        return Inertia::render('Classes/Show', [
            'classroom' => $classroom,
        ]);
    }

    public function edit(Classroom $classroom)
    {
        return Inertia::render('Classes/Edit', [
            'classroom' => $classroom,
        ]);
    }

    public function update(Request $request, Classroom $classroom)
    {
        $validated = $this->validateData($request, $classroom->id);

        DB::beginTransaction();

        try {
            $classroom->update($validated);
            DB::commit();

            return redirect()->route('classes.show', $classroom->uuid)
                ->with('success', 'Class updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy(Classroom $classroom)
    {
        DB::beginTransaction();

        try {
            $classroom->delete();
            DB::commit();

            return redirect()->route('classes.index')
                ->with('success', 'Class deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    private function validateData(Request $request, ?int $classroomId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'section' => [
                'required',
                'string',
                'max:10',
                Rule::unique('classes', 'section')
                    ->where(fn ($query) => $query
                        ->where('grade_level', $request->integer('grade_level'))
                        ->where('academic_year', $request->string('academic_year')->toString()))
                    ->ignore($classroomId),
            ],
            'capacity' => ['required', 'integer', 'min:1'],
            'room_number' => ['nullable', 'string', 'max:20'],
            'academic_year' => ['required', 'string', 'max:9'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
